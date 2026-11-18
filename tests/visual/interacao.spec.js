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

// Fase 7 (UX): galeria com foto ampliada, perguntas em sanfona, mapa do Google sob demanda, links em nova aba e botão final do cabeçalho.
test.describe('galeria: foto ampliada', () => {
  test('abre ao clicar, passa com as setas (tela e teclado) e fecha com Esc devolvendo o foco', async ({ page }) => {
    await page.goto(WP + '/galeria/');
    const fotos = page.locator('.fotos > div[role="button"]');
    const total = await fotos.count();
    expect(total).toBeGreaterThan(30);
    const janela = page.locator('dialog.lightbox');
    await expect(janela).not.toHaveAttribute('open', '');
    await fotos.nth(2).click();
    await expect(janela).toHaveAttribute('open', '');
    await expect(page.locator('.lb-cont')).toHaveText(`3 de ${total}`);
    await page.locator('.lb-prox').click();
    await expect(page.locator('.lb-cont')).toHaveText(`4 de ${total}`);
    await page.keyboard.press('ArrowRight');
    await expect(page.locator('.lb-cont')).toHaveText(`5 de ${total}`);
    await page.keyboard.press('ArrowLeft');
    await page.locator('.lb-ant').click();
    await expect(page.locator('.lb-cont')).toHaveText(`3 de ${total}`);
    await page.keyboard.press('Escape');
    await expect(janela).not.toHaveAttribute('open', '');
    await expect(fotos.nth(2)).toBeFocused();
  });
});

test.describe('perguntas frequentes em sanfona', () => {
  test('abre uma pergunta por vez e responde ao teclado', async ({ page }) => {
    await page.goto(WP + '/horarios-e-localizacao/');
    const itens = page.locator('details.faq-item');
    expect(await itens.count()).toBeGreaterThan(3);
    await expect(itens.nth(0)).not.toHaveAttribute('open', '');
    await itens.nth(0).locator('summary').click();
    await expect(itens.nth(0)).toHaveAttribute('open', '');
    await itens.nth(1).locator('summary').focus();
    await page.keyboard.press('Enter');
    await expect(itens.nth(1)).toHaveAttribute('open', '');
    await expect(itens.nth(0)).not.toHaveAttribute('open', ''); // uma por vez (details name="faq")
  });
});

test.describe('mapa do Google', () => {
  test('o iframe só entra depois do clique em "Ver o mapa do Google"', async ({ page }) => {
    await page.goto(WP + '/horarios-e-localizacao/');
    await expect(page.locator('.mapa iframe')).toHaveCount(0);
    await page.locator('.mapa-abrir').click();
    const quadro = page.locator('.mapa iframe');
    await expect(quadro).toHaveCount(1);
    await expect(quadro).toHaveAttribute('src', /^https:\/\/maps\.google\.com\/maps\?cid=\d+&hl=pt-BR&output=embed$/);
    await expect(quadro).toHaveAttribute('title', /Mapa do Google/);
    await expect(page.locator('.mapa-abrir')).toBeHidden();
  });
});

test.describe('links e cabeçalho', () => {
  test('link que leva a outro site abre em nova aba (rel noopener noreferrer); link do site, não', async ({ page }) => {
    await page.goto(WP + '/horarios-e-localizacao/');
    const externos = page.locator('main a[href^="http"]:not([href*="localhost"]):not([href*="127.0.0.1"])');
    expect(await externos.count()).toBeGreaterThan(2);
    for (const a of await externos.all()) {
      await expect(a).toHaveAttribute('target', '_blank');
      await expect(a).toHaveAttribute('rel', /noopener/);
      await expect(a).toHaveAttribute('rel', /noreferrer/);
    }
    const interno = page.locator('.nav a[href*="contato"]').first();
    await expect(interno).not.toHaveAttribute('target', '_blank');
  });

  test('"Aceitar Jesus" é o último botão do cabeçalho', async ({ page }) => {
    await page.goto(WP + '/');
    const ultimo = page.locator('.nav > a').last();
    await expect(ultimo).toHaveClass(/jesus/);
    await expect(ultimo).toHaveText(/Aceitar Jesus/);
    await expect(page.locator('.nav > ul a', { hasText: 'Aceitar Jesus' })).toHaveCount(0);
  });
});
