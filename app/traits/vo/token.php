<?php

namespace app\traits\vo;

use app\core\Model;
use app\helpers\Utils;

trait token
{

    protected $token;

    /**
     * Gera o TOKEN único do Objeto
     * @return string TOKEN 50 caracteres
     * @throws \Exception
     */
    public function getToken()
    {
        if (!$this->token) {
            $tentativas = 20;
            /** @var Model $model */
            $model = $this->voModel();
            while (!$this->token or $model::lista('WHERE a.token = :token AND a.id != :id LIMIT 1', ['token' => $this->getToken(), 'id' => $this->getId()])) {
                if (!$tentativas) {
                    throw new \Exception('Não foi possível gerar o TOKEN');
                }
                $tentativas--;
                $this->setToken('21' . uniqid() . Utils::gerarCodigo(35, false, true, true, false));
            }
        }
        return strtoupper($this->token);
    }

    /**
     * Define o TOKEN
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

}
    