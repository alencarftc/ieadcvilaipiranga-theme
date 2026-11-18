// Gera assets/css/base-blocksy.css: a parte do main.min.css do Blocksy que as páginas do tema de fato usam (fase 6).
//
// O Blocksy entrega um main.min.css de ~94 KB (18 KB comprimido) e o Lighthouse mede 95% dele sem uso nas páginas do protótipo: sobram o reset
// (margens, box-sizing, imagens, tabelas, botões, formulários) e pouca coisa mais. Ele bloqueava a renderização; a cópia reduzida entra no começo do
// CSS do tema (inc/desempenho.php) e o arquivo original deixa de ser pedido nas páginas do modelo "larga".
//
// Como funciona: abre as 28 páginas e os artigos a 390, 900 e 1440 px com a cobertura de CSS do Chrome ligada, junta o que foi usado e acrescenta toda
// regra "genérica" (seletores só de elemento e estado, como `a:focus-visible`, que a cobertura não pega sem foco). Não toca no Blocksy.
//
// Uso (site local no ar em http://localhost:8080, com o Blocksy ainda sendo pedido nas páginas: rode antes de ligar o corte):
//   IEADC_CHROMIUM=<headless_shell.exe> node tests/lighthouse/reduzir-blocksy.js [saida.css]
// Depois confira com ./bin/testar.sh visual e ./bin/testar.sh a11y (precisam continuar verdes).
const fs = require('fs');
const path = require('path');
const { chromium } = require('../../node_modules/playwright');
const { paginas, urlWp } = require('../visual/lib/paginas');

const SAIDA = process.argv[2] || path.join(__dirname, '..', '..', 'wp-content', 'themes', 'ieadc-vila-ipiranga', 'assets', 'css', 'base-blocksy.css');
const EXTRAS = ['/alcancar-curitiba/', '/ate-que-ele-venha/', '/orai-uns-pelos-outros/'];
const LARGURAS = [390, 900, 1440];

/** Quebra o CSS minificado em regras (com o início e o fim de cada uma) e at-rules com regras dentro. Ignora chaves dentro de strings e url(). */
function regras(css, ini = 0, fim = css.length) {
  const lista = [];
  let i = ini;
  while (i < fim) {
    while (i < fim && /\s/.test(css[i])) i++;
    if (i >= fim) break;
    const comeco = i;
    let prof = 0, aspas = null, achou = false;
    while (i < fim) {
      const c = css[i];
      if (aspas) {
        if (c === '\\') i++;
        else if (c === aspas) aspas = null;
      } else if (c === '"' || c === "'") aspas = c;
      else if (c === '(' && css.startsWith('url(', i - 3)) {
        const f = css.indexOf(')', i);
        i = f < 0 ? fim : f;
      } else if (c === '{') prof++;
      else if (c === '}') {
        prof--;
        if (prof === 0) { achou = true; i++; break; }
      } else if (c === ';' && prof === 0) { achou = true; i++; break; }
      i++;
    }
    if (!achou && prof !== 0) break;
    const texto = css.slice(comeco, i);
    const abre = texto.indexOf('{');
    const cabeca = abre < 0 ? texto : texto.slice(0, abre);
    const r = { comeco, fim: i, cabeca: cabeca.trim(), texto };
    if (cabeca.trim().startsWith('@') && /^@(media|supports|layer|container)/.test(cabeca.trim()) && abre >= 0) {
      r.filhos = regras(css, comeco + abre + 1, i - 1);
    }
    lista.push(r);
  }
  return lista;
}

/** Seletor "genérico": só elementos, `*`, `:root`, pseudoclasses e pseudoelementos; sem classe, id nem atributo do Blocksy. */
function generico(seletor) {
  return !/[.#]/.test(seletor) && !/\[data-|\[class|\[id/.test(seletor);
}

function filtra(lista, usado) {
  let saida = '';
  for (const r of lista) {
    if (r.filhos) {
      const dentro = filtra(r.filhos, usado);
      if (dentro) saida += r.cabeca + '{' + dentro + '}';
      continue;
    }
    if (r.cabeca.startsWith('@')) {
      // @keyframes, @font-face... só se a cobertura marcou
      if (usado(r.comeco, r.fim)) saida += r.texto;
      continue;
    }
    const seletores = r.cabeca.split(',').map((s) => s.trim());
    if (usado(r.comeco, r.fim) || seletores.every(generico)) saida += r.texto;
  }
  return saida;
}

(async () => {
  const raiz = urlWp('inicio').replace(/\/$/, '');
  const urls = [...new Set([...paginas().map((p) => urlWp(p)), ...EXTRAS.map((c) => raiz + c)])];
  const b = await chromium.launch({ executablePath: process.env.IEADC_CHROMIUM, args: ['--no-sandbox'] });
  let texto = null;
  const marca = [];
  for (const w of LARGURAS) {
    const ctx = await b.newContext({ viewport: { width: w, height: 900 } });
    const page = await ctx.newPage();
    for (const url of urls) {
      await page.coverage.startCSSCoverage();
      await page.goto(url, { waitUntil: 'load' });
      await page.evaluate(async () => {
        for (let y = 0; y < document.body.scrollHeight; y += 700) { scrollTo(0, y); await new Promise((r) => setTimeout(r, 20)); }
      });
      const cov = await page.coverage.stopCSSCoverage();
      const e = cov.find((x) => /blocksy.*main\.min\.css/.test(x.url));
      if (!e) throw new Error('main.min.css do Blocksy não foi pedido em ' + url + ' (rode antes de ligar o corte)');
      texto = e.text;
      marca.push(e.ranges);
    }
    await ctx.close();
  }
  await b.close();
  const usado = new Uint8Array(texto.length);
  for (const rs of marca) for (const r of rs) usado.fill(1, r.start, r.end);
  const foiUsado = (ini, fim) => {
    for (let i = ini; i < fim; i++) if (usado[i]) return true;
    return false;
  };
  const css = filtra(regras(texto), foiUsado);
  const cab = '/* Reset do Blocksy usado pelas páginas do tema (cópia reduzida do main.min.css do Blocksy, gerada por tests/lighthouse/reduzir-blocksy.js a partir da cobertura de CSS das ' + urls.length + ' páginas a ' + LARGURAS.join(', ') + ' px). Vem antes do resto do CSS, como o arquivo original vinha. */\n';
  fs.writeFileSync(SAIDA, cab + css + '\n');
  console.log(`main.min.css ${texto.length} bytes -> ${css.length} bytes (${Math.round((100 * css.length) / texto.length)}%) em ${SAIDA}`);
})();
