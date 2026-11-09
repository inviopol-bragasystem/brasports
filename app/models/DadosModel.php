<?php

namespace app\models;

use app\core\Model;
use app\vo\DadosVO;
use app\vo\UserVO;

class DadosModel extends Model
{

    private static $config;

    public function __construct()
    {
        $this->table = 'sis_dados';
        $this->valueObject = DadosVO::class;
    }

    /**
     * Verifica se o sistema está ativo
     * @return boolean
     */
    public static function estaAtivo()
    {
        $config = self::get();

        $hoje = date('Y-m-d');

        if ($config->getPeriodoInicial() <= $hoje) {
            if (!$config->getPeriodoFinal() or $config->getPeriodoFinal() >= $hoje) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retorna os temas disponíveis para o site
     * Para criar um novo tema: crie o módulo (ex.: app\modules\website4\Site4),
     * registre em inc.config.php ($config['modules']) e adicione aqui a chave e o título.
     * @return array [chave => título]
     */
    public static function getTemas()
    {
        return [
            'site1' => 'Bonanza Sport (Padrão)',
            'site2' => 'Layout Alternativo 2',
            'site3' => 'Layout Alternativo 3',
            'site4' => 'Bet365 (Verde & Amarelo)',
            'site5' => 'Banca de Apostas (Vermelho & Preto)',
            'site6' => 'Betano (Laranja & Grafite)',
            'site7' => 'Primor Bet (Cyber Neon Verde)',
            'site8' => 'Superbet (Verde Esmeralda & Escuro)',
            'site9' => 'Moderno Escuro (Minimalista)',
            'site10' => 'DP Sports (Azul Royal & Marinho)',
            'site11' => 'Bet24',
            'site12' => 'GoldEsporte (Clássico Dourado & Preto)',
            'site13' => '99Play (Azul Noturno & Elétrico)',
            'site14' => 'TopSportsBet (Grafite & Preto)',
        ];
    }

    /**
     * Retorna a chave do tema salvo no banco (sem criar registro padrão)
     * @return string|null
     */
    public static function getTemaAtivo()
    {
        $config = current(self::lista('ORDER BY a.id DESC LIMIT 1', null, false, true, true));
        $tema = $config ? $config->getTema() : null;
        if ($tema === 'goldesporte') return 'site12';
        if ($tema === '99play') return 'site13';
        if ($tema === 'topsportsbet') return 'site14';
        if ($tema && array_key_exists($tema, self::getTemas())) {
            return $tema;
        }
        return 'site1';
    }

    /**
     * Retorna se a exibição de escudos dos times está ativa no sistema
     * @return bool
     */
    public static function exibirEscudos(): bool
    {
        return (bool)self::get()->getExibirEscudos();
    }

    /**
     * Retorna a configuração do sistema
     * @return DadosVO
     */
    public static function get()
    {
        if (!self::$config) {
            self::$config = current(self::lista('ORDER BY a.id DESC LIMIT 1', null, false, true, true));
            if (!self::$config) {
                self::$config = self::newValueObject([
                    'banca' => 'Grupo Adapta',
                    'periodoinicial' => date('Y-m-d'),
                    'periodofinal' => date('Y-m-d', strtotime('+5years')),
                    'periodovalor' => 30000,
                    'limiteusuarios' => 99999,
                ]);
                self::$config->Save();
            }
        }
        return self::$config;
    }

    /**
     * @param bool $formatar
     * @return string|float
     */
    public static function getValorPreCadastro($formatar = false)
    {
        return self::get()->getValorPreCadastro($formatar);
    }

    /**
     * @param UserVO $user
     * @param bool $formatar
     * @return string|float
     */
    public static function getValorRecarga(UserVO $user, bool $formatar = false)
    {
        return self::get()->valorRecarga($user, $formatar);
    }

    /**
     * @param UserVO $user
     * @param bool $formatar
     * @return float|string
     */
    public static function getValorPlanoAdesao(UserVO $user, bool $formatar = false)
    {
        return self::get()->valorPlanoAdesao($user, $formatar);
    }

    /**
     * @param UserVO $user
     * @param bool $formatar
     * @return string|float
     */
    public static function getValorPlano(UserVO $user, bool $formatar = false)
    {
        return self::get()->valorPlano($user, $formatar);
    }

    /**
     * @param UserVO $user
     * @param bool $formatar
     * @return string|float
     */
    public static function getValorAdesao(UserVO $user, bool $formatar = false)
    {
        if ($licenca = $user->voLicenca()) {
            return $licenca->getValorAdesao($formatar);
        } else {
            return 0;
        }
    }

    /**
     * @param UserVO $user
     * @param int $nivel
     * @param bool $formatar
     * @return mixed
     */
    public static function getBonusComercializacao(UserVO $user, int $nivel, bool $formatar = false)
    {
        if ($licenca = $user->voLicenca()) {
            return $licenca->get("ComissaoJogos{$nivel}", $formatar);
        } else {
            return 0;
        }
    }

    /**
     * @param UserVO $user
     * @param int $nivel
     * @param bool $formatar
     * @return mixed
     */
    public static function getBonusApuracao(UserVO $user, int $nivel, bool $formatar = false)
    {
        if ($licenca = $user->voLicenca()) {
            return $licenca->get("ComissaoApuracao{$nivel}", $formatar);
        }
        return 0;
    }

    /**
     * Recupera o percentual de indicação do usuário
     * @param UserVO $user
     * @param bool $formatar
     * @return string|float
     */
    public static function getComissaoIndicacao(UserVO $user, bool $formatar = false)
    {
        if ($licenca = $user->voLicenca()) {
            return $licenca->getComissaoIndicacao($formatar);
        } else {
            return 0;
        }
    }

}
    