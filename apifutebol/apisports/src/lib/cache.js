'use strict';

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const config = require('../../config');

const dir = config.cache.dir;
if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });

function fileFor(key) {
  return path.join(dir, `c_${crypto.createHash('sha1').update(key).digest('hex')}.json`);
}

async function remember(key, ttlSec, fn) {
  if (!config.cache.enabled || !ttlSec || ttlSec <= 0) return fn();

  const file = fileFor(key);
  try {
    if (fs.existsSync(file)) {
      const row = JSON.parse(fs.readFileSync(file, 'utf8'));
      if (row.exp > Date.now()) return row.val;
    }
  } catch {
    // miss
  }

  const val = await fn();
  try {
    fs.writeFileSync(file, JSON.stringify({ exp: Date.now() + ttlSec * 1000, val }), 'utf8');
  } catch {
    // ignore disk errors
  }
  return val;
}

function forget(key) {
  try {
    const f = fileFor(key);
    if (fs.existsSync(f)) fs.unlinkSync(f);
  } catch {
    // ignore
  }
}

function clear() {
  let n = 0;
  for (const name of fs.readdirSync(dir)) {
    if (name.startsWith('c_') && name.endsWith('.json')) {
      try {
        fs.unlinkSync(path.join(dir, name));
        n++;
      } catch {
        // ignore
      }
    }
  }
  return n;
}

module.exports = { remember, forget, clear };
