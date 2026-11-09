

| Fonte | Site | O que entrega |
|-------|------|----------------|
| **sofascore** | sofascore.com | Live, placar, ligas, odds |
| **vaidebet** | vaidebet.bet.br (Altenar) | Live, pré-jogo, odds completas |
| **pixbet** | pix.bet.br (FSSB) | Live, pré-jogo, odds completas |

## Subir

```bash
npm install
npm start
```

Windows: `start.bat`

- Painel: http://127.0.0.1:8090/
- Docs: http://127.0.0.1:8090/api
- Health: http://127.0.0.1:8090/api/health?deep=1

Porta: `PORT=8090` (env) ou `config.js`.

## Endpoints

| Rota | Descrição |
|------|-----------|
| `GET /api` | Documentação |
| `GET /api/health?deep=1` | Testa fontes |
| `GET /api/sources` | Lista fontes |
| `GET /api/football/live?source=` | Ao vivo + odds |
| `GET /api/football/today?source=` | Hoje / próximos + odds |
| `GET /api/football/upcoming?source=` | Alias de today |
| `GET /api/football/leagues?source=` | Ligas |
| `GET /api/football/leagues/:id/events` | Eventos (id SofaScore) |
| `GET /api/football/events/:id?source=` | Detalhe |
| `GET /api/football/events/:id/odds?source=` | Só odds |
| `GET /api/football/search?q=` | Busca |
| `GET /api/football/compare?mode=live` | Mescla fontes |
| `GET /api/odds/live?source=` | Live focado em 1X2 |
| `GET /api/cache/clear?confirm=1` | Limpa cache |

`source` = `sofascore` \| `vaidebet` \| `pixbet` \| omitir/`all`

## Exemplos

```bash
curl "http://127.0.0.1:8090/api/health?deep=1"
curl "http://127.0.0.1:8090/api/football/live?source=vaidebet"
curl "http://127.0.0.1:8090/api/football/live?source=pixbet"
curl "http://127.0.0.1:8090/api/football/today?source=vaidebet"
curl "http://127.0.0.1:8090/api/odds/live?source=pixbet"
curl "http://127.0.0.1:8090/api/football/events/ID?source=vaidebet&odds=1"
curl "http://127.0.0.1:8090/api/football/compare?mode=live"
```

## VPS

1. Node 18+
2. `cd apisports && npm install && npm start`
3. Ou PM2: `pm2 start src/index.js --name sportsbook-api`
4. Nginx → proxy para `127.0.0.1:8090`

Config: `config.js` → `sources.*.enabled`

## Estrutura

```
apisports/
├── package.json
├── config.js
├── start.bat
├── public/dashboard.html
├── src/
│   ├── index.js
│   ├── lib/      # http, cache, normalize
│   ├── sources/  # sofascore, vaidebet, pixbet
│   └── services/aggregator.js
└── cache/
```

IDs de evento são **por fonte** — use sempre `?source=` no detalhe.
