<?php

namespace app\helpers;

use app\core\Model;

class TeamBadgeHelper
{
    private static $teamImagesDb = null;
    private static $countryFlagsDb = null;

    /**
     * Mapeamento de apelidos e variações para o slug local do clube em imagens/times/{slug}.png
     * A ordem de prioridade protege nomes compostos (ex.: 'atletico-mg' antes de genéricos,
     * 'botafogo-sp' antes de 'botafogo', 'novorizontino' antes de 'gremio').
     */
    private static $clubMap = [
        // Nomes compostos prioritários
        'atletico-mg' => ['atletico-mg', 'atletico mg', 'atletico mineiro', 'atletico mineiro mg', 'clube atletico mineiro', 'atletico mineiro sub20', 'atletico mg sub 20', 'atletico mg u20'],
        'atletico-go' => ['atletico-go', 'atletico go', 'atletico goianiense', 'atletico clube goianiense'],
        'athletico-pr' => ['athletico-pr', 'athletico pr', 'athletico paranaense', 'atletico-pr', 'atletico pr', 'atletico paranaense', 'atletico pr u20'],
        'atletico-madrid' => ['atletico de madrid', 'atletico madrid', 'club atletico de madrid', 'atletico madrid b', 'atletico madrid c'],

        'botafogo-sp' => ['botafogo-sp', 'botafogo sp', 'botafogo ribeirao preto'],
        'botafogo-pb' => ['botafogo-pb', 'botafogo pb', 'botafogo paraiba'],
        'botafogo' => ['botafogo', 'botafogo rj', 'bfr'],

        'novorizontino' => ['novorizontino', 'gremio novorizontino', 'gremio novorizontino sp', 'gremio novorizontino sub20'],
        'gremio' => ['gremio', 'grêmio', 'gremio rs', 'gremio fbpa', 'gremio fb porto alegrense', 'gremio fb porto alegrense rs', 'gremio sub20', 'gremio u20', 'gremio b'],

        'america-mg' => ['america-mg', 'america mg', 'america mineiro', 'america mineiro mg'],
        'america-rn' => ['america-rn', 'america rn', 'america natal'],

        'bragantino' => ['bragantino', 'bragantino-sp', 'bragantino sp', 'red bull bragantino', 'rb bragantino'],

        // Principais Clubes Brasileiros
        'flamengo' => ['flamengo', 'cr flamengo', 'flamengo rj', 'cr flamengo rj', 'flamengo sub20', 'flamengo u20', 'flamengo pi', 'flamengo sp'],
        'palmeiras' => ['palmeiras', 'se palmeiras', 'palmeiras sp', 'palmeiras sub20', 'palmeiras u20'],
        'santos' => ['santos', 'santos fc', 'santos sp', 'santos sub20', 'santos u20'],
        'corinthians' => ['corinthians', 'sc corinthians', 'corinthians sp', 'corinthians paulista', 'corinthians sub20'],
        'sao-paulo' => ['sao paulo', 'são paulo', 'sao paulo fc', 'sao paulo sp', 'sao paulo sub20'],
        'cruzeiro' => ['cruzeiro', 'cruzeiro mg', 'cruzeiro ec', 'cruzeiro sub20'],
        'vasco' => ['vasco', 'vasco da gama', 'cr vasco da gama', 'cr vasco', 'vasco rj', 'vasco sub20'],
        'fluminense' => ['fluminense', 'fluminense rj', 'fluminense fc', 'fluminense sub20'],
        'internacional' => ['internacional', 'inter rs', 'sc internacional', 'internacional rs', 'inter sub20'],
        'bahia' => ['bahia', 'ec bahia', 'bahia ba', 'bahia sub20'],
        'fortaleza' => ['fortaleza', 'fortaleza ce', 'fortaleza ec', 'fortaleza sub20'],
        'ceara' => ['ceara', 'ceará', 'ceara sc', 'ceara ce', 'ceara sub20'],
        'coritiba' => ['coritiba', 'coritiba pr', 'coritiba fbc', 'coritiba sub20'],
        'goias' => ['goias', 'goiás', 'goias go', 'goias ec', 'goias sub20'],
        'sport' => ['sport', 'sport recife', 'sport pe', 'sport club do recife', 'sport sub20'],
        'cuiaba' => ['cuiaba', 'cuiabá', 'cuiaba mt', 'cuiaba ec'],
        'juventude' => ['juventude', 'juventude rs', 'ec juventude'],
        'criciuma' => ['criciuma', 'criciúma', 'criciuma sc', 'criciuma ec'],
        'vitoria' => ['vitoria', 'vitória', 'vitoria ba', 'ec vitoria'],
        'avai' => ['avai', 'avaí', 'avai sc', 'avai fc'],
        'chapecoense' => ['chapecoense', 'chapecoense sc', 'acf chapecoense'],
        'remo' => ['remo', 'clube do remo', 'remo pa'],
        'paysandu' => ['paysandu', 'paysandu pa', 'paysandu sc'],
        'ponte-preta' => ['ponte preta', 'ponte preta sp', 'aa ponte preta'],
        'guarani' => ['guarani', 'guarani sp', 'guarani fc'],
        'vila-nova' => ['vila nova', 'vila nova go', 'vila nova fc'],
        'nautico' => ['nautico', 'náutico', 'nautico pe', 'cnc nautico'],
        'operario-pr' => ['operario-pr', 'operario pr', 'operario ferroviario', 'operario ferroviario pr'],
        'mirassol' => ['mirassol', 'mirassol sp', 'mirassol fc'],
        'crb' => ['crb', 'crb al', 'clube de regatas brasil'],
        'londrina' => ['londrina', 'londrina pr', 'londrina ec'],
        'sao-bernardo' => ['sao bernardo', 'são bernardo', 'sao bernardo sp', 'sao bernardo fc'],
        'athletic-club' => ['athletic club', 'athletic club mg', 'athletic club (mg)', 'athletic mg'],
        'amazonas' => ['amazonas', 'amazonas fc', 'amazonas am'],
        'ituano' => ['ituano', 'ituano sp', 'ituano fc'],
        'brusque' => ['brusque', 'brusque sc', 'brusque fc'],
        'tombense' => ['tombense', 'tombense mg', 'tombense fc'],
        'ypiranga' => ['ypiranga', 'ypiranga rs', 'ypiranga fc'],
        'ferroviario' => ['ferroviario', 'ferroviário', 'ferroviario ce', 'fac ferroviario'],
        'floresta' => ['floresta', 'floresta ce', 'floresta ec'],
        'manaus' => ['manaus', 'manaus fc', 'manaus am'],
        'treze' => ['treze', 'treze pb', 'treze fc'],
        'campinense' => ['campinense', 'campinense pb', 'campinense clube'],
        'sergipe' => ['sergipe', 'sergipe se', 'cs sergipe'],
        'portuguesa' => ['portuguesa', 'portuguesa sp', 'portuguesa de desportos'],
        'portuguesa-rj' => ['portuguesa rj', 'portuguesa-rj', 'aa portuguesa rj'],
        'bangu' => ['bangu', 'bangu rj', 'bangu ac'],
        'madureira' => ['madureira', 'madureira rj', 'madureira ec'],
        'boavista-rj' => ['boavista rj', 'boavista-rj', 'boavista sc'],
        'nova-iguacu' => ['nova iguacu', 'nova iguaçu', 'nova iguacu rj'],
        'agua-santa' => ['agua santa', 'água santa', 'agua santa sp', 'ec agua santa'],
        'santo-andre' => ['santo andre', 'santo andré', 'santo andre sp', 'ec santo andre'],
        'inter-limeira' => ['inter de limeira', 'internacional de limeira', 'inter limeira', 'aa internacional'],
        'xv-piracicaba' => ['xv de piracicaba', 'xv de novembro', 'xv de novembro piracicaba'],
        'ferroviaria' => ['ferroviaria', 'ferroviária', 'ferroviaria sp', 'afe ferroviaria'],
        'volta-redonda' => ['volta redonda', 'volta redonda rj', 'volta redonda fc'],
        'santa-cruz' => ['santa cruz', 'santa cruz pe', 'santa cruz fc'],
        'parana' => ['parana', 'paraná', 'parana clube', 'parana pr'],
        'figueirense' => ['figueirense', 'figueirense sc', 'figueirense fc'],
        'joinville' => ['joinville', 'joinville sc', 'jec joinville'],
        'brasil-pelotas' => ['brasil de pelotas', 'brasil pelotas', 'ge brasil'],
        'confianca' => ['confianca', 'confiança', 'confianca se', 'ad confianca'],
        'sampaio-correa' => ['sampaio correa', 'sampaio corrêa', 'sampaio correa ma', 'sampaio correa fc'],
        'csa' => ['csa', 'csa al', 'centro sportivo alagoano'],
        'abc' => ['abc', 'abc rn', 'abc fc'],
        'maringa' => ['maringa', 'maringá', 'maringa fc', 'maringa pr'],

        // Gigantes Internacionais
        'boca-juniors' => ['boca juniors', 'boca jrs', 'ca boca juniors'],
        'river-plate' => ['river plate', 'ca river plate'],
        'real-madrid' => ['real madrid', 'real madrid cf'],
        'barcelona' => ['barcelona', 'fc barcelona'],
        'arsenal' => ['arsenal', 'arsenal fc'],
        'chelsea' => ['chelsea', 'chelsea fc'],
        'liverpool' => ['liverpool', 'liverpool fc'],
        'manchester-city' => ['manchester city', 'man city', 'manchester city fc'],
        'manchester-united' => ['manchester united', 'man united', 'man utd', 'manchester united fc'],
        'tottenham' => ['tottenham', 'tottenham hotspur', 'tottenham hotspur fc'],
        'juventus' => ['juventus', 'juventus fc'],
        'milan' => ['milan', 'ac milan'],
        'inter-milao' => ['inter de milao', 'inter milao', 'internazionale', 'fc internazionale'],
        'roma' => ['roma', 'as roma'],
        'napoli' => ['napoli', 'ssc napoli'],
        'lazio' => ['lazio', 'ss lazio'],
        'psg' => ['psg', 'paris saint-germain', 'paris saint germain', 'paris sg'],
        'bayern' => ['bayern', 'bayern munchen', 'bayern de munique', 'fc bayern'],
        'dortmund' => ['dortmund', 'borussia dortmund', 'bvb dortmund'],
        'leverkusen' => ['leverkusen', 'bayer leverkusen', 'bayer 04 leverkusen'],
        'benfica' => ['benfica', 'sl benfica'],
        'porto' => ['porto', 'fc porto'],
        'sporting' => ['sporting', 'sporting cp', 'sporting lisboa']
    ];

    /**
     * Carrega as imagens de times gravadas na tabela sis_imagens
     */
    private static function initTeamImages(): void
    {
        if (self::$teamImagesDb !== null) {
            return;
        }

        self::$teamImagesDb = [];
        try {
            $read = Model::pdoRead()->FullRead("
                SELECT refid, source 
                FROM sis_imagens 
                WHERE ref = 'sis_times' AND status = 1
                ORDER BY position ASC, id ASC
            ");
            $rows = $read ? $read->getResult() : [];
            foreach ($rows as $r) {
                $id = (int)$r['refid'];
                if (!isset(self::$teamImagesDb[$id]) && !empty($r['source'])) {
                    self::$teamImagesDb[$id] = source_images($r['source']);
                }
            }
        } catch (\Throwable $t) {
            // Silencioso
        }
    }

    /**
     * Carrega as bandeiras de países gravadas na tabela sis_options
     */
    private static function initCountryFlags(): void
    {
        if (self::$countryFlagsDb !== null) {
            return;
        }

        self::$countryFlagsDb = [
            'by_id' => [],
            'by_name' => [],
        ];

        try {
            $read = Model::pdoRead()->FullRead("
                SELECT a.id, a.title, b.source 
                FROM sis_options a 
                INNER JOIN sis_imagens b ON b.ref = 'sis_options' AND b.refid = a.id AND b.status = 1 
                WHERE a.ref = 'paises'
            ");
            $rows = $read ? $read->getResult() : [];
            foreach ($rows as $r) {
                if (!empty($r['source'])) {
                    $url = source_images($r['source']);
                    $id = (int)$r['id'];
                    $name = self::normalizeString($r['title']);

                    self::$countryFlagsDb['by_id'][$id] = $url;
                    self::$countryFlagsDb['by_name'][$name] = $url;
                }
            }
        } catch (\Throwable $t) {
            // Silencioso
        }
    }

    /**
     * Resolve a bandeira ou escudo oficial do time
     *
     * @param string $teamName Nome do time
     * @param int $teamId ID do time em sis_times
     * @param int $countryId ID do país do campeonato em sis_campeonatos
     * @return string|null URL da imagem da bandeira / escudo
     */
    public static function getTeamBadge(string $teamName, int $teamId = 0, int $countryId = 0): ?string
    {
        try {
            if (!\app\models\DadosModel::get()->getExibirEscudos()) {
                return null;
            }
        } catch (\Throwable $t) {
            // Continua caso não consiga ler configuração
        }

        self::initTeamImages();
        self::initCountryFlags();

        // 1. Imagem customizada salva no painel (tabela sis_imagens para este time)
        if ($teamId > 0 && isset(self::$teamImagesDb[$teamId])) {
            return self::$teamImagesDb[$teamId];
        }

        $norm = self::normalizeString($teamName);

        // 2. Se for uma seleção nacional cujo nome está cadastrado em sis_options (paises)
        if (isset(self::$countryFlagsDb['by_name'][$norm])) {
            return self::$countryFlagsDb['by_name'][$norm];
        }

        // 3. Resolução do escudo oficial local em imagens/times/{slug}.png
        $slug = self::matchClubSlug($norm);
        if ($slug !== null) {
            $localFile = 'imagens/times/' . $slug . '.png';
            if (file_exists(abs_source_images('times/' . $slug . '.png')) || file_exists($localFile)) {
                return source_images('times/' . $slug . '.png');
            }
        }

        // 4. Se o campeonato for estrangeiro (país diferente de Brasil 12 ou 0), usa a bandeira do país da liga
        if ($countryId > 0 && $countryId != 12 && isset(self::$countryFlagsDb['by_id'][$countryId])) {
            return self::$countryFlagsDb['by_id'][$countryId];
        }

        // 5. Fallback padrão: ícone de futebol limpo (nunca uma bandeira de país errada para um clube brasileiro)
        if (file_exists('imagens/sport_logos/futebol.svg') || file_exists(abs_source_images('sport_logos/futebol.svg'))) {
            return source_images('sport_logos/futebol.svg');
        }

        return source_images('default.jpg');
    }

    /**
     * Mapeia o nome normalizado para o slug oficial do clube
     */
    private static function matchClubSlug(string $norm): ?string
    {
        // 1. Correspondência exata em qualquer dos apelidos
        foreach (self::$clubMap as $slug => $aliases) {
            foreach ($aliases as $alias) {
                if ($norm === self::normalizeString($alias)) {
                    return $slug;
                }
            }
        }

        // 2. Correspondência com limites de palavras (início, fim ou cercado por espaços)
        foreach (self::$clubMap as $slug => $aliases) {
            // Regras de exclusão para evitar falsos positivos
            if ($slug === 'santos' && str_contains($norm, 'laguna')) continue;
            if ($slug === 'sport' && (str_contains($norm, 'boys') || str_contains($norm, 'sporting') || str_contains($norm, 'cristal'))) continue;
            if ($slug === 'parana' && str_contains($norm, 'patronato')) continue;
            if ($slug === 'gremio' && (str_contains($norm, 'novorizontino') || str_contains($norm, 'osasco') || str_contains($norm, 'prudente') || str_contains($norm, 'anapolis'))) continue;
            if (in_array($slug, ['atletico-mg', 'atletico-go', 'athletico-pr']) && (
                str_contains($norm, 'tucuman') || str_contains($norm, 'morelia') || str_contains($norm, 'nacional') ||
                str_contains($norm, 'rafaela') || str_contains($norm, 'baleares') || str_contains($norm, 'sanluqueno') ||
                str_contains($norm, 'bucaramanga') || str_contains($norm, 'huila') || str_contains($norm, 'san luis') ||
                str_contains($norm, 'ottawa') || str_contains($norm, 'marte') || str_contains($norm, 'central') ||
                str_contains($norm, 'progreso') || str_contains($norm, 'madrid') || str_contains($norm, 'bilbao') ||
                str_contains($norm, 'paso') || str_contains($norm, 'astorga') || str_contains($norm, 'camioneros') ||
                str_contains($norm, 'guemes') || str_contains($norm, 'atlanta')
            )) continue;

            foreach ($aliases as $alias) {
                $aNorm = self::normalizeString($alias);
                if (
                    str_starts_with($norm, $aNorm . ' ') ||
                    str_ends_with($norm, ' ' . $aNorm) ||
                    str_contains($norm, ' ' . $aNorm . ' ')
                ) {
                    return $slug;
                }
            }
        }

        return null;
    }

    /**
     * Retorna a bandeira do campeonato / país
     */
    public static function getCountryFlag(int $countryId = 0, string $countryName = ''): string
    {
        self::initCountryFlags();

        if ($countryId > 0 && isset(self::$countryFlagsDb['by_id'][$countryId])) {
            return self::$countryFlagsDb['by_id'][$countryId];
        }

        if (!empty($countryName)) {
            $norm = self::normalizeString($countryName);
            if (isset(self::$countryFlagsDb['by_name'][$norm])) {
                return self::$countryFlagsDb['by_name'][$norm];
            }
        }

        if (isset(self::$countryFlagsDb['by_id'][12])) {
            return self::$countryFlagsDb['by_id'][12];
        }

        return source_images('default.jpg');
    }

    public static function normalizeString(string $str): string
    {
        $str = mb_strtolower(trim($str), 'UTF-8');
        $map = [
            'á'=>'a', 'à'=>'a', 'ã'=>'a', 'â'=>'a', 'ä'=>'a',
            'é'=>'e', 'è'=>'e', 'ê'=>'e', 'ë'=>'e',
            'í'=>'i', 'ì'=>'i', 'î'=>'i', 'ï'=>'i',
            'ó'=>'o', 'ò'=>'o', 'õ'=>'o', 'ô'=>'o', 'ö'=>'o',
            'ú'=>'u', 'ù'=>'u', 'û'=>'u', 'ü'=>'u',
            'ç'=>'c', 'ñ'=>'n'
        ];
        return strtr($str, $map);
    }
}
