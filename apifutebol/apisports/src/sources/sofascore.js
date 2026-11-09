'use strict';

const config = require('../../config');
const { httpGet, ApiError } = require('../lib/http');
const { remember } = require('../lib/cache');
const { scheduleFromUnix, eventShell, listPayload, emptyOdds, roundPrice } = require('../lib/normalize');

const cfg = config.sources.sofascore;
const KEY = 'sofascore';
const LABEL = cfg.label;
const TTL = config.cache.ttl;

function headers() {
  return {
    Accept: 'application/json',
    Referer: 'https://www.sofascore.com/',
    Origin: 'https://www.sofascore.com',
    'sec-fetch-site': 'same-site',
    'sec-fetch-mode': 'cors',
    'sec-fetch-dest': 'empty',
  };
}

async function getJson(path) {
  // SofaScore: preferCurl (Cloudflare bloqueia fetch nativo do Node)
  const primary = cfg.baseUrl.replace(/\/$/, '') + path;
  let res = await httpGet(primary, headers(), { allowError: true, preferCurl: true });
  if (res.status >= 400 || !res.json) {
    const alt = (cfg.altBaseUrl || '').replace(/\/$/, '') + path;
    res = await httpGet(alt, headers(), { preferCurl: true });
  }
  if (!res.json) throw new ApiError('SofaScore JSON inválido', 502);
  return res.json;
}

function mapEvent(ev, forceLive = false) {
  const home = ev.homeTeam?.name || null;
  const away = ev.awayTeam?.name || null;
  const type = ev.status?.type || '';
  const isLive = forceLive || type === 'inprogress';
  const hs = ev.homeScore?.current ?? ev.homeScore?.display ?? null;
  const as = ev.awayScore?.current ?? ev.awayScore?.display ?? null;
  let period = null;
  if (ev.statusTime?.timestamp != null && ev.statusTime?.initial != null) {
    const elapsed =
      Math.max(0, Math.floor(Date.now() / 1000) - Number(ev.statusTime.timestamp)) +
      Number(ev.statusTime.initial || 0);
    period = {
      description: ev.status?.description || null,
      minute: Math.floor(elapsed / 60),
      elapsed_seconds: elapsed,
    };
  } else if (ev.status?.description) {
    period = { description: ev.status.description };
  }
  const unix = ev.startTimestamp != null ? Number(ev.startTimestamp) : null;
  return eventShell(KEY, LABEL, {
    id: String(ev.id || ''),
    name: `${home || '?'} - ${away || '?'}`,
    home,
    away,
    league: ev.tournament?.name || ev.tournament?.uniqueTournament?.name || null,
    league_id: ev.tournament?.uniqueTournament?.id
      ? String(ev.tournament.uniqueTournament.id)
      : ev.tournament?.id
        ? String(ev.tournament.id)
        : null,
    country: ev.tournament?.category?.name || null,
    is_live: isLive,
    status: ev.status?.description || type || null,
    score: hs != null || as != null ? { home: hs, away: as, period } : null,
    schedule: scheduleFromUnix(unix),
    url: ev.id ? `https://www.sofascore.com/event/${ev.id}` : null,
    slug: ev.slug || null,
  });
}

function fractionalToDecimal(frac) {
  if (!String(frac).includes('/')) {
    const n = Number(frac);
    return Number.isNaN(n) ? null : n;
  }
  const [a, b] = String(frac).split('/').map((x) => x.trim());
  if (!a || !b || Number(b) === 0) return null;
  return roundPrice(1 + Number(a) / Number(b));
}

function mapOdds(payload) {
  const out = emptyOdds();
  for (const market of payload.markets || []) {
    if (!market) continue;
    const name = market.marketName || `market_${market.marketId || ''}`;
    const choices = [];
    for (const ch of market.choices || []) {
      if (!ch) continue;
      let price = null;
      if (ch.fractionalValue != null) price = fractionalToDecimal(ch.fractionalValue);
      else if (ch.decimalValue != null) price = roundPrice(ch.decimalValue);
      choices.push({ name: ch.name || null, price, change: ch.change ?? null });
    }
    out.markets.push({
      id: market.marketId ?? null,
      name,
      is_live: Boolean(market.isLive),
      choices,
    });
    const ln = String(name).toLowerCase();
    if (
      !out.match_result &&
      (ln.includes('full time') || ln.includes('1x2') || ln.includes('match result') || name === 'Full time')
    ) {
      const mr = {};
      for (const c of choices) {
        const n = String(c.name || '').toLowerCase();
        if (n === '1' || n === 'home') mr['1'] = c.price;
        else if (n === 'x' || n === 'draw') mr.X = c.price;
        else if (n === '2' || n === 'away') mr['2'] = c.price;
      }
      if (Object.keys(mr).length) out.match_result = mr;
    }
  }
  return out;
}

async function latestSeasonId(tid) {
  return remember(`sofa:season:${tid}`, 3600, async () => {
    const data = await getJson(`/unique-tournament/${tid}/seasons`);
    const s = data.seasons?.[0];
    return s?.id ? Number(s.id) : null;
  });
}

async function health() {
  const t0 = Date.now();
  try {
    const data = await getJson('/sport/football/events/live');
    const n = (data.events || []).length;
    return { ok: true, latency_ms: Date.now() - t0, message: `ok (${n} live)` };
  } catch (e) {
    return { ok: false, latency_ms: Date.now() - t0, message: e.message };
  }
}

async function live() {
  return remember('sofa:live', TTL.live, async () => {
    const data = await getJson('/sport/football/events/live');
    const events = (data.events || []).filter(Boolean).map((e) => mapEvent(e, true));
    return listPayload(KEY, LABEL, events, { mode: 'live' });
  });
}

async function today() {
  return remember('sofa:today', TTL.today, async () => {
    const events = [];
    const seen = {};
    try {
      for (const e of (await live()).events) {
        if (e.id && !seen[e.id]) {
          seen[e.id] = true;
          events.push(e);
        }
      }
    } catch {
      // ignore
    }
    const now = Math.floor(Date.now() / 1000);
    for (const lg of cfg.featuredLeagues || []) {
      try {
        const seasonId = await latestSeasonId(lg.id);
        if (!seasonId) continue;
        const data = await getJson(`/unique-tournament/${lg.id}/season/${seasonId}/events/next/0`);
        for (const ev of data.events || []) {
          if (!ev) continue;
          const m = mapEvent(ev, false);
          if (!m.id || seen[m.id]) continue;
          if (m.schedule.start_time_unix && m.schedule.start_time_unix > now + 3 * 86400) continue;
          seen[m.id] = true;
          events.push(m);
        }
      } catch {
        // next league
      }
    }
    events.sort((a, b) => (a.schedule.start_time_unix || 0) - (b.schedule.start_time_unix || 0));
    return listPayload(KEY, LABEL, events, { mode: 'today_and_upcoming' });
  });
}

async function leagues() {
  return remember('sofa:leagues', TTL.leagues, async () => {
    const list = [];
    for (const lg of cfg.featuredLeagues || []) {
      let seasonId = null;
      try {
        seasonId = await latestSeasonId(lg.id);
      } catch {
        // ignore
      }
      list.push({
        id: String(lg.id),
        name: lg.name,
        country: lg.country,
        season_id: seasonId,
        source: KEY,
      });
    }
    return { source: KEY, count: list.length, leagues: list };
  });
}

async function leagueEvents(leagueId) {
  const tid = Number(leagueId);
  return remember(`sofa:lg:${tid}`, TTL.today, async () => {
    const seasonId = await latestSeasonId(tid);
    if (!seasonId) throw new ApiError(`Temporada não encontrada para liga ${tid}`, 404);
    const data = await getJson(`/unique-tournament/${tid}/season/${seasonId}/events/next/0`);
    const events = (data.events || []).filter(Boolean).map((e) => mapEvent(e, false));
    return listPayload(KEY, LABEL, events, { league_id: String(tid), season_id: seasonId });
  });
}

async function event(id, { odds = true } = {}) {
  id = String(id).replace(/\D/g, '') || id;
  const withOdds = odds !== false && odds !== '0';
  return remember(`sofa:ev:${id}:${withOdds ? 'o' : 'n'}`, TTL.event, async () => {
    const data = await getJson(`/event/${id}`);
    const ev = data.event;
    if (!ev) throw new ApiError(`Evento SofaScore ${id} não encontrado`, 404);
    const mapped = mapEvent(ev, ev.status?.type === 'inprogress');
    if (withOdds) {
      try {
        const o = await getJson(`/event/${id}/odds/1/all`);
        mapped.odds = mapOdds(o);
        mapped.odds_raw_markets = (o.markets || []).length;
      } catch (e) {
        mapped.odds = emptyOdds();
        mapped.odds_error = e.message;
      }
    }
    return { source: KEY, event: mapped, fetched_at: new Date().toISOString() };
  });
}

async function search(q, limit = 30) {
  q = String(q || '').trim();
  if (!q) return listPayload(KEY, LABEL, []);
  limit = Math.max(1, Math.min(50, Number(limit) || 30));
  return remember(`sofa:search:${q}:${limit}`, TTL.search, async () => {
    const data = await getJson('/search/events?q=' + encodeURIComponent(q));
    const events = [];
    for (const row of data.results || []) {
      const entity = row.entity;
      if (!entity) continue;
      let home = null;
      let away = null;
      const name = entity.name || null;
      if (typeof name === 'string' && name.includes(' - ')) {
        const p = name.split(' - ');
        home = p[0].trim();
        away = p.slice(1).join(' - ').trim();
      }
      events.push(
        eventShell(KEY, LABEL, {
          id: String(entity.id || ''),
          name,
          home,
          away,
          league: entity.tournament?.name || null,
          country: entity.tournament?.category?.name || null,
          schedule: scheduleFromUnix(entity.startTimestamp ? Number(entity.startTimestamp) : null),
          url: entity.id ? `https://www.sofascore.com/event/${entity.id}` : null,
        })
      );
      if (events.length >= limit) break;
    }
    return listPayload(KEY, LABEL, events, { query: q });
  });
}

module.exports = {
  key: KEY,
  label: LABEL,
  enabled: () => Boolean(cfg.enabled),
  health,
  live,
  today,
  leagues,
  leagueEvents,
  event,
  search,
};
