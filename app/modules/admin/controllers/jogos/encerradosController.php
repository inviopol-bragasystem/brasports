<?php

namespace app\modules\admin\controllers\jogos;

use app\core\Controller;
use app\core\crud\Conn;
use app\models\JogosModel;
use app\vo\JogoVO;

class encerradosController extends Controller
{

    protected $tpl = 'admin/jogos/encerrados';

    function indexAction()
    {
        $this->view($this->tpl);
    }

    function listAction()
    {

        $parans = inputPost();
        $parans['status'] = 2;
        $parans += ['page' => 1, 'forpage' => 20];

        $busca = JogosModel::busca($parans, $parans['page'], $parans['forpage']);

        $this->view($this->tpl, [
            'parans' => $parans,
            'busca' => $busca,
        ], 'list');

    }

    function revalidarAction()
    {
        try {

            $token = inputPost('token');
            if (!$token)
                throw new \Exception("Informe o token");

            $jogo = JogosModel::getByLabel('token', $token);
            if (!$jogo instanceof JogoVO)
                throw new \Exception("Jogo inválido");

            Conn::startTransaction();
            $jogo->save(null, false);
            JogosModel::redefinirPlacar($jogo);
            Conn::commit();

            return [
                'message' => "Redefinição realizada",
                'result' => 1,
            ];
        } catch (\Exception $ex) {
            Conn::rollBack();
            return $ex;
        }
    }

}
