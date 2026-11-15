// Comportamento no WordPress: menu do celular, submenus por teclado, carrossel, formulários e selo "Próximo".
const { test, expect } = require('@playwright/test');
const { WP } = require('./lib/paginas');
const { RELOGIO } = require('./lib/preparar');

const CELULAR = { width: 390, height: 844 };

test.describe('menu do celular', () => {
  test.use({ viewport: CELULAR });

  test('abre, fecha com o botão e com Esc', async ({ page }) => {
    await page.goto(WP + '/');
    const header = page.locator('.header');
    const botao = header.locator('.menu-btn');
    await expect(botao).toBeVisible();
    await expect(header).not.toHaveClass(/aberto/);
    await expect(botao).toHaveAttribute('aria-expanded', 'false');
    await botao.click();
    await expect(header).toHaveClass(/aberto/);
    await expect(botao).toHaveAttribute('aria-expanded', 'true');
    await expect(botao).toHaveText('FECHAR');
    await expect(header.locator('.nav a', { hasText: 'Aceitar Jesus' }).first()).toBeVisible();
    await botao.click();
    await expect(header).not.toHaveClass(/aberto/);
    await botao.click();
    await expect(header).toHaveClass(/aberto/);
    await page.keyboard.press('Escape');
    await expect(header).not.toHaveClass(/aberto/);
    await expect(botao).toHaveText('MENU');
  });
});

test.describe('menu do computador', () => {
  test.use({ viewport: { width: 1440, height: 900 } });

  test('submenu abre por teclado (Tab) e mostra os itens', async ({ page }) => {
    await page.goto(WP + '/');
    const grupo = page.locator('.nav li', { has: page.locator('a.grp', { hasText: 'A Igreja' }) }).first();
    const sub = grupo.locator('> .sub');
    await expect(sub).toHaveCSS('visibility', 'hidden');
    await grupo.locator('a.grp').focus();
    await expect(sub).toHaveCSS('visibility', 'visible');
    await expect(sub.locator('a', { hasText: 'Liderança' })).toBeVisible();
    await page.keyboard.press('Tab'); // primeiro item do submenu
    await expect(sub.locator('a').first()).toBeFocused();
    await expect(sub).toHaveCSS('visibility', 'visible');
  });
});

test.describe('carrossel', () => {
  test('botões e pontos andam entre os slides', async ({ page }) => {
    await page.goto(WP + '/culto-de-oracao/');
    const car = page.locator('[data-carrossel]').first();
    await car.scrollIntoViewIfNeeded();
    const pista = car.locator('.pista');
    const sec = page.locator('section', { has: car }).first();
    const pontos = car.locator('.pontos button');
    expect(await pontos.count()).toBeGreaterThan(1);
    await expect(pontos.first()).toHaveClass(/on/);
    await sec.locator('.car-prox').first().click();
    await expect.poll(() => pista.evaluate((p) => p.scrollLeft)).toBeGreaterThan(0);
    await expect(pontos.nth(1)).toHaveClass(/on/);
    await pontos.first().click();
    await expect.poll(() => pista.evaluate((p) => Math.round(p.scrollLeft))).toBe(0);
    await expect(pontos.first()).toHaveClass(/on/);
    await sec.locator('.car-ant').first().click(); // volta ao último (dá a volta)
    await expect(pontos.last()).toHaveClass(/on/);
  });
});

test.describe('formulários', () => {
  test('pedido de oração: envia e mostra o aviso de sucesso', async ({ page }) => {
    await page.goto(WP + '/pedidos-de-oracao/');
    const form = page.locator('form#pedido');
    await form.locator('#p-nome').fill('Teste automático');
    await form.locator('#p-pedido').fill('Pedido de teste do M1.');
    await form.locator('#p-ok').check();
    await form.locator('button[type=submit]').click();
    await expect(page).toHaveURL(/ieadc_pedido=ok/);
    await expect(page.locator('form#pedido .msg:not(.erro)')).toContainText('Recebemos o seu pedido');
  });

  test('pedido de oração: sem autorização o navegador bloqueia o envio', async ({ page }) => {
    await page.goto(WP + '/pedidos-de-oracao/');
    const form = page.locator('form#pedido');
    await form.locator('#p-nome').fill('Teste');
    await form.locator('#p-pedido').fill('Pedido.');
    await form.locator('button[type=submit]').click();
    await expect(page).not.toHaveURL(/ieadc_pedido=/);
    expect(await form.locator('#p-ok').evaluate((e) => e.validity.valueMissing)).toBe(true);
  });

  test('pedido de oração: o servidor devolve o aviso de erro', async ({ page }) => {
    await page.goto(WP + '/pedidos-de-oracao/?ieadc_pedido=erro');
    await expect(page.locator('form#pedido .msg.erro')).toContainText('Não foi possível enviar');
  });

  test('fale conosco: envia e mostra o aviso de sucesso', async ({ page }) => {
    await page.goto(WP + '/contato/');
    const form = page.locator('form#contato');
    await form.locator('#c-nome').fill('Teste automático');
    await form.locator('#c-contato').fill('teste@example.com');
    await form.locator('#c-msg').fill('Mensagem de teste do M1.');
    await form.locator('button[type=submit]').click();
    await expect(page).toHaveURL(/ieadc_contato=ok/);
    await expect(page.locator('form#contato .msg:not(.erro)')).toContainText('Mensagem enviada');
  });
});

test.describe('selo "Próximo" (06/10/2026, terça, 10h em Curitiba)', () => {
  test('marca o culto de ensino da quarta-feira', async ({ page }) => {
    await page.clock.setFixedTime(RELOGIO);
    await page.goto(WP + '/');
    const selos = page.locator('.tag', { hasText: 'Próximo' });
    await expect(selos).toHaveCount(1);
    const cartao = page.locator('.dia.hoje');
    await expect(cartao).toHaveCount(1);
    await expect(cartao).toContainText('QUA');
    await expect(cartao).toContainText('Culto de Ensino');
  });
});
