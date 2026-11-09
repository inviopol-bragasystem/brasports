<?php

namespace app\models\helpers;

use app\core\Model;
use app\helpers\IMGCanvas;
use app\helpers\Utils;
use app\vo\helpers\ImageVO;

class ImagensModel extends Model
{

    protected $ImgExtensions = ['gif', 'jpg', 'png', 'jpeg', 'webp'];
    protected $MimeTypes = [
        'image/png',
        'image/jpg',
        'image/jpeg',
        'image/pjpeg',
        'image/gif',
        'image/bmp',
        'image/webp',
    ];

    public function __construct()
    {
        $this->table = 'sis_imagens';
        $this->valueObject = ImageVO::class;
    }

    /**
     * Retorna a imagem de capa do registro
     * @param string $ref
     * @param int $refId
     * @param boolean $default
     * @param string $defaultSource Arquivo padrão de imagem (default.jpg)
     * @return ImageVO|null
     */
    public static function capa($ref, $refId, $default = true, $defaultSource = 'default.jpg')
    {
        $img = current(self::lista('WHERE a.ref = :ref AND a.refid = :id AND a.status = 1 ORDER BY a.position ASC LIMIT 1', ['ref' => $ref, 'id' => $refId]));
        if ($img instanceof ImageVO) {
            $img->setDefaultSource($defaultSource);
            return $img;
        } else if ($default) {
            return self::newValueObject([
                'source' => $defaultSource,
                'defaultsource' => $defaultSource,
            ]);
        }
        return null;
    }

    /**
     * Retorna lista de imagens referente
     * @param string $Ref Referência/Tabela
     * @param int $RefId ID de referência
     * @param boolean $Default Retorna uma imagem default
     * @param boolean $onlyActive Retornar só ativos?
     * @param string $defaultSource Arquivo padrão de imagem (default.jpg)
     * @return array|ImageVO
     */
    static function getByRef($Ref, $RefId = 0, $Default = false, $onlyActive = true, $defaultSource = 'default.jpg')
    {

        /** @var ImageVO[] $imagens */
        $imagens = self::lista('WHERE (a.ref = :ref AND a.refid = :refid)  AND ((:status = "all" AND a.status = 1) OR a.status = :status) ORDER BY a.position ASC', [
            'ref' => $Ref,
            'refid' => $RefId,
            'status' => $onlyActive ? 1 : 'all',
        ], false, true);

        if (!$imagens and $Default) {
            $imagens[] = self::newValueObject([
                'source' => $defaultSource,
                'defaultsource' => $defaultSource,
            ]);
        }

        foreach ($imagens as $img) {
            $img->setDefaultSource($defaultSource);
        }

        return $imagens;
    }

    /**
     * Salva a imagem
     * @param ImageVO $img
     * @param string $fileInput
     * @param bool $last
     * @param boolean $excluirSecundarias
     * @return boolean
     */
    function salvaImage(ImageVO $img, $fileInput = null, $last = true, $excluirSecundarias = false)
    {
        $files = $this->getArrayFiles($fileInput);
        if ($img->getId()) {
            if (count($files)) {
                if ($source = $this->moveFile($files[0])) {
                    $img->setSource($source);
                }
            }
            $img->save();
            $this->reorganizar($img->getRef(), $img->getRefId());
            if ($excluirSecundarias) {
                $this->excluirTodasImagens($img->getRef(), $img->getRefId(), true);
            }
            return $this;
        } else if ($files) {
            $count = 0;
            foreach ($files as $file) {
                if ($source = $this->moveFile($file)) {
                    $newImg = clone $img;
                    $newImg->setSource($source);
                    $newImg->setPosition($last ? 9999 : 0);
                    $newImg->save();
                    $count++;
                }
            }
            $this->reorganizar($img->getRef(), $img->getRefId());
            if ($excluirSecundarias) {
                $this->excluirTodasImagens($img->getRef(), $img->getRefId(), true);
            }
            return $count ? true : false;
        }
        return false;
    }

    /**
     * Retorna uma array com todos os arquivos enviados
     * @param string $fileInputKeyName
     * @return array
     */
    public function getArrayFiles($fileInputKeyName)
    {
        $files = isset($_FILES[$fileInputKeyName]) ? $_FILES[$fileInputKeyName] : null;
        if ($files) {
            if (is_array($files['tmp_name'])) {
                $array = array();
                foreach ($files['tmp_name'] as $key => $tmp) {
                    //verifica: Error, isImage, extension
                    $extension = strtolower(Utils::getFileExtension($files['name'][$key]));
                    if ($files['error'][$key] == UPLOAD_ERR_OK and $size = getimagesize($tmp)) {
                        if ($this->validateFile($files['name'][$key], $files['size'][$key], $files['type'][$key], $tmp)) {
                            $array[] = [
                                'path' => $tmp,
                                'name' => date('Ymd_') . uniqid() . '.' . $extension,
                                'size' => $files['size'][$key],
                                'width' => $size[0],
                                'height' => $size[1],
                                'type' => $files['type'][$key],
                            ];
                        }
                    }
                }
                return empty($array) ? null : $array;
            } //verifica: Error, isImage, extension
            else if ($files['error'] == UPLOAD_ERR_OK and ($size = getimagesize($files['tmp_name']))) {
                if ($this->validateFile($files['name'], $files['size'], $files['type'], $files['tmp_name'])) {
                    $extension = strtolower(Utils::getFileExtension($files['name']));
                    return [
                        [
                            'path' => $files['tmp_name'],
                            'name' => date('Ymd_') . uniqid() . '.' . $extension,
                            'size' => $files['size'],
                            'width' => $size[0],
                            'height' => $size[1],
                            'type' => $files['type'],
                        ]
                    ];
                }
            }
        }
        return null;
    }

    private function validateFile($name, $size, $mimeType, $tmpPath = null)
    {
        $ext = strtolower(Utils::getFileExtension($name));
        if (!in_array($ext, $this->ImgExtensions)) {
            return false;
        } else if (!in_array($mimeType, $this->MimeTypes)) {
            return false;
        } else if ($size > 15 * 1024 * 1024) {
            return false;
        }

        // Validação de segurança: verificação do MIME real do arquivo no disco via finfo
        if ($tmpPath && file_exists($tmpPath)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $realMime = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
            if (!in_array($realMime, $this->MimeTypes)) {
                return false;
            }
        }
        return true;
    }

    private function moveFile($file)
    {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['size'] > 800 * KB) {

            if ($file['size'] > 2 * MB) {
                $quality = 60;
            } else if ($file['size'] > 1 * MB) {
                $quality = 80;
            } else {
                $quality = 100;
            }


            if ($file['width'] > 2000 or $file['height'] > 2000) {
                if ($file['width'] > $file['height']) {
                    $scale = 2000 / $file['width'];
                } else {
                    $scale = 2000 / $file['height'];
                }
                $file['width'] = $file['width'] * $scale;
                $file['height'] = $file['height'] * $scale;
            }

            $canvas = new IMGCanvas($file['path']);
            if ($ext !== 'png') {
                $canvas->hexa('#FFFFFF');
            }

            $salva = $canvas
                ->redimensiona($file['width'], $file['height'], 'proporcional')
                ->grava(self::getImagePath() . DIRECTORY_SEPARATOR . $file['name'], $quality);

            return $salva ? $file['name'] : null;
        } else {
            $dest = self::getImagePath() . DIRECTORY_SEPARATOR . $file['name'];
            $moved = is_uploaded_file($file['path']) ? move_uploaded_file($file['path'], $dest) : copy($file['path'], $dest);
            return $moved ? $file['name'] : null;
        }
    }

    public static function getImagePath()
    {
        $path = abs_source_images();
        if (!file_exists($path)) {
            mkdir($path, 0777);
        }
        return $path;
    }

    /**
     * Reorganiza as imagens na tabela
     * @param string $ref
     * @param int $refId
     */
    function reorganizar($ref, $refId)
    {
        /** @var ImageVO[] $imagens */
        $imagens = self::lista('WHERE a.ref = :ref AND a.refid = :refid AND a.status != 99 ORDER BY a.position ASC', ['ref' => $ref, 'refid' => $refId]);

        foreach ($imagens as $i => $img) {
            $img->setPosition($i + 1);
            $img->save();
        }
    }

    /**
     * Excluí todas as imagens da referencia
     * @param string $ref
     * @param int $refId
     * @param boolean $manterPrincipal
     */
    static function excluirTodasImagens($ref, $refId, $manterPrincipal = false)
    {
        /** @var ImageVO $img */
        foreach (self::lista('WHERE a.ref = :ref AND a.refid = :id', ['ref' => $ref, 'id' => $refId]) as $img) {
            if (!$manterPrincipal or $img->getPosition() != 1) {
                self::excluirImage($img);
            }
        }
    }

    /**
     * Excluí a imagem da galeria
     * @param int $id
     * @return string|boolean
     */
    static function excluirImage(ImageVO $img)
    {
        $deleta = self::excluir($img->getId());
        self::unlinkImage($img->getSource());
    }

    /**
     * Deleta o arquivo na pasta
     * @param string $name
     * @return boolean
     */
    private static function unlinkImage($name)
    {
        $path = self::getImagePath() . DIRECTORY_SEPARATOR . $name;
        if (!preg_match('/default/', $path) and file_exists($path) and is_file($path)) {
            return unlink($path);
        }
        return false;
    }

}
    