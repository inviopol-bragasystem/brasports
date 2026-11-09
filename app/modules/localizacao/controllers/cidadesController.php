<?php

namespace app\modules\localizacao\controllers;

use app\core\Controller;
use app\helpers\Cache;
use app\models\CidadesModel;
use app\vo\CidadeVO;

class cidadesController extends Controller
{

    function indexAction()
    {
        /* @var $model CidadesModel */
        $model = CidadesModel::Instance();

        /* @var $v CidadeVO */
        $start = microtime(true);

        # Valores
        $estado = url_parans(0) ? url_parans(0) : inputPost('estado');
        $cidade = url_parans(1) ? url_parans(1) : inputPost('cidade');

        if ($estado > 0 or (is_string($estado) and preg_match('/^[A-Z]{2}$/i', $estado))) {
            $cache = new Cache('cidades.' . $estado, 60);
            if (!$options = $cache->getContent()) {
                $cidades = $model->getCidades($estado);
                if (count($cidades)) {
                    $options = formOption('-- Selecione a cidade --', '');
                } else {
                    $options = formOption('-- Selecione o estado --', '');
                }
                foreach ($cidades as $v) {
                    $options .= formOption($v->getTitle(), $v->getId(), false);
                }

                # Salvando cache
                $cache->setContent($options);
            }
            if (!empty($cidade)) {
                $options = preg_replace(['/(value="' . preg_quote($cidade) . '")/', '/>(' . preg_quote($cidade) . ')</'], ['$1 selected=""', 'selected="" >$1<'], (string)$options);
            }
            echo $options;
        }

        $end = microtime(true);
        echo "\n\n<!-- " . number_format(($end - $start) * 1000, 5, ',', '.') . "ms --> Buscou por {$cidade}";
        exit;
    }

}
    