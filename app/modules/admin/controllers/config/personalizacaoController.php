<?php

namespace app\modules\admin\controllers\config;

use app\core\Controller;
use app\models\DadosModel;
use app\models\HistoricoModel;
use app\modules\admin\Admin;
use app\vo\admin\MenuVO;
use Exception;

class personalizacaoController extends Controller
{

    function indexAction(MenuVO $p)
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
        $webhookUrl = rtrim($protocol . $host, '/') . '/' . ltrim(url('pixup/webhook', null, 'api'), '/');
        $veopagWebhookUrl = rtrim($protocol . $host, '/') . '/' . ltrim(url('veopag/webhook', null, 'api'), '/');

        $this->view('admin/configuracoes/personalizacao', [
            'config' => DadosModel::get(),
            'temas' => DadosModel::getTemas(),
            'temaPadrao' => 'site1',
            'webhookUrl' => $webhookUrl,
            'veopagWebhookUrl' => $veopagWebhookUrl,
        ]);
    }

    public function insertAction()
    {
        if (!IS_AJAX) {
            location();
        }

        try {

            if (!Admin::isMaster() && !Admin::isAdmin()) {
                throw new Exception('Acesso restrito a administradores.');
            }

            $post = inputPost();
            // O formulário de personalização altera o registro existente; nunca aceite
            // um id vazio vindo do POST para não transformar essa gravação em INSERT.
            unset($post['id']);
            if (isset($post['tema'])) {
                $temasValidos = array_keys(DadosModel::getTemas());
                if (!in_array($post['tema'], $temasValidos, true)) {
                    throw new Exception('Tema selecionado é inválido.');
                }
            }
            if (isset($post['gatewaypix']) && !in_array($post['gatewaypix'], ['pixup', 'veopag'], true)) {
                throw new Exception('Gateway PIX selecionado é inválido.');
            }
            foreach (['pixup_status', 'veopag_status'] as $statusKey) {
                if (isset($post[$statusKey])) $post[$statusKey] = (int)((string)$post[$statusKey] === '1');
            }

            if (isset($post['exibirescudos'])) {
                $post['exibirescudos'] = ((int)$post['exibirescudos'] === 1) ? 1 : 0;
            }

            if (isset($post['exibircassino'])) {
                $post['exibircassino'] = ((int)$post['exibircassino'] === 1) ? 1 : 0;
            }

            if (isset($post['tiposlideshome'])) {
                $tiposValidos = ['mesclado', 'banners', 'jogos', 'futebol_banners', 'cassino_banners', 'desativado'];
                if (!in_array($post['tiposlideshome'], $tiposValidos, true)) {
                    $post['tiposlideshome'] = 'mesclado';
                }
            }

            if (isset($post['tiposlidescassino'])) {
                $tiposValidosCassino = ['mesclado', 'banners', 'jogos', 'desativado'];
                if (!in_array($post['tiposlidescassino'], $tiposValidosCassino, true)) {
                    $post['tiposlidescassino'] = 'mesclado';
                }
            }

            $config = DadosModel::get();
            if (!$config->getId()) {
                throw new Exception('Não foi possível localizar a configuração do sistema para salvar.');
            }

            $config
                ->save($post);

            HistoricoModel::add("Alterou a personalização do sistema (temas, botões, dimensões e gateway PIX)", $config, Admin::getLogged());

            return [
                'message' => 'Personalização atualizada com sucesso.',
                'result' => 1,
            ];
        } catch (\Throwable $ex) {
            return $ex;
        }
    }

    public function updateAction()
    {
        return $this->insertAction();
    }

}
