<?php

namespace app\vo\financeiro;

use app\core\ValueObject;
use app\helpers\Number;
use app\models\DadosModel;
use app\models\financeiro\DepositosModel;
use app\traits\vo\user;

class DepositoVO extends ValueObject
{

    use user;

    private $tipo;
    private $valor;
    private $transacaoid;
    private $qrcode;
    private $qrcodepng;
    private $payload;

    public function check()
    {
        if (!$this->voUser()) {
            throw new \Exception("Usuário inválido");
        }

        if (!$this->getId()) {
            $valorMinimo = DadosModel::get()->getDepositoMinimo();
            if ($this->getValor() < $valorMinimo) {
                $valorMinimo = Number::real($valorMinimo);
                throw new \Exception("Valor mínimo para depósito é de R$ {$valorMinimo}");
            }
        }
    }

    /**
     * @return mixed
     */
    public function getValor($formatar = false)
    {
        return $this->formatValue($this->valor, 'real', $formatar);
    }

    /**
     * @param mixed $valor
     * @return $this
     */
    public function setValor($valor)
    {
        $this->valor = $valor;
        return $this;
    }

    public function getStatusTitle()
    {
        return DepositosModel::STATUS[$this->getStatus()] ?? 'Inválido';
    }

    /**
     * @return mixed
     */
    public function getData($formatar = false)
    {
        return $this->formatValue($this->getInsert(), 'data', $formatar);
    }

    /**
     * @return mixed
     */
    public function getTipo()
    {
        return $this->tipo;
    }

    /**
     * @param mixed $tipo
     * @return $this
     */
    public function setTipo($tipo)
    {
        $this->tipo = $tipo;
        return $this;
    }

    public function getTransacaoId()
    {
        return $this->transacaoid;
    }

    public function setTransacaoId($transacaoid)
    {
        $this->transacaoid = $transacaoid;
        return $this;
    }

    public function getQrcode()
    {
        return $this->qrcode;
    }

    public function setQrcode($qrcode)
    {
        $this->qrcode = $qrcode;
        return $this;
    }

    public function getQrcodePng()
    {
        return $this->qrcodepng;
    }

    public function setQrcodePng($qrcodepng)
    {
        $this->qrcodepng = $qrcodepng;
        return $this;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function setPayload($payload)
    {
        $this->payload = $payload;
        return $this;
    }

}