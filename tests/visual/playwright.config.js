// Testes de fidelidade ao protótipo v3. Rode com ./bin/testar.sh (sobe o servidor do protótipo sozinho).
const { defineConfig } = require('@playwright/test');
const path = require('path');

const PORTA = Number(process.env.PROTO_PORTA || 8090);

module.exports = defineConfig({
  testDir: __dirname,
  testMatch: /.*\.spec\.js$/,
  outputDir: path.join(__dirname, 'resultados'),
  timeout: 120_000,
  expect: { timeout: 10_000 },
  fullyParallel: true,
  workers: process.env.CI ? 2 : 4,
  retries: 0,
  reporter: [['list'], ['html', { outputFolder: path.join(__dirname, 'relatorio'), open: 'never' }]],
  use: {
    locale: 'pt-BR',
    timezoneId: 'America/Sao_Paulo',
    // Data da agenda fixa em 06/10/2026 (a do protótipo). O site local mostra o dia real; só o ieadc_hoje() do
    // ambiente "local" obedece a este cabeçalho (wp-content/mu-plugins/ieadc-core/agenda.php).
    extraHTTPHeaders: { 'X-Ieadc-Hoje': process.env.IEADC_HOJE_TESTE || '2026-10-06' },
    trace: 'off',
    ...(process.env.IEADC_CHROMIUM ? { launchOptions: { executablePath: process.env.IEADC_CHROMIUM } } : {}),
  },
  webServer: {
    command: `node "${path.join(__dirname, 'servidor-prototipo.js')}" ${PORTA}`,
    url: `http://localhost:${PORTA}/inicio.html`,
    reuseExistingServer: true,
    timeout: 20_000,
  },
});
