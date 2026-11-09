<?php

namespace app\modules\api\controllers;


use app\core\Controller;
use app\models\ApostasModel;
use app\models\CotacoesModel;
use app\vo\ApostaVO;

class ticketsController extends Controller
{

    function indexAction()
    {


        /** @var ApostaVO[] $tickets */
        $tickets = ApostasModel::lista('WHERE a.status = 1 AND a.insert > :insert ORDER BY a.id ASC', [
            'insert' => date('Y-m-d H:i:s', strtotime('-3days')),
        ]);

        $result = [];

        foreach ($tickets as $aposta) {
            $result[] = $this->ticket($aposta);
        }

        return $result;
    }

    function ticket(ApostaVO $aposta)
    {

        return [
            'code' => $aposta->getCodigoBilhete(),
            'name' => $aposta->getApostadorNome(),
            'username' => $aposta->getUserNome(),
            'date' => date('c', strtotime($aposta->getInsert())),
            'value' => $aposta->getValor(),
            'prize' => $aposta->getRetornoValido(),
            'status' => $aposta->getVerificado() ? ($aposta->getGanhou() ? 'Ganhou' : 'Perdeu') : 'Aberto',
            'selections' => call_user_func(function () use ($aposta) {

                $jogos = [];

                foreach ($aposta->voJogos() as $v) {
                    $jogo = $v->voJogo();

                    $score = null;
                    if ($jogo->getStatus() == 2) {
                        $score = [
                            'home_score' => $jogo->getTimeCasaPlacar(),
                            'away_score' => $jogo->getTimeForaPlacar(),
                            'ft_home_score' => $jogo->getTimeCasaPlacarPrimeiro(),
                            'ft_away_score' => $jogo->getTimeForaPlacarPrimeiro(),
                        ];
                    }

                    $jogos[] = [
                        'competition_name' => $jogo->getCampeonatoTitle(),
                        'home' => $jogo->getTimeCasaTitle(),
                        'away' => $jogo->getTimeForaTitle(),
                        'date' => date('c', strtotime("{$jogo->getData()} {$jogo->getHora()}")),
                        'score' => $score,
                        'status' => $jogo->getStatus() == 2 ? ($v->getAcertou() ? 'Ganhou' : 'Perdeu') : 'Aberto',
                        'odd' => [
                            'makert' => CotacoesModel::GRUPOS[$v->getCotacaoGrupo()],
                            'name' => $v->getCotacaoTitle(),
                            'price' => $v->getCotacaoValor(),
                        ]
                    ];
                }

                return $jogos;

            })
        ];

    }

    function detailsAction()
    {
        try {

            $code = inputPost('code');
            if (!$code)
                throw new \Exception("Informe o código do bilhete");

            $aposta = ApostasModel::getByLabel('codigobilhete', $code);
            if (!$aposta instanceof ApostaVO)
                throw new \Exception("Aposta inválida");

            return [
                'message' => 'Dados da aposta',
                'ticket' => $this->ticket($aposta),
                'result' => 1,
            ];

        } catch (\Exception $ex) {
            return $ex;
        }
    }

}