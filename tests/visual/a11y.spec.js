// Acessibilidade (WCAG 2.1 nível AA) com axe-core e navegação por teclado. Rode com ./bin/testar.sh a11y.
// 1) axe nas 28 páginas, em 390, 900 e 1440 px (regras wcag2a, wcag2aa, wcag21a, wcag21aa, wcag22aa e best-practice);
//    a falha lista regra, impacto, seletor e trecho de cada ocorrência.
// 2) teclado: link "Pular para o conteúdo", ordem e foco visível, menu do celular, submenu do computador, carrossel.
// 3) prefers-reduced-motion: sem rolagem suave nem animação.
// Diferente do teste visual, aqui nada é congelado: a página é vista como a pessoa a vê (animações e rolagem suave ligadas).
const { test, expect } = require('@playwright/test');
const AxeBuilder = require('@axe-core/playwright').default;
const { paginas, urlWp, WP } = require('./lib/paginas');

const LARGURAS = [390, 900, 1440];
// wcag22aa entra por causa do tamanho do alvo de toque (24 px, regra target-size): o Lighthouse a cobra.
const TAGS = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'];

/** Abre a página e espera fontes e imagens (o contraste é medido sobre o que está desenhado). */
async function abrir(page, url) {
  const resp = await page.goto(url, { waitUntil: 'load' });
  if (!resp || resp.status() !== 200) throw new Error(`${url} respondeu ${resp && resp.status()}`);
  // Rola a página como uma pessoa faria: as fotos de fundo abaixo da primeira seção só carregam perto da tela (ajustes.css, "Fase 3").
  await page.evaluate(async () => {
    for (let y = 0; y < document.body.scrollHeight; y += window.innerHeight / 2) {
      window.scrollTo({ top: y, behavior: 'instant' }); // o html tem scroll-behavior:smooth: 'instant' evita rolagem pela metade
      await new Promise((r) => setTimeout(r, 25));
    }
    window.scrollTo({ top: 0, behavior: 'instant' });
  });
  await page.waitForLoadState('networkidle');
  await page.evaluate(async () => {
    await document.fonts.ready;
    await Promise.all([...document.images].map((i) => (i.complete ? null : new Promise((r) => { i.onload = i.onerror = r; }))));
  });
}

/** Texto legível das violações: regra, impacto, ajuda, e para cada ocorrência o seletor, o trecho e o motivo. */
function relatorio(violacoes) {
  const linhas = [];
  for (const v of violacoes) {
    linhas.push(`\n[${v.impact || 'sem impacto'}] ${v.id}: ${v.help} (${v.helpUrl})`);
    for (const n of v.nodes.slice(0, 6)) {
      linhas.push(`    seletor: ${n.target.join(' ')}`);
      linhas.push(`    trecho : ${n.html.replace(/\s+/g, ' ').slice(0, 160)}`);
      const motivo = (n.failureSummary || '').split('\n').slice(1, 3).map((s) => s.trim()).join(' | ');
      if (motivo) linhas.push(`    motivo : ${motivo.slice(0, 220)}`);
    }
    if (v.nodes.length > 6) linhas.push(`    … e mais ${v.nodes.length - 6} ocorrência(s)`);
  }
  return linhas.join('\n');
}

test.describe('axe (WCAG 2.1 AA e boas práticas)', () => {
  for (const pagina of paginas()) {
    for (const largura of LARGURAS) {
      test(`axe: ${pagina} @${largura}`, async ({ page }) => {
        await page.setViewportSize({ width: largura, height: 900 });
        await abrir(page, urlWp(pagina));
        const { violations } = await new AxeBuilder({ page }).withTags(TAGS).analyze();
        expect(violations, `${violations.length} regra(s) violada(s) em ${urlWp(pagina)} a ${largura}px:${relatorio(violations)}\n`).toEqual([]);
      });
    }
  }
});



// A agenda marca o "hoje" de um jeito diferente em cada data (o dia de hoje sem culto aparece em laranja na lista do celular, por exemplo), e os
// testes fixam 06/10/2026: estas datas cobrem o "hoje" vazio, fim de ano e mês sem evento.
test.describe('axe com outras datas na agenda', () => {
  for (const data of ['2026-11-16', '2026-12-24', '2027-01-10']) {
    for (const pagina of ['inicio', 'eventos', 'horarios-e-localizacao']) {
      for (const largura of [390, 900]) {
        test(`axe: ${pagina} @${largura} em ${data}`, async ({ page }) => {
          await page.setExtraHTTPHeaders({ 'X-Ieadc-Hoje': data });
          await page.setViewportSize({ width: largura, height: 900 });
          await abrir(page, urlWp(pagina));
          const { violations } = await new AxeBuilder({ page }).withTags(TAGS).analyze();
          expect(violations, `${violations.length} regra(s) violada(s) em ${urlWp(pagina)} a ${largura}px com a data ${data}:${relatorio(violations)}
`).toEqual([]);
        });
      }
    }
  }
});

test.describe('teclado', () => {
  test.use({ viewport: { width: 1440, height: 900 } });

  test('o primeiro Tab leva ao link "Pular para o conteúdo", visível, e ele vai ao conteúdo principal', async ({ page }) => {
    await abrir(page, WP + '/');
    await page.keyboard.press('Tab');
    const pular = page.locator('a.skip-link');
    await expect(pular).toBeFocused();
    // aparece com uma transição curta: espera ele chegar dentro da tela
    await expect.poll(async () => { const c = await pular.boundingBox(); return c ? Math.min(c.x, c.y) : -999; }, { message: 'o link de pular precisa aparecer na tela ao receber o foco' }).toBeGreaterThanOrEqual(0);
    const caixa = await pular.boundingBox();
    expect(caixa.width).toBeGreaterThan(20);
    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/#main$/);
    const alvo = await page.evaluate(() => { const m = document.getElementById('main'); return m ? m.tagName : null; });
    expect(alvo, 'o destino #main do link de pular precisa existir').toBe('MAIN');
    // o próximo Tab entra no conteúdo (depois do cabeçalho), não volta ao topo da página
    await page.keyboard.press('Tab');
    const dentro = await page.evaluate(() => !!document.activeElement.closest('main'));
    expect(dentro, 'depois de pular, o foco precisa seguir dentro de <main>').toBe(true);
  });

  for (const [nome, rota] of [['início', '/'], ['contato', '/contato/'], ['ministérios', '/ministerios/'], ['pedidos de oração', '/pedidos-de-oracao/']]) {
    test(`ordem e foco visível em ${nome}`, async ({ page }) => {
      await abrir(page, WP + rota);
      const falhas = [];
      let passos = 0;
      for (let i = 0; i < 60; i++) {
        await page.keyboard.press('Tab');
        const r = await page.evaluate(() => {
          const el = document.activeElement;
          if (!el || el === document.body) return null;
          const cs = getComputedStyle(el);
          const caixa = el.getBoundingClientRect();
          const contorno = cs.outlineStyle !== 'none' && parseFloat(cs.outlineWidth) > 0 && cs.outlineColor !== 'rgba(0, 0, 0, 0)';
          const sombra = cs.boxShadow !== 'none';
          const rotulo = (el.getAttribute('aria-label') || el.textContent || el.getAttribute('title') || '').trim();
          return {
            visivelFoco: contorno || sombra,
            oculto: cs.visibility === 'hidden' || cs.display === 'none' || (caixa.width === 0 && caixa.height === 0),
            semNome: !rotulo && !el.getAttribute('aria-labelledby') && !(el.labels && el.labels.length),
            tab: el.tabIndex,
            y: caixa.top + window.scrollY,
            descricao: el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + (el.className && typeof el.className === 'string' ? '.' + el.className.trim().split(/\s+/).join('.') : '') + ' "' + rotulo.replace(/\s+/g, ' ').slice(0, 40) + '"',
          };
        });
        if (!r) break;
        passos++;
        if (r.oculto) falhas.push(`foco em elemento invisível: ${r.descricao}`);
        if (!r.visivelFoco) falhas.push(`sem indicador de foco visível: ${r.descricao}`);
        if (r.semNome) falhas.push(`foco em controle sem nome: ${r.descricao}`);
        if (r.tab > 0) falhas.push(`tabindex positivo (quebra a ordem natural): ${r.descricao}`);
      }
      expect(passos, 'a página precisa ter vários controles alcançáveis por Tab').toBeGreaterThan(10);
      expect(falhas, falhas.join('\n')).toEqual([]);
    });
  }

  test('o submenu abre com o teclado e o foco fica visível dentro dele', async ({ page }) => {
    await abrir(page, WP + '/');
    const grupo = page.locator('.nav li', { has: page.locator('a.grp', { hasText: 'A Igreja' }) }).first();
    await grupo.locator('a.grp').focus();
    const sub = grupo.locator('> .sub');
    await expect(sub).toHaveCSS('visibility', 'visible');
    await expect(sub).toHaveCSS('opacity', '1'); // a transição do submenu (0,18 s) precisa acabar: um Tab no meio dela pula o submenu
    await page.keyboard.press('Tab');
    const primeiro = sub.locator('a').first();
    const onde = await page.evaluate(() => document.activeElement.outerHTML.slice(0, 90));
    await expect(primeiro, `depois do Tab o foco está em: ${onde}`).toBeFocused();
    const contorno = await primeiro.evaluate((el) => { const cs = getComputedStyle(el); return cs.outlineStyle !== 'none' && parseFloat(cs.outlineWidth) > 0; });
    expect(contorno, 'o item do submenu em foco precisa ter contorno').toBe(true);
  });
});

test.describe('menu do celular por teclado', () => {
  test.use({ viewport: { width: 390, height: 844 } });

  test('o botão MENU abre com Enter e Espaço, anuncia o estado e Esc devolve o foco ao botão', async ({ page }) => {
    await abrir(page, WP + '/');
    const botao = page.locator('.header .menu-btn');
    await botao.focus();
    await expect(botao).toHaveAttribute('aria-controls', 'nav');
    await expect(botao).toHaveAttribute('aria-expanded', 'false');
    await page.keyboard.press('Enter');
    await expect(botao).toHaveAttribute('aria-expanded', 'true');
    // dentro do menu aberto, o próximo Tab vai ao primeiro item, que precisa estar visível na tela
    await page.keyboard.press('Tab');
    const foco = await page.evaluate(() => {
      const el = document.activeElement;
      const caixa = el.getBoundingClientRect();
      return { dentroNav: !!el.closest('#nav'), visivel: caixa.width > 0 && caixa.height > 0 && caixa.top >= 0 && caixa.bottom <= window.innerHeight };
    });
    expect(foco.dentroNav, 'depois do botão, o Tab precisa entrar no menu aberto').toBe(true);
    expect(foco.visivel, 'o item do menu em foco precisa estar na tela').toBe(true);
    await page.keyboard.press('Escape');
    await expect(botao).toHaveAttribute('aria-expanded', 'false');
    await expect(botao).toBeFocused();
    await page.keyboard.press('Space');
    await expect(botao).toHaveAttribute('aria-expanded', 'true');
  });

  test('com o menu fechado, os links dele não recebem foco', async ({ page }) => {
    await abrir(page, WP + '/');
    const focaveis = await page.evaluate(() => [...document.querySelectorAll('#nav a')].filter((a) => {
      const cs = getComputedStyle(a);
      return cs.visibility !== 'hidden' && cs.display !== 'none' && a.getClientRects().length > 0;
    }).length);
    expect(focaveis, 'links do menu fechado não podem estar no fluxo do teclado').toBe(0);
  });
});

test.describe('carrossel por teclado', () => {
  test('setas e pontos têm nome, funcionam com Enter e nada gira sozinho', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await abrir(page, WP + '/culto-de-oracao/');
    const car = page.locator('[data-carrossel]').first();
    const sec = page.locator('section', { has: car }).first();
    const pista = car.locator('.pista');
    const botoes = sec.locator('.car-ant, .car-prox, .pontos button');
    const total = await botoes.count();
    expect(total).toBeGreaterThan(2);
    for (let i = 0; i < total; i++) {
      const b = botoes.nth(i);
      const nome = ((await b.getAttribute('aria-label')) || (await b.textContent()) || '').trim();
      expect(nome, `o botão ${i + 1} do carrossel precisa de nome acessível`).not.toBe('');
    }
    const antes = await pista.evaluate((p) => Math.round(p.scrollLeft));
    await sec.locator('.car-prox').first().focus();
    await page.keyboard.press('Enter');
    await expect.poll(() => pista.evaluate((p) => Math.round(p.scrollLeft))).toBeGreaterThan(antes);
    // espera a rolagem suave terminar (duas leituras iguais seguidas)
    let parado = -1;
    await expect.poll(async () => { const x = await pista.evaluate((p) => Math.round(p.scrollLeft)); const igual = x === parado; parado = x; return igual; }, { intervals: [250] }).toBe(true);
    // WCAG 2.2.2: conteúdo que se move sozinho precisa de pausa; este carrossel só anda a pedido da pessoa.
    await page.waitForTimeout(1500);
    expect(await pista.evaluate((p) => Math.round(p.scrollLeft)), 'o carrossel não pode girar sozinho (ou teria de ter botão de pausa)').toBe(parado);
  });
});

test.describe('prefers-reduced-motion', () => {
  test.use({ reducedMotion: 'reduce', viewport: { width: 390, height: 844 } });

  test('sem rolagem suave, sem animação e sem transição', async ({ page }) => {
    await abrir(page, WP + '/culto-de-oracao/');
    const r = await page.evaluate(() => {
      const duracoes = new Set();
      document.querySelectorAll('body *').forEach((el) => {
        const cs = getComputedStyle(el);
        if (cs.animationName !== 'none' && parseFloat(cs.animationDuration) > 0.05) duracoes.add(`animação ${cs.animationName} ${cs.animationDuration}`);
        if (cs.transitionProperty !== 'none' && cs.transitionProperty !== 'all' && cs.transitionDuration.split(',').some((d) => parseFloat(d) > 0.05)) duracoes.add(`transição ${cs.transitionProperty} ${cs.transitionDuration}`);
        if (cs.transitionProperty === 'all' && cs.transitionDuration.split(',').some((d) => parseFloat(d) > 0.05)) duracoes.add(`transição all ${cs.transitionDuration}`);
      });
      return { suave: getComputedStyle(document.documentElement).scrollBehavior, duracoes: [...duracoes] };
    });
    expect(r.suave, 'html não pode rolar com suavidade').toBe('auto');
    expect(r.duracoes, `movimento que sobrou: ${r.duracoes.join('; ')}`).toEqual([]);
  });

  test('o carrossel pula direto, sem rolagem animada', async ({ page }) => {
    await abrir(page, WP + '/culto-de-oracao/');
    const car = page.locator('[data-carrossel]').first();
    const pista = car.locator('.pista');
    const sec = page.locator('section', { has: car }).first();
    await sec.locator('.car-prox').first().click();
    // com rolagem animada a posição passaria por valores intermediários; aqui chega ao destino na hora
    const x = await pista.evaluate((p) => p.scrollLeft);
    await page.waitForTimeout(80);
    const depois = await pista.evaluate((p) => p.scrollLeft);
    expect(depois, 'a rolagem do carrossel deve ser instantânea com movimento reduzido').toBe(x);
    expect(depois).toBeGreaterThan(0);
  });
});
