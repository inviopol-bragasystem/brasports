<?php

namespace app\models;

use app\core\Model;
use app\vo\CotacaoVO;

class CotacoesModel extends Model
{

    const GRUPOS = [
        1 => 'Resultado da partida',
        2 => 'Duplas',
        3 => 'Gol e Meio',
        4 => 'Placar exato',
        5 => 'Marcam',
        6 => 'Gols da partida',
        7 => 'Paridade',
        8 => 'Condições',
        9 => 'Intervalo / Final',
    ];

    public function __construct()
    {
        $this->table = 'sis_cotacoes';
        $this->valueObject = CotacaoVO::class;
    }

    /**
     * Retorna a lista de cotações ativas
     * @return CotacaoVO[]
     */
    public static function getCotacoesAtivas(string $orderby = 'a.grupo ASC, a.ordem ASC, a.titulo ASC')
    {
        static $_cotacoes_ativas = [];
        if (empty($_cotacoes_ativas[$orderby])) {
            $_cotacoes_ativas[$orderby] = self::lista("WHERE a.status = 1 AND a.query != '' ORDER BY {$orderby}", null, false, true, true);
        }
        return $_cotacoes_ativas[$orderby];
    }

    /**
     * Busca uma cotação ativa pelo campo usado pelo bilhete.
     * O getByLabel genérico pode priorizar um registro inativo duplicado.
     */
    public static function getAtivaByCampo(string $campo)
    {
        $campo = trim($campo);
        if ($campo === '') {
            return null;
        }

        return self::buscarAtiva('LOWER(TRIM(a.campo)) = :valor', strtolower($campo));
    }

    /** Busca diretamente na tabela e hidrata a cotação sem usar o cache genérico de Model. */
    private static function buscarAtiva(string $condicao, string $valor)
    {
        $result = self::pdoRead()->FullRead(
            "SELECT a.* FROM `sis_cotacoes` AS a WHERE a.status = 1 AND {$condicao} ORDER BY a.id DESC LIMIT 1",
            ['valor' => $valor]
        )->getResult();

        return $result ? new CotacaoVO(self::instance(), $result[0]) : null;
    }

    /**
     * Resolve uma seleção antiga ou atual do bilhete por campo e, se necessário, por ID.
     * O ID só é aceito quando a cotação continua ativa no admin.
     */
    public static function getAtivaBySelecao($selecao, $idAlternativo = null)
    {
        if (is_array($selecao)) {
            $idAlternativo = $selecao['id'] ?? $idAlternativo;
            $selecao = $selecao['campo'] ?? $selecao['cotacao'] ?? $selecao['sigla'] ?? '';
        } elseif (is_object($selecao)) {
            $idAlternativo = $selecao->id ?? $idAlternativo;
            $selecao = $selecao->campo ?? $selecao->cotacao ?? $selecao->sigla ?? '';
        }

        $campo = is_scalar($selecao) ? trim((string)$selecao) : '';
        if ($campo !== '') {
            $cotacao = self::getAtivaByCampo($campo);
            if ($cotacao) {
                return $cotacao;
            }
            $cotacao = self::buscarAtiva('LOWER(TRIM(a.sigla)) = :valor', strtolower($campo));
            if ($cotacao) {
                return $cotacao;
            }
            if ($idAlternativo === null && ctype_digit($campo)) {
                $idAlternativo = $campo;
            }
        }

        if (is_scalar($idAlternativo) && ctype_digit((string)$idAlternativo) && (int)$idAlternativo > 0) {
            $result = self::pdoRead()->FullRead(
                'SELECT a.* FROM `sis_cotacoes` AS a WHERE a.id = :id AND a.status = 1 LIMIT 1',
                ['id' => (int)$idAlternativo]
            )->getResult();
            return $result ? new CotacaoVO(self::instance(), $result[0]) : null;
        }

        return null;
    }

    /**
     * Retorna a lista de todas as cotações do sistema
     * @return CotacaoVO[]
     */
    public static function getTodas()
    {
        return self::lista('WHERE a.status != 99 ORDER BY a.ordem ASC, a.titulo ASC', null, false, true, true);
    }

}
    
