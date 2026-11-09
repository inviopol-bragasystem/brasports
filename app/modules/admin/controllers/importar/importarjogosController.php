<?php

namespace app\modules\admin\controllers\importar;

use app\core\Controller;
use app\helpers\SportsImporter;
use app\models\CotacoesModel;
use app\models\JogosModel;
use Exception;

class importarjogosController extends Controller
{
    function indexAction()
    {
        $this->view('admin/importar/jogos', [
            'cotacoes' => CotacoesModel::getCotacoesAtivas(),
            'tempos' => JogosModel::TEMPOS,
            'grupos' => CotacoesModel::GRUPOS,
            'theOddsApiKey' => SportsImporter::getSavedTheOddsApiKey(),
            'theOddsSports' => SportsImporter::getTheOddsSportsList(),
        ]);
    }

    function jogosAction()
    {
        try {
            $source = (string)(inputGet('source') ?: inputPost('source') ?: 'node');
            $mode = (string)(inputGet('mode') ?: inputPost('mode') ?: 'today');
            $subsource = (string)(inputGet('subsource') ?: inputPost('subsource') ?: 'vaidebet');
            $sport = (string)(inputGet('sport') ?: inputPost('sport') ?: 'soccer_brazil_campeonato');
            $apiKey = (string)(inputGet('apiKey') ?: inputPost('apiKey') ?: SportsImporter::getSavedTheOddsApiKey());

            if ($source === 'node') {
                $response = SportsImporter::fetchFromNodeApi($mode, $subsource);
            } elseif ($source === 'theodds') {
                $response = SportsImporter::fetchFromTheOddsApi($apiKey, $sport, $mode === 'live');
            } elseif ($source === 'betby') {
                $response = SportsImporter::fetchFromBetby($mode);
            } else {
                // Padrão: Betano BR (PHP Local)
                $response = SportsImporter::fetchFromBetano($mode);
            }

            $response['jogosAlterados'] = [];

            // Identifica jogos que já existem no banco
            if (!empty($response['referencias']) && is_array($response['referencias'])) {
                $refPlaces = [];
                $refValues = [];
                foreach (array_values(array_slice($response['referencias'], 0, 500)) as $i => $ref) {
                    $refPlaces[] = ":ref{$i}";
                    $refValues["ref{$i}"] = (string)$ref;
                }
                if ($refPlaces) {
                    $inSql = implode(', ', $refPlaces);
                    $stmt = \app\core\crud\Conn::getConn()->prepare("SELECT a.refimport FROM `sis_jogos` AS a WHERE a.refimport IN({$inSql}) AND a.status != 99");
                    $stmt->execute($refValues);
                    $jogosAlterados = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                    if (!empty($jogosAlterados)) {
                        $response['jogosAlterados'] = $jogosAlterados;
                    }
                }
            }

            return $response;

        } catch (\Throwable $ex) {
            return [
                'result' => 0,
                'message' => $ex->getMessage(),
                'jogos' => [],
                'campeonatos' => [],
            ];
        }
    }

    function importarAction()
    {
        try {
            // Suporta JSON bruto (php://input), $_POST['jogos'] ou inputPost
            $raw = null;
            if (!empty($_POST['jogos'])) {
                $raw = $_POST['jogos'];
            } else {
                $post = filter_input_array(INPUT_POST);
                if (!empty($post['jogos'])) {
                    $raw = $post['jogos'];
                } else {
                    $raw = inputPost('jogos');
                }
            }

            if (empty($raw)) {
                $input = file_get_contents('php://input');
                if (!empty($input)) {
                    $decodedInput = json_decode($input, true);
                    if (is_array($decodedInput)) {
                        $raw = $decodedInput['jogos'] ?? $decodedInput;
                    }
                }
            }

            if (is_string($raw)) {
                $jogos = json_decode($raw, true);
                if (!$jogos) {
                    $jogos = json_decode(stripslashes($raw), true) ?: [];
                }
            } elseif (is_array($raw)) {
                $jogos = $raw;
            } else {
                $jogos = [];
            }

            if (empty($jogos)) {
                throw new Exception('Nenhum jogo recebido para importação.');
            }

            $ajustarHorario = (bool)(inputPost('ajustarHorario') ?? false);
            $options = [
                'ajustarHorario' => $ajustarHorario,
            ];

            $resultado = SportsImporter::importarJogos($jogos, $options);

            // Limpa o cache do Twig/views para atualizar a página inicial imediatamente
            try {
                $this->viewCacheClean();
            } catch (\Throwable $t) {
                // Silencioso
            }

            return $resultado;

        } catch (\Throwable $exception) {
            return [
                'result' => 0,
                'message' => $exception->getMessage(),
            ];
        }
    }

    function saveTheOddsKeyAction()
    {
        try {
            $key = (string)inputPost('apiKey');
            SportsImporter::saveTheOddsApiKey($key);
            return [
                'result' => 1,
                'message' => 'Chave da The Odds API salva com sucesso!',
            ];
        } catch (Exception $ex) {
            return [
                'result' => 0,
                'message' => $ex->getMessage(),
            ];
        }
    }
}
