<?php

namespace app\modules\cron\controllers;

use app\core\Controller;
use app\core\crud\Conn;
use app\helpers\APIMarjo;

class jogosController extends Controller
{

    public function __construct()
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);
    }

    /**
     * Importação dos jogos
     */
    function indexAction()
    {
        try {
            $imported = \app\helpers\SportsImporter::fetchFromNodeApi('today', 'vaidebet');
            if (!empty($imported['jogos'])) {
                $result = \app\helpers\SportsImporter::importarJogos($imported['jogos']);
                $ip = getUserIP();
                error_log("Cron SportsImporter: {$ip} {$result['message']}");
                return $result;
            }
        } catch (\Throwable $e) {
            error_log("Cron SportsImporter error: " . $e->getMessage());
        }

        $result = (new APIMarjo())->importarJogos();
        $ip = getUserIP();
        error_log("Cron APIMarjo: {$ip} " . (is_array($result) ? ($result['message'] ?? '') : ''));
        return $result;
    }

    /**
     * Resultados dos jogos
     * @throws \Exception
     */
    function resultadosAction()
    {
        try {
            Conn::startTransaction();
            $response = (new APIMarjo())->importarPlacares();
            $ip = getUserIP();
            error_log("Cron: {$ip} {$response['message']}");
            apostasController::instance()->baixaAction();
            Conn::commit();
            return $response;
        } catch (\Exception $ex) {
            Conn::rollBack();
            return $ex;
        }
    }

}