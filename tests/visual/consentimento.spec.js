// Aviso de cookies e medição com consentimento (LGPD). Rode com ./bin/testar.sh consentimento.
// O ID de teste GTM-TESTE0000 só existe durante o teste: é gravado em Configurações (opção ieadc_info) antes e a opção volta ao que era no fim.
// As requisições a googletagmanager.com são interceptadas (resposta 200 vazia): nada sai para a rede.
// Prova: o aviso aparece; "Recusar" não carrega o gtm.js; "Aceitar" carrega; a escolha persiste; sem ID não há aviso; eventos só com consentimento;
// foco e teclado do aviso; axe sem violações com o aviso aberto (celular e computador).
const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;
const { execFileSync } = require('child_process');
const path = require('path');
const { WP, RAIZ } = require('./lib/paginas');

const ID_TESTE = 'GTM-TESTE0000';
const TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'];

/** wp-cli no contêiner (o mesmo que bin/wp.sh usa). Devolve a saída; aceita falha quando `tolera`. */
function wp(args, tolera = false) {
  try {
    return execFileSync('docker', ['compose', 'run', '--rm', '-T', 'wpcli', 'wp', ...args], { cwd: RAIZ, encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] });
  } catch (e) {
    if (tolera) return null;
    throw e;
  }
}

let original; // null = a opção não existia

test.describe.configure({ mode: 'serial' });

test.beforeAll(() => {
  const atual = wp(['option', 'get', 'ieadc_info', '--format=json'], true);
  original = atual ? JSON.parse(atual) : null;
  const novo = Object.assign({}, original || {}, { gtm_id: ID_TESTE });
  wp(['option', 'update', 'ieadc_info', JSON.stringify(novo), '--format=json']);
});

test.afterAll(() => {
  if (original === null) wp(['option', 'delete', 'ieadc_info'], true);
  else wp(['option', 'update', 'ieadc_info', JSON.stringify(original), '--format=json']);
});

/** Contexto novo (sem localStorage nem cookies), com o GTM interceptado e a lista de pedidos a ele. */
async function novaPagina(browser, largura = 1280) {
  const context = await browser.newContext({ viewport: { width: largura, height: 800 }, locale: 'pt-BR' });
  const pedidos = [];
  await context.route(/googletagmanager\.com|google-analytics\.com|clarity\.ms/, (rota) => {
    pedidos.push(rota.request().url());
    return rota.fulfill({ status: 200, contentType: 'application/javascript', body: '' });
  });
  const page = await context.newPage();
  return { context, page, pedidos };
}

async function abrir(page, caminho = '/') {
  await page.goto(WP + caminho, { waitUntil: 'load' });
  await page.waitForFunction(() => !!document.getElementById('ieadc-cookies'));
}

test('o aviso aparece, tem Aceitar e Recusar iguais e nada do Google carrega antes da escolha', async ({ browser }) => {
  const { context, page, pedidos } = await novaPagina(browser);
  await abrir(page);
  const aviso = page.getByRole('dialog', { name: /medir os acessos/i });
  await expect(aviso).toBeVisible();
  await expect(aviso).toContainText('Google Analytics');
  await expect(aviso.getByRole('link', { name: /política de privacidade/i })).toBeVisible();
  const aceitar = aviso.getByRole('button', { name: 'Aceitar' });
  const recusar = aviso.getByRole('button', { name: 'Recusar' });
  await expect(aceitar).toBeVisible();
  await expect(recusar).toBeVisible();
  // Mesmo destaque: tamanho, cor, fundo e peso iguais.
  const estilo = (b) => b.evaluate((el) => { const c = getComputedStyle(el), r = el.getBoundingClientRect(); return [Math.round(r.width), Math.round(r.height), c.backgroundColor, c.color, c.fontWeight, c.fontSize, c.borderTopColor].join('|'); });
  expect(await estilo(aceitar)).toBe(await estilo(recusar));
  // Consent Mode v2: tudo negado por padrão, antes de qualquer tag.
  const padrao = await page.evaluate(() => Array.from(window.dataLayer).filter((x) => x[0] === 'consent' && x[1] === 'default').map((x) => x[2]));
  expect(padrao.length).toBe(1);
  expect(padrao[0]).toMatchObject({ ad_storage: 'denied', analytics_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied' });
  expect(pedidos).toEqual([]);
  await context.close();
});

test('Recusar não carrega o gtm.js, guarda a escolha e o aviso não volta', async ({ browser }) => {
  const { context, page, pedidos } = await novaPagina(browser);
  await abrir(page);
  await page.getByRole('button', { name: 'Recusar' }).click();
  await expect(page.locator('#ieadc-cookies')).toBeHidden();
  // Foco gerenciado: vai para o conteúdo principal, não fica "no ar".
  await expect.poll(() => page.evaluate(() => document.activeElement && document.activeElement.id)).toBe('main');
  expect(await page.evaluate(() => localStorage.getItem('ieadc_consentimento'))).toBe('recusado');
  await page.reload({ waitUntil: 'load' });
  await page.waitForTimeout(500);
  await expect(page.locator('#ieadc-cookies')).toBeHidden();
  // Um clique em telefone depois de recusar não gera evento.
  await page.evaluate(() => { document.addEventListener('click', (e) => e.preventDefault(), true); });
  const tel = page.locator('a[href^="tel:"]').first();
  if (await tel.count()) {
    await tel.click({ force: true });
    expect(await page.evaluate(() => Array.from(window.dataLayer).some((x) => x && x.event))).toBe(false);
  }
  expect(pedidos).toEqual([]);
  await context.close();
});

test('Aceitar carrega o gtm.js com o ID, libera só a medição e a escolha persiste', async ({ browser }) => {
  const { context, page, pedidos } = await novaPagina(browser);
  await abrir(page);
  await page.getByRole('button', { name: 'Aceitar' }).click();
  await expect(page.locator('#ieadc-cookies')).toBeHidden();
  await expect.poll(() => pedidos.some((u) => u.includes('googletagmanager.com/gtm.js') && u.includes('id=' + ID_TESTE))).toBe(true);
  const dl = await page.evaluate(() => Array.from(window.dataLayer).map((x) => (x && x[0] === 'consent' ? ['consent', x[1], x[2]] : x)));
  const update = dl.find((x) => Array.isArray(x) && x[0] === 'consent' && x[1] === 'update');
  expect(update && update[2]).toMatchObject({ analytics_storage: 'granted' });
  expect(update[2].ad_storage).toBeUndefined(); // anúncios seguem negados
  expect(dl.some((x) => x && x.event === 'gtm.js')).toBe(true);
  expect(await page.evaluate(() => localStorage.getItem('ieadc_consentimento'))).toBe('aceito');
  // Persiste: nova visita (mesmo contexto) carrega o GTM sozinha e não mostra o aviso.
  pedidos.length = 0;
  await page.goto(WP + '/contato/', { waitUntil: 'load' });
  await page.waitForTimeout(600);
  await expect(page.locator('#ieadc-cookies')).toBeHidden();
  expect(pedidos.some((u) => u.includes('gtm.js'))).toBe(true);
  await context.close();
});

test('Clarity: só com o ID, só depois do Aceitar, com a API de consentimento e a CSP liberada', async ({ browser }) => {
  const atual = JSON.parse(wp(['option', 'get', 'ieadc_info', '--format=json']));
  wp(['option', 'update', 'ieadc_info', JSON.stringify(Object.assign({}, atual, { clarity_id: 'abcde12345' })), '--format=json']);
  try {
    // Recusar: nada do Clarity.
    let { context, page, pedidos } = await novaPagina(browser);
    await abrir(page);
    await expect(page.locator('script[src*="clarity.ms"]')).toHaveCount(0);
    await page.getByRole('button', { name: 'Recusar' }).click();
    await page.waitForTimeout(400);
    expect(pedidos.filter((u) => u.includes('clarity.ms'))).toEqual([]);
    await context.close();
    // Aceitar: o carregador oficial, com o ID, e o consentimento só de medição.
    ({ context, page, pedidos } = await novaPagina(browser));
    await abrir(page);
    const resp = await page.request.get(WP + '/');
    const csp = resp.headers()['content-security-policy-report-only'] || resp.headers()['content-security-policy'] || '';
    expect(csp).toContain('https://www.clarity.ms');
    expect(csp).toContain('https://c.clarity.ms');
    await page.getByRole('button', { name: 'Aceitar' }).click();
    await expect.poll(() => pedidos.some((u) => u.includes('clarity.ms/tag/abcde12345'))).toBe(true);
    const consent = await page.evaluate(() => Array.from((window.clarity && window.clarity.q) || []).map((a) => Array.from(a)).find((a) => a[0] === 'consentv2'));
    expect(consent && consent[1]).toMatchObject({ analytics_Storage: 'granted', ad_Storage: 'denied' });
    await context.close();
  } finally {
    wp(['option', 'update', 'ieadc_info', JSON.stringify(atual), '--format=json']);
  }
});

test('eventos por dataLayer só depois do consentimento e sem dado pessoal', async ({ browser }) => {
  const { context, page } = await novaPagina(browser);
  await abrir(page);
  await page.evaluate(() => { document.addEventListener('click', (e) => { if (e.target.closest('a')) e.preventDefault(); }, false); });
  await page.getByRole('button', { name: 'Aceitar' }).click();
  const tel = page.locator('footer a[href^="tel:"]').first();
  await tel.scrollIntoViewIfNeeded();
  await tel.click();
  const wa = page.locator('.ieadc-whats a');
  if (await wa.count()) { await wa.evaluate((el) => el.click()); }
  const eventos = await page.evaluate(() => Array.from(window.dataLayer).filter((x) => x && x.event && x.event.indexOf('ieadc_') === 0));
  expect(eventos.map((e) => e.event)).toContain('ieadc_clique_telefone');
  if (await wa.count()) expect(eventos.map((e) => e.event)).toContain('ieadc_clique_whatsapp');
  // Nenhum parâmetro com número, e-mail ou texto digitado.
  expect(JSON.stringify(eventos)).not.toMatch(/\d{4}-?\d{4}|@|tel:|wa\.me/);
  await context.close();
});

test('sem ID de medição não existe aviso, JSON nem script de medição', async ({ browser }) => {
  const atual = JSON.parse(wp(['option', 'get', 'ieadc_info', '--format=json']));
  const vazio = Object.assign({}, atual); delete vazio.gtm_id;
  wp(['option', 'update', 'ieadc_info', JSON.stringify(vazio), '--format=json']);
  try {
    const { context, page } = await novaPagina(browser);
    await page.goto(WP + '/', { waitUntil: 'load' });
    expect(await page.locator('#ieadc-cookies, #ieadc-medicao, script[src*="medicao.js"]').count()).toBe(0);
    expect(await page.evaluate(() => typeof window.dataLayer)).toBe('undefined');
    await context.close();
  } finally {
    wp(['option', 'update', 'ieadc_info', JSON.stringify(atual), '--format=json']);
  }
});

for (const largura of [390, 1440]) {
  test(`axe: o aviso aberto e a página não têm violações (${largura} px)`, async ({ browser }) => {
    const { context, page } = await novaPagina(browser, largura);
    await abrir(page);
    await expect(page.locator('#ieadc-cookies')).toBeVisible();
    const r = await new AxeBuilder({ page }).withTags(TAGS).analyze();
    const msg = r.violations.map((v) => `[${v.impact}] ${v.id}: ${v.nodes.map((n) => n.target.join(' ')).join(', ')}`).join('\n');
    expect(r.violations, msg).toEqual([]);
    // O aviso não cobre a página inteira: deixa ao menos metade da tela livre no celular.
    const altura = await page.locator('#ieadc-cookies').evaluate((el) => el.getBoundingClientRect().height);
    expect(altura).toBeLessThan(800 * 0.5);
    await context.close();
  });
}

test('teclado: o foco vai para o aviso quando ele aparece, sem armadilha de foco, e a política reabre a escolha', async ({ browser }) => {
  const { context, page } = await novaPagina(browser);
  await abrir(page);
  // O aviso fica no fim do HTML (caixa de diálogo não modal) e recebe o foco ao aparecer.
  await expect.poll(() => page.evaluate(() => document.activeElement && document.activeElement.id)).toBe('ieadc-cookies');
  await page.keyboard.press('Tab');
  expect(await page.evaluate(() => document.activeElement.closest('#ieadc-cookies') !== null)).toBe(true); // link da política ou botão
  // Sem armadilha: poucos Tabs depois o foco já saiu do aviso (e Shift+Tab volta para a página).
  for (let i = 0; i < 12; i++) await page.keyboard.press('Tab');
  expect(await page.evaluate(() => document.activeElement.closest('#ieadc-cookies') === null)).toBe(true);
  // Escolhe e reabre pela política de privacidade.
  await page.getByRole('button', { name: 'Recusar' }).click();
  await page.goto(WP + '/politica-de-privacidade/', { waitUntil: 'load' });
  const mudar = page.getByRole('button', { name: /Mudar minha escolha de cookies/i });
  await expect(mudar).toBeVisible();
  await mudar.click();
  await expect(page.locator('#ieadc-cookies')).toBeVisible();
  expect(await page.evaluate(() => document.activeElement.closest('#ieadc-cookies') !== null)).toBe(true);
  await page.getByRole('button', { name: 'Aceitar' }).click();
  await expect(page.locator('#ieadc-cookies')).toBeHidden();
  expect(await page.evaluate(() => document.activeElement && document.activeElement.textContent.trim())).toMatch(/Mudar minha escolha/i); // o foco volta a quem abriu
  await context.close();
});
