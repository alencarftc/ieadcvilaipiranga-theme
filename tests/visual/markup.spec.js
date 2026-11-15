// Compara, seção por seção, o HTML do WordPress com o do protótipo (docs/specs/mapa-secoes.json).
// Falha mostrando o diff (- protótipo, + WordPress) de cada seção diferente.
const { test, expect } = require('@playwright/test');
const { MAPA, MINIS, WP, paginas, urlWp, urlProto } = require('./lib/paginas');
const { serializarSecoes } = require('./lib/normalizar');
const { diffLinhas } = require('./lib/diff');

// HTML como o servidor entregou: sem o JavaScript da página (selo "Próximo", carrosséis…).
test.use({ javaScriptEnabled: false });

async function secoes(page, url, base) {
  const resp = await page.goto(url, { waitUntil: 'domcontentloaded' });
  expect(resp && resp.status(), `${url} respondeu`).toBe(200);
  const s = await page.evaluate(serializarSecoes, { minis: MINIS, base });
  expect(s, `${url} tem <main>`).not.toBeNull();
  return s;
}

for (const pagina of paginas()) {
  test(`markup: ${pagina}`, async ({ page }) => {
    const proto = await secoes(page, urlProto(pagina), '');
    const wp = await secoes(page, urlWp(pagina), WP);
    const nomes = MAPA[pagina];
    const total = Math.max(proto.length, wp.length);
    const erros = [];
    for (let i = 0; i < total; i++) {
      const p = proto[i], w = wp[i];
      const nome = `${i + 1}. ${nomes[i] || '(fora do mapa)'}`;
      if (!p) { erros.push(`### ${nome}: sobra no WordPress <${w.tag} class="${w.classe}">`); continue; }
      if (!w) { erros.push(`### ${nome}: falta no WordPress <${p.tag} class="${p.classe}">`); continue; }
      if (p.html !== w.html) erros.push(`### ${nome}\n${diffLinhas(p.html, w.html)}`);
    }
    expect(erros.length, `${pagina}: ${erros.length} seção(ões) diferente(s) (- protótipo, + WordPress)\n\n${erros.join('\n\n')}\n`).toBe(0);
  });
}
