'use strict';

const config = require('../../config');
const sofascore = require('../sources/sofascore');
const vaidebet = require('../sources/vaidebet');
const pixbet = require('../sources/pixbet');
const { ApiError } = require('../lib/http');

const ALL = { sofascore, vaidebet, pixbet };

function sourcesList() {
  return Object.values(ALL)
    .map((s) => ({
      key: s.key,
      label: s.label,
      enabled: s.enabled(),
      site: config.sources[s.key]?.site || null,
      priority: config.sources[s.key]?.priority ?? 99,
    }))
    .sort((a, b) => a.priority - b.priority);
}

function enabledKeys(only = null) {
  if (only && String(only).toLowerCase().trim() !== 'all') {
    const k = String(only).toLowerCase().trim();
    if (!ALL[k] || !ALL[k].enabled()) {
      throw new ApiError(`Fonte '${k}' inválida ou desabilitada`, 400);
    }
    return [k];
  }
  return sourcesList()
    .filter((s) => s.enabled)
    .map((s) => s.key);
}

async function health(deep = false) {
  const items = {};
  for (const s of Object.values(ALL)) {
    if (!s.enabled()) {
      items[s.key] = { enabled: false, ok: null, message: 'disabled' };
      continue;
    }
    if (!deep) {
      items[s.key] = { enabled: true, ok: null, message: 'use ?deep=1' };
      continue;
    }
    items[s.key] = { enabled: true, ...(await s.health()) };
  }
  let ok = 0;
  let en = 0;
  for (const r of Object.values(items)) {
    if (!r.enabled) continue;
    en++;
    if (r.ok) ok++;
  }
  return {
    sources: items,
    enabled_count: en,
    ok_count: deep ? ok : null,
    default_source: config.app.defaultSource,
  };
}

async function call(method, source = null, args = []) {
  const keys = enabledKeys(source);
  const results = {};
  const errors = {};

  await Promise.all(
    keys.map(async (key) => {
      try {
        const src = ALL[key];
        let fn = src[method];
        if (typeof fn !== 'function' && method === 'upcoming' && typeof src.today === 'function') {
          fn = src.today;
        }
        if (typeof fn !== 'function') {
          throw new ApiError(`Método ${method} indisponível em ${key}`, 400);
        }
        results[key] = await fn.apply(src, args);
      } catch (e) {
        errors[key] = e.message || String(e);
      }
    })
  );

  if (!Object.keys(results).length && Object.keys(errors).length) {
    throw new ApiError('Todas as fontes falharam: ' + JSON.stringify(errors), 502);
  }
  return { results, errors };
}

function mergeEvents(callResult) {
  const merged = [];
  const comparison = {};
  for (const [source, payload] of Object.entries(callResult.results || {})) {
    for (const ev of payload.events || []) {
      if (!ev) continue;
      merged.push({ ...ev, source });
      const norm = `${String(ev.home || '').toLowerCase()}|${String(ev.away || '').toLowerCase()}`.replace(/\s+/g, ' ').trim();
      if (norm !== '|') {
        if (!comparison[norm]) comparison[norm] = {};
        comparison[norm][source] = {
          id: ev.id,
          odds: ev.odds?.match_result || null,
          score: ev.score || null,
          is_live: Boolean(ev.is_live),
        };
      }
    }
  }
  merged.sort((a, b) => {
    const la = a.is_live ? 0 : 1;
    const lb = b.is_live ? 0 : 1;
    if (la !== lb) return la - lb;
    return (a.schedule?.start_time_unix || 0) - (b.schedule?.start_time_unix || 0);
  });
  return {
    sport: 'football',
    count: merged.length,
    events: merged,
    comparison,
    errors: callResult.errors,
    sources_ok: Object.keys(callResult.results || {}),
    fetched_at: new Date().toISOString(),
  };
}

module.exports = {
  sourcesList,
  enabledKeys,
  health,
  call,
  mergeEvents,
  ALL,
};
