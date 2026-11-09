<?php

namespace app\modules\admin\controllers\cassino;

use app\core\Controller;
use app\models\CassinoModel;
use app\models\HistoricoModel;
use app\modules\admin\Admin;
use app\vo\admin\MenuVO;
use Exception;

class rtpController extends Controller
{
    public function indexAction(?MenuVO $menu = null)
    {
        view_vars('title', 'RTP & Lucro - Cassino');

        $settings = CassinoModel::getSettings();
        $stats = CassinoModel::getStats();

        $this->view('admin/cassino/rtp', [
            'settings' => $settings,
            'stats' => $stats,
            'menu' => $menu,
        ]);
    }

    public function salvarAction()
    {
        if (!IS_AJAX) {
            location();
        }

        try {
            if (!Admin::isMaster() && !Admin::isAdmin()) {
                throw new Exception('Acesso restrito a administradores.');
            }

            $rtpDefault = (float)inputPost('rtp_default');
            $rtpInfluencer = (float)inputPost('rtp_influencer');

            if ($rtpDefault < 1 || $rtpDefault > 100 || $rtpInfluencer < 1 || $rtpInfluencer > 100) {
                throw new Exception('Os valores de RTP devem estar entre 1% e 100%.');
            }

            $settings = CassinoModel::getSettings();
            $settings['rtp_default'] = $rtpDefault;
            $settings['rtp_influencer'] = $rtpInfluencer;

            CassinoModel::saveSettings($settings);
            HistoricoModel::add("Atualizou as regras de RTP do Cassino: Padrão {$rtpDefault}% | Influenciadores {$rtpInfluencer}%", null, Admin::getLogged());

            return [
                'result' => 1,
                'message' => 'RTP atualizado com sucesso!'
            ];
        } catch (Exception $e) {
            return $e;
        }
    }
}
