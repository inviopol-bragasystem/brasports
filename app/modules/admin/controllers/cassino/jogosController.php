<?php

namespace app\modules\admin\controllers\cassino;

use app\core\Controller;
use app\models\CassinoModel;
use app\modules\admin\Admin;
use app\vo\admin\MenuVO;
use Exception;

class jogosController extends Controller
{
    public function indexAction(?MenuVO $menu = null)
    {
        view_vars('title', 'Catálogo de Jogos - Cassino');

        $category = inputGet('category');
        $provider = inputGet('provider');
        $search = inputGet('search');

        $jogos = CassinoModel::getGames(false, $category, $search, $provider);
        $settings = CassinoModel::getSettings();
        $providers = CassinoModel::getDistinctProviders();

        $this->view('admin/cassino/jogos', [
            'jogos' => $jogos,
            'settings' => $settings,
            'providers' => $providers,
            'provider' => $provider,
            'category' => $category,
            'search' => $search,
            'menu' => $menu,
        ]);
    }

    public function statusAction()
    {
        if (!IS_AJAX) {
            location();
        }

        try {
            if (!Admin::isMaster() && !Admin::isAdmin()) {
                throw new Exception('Acesso restrito a administradores.');
            }

            $id = (int)inputPost('id');
            $status = (int)inputPost('status');

            CassinoModel::toggleGame($id, $status);

            return [
                'result' => 1,
                'message' => 'Status do jogo atualizado com sucesso!'
            ];
        } catch (Exception $e) {
            return $e;
        }
    }

    public function destaqueAction()
    {
        if (!IS_AJAX) {
            location();
        }

        try {
            if (!Admin::isMaster() && !Admin::isAdmin()) {
                throw new Exception('Acesso restrito a administradores.');
            }

            $id = (int)inputPost('id');
            $featured = (int)inputPost('featured');

            CassinoModel::toggleFeatured($id, $featured);

            return [
                'result' => 1,
                'message' => 'Destaque atualizado com sucesso!'
            ];
        } catch (Exception $e) {
            return $e;
        }
    }

    public function rtpAction()
    {
        if (!IS_AJAX) {
            location();
        }

        try {
            if (!Admin::isMaster() && !Admin::isAdmin()) {
                throw new Exception('Acesso restrito a administradores.');
            }

            $id = (int)inputPost('id');
            $rtp = (float)inputPost('rtp');

            if ($rtp < 1 || $rtp > 100) {
                throw new Exception('RTP deve estar entre 1% e 100%.');
            }

            CassinoModel::updateGameRtp($id, $rtp);

            return [
                'result' => 1,
                'message' => 'RTP do jogo atualizado com sucesso!'
            ];
        } catch (Exception $e) {
            return $e;
        }
    }
}
