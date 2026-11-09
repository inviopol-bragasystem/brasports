'use strict';

const path = require('path');
const express = require('express');
const config = require('../config');
const { clear } = require('./lib/cache');
const { ApiError } = require('./lib/http');
const agg = require('./services/aggregator');

const app = express();

function ok(res, data, status = 200, meta = null) {
  const body = { success: true, status, data };
  if (meta) body.meta = meta;
  res.status(status).json(body);
}

function fail(res, err) {
  let status = Number(err?.status || 500);
  if (status < 400 || status > 599) status = 500;
  res.status(status).json({
    success: false,
    status,
    error: err?.message || String(err),
    ...(config.app.debug ? { stack: String(err?.stack || '').split('\n') } : {}),
  });
}

function sourceOf(req) {
  const s = req.query.source;
  if (s == null || s === '' || s === 'all') return null;
  return String(s).toLowerCase();
}

app.use((req, res, next) => {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
  res.setHeader('X-Content-Type-Options', 'nosniff');
  res.setHeader('Cache-Control', 'no-store');
  if (req.method === 'OPTIONS') return res.status(204).end();
  next();
});

app.get(['/', '/dashboard', '/dashboard.html'], (req, res) => {
  res.sendFile(path.join(__dirname, '..', 'public', 'dashboard.html'));
});

app.get('/api', (req, res) => {
  ok(res, {
    name: config.app.name,
    version: config.app.version,
    runtime: 'nodejs',
    sport: 'football',
    timezone: config.app.timezone,
    default_source: config.app.defaultSource,
    sources: agg.sourcesList(),
    endpoints: {
      'GET /api': 'Documentação',
      'GET /api/health?deep=1': 'Health de cada fonte',
      'GET /api/sources': 'Fontes enabled',
      'GET /api/football/live?source=': 'Jogos ao vivo + odds',
      'GET /api/football/today?source=': 'Hoje e próximos + odds',
      'GET /api/football/upcoming?source=': 'Alias de today',
      'GET /api/football/leagues?source=': 'Ligas',
      'GET /api/football/leagues/:id/events': 'Eventos da liga (SofaScore id)',
      'GET /api/football/events/:id?source=&odds=1': 'Detalhe do evento',
      'GET /api/football/events/:id/odds?source=': 'Só odds do evento',
      'GET /api/football/search?q=&source=': 'Busca',
      'GET /api/football/compare?mode=live|today': 'Mescla todas as fontes',
      'GET /api/odds/live?source=': 'Live focado em odds 1X2',
      'GET /api/cache/clear?confirm=1': 'Limpa cache',
    },
    examples: [
      '/api/health?deep=1',
      '/api/football/live?source=sofascore',
      '/api/football/live?source=vaidebet',
      '/api/football/live?source=pixbet',
      '/api/football/today?source=vaidebet',
      '/api/football/compare?mode=live',
      '/api/odds/live?source=pixbet',
      '/api/football/search?q=flamengo&source=pixbet',
    ],
  });
});

app.get('/api/health', async (req, res) => {
  try {
    const deep = ['1', 'true', 'yes'].includes(String(req.query.deep || '').toLowerCase());
    const health = await agg.health(deep);
    let status = 'ok';
    if (deep && health.ok_count === 0 && health.enabled_count > 0) status = 'degraded';
    ok(res, {
      status,
      time: new Date().toISOString(),
      version: config.app.version,
      node: process.version,
      health,
    });
  } catch (e) {
    fail(res, e);
  }
});

app.get('/api/sources', (req, res) => {
  try {
    ok(res, { sources: agg.sourcesList(), enabled: agg.enabledKeys() });
  } catch (e) {
    fail(res, e);
  }
});

async function listHandler(req, res, method) {
  try {
    const src = sourceOf(req);
    const call = await agg.call(method, src);
    if (src) return ok(res, call.results[src] ?? null, 200, { errors: call.errors });
    ok(res, agg.mergeEvents(call));
  } catch (e) {
    fail(res, e);
  }
}

app.get('/api/football/live', (req, res) => listHandler(req, res, 'live'));
app.get('/api/football/today', (req, res) => listHandler(req, res, 'today'));
app.get('/api/football/upcoming', (req, res) => listHandler(req, res, 'today'));

app.get('/api/football/leagues', async (req, res) => {
  try {
    const src = sourceOf(req) || config.app.defaultSource || 'sofascore';
    const call = await agg.call('leagues', src);
    const key = Object.keys(call.results)[0];
    if (!key) throw new ApiError('Falha ao listar ligas: ' + JSON.stringify(call.errors), 502);
    ok(res, call.results[key], 200, { errors: call.errors });
  } catch (e) {
    fail(res, e);
  }
});

app.get('/api/football/leagues/:id/events', async (req, res) => {
  try {
    const call = await agg.call('leagueEvents', 'sofascore', [req.params.id]);
    if (!call.results.sofascore) {
      throw new ApiError(call.errors.sofascore || 'Falha na liga', 502);
    }
    ok(res, call.results.sofascore);
  } catch (e) {
    fail(res, e);
  }
});

app.get('/api/football/events/:id', async (req, res) => {
  try {
    const src = sourceOf(req) || config.app.defaultSource || 'sofascore';
    const call = await agg.call('event', src, [req.params.id, { odds: req.query.odds ?? true }]);
    if (!call.results[src]) throw new ApiError(call.errors[src] || 'Evento não encontrado', 404);
    ok(res, call.results[src]);
  } catch (e) {
    fail(res, e);
  }
});

app.get('/api/football/events/:id/odds', async (req, res) => {
  try {
    const src = sourceOf(req) || config.app.defaultSource || 'sofascore';
    const call = await agg.call('event', src, [req.params.id, { odds: true }]);
    const payload = call.results[src];
    if (!payload) throw new ApiError(call.errors[src] || 'Evento não encontrado', 404);
    ok(res, {
      source: src,
      event_id: payload.event?.id,
      name: payload.event?.name,
      odds: payload.event?.odds || null,
      odds_raw_markets: payload.event?.odds_raw_markets ?? null,
      fetched_at: payload.fetched_at,
    });
  } catch (e) {
    fail(res, e);
  }
});

app.get('/api/football/search', async (req, res) => {
  try {
    const q = String(req.query.q || '');
    if (!q.trim()) throw new ApiError('Parâmetro q é obrigatório', 422);
    const limit = Math.max(1, Math.min(50, Number(req.query.limit || 30)));
    const src = sourceOf(req);
    const call = await agg.call('search', src, [q, limit]);
    if (src) return ok(res, call.results[src] ?? null, 200, { errors: call.errors });
    ok(res, agg.mergeEvents(call));
  } catch (e) {
    fail(res, e);
  }
});

app.get('/api/football/compare', async (req, res) => {
  try {
    let mode = String(req.query.mode || 'live').toLowerCase();
    if (!['live', 'today'].includes(mode)) mode = 'live';
    const call = await agg.call(mode, null);
    ok(res, agg.mergeEvents(call), 200, { mode });
  } catch (e) {
    fail(res, e);
  }
});

app.get('/api/odds/live', async (req, res) => {
  try {
    const src = sourceOf(req);
    const call = await agg.call('live', src);
    const merged = src ? call.results[src] : agg.mergeEvents(call);
    const events = (merged?.events || [])
      .filter((e) => e.is_live)
      .map((e) => ({
        source: e.source,
        id: e.id,
        name: e.name,
        home: e.home,
        away: e.away,
        league: e.league,
        score: e.score,
        status: e.status,
        match_result: e.odds?.match_result || null,
        markets_count: e.odds?.markets?.length || e.markets_count || null,
        url: e.url,
      }));
    ok(res, {
      sport: 'football',
      mode: 'live_odds',
      count: events.length,
      events,
      errors: call.errors,
      sources_ok: Object.keys(call.results || {}),
      fetched_at: new Date().toISOString(),
    });
  } catch (e) {
    fail(res, e);
  }
});

app.get('/api/cache/clear', (req, res) => {
  if (req.query.confirm !== '1') return fail(res, new ApiError('Confirme com ?confirm=1', 400));
  ok(res, { cleared: clear() });
});

app.use(express.static(path.join(__dirname, '..', 'public')));

app.use((req, res) => {
  fail(res, new ApiError(`Rota não encontrada: ${req.path}`, 404));
});

const port = config.app.port;
const host = config.app.host;

const server = app.listen(port, host, () => {
  console.log('');
  console.log(`  ${config.app.name} v${config.app.version}`);
  console.log(`  Node ${process.version}`);
  console.log(`  Painel  http://127.0.0.1:${port}/`);
  console.log(`  API     http://127.0.0.1:${port}/api`);
  console.log(`  Health  http://127.0.0.1:${port}/api/health?deep=1`);
  console.log('');
  console.log('  Servidor rodando. Nao feche esta janela.');
  console.log('');
});

server.on('error', (err) => {
  if (err && err.code === 'EADDRINUSE') {
    console.error('');
    console.error(`  [ERRO] Porta ${port} ja esta em uso.`);
    console.error('  Feche o outro processo ou rode start.bat de novo');
    console.error('  (ele tenta liberar a porta 8090 automaticamente).');
    console.error('');
  } else {
    console.error('');
    console.error('  [ERRO] Falha ao iniciar servidor:', err.message || err);
    console.error('');
  }
  process.exit(1);
});

process.on('uncaughtException', (err) => {
  console.error('[uncaughtException]', err);
});
process.on('unhandledRejection', (err) => {
  console.error('[unhandledRejection]', err);
});
