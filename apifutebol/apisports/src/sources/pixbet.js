'use strict';

const config = require('../../config');
const { httpGet, ApiError } = require('../lib/http');
const { remember, forget } = require('../lib/cache');
const { scheduleFromUnix, eventShell, listPayload, emptyOdds, roundPrice } = require('../lib/normalize');

const cfg = config.sources.pixbet;
const KEY = 'pixbet';
const LABEL = cfg.label;
const TTL = config.cache.ttl;

let tokensMem = null;

function fssb() {
  return String(cfg.fssbBase || 'https://prod20383.fssb.io').replace(/\/$/, '');
}

function baseHeaders(auth = false) {
  const h = {
    Accept: 'application/json, text/plain, */*',
    Origin: fssb(),
    Referer: `${fssb()}/br-pt/spbk/`,
    'time-area': 'european-modern',
  };
  if (auth && tokensMem) {
    h.Session = tokensMem.session;
    h.Authorization = tokensMem.internal;
  }
  return h;
}

async function ensureTokens() {
  if (tokensMem) return tokensMem;
  tokensMem = await remember('pixbet:tokens', TTL.tokens || 280, async () => {
    const url =
      `${fssb()}/br-pt/spbk/?operatorToken=logout&api=` +
      encodeURIComponent(`${cfg.site}/scripts/whl/production/pixbet/whl.js`);
    const res = await httpGet(
      url,
      {
        Accept: 'text/html,application/xhtml+xml',
        Referer: `${cfg.site}/sports`,
        Origin: cfg.site,
      },
      { allowError: true }
    );
    const html = res.text || '';
    if (!html || res.status >= 400) throw new ApiError('Pixbet: falha ao obter página FSSB (tokens)', 502);
    let internal = null;
    let session = null;
    let m = html.match(/'internalToken':'([^']+)'/) || html.match(/"internalToken"\s*:\s*"([^"]+)"/);
    if (m) internal = m[1];
    m = html.match(/'sessionToken':'([^']+)'/) || html.match(/"sessionToken"\s*:\s*"([^"]+)"/);
    if (m) session = m[1];
    if (!internal || !session) throw new ApiError('Pixbet: tokens não encontrados no HTML', 502);
    return { internal, session };
  });
  return tokensMem;
}

async function getJson(path, auth = false) {
  const url = path.startsWith('http') ? path : fssb() + path;
  if (auth) await ensureTokens();
  try {
    const res = await httpGet(url, baseHeaders(auth));
    if (res.json == null) throw new ApiError(`Pixbet JSON inválido: ${path}`, 502);
    return res.json;
  } catch (e) {
    if (auth && /HTTP (401|403|422)/.test(e.message || '')) {
      tokensMem = null;
      forget('pixbet:tokens');
      await ensureTokens();
      const res = await httpGet(url, baseHeaders(true));
      if (res.json == null) throw new ApiError(`Pixbet JSON inválido: ${path}`, 502);
      return res.json;
    }
    throw e;
  }
}

async function snapshotFixtures(onlyLive = null) {
  const lang = encodeURIComponent(cfg.lang || 'BR-PT');
  const data = await remember(`pixbet:snap:${cfg.lang || 'BR-PT'}`, TTL.live, async () =>
    getJson(`/api/pulse/snapshot/events?lang=${lang}`, false)
  );
  if (!Array.isArray(data)) return [];
  const sid = String(cfg.sportId || '1');
  return data.filter((row) => {
    if (!row || row.Type !== 'Fixture') return false;
    if (String(row.SportId || '') !== sid) return false;
    if (onlyLive === true && !row.IsLive) return false;
    if (onlyLive === false && row.IsLive) return false;
    return true;
  });
}

function mapFixture(fx, isLive, odds = null) {
  const participants = fx.Participants || [];
  let home = null;
  let away = null;
  for (const p of participants) {
    if (!p) continue;
    const role = p.VenueRole || p.venueRole;
    const name = p.Name || p.name || null;
    if (role === 'Home' || role === 'home') home = name;
    else if (role === 'Away' || role === 'away') away = name;
  }
  if ((home == null || away == null) && participants.length >= 2) {
    home = home ?? participants[0].Name ?? participants[0].name ?? null;
    away = away ?? participants[1].Name ?? participants[1].name ?? null;
  }
  const eventName = fx.EventName || null;
  if ((!home || !away) && typeof eventName === 'string' && /\s+vs\.?\s+/i.test(eventName)) {
    const p = eventName.split(/\s+vs\.?\s+/i);
    home = home || p[0]?.trim() || null;
    away = away || p[1]?.trim() || null;
  }
  const startMs = fx.StartEventDate != null ? Number(fx.StartEventDate) : 0;
  const unix = startMs > 0 ? Math.floor(startMs / 1000) : null;
  let score = null;
  const sc = fx.Score;
  if (sc && typeof sc === 'object') {
    const hs = sc.HomeScore ?? '';
    const as = sc.AwayScore ?? '';
    if (hs !== '' || as !== '' || isLive) {
      const lgs = fx.LiveGameState || {};
      score = {
        home: hs === '' ? null : isNaN(Number(hs)) ? hs : Number(hs),
        away: as === '' ? null : isNaN(Number(as)) ? as : Number(as),
        period: {
          description: lgs.GamePart || lgs.gamePart || null,
          clock_running: lgs.ClockRunning ?? null,
        },
      };
    }
  }
  const eid = String(fx._id || '');
  return eventShell(KEY, LABEL, {
    id: eid,
    name: eventName || `${home || '?'} - ${away || '?'}`,
    home,
    away,
    league: fx.LeagueName || null,
    league_id: fx.LeagueId != null ? String(fx.LeagueId) : null,
    country: fx.RegionName || null,
    is_live: isLive,
    status: fx.IsSuspended ? 'Suspenso' : isLive ? 'Ao vivo' : 'Agendado',
    score,
    odds,
    schedule: scheduleFromUnix(unix),
    url: `${cfg.site}/sports?eventId=${eid}`,
    markets_count: fx.TotalActiveMarketsCount ?? fx.TotalMarketsCount ?? null,
  });
}

function is1x2(typeId, name) {
  if (['ML39', 'ML0', 'ML1'].includes(String(typeId || ''))) return true;
  const n = String(name || '').toLowerCase();
  return n.includes('resultado final') || n.includes('1x2') || n.includes('vencedor') || n.includes('match result');
}

function selectionsTo1x2(choices) {
  const mr = {};
  for (const c of choices) {
    const n = String(c.name || '').toLowerCase().trim();
    if (n === '1' || n === 'home') mr['1'] = c.price;
    else if (n === 'x' || n === 'draw' || n.includes('empate')) mr.X = c.price;
    else if (n === '2' || n === 'away') mr['2'] = c.price;
  }
  if (mr['1'] == null || mr.X == null || mr['2'] == null) {
    for (const c of choices) {
      const n = String(c.name || '').toLowerCase();
      if (n.includes('empate') || n === 'x') mr.X = mr.X ?? c.price;
    }
    const rest = choices.filter((c) => {
      const n = String(c.name || '').toLowerCase();
      return !n.includes('empate') && n !== 'x' && n !== 'draw';
    });
    if (rest.length >= 2) {
      mr['1'] = mr['1'] ?? rest[0].price;
      mr['2'] = mr['2'] ?? rest[1].price;
    } else if (choices.length === 3) {
      mr['1'] = mr['1'] ?? choices[0].price;
      mr.X = mr.X ?? choices[1].price;
      mr['2'] = mr['2'] ?? choices[2].price;
    }
  }
  return Object.keys(mr).length ? mr : null;
}

function mapMarketsList(markets) {
  const out = emptyOdds();
  for (const m of markets) {
    if (!m) continue;
    const choices = [];
    for (const sel of m.Selections || []) {
      if (!sel || sel.IsRemoved) continue;
      let price = null;
      if (sel.TrueOdds != null) price = roundPrice(sel.TrueOdds);
      else if (sel.DisplayOdds?.Decimal != null) price = roundPrice(sel.DisplayOdds.Decimal);
      choices.push({
        name: sel.Name || sel.BetslipLine || sel.OutcomeType || null,
        price,
        side: sel.Side ?? null,
        disabled: Boolean(sel.IsDisabled),
      });
    }
    const typeId = m.MarketType?._id || null;
    const name = m.Name || m.MarketType?.Name || null;
    out.markets.push({
      id: m._id ?? null,
      name,
      type_id: typeId,
      is_live: Boolean(m.IsLive),
      is_suspended: Boolean(m.IsSuspended),
      choices,
    });
    if (!out.match_result && is1x2(typeId, name)) out.match_result = selectionsTo1x2(choices);
  }
  return out;
}

function mapEventPageMarkets(markets) {
  const out = emptyOdds();
  for (const m of markets) {
    if (!Array.isArray(m)) continue;
    const typeInfo = m[5];
    const typeId = Array.isArray(typeInfo) ? typeInfo[0] : null;
    const name = m[1] ?? m[3] ?? (Array.isArray(typeInfo) ? typeInfo[1] : null);
    const choices = [];
    for (const sel of m[13] || []) {
      if (!Array.isArray(sel)) continue;
      let selName = null;
      if (sel[1] && typeof sel[1] === 'object') selName = sel[1]['BR-PT'] || sel[1].EN || Object.values(sel[1])[0];
      else if (typeof sel[1] === 'string') selName = sel[1];
      let price = null;
      if (typeof sel[6] === 'number') price = roundPrice(sel[6]);
      else if (Array.isArray(sel[8]) && sel[8][1] != null) price = roundPrice(sel[8][1]);
      choices.push({ name: selName, price: price && price > 0 ? price : null });
    }
    out.markets.push({
      id: m[0] ?? null,
      name,
      type_id: typeId,
      is_live: Array.isArray(typeInfo) ? typeInfo[5] === 'Live' : null,
      choices,
    });
    if (!out.match_result && is1x2(typeId, name)) out.match_result = selectionsTo1x2(choices);
  }
  return out;
}

function mapEventPage(row, fixture) {
  const id = String(row[0] || '');
  const participants = row[8] || [];
  let home = null;
  let away = null;
  for (const p of participants) {
    if (!Array.isArray(p)) continue;
    if (p[2] === 'Home') home = p[1];
    else if (p[2] === 'Away') away = p[1];
  }
  if ((home == null || away == null) && participants.length >= 2) {
    home = home ?? participants[0]?.[1] ?? null;
    away = away ?? participants[1]?.[1] ?? null;
  }
  const eventName = row[10] ?? null;
  const startIso = row[11] ?? null;
  const scoreArr = row[12] ?? null;
  const isLive = Boolean(row[13]);
  const markets = row[20] || [];
  let unix = null;
  if (typeof startIso === 'string' && startIso) {
    const ts = Date.parse(startIso);
    if (!Number.isNaN(ts)) unix = Math.floor(ts / 1000);
  }
  let score = null;
  if (Array.isArray(scoreArr) && scoreArr.length >= 2) {
    score = {
      home: !isNaN(Number(scoreArr[0])) ? Number(scoreArr[0]) : scoreArr[0] ?? null,
      away: !isNaN(Number(scoreArr[1])) ? Number(scoreArr[1]) : scoreArr[1] ?? null,
      period: { description: isLive ? 'Ao vivo' : null },
    };
  }
  if (fixture && !score) score = mapFixture(fixture, isLive, null).score;
  const odds = mapEventPageMarkets(Array.isArray(markets) ? markets : []);
  return eventShell(KEY, LABEL, {
    id,
    name: typeof eventName === 'string' ? eventName : `${home || '?'} - ${away || '?'}`,
    home,
    away,
    league: row[2] ?? null,
    league_id: row[1] != null ? String(row[1]) : null,
    country: row[7] ?? null,
    is_live: isLive,
    status: isLive ? 'Ao vivo' : 'Agendado',
    score,
    odds,
    schedule: scheduleFromUnix(unix),
    url: `${cfg.site}/sports?eventId=${id}`,
    odds_raw_markets: odds.markets.length,
  });
}

async function attachListOdds(events, preferLiveOnly) {
  if (!events.length) return;
  const max = Number(cfg.listOddsLimit || 50);
  let ids = [];
  for (const ev of events) {
    if (preferLiveOnly && !ev.is_live) continue;
    if (!ev.id) continue;
    ids.push(ev.id);
    if (ids.length >= max) break;
  }
  if (!ids.length) ids = events.slice(0, max).map((e) => e.id).filter(Boolean);
  if (!ids.length) return;

  const marketsParam = `${ids.join('|')}:ML39|HC39|OU39|ML0|HC0|OU0`;
  try {
    const markets = await getJson(
      `/api/eventlist/eu/markets/all?markets=${encodeURIComponent(marketsParam)}`,
      true
    );
    if (!Array.isArray(markets)) return;
    const byEvent = {};
    for (const m of markets) {
      const eid = String(m?.EventId || '');
      if (!eid) continue;
      if (!byEvent[eid]) byEvent[eid] = [];
      byEvent[eid].push(m);
    }
    for (const ev of events) {
      if (ev.id && byEvent[ev.id]) ev.odds = mapMarketsList(byEvent[ev.id]);
    }
  } catch {
    // lista sem odds ainda útil
  }
}

async function health() {
  const t0 = Date.now();
  try {
    const fixtures = await snapshotFixtures(true);
    await ensureTokens();
    return {
      ok: true,
      latency_ms: Date.now() - t0,
      message: `ok (${fixtures.length} live, tokens ok)`,
    };
  } catch (e) {
    return { ok: false, latency_ms: Date.now() - t0, message: e.message };
  }
}

async function live() {
  return remember('pixbet:live', TTL.live, async () => {
    const fixtures = await snapshotFixtures(true);
    const events = fixtures.map((fx) => mapFixture(fx, true, null));
    await attachListOdds(events, true);
    return listPayload(KEY, LABEL, events, { mode: 'live', provider: 'fssb' });
  });
}

async function today() {
  return remember('pixbet:today', TTL.today, async () => {
    const fixtures = await snapshotFixtures(null);
    const now = Math.floor(Date.now() / 1000);
    const horizon = now + 3 * 86400;
    const events = [];
    const seen = {};
    for (const fx of fixtures) {
      const isLive = Boolean(fx.IsLive);
      const startMs = fx.StartEventDate != null ? Number(fx.StartEventDate) : 0;
      const startUnix = startMs > 0 ? Math.floor(startMs / 1000) : null;
      if (!isLive && startUnix != null && (startUnix < now - 3600 || startUnix > horizon)) continue;
      const m = mapFixture(fx, isLive, null);
      if (!m.id || seen[m.id]) continue;
      seen[m.id] = true;
      events.push(m);
    }
    await attachListOdds(events, false);
    events.sort((a, b) => {
      const la = a.is_live ? 0 : 1;
      const lb = b.is_live ? 0 : 1;
      if (la !== lb) return la - lb;
      return (a.schedule.start_time_unix || 0) - (b.schedule.start_time_unix || 0);
    });
    return listPayload(KEY, LABEL, events, { mode: 'today_and_upcoming', provider: 'fssb' });
  });
}

async function leagues() {
  return remember('pixbet:leagues', TTL.leagues, async () => {
    const fixtures = await snapshotFixtures(null);
    const leagues = {};
    for (const fx of fixtures) {
      const id = String(fx.LeagueId || '');
      if (!id || leagues[id]) continue;
      leagues[id] = {
        id,
        name: fx.LeagueName || null,
        country: fx.RegionName || null,
        source: KEY,
      };
    }
    const list = Object.values(leagues).sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')));
    return { source: KEY, count: list.length, leagues: list, provider: 'fssb' };
  });
}

async function event(id, { odds = true } = {}) {
  id = String(id).replace(/\D/g, '') || id;
  const withOdds = odds !== false && odds !== '0';
  return remember(`pixbet:ev:${id}:${withOdds ? 'o' : 'n'}`, TTL.event, async () => {
    let fixture = null;
    try {
      for (const fx of await snapshotFixtures(null)) {
        if (String(fx._id) === String(id)) {
          fixture = fx;
          break;
        }
      }
    } catch {
      // ignore
    }

    let mapped = null;
    if (withOdds) {
      try {
        const page = await getJson(`/api/eventpage/events/${encodeURIComponent(id)}`, true);
        if (page?.data?.[0] && Array.isArray(page.data[0])) {
          mapped = mapEventPage(page.data[0], fixture);
        }
      } catch (e) {
        if (!fixture) throw e;
      }
    }
    if (!mapped && fixture) mapped = mapFixture(fixture, Boolean(fixture.IsLive), null);
    if (!mapped) throw new ApiError(`Evento Pixbet ${id} não encontrado`, 404);
    return { source: KEY, event: mapped, provider: 'fssb', fetched_at: new Date().toISOString() };
  });
}

async function search(q, limit = 30) {
  q = String(q || '').trim();
  if (!q) return listPayload(KEY, LABEL, []);
  limit = Math.max(1, Math.min(50, Number(limit) || 30));
  return remember(`pixbet:search:${q}:${limit}`, TTL.search, async () => {
    let events = [];
    try {
      const data = await getJson(`/api/eventlist/eu/search/tabbed?query=${encodeURIComponent(q)}`, true);
      const rows = data.events?.data || data.events || [];
      for (const fx of rows) {
        if (!fx) continue;
        if (fx.SportId != null && String(fx.SportId) !== String(cfg.sportId || '1')) continue;
        events.push(mapFixture(fx, Boolean(fx.IsLive), null));
        if (events.length >= limit) break;
      }
    } catch {
      const needle = q.toLowerCase();
      for (const fx of await snapshotFixtures(null)) {
        const hay = [fx.EventName, fx.LeagueName, fx.RegionName].filter(Boolean).join(' ').toLowerCase();
        if (hay.includes(needle)) events.push(mapFixture(fx, Boolean(fx.IsLive), null));
        if (events.length >= limit) break;
      }
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
  event,
  search,
};
