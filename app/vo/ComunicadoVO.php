<?php

namespace app\vo;

use app\core\ValueObject;
use app\traits\vo\user;

class ComunicadoVO extends ValueObject
{

    use user;

    private $data;
    private $hora;
    private $title;
    private $mensagem;

    /**
     * @return mixed
     */
    public function getData($formatar = false)
    {
        return $this->formatValue($this->data, 'data', $formatar);
    }

    /**
     * @param mixed $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHora()
    {
        return substr($this->hora, 0, 5);
    }

    /**
     * @param mixed $hora
     * @return $this
     */
    public function setHora($hora)
    {
        $this->hora = $hora;
        return $this;
    }


    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMensagem()
    {
        return $this->mensagem;
    }

    /**
     * @param mixed $mensagem
     * @return $this
     */
    public function setMensagem($mensagem)
    {
        $this->mensagem = $mensagem;
        return $this;
    }

}