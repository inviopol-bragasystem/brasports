<?php

namespace app\vo;

use app\core\ValueObject;
use app\helpers\Number;
use app\traits\vo\cidade;
use app\traits\vo\email;

class DadosVO extends ValueObject
{

    use cidade;
    use email;

    private $banca;
    private $corPrimaria;
    private $corSecundaria;
    private $limiteUsuarios;
    private $valorGrupo = 0;
    private $periodoInicial;
    private $periodoFinal;
    private $periodoValor;
    private $app;
    private $informacoes;
    private $regulamento;
    private $regrasAposta;
    private $bloqueado;
    private $imprimirRegras;
    private $imprimirLogo;
    private $revendaPaga = 0;
    private $valorPreCadastro;
    private $comissaoJogador1;
    private $comissaoJogador2;
    private $comissaoJogador3;
    private $comissaoJogador4;
    private $comissaoJogador5;
    private $comissaoJogadorIndicacao;
    private $comissaoConsultor1;
    private $comissaoConsultor2;
    private $comissaoConsultor3;
    private $comissaoConsultor4;
    private $comissaoConsultor5;
    private $comissaoConsultorIndicacao;
    private $comissaoFranqueado1;
    private $comissaoFranqueado2;
    private $comissaoFranqueado3;
    private $comissaoFranqueado4;
    private $comissaoFranqueado5;
    private $comissaoFranqueadoIndicacao;
    private $valorPlanoJogador;
    private $valorPlanoConsultor;
    private $valorPlanoFranqueado;
    private $valorAdesaoJogador;
    private $valorAdesaoConsultor;
    private $valorAdesaoFranqueado;
    private $valorRecargaJogador;
    private $valorRecargaConsultor;
    private $valorRecargaFranqueado;
    private $cotacaoMinima;
    private $cotacaoMaxima;
    private $retornoMaximo;
    private $maxApostasDia;
    private $apostaMinima;
    private $apostaMaxima;
    private $bonusApuracaoJogador1;
    private $bonusApuracaoJogador2;
    private $bonusApuracaoJogador3;
    private $bonusApuracaoJogador4;
    private $bonusApuracaoJogador5;
    private $bonusApuracaoConsultor1;
    private $bonusApuracaoConsultor2;
    private $bonusApuracaoConsultor3;
    private $bonusApuracaoConsultor4;
    private $bonusApuracaoConsultor5;
    private $bonusApuracaoFranqueado1;
    private $bonusApuracaoFranqueado2;
    private $bonusApuracaoFranqueado3;
    private $bonusApuracaoFranqueado4;
    private $bonusApuracaoFranqueado5;
    private $taxaSaque;
    private $taxaTransferencia;
    private $textoImpressoras;
    private $depositoMinimo;
    private $maxApostas;
    private $minJogos;
    private $limite1;
    private $limite2;
    private $limite3;
    private $limite4;
    private $saqueIndicados;
    private $saldoInicial;
    private $limiteCotacao;
    private $linkCadastro;
    private $retornoRisco;
    private $minutosJogo = 5;
    private $htmlHead;
    private $htmlFooter;
    private $linkSaque;
    private $linkComprar;
    private $condicaoCotacao;
    private $apiPrefixo;
    private $tema = 'site1';
    private $exibirescudos = 1;
    private $exibirRegistro = 1;
    private $logowidth = 160;
    private $logoheight = 45;
    private $slideheight = 320;
    private $slideheightmobile = 160;

    public function getValorGrupo()
    {
        return (int)$this->valorGrupo;
    }

    public function setValorGrupo($valorGrupo)
    {
        $this->valorGrupo = (int)$valorGrupo;
        return $this;
    }
    private $exibirlogin = 1;
    private $exibirapp = 1;
    private $exibirbilhete = 1;
    private $exibircotacoes = 1;
    private $exibirregras = 1;
    private $exibircontato = 1;
    private $exibircomunicados = 1;
    private $exibircassino = 1;
    private $tiposlideshome = 'mesclado';
    private $tiposlidescassino = 'mesclado';
    private $mododeposito = 'todos';
    private $pixup_client_id;
    private $pixup_client_secret;
    private $pixup_status = 1;
    private $gatewaypix = 'pixup';
    private $veopag_client_id;
    private $veopag_client_secret;
    private $veopag_webhook_secret;
    private $veopag_webhook_token;
    private $veopag_status = 0;

    /**
     * @return string
     */
    public function getApiPrefixo()
    {
        return $this->apiPrefixo ?: 'marjo';
    }

    /**
     * @param string $apiPrefixo
     * @return $this
     */
    public function setApiPrefixo($apiPrefixo)
    {
        $this->apiPrefixo = $apiPrefixo;
        return $this;
    }

    /**
     * Tema visual ativo do site
     * @return string
     */
    public function getTema()
    {
        return $this->tema ?: 'site1';
    }

    /**
     * @param string $tema
     * @return $this
     */
    public function setTema($tema)
    {
        $this->tema = $tema;
        return $this;
    }

    /**
     * Define se os escudos dos times são exibidos nos confrontos
     * @return int
     */
    public function getExibirEscudos()
    {
        return ($this->exibirescudos === null || $this->exibirescudos === '' || $this->exibirescudos) ? 1 : 0;
    }

    /**
     * @param mixed $exibirescudos
     * @return $this
     */
    public function setExibirEscudos($exibirescudos)
    {
        $this->exibirescudos = $exibirescudos ? 1 : 0;
        return $this;
    }

    /**
     * Verifica se a área de registro está visível no site
     * @return int
     */
    public function getExibirRegistro()
    {
        return $this->exibirRegistro ? 1 : 0;
    }

    /**
     * @param mixed $exibirRegistro
     * @return $this
     */
    public function setExibirRegistro($exibirRegistro)
    {
        $this->exibirRegistro = $exibirRegistro ? 1 : 0;
        return $this;
    }

    public function getExibirCassino()
    {
        return $this->exibircassino ? 1 : 0;
    }

    public function setExibirCassino($exibirCassino)
    {
        $this->exibircassino = $exibirCassino ? 1 : 0;
        return $this;
    }

    public function getTipoSlidesHome()
    {
        return $this->tiposlideshome ?: 'mesclado';
    }

    public function setTipoSlidesHome($tipo)
    {
        $this->tiposlideshome = $tipo ?: 'mesclado';
        return $this;
    }

    public function getTipoSlidesCassino()
    {
        return $this->tiposlidescassino ?: 'mesclado';
    }

    public function setTipoSlidesCassino($tipo)
    {
        $this->tiposlidescassino = $tipo ?: 'mesclado';
        return $this;
    }

    public function getLogoWidth()
    {
        return (int)($this->logowidth ?: 160);
    }

    public function setLogoWidth($v)
    {
        $this->logowidth = (int)$v;
        return $this;
    }

    public function getLogoHeight()
    {
        return (int)($this->logoheight ?: 45);
    }

    public function setLogoHeight($v)
    {
        $this->logoheight = (int)$v;
        return $this;
    }

    public function getSlideHeight()
    {
        return (int)($this->slideheight ?: 320);
    }

    public function setSlideHeight($v)
    {
        $this->slideheight = (int)$v;
        return $this;
    }

    public function getSlideHeightMobile()
    {
        return (int)($this->slideheightmobile ?: 160);
    }

    public function setSlideHeightMobile($v)
    {
        $this->slideheightmobile = (int)$v;
        return $this;
    }

    public function getExibirLogin()
    {
        return ($this->exibirlogin === null || $this->exibirlogin === '' || $this->exibirlogin) ? 1 : 0;
    }

    public function setExibirLogin($v)
    {
        $this->exibirlogin = $v ? 1 : 0;
        return $this;
    }

    public function getExibirApp()
    {
        return ($this->exibirapp === null || $this->exibirapp === '' || $this->exibirapp) ? 1 : 0;
    }

    public function setExibirApp($v)
    {
        $this->exibirapp = $v ? 1 : 0;
        return $this;
    }

    public function getExibirBilhete()
    {
        return ($this->exibirbilhete === null || $this->exibirbilhete === '' || $this->exibirbilhete) ? 1 : 0;
    }

    public function setExibirBilhete($v)
    {
        $this->exibirbilhete = $v ? 1 : 0;
        return $this;
    }

    public function getExibirCotacoes()
    {
        return ($this->exibircotacoes === null || $this->exibircotacoes === '' || $this->exibircotacoes) ? 1 : 0;
    }

    public function setExibirCotacoes($v)
    {
        $this->exibircotacoes = $v ? 1 : 0;
        return $this;
    }

    public function getExibirRegras()
    {
        return ($this->exibirregras === null || $this->exibirregras === '' || $this->exibirregras) ? 1 : 0;
    }

    public function setExibirRegras($v)
    {
        $this->exibirregras = $v ? 1 : 0;
        return $this;
    }

    public function getExibirContato()
    {
        return ($this->exibircontato === null || $this->exibircontato === '' || $this->exibircontato) ? 1 : 0;
    }

    public function setExibirContato($v)
    {
        $this->exibircontato = $v ? 1 : 0;
        return $this;
    }

    public function getExibirComunicados()
    {
        return ($this->exibircomunicados === null || $this->exibircomunicados === '' || $this->exibircomunicados) ? 1 : 0;
    }

    public function setExibirComunicados($v)
    {
        $this->exibircomunicados = $v ? 1 : 0;
        return $this;
    }

    public function getModoDeposito()
    {
        return $this->mododeposito ?: 'todos';
    }

    public function setModoDeposito($v)
    {
        $this->mododeposito = $v ?: 'todos';
        return $this;
    }

    public function getPixupClientId()
    {
        return $this->pixup_client_id;
    }

    public function setPixupClientId($v)
    {
        $this->pixup_client_id = $v;
        return $this;
    }

    public function setPixup_client_id($v)
    {
        return $this->setPixupClientId($v);
    }

    public function getPixupClientSecret()
    {
        return $this->pixup_client_secret;
    }

    public function setPixupClientSecret($v)
    {
        $this->pixup_client_secret = $v;
        return $this;
    }

    public function setPixup_client_secret($v)
    {
        return $this->setPixupClientSecret($v);
    }

    public function getPixupStatus()
    {
        return ($this->pixup_status === null || $this->pixup_status === '' || $this->pixup_status) ? 1 : 0;
    }

    public function setPixupStatus($v)
    {
        $this->pixup_status = $v ? 1 : 0;
        return $this;
    }

    public function setPixup_status($v)
    {
        return $this->setPixupStatus($v);
    }

    public function getGatewaypix() { return in_array($this->gatewaypix, ['pixup', 'veopag'], true) ? $this->gatewaypix : 'pixup'; }
    public function setGatewaypix($v) { $this->gatewaypix = in_array($v, ['pixup', 'veopag'], true) ? $v : 'pixup'; return $this; }
    public function getVeopagClientId() { return $this->veopag_client_id; }
    public function setVeopagClientId($v) { $this->veopag_client_id = $v; return $this; }
    public function getVeopagClientSecret() { return $this->veopag_client_secret; }
    public function setVeopagClientSecret($v) { $this->veopag_client_secret = $v; return $this; }
    public function getVeopagWebhookSecret() { return $this->veopag_webhook_secret; }
    public function setVeopagWebhookSecret($v) { $this->veopag_webhook_secret = $v; return $this; }
    public function getVeopagWebhookToken() { return $this->veopag_webhook_token; }
    public function setVeopagWebhookToken($v) { $this->veopag_webhook_token = $v; return $this; }
    public function getVeopagStatus() { return ($this->veopag_status === null || $this->veopag_status === '') ? 0 : (int)(bool)$this->veopag_status; }
    public function setVeopagStatus($v) { $this->veopag_status = $v ? 1 : 0; return $this; }
    public function setVeopag_client_id($v) { return $this->setVeopagClientId($v); }
    public function setVeopag_client_secret($v) { return $this->setVeopagClientSecret($v); }
    public function setVeopag_webhook_secret($v) { return $this->setVeopagWebhookSecret($v); }
    public function setVeopag_webhook_token($v) { return $this->setVeopagWebhookToken($v); }
    public function setVeopag_status($v) { return $this->setVeopagStatus($v); }

    /**
     * @param bool $toArray
     * @return mixed
     */
    public function getCondicaoCotacao($toArray = false)
    {
        if ($toArray)
            return json_decode($this->condicaoCotacao, true) ?: [];
        return $this->condicaoCotacao;
    }

    /**
     * @param mixed $condicaoCotacao
     * @return $this
     */
    public function setCondicaoCotacao($condicaoCotacao)
    {
        if (is_array($condicaoCotacao)) {
            foreach ($condicaoCotacao as $index => $values) {
                foreach ($values as $key => $value) {
                    $condicaoCotacao[$index][$key] = Number::float($value);
                }
            }
            $condicaoCotacao = json_encode($condicaoCotacao);
        } else if (is_string($condicaoCotacao) and $json = json_decode($condicaoCotacao, true)) {
            return $this->setCondicaoCotacao($json);
        }
        $this->condicaoCotacao = $condicaoCotacao;
        return $this;
    }


    /**
     * @return mixed
     */
    public function getLinkSaque()
    {
        return $this->linkSaque;
    }

    /**
     * @param mixed $linkSaque
     * @return $this
     */
    public function setLinkSaque($linkSaque)
    {
        $this->linkSaque = $linkSaque;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLinkComprar()
    {
        return $this->linkComprar;
    }

    /**
     * @param mixed $linkComprar
     * @return $this
     */
    public function setLinkComprar($linkComprar)
    {
        $this->linkComprar = $linkComprar;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHtmlHead()
    {
        return $this->htmlHead;
    }

    /**
     * @param mixed $htmlHead
     * @return $this
     */
    public function setHtmlHead($htmlHead)
    {
        $this->htmlHead = $htmlHead;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHtmlFooter()
    {
        return $this->htmlFooter;
    }

    /**
     * @param mixed $htmlFooter
     * @return $this
     */
    public function setHtmlFooter($htmlFooter)
    {
        $this->htmlFooter = $htmlFooter;
        return $this;
    }

    /**
     * @return int
     */
    public function getRevendaPaga()
    {
        return (int)$this->revendaPaga;
    }

    /**
     * @param int $revendaPaga
     * @return $this
     */
    public function setRevendaPaga($revendaPaga)
    {
        $this->revendaPaga = $revendaPaga;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMinutosJogo()
    {
        return (int)$this->minutosJogo;
    }

    /**
     * @param mixed $minutosJogo
     * @return $this
     */
    public function setMinutosJogo($minutosJogo)
    {
        $this->minutosJogo = $minutosJogo;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRetornoRisco($formatar = false)
    {
        return $this->formatValue($this->retornoRisco, 'real', $formatar);
    }

    /**
     * @param mixed $retornoRisco
     * @return $this
     */
    public function setRetornoRisco($retornoRisco)
    {
        $this->retornoRisco = $retornoRisco;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLinkCadastro()
    {
        return $this->linkCadastro;
    }

    /**
     * @param mixed $linkCadastro
     * @return $this
     */
    public function setLinkCadastro($linkCadastro)
    {
        $this->linkCadastro = $linkCadastro;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLimite4($formatar = false)
    {
        return $this->formatValue($this->limite4, 'real', $formatar);
    }

    /**
     * @param mixed $limite4
     * @return $this
     */
    public function setLimite4($limite4)
    {
        $this->limite4 = $limite4;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLimiteCotacao($formatar = false)
    {
        return $this->formatValue($this->limiteCotacao, 'real', $formatar);
    }

    /**
     * @param mixed $limiteCotacao
     * @return $this
     */
    public function setLimiteCotacao($limiteCotacao)
    {
        $this->limiteCotacao = $limiteCotacao;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSaldoInicial()
    {
        return $this->saldoInicial ? 1 : 0;
    }

    /**
     * @param mixed $saldoInicial
     * @return $this
     */
    public function setSaldoInicial($saldoInicial)
    {
        $this->saldoInicial = $saldoInicial;
        return $this;
    }

    /**
     * @param UserVO $user
     * @param bool $formatar
     * @return mixed
     */
    public function valorRecarga(UserVO $user, bool $formatar = false)
    {
        if ($licenca = $user->voLicenca()) {
            return $licenca->getRecargaMensal($formatar);
        } else {
            return 0;
        }
    }

    /**
     * @return mixed
     */
    public function getValorRecargaJogador($formatar = false)
    {
        return $this->formatValue($this->valorRecargaJogador, 'real', $formatar);
    }

    /**
     * @param mixed $valorRecargaJogador
     * @return $this
     */
    public function setValorRecargaJogador($valorRecargaJogador)
    {
        $this->valorRecargaJogador = $valorRecargaJogador;
        return $this;
    }

    /**
     * @param UserVO $user
     * @param bool $formatar
     * @return mixed
     */
    public function valorPlanoAdesao(UserVO $user, bool $formatar = false)
    {
        $valor = $this->valorPlano($user) + $this->valorAdesao($user);
        return $this->formatValue($valor, 'real', $formatar);
    }

    /**
     * @param UserVO $user
     * @param bool $formatar
     * @return mixed
     */
    public function valorPlano(UserVO $user, bool $formatar = false)
    {
        if ($licenca = $user->voLicenca()) {
            return $licenca->getValorPlano($formatar);
        } else {
            return 0;
        }
    }

    /**
     * @param UserVO $user
     * @param bool $formatar
     * @return mixed
     */
    public function valorAdesao(UserVO $user, bool $formatar = false)
    {
        if ($licenca = $user->voLicenca()) {
            return $licenca->getValorAdesao($formatar);
        } else {
            return 0;
        }
    }

    /**
     * @return mixed
     */
    public function getValorAdesaoJogador($formatar = false)
    {
        return $this->formatValue($this->valorAdesaoJogador, 'real', $formatar);
    }

    /**
     * @param mixed $valorAdesaoJogador
     * @return $this
     */
    public function setValorAdesaoJogador($valorAdesaoJogador)
    {
        $this->valorAdesaoJogador = $valorAdesaoJogador;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValorPlanoJogador($formatar = false)
    {
        return $this->formatValue($this->valorPlanoJogador, 'real', $formatar);
    }

    /**
     * @param mixed $valorPlanoJogador
     * @return $this
     */
    public function setValorPlanoJogador($valorPlanoJogador)
    {
        $this->valorPlanoJogador = $valorPlanoJogador;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMinJogos()
    {
        return (int)$this->minJogos;
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
    public function getMaxApostas()
    {
        return (int)$this->maxApostas;
    }

    /**
     * @param mixed $maxApostas
     * @return $this
     */
    public function setMaxApostas($maxApostas)
    {
        $this->maxApostas = $maxApostas;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLimite1($formatar = false)
    {
        return $this->formatValue($this->limite1, 'real', $formatar);
    }

    /**
     * @param mixed $limite1
     * @return $this
     */
    public function setLimite1($limite1)
    {
        $this->limite1 = $limite1;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLimite2($formatar = false)
    {
        return $this->formatValue($this->limite2, 'real', $formatar);
    }

    /**
     * @param mixed $limite2
     * @return $this
     */
    public function setLimite2($limite2)
    {
        $this->limite2 = $limite2;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLimite3($formatar = false)
    {
        return $this->formatValue($this->limite3, 'real', $formatar);
    }

    /**
     * @param mixed $limite3
     * @return $this
     */
    public function setLimite3($limite3)
    {
        $this->limite3 = $limite3;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSaqueIndicados()
    {
        return $this->saqueIndicados;
    }

    /**
     * @param mixed $saqueIndicados
     * @return $this
     */
    public function setSaqueIndicados($saqueIndicados)
    {
        $this->saqueIndicados = $saqueIndicados;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMaxApostasDia()
    {
        return (int)$this->maxApostasDia;
    }

    /**
     * @param mixed $maxApostasDia
     */
    public function setMaxApostasDia($maxApostasDia)
    {
        $this->maxApostasDia = $maxApostasDia;
    }

    /**
     * @return mixed
     */
    public function getRegrasAposta()
    {
        return $this->regrasAposta;
    }

    /**
     * @param mixed $regrasAposta
     * @return $this
     */
    public function setRegrasAposta($regrasAposta)
    {
        $this->regrasAposta = $regrasAposta;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDepositoMinimo($formatar = false)
    {
        return $this->formatValue($this->depositoMinimo, 'real', $formatar);
    }

    /**
     * @param mixed $depositoMinimo
     * @return $this
     */
    public function setDepositoMinimo($depositoMinimo)
    {
        $this->depositoMinimo = $depositoMinimo;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTextoImpressoras()
    {
        return $this->textoImpressoras;
    }

    /**
     * @param mixed $textoImpressoras
     * @return $this
     */
    public function setTextoImpressoras($textoImpressoras)
    {
        $this->textoImpressoras = $textoImpressoras;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTaxaTransferencia($formatar = false)
    {
        return $this->formatValue($this->taxaTransferencia, 'real', $formatar);
    }

    /**
     * @param mixed $taxaTransferencia
     * @return $this
     */
    public function setTaxaTransferencia($taxaTransferencia)
    {
        $this->taxaTransferencia = $taxaTransferencia;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getTaxaSaque($formatar = false)
    {
        return $this->formatValue($this->taxaSaque, 'real', $formatar);
    }

    /**
     * @param mixed $taxaSaque
     * @return $this
     */
    public function setTaxaSaque($taxaSaque)
    {
        $this->taxaSaque = $taxaSaque;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBonusApuracaoJogador1($formatar = false)
    {
        return $this->formatValue($this->bonusApuracaoJogador1, 'real', $formatar);
    }

    /**
     * @param mixed $bonusApuracaoJogador1
     * @return $this
     */
    public function setBonusApuracaoJogador1($bonusApuracaoJogador1)
    {
        $this->bonusApuracaoJogador1 = $bonusApuracaoJogador1;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBonusApuracaoJogador2($formatar = false)
    {
        return $this->formatValue($this->bonusApuracaoJogador2, 'real', $formatar);
    }

    /**
     * @param mixed $bonusApuracaoJogador2
     * @return $this
     */
    public function setBonusApuracaoJogador2($bonusApuracaoJogador2)
    {
        $this->bonusApuracaoJogador2 = $bonusApuracaoJogador2;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBonusApuracaoJogador3($formatar = false)
    {
        return $this->formatValue($this->bonusApuracaoJogador3, 'real', $formatar);
    }

    /**
     * @param mixed $bonusApuracaoJogador3
     * @return $this
     */
    public function setBonusApuracaoJogador3($bonusApuracaoJogador3)
    {
        $this->bonusApuracaoJogador3 = $bonusApuracaoJogador3;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBonusApuracaoJogador4($formatar = false)
    {
        return $this->formatValue($this->bonusApuracaoJogador4, 'real', $formatar);
    }

    /**
     * @param mixed $bonusApuracaoJogador4
     * @return $this
     */
    public function setBonusApuracaoJogador4($bonusApuracaoJogador4)
    {
        $this->bonusApuracaoJogador4 = $bonusApuracaoJogador4;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBonusApuracaoJogador5($formatar = false)
    {
        return $this->formatValue($this->bonusApuracaoJogador5, 'real', $formatar);
    }

    /**
     * @param mixed $bonusApuracaoJogador5
     * @return $this
     */
    public function setBonusApuracaoJogador5($bonusApuracaoJogador5)
    {
        $this->bonusApuracaoJogador5 = $bonusApuracaoJogador5;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBonusApuracaoConsultor1($formatar = false)
    {
        return $this->formatValue($this->bonusApuracaoConsultor1, 'real', $formatar);
    }

    /**
     * @param mixed $bonusApuracaoConsultor1
     * @return $this
     */
    public function setBonusApuracaoConsultor1($bonusApuracaoConsultor1)
    {
        $this->bonusApuracaoConsultor1 = $bonusApuracaoConsultor1;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBonusApuracaoConsultor2($formatar = false)
    {
        return $this->formatValue($this->bonusApuracaoConsultor2, 'real', $formatar);
    }

    /**
     * @param mixed $bonusApuracaoConsultor2
     * @return $this
     */
    public function setBonusApuracaoConsultor2($bonusApuracaoConsultor2)
    {
        $this->bonusApuracaoConsultor2 = $bonusApuracaoConsultor2;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBonusApuracaoConsultor3($formatar = false)
    {
        return $this->formatValue($this->bonusApuracaoConsultor3, 'real', $formatar);
    }

    /**
     * @param mixed $bonusApuracaoConsultor3
     * @return $this
     */
    public function setBonusApuracaoConsultor3($bonusApuracaoConsultor3)
    {
        $this->bonusApuracaoConsultor3 = $bonusApuracaoConsultor3;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBonusApuracaoConsultor4($formatar = false)
    {
        return $this->formatValue($this->bonusApuracaoConsultor4, 'real', $formatar);
    }

    /**
     * @param mixed $bonusApuracaoConsultor4
     * @return $this
     */
    public function setBonusApuracaoConsultor4($bonusApuracaoConsultor4)
    {
        $this->bonusApuracaoConsultor4 = $bonusApuracaoConsultor4;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBonusApuracaoConsultor5($formatar = false)
    {
        return $this->formatValue($this->bonusApuracaoConsultor5, 'real', $formatar);
    }

    /**
     * @param mixed $bonusApuracaoConsultor5
     * @return $this
     */
    public function setBonusApuracaoConsultor5($bonusApuracaoConsultor5)
    {
        $this->bonusApuracaoConsultor5 = $bonusApuracaoConsultor5;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBonusApuracaoFranqueado1($formatar = false)
    {
        return $this->formatValue($this->bonusApuracaoFranqueado1, 'real', $formatar);
    }

    /**
     * @param mixed $bonusApuracaoFranqueado1
     * @return $this
     */
    public function setBonusApuracaoFranqueado1($bonusApuracaoFranqueado1)
    {
        $this->bonusApuracaoFranqueado1 = $bonusApuracaoFranqueado1;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBonusApuracaoFranqueado2($formatar = false)
    {
        return $this->formatValue($this->bonusApuracaoFranqueado2, 'real', $formatar);
    }

    /**
     * @param mixed $bonusApuracaoFranqueado2
     * @return $this
     */
    public function setBonusApuracaoFranqueado2($bonusApuracaoFranqueado2)
    {
        $this->bonusApuracaoFranqueado2 = $bonusApuracaoFranqueado2;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBonusApuracaoFranqueado3($formatar = false)
    {
        return $this->formatValue($this->bonusApuracaoFranqueado3, 'real', $formatar);
    }

    /**
     * @param mixed $bonusApuracaoFranqueado3
     * @return $this
     */
    public function setBonusApuracaoFranqueado3($bonusApuracaoFranqueado3)
    {
        $this->bonusApuracaoFranqueado3 = $bonusApuracaoFranqueado3;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBonusApuracaoFranqueado4($formatar = false)
    {
        return $this->formatValue($this->bonusApuracaoFranqueado4, 'real', $formatar);
    }

    /**
     * @param mixed $bonusApuracaoFranqueado4
     * @return $this
     */
    public function setBonusApuracaoFranqueado4($bonusApuracaoFranqueado4)
    {
        $this->bonusApuracaoFranqueado4 = $bonusApuracaoFranqueado4;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getBonusApuracaoFranqueado5($formatar = false)
    {
        return $this->formatValue($this->bonusApuracaoFranqueado5, 'real', $formatar);
    }

    /**
     * @param mixed $bonusApuracaoFranqueado5
     * @return $this
     */
    public function setBonusApuracaoFranqueado5($bonusApuracaoFranqueado5)
    {
        $this->bonusApuracaoFranqueado5 = $bonusApuracaoFranqueado5;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValorPreCadastro($formatar = false)
    {
        return $this->formatValue($this->valorPreCadastro, 'real', $formatar);
    }

    /**
     * @param mixed $valorPreCadastro
     */
    public function setValorPreCadastro($valorPreCadastro)
    {
        $this->valorPreCadastro = $valorPreCadastro;
    }

    /**
     * @return mixed
     */
    public function getComissaoJogadorIndicacao($formatar = false)
    {
        return $this->formatValue($this->comissaoJogadorIndicacao, 'real', $formatar);
    }

    /**
     * @param mixed $comissaoJogadorIndicacao
     * @return $this
     */
    public function setComissaoJogadorIndicacao($comissaoJogadorIndicacao)
    {
        $this->comissaoJogadorIndicacao = $comissaoJogadorIndicacao;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComissaoConsultorIndicacao($formatar = false)
    {
        return $this->formatValue($this->comissaoConsultorIndicacao, 'real', $formatar);
    }

    /**
     * @param mixed $comissaoConsultorIndicacao
     * @return $this
     */
    public function setComissaoConsultorIndicacao($comissaoConsultorIndicacao)
    {
        $this->comissaoConsultorIndicacao = $comissaoConsultorIndicacao;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComissaoFranqueadoIndicacao($formatar = false)
    {
        return $this->formatValue($this->comissaoFranqueadoIndicacao, 'real', $formatar);
    }

    /**
     * @param mixed $comissaoFranqueadoIndicacao
     * @return $this
     */
    public function setComissaoFranqueadoIndicacao($comissaoFranqueadoIndicacao)
    {
        $this->comissaoFranqueadoIndicacao = $comissaoFranqueadoIndicacao;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValorPlanoConsultor($formatar = false)
    {
        return $this->formatValue($this->valorPlanoConsultor, 'real', $formatar);
    }

    /**
     * @param mixed $valorPlanoConsultor
     * @return $this
     */
    public function setValorPlanoConsultor($valorPlanoConsultor)
    {
        $this->valorPlanoConsultor = $valorPlanoConsultor;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValorPlanoFranqueado($formatar = false)
    {
        return $this->formatValue($this->valorPlanoFranqueado, 'real', $formatar);
    }

    /**
     * @param mixed $valorPlanoFranqueado
     * @return $this
     */
    public function setValorPlanoFranqueado($valorPlanoFranqueado)
    {
        $this->valorPlanoFranqueado = $valorPlanoFranqueado;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValorAdesaoConsultor($formatar = false)
    {
        return $this->formatValue($this->valorAdesaoConsultor, 'real', $formatar);
    }

    /**
     * @param mixed $valorAdesaoConsultor
     * @return $this
     */
    public function setValorAdesaoConsultor($valorAdesaoConsultor)
    {
        $this->valorAdesaoConsultor = $valorAdesaoConsultor;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValorAdesaoFranqueado($formatar = false)
    {
        return $this->formatValue($this->valorAdesaoFranqueado, 'real', $formatar);
    }

    /**
     * @param mixed $valorAdesaoFranqueado
     * @return $this
     */
    public function setValorAdesaoFranqueado($valorAdesaoFranqueado)
    {
        $this->valorAdesaoFranqueado = $valorAdesaoFranqueado;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValorRecargaConsultor($formatar = false)
    {
        return $this->formatValue($this->valorRecargaConsultor, 'real', $formatar);
    }

    /**
     * @param mixed $valorRecargaConsultor
     * @return $this
     */
    public function setValorRecargaConsultor($valorRecargaConsultor)
    {
        $this->valorRecargaConsultor = $valorRecargaConsultor;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValorRecargaFranqueado($formatar = false)
    {
        return $this->formatValue($this->valorRecargaFranqueado, 'real', $formatar);
    }

    /**
     * @param mixed $valorRecargaFranqueado
     * @return $this
     */
    public function setValorRecargaFranqueado($valorRecargaFranqueado)
    {
        $this->valorRecargaFranqueado = $valorRecargaFranqueado;
        return $this;
    }

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
    public function getRetornoMaximo($formatar = false)
    {
        return $this->formatValue($this->retornoMaximo, 'real', $formatar);
    }

    /**
     * @param mixed $retornoMaximo
     * @return $this
     */
    public function setRetornoMaximo($retornoMaximo)
    {
        $this->retornoMaximo = $retornoMaximo;
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
    public function getComissaoJogador1($formatar = false)
    {
        return $this->formatValue($this->comissaoJogador1, 'real', $formatar);
    }

    /**
     * @param mixed $comissaoJogador1
     * @return $this
     */
    public function setComissaoJogador1($comissaoJogador1)
    {
        $this->comissaoJogador1 = $comissaoJogador1;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComissaoJogador2($formatar = false)
    {
        return $this->formatValue($this->comissaoJogador2, 'real', $formatar);
    }

    /**
     * @param mixed $comissaoJogador2
     * @return $this
     */
    public function setComissaoJogador2($comissaoJogador2)
    {
        $this->comissaoJogador2 = $comissaoJogador2;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComissaoJogador3($formatar = false)
    {
        return $this->formatValue($this->comissaoJogador3, 'real', $formatar);
    }

    /**
     * @param mixed $comissaoJogador3
     * @return $this
     */
    public function setComissaoJogador3($comissaoJogador3)
    {
        $this->comissaoJogador3 = $comissaoJogador3;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComissaoJogador4($formatar = false)
    {
        return $this->formatValue($this->comissaoJogador4, 'real', $formatar);
    }

    /**
     * @param mixed $comissaoJogador4
     * @return $this
     */
    public function setComissaoJogador4($comissaoJogador4)
    {
        $this->comissaoJogador4 = $comissaoJogador4;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComissaoJogador5($formatar = false)
    {
        return $this->formatValue($this->comissaoJogador5, 'real', $formatar);
    }

    /**
     * @param mixed $comissaoJogador5
     * @return $this
     */
    public function setComissaoJogador5($comissaoJogador5)
    {
        $this->comissaoJogador5 = $comissaoJogador5;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComissaoConsultor1($formatar = false)
    {
        return $this->formatValue($this->comissaoConsultor1, 'real', $formatar);
    }

    /**
     * @param mixed $comissaoConsultor1
     * @return $this
     */
    public function setComissaoConsultor1($comissaoConsultor1)
    {
        $this->comissaoConsultor1 = $comissaoConsultor1;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComissaoConsultor2($formatar = false)
    {
        return $this->formatValue($this->comissaoConsultor2, 'real', $formatar);
    }

    /**
     * @param mixed $comissaoConsultor2
     * @return $this
     */
    public function setComissaoConsultor2($comissaoConsultor2)
    {
        $this->comissaoConsultor2 = $comissaoConsultor2;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComissaoConsultor3($formatar = false)
    {
        return $this->formatValue($this->comissaoConsultor3, 'real', $formatar);
    }

    /**
     * @param mixed $comissaoConsultor3
     * @return $this
     */
    public function setComissaoConsultor3($comissaoConsultor3)
    {
        $this->comissaoConsultor3 = $comissaoConsultor3;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComissaoConsultor4($formatar = false)
    {
        return $this->formatValue($this->comissaoConsultor4, 'real', $formatar);
    }

    /**
     * @param mixed $comissaoConsultor4
     * @return $this
     */
    public function setComissaoConsultor4($comissaoConsultor4)
    {
        $this->comissaoConsultor4 = $comissaoConsultor4;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComissaoConsultor5($formatar = false)
    {
        return $this->formatValue($this->comissaoConsultor5, 'real', $formatar);
    }

    /**
     * @param mixed $comissaoConsultor5
     * @return $this
     */
    public function setComissaoConsultor5($comissaoConsultor5)
    {
        $this->comissaoConsultor5 = $comissaoConsultor5;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComissaoFranqueado1($formatar = false)
    {
        return $this->formatValue($this->comissaoFranqueado1, 'real', $formatar);
    }

    /**
     * @param mixed $comissaoFranqueado1
     * @return $this
     */
    public function setComissaoFranqueado1($comissaoFranqueado1)
    {
        $this->comissaoFranqueado1 = $comissaoFranqueado1;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComissaoFranqueado2($formatar = false)
    {
        return $this->formatValue($this->comissaoFranqueado2, 'real', $formatar);
    }

    /**
     * @param mixed $comissaoFranqueado2
     * @return $this
     */
    public function setComissaoFranqueado2($comissaoFranqueado2)
    {
        $this->comissaoFranqueado2 = $comissaoFranqueado2;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComissaoFranqueado3($formatar = false)
    {
        return $this->formatValue($this->comissaoFranqueado3, 'real', $formatar);
    }

    /**
     * @param mixed $comissaoFranqueado3
     * @return $this
     */
    public function setComissaoFranqueado3($comissaoFranqueado3)
    {
        $this->comissaoFranqueado3 = $comissaoFranqueado3;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComissaoFranqueado4($formatar = false)
    {
        return $this->formatValue($this->comissaoFranqueado4, 'real', $formatar);
    }

    /**
     * @param mixed $comissaoFranqueado4
     * @return $this
     */
    public function setComissaoFranqueado4($comissaoFranqueado4)
    {
        $this->comissaoFranqueado4 = $comissaoFranqueado4;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComissaoFranqueado5($formatar = false)
    {
        return $this->formatValue($this->comissaoFranqueado5, 'real', $formatar);
    }

    /**
     * @param mixed $comissaoFranqueado5
     * @return $this
     */
    public function setComissaoFranqueado5($comissaoFranqueado5)
    {
        $this->comissaoFranqueado5 = $comissaoFranqueado5;
        return $this;
    }

    /**
     * @return string
     */
    public function getBanca()
    {
        return trim((string)$this->banca);
    }

    /**
     * @param $banca
     * @return $this
     */
    public function setBanca($banca)
    {
        $this->banca = $banca;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCorPrimaria()
    {
        return $this->corPrimaria;
    }

    /**
     * @param $corPrimaria
     * @return $this
     */
    public function setCorPrimaria($corPrimaria)
    {
        $this->corPrimaria = $corPrimaria;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCorSecundaria()
    {
        return $this->corSecundaria;
    }

    /**
     * @param $corSecundaria
     * @return $this
     */
    public function setCorSecundaria($corSecundaria)
    {
        $this->corSecundaria = $corSecundaria;
        return $this;
    }

    /**
     * @return int
     */
    public function getLimiteUsuarios()
    {
        return (int)$this->limiteUsuarios;
    }

    /**
     * @param $limiteUsuarios
     * @return $this
     */
    public function setLimiteUsuarios($limiteUsuarios)
    {
        $this->limiteUsuarios = $limiteUsuarios;
        return $this;
    }

    /**
     * @param bool $formatar
     * @return mixed
     */
    public function getPeriodoInicial($formatar = false)
    {
        if ($this->periodoInicial === null || $this->periodoInicial === '' || $this->periodoInicial === '0000-00-00') {
            return null;
        }
        return $this->formatValue($this->periodoInicial, 'data', $formatar);
    }

    /**
     * @param $periodoInicial
     * @return $this
     */
    public function setPeriodoInicial($periodoInicial)
    {
        $this->periodoInicial = ($periodoInicial === '' || $periodoInicial === '0000-00-00') ? null : $periodoInicial;
        return $this;
    }

    /**
     * @param bool $formatar
     * @return mixed
     */
    public function getPeriodoFinal($formatar = false)
    {
        if ($this->periodoFinal === null || $this->periodoFinal === '' || $this->periodoFinal === '0000-00-00') {
            return null;
        }
        return $this->formatValue($this->periodoFinal, 'data', $formatar);
    }

    /**
     * @param $periodoFinal
     * @return $this
     */
    public function setPeriodoFinal($periodoFinal)
    {
        $this->periodoFinal = ($periodoFinal === '' || $periodoFinal === '0000-00-00') ? null : $periodoFinal;
        return $this;
    }

    /**
     * @param bool $toReal
     * @return mixed
     */
    public function getPeriodoValor($toReal = false)
    {
        return $this->formatValue($this->periodoValor, 'real', $toReal);
    }

    /**
     * @param $periodoValor
     * @return $this
     */
    public function setPeriodoValor($periodoValor)
    {
        $this->periodoValor = $periodoValor;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * @param $app
     * @return $this
     */
    public function setApp($app)
    {
        $this->app = $app;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getInformacoes()
    {
        return $this->informacoes;
    }

    /**
     * @param $informacoes
     * @return $this
     */
    public function setInformacoes($informacoes)
    {
        $this->informacoes = $informacoes;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getRegulamento()
    {
        return $this->regulamento;
    }

    /**
     * @param $regulamento
     * @return $this
     */
    public function setRegulamento($regulamento)
    {
        $this->regulamento = $regulamento;
        return $this;
    }

    /**
     * @return int
     */
    public function getBloqueado()
    {
        return $this->bloqueado ? 1 : 0;
    }

    /**
     * @param $bloqueado
     * @return $this
     */
    public function setBloqueado($bloqueado)
    {
        $this->bloqueado = $bloqueado;
        return $this;
    }

    /**
     * @return int
     */
    public function getImprimirRegras()
    {
        return $this->imprimirRegras ? 1 : 0;
    }

    /**
     * @param $imprimirRegras
     * @return $this
     */
    public function setImprimirRegras($imprimirRegras)
    {
        $this->imprimirRegras = $imprimirRegras;
        return $this;
    }

    /**
     * @return int
     */
    public function getImprimirLogo()
    {
        return $this->imprimirLogo ? 1 : 0;
    }

    /**
     * @param $imprimirLogo
     * @return $this
     */
    public function setImprimirLogo($imprimirLogo)
    {
        $this->imprimirLogo = $imprimirLogo;
        return $this;
    }

}
