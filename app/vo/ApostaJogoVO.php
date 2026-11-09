<?php

namespace app\vo;

use app\core\ValueObject;
use app\models\ApostasModel;
use app\models\DadosModel;
use app\models\JogosModel;
use app\traits\vo\user;
use Exception;

class ApostaJogoVO extends ValueObject
{

    use user;

    private $aposta;
    private $jogo;
    private $valor;
    private $tipo;
    private $tempo = 90;
    private $cotacaoValor;
    private $cotacaoSigla;
    private $cotacaoTitle;
    private $cotacaoGrupo;
    private $cotacaoCampo;
    private $verificado = 0;
    private $acertou;
    private $jogos;
    private $casa;
    private $fora;
    private $cambista = 0;
    private $regiao = 0;

    public function getCambista()
    {
        return (int)$this->cambista;
    }

    public function setCambista($cambista)
    {
        $this->cambista = (int)$cambista;
        return $this;
    }

    public function getRegiao()
    {
        return (int)$this->regiao;
    }

    public function setRegiao($regiao)
    {
        $this->regiao = (int)$regiao;
        return $this;
    }

    public function getStatusTitle()
    {
        switch ($this->getStatus()) {
            case 0:
                return 'Cancelado';
                break;
            case 1:
                if ($this->getVerificado()) {
                    return ['Errou', 'Acertou'][$this->getAcertou()];
                } else {
                    return 'Aguardando resultado';
                }
            default:
                return 'Status inválido: ' . $this->getStatus();
        }
    }

    public function getVerificado()
    {
        return $this->verificado;
    }

    public function setVerificado($verificado)
    {
        $this->verificado = $verificado;
        return $this;
    }

    public function getAcertou()
    {
        return $this->acertou ? 1 : 0;
    }

    public function setAcertou($acertou)
    {
        $this->acertou = $acertou;
        return $this;
    }

    public function getIsEditavel()
    {
        if ($this->getStatus() == 1 and !$this->getVerificado() and $this->voJogo() and !$this->voJogo()->jaComecou()) {
            return true;
        }
        return false;
    }

    /** @return JogoVO */
    public function voJogo()
    {
        $jogo = JogosModel::getByLabel('id', $this->getJogo(), true);
        if (!$jogo instanceof JogoVO) {
            $jogo = new JogoVO();
        }
        return $jogo;
    }

    public function getJogo()
    {
        return (int)$this->jogo;
    }

    public function setJogo($jogo)
    {
        $this->jogo = $jogo;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCotacaoGrupo()
    {
        return $this->cotacaoGrupo;
    }

    /**
     * @param mixed $cotacaoGrupo
     * @return $this
     */
    public function setCotacaoGrupo($cotacaoGrupo)
    {
        $this->cotacaoGrupo = $cotacaoGrupo;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCotacaoSigla()
    {
        return $this->cotacaoSigla;
    }

    /**
     * @param mixed $cotacaoSigla
     * @return $this
     */
    public function setCotacaoSigla($cotacaoSigla)
    {
        $this->cotacaoSigla = $cotacaoSigla;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCotacaoTitle()
    {
        return $this->cotacaoTitle;
    }

    /**
     * @param mixed $cotacaoTitle
     * @return $this
     */
    public function setCotacaoTitle($cotacaoTitle)
    {
        $this->cotacaoTitle = $cotacaoTitle;
        return $this;
    }

    /**
     * @return int
     */
    public function getTempoTitle()
    {
        switch ($this->getTempo()) {
            case 'pt':
                return 'Primeiro Tempo';
                break;
            case 'st':
                return 'Segundo Tempo';
                break;
            default:
                return 'Resultado Final';
        }
    }

    /**
     * @return int
     */
    public function getTempo()
    {
        return $this->tempo;
    }

    /**
     * @param int $tempo
     * @return $this
     */
    public function setTempo($tempo)
    {
        if (!in_array($tempo, ['90', 'st', 'pt'])) {
            throw new Exception('Tempo da aposta inválido');
        }
        $this->tempo = $tempo;
        return $this;
    }

    public function getJogos()
    {
        return $this->jogos;
    }

    public function setJogos($jogos)
    {
        $this->jogos = $jogos;
        return $this;
    }

    public function getCasa()
    {
        return (int)$this->casa;
    }

    public function setCasa($casa)
    {
        $this->casa = $casa;
        return $this;
    }

    public function getFora()
    {
        return (int)$this->fora;
    }

    public function setFora($fora)
    {
        $this->fora = $fora;
        return $this;
    }

    public function check()
    {
        if ($this->getId() and !$this->voAposta()) {
            error_log("Error: Aposta #{$this->getAposta()} inválida para o jogo #{$this->getId()}");
        }

        $jogo = $this->voJogo();
        $config = DadosModel::get();

        if (!$jogo instanceof JogoVO or !$config instanceof DadosVO) {
            throw new Exception('Jogo inválido.');
        } else if (!$this->getId()) {
            if ((int)$jogo->getStatus() !== 1) {
                throw new Exception("O Jogo {$jogo->getTimeCasaTitle()} x {$jogo->getTimeForaTitle()} foi cancelado ou não está ativo.");
            } else if ($jogo->jaComecou()) {
                throw new Exception("O Jogo {$jogo->getTimeCasaTitle()} x {$jogo->getTimeForaTitle()} já se iniciou.");
            } else if (!$jogo->aceitaApostas()) {
                throw new Exception("O Jogo {$jogo->getTimeCasaTitle()} x {$jogo->getTimeForaTitle()} não está recebendo mais apostas.");
            }
        }

        if ($this->getValor() <= 0) {
            throw new Exception('Valor da aposta inválido.');
        }

        if (!$this->getCotacaoCampo()) {
            throw new Exception('Não foi definido o tipo da aposta.');
        }

        if ($this->getCotacaoValor() < 1) {
            throw new Exception('Valor do cotação é inválido');
        }
    }

    /** @return ApostaVO */
    public function voAposta()
    {
        return ApostasModel::getByLabel('id', $this->getAposta(), true);
    }

    public function getAposta()
    {
        return (int)$this->aposta;
    }

    public function setAposta($aposta)
    {
        $this->aposta = $aposta;
        return $this;
    }

    public function getValor($toReal = false)
    {
        return $this->formatValue($this->valor, 'real', $toReal);
    }

    public function setValor($valor)
    {
        $this->valor = $valor;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCotacaoCampo()
    {
        return $this->cotacaoCampo;
    }

    /**
     * @param mixed $cotacaoCampo
     * @return $this
     */
    public function setCotacaoCampo($cotacaoCampo)
    {
        $this->cotacaoCampo = $cotacaoCampo;
        return $this;
    }

    public function getCotacaoValor($toReal = false)
    {
        return $this->formatValue($this->cotacaoValor, 'real', $toReal);
    }

    public function setCotacaoValor($cotacaoValor)
    {
        $this->cotacaoValor = $cotacaoValor;
        return $this;
    }

    /**
     * @return string
     */
    public function getTipo()
    {
        return trim((string)$this->tipo);
    }

    public function setTipo($tipo)
    {
        $this->tipo = $tipo;
        return $this;
    }

}
    
