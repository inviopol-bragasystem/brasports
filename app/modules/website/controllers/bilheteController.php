<?php

namespace app\modules\website\controllers;

use app\core\Controller;
use app\models\ApostasModel;
use app\models\DadosModel;
use app\models\HistoricoBancarioModel;
use app\modules\admin\Admin;
use app\vo\ApostaVO;

class bilheteController extends Controller
{

    protected $view = 'website/page/bilhete';

    function indexAction()
    {
        $this->view($this->view, [
            'title' => 'Consultar bilhete',
        ]);
    }

    function validarAction()
    {
        try {

            $user = Admin::getLogged();
            $aposta = ApostasModel::getByLabel('token', inputPost('token'));
            $config = DadosModel::get();

            if (!$user) {
                throw new \Exception("Atualize sua página e faça login novamente.");
            } else if (!$aposta instanceof ApostaVO) {
                throw new \Exception("Aposta inválida");
            } else if ($aposta->getStatus() != ApostasModel::STATUS_AGUARDANDO_PAGAMENTO) {
                throw new \Exception("Aposta não pode mais ser validada");
            } else if ($aposta->getValor() > $user->getCredito()) {
                throw new \Exception("Saldo insuficiente para validar a aposta.");
            }

            foreach ($aposta->voJogos() as $v) {
                $jogo = $v->voJogo();
                if (!$jogo instanceof \app\vo\JogoVO) {
                    throw new \Exception("Aposta contém jogo inválido.");
                } else if ((int)$jogo->getStatus() !== 1) {
                    throw new \Exception("A aposta não pode mais ser validada pois a partida `{$jogo->getDescricao()}` foi cancelada ou encerrada.");
                } else if ($jogo->jaComecou()) {
                    throw new \Exception("A aposta não pode mais ser validada pois a partida `{$jogo->getDescricao()}` já começou.");
                } else if (!$jogo->aceitaApostas()) {
                    throw new \Exception("A aposta não pode mais ser validada pois a partida `{$jogo->getDescricao()}` encerrou o período de apostas.");
                }
            }

            $aposta->setGerente($user->getUser());
            $aposta->setUser($user->getId());
            $aposta->setStatus(1);
            $aposta->save();

            HistoricoBancarioModel::add($user, -$aposta->getValor(), "Validação de aposta #{$aposta->getId()} {$aposta->getCodigoBilhete()}", $aposta);

            ApostasModel::pagarComissoes($aposta);

            if ($config->getRetornoRisco() and $config->getRetornoRisco() <= $aposta->getRetornoValido()) {
                ApostasModel::sendAvisoRisco($aposta);
            }

            return [
                'message' => 'Aposta validada com sucesso',
                'saldo' => $user->getCredito(),
                'result' => 1,
            ];

        } catch (\Exception $ex) {
            return $ex;
        }
    }

    function listAction()
    {
        try {

            $codigo = trim((string)inputPost('codigo'));
            if (!$codigo)
                throw new \Exception("Informe o código da aposta");

            $cleanCodigo = strtoupper(str_replace([' ', '-'], '', $codigo));
            $aposta = ApostasModel::getByLabel('codigobilhete', $cleanCodigo);
            if (!$aposta instanceof ApostaVO) {
                $aposta = ApostasModel::getByLabel('codigobilhete', str_replace(' ', '', $codigo));
            }

            if (!$aposta instanceof ApostaVO) {
                throw new \Exception("Aposta inválida");
            }

            $this->view($this->view, [
                'aposta' => $aposta,
                'user' => $aposta->voUser(),
            ], 'bilhete');

        } catch (\Exception $ex) {
            echo <<<HTML
<div class="alert alert-info mb-0">
    <i class="fa fa-warning"></i> {$ex->getMessage()}
</div>
HTML;
        }
    }

}