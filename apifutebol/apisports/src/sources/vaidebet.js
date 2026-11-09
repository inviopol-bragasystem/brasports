'use strict';

const config = require('../../config');
const { httpGet, ApiError } = require('../lib/http');
const { remember } = require('../lib/cache');
const { scheduleFromUnix, eventShell, listPayload, emptyOdds, roundPrice } = require('../lib/normalize');

const cfg = config.sources.vaidebet;
const KEY = 'vaidebet';
const LABEL = cfg.label;
const TTL = config.cache.ttl;

function headers() {
  return {
    Accept: 'application/json, text/plain, */*',
    Origin: cfg.site,
    Referer: cfg.site + '/',
  };
}

function qs(extra = {}) {
  const p = {
    culture: cfg.culture || 'pt-BR',
    timezoneOffset: String(cfg.timezoneOffset ?? -180),
    integration: cfg.integration || 'vaidebet',
    deviceType: '1',
    numFormat: 'en-GB',
    countryCode: 'BR',
    ...Object.fromEntries(Object.entries(extra).map(([k, v]) => [k, String(v)])),
  };
  return new URLSearchParams(p).toString();
}

async function widget(action, extra = {}) {
  const url = `${cfg.apiBase.replace(/\/$/, '')}/api/Widget/${action}?${qs(extra)}`;
  const res = await httpGet(url, headers());
  if (!res.json) throw new ApiError(`Vaidebet JSON inválido: ${action}`, 502);
  return res.json;
}

function flattenOddIds(ids) {
  if (!Array.isArray(ids)) return [];
  const out = [];
  const walk = (n) => {
    if (Array.isArray(n)) {
      if (n.length && typeof n[0] !== 'object') {
        for (const v of n) if (v != null && !Number.isNaN(Number(v))) out.push(Number(v));
        return;
      }
      for (const v of n) walk(v);
      return;
    }
    if (n != null && !Number.isNaN(Number(n))) out.push(Number(n));
  };
  walk(ids);
  return [...new Set(out)];
}

function is1x2(name, typeId) {
  if (typeId === 1 || typeId === 186) return true;
  const n = String(name || '').toLowerCase();
  return n.includes('vencedor do encontro') || n.includes('resultado final') || n.includes('1x2') || n.includes('match result');
}

function choicesTo1x2(choices, home = '', away = '') {
  const mr = {};
  const h = String(home || '').toLowerCase();
  const a = String(away || '').toLowerCase();
  for (const c of choices) {
    const n = String(c.name || '').toLowerCase().trim();
    const tid = Number(c.type_id || 0);
    if (tid === 1 || n === '1' || n === 'home' || (h && n === h)) mr['1'] = c.price;
    else if (tid === 2 || n === 'x' || n === 'draw' || n.includes('empate')) mr.X = c.price;
    else if (tid === 3 || n === '2' || n === 'away' || (a && n === a)) mr['2'] = c.price;
  }
  if (!Object.keys(mr).length && choices.length === 3) {
    mr['1'] = choices[0].price;
    mr.X = choices[1].price;
    mr['2'] = choices[2].price;
  }
  return Object.keys(mr).length ? mr : null;
}

function mapBundle(raw, forceLive) {
  const competitors = {};
  for (const c of raw.competitors || []) if (c?.id != null) competitors[Number(c.id)] = c;
  const champs = {};
  for (const c of raw.champs || []) if (c?.id != null) champs[Number(c.id)] = c;
  const categories = {};
  for (const c of raw.categories || []) if (c?.id != null) categories[Number(c.id)] = c;
  const marketsById = {};
  for (const m of raw.markets || []) if (m?.id != null) marketsById[Number(m.id)] = m;
  const oddsById = {};
  for (const o of raw.odds || []) if (o?.id != null) oddsById[Number(o.id)] = o;

  const events = [];
  for (const ev of raw.events || []) {
    if (!ev) continue;
    if (ev.sportId != null && Number(ev.sportId) !== Number(cfg.sportId || 66)) continue;
    events.push(mapListEvent(ev, competitors, champs, categories, marketsById, oddsById, forceLive));
  }
  return events;
}

function mapListEvent(ev, competitors, champs, categories, marketsById, oddsById, forceLive) {
  const ids = ev.competitorIds || [];
  let home = ids[0] != null ? competitors[Number(ids[0])]?.name || null : null;
  let away = ids[1] != null ? competitors[Number(ids[1])]?.name || null : null;
  const name = ev.name || null;
  if ((!home || !away) && typeof name === 'string' && /\s+vs\.?\s+/i.test(name)) {
    const p = name.split(/\s+vs\.?\s+/i);
    home = home || p[0]?.trim() || null;
    away = away || p[1]?.trim() || null;
  }
  const champId = Number(ev.champId || 0);
  const catId = Number(ev.catId || 0);
  const isLive = forceLive || Number(ev.status || 0) === 1 || Boolean(ev.liveTime);
  let score = null;
  if (Array.isArray(ev.score) && ev.score.length >= 2) {
    const m = String(ev.liveTime || '').match(/(\d{1,3})/);
    score = {
      home: ev.score[0] ?? null,
      away: ev.score[1] ?? null,
      period: { description: ev.ls || ev.liveTime || null, minute: m ? Number(m[1]) : null },
    };
  }
  let unix = null;
  if (ev.startDate) {
    const ts = Date.parse(ev.startDate);
    if (!Number.isNaN(ts)) unix = Math.floor(ts / 1000);
  }

  const odds = emptyOdds();
  for (const mid of ev.marketIds || []) {
    const m = marketsById[Number(mid)];
    if (!m) continue;
    const choices = [];
    for (const oid of m.oddIds || []) {
      const o = oddsById[Number(oid)];
      if (!o) continue;
      choices.push({
        name: o.name || null,
        price: roundPrice(o.price),
        type_id: o.typeId ?? null,
      });
    }
    odds.markets.push({
      id: m.id ?? mid,
      name: m.name || m.headerName || null,
      type_id: m.typeId ?? null,
      is_live: isLive,
      choices,
    });
    if (!odds.match_result && is1x2(m.name, Number(m.typeId || 0))) {
      odds.match_result = choicesTo1x2(choices, home, away);
    }
  }

  return eventShell(KEY, LABEL, {
    id: String(ev.id || ''),
    name: name || `${home || '?'} - ${away || '?'}`,
    home,
    away,
    league: champs[champId]?.name || null,
    league_id: champId ? String(champId) : null,
    country: categories[catId]?.name || null,
    is_live: isLive,
    status: isLive ? String(ev.ls || ev.liveTime || 'Ao vivo') : 'Agendado',
    score,
    odds: odds.markets.length || odds.match_result ? odds : null,
    schedule: scheduleFromUnix(unix),
    url: `${cfg.site}/sports#/event/${ev.id || ''}`,
    markets_count: Array.isArray(ev.marketIds) ? ev.marketIds.length : null,
  });
}

function mapDetail(raw) {
  let home = raw.competitors?.[0]?.name || null;
  let away = raw.competitors?.[1]?.name || null;
  const name = raw.name || null;
  if ((!home || !away) && typeof name === 'string' && /\s+vs\.?\s+/i.test(name)) {
    const p = name.split(/\s+vs\.?\s+/i);
    home = home || p[0]?.trim() || null;
    away = away || p[1]?.trim() || null;
  }
  const isLive = Number(raw.status || 0) === 1 || Boolean(raw.liveTime) || Boolean(raw.ls);
  let unix = null;
  if (raw.startDate) {
    const ts = Date.parse(raw.startDate);
    if (!Number.isNaN(ts)) unix = Math.floor(ts / 1000);
  }
  let score = null;
  if (Array.isArray(raw.score) && raw.score.length >= 2) {
    score = {
      home: raw.score[0] ?? null,
      away: raw.score[1] ?? null,
      period: { description: raw.ls || raw.liveTime || null },
    };
  }

  const oddsById = {};
  for (const o of raw.odds || []) if (o?.id != null) oddsById[Number(o.id)] = o;
  const childById = {};
  for (const cm of raw.childMarkets || []) if (cm?.id != null) childById[Number(cm.id)] = cm;

  const flat = [];
  for (const m of raw.markets || []) {
    if (!m) continue;
    const ids = flattenOddIds(m.oddIds || m.desktopOddIds || m.mobileOddIds || []);
    if (!ids.length && Array.isArray(m.childMarketIds)) {
      for (const cid of m.childMarketIds) if (childById[Number(cid)]) flat.push(childById[Number(cid)]);
    }
    flat.push(m);
  }
  for (const cm of raw.childMarkets || []) if (cm) flat.push(cm);

  const odds = emptyOdds();
  const seen = {};
  for (const m of flat) {
    if (!m) continue;
    const mid = String(m.id || '');
    if (mid && seen[mid]) continue;
    if (mid) seen[mid] = true;
    const ids = flattenOddIds(m.oddIds || m.desktopOddIds || m.mobileOddIds || []);
    if (!ids.length) continue;
    const choices = [];
    for (const oid of ids) {
      const o = oddsById[oid];
      if (!o) continue;
      choices.push({
        name: o.name || null,
        price: roundPrice(o.price),
        type_id: o.typeId ?? null,
        line: o.sv ?? m.sv ?? null,
      });
    }
    if (!choices.length) continue;
    const row = {
      id: m.id ?? null,
      name: m.name || m.shortName || null,
      type_id: m.typeId ?? null,
      is_live: isLive,
      choices,
    };
    odds.markets.push(row);
    if (!odds.match_result && is1x2(row.name, Number(m.typeId || 0))) {
      odds.match_result = choicesTo1x2(choices, home, away);
    }
  }

  return eventShell(KEY, LABEL, {
    id: String(raw.id || ''),
    name: name || `${home || '?'} - ${away || '?'}`,
    home,
    away,
    league: raw.champ?.name || null,
    country: raw.category?.name || null,
    is_live: isLive,
    status: isLive ? String(raw.ls || raw.liveTime || 'Ao vivo') : 'Agendado',
    score,
    odds,
    schedule: scheduleFromUnix(unix),
    url: `${cfg.site}/sports#/event/${raw.id || ''}`,
    sport_name: raw.sport?.name || null,
    odds_raw_markets: odds.markets.length,
  });
}

async function health() {
  const t0 = Date.now();
  try {
    const data = await widget('GetLiveOverview');
    let n = 0;
    for (const s of data.liveSports || []) {
      if (Number(s.id) === Number(cfg.sportId || 66)) {
        n = Number(s.count || 0);
        break;
      }
    }
    return { ok: true, latency_ms: Date.now() - t0, message: `ok (${n} live futebol)` };
  } catch (e) {
    return { ok: false, latency_ms: Date.now() - t0, message: e.message };
  }
}

async function live() {
  return remember('vaidebet:live', TTL.live, async () => {
    const raw = await widget('GetLiveEvents', { sportId: cfg.sportId || 66 });
    return listPayload(KEY, LABEL, mapBundle(raw, true), { mode: 'live', provider: 'altenar' });
  });
}

async function today() {
  return remember('vaidebet:today', TTL.today, async () => {
    // GetEvents: eventCount sem page (page=0 às vezes retorna 400)
    let raw;
    try {
      raw = await widget('GetEvents', {
        sportId: cfg.sportId || 66,
        eventCount: cfg.eventCount || 100,
      });
    } catch {
      raw = await widget('GetEvents', { sportId: cfg.sportId || 66 });
    }
    let events = mapBundle(raw, false);
    try {
      const liveEv = (await live()).events || [];
      const seen = Object.fromEntries(events.map((e) => [e.id, true]));
      for (const e of liveEv) {
        if (e.id && !seen[e.id]) {
          events.push(e);
          seen[e.id] = true;
        }
      }
    } catch {
      // ignore
    }
    events.sort((a, b) => {
      const la = a.is_live ? 0 : 1;
      const lb = b.is_live ? 0 : 1;
      if (la !== lb) return la - lb;
      return (a.schedule.start_time_unix || 0) - (b.schedule.start_time_unix || 0);
    });
    return listPayload(KEY, LABEL, events, { mode: 'today_and_upcoming', provider: 'altenar' });
  });
}

async function leagues() {
  return remember('vaidebet:leagues', TTL.leagues, async () => {
    const list = [];
    const seen = {};
    try {
      const raw = await widget('GetEvents', { sportId: cfg.sportId || 66, eventCount: 120 });
      for (const ch of raw.champs || []) {
        if (!ch?.id || seen[ch.id]) continue;
        seen[ch.id] = true;
        list.push({ id: String(ch.id), name: ch.name || null, country: null, source: KEY });
      }
    } catch {
      // ignore
    }
    return { source: KEY, count: list.length, leagues: list, provider: 'altenar' };
  });
}

async function event(id, { odds = true } = {}) {
  id = String(id).replace(/\D/g, '') || id;
  const withOdds = odds !== false && odds !== '0';
  return remember(`vaidebet:ev:${id}:${withOdds ? 'o' : 'n'}`, TTL.event, async () => {
    const raw = await widget('GetEventDetails', { eventId: id });
    if (!raw?.id) throw new ApiError(`Evento Vaidebet ${id} não encontrado`, 404);
    const mapped = mapDetail(raw);
    if (!withOdds) mapped.odds = null;
    return { source: KEY, event: mapped, provider: 'altenar', fetched_at: new Date().toISOString() };
  });
}

async function search(q, limit = 30) {
  q = String(q || '').toLowerCase().trim();
  if (!q) return listPayload(KEY, LABEL, []);
  limit = Math.max(1, Math.min(50, Number(limit) || 30));
  return remember(`vaidebet:search:${q}:${limit}`, TTL.search, async () => {
    const pool = {};
    for (const m of ['live', 'today']) {
      try {
        for (const e of (await module.exports[m]()).events || []) pool[e.id] = e;
      } catch {
        // ignore
      }
    }
    const matches = [];
    for (const e of Object.values(pool)) {
      const hay = [e.name, e.home, e.away, e.league].filter(Boolean).join(' ').toLowerCase();
      if (hay.includes(q)) matches.push(e);
      if (matches.length >= limit) break;
    }
    return listPayload(KEY, LABEL, matches, { query: q });
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
  event,
  search,
};
