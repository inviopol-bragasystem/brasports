<?php

namespace app\modules\admin\controllers\apostas;

use app\core\Controller;
use app\helpers\bootstrap\Panel;
use app\helpers\Number;
use app\helpers\Table;
use app\models\ApostaJogosModel;
use app\models\CotacoesModel;
use app\models\JogosModel;
use app\vo\ApostaJogoVO;
use app\vo\JogoVO;

class jogosController extends Controller
{

    function indexAction()
    {
        $this->view('admin/apostas/mais-apostados');
    }

    function detalhesAction()
    {
        $jogo = JogosModel::getByLabel('token', url_parans(0), true);

        if ($jogo instanceof JogoVO) {

            $cotacoes = ApostaJogosModel::cotacoesApostadasPorJogo($jogo->getId());
            $ordem = CotacoesModel::getCotacoesAtivas();

            $totais = [
                'apostas' => 0,
                'valor' => 0,
            ];

            if ($cotacoes) {
                echo '<div class="valoresCotacoes">'
                    . '<div class="m-t-md m-b-md m-l-1 m-r-1" style="width: 95%;margin: 13px auto;">'
                    . '<div class="row"><div class="col-md-12 col-sm-12 col-xs-12">';

                foreach ($ordem as $cotacao) {

                    $key = $cotacao->getCampo();
                    $label = $cotacao->getTitulo();

                    if (isset($cotacoes[$key])) {
                        $values = $cotacoes[$key];

                        $totais['apostas'] += (int)($values['total'] ?? 0);
                        $totais['valor'] += (float)($values['valor'] ?? 0);

                        switch ($key) {
                            case 'casa':
                                $class = 'btn-success';
                                break;
                            case 'fora':
                                $class = 'btn-warning';
                                break;
                            case 'empate':
                                $class = 'btn-info';
                                break;
                            default:
                                $class = 'btn-default';
                        }
                        echo '<span class="btn btn-xs ' . $class . ' m-r-xs m-b-xs">' . $label . ' (' . $values['total'] . '): R$ ' . Number::real($values['valor']) . '</span>';
                    }
                }

                echo '</div></div></div>';
            }

            $t = new Table(null, 'table table-minified table-hover table-bordered table-striped list-table');

            $t
                ->addTSection('thead')
                ->addRow()
                ->addCell('Cotação/Valor', ['width' => 120])
                ->addCell('Aposta', ['width' => 100])
                ->addTSection('tbody');

            $listApostas = ApostaJogosModel::lista("WHERE a.jogo = :jogo AND a.status = 1 ORDER BY a.insert ASC", ['jogo' => $jogo->getId()]);
            $totalApostas = count($listApostas);
            $valor = 0;

            if ($totalApostas) {

                foreach ($listApostas as $v) {
                    if ($v instanceof ApostaJogoVO) {
                        $valor += $v->getValor();
                        $t
                            ->addRow()
                            ->addCell("{$v->getCotacaoValor(true)}<br /><b>R$ {$v->getValor(true)}</b>", 'text-center')
                            ->addCell($v->getCotacaoTitle(), 'text-center');
                    }
                }

                $totais['real'] = Number::real($totais['valor']);

                echo <<<HTML
<div class='table-responsive' >{$t}</div>
<p class='text-center' >
    Apostas: <b>{$totais['valor']}</b>
    <br />
    Total: <b>R$ {$totais['real']}</b>
</p>
HTML;

            } else {
                echo '<div class="alert alert-warning" >Nenhuma aposta realizada para o jogo até o momento.</div>';
            }
        } else {
            echo '<div class="alert alert-danger" >Jogo inválido.</div>';
        }
    }

    function listAction()
    {

        $parans = inputPost();
        $parans['status'] = 1;
        $parans += ['page' => 1, 'forpage' => 20, 'orderby' => 'apostas'];

        $busca = JogosModel::busca($parans, $parans['page'], $parans['forpage'], $parans['orderby']);

        $cotacoes = CotacoesModel::getCotacoesAtivas();

        if ($busca->getCount()) {

            $html = '';

            $t = new Table(null, 'table table-minified table-hover table-bordered table-striped no-wrap');

            $t
                ->addTSection('thead')
                ->addRow()
                ->addCell('Casa / Fora', ['width' => 180, 'class' => 'text-center']);

            foreach ($cotacoes as $indexCotacao => $cotacao) {
                $class = $indexCotacao % 2 == 0 ? 'dark' : 'light';
                if ($class == 'dark') {
                    $html = '';
                }

                $html .= '<span class="cotacoes-' . $class . '" style="color: ' . $cotacao->getCor() . ';">' . $cotacao->getSigla() . '</span>';

                if ($class == 'light') {
                    $t->addCell($html, ['width' => 90]);
                }
            }

            $t
                ->addCell('Total de Apostas<br/>Valor em Apostas', ['width' => 150, 'class' => 'text-center no-wrap'])
                ->addCell('Ações', ['width' => 90, 'class' => 'text-center no-wrap'])
                ->addTSection('tbody');

            foreach ($busca->getRegistros() as $v) {

                if ($v instanceof JogoVO) {

                    $totais = [
                        'apostas' => 0,
                        'valor' => 0,
                    ];

                    $countCotacoes = ApostaJogosModel::cotacoesApostadasPorJogo($v->getId());

                    $title = <<<HTML
<a href="javascript:;" data-jogo-cotacoes="{$v->getToken()}">
    #{$v->getId()} {$v->getTimeCasaTitle()} x {$v->getTimeForaTitle()}
</a>
<br /><small>{$v->getData(true)} ás {$v->getHora(true)}</small>
HTML;


                    $t
                        ->addRow()
                        ->addCell($title);

                    foreach ($cotacoes as $indexCotacao => $cotacao) {

                        $class = $indexCotacao % 2 == 0 ? 'dark' : 'light';
                        if ($class == 'dark') {
                            $html = '';
                        }

                        $method = $cotacao->getCampo();

                        $totais['apostas'] += $countCotacoes[$method]['total'] ?? 0;
                        $totais['valor'] += $countCotacoes[$method]['valor'] ?? 0;

                        $count = !empty($countCotacoes[$method]) ? $countCotacoes[$method]['total'] : 0;
                        $total = !empty($countCotacoes[$method]) ? 'R$ ' . Number::real($countCotacoes[$method]['valor']) : 'R$ 0,00';
                        $html .= '<span class="cotacoes-' . $class . '" style="color: ' . $cotacao->getCor() . '">' . $v->get($method, 'real') . ' <span data-toggle="tooltip" title="' . $count . ' Aposta(s) (' . $total . ')" >(' . $count . ')</span></span>';

                        if ($class == 'light') {
                            $t->addCell($html, 'text-center');
                        }

                    }

                    $t->addCell($totais['apostas'] . "<br/>" . 'R$ ' . Number::real($totais['valor']), 'text-center no-wrap');
                    $t->addCell('<div class="btn-group" >'
                        . '<div class="btn btn-success" data-jogo-cotacoes="' . $v->getToken() . '" ><i class="fa fa-eye" ></i> Apostas</div>'
                        . '</div>', 'text-center');

                }
            }

            echo (new Panel)
                ->setBody("<div class='table-responsive' >{$t}</div>")
                ->setFooter($busca, ['class' => 'text-right clearfix']);
        } else {
            echo '<div class="alert alert-warning no-margin" ><i class="fa fa-warning" ></i> Nenhum jogo encontrado.</div>';
        }
    }

}
    