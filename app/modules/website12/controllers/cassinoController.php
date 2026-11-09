<?php

namespace app\modules\website12\controllers;

use app\models\CassinoModel;
use app\models\DadosModel;
use app\modules\admin\Admin;

/**
 * Cassino do tema GoldEsporte (site12).
 */
class cassinoController extends \app\modules\website\controllers\cassinoController
{
    protected $tpl = 'website12/page/cassino';

    public function jogarAction(?string $gameCode = null)
    {
        $dados = DadosModel::get();
        if (!$dados->getExibirCassino()) {
            location('');
        }

        if (empty($gameCode)) {
            $gameCode = url_parans(0) ?: (inputGet('code') ?: (inputGet('game') ?: inputGet('id')));
            if (empty($gameCode) && !empty($_SERVER['REQUEST_URI'])) {
                $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                $parts = array_values(array_filter(explode('/', trim((string)$path, '/'))));
                $idx = array_search('jogar', $parts);
                if ($idx !== false && isset($parts[$idx + 1])) {
                    $gameCode = urldecode($parts[$idx + 1]);
                }
            }
        }

        $user = Admin::getLogged();
        $isDemo = false;
        if (!$user) {
            $isDemo = true;
        }

        $game = !empty($gameCode) ? CassinoModel::getGameByCode($gameCode) : null;
        if (!$game || !$game['active']) {
            if (IS_AJAX) {
                return [
                    'result' => 0,
                    'message' => 'Jogo indisponível ou código inválido.'
                ];
            }
            location('cassino');
        }

        $launch = CassinoModel::launchGame($user, $game);

        if (!$launch['ok']) {
            if (IS_AJAX) {
                return [
                    'result' => 0,
                    'message' => $launch['message'] ?? 'Falha ao iniciar jogo.'
                ];
            }
            $this->view('website12/page/cassino-player', [
                'game' => $game,
                'launchUrl' => '',
                'user' => $user,
                'isDemo' => $isDemo,
                'errorMessage' => $launch['message'] ?? 'Falha ao iniciar o jogo no momento. Tente novamente em instantes.'
            ]);
            return;
        }

        if (IS_AJAX) {
            return [
                'result' => 1,
                'url' => $launch['url'],
                'game' => $game,
                'isDemo' => $isDemo,
                'saldo' => $user ? $user->getCredito(true) : '1.000,00 (Demo)',
            ];
        }

        $this->view('website12/page/cassino-player', [
            'game' => $game,
            'launchUrl' => $launch['url'],
            'user' => $user,
            'isDemo' => $isDemo,
        ]);
    }
}

