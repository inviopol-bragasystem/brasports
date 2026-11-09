# API Betano Sports (PHP)

API REST em PHP que consome os endpoints públicos internos da [Betano BR](https://www.betano.bet.br/) e devolve **futebol e outros esportes** em JSON limpo e o mais completo possível:

- **Horários** em UTC, Brasil (`America/Sao_Paulo`), unix, ms, data, hora e countdown
- **Odds** principais em listagens (1X2, mais/menos, BTTS, chance dupla, DNB…)
- **Mercados completos** no detalhe do evento (`markets=all` → centenas de mercados)
- **Agenda** por data/hora (`schedule.by_date`, `by_hour`, `timeline`)
- **Ao vivo** com placar, período e odds

> **Aviso:** os dados pertencem à Betano/Kaizen Gaming. Use apenas para fins pessoais, estudo ou protótipos. Respeite os Termos de Uso e a legislação local (18+). Scraping/proxy de odds em escala comercial pode violar ToS e regras regulatórias.

---

## Requisitos

- PHP **8.1+** com extensão **cURL** e **JSON**
- Apache com `mod_rewrite` **ou** servidor embutido do PHP

---

## Subir a API

### Opção A — PHP built-in (recomendado para dev)

**Windows (XAMPP):** use o script (ele desliga `open_basedir`, que bloqueia pastas fora de `htdocs`):

```bat
start.bat
```

Ou manualmente:

```bash
cd apibetano
php -d open_basedir= -S 127.0.0.1:8080 router.php
```

Linux/macOS:

```bash
cd apibetano
php -S localhost:8080 router.php
```

Abra:

- Painel HTML: [http://localhost:8080/](http://localhost:8080/) ou [http://localhost:8080/dashboard.html](http://localhost:8080/dashboard.html)
- API docs: [http://localhost:8080/api](http://localhost:8080/api)

> **Nota XAMPP:** o cURL da extensão PHP costuma tomar **403** do Cloudflare. A API usa automaticamente o `curl.exe` do Windows (`prefer_binary_curl`). Se `exec` estiver desabilitado no `php.ini`, deixe ao menos `system` liberado (padrão XAMPP).

### Opção B — Apache

Aponte o `DocumentRoot` para a pasta `public/` (ou a raiz do projeto; o `.htaccess` redireciona).

### Opção C — testar endpoints

```bash
php examples/smoke_test.php http://127.0.0.1:8080
```

---

## Endpoints

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/api` | Documentação rápida |
| GET | `/api/health` | Healthcheck |
| GET | `/api/sports` | Lista de esportes |
| GET | `/api/sports/{slug}` | Esporte + top ligas + regiões |
| GET | `/api/sports/{slug}/leagues` | Todas as ligas |
| GET | `/api/sports/{slug}/today` | Jogos de hoje + odds + agenda (`?expand=full`) |
| GET | `/api/sports/{slug}/schedule` | Agenda compacta (hora + odds principais) |
| GET | `/api/leagues/{leagueId}/events` | Eventos da liga (`?mode=&expand=full`) |
| GET | `/api/leagues/path/{path}` | Liga via path da Betano |
| GET | `/api/events/top` | Destaques + odds + agenda |
| GET | `/api/events/{id}` | **Evento completo** (`?slug=&markets=all`) |
| GET | `/api/events/{id}/full` | Mescla tabs de mercados |
| GET | `/api/markets/tabs` | Tabs disponíveis (popular, all, players…) |
| GET | `/api/live` | Ao vivo (todos) |
| GET | `/api/live/{sportId}` | Ao vivo por esporte (`FOOT`, `BASK`…) |
| GET | `/api/live/layout` | Layout de mercados live |
| GET | `/api/coupons` | Cupons / próximos |
| GET | `/api/search?q=` | Busca simples |
| GET | `/api/cache/clear?confirm=1` | Limpa cache |

### Completude de odds

| Contexto | O que vem |
|----------|-----------|
| Listagens (`/today`, `/top`, `/live`, liga) | Mercados principais + objeto `odds` (1X2, gols, BTTS…) + `schedule` |
| Detalhe (`/events/{id}`) | Por padrão `markets=all` (~centenas de mercados quando a Betano oferece) |
| `?markets=popular` | ~20–30 mercados “Populares” |
| `?markets=players\|goals\|cards…` | Tab específico |
| `?expand=full` nas listagens | Hidrata N eventos com todos os mercados (mais lento) |

### Slugs de esporte

`futebol`, `basquete`, `tenis`, `voleibol`, `handebol`, `futsal`, `futebol-americano`, `baseball`, `hockey`, `mma`, `boxe`, `tenis-de-mesa`, `formula-1`, `ciclismo`, `golf`, `esports`, …

Aliases aceitos: `soccer`→`futebol`, `basketball`→`basquete`, `ufc`→`mma`, `f1`→`formula-1`, etc.

### IDs de esporte (live)

| ID | Esporte |
|----|---------|
| `FOOT` | Futebol |
| `BASK` | Basquete |
| `TENN` | Tênis |
| `VOLL` | Vôlei |
| `MMAF` | MMA |
| `ESPS` | Esports |
| `TABL` | Tênis de mesa |
| … | (ver `/api/live`) |

---

## Exemplos

```bash
# Saúde
curl http://localhost:8080/api/health

# Futebol – estrutura e ligas
curl http://localhost:8080/api/sports/futebol

# Jogos de hoje (futebol)
curl http://localhost:8080/api/sports/futebol/today

# Brasileirão Série B (id 10017)
curl http://localhost:8080/api/leagues/10017/events

# Path completo da liga
curl http://localhost:8080/api/leagues/path/futebol/brasil/brasileirao-serie-b/10017

# Destaques
curl http://localhost:8080/api/events/top

# Ao vivo
curl http://localhost:8080/api/live
curl http://localhost:8080/api/live/FOOT

# Evento com odds (slug opcional se o id estiver em top/live/hoje)
curl "http://localhost:8080/api/events/88703633?slug=argentina-suica"

# Busca
curl "http://localhost:8080/api/search?q=argentina"
```

### Resposta típica (listagem — com horário + odds)

```json
{
  "id": "88703633",
  "name": "Argentina - Suíça",
  "home": "Argentina",
  "away": "Suíça",
  "date_br": "2026-07-11",
  "time_br": "22:00",
  "start_time": "2026-07-12T01:00:00+00:00",
  "start_time_br": "2026-07-11T22:00:00-03:00",
  "schedule": {
    "starts_in_human": "em 1h 13min",
    "day_name_br": "Sábado"
  },
  "odds": {
    "all_main": {
      "match_result": { "1": 1.75, "X": 3.45, "2": 5.2 },
      "total_goals": { "Mais de 2.5": 2.22, "Menos de 2.5": 1.67 },
      "both_teams_score": { "Sim": 2.05, "Não": 1.72 }
    }
  },
  "markets_returned": 6,
  "total_markets_available": 1134
}
```

### Detalhe completo (`/api/events/{id}?markets=all`)

Retorna **centenas de mercados** (ex.: 667), agrupados em:

- `markets` — lista completa com `selections[].price`
- `markets_by_type` — por código Betano (MRES, HCTG, BTSC…)
- `markets_by_category` — match_result, total_goals, players, corners…
- `odds` — resumo pronto (1X2, BTTS, OU, handicap…)
- `schedule` — todos os formatos de horário

---

## Arquitetura

```
apibetano/
├── config.php              # URLs, cache, sports map, CORS
├── router.php              # PHP built-in server entry
├── public/
│   ├── index.php           # Front controller + rotas
│   └── .htaccess
├── src/
│   ├── Router.php
│   ├── Http/Client.php     # cURL → Betano
│   ├── Http/Response.php   # JSON + CORS
│   ├── Cache/FileCache.php
│   └── Services/
│       ├── BetanoService.php
│       └── Normalizer.php
├── cache/                  # cache em arquivo
└── examples/smoke_test.php
```

### Fontes internas Betano usadas

| Uso | URL Betano |
|-----|------------|
| Top events | `/api/home/top-events-v2/` |
| Esporte | `/api/sport/{slug}` |
| Jogos de hoje | `/api/sport/{slug}/jogos-de-hoje` |
| Liga (página) | `/api/sport/.../{leagueId}/` |
| Eventos hot da liga | `/api/league/hot/upcoming?leagueId=` |
| Odds do evento | `/api/odds/{slug}/{eventId}/` |
| Live | `/danae-webapi/api/live/overview/latest` |
| Cupons | `/api/home/upcoming-coupons` |

---

## Configuração

Edite `config.php`:

- **Cache TTL** — `cache.ttl.live` (padrão 8s), `event` (30s), etc.
- **CORS** — `cors.allow_origin`
- **Debug** — `app.debug = true` para stack traces
- **Timeout** — `betano.timeout`

---

## Integração com seus sistemas

### PHP

```php
$json = file_get_contents('http://localhost:8080/api/sports/futebol/today');
$data = json_decode($json, true);
foreach ($data['data']['events'] as $game) {
    echo $game['name'] . ' @ ' . ($game['main_odds']['odds']['1'] ?? '-');
}
```

### JavaScript / Node

```js
const res = await fetch('http://localhost:8080/api/live/FOOT');
const { data } = await res.json();
console.log(data.events_count, data.events[0]);
```

---

## Limitações conhecidas

1. A Betano pode alterar paths internos sem aviso.
2. Bloqueios Cloudflare / rate limit em IPs residenciais ou alto volume.
3. Detalhe de evento exige `slug` na URL da Betano; a API tenta resolver via top/live/hoje, senão use `?slug=`.
4. A página do evento retorna mercados “principais/populares” (dezenas), não necessariamente todos os 1000+ mercados.
5. Dados de live dependem de `queryLanguageId=5` e `queryOperatorId=8` (Brasil).

---

## Licença / responsabilidade

Código da API: uso livre no seu projeto.  
Conteúdo de odds/eventos: propriedade da Betano. Não redistribua comercialmente sem autorização. 18+ Jogue com responsabilidade.
