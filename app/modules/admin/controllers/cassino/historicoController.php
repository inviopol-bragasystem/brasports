<?php

namespace app\modules\admin\controllers\cassino;

use app\core\Controller;
use app\models\CassinoModel;
use app\vo\admin\MenuVO;

class historicoController extends Controller
{
    public function indexAction(?MenuVO $menu = null)
    {
        view_vars('title', 'Histórico de Rodadas - Cassino');

        $userId = inputGet('user_id') ? (int)inputGet('user_id') : null;
        $page = max(1, (int)inputGet('page', 1));
        $limit = 50;

        $historico = CassinoModel::getHistory($userId, $page, $limit);
        $stats = CassinoModel::getStats();

        $this->view('admin/cassino/historico', [
            'historico' => $historico,
            'stats' => $stats,
            'page' => $page,
            'userId' => $userId,
            'menu' => $menu,
        ]);
    }
}
