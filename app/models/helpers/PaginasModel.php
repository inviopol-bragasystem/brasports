<?php

namespace app\models\helpers;

use app\core\Model;
use app\helpers\Pagination;
use app\vo\helpers\PaginaVO;

class PaginasModel extends Model
{

    public function __construct()
    {
        $this->table = 'sis_paginas';
        $this->valueObject = PaginaVO::class;
    }

    /**
     *
     * @param string $ref
     * @param int $refid
     * @param boolean $autoCrete
     * @return PaginaVO
     */
    static function getByRef($ref, $refid = 0, $autoCrete = true)
    {
        if ($ref) {
            $pagina = current(self::lista('WHERE a.ref = :ref AND a.refid = :refid AND a.status != 99 LIMIT 1', ['ref' => $ref, 'refid' => $refid]));
            if (!$pagina and $autoCrete) {
                return self::Instance()->newValueObject([
                    'ref' => $ref,
                    'refid' => $refid,
                ])->save();
            } else {
                return $pagina;
            }
        }
        return null;
    }

    /**
     * Organiza pela referência
     * @param string $ref
     */
    static function organizaRef(string $ref)
    {
        foreach (self::listRef($ref) as $i => $v) {
            $v->setOrdem($i);
            $v->Save();
        }
    }

    /**
     * @param string $ref
     * @return PaginaVO[]|int
     */
    static function listRef(string $ref)
    {
        return self::lista('WHERE a.ref = :ref AND a.status != 99 ORDER BY a.ordem ASC, a.title ASC', ['ref' => $ref]);
    }

    /**
     * Retorna a última posição
     * @param string $ref
     * @return int
     */
    static function lastOrdem(string $ref)
    {
        /** @var PaginaVO $v */
        foreach (self::listRef($ref) as $v) {
            return $v->getOrdem() + 1;
        }
        return 1;
    }

    /**
     * Busca complexa
     * @param array $Parans
     * @param int $Pagina
     * @param int $PorPagina
     * @return Pagination
     */
    function busca(array $Parans = null, $Pagina = 1, $PorPagina = 10)
    {
        $Termos = 'WHERE a.status!=99';
        $Places = [];
        if ($Parans) {
            foreach ($Parans as $key => $value) {
                if (!isEmpty($value) and !empty($key)) {
                    switch ($key) {
                        case 'status':
                        case 'ref':
                            $Termos .= " AND a.{$key} = :{$key}";
                            $Places[$key] = $value;
                            break;
                        case 'search':
                            $por = "LIKE CONCAT('%',:{$key},'%')";
                            $Termos .= " AND (a.title {$por} OR a.descricao {$por} OR a.keywords {$por})";
                            $Places[$key] = $value;
                            break;
                    }
                }
            }
        }
        return self::listaPagination("{$Termos} ORDER BY a.ordem ASC", $Places, $Pagina, $PorPagina);
    }

}
    