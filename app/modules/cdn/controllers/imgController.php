<?php


namespace app\modules\cdn\controllers;

use app\core\Controller;
use app\helpers\IMGCanvas;

class imgController extends Controller
{

    function indexAction()
    {
        ob_end_clean();

        $w = max(0, min(3000, (int)url_parans(0)));
        $h = max(0, min(3000, (int)url_parans(1)));
        $type = (string)url_parans(2) ?: 'crop';
        if (!in_array($type, ['crop', 'preenchimento', 'proporcional', 'simples'])) {
            $type = 'crop';
        }
        $qualidade = max(10, min(100, (int)url_parans(3) ?: 100));

        // Sanitização rigorosa contra Path Traversal
        $rawFilename = (string)url_parans(4);
        if (!$rawFilename) {
            $rawFilename = (string)url_parans(0);
        }
        $filename = basename(str_replace(['/', '\\', "\0"], '', $rawFilename));
        $extension = strtolower(preg_replace('/[^a-z0-9]/i', '', (string)url_parans('extension')));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($extension, $allowedExtensions, true)) {
            $extension = 'jpg';
        }

        $etagFile = md5("{$w}/{$h}/{$type}/{$qualidade}/{$filename}/{$extension}");

        $baseDir = realpath(abs_source_images());
        $source = abs_source_images("{$filename}.{$extension}");
        $realSource = realpath($source);

        // Previne qualquer escape fora do diretório de imagens permitido
        if (!$realSource || !file_exists($realSource) || ($baseDir && strpos($realSource, $baseDir) !== 0)) {
            $etagFile = md5("{$w}/{$h}/{$type}/{$qualidade}/default/default.jpg");
            $source = abs_source_images("default.jpg");
        } else {
            $source = $realSource;
        }

        $etagHeader = trim(getenv('HTTP_IF_NONE_MATCH'));

        header("Etag: {$etagFile}");
        header('Cache-Control: public');

        if ($etagHeader == $etagFile) {
            exit(http_response_code(304));
        } else {
            http_response_code(200);
        }

        $img = new IMGCanvas($source);

        # Redimensionando imagem
        if ($w > 0 or $h > 0) {
            $img->redimensiona($w, $h, $type);
        }

        $img->grava('', (int)$qualidade);
    }

}