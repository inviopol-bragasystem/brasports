<?php

namespace app\modules\api\controllers;

use app\core\Controller;
use app\core\crud\Conn;
use app\models\CassinoModel;
use app\services\CassinoAgregadorService;
use PDO;
use Exception;

class cassinoController extends Controller
{
    /**
     * Webhook oficial assinado da carteira única (GamesVIP / Acelora)
     * URL: https://banca.0um.online/api/cassino/wallet
     */
    public function walletAction()
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Content-Type: application/json; charset=utf-8');

        $rawBody = file_get_contents('php://input');

        // Validação de Assinatura HMAC SHA256 do Agregador
        if (!$this->validAceloraSignature($rawBody)) {
            http_response_code(401);
            return ['status' => false, 'msg' => 'INVALID_SIGNATURE'];
        }

        $data = json_decode($rawBody, true);
        if (!is_array($data)) {
            http_response_code(400);
            return ['status' => false, 'msg' => 'INVALID_JSON'];
        }

        $method = trim((string)($data['method'] ?? ''));
        $userCode = trim((string)($data['user_code'] ?? ''));
        $providerCode = strtoupper(trim((string)($data['provider_code'] ?? '')));

        if (empty($userCode)) {
            http_response_code(422);
            return ['status' => false, 'msg' => 'INVALID_USER'];
        }

        // Suporte a jogadores convidados / modo demonstração
        if (str_starts_with((string)$userCode, 'guest_')) {
            if ($method === 'user_balance') {
                return [
                    'status' => true,
                    'msg' => 'SUCCESS',
                    'balance' => 1000.00
                ];
            }
            if ($method === 'game_callback') {
                return [
                    'status' => true,
                    'msg' => 'SUCCESS',
                    'balance' => 1000.00
                ];
            }
        }

        $pdo = Conn::getConn();

        // 1. Consulta de Saldo (user_balance)
        if ($method === 'user_balance') {
            $stmt = $pdo->prepare("SELECT credito FROM sis_users WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => (int)$userCode]);
            $credito = $stmt->fetchColumn();

            if ($credito === false) {
                http_response_code(404);
                return ['status' => false, 'msg' => 'USER_NOT_FOUND'];
            }

            return [
                'status' => true,
                'msg' => 'SUCCESS',
                'balance' => round((float)$credito, 2)
            ];
        }

        // 2. Processamento de Rodada (game_callback)
        if ($method !== 'game_callback' || !is_array($data['slot'] ?? null)) {
            http_response_code(422);
            return ['status' => false, 'msg' => 'INVALID_METHOD'];
        }

        $slot = $data['slot'];
        $bet = round(max(0, (float)($slot['bet'] ?? 0)), 2);
        $win = round(max(0, (float)($slot['win'] ?? 0)), 2);
        $txnId = trim((string)($slot['txn_id'] ?? ''));
        $roundId = trim((string)($slot['round_id'] ?? $txnId));
        $gameCode = trim((string)($slot['game_code'] ?? 'unknown'));

        if (empty($txnId)) {
            http_response_code(422);
            return ['status' => false, 'msg' => 'INVALID_TRANSACTION'];
        }

        $eventId = hash('sha256', $providerCode . '|' . $txnId);
        $requestHash = hash('sha256', $rawBody);

        try {
            // Idempotência: verifica se esta transação já foi processada
            $stmtChk = $pdo->prepare("SELECT status, response_json FROM gamesvip_webhook_events WHERE event_id = :eid LIMIT 1");
            $stmtChk->execute(['eid' => $eventId]);
            $existing = $stmtChk->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $stored = json_decode((string)$existing['response_json'], true);
                if (is_array($stored)) {
                    return $stored;
                }
                return ['status' => true, 'msg' => 'TRANSACTION_ALREADY_PROCESSED'];
            }

            // Inicia transação atômica no banco de dados
            $pdo->beginTransaction();

            // Registra evento em processamento
            $insEvent = $pdo->prepare("INSERT INTO gamesvip_webhook_events 
                (event_id, user_id, provider_code, game_code, round_id, transaction_id, bet, win, request_hash, status, created_at)
                VALUES 
                (:eid, :uid, :prov, :gcode, :rid, :tx, :bet, :win, :rhash, 'processing', NOW())");
            $insEvent->execute([
                'eid' => $eventId,
                'uid' => (int)$userCode,
                'prov' => $providerCode,
                'gcode' => $gameCode,
                'rid' => $roundId,
                'tx' => $txnId,
                'bet' => $bet,
                'win' => $win,
                'rhash' => $requestHash
            ]);

            // Bloqueia e busca saldo atual do usuário
            $stmtUser = $pdo->prepare("SELECT id, credito, nome FROM sis_users WHERE id = :id FOR UPDATE");
            $stmtUser->execute(['id' => (int)$userCode]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $pdo->rollBack();
                $this->finishEvent($eventId, 404, ['status' => false, 'msg' => 'USER_NOT_FOUND']);
                http_response_code(404);
                return ['status' => false, 'msg' => 'USER_NOT_FOUND'];
            }

            $saldoAnterior = (float)$user['credito'];

            if ($bet > $saldoAnterior) {
                $pdo->rollBack();
                $resp = [
                    'status' => false,
                    'msg' => 'INSUFFICIENT_BALANCE',
                    'balance' => round($saldoAnterior, 2)
                ];
                $this->finishEvent($eventId, 409, $resp);
                http_response_code(409);
                return $resp;
            }

            // Novo saldo: deduz aposta e credita ganho
            $novoSaldo = max(0, $saldoAnterior - $bet) + $win;

            // Atualiza crédito do usuário na tabela principal de usuários
            $upd = $pdo->prepare("UPDATE sis_users SET credito = :saldo, `update` = NOW() WHERE id = :id");
            $upd->execute(['saldo' => $novoSaldo, 'id' => (int)$userCode]);

            // Busca título do jogo
            $game = CassinoModel::getGameByCode($gameCode);
            $gameNome = $game ? $game['name'] : $gameCode;

            // Grava histórico de aposta (bet)
            if ($bet > 0) {
                $insBet = $pdo->prepare("INSERT INTO casino_history 
                    (user_id, game_code, game_name, provider, round_id, txn_id, action, amount, balance_before, balance_after, status, created_at) 
                    VALUES 
                    (:uid, :gcode, :gname, :prov, :round, :tx, 'bet', :amt, :bb, :ba, 'completed', NOW())");
                $insBet->execute([
                    'uid' => (int)$userCode,
                    'gcode' => $gameCode,
                    'gname' => $gameNome,
                    'prov' => $providerCode,
                    'round' => $roundId,
                    'tx' => $txnId . '_bet',
                    'amt' => $bet,
                    'bb' => $saldoAnterior,
                    'ba' => $saldoAnterior - $bet
                ]);
            }

            // Grava histórico de ganho (win)
            if ($win > 0) {
                $insWin = $pdo->prepare("INSERT INTO casino_history 
                    (user_id, game_code, game_name, provider, round_id, txn_id, action, amount, balance_before, balance_after, status, created_at) 
                    VALUES 
                    (:uid, :gcode, :gname, :prov, :round, :tx, 'win', :amt, :bb, :ba, 'completed', NOW())");
                $insWin->execute([
                    'uid' => (int)$userCode,
                    'gcode' => $gameCode,
                    'gname' => $gameNome,
                    'prov' => $providerCode,
                    'round' => $roundId,
                    'tx' => $txnId . '_win',
                    'amt' => $win,
                    'bb' => max(0, $saldoAnterior - $bet),
                    'ba' => $novoSaldo
                ]);
            } elseif ($bet <= 0) {
                // Registro de transação informativa caso bet e win sejam 0
                $insZero = $pdo->prepare("INSERT INTO casino_history 
                    (user_id, game_code, game_name, provider, round_id, txn_id, action, amount, balance_before, balance_after, status, created_at) 
                    VALUES 
                    (:uid, :gcode, :gname, :prov, :round, :tx, 'bet', 0.00, :bb, :ba, 'completed', NOW())");
                $insZero->execute([
                    'uid' => (int)$userCode,
                    'gcode' => $gameCode,
                    'gname' => $gameNome,
                    'prov' => $providerCode,
                    'round' => $roundId,
                    'tx' => $txnId,
                    'bb' => $saldoAnterior,
                    'ba' => $novoSaldo
                ]);
            }

            $pdo->commit();

            $payloadResp = [
                'status' => true,
                'msg' => 'SUCCESS',
                'balance' => round($novoSaldo, 2)
            ];

            $this->finishEvent($eventId, 200, $payloadResp);
            return $payloadResp;

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(500);
            return ['status' => false, 'msg' => 'WALLET_ERROR', 'error' => $e->getMessage()];
        }
    }

    /**
     * Valida assinatura HMAC SHA256 dos headers X-Acelora-*
     */
    private function validAceloraSignature(string $rawBody): bool
    {
        $settings = CassinoAgregadorService::getSettings();
        $token = trim((string)($settings['agent_token'] ?? ''));
        $secret = trim((string)($settings['secret_key'] ?? ''));

        $providedToken = trim((string)($_SERVER['HTTP_X_ACELORA_AGENT'] ?? ''));
        $timestamp = trim((string)($_SERVER['HTTP_X_ACELORA_TIMESTAMP'] ?? ''));
        $signature = trim((string)($_SERVER['HTTP_X_ACELORA_SIGNATURE'] ?? ''));

        if (empty($token) || empty($secret) || !ctype_digit($timestamp)) {
            return false;
        }

        // Tolerância de 300 segundos
        if (abs(time() - (int)$timestamp) > 300) {
            return false;
        }

        $expected = 'v1=' . hash_hmac('sha256', $timestamp . '.' . $rawBody, $secret);
        return hash_equals($token, $providedToken) && hash_equals($expected, $signature);
    }

    /**
     * Atualiza o status do evento na tabela gamesvip_webhook_events
     */
    private function finishEvent(string $eventId, int $httpStatus, array $payload): void
    {
        try {
            $pdo = Conn::getConn();
            $upd = $pdo->prepare("UPDATE gamesvip_webhook_events SET 
                status = :st, 
                response_json = :resp, 
                updated_at = NOW() 
                WHERE event_id = :eid");
            $upd->execute([
                'st' => ($httpStatus === 200) ? 'completed' : 'rejected',
                'resp' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'eid' => $eventId
            ]);
        } catch (Exception $e) {}
    }
    /**
     * Webhook de consulta de saldo antes da rodada
     * URL: http://dominio/api/cassino/balance
     */
    public function balanceAction()
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true) ?: $_POST;

        $userCode = trim($data['user_code'] ?? ($data['user_id'] ?? ''));
        if (empty($userCode)) {
            return ['status' => 1, 'msg' => 'SUCCESS'];
        }

        $pdo = Conn::getConn();
        $stmt = $pdo->prepare("SELECT credito FROM sis_users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => (int)$userCode]);
        $credito = (float)$stmt->fetchColumn();

        return [
            'status' => 1,
            'msg' => 'SUCCESS',
            'user_balance' => round($credito, 2)
        ];
    }

    /**
     * Webhook de processamento de rodada (Aposta e Ganho) do PGV4
     * URL: http://dominio/api/cassino/callback
     */
    public function callbackAction()
    {
        try {
            $settings = CassinoModel::getSettings();
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true) ?: $_POST;

            // Suporte unificado para chamadas Pragmatic direcionadas a /api/cassino/callback
            if ((isset($data['method']) && in_array($data['method'], ['user_balance', 'game_callback', 'transaction'])) 
                || (isset($data['provider_code']) && strtoupper($data['provider_code']) === 'PRAGMATICPLAY')) {
                return $this->processPragmaticWebhook();
            }

            // Validação de Secret Key
            $expectedSecret = trim($settings['secret_key']);
            $receivedSecret = trim($data['agent_secret'] ?? ($data['secretKey'] ?? ''));

            if (!empty($expectedSecret) && !empty($receivedSecret) && $expectedSecret !== $receivedSecret) {
                http_response_code(401);
                return ['status' => 0, 'message' => 'Unauthorized'];
            }

            $userCode = trim((string)($data['user_code'] ?? ''));
            $slot = is_array($data['slot'] ?? null) ? $data['slot'] : [];

            $transactionId = trim((string)($slot['txn_id'] ?? ($data['txn_id'] ?? '')));
            $roundId = trim((string)($slot['round_id'] ?? ($data['round_id'] ?? $transactionId)));
            $gameCode = trim((string)($slot['game_code'] ?? ($data['game_code'] ?? 'PGSOFT')));
            $bet = round((float)($slot['bet'] ?? ($data['bet'] ?? 0)), 2);
            $win = round((float)($slot['win'] ?? ($data['win'] ?? 0)), 2);

            if (empty($userCode) || empty($transactionId)) {
                return ['status' => 0, 'message' => 'Invalid parameters.'];
            }

            $pdo = Conn::getConn();

            // Evitar processamento duplo da mesma transação
            $chk = $pdo->prepare("SELECT id FROM casino_history WHERE txn_id = :tx LIMIT 1");
            $chk->execute(['tx' => $transactionId]);
            if ($chk->fetchColumn()) {
                $stmtBal = $pdo->prepare("SELECT credito FROM sis_users WHERE id = :id LIMIT 1");
                $stmtBal->execute(['id' => (int)$userCode]);
                $saldo = (float)$stmtBal->fetchColumn();

                return [
                    'status' => 1,
                    'msg' => 'SUCCESS',
                    'user_balance' => round($saldo, 2)
                ];
            }

            // Inicia transação segura no banco de dados
            $pdo->beginTransaction();

            $stmtUser = $pdo->prepare("SELECT id, credito, nome FROM sis_users WHERE id = :id FOR UPDATE");
            $stmtUser->execute(['id' => (int)$userCode]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $pdo->rollBack();
                return ['status' => 0, 'message' => 'User not found.'];
            }

            $saldoAnterior = (float)$user['credito'];

            if ($bet > $saldoAnterior) {
                $pdo->rollBack();
                return ['status' => 0, 'message' => 'Insufficient balance.'];
            }

            // Calcula novo saldo: deduz aposta e soma prêmio
            $novoSaldo = max(0, $saldoAnterior - $bet) + $win;

            // Atualiza o saldo do usuário
            $upd = $pdo->prepare("UPDATE sis_users SET credito = :saldo, `update` = NOW() WHERE id = :id");
            $upd->execute(['saldo' => $novoSaldo, 'id' => (int)$userCode]);

            // Busca nome do jogo para o relatório
            $game = CassinoModel::getGameByCode($gameCode);
            $gameNome = $game ? $game['name'] : $gameCode;

            // Registra débito da aposta se houver
            if ($bet > 0) {
                $insBet = $pdo->prepare("INSERT INTO casino_history (user_id, game_code, game_name, provider, round_id, txn_id, action, amount, balance_before, balance_after, status, created_at) 
                    VALUES (:uid, :gcode, :gname, 'PGSOFT', :round, :tx, 'bet', :amt, :bb, :ba, 'completed', NOW())");
                $insBet->execute([
                    'uid' => (int)$userCode,
                    'gcode' => $gameCode,
                    'gname' => $gameNome,
                    'round' => $roundId,
                    'tx' => $transactionId . '_bet',
                    'amt' => $bet,
                    'bb' => $saldoAnterior,
                    'ba' => $saldoAnterior - $bet
                ]);
            }

            // Registra premiação do ganho se houver
            if ($win > 0) {
                $insWin = $pdo->prepare("INSERT INTO casino_history (user_id, game_code, game_name, provider, round_id, txn_id, action, amount, balance_before, balance_after, status, created_at) 
                    VALUES (:uid, :gcode, :gname, 'PGSOFT', :round, :tx, 'win', :amt, :bb, :ba, 'completed', NOW())");
                $insWin->execute([
                    'uid' => (int)$userCode,
                    'gcode' => $gameCode,
                    'gname' => $gameNome,
                    'round' => $roundId,
                    'tx' => $transactionId,
                    'amt' => $win,
                    'bb' => $saldoAnterior - $bet,
                    'ba' => $novoSaldo
                ]);
            } else if ($bet > 0) {
                // Caso não tenha ganho, marca a transação principal para idempotência
                $insTx = $pdo->prepare("INSERT INTO casino_history (user_id, game_code, game_name, provider, round_id, txn_id, action, amount, balance_before, balance_after, status, created_at) 
                    VALUES (:uid, :gcode, :gname, 'PGSOFT', :round, :tx, 'bet', :amt, :bb, :ba, 'completed', NOW())");
                $insTx->execute([
                    'uid' => (int)$userCode,
                    'gcode' => $gameCode,
                    'gname' => $gameNome,
                    'round' => $roundId,
                    'tx' => $transactionId,
                    'amt' => $bet,
                    'bb' => $saldoAnterior,
                    'ba' => $novoSaldo
                ]);
            }

            $pdo->commit();

            return [
                'status' => 1,
                'msg' => 'SUCCESS',
                'user_balance' => round($novoSaldo, 2)
            ];
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['status' => 0, 'message' => $e->getMessage()];
        }
    }

    /**
     * Webhook unificado da API Pragmatic Play
     * URL: http://dominio/api/cassino/pragmatic
     */
    public function pragmaticAction()
    {
        return $this->processPragmaticWebhook();
    }

    /**
     * Webhook com sufixo callback para compatibilidade de rotas
     * URL: http://dominio/api/cassino/pragmaticCallback
     */
    public function pragmaticCallbackAction()
    {
        return $this->processPragmaticWebhook();
    }

    /**
     * Processamento central de webhooks da Pragmatic Play (Seamless Wallet)
     */
    private function processPragmaticWebhook()
    {
        try {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true) ?: $_POST;

            $method = trim($data['method'] ?? '');
            $userCode = trim((string)($data['user_code'] ?? ($data['user_id'] ?? '')));

            $pdo = Conn::getConn();

            // MODO 1: Consulta de Saldo do Jogador (user_balance)
            if ($method === 'user_balance' || (empty($method) && !isset($data['slot']) && !isset($data['bet']))) {
                if (empty($userCode) || strpos($userCode, 'demo_') === 0) {
                    return [
                        'status' => 1,
                        'msg' => 'SUCCESS',
                        'user_balance' => 1000.00,
                        'balance' => 1000.00
                    ];
                }

                $stmt = $pdo->prepare("SELECT credito FROM sis_users WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => (int)$userCode]);
                $credito = (float)$stmt->fetchColumn();

                return [
                    'status' => 1,
                    'msg' => 'SUCCESS',
                    'user_balance' => round($credito, 2),
                    'balance' => round($credito, 2)
                ];
            }

            // MODO 2: Transação de Rodada (game_callback / transaction)
            $settings = \app\models\PragmaticModel::getSettings();
            $expectedSecret = trim($settings['secret_key'] ?? '');
            $receivedSecret = trim($data['agent_secret'] ?? ($data['secretKey'] ?? ''));

            if (!empty($expectedSecret) && !empty($receivedSecret) && $expectedSecret !== $receivedSecret) {
                http_response_code(401);
                return ['status' => 0, 'message' => 'Unauthorized'];
            }

            $slot = is_array($data['slot'] ?? null) ? $data['slot'] : [];

            $transactionId = trim((string)($slot['txn_id'] ?? ($data['txn_id'] ?? '')));
            $roundId = trim((string)($slot['round_id'] ?? ($data['round_id'] ?? $transactionId)));
            $gameCode = trim((string)($slot['game_code'] ?? ($data['game_code'] ?? 'PRAGMATIC')));
            $bet = round((float)($slot['bet'] ?? ($data['bet'] ?? 0)), 2);
            $win = round((float)($slot['win'] ?? ($data['win'] ?? 0)), 2);

            if (empty($userCode) || empty($transactionId)) {
                return ['status' => 0, 'message' => 'Invalid parameters.'];
            }

            // Jogador em modo demonstração
            if (strpos($userCode, 'demo_') === 0) {
                return [
                    'status' => 1,
                    'msg' => 'SUCCESS',
                    'user_balance' => 1000.00,
                    'balance' => 1000.00
                ];
            }

            // Idempotência: evitar duplicidade de débito/crédito
            $chk = $pdo->prepare("SELECT id FROM casino_history WHERE txn_id = :tx LIMIT 1");
            $chk->execute(['tx' => $transactionId]);
            if ($chk->fetchColumn()) {
                $stmtBal = $pdo->prepare("SELECT credito FROM sis_users WHERE id = :id LIMIT 1");
                $stmtBal->execute(['id' => (int)$userCode]);
                $saldo = (float)$stmtBal->fetchColumn();

                return [
                    'status' => 1,
                    'msg' => 'SUCCESS',
                    'user_balance' => round($saldo, 2),
                    'balance' => round($saldo, 2)
                ];
            }

            // Inicia transação segura no banco
            $pdo->beginTransaction();

            $stmtUser = $pdo->prepare("SELECT id, credito, nome FROM sis_users WHERE id = :id FOR UPDATE");
            $stmtUser->execute(['id' => (int)$userCode]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $pdo->rollBack();
                return ['status' => 0, 'message' => 'User not found.'];
            }

            $saldoAnterior = (float)$user['credito'];

            if ($bet > $saldoAnterior) {
                $pdo->rollBack();
                return ['status' => 0, 'message' => 'Insufficient balance.'];
            }

            // Calcula novo saldo
            $novoSaldo = max(0, $saldoAnterior - $bet) + $win;

            // Atualiza saldo do usuário
            $upd = $pdo->prepare("UPDATE sis_users SET credito = :saldo, `update` = NOW() WHERE id = :id");
            $upd->execute(['saldo' => $novoSaldo, 'id' => (int)$userCode]);

            // Busca nome amigável do jogo
            $game = CassinoModel::getGameByCode($gameCode);
            $gameNome = $game ? $game['name'] : $gameCode;

            // Registra aposta
            if ($bet > 0) {
                $insBet = $pdo->prepare("INSERT INTO casino_history (user_id, game_code, game_name, provider, round_id, txn_id, action, amount, balance_before, balance_after, status, created_at) 
                    VALUES (:uid, :gcode, :gname, 'PRAGMATIC', :round, :tx, 'bet', :amt, :bb, :ba, 'completed', NOW())");
                $insBet->execute([
                    'uid' => (int)$userCode,
                    'gcode' => $gameCode,
                    'gname' => $gameNome,
                    'round' => $roundId,
                    'tx' => $transactionId . '_bet',
                    'amt' => $bet,
                    'bb' => $saldoAnterior,
                    'ba' => $saldoAnterior - $bet
                ]);
            }

            // Registra ganho
            if ($win > 0) {
                $insWin = $pdo->prepare("INSERT INTO casino_history (user_id, game_code, game_name, provider, round_id, txn_id, action, amount, balance_before, balance_after, status, created_at) 
                    VALUES (:uid, :gcode, :gname, 'PRAGMATIC', :round, :tx, 'win', :amt, :bb, :ba, 'completed', NOW())");
                $insWin->execute([
                    'uid' => (int)$userCode,
                    'gcode' => $gameCode,
                    'gname' => $gameNome,
                    'round' => $roundId,
                    'tx' => $transactionId,
                    'amt' => $win,
                    'bb' => $saldoAnterior - $bet,
                    'ba' => $novoSaldo
                ]);
            } else if ($bet > 0) {
                $insTx = $pdo->prepare("INSERT INTO casino_history (user_id, game_code, game_name, provider, round_id, txn_id, action, amount, balance_before, balance_after, status, created_at) 
                    VALUES (:uid, :gcode, :gname, 'PRAGMATIC', :round, :tx, 'bet', :amt, :bb, :ba, 'completed', NOW())");
                $insTx->execute([
                    'uid' => (int)$userCode,
                    'gcode' => $gameCode,
                    'gname' => $gameNome,
                    'round' => $roundId,
                    'tx' => $transactionId,
                    'amt' => $bet,
                    'bb' => $saldoAnterior,
                    'ba' => $novoSaldo
                ]);
            }

            $pdo->commit();

            return [
                'status' => 1,
                'msg' => 'SUCCESS',
                'user_balance' => round($novoSaldo, 2),
                'balance' => round($novoSaldo, 2)
            ];

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['status' => 0, 'message' => $e->getMessage()];
        }
    }
}

