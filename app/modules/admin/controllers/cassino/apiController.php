<?php

namespace app\modules\admin\controllers\cassino;

use app\core\Controller;
use app\models\HistoricoModel;
use app\modules\admin\Admin;
use app\services\CassinoAgregadorService;
use app\vo\admin\MenuVO;
use Exception;

class apiController extends Controller
{
    public function indexAction(?MenuVO $menu = null)
    {
        view_vars('title', 'Configuração & API - Cassino');

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "https://";
        $host = $_SERVER['HTTP_HOST'] ?? 'banca.0um.online';
        $baseUrl = rtrim($protocol . $host, '/');

        $walletCallbackUrl = $baseUrl . '/api/cassino/wallet';

        $this->view('admin/cassino/api', [
            'settings' => CassinoAgregadorService::getSettings(),
            'walletCallbackUrl' => $walletCallbackUrl,
            'menu' => $menu,
            'dados' => \app\models\DadosModel::get(),
        ]);
    }

    public function salvarAction()
    {
        if (!IS_AJAX) {
            location();
        }

        try {
            if (!Admin::isMaster() && !Admin::isAdmin()) {
                throw new Exception('Acesso restrito a administradores.');
            }

            $post = inputPost();
            $post['active'] = isset($post['active']) ? (int)$post['active'] : 1;

            if (isset($post['exibircassino'])) {
                $exibirCassino = ((int)$post['exibircassino'] === 1) ? 1 : 0;
                $configDados = \app\models\DadosModel::get();
                if ($configDados && $configDados->getId()) {
                    $configDados->save(['exibircassino' => $exibirCassino]);
                }
            }

            CassinoAgregadorService::saveSettings($post);
            HistoricoModel::add("Atualizou as configurações do Agregador de Cassino GamesVIP", null, Admin::getLogged());

            // Tenta registrar o callback da carteira automaticamente no agregador
            $callbackMsg = '';
            try {
                $syncCb = CassinoAgregadorService::syncWalletCallback();
                if (!empty($syncCb['status'])) {
                    $callbackMsg = ' Callback da carteira registrado no agregador com sucesso!';
                }
            } catch (Exception $cbEx) {
                $callbackMsg = ' (Aviso: Verifique a conexão com o agregador para registrar o callback)';
            }

            return [
                'result' => 1,
                'message' => 'Configurações de API salvas com sucesso!' . $callbackMsg
            ];
        } catch (Exception $e) {
            return $e;
        }
    }

    public function testarAction()
    {
        if (!IS_AJAX) {
            location();
        }

        try {
            $settings = CassinoAgregadorService::getSettings();
            $apiUrl = rtrim($settings['api_url'] ?? '', '/');

            if (empty($apiUrl) || empty($settings['agent_token']) || empty($settings['secret_key'])) {
                throw new Exception('Preencha a URL, Agent Token e Secret Key para testar.');
            }

            $res = CassinoAgregadorService::request('providers');

            if (!empty($res['status']) && isset($res['providers'])) {
                $count = count($res['providers']);
                return [
                    'result' => 1,
                    'message' => "Conexão com o Agregador estabelecida com sucesso! {$count} provedores ativos encontrados."
                ];
            }

            $errMsg = $res['msg'] ?? ($res['error'] ?? 'Falha ao autenticar com o agregador.');
            return [
                'result' => 0,
                'message' => "Erro no Agregador: {$errMsg}"
            ];
        } catch (Exception $e) {
            return $e;
        }
    }

    public function sincronizarAction()
    {
        if (!IS_AJAX) {
            location();
        }

        try {
            if (!Admin::isMaster() && !Admin::isAdmin()) {
                throw new Exception('Acesso restrito a administradores.');
            }

            $res = CassinoAgregadorService::syncCatalog();

            if (!empty($res['status'])) {
                HistoricoModel::add("Sincronizou catálogo de jogos do Cassino ({$res['total']} jogos)", null, Admin::getLogged());
                return [
                    'result' => 1,
                    'message' => $res['msg']
                ];
            }

            return [
                'result' => 0,
                'message' => $res['msg'] ?? 'Falha ao sincronizar catálogo.'
            ];
        } catch (Exception $e) {
            return $e;
        }
    }
}
