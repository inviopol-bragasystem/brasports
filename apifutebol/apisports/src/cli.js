'use strict';

const agg = require('./services/aggregator');

async function main() {
  const args = process.argv.slice(2);
  const params = {};
  for (const arg of args) {
    if (arg.startsWith('--')) {
      const [k, v] = arg.slice(2).split('=');
      params[k] = v || true;
    }
  }

  const mode = params.mode || 'today'; // today, live, upcoming
  const source = params.source || null;

  try {
    let callResult;
    if (mode === 'live') {
      callResult = await agg.call('live', source);
    } else if (mode === 'upcoming') {
      callResult = await agg.call('upcoming', source);
    } else {
      callResult = await agg.call('today', source);
    }

    const merged = agg.mergeEvents(callResult);
    process.stdout.write(JSON.stringify({ success: true, ...merged }));
  } catch (err) {
    process.stdout.write(JSON.stringify({ success: false, error: err.message || String(err) }));
    process.exit(1);
  }
}

main();
