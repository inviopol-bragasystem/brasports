<?php

namespace app\traits;

use app\models\UsersModel;
use app\vo\UserVO;

trait gerente
{

    private $gerente;

    /**
     * @return UserVO|null
     */
    public function voGerente()
    {
        return UsersModel::getByLabel('id', $this->getGerente());
    }

    /**
     * @return mixed
     */
    public function getGerente()
    {
        return (int)$this->gerente;
    }

    /**
     * @param mixed $gerente
     * @return $this
     */
    public function setGerente($gerente)
    {
        $this->gerente = $gerente;
        return $this;
    }

}