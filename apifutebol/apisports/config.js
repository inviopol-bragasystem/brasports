'use strict';

const path = require('path');

module.exports = {
  app: {
    name: 'Sportsbook API',
    version: '3.0.0',
    timezone: 'America/Sao_Paulo',
    port: Number(process.env.PORT || 8090),
    host: process.env.HOST || '0.0.0.0',
    defaultSource: process.env.DEFAULT_SOURCE || 'sofascore',
    debug: process.env.API_DEBUG === '1',
  },

  cache: {
    enabled: true,
    dir: path.join(__dirname, 'cache'),
    ttl: {
      live: 12,
      today: 40,
      event: 25,
      leagues: 300,
      search: 60,
      tokens: 280,
      default: 45,
    },
  },

  http: {
    timeoutMs: 18000,
    userAgent:
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
  },

  sources: {
    sofascore: {
      enabled: true,
      label: 'SofaScore',
      priority: 1,
      site: 'https://www.sofascore.com/pt',
      baseUrl: 'https://api.sofascore.com/api/v1',
      altBaseUrl: 'https://www.sofascore.com/api/v1',
      featuredLeagues: [
        { id: 325, name: 'Brasileirão Série A', country: 'Brazil' },
        { id: 390, name: 'Brasileirão Série B', country: 'Brazil' },
        { id: 329, name: 'Copa do Brasil', country: 'Brazil' },
        { id: 17, name: 'Premier League', country: 'England' },
        { id: 8, name: 'LaLiga', country: 'Spain' },
        { id: 23, name: 'Serie A', country: 'Italy' },
        { id: 35, name: 'Bundesliga', country: 'Germany' },
        { id: 34, name: 'Ligue 1', country: 'France' },
        { id: 7, name: 'UEFA Champions League', country: 'Europe' },
        { id: 679, name: 'UEFA Europa League', country: 'Europe' },
        { id: 16, name: 'Copa Libertadores', country: 'South America' },
        { id: 13, name: 'Copa Sudamericana', country: 'South America' },
      ],
    },

    vaidebet: {
      enabled: true,
      label: 'Vai de Bet',
      priority: 2,
      site: 'https://www.vaidebet.bet.br',
      apiBase: 'https://sb2frontend-altenar2.biahosted.com',
      integration: 'vaidebet',
      sportId: 66,
      culture: 'pt-BR',
      timezoneOffset: -180,
      eventCount: 120,
    },

    pixbet: {
      enabled: true,
      label: 'Pix.bet',
      priority: 3,
      site: 'https://pix.bet.br',
      fssbBase: 'https://prod20383.fssb.io',
      lang: 'BR-PT',
      sportId: '1',
      agentId: '152338457',
      siteId: '20383',
      listOddsLimit: 50,
    },
  },
};
