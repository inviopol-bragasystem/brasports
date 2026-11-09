<?php

namespace app\modules\admin\controllers\jogos;

use app\core\Controller;
use app\core\crud\Conn;
use app\helpers\Number;
use app\models\ApostaJogosModel;
use app\models\CotacoesModel;
use app\models\HistoricoModel;
use app\models\JogosModel;
use app\modules\admin\Admin;
use app\vo\JogoVO;
use Exception;
use PDO;

/**
 * Limpeza de jogos: lista todos os jogos (qualquer situação) e permite
 * excluir um, vários ou todos, além de editar as odds rapidamente.
 */
class limpezaController extends Controller
{

    protected $tpl = 'admin/jogos/limpeza';

    /**
     * Apenas administradores podem executar ações destrutivas
     * @throws Exception
     */
    private function guard()
    {
        if (!Admin::isMaster()) {
            throw new Exception('Somente para administradores.');
        }
    }

    function indexAction()
    {
        $this->view($this->tpl, [
            'resumo' => $this->resumo(),
            'cotacoes' => CotacoesModel::getCotacoesAtivas('a.ordem ASC, a.titulo ASC, a.grupo ASC'),
            'grupos' => CotacoesModel::GRUPOS,
            'tempos' => JogosModel::TEMPOS,
        ]);
    }

    /**
     * Resumo de jogos por situação
     * @return array
     */
    private function resumo(): array
    {
        $tb = JogosModel::getTable();
        $tbAJ = ApostaJogosModel::getTable();

        $aberto = "(a.data > :hoje OR (a.data = :hoje AND a.hora > :hora))";

        $sql = <<<SQL
SELECT
    COUNT(*) AS total,
    SUM(IF(a.status = 1 AND {$aberto}, 1, 0)) AS abertos,
    SUM(IF(a.status = 1 AND NOT {$aberto}, 1, 0)) AS iniciados,
    SUM(IF(a.status = 2, 1, 0)) AS encerrados,
    SUM(IF(a.status = 0, 1, 0)) AS cancelados,
    SUM(IF(EXISTS(SELECT 1 FROM `{$tbAJ}` AS aj WHERE aj.jogo = a.id), 1, 0)) AS comapostas
FROM `{$tb}` AS a
WHERE a.status != 99
SQL;

        $prepare = Conn::getConn()->prepare($sql);
        $prepare->execute(['hoje' => date('Y-m-d'), 'hora' => date('H:i:s')]);
        $r = $prepare->fetch(PDO::FETCH_ASSOC) ?: [];

        foreach (['total', 'abertos', 'iniciados', 'encerrados', 'cancelados', 'comapostas'] as $k) {
            $r[$k] = (int)($r[$k] ?? 0);
        }

        return $r;
    }

    function resumoAction()
    {
        return ['result' => 1] + $this->resumo();
    }

    function listAction()
    {

        $parans = inputPost();
        $parans += ['page' => 1, 'forpage' => 20, 'situacao' => ''];

        $termos = 'WHERE a.status != 99';
        $places = [];

        $aberto = "(a.data > :hoje OR (a.data = :hoje AND a.hora > :hora))";

        switch ($parans['situacao']) {
            case 'aberto':
                $termos .= " AND a.status = 1 AND {$aberto}";
                $places['hoje'] = date('Y-m-d');
                $places['hora'] = date('H:i:s');
                break;
            case 'iniciado':
                $termos .= " AND a.status = 1 AND NOT {$aberto}";
                $places['hoje'] = date('Y-m-d');
                $places['hora'] = date('H:i:s');
                break;
            case 'encerrado':
                $termos .= ' AND a.status = 2';
                break;
            case 'cancelado':
                $termos .= ' AND a.status = 0';
                break;
        }

        if (!empty($parans['id'])) {
            $termos .= ' AND a.id = :id';
            $places['id'] = (int)$parans['id'];
        }

        if (!empty($parans['campeonato'])) {
            $termos .= ' AND a.campeonato = :campeonato';
            $places['campeonato'] = (int)$parans['campeonato'];
        }

        if (!empty($parans['data'])) {
            $data = \app\helpers\Date::data($parans['data']);
            if ($data) {
                $termos .= ' AND a.data = :data';
                $places['data'] = $data;
            }
        }

        if (isset($parans['search']) and trim($parans['search']) !== '') {
            $termos .= " AND (casa.title LIKE CONCAT('%', :search, '%') OR fora.title LIKE CONCAT('%', :search, '%') OR campeonato.title LIKE CONCAT('%', :search, '%'))";
            $places['search'] = trim($parans['search']);
        }

        $busca = JogosModel::listaPagination("{$termos} ORDER BY a.data ASC, a.hora ASC", $places, (int)$parans['page'], (int)$parans['forpage']);

        // Total de apostas de cada jogo listado
        $contagem = [];
        $ids = [];
        foreach ($busca->getRegistros() as $v) {
            $ids[] = (int)$v->getId();
        }
        if ($ids) {
            $tbAJ = ApostaJogosModel::getTable();
            $rows = Conn::getConn()->query("SELECT jogo, COUNT(*) AS total FROM `{$tbAJ}` WHERE jogo IN(" . implode(',', $ids) . ") GROUP BY jogo")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $contagem[(int)$r['jogo']] = (int)$r['total'];
            }
        }

        $this->view($this->tpl, [
            'busca' => $busca,
            'contagem' => $contagem,
        ], 'list');

        $this->view($this->tpl, [
            'busca' => $busca,
        ], 'list');
    }

    /**
     * Total de apostas vinculadas ao jogo
     */
    private function totalApostas(int $jogoId): int
    {
        $tb = ApostaJogosModel::getTable();
        $prepare = Conn::getConn()->prepare("SELECT COUNT(*) FROM `{$tb}` WHERE jogo = :jogo");
        $prepare->execute(['jogo' => $jogoId]);
        return (int)$prepare->fetchColumn();
    }

    /**
     * Remove um jogo definitivamente.
     * - Sem apostas: exclui o registro definitivamente.
     * - Com apostas:
     *   * se $comApostas for falso: mantém o jogo.
     *   * se $comApostas for verdadeiro:
     *     - se o jogo ainda estiver aberto (status=1), cancela e estorna as apostas associadas.
     *     - exclui definitivamente de sis_jogos, sem exceção (mesmo com apostas já liquidadas).
     * @return string deletado|mantido
     * @throws Exception
     */
    private function remover(JogoVO $jogo, bool $comApostas): string
    {
        $temApostas = $this->totalApostas($jogo->getId()) > 0;

        if ($temApostas && !$comApostas) {
            return 'mantido';
        }

        // Se tem apostas e o jogo ainda estava aberto (status 1), cancela e estorna apostas antes
        if ($temApostas && (int)$jogo->getStatus() === 1) {
            try {
                JogosModel::cancelar($jogo);
            } catch (\Throwable $t) {
                // continua para excluir
            }
        }

        // Exclui definitivamente o registro de sis_jogos
        $tb = JogosModel::getTable();
        $prepare = Conn::getConn()->prepare("DELETE FROM `{$tb}` WHERE id = :id LIMIT 1");
        $prepare->execute(['id' => $jogo->getId()]);

        return 'deletado';
    }

    /**
     * Exclusão individual (compatível com o botão data-excluir do adminPage)
     */
    function excluirAction()
    {
        try {
            $this->guard();

            $jogo = JogosModel::getByLabel('token', inputPost('id'));
            if (!$jogo instanceof JogoVO) {
                throw new Exception('Jogo inválido');
            }

            $resultado = $this->remover($jogo, true);

            HistoricoModel::add("Limpeza: jogo #{$jogo->getId()} ({$resultado})", null, Admin::getLogged());

            return [
                'message' => 'Jogo removido com sucesso',
                'result' => 1,
            ];
        } catch (Exception $ex) {
            return $ex;
        }
    }

    /**
     * Limpeza em massa.
     * escopo: selecionados | iniciados | encerrados | cancelados | todos
     */
    function limparAction()
    {
        try {
            $this->guard();

            $escopo = (string)inputPost('escopo');
            $comApostas = (int)inputPost('comApostas') === 1;
            $tb = JogosModel::getTable();

            $places = [];

            switch ($escopo) {
                case 'selecionados':
                    $ids = array_values(array_filter(array_map('intval', (array)inputPost('ids'))));
                    if (!$ids) {
                        throw new Exception('Selecione ao menos um jogo.');
                    }
                    $termos = 'WHERE a.status != 99 AND a.id IN(' . implode(',', $ids) . ')';
                    break;
                case 'iniciados':
                    $termos = 'WHERE a.status = 1 AND (a.data < :hoje OR (a.data = :hoje AND a.hora <= :hora))';
                    $places = ['hoje' => date('Y-m-d'), 'hora' => date('H:i:s')];
                    break;
                case 'encerrados':
                    $termos = 'WHERE a.status = 2';
                    break;
                case 'cancelados':
                    $termos = 'WHERE a.status = 0';
                    break;
                case 'todos':
                    if (strtoupper(trim((string)inputPost('confirmacao'))) !== 'LIMPAR') {
                        throw new Exception('Digite LIMPAR para confirmar a limpeza de todos os jogos.');
                    }
                    $comApostas = true; // SEM EXCEÇÃO: limpa todos os jogos, inclusive com apostas liquidadas
                    $termos = 'WHERE a.status != 99';
                    break;
                default:
                    throw new Exception('Escopo inválido.');
            }

            $jogos = JogosModel::lista($termos, $places);

            $total = ['deletado' => 0, 'mantido' => 0, 'erro' => 0];

            /** @var JogoVO $jogo */
            foreach ($jogos as $jogo) {
                try {
                    $res = $this->remover($jogo, $comApostas);
                    if ($res === 'deletado') {
                        $total['deletado']++;
                    } else {
                        $total['mantido']++;
                    }
                } catch (Exception $e) {
                    $total['erro']++;
                }
            }

            HistoricoModel::add(
                "Limpeza de jogos ({$escopo}): {$total['deletado']} excluídos, {$total['mantido']} mantidos, {$total['erro']} erros",
                null,
                Admin::getLogged()
            );

            $msg = "Limpeza concluída!\n\n"
                . "✓ {$total['deletado']} jogos excluídos definitivamente\n"
                . ($total['mantido'] ? "• {$total['mantido']} jogos mantidos (possuem apostas)\n" : '')
                . ($total['erro'] ? "! {$total['erro']} jogos com erro\n" : '');

            return [
                'message' => $msg,
                'result' => 1,
                'totais' => $total,
            ] + $this->resumo();
        } catch (Exception $ex) {
            return $ex;
        }
    }

    /**
     * Edição rápida das odds de um jogo
     */
    function oddsAction()
    {
        try {
            $this->guard();

            $jogo = JogosModel::getByLabel('token', inputPost('token'));
            if (!$jogo instanceof JogoVO) {
                throw new Exception('Jogo inválido');
            } else if ((int)$jogo->getStatus() !== 1) {
                throw new Exception('Jogo não pode mais ser alterado.');
            }

            $enviadas = inputPost('cotacoes');
            if (!is_array($enviadas) or !$enviadas) {
                throw new Exception('Nenhuma cotação informada.');
            }

            $cotacoes = $jogo->getCotacoes(true) ?: [];

            foreach ($enviadas as $tempo => $valores) {
                if (!in_array((string)$tempo, ['90', 'pt', 'st'], true) or !is_array($valores)) {
                    continue;
                }
                foreach ($valores as $campo => $valor) {
                    $campo = preg_replace('/[^a-z0-9_]/i', '', (string)$campo);
                    $valor = Number::float($valor);
                    if ($campo === '') {
                        continue;
                    }
                    if ($valor > 0) {
                        $cotacoes[$tempo][$campo] = $valor;
                    } else {
                        unset($cotacoes[$tempo][$campo]);
                    }
                }
            }

            $jogo->setCotacoes($cotacoes)->setAlterouCotacoes(1)->save();

            HistoricoModel::add("Limpeza: alterou as odds do jogo #{$jogo->getId()}", $jogo, Admin::getLogged());

            return [
                'message' => 'Odds atualizadas com sucesso',
                'result' => 1,
            ];
        } catch (Exception $ex) {
            return $ex;
        }
    }

    function getModel()
    {
        return JogosModel::Instance();
    }

}
