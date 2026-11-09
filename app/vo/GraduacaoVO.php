<?php

namespace app\vo;

use app\core\ValueObject;

class GraduacaoVO extends ValueObject
{

    private $ordem;
    private $title;
    private $descricao;
    private $texto;
    private $indicadosTotal;
    private $indicadosDiretos;
    private $pontos;
    private $minJogos;
    private $apostaMinima;
    private $apostaMaxima;
    private $cotacaoMinima;
    private $cotacaoMaxima;
    private $jogos1;
    private $jogos2;
    private $jogos3;
    private $jogos4;

    /**
     * @return mixed
     */
    public function getCotacaoMinima($formatar = false)
    {
        return $this->formatValue($this->cotacaoMinima, 'real', $formatar);
    }

    /**
     * @param mixed $cotacaoMinima
     * @return $this
     */
    public function setCotacaoMinima($cotacaoMinima)
    {
        $this->cotacaoMinima = $cotacaoMinima;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCotacaoMaxima($formatar = false)
    {
        return $this->formatValue($this->cotacaoMaxima, 'real', $formatar);
    }

    /**
     * @param mixed $cotacaoMaxima
     * @return $this
     */
    public function setCotacaoMaxima($cotacaoMaxima)
    {
        $this->cotacaoMaxima = $cotacaoMaxima;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMinJogos()
    {
        return $this->minJogos;
    }

    /**
     * @param mixed $minJogos
     * @return $this
     */
    public function setMinJogos($minJogos)
    {
        $this->minJogos = $minJogos;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getApostaMinima($formatar = false)
    {
        return $this->formatValue($this->apostaMinima, 'real', $formatar);
    }

    /**
     * @param mixed $apostaMinima
     * @return $this
     */
    public function setApostaMinima($apostaMinima)
    {
        $this->apostaMinima = $apostaMinima;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getApostaMaxima($formatar = false)
    {
        return $this->formatValue($this->apostaMaxima, 'real', $formatar);
    }

    /**
     * @param mixed $apostaMaxima
     * @return $this
     */
    public function setApostaMaxima($apostaMaxima)
    {
        $this->apostaMaxima = $apostaMaxima;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getJogos4()
    {
        return (float)$this->jogos4;
    }

    /**
     * @param mixed $jogos4
     * @return $this
     */
    public function setJogos4($jogos4)
    {
        $this->jogos4 = $jogos4;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getJogos1()
    {
        return (float)$this->jogos1;
    }

    /**
     * @param mixed $jogos1
     * @return $this
     */
    public function setJogos1($jogos1)
    {
        $this->jogos1 = $jogos1;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getJogos2()
    {
        return (float)$this->jogos2;
    }

    /**
     * @param mixed $jogos2
     * @return $this
     */
    public function setJogos2($jogos2)
    {
        $this->jogos2 = $jogos2;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getJogos3()
    {
        return (float)$this->jogos3;
    }

    /**
     * @param mixed $jogos3
     * @return $this
     */
    public function setJogos3($jogos3)
    {
        $this->jogos3 = $jogos3;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOrdem()
    {
        return $this->ordem;
    }

    /**
     * @param mixed $ordem
     * @return $this
     */
    public function setOrdem($ordem)
    {
        $this->ordem = $ordem;
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
    public function getDescricao()
    {
        return $this->descricao;
    }

    /**
     * @param mixed $descricao
     * @return $this
     */
    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTexto()
    {
        return $this->texto;
    }

    /**
     * @param mixed $texto
     * @return $this
     */
    public function setTexto($texto)
    {
        $this->texto = $texto;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIndicadosTotal()
    {
        return $this->indicadosTotal;
    }

    /**
     * @param mixed $indicadosTotal
     * @return $this
     */
    public function setIndicadosTotal($indicadosTotal)
    {
        $this->indicadosTotal = $indicadosTotal;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getIndicadosDiretos()
    {
        return $this->indicadosDiretos;
    }

    /**
     * @param mixed $indicadosDiretos
     * @return $this
     */
    public function setIndicadosDiretos($indicadosDiretos)
    {
        $this->indicadosDiretos = $indicadosDiretos;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPontos()
    {
        return $this->pontos;
    }

    /**
     * @param mixed $pontos
     * @return $this
     */
    public function setPontos($pontos)
    {
        $this->pontos = $pontos;
        return $this;
    }

}