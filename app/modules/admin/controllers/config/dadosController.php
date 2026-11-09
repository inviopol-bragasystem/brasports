<?php

namespace app\modules\admin\controllers\config;

use app\core\Controller;
use app\models\DadosModel;
use app\models\HistoricoModel;
use app\modules\admin\Admin;
use app\vo\admin\MenuVO;
use Exception;

class dadosController extends Controller
{

    function indexAction(MenuVO $p)
    {
        $this->view('admin/configuracoes/configuracoes', [
            'config' => DadosModel::get(),
            'types' => [
                'jogador' => 'Jogador',
                'consultor' => 'Consultor',
                'franqueado' => 'Franqueado',
            ],
            'jogos' => [
                1 => '(1 jogo)',
                2 => '(2 jogos)',
                3 => '(3 a 5 jogos)',
                4 => '(6 a 10 jogos)',
                5 => '(11 jogos acima)',
            ]
        ]);
    }

    public function insertAction()
    {
        if (!IS_AJAX) {
            location();
        }

        try {

            if (!Admin::isMaster()) {
                throw new Exception('Somente para desenvolvedores.');
            }

            // Validação estrita de segurança: apenas PNG e JPG são permitidos
            $this->validarUploadImagem('logo', 'Logotipo');
            $this->validarUploadImagem('background', 'Background');

            $config = DadosModel::get();

            $config
                ->save(inputPost())
                ->imgAddCapa('logo', 'logo', true)
                ->imgAddCapa('background', 'background', true);

            HistoricoModel::add("Alterou as configurações do sistema", $config, Admin::getLogged());

            return [
                'message' => 'Configurações atualizadas com sucesso.',
                'result' => 1,
            ];
        } catch (Exception $ex) {
            return $ex;
        }
    }

    /**
     * Valida com rigor se o upload é estritamente PNG ou JPG,
     * inspecionando cabeçalho binário, extensão, MIME real e decodificação GD.
     * Previne vulnerabilidades de web shell, SVG XSS, polyglots e double-extensions.
     */
    private function validarUploadImagem($fileInputKey, $nomeCampo)
    {
        if (!isset($_FILES[$fileInputKey]) || empty($_FILES[$fileInputKey]['name'])) {
            return;
        }

        $file = $_FILES[$fileInputKey];
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Falha no upload do arquivo para {$nomeCampo}. Código de erro: {$file['error']}");
        }

        // 1. Validação de tamanho máximo (10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception("O arquivo para {$nomeCampo} excede o limite máximo permitido de 10MB.");
        }

        // 2. Validação estrita da extensão do arquivo (apenas png, jpg, jpeg)
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['png', 'jpg', 'jpeg'];
        if (!in_array($ext, $allowedExtensions, true)) {
            throw new Exception("Formato inválido para {$nomeCampo}! São permitidos exclusivamente arquivos PNG e JPG.");
        }

        // Previne caracteres perigosos ou tentativas de bypass no nome
        if (strpos($file['name'], "\0") !== false || preg_match('/\.(php|phtml|phar|cgi|pl|py|sh|exe|svg|html|htm)/i', $file['name'])) {
            throw new Exception("Nome de arquivo não permitido para {$nomeCampo}.");
        }

        // 3. Validação do MIME type real via finfo no conteúdo binário do arquivo no disco
        if (!file_exists($file['tmp_name'])) {
            throw new Exception("Arquivo temporário para {$nomeCampo} não foi encontrado.");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/png', 'image/jpeg'];
        if (!in_array($realMime, $allowedMimes, true)) {
            throw new Exception("Tipo de arquivo não permitido para {$nomeCampo}. Envie exclusivamente imagem PNG ou JPG.");
        }

        // 4. Validação da estrutura de imagem via getimagesize
        $imageInfo = @getimagesize($file['tmp_name']);
        if (!$imageInfo || !in_array($imageInfo[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            throw new Exception("O arquivo enviado para {$nomeCampo} não é uma imagem PNG ou JPG válida.");
        }

        // 5. Teste de decodificação via GD para assegurar integridade do arquivo
        if ($imageInfo[2] === IMAGETYPE_PNG) {
            $testGd = @imagecreatefrompng($file['tmp_name']);
        } else {
            $testGd = @imagecreatefromjpeg($file['tmp_name']);
        }

        if (!$testGd) {
            throw new Exception("Não foi possível decodificar a imagem de {$nomeCampo}. O arquivo pode estar corrompido.");
        }
        imagedestroy($testGd);
    }

    public function bloqueioAction()
    {
        if (Admin::isMaster()) {
            $config = DadosModel::get();
            $config->setBloqueado($config->getBloqueado() ? 0 : 1);
            $config->Save();
        }
        location(url_referer());
    }

}
    