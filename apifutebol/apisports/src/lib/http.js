'use strict';

const { spawnSync } = require('child_process');
const config = require('../../config');

class ApiError extends Error {
  constructor(message, status = 502) {
    super(message);
    this.status = status;
  }
}

function findCurl() {
  if (process.platform === 'win32') {
    const candidates = ['curl.exe', 'C:\\Windows\\System32\\curl.exe'];
    for (const c of candidates) {
      const r = spawnSync(c, ['--version'], { encoding: 'utf8', windowsHide: true });
      if (r.status === 0) return c;
    }
    return null;
  }
  const r = spawnSync('curl', ['--version'], { encoding: 'utf8' });
  return r.status === 0 ? 'curl' : null;
}

const CURL = findCurl();

function curlGet(url, headers = {}, timeoutMs = 28000) {
  if (!CURL) throw new ApiError('curl não disponível no sistema', 502);
  const args = [
    '-sS',
    '-L',
    '--max-time',
    String(Math.ceil(timeoutMs / 1000)),
    '-A',
    config.http.userAgent,
    '-w',
    '\n__HTTP_STATUS__:%{http_code}',
  ];
  for (const [k, v] of Object.entries(headers)) {
    if (v == null) continue;
    args.push('-H', `${k}: ${v}`);
  }
  args.push(url);

  const r = spawnSync(CURL, args, {
    encoding: 'utf8',
    maxBuffer: 25 * 1024 * 1024,
    windowsHide: true,
  });

  if (r.error) throw new ApiError(`curl error: ${r.error.message}`, 502);
  const out = String(r.stdout || '');
  const m = out.match(/\n__HTTP_STATUS__:(\d+)\s*$/);
  const status = m ? Number(m[1]) : 0;
  const body = m ? out.slice(0, m.index) : out;

  let json = null;
  const t = body.trim();
  if (t.startsWith('{') || t.startsWith('[')) {
    try {
      json = JSON.parse(t);
    } catch {
      json = null;
    }
  }
  return { status, json, text: body };
}

async function nativeGet(url, headers = {}, timeoutMs = 28000) {
  const ctrl = new AbortController();
  const timer = setTimeout(() => ctrl.abort(), timeoutMs);
  try {
    const res = await fetch(url, {
      method: 'GET',
      headers: {
        'User-Agent': config.http.userAgent,
        Accept: 'application/json, text/plain, */*',
        'Accept-Language': 'pt-BR,pt;q=0.9,en;q=0.8',
        ...headers,
      },
      signal: ctrl.signal,
      redirect: 'follow',
    });
    const text = await res.text();
    let json = null;
    const t = text.trim();
    if (t.startsWith('{') || t.startsWith('[')) {
      try {
        json = JSON.parse(t);
      } catch {
        json = null;
      }
    }
    return { status: res.status, json, text };
  } catch (e) {
    if (e?.name === 'AbortError') throw new ApiError(`Timeout: ${url}`, 504);
    throw new ApiError(e.message || String(e), 502);
  } finally {
    clearTimeout(timer);
  }
}

/**
 * GET com fallback curl (Cloudflare costuma bloquear fetch do Node; curl do SO passa).
 */
async function httpGet(url, headers = {}, opts = {}) {
  const timeoutMs = opts.timeoutMs || config.http.timeoutMs || 28000;
  const allowError = Boolean(opts.allowError);
  const preferCurl = Boolean(opts.preferCurl) || /sofascore\.com/i.test(url);

  let res;
  if (preferCurl && CURL) {
    res = curlGet(url, {
      Accept: 'application/json, text/plain, */*',
      'Accept-Language': 'pt-BR,pt;q=0.9,en;q=0.8',
      ...headers,
    }, timeoutMs);
  } else {
    res = await nativeGet(url, headers, timeoutMs);
    // Cloudflare / bot block → tenta curl
    if (CURL && [403, 429, 503].includes(res.status)) {
      try {
        const viaCurl = curlGet(url, {
          Accept: 'application/json, text/plain, */*',
          'Accept-Language': 'pt-BR,pt;q=0.9,en;q=0.8',
          ...headers,
        }, timeoutMs);
        if (viaCurl.status < 400 || viaCurl.json) res = viaCurl;
      } catch {
        // keep original
      }
    }
  }

  if (!allowError && res.status >= 400) {
    throw new ApiError(`HTTP ${res.status} ${url}`, res.status === 404 ? 404 : 502);
  }
  return res;
}

module.exports = { httpGet, ApiError, CURL };
