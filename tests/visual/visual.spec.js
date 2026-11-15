// Screenshot da página inteira no WordPress e no protótipo (1440, 900 e 390 px), comparados com pixelmatch.
// Imagens em tests/visual/saida/<pagina>-<largura>-{wp,proto,diff}.png.
const { test, expect } = require('@playwright/test');
const fs = require('fs');
const path = require('path');
const { PNG } = require('pngjs');
const pixelmatch = require('pixelmatch');
const { paginas, urlWp, urlProto } = require('./lib/paginas');
const { abrir } = require('./lib/preparar');

const SAIDA = path.join(__dirname, 'saida');
const LARGURAS = { 1440: 1.0, 900: 1.5, 390: 1.0 }; // largura → diferença máxima em %
const ALTURA_JANELA = 900;

/** Coloca a imagem numa tela maior (fundo magenta: sobra de altura conta como diferença). */
function ampliar(png, w, h) {
  if (png.width === w && png.height === h) return png;
  const novo = new PNG({ width: w, height: h });
  for (let i = 0; i < novo.data.length; i += 4) novo.data.set([255, 0, 255, 255], i);
  PNG.bitblt(png, novo, 0, 0, png.width, png.height, 0, 0);
  return novo;
}

async function foto(page, url) {
  await abrir(page, url);
  return PNG.sync.read(await page.screenshot({ fullPage: true, animations: 'disabled' }));
}

fs.mkdirSync(SAIDA, { recursive: true });

for (const pagina of paginas()) {
  for (const [largura, limite] of Object.entries(LARGURAS)) {
    test(`visual: ${pagina} @${largura}`, async ({ page }) => {
      await page.setViewportSize({ width: Number(largura), height: ALTURA_JANELA });
      const proto = await foto(page, urlProto(pagina));
      const wp = await foto(page, urlWp(pagina));
      const w = Math.max(proto.width, wp.width), h = Math.max(proto.height, wp.height);
      const a = ampliar(proto, w, h), b = ampliar(wp, w, h);
      const diff = new PNG({ width: w, height: h });
      const n = pixelmatch(a.data, b.data, diff.data, w, h, { threshold: 0.1 });
      const pct = (100 * n) / (w * h);
      const base = path.join(SAIDA, `${pagina}-${largura}`);
      fs.writeFileSync(`${base}-proto.png`, PNG.sync.write(proto));
      fs.writeFileSync(`${base}-wp.png`, PNG.sync.write(wp));
      fs.writeFileSync(`${base}-diff.png`, PNG.sync.write(diff));
      test.info().annotations.push({ type: 'diferença', description: `${pct.toFixed(3)}%` });
      console.log(`${pagina} @${largura}: ${pct.toFixed(3)}% (alturas: protótipo ${proto.height}, WordPress ${wp.height})`);
      expect(pct, `${pagina} @${largura}: ${pct.toFixed(3)}% de pixels diferentes (limite ${limite}%). Veja ${base}-diff.png`).toBeLessThanOrEqual(limite);
    });
  }
}
