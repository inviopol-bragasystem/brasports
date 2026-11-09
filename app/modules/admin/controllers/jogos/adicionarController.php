<?php

namespace app\modules\admin\controllers\jogos;

use app\core\Controller;
use app\helpers\Number;
use app\models\CotacoesModel;
use app\models\HistoricoModel;
use app\models\JogosModel;
use app\modules\admin\Admin;
use app\vo\admin\MenuVO;
use app\vo\JogoVO;

class adicionarController extends Controller
{

    public function indexAction(MenuVO $menu)
    {
        $this->view('admin/jogos/adicionar', [
            'grupos' => CotacoesModel::GRUPOS,
            'cotacoes' => CotacoesModel::getCotacoesAtivas(),
        ]);
    }

    function novoAction()
    {
        $this->form(JogosModel::newValueObject(), 'Novo Jogo');
    }

    function form(JogoVO $jogo, string $title)
    {
        $this->view('admin/jogos/form', [
            'v' => $jogo,
            'cotacoes' => CotacoesModel::getCotacoesAtivas('a.ordem ASC, a.titulo ASC, a.grupo ASC'),
            'tempos' => JogosModel::TEMPOS,
            'title' => $title,
        ]);
    }

    function editarAction()
    {
        $jogo = JogosModel::getByLabel('token', url_parans(0));
        if ($jogo instanceof JogoVO) {
            $this->form($jogo, 'Editar Jogo');
        } else {
            location(url_referer());
        }
    }

    function todosAction()
    {
        if (!IS_AJAX) {
            location();
        }

        try {

            $jogosAdd = 0;

            $jogos = filter_input(INPUT_POST, 'jogos', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);


            foreach ($jogos as $dados) {
                $jogo = JogosModel::newValueObject($dados)->Save();
                $jogosAdd++;
            }

            if (!$jogosAdd) {
                throw new \Exception('Nenhum jogo foi adicionado.');
            }

            HistoricoModel::add("Adicionou {$jogosAdd} jogos.", null, Admin::getLogged());

            json($jogosAdd > 1 ? "{$jogosAdd} jogos adicionados." : "Jogo adicionado com sucesso.", 1);
        } catch (\Exception $ex) {
            return $ex;
        }
    }

    function updateCotacoesAction()
    {
        try {

            $ids = inputPost('ids');
            $tipo = inputPost('tipo');
            $valor = (float)(inputPost('valor'));
            $campos = inputPost('cotacoes') ?: [];
            $tempos = inputPost('tempos') ?: [];

            // Termos da consulta
            if (!$ids or empty($ids) or !is_array($ids)) {
                $termos = "WHERE a.status = 1 AND a.data >= curdate()";
            } else {
                $termos = 'WHERE a.id IN(' . implode(', ', $ids) . ')';
            }

            if (empty($campos) or !is_array($campos)) {
                throw new \Exception("Selecione no mínimo uma cotação para atualização");
            } else if (empty($tempos) or !is_array($tempos)) {
                throw new \Exception("Selecione no mínimo um tempo do jogo");
            }

            if ($valor == 0) {
                throw new \Exception("O valor deve ser diferente de zero");
            }

            $jogos = JogosModel::lista($termos);

            /** @var JogoVO $jogo */
            foreach ($jogos as $jogo) {

                $cotacoes = $jogo->getCotacoes(true);

                foreach ($cotacoes as $tempo => $valores) {
                    if (in_array($tempo, $tempos)) {
                        foreach ($valores as $campo => $v) {
                            if (in_array($campo, $campos)) {
                                $before = $v = Number::float($v);
                                switch ($tipo) {
                                    case '$':
                                        $v += $valor;
                                        break;
                                    case '%':
                                        $v = $v * ((100 + $valor) / 100);
                                        break;
                                }
                            }
                            $cotacoes[$tempo][$campo] = $v;
                        }
                    }
                }

                $jogo
                    ->setCotacoes($cotacoes)
                    ->setAlterouCotacoes(1)
                    ->save();

            }

            return [
                'message' => 'Cotações atualizadas com sucesso',
                'result' => 1,
            ];
        } catch (\Exception $ex) {
            return $ex;
        }
    }

    function listAction()
    {
        $parans = inputPost();
        $parans += ['page' => 1, 'forpage' => 20, 'status' => 1];
        $busca = JogosModel::busca($parans, $parans['page'], $parans['forpage']);
        $this->view('admin/jogos/adicionar', [
            'busca' => $busca,
        ], 'list');
    }

}
    