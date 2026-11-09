<?php

namespace app\modules\api\controllers;

use app\core\Controller;
use app\models\DadosModel;
use app\modules\api\API;

class indexController extends Controller
{

    function indexAction()
    {

        $dados = DadosModel::get();

        try {
            $user = API::getUser();
        } catch (\Exception $ex) {
            $user = null;
        }

        $graduacao = $user ? $user->voGraduacao() : null;

        $cotacaoMaxima = ($graduacao ? $graduacao->getCotacaoMaxima() : 0) ?: $dados->getCotacaoMaxima();
        $cotacaoMinima = ($graduacao ? $graduacao->getCotacaoMinima() : 0) ?: $dados->getCotacaoMinima();
        $apostaMinima = ($user ? $user->getApostaMinima() : 0) ?: ($graduacao ? $graduacao->getApostaMinima() : 0) ?: $dados->getApostaMinima();
        $apostaMaxima = ($user ? $user->getApostaMaxima() : 0) ?: ($graduacao ? $graduacao->getApostaMaxima() : 0) ?: $dados->getApostaMaxima();
        $retornoMaximo = $dados->getRetornoMaximo();
        $minJogos = ($user ? $user->getMinJogos() : 0) ?: ($graduacao ? $graduacao->getMinJogos() : 0) ?: $dados->getMinJogos();

        return [
            'title' => $dados->getBanca(),
            'apostaMin' => (float)$apostaMinima,
            'apostaMax' => (float)$apostaMaxima ?: 9999,
            'cotacaoMin' => (float)$cotacaoMinima,
            'cotacaoMax' => (float)$cotacaoMaxima ?: 999,
            'minJogos' => (int)$minJogos,
            'retornoMaximo' => (float)$retornoMaximo ?: 9999,
            'logotipo' => $dados->imgCapa(true, 'logo')->getSource(true),
            'background' => $dados->imgCapa(true, 'background')->getSource(true),
            'linkSacar' => $dados->getLinkSaque(),
            'linkComprar' => $dados->getLinkComprar(),
            'result' => 1,
        ];
    }

    function validarAction()
    {
        return [
            'url' => url(null, null, 'api'),
            'result' => 1,
        ];
    }

}