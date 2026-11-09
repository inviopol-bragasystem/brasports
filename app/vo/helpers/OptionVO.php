<?php

namespace app\vo\helpers;

use app\core\ValueObject;
use app\helpers\Utils;
use app\traits\vo\urlAmigavel;
use Exception;

class OptionVO extends ValueObject
{

    use urlAmigavel;

    private $root;
    private $ref;
    private $refId;
    private $title;
    private $ordem;
    private $site = 0;
    private $data = '';
    private $obs = '';

    public function getSite()
    {
        return (int)$this->site;
    }

    public function setSite($site)
    {
        $this->site = (int)$site;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data ?? '';
        return $this;
    }

    public function getObs()
    {
        return $this->obs;
    }

    public function setObs($obs)
    {
        $this->obs = $obs ?? '';
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * @param $root
     * @return $this
     */
    public function setRoot($root)
    {
        $this->root = $root;
        return $this;
    }

    /**
     * @return int
     */
    public function getOrdem()
    {
        return (int)$this->ordem;
    }

    /**
     * @param $ordem
     * @return $this
     */
    public function setOrdem($ordem)
    {
        $this->ordem = $ordem;
        return $this;
    }

    public function check()
    {
        if (!trim($this->getTitle())) {
            throw new Exception('Informe o título.');
        } else if (!$this->getUrlAmigavel()) {
            $this->UrlAmigavel = $this->getTitle();
        }

        while ($this->voModel()->lista('WHERE a.urlamigavel = :url AND a.ref = :ref AND a.refid = :refid AND a.id != :id AND a.status != 99 LIMIT 1', [
            'id' => $this->getId(),
            'url' => $this->getUrlAmigavel(),
            'ref' => $this->getRef(),
            'refid' => $this->getRefId(),
        ])) {
            $this->UrlAmigavel = $this->getTitle() . ' ' . Utils::gerarCodigo(5, false, false, true, false);
        }
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param $title
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
    public function getRef()
    {
        return $this->ref;
    }

    /**
     * @param $ref
     * @return $this
     */
    public function setRef($ref)
    {
        $this->ref = $ref;
        return $this;
    }

    /**
     * @return int
     */
    public function getRefId()
    {
        return (int)$this->refId;
    }

    /**
     * @param $refId
     * @return $this
     */
    public function setRefId($refId)
    {
        $this->refId = $refId;
        return $this;
    }

}
    
