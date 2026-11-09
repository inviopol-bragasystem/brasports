'use strict';

function scheduleFromUnix(unix) {
  if (!unix) {
    return {
      start_time_unix: null,
      start_time_utc: null,
      start_time_br: null,
      date_br: null,
      time_br: null,
    };
  }
  const d = new Date(unix * 1000);
  const parts = new Intl.DateTimeFormat('en-CA', {
    timeZone: 'America/Sao_Paulo',
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  }).formatToParts(d);
  const g = (t) => parts.find((p) => p.type === t)?.value;
  return {
    start_time_unix: unix,
    start_time_utc: d.toISOString(),
    start_time_br: `${g('year')}-${g('month')}-${g('day')}T${g('hour')}:${g('minute')}:${g('second')}-03:00`,
    date_br: `${g('year')}-${g('month')}-${g('day')}`,
    time_br: `${g('hour')}:${g('minute')}`,
  };
}

function eventShell(source, label, fields = {}) {
  return {
    source,
    source_label: label,
    sport: 'football',
    id: null,
    name: null,
    home: null,
    away: null,
    league: null,
    league_id: null,
    country: null,
    is_live: false,
    status: null,
    score: null,
    odds: null,
    schedule: scheduleFromUnix(null),
    url: null,
    ...fields,
  };
}

function listPayload(source, label, events, extra = {}) {
  return {
    source,
    source_label: label,
    sport: 'football',
    count: events.length,
    events,
    fetched_at: new Date().toISOString(),
    ...extra,
  };
}

function roundPrice(n) {
  if (n == null || Number.isNaN(Number(n))) return null;
  return Math.round(Number(n) * 1000) / 1000;
}

function emptyOdds() {
  return { match_result: null, markets: [] };
}

module.exports = {
  scheduleFromUnix,
  eventShell,
  listPayload,
  roundPrice,
  emptyOdds,
};
