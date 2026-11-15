// Deixa a página pronta para screenshot: relógio fixo, faixa do protótipo escondida,
// animações desligadas, imagens carregadas e fontes prontas.
const RELOGIO = new Date('2026-10-06T10:00:00-03:00'); // data em que o protótipo foi gerado

const CSS_ESTAVEL = `
  .proto { display: none !important; }
  /* avisos de envio: o protótipo mostra exemplos fixos; o WordPress só depois de enviar */
  form .msg { display: none !important; }
  *, *::before, *::after {
    animation-duration: 0s !important; animation-delay: 0s !important;
    transition-duration: 0s !important; transition-delay: 0s !important;
    caret-color: transparent !important; scroll-behavior: auto !important;
  }
`;

async function abrir(page, url) {
  await page.clock.setFixedTime(RELOGIO);
  const resp = await page.goto(url, { waitUntil: 'load' });
  if (!resp || resp.status() !== 200) throw new Error(`${url} respondeu ${resp && resp.status()}`);
  await page.addStyleTag({ content: CSS_ESTAVEL });
  // Rola até o fim para disparar imagens com loading="lazy", e volta ao topo.
  await page.evaluate(async () => {
    for (let y = 0; y < document.body.scrollHeight; y += window.innerHeight) {
      window.scrollTo(0, y);
      await new Promise((r) => setTimeout(r, 30));
    }
    window.scrollTo(0, 0);
    document.querySelectorAll('img').forEach((i) => { i.loading = 'eager'; });
  });
  await page.waitForLoadState('networkidle');
  await page.evaluate(async () => {
    await document.fonts.ready;
    await Promise.all([...document.images].map((i) => (i.complete ? null : new Promise((r) => { i.onload = i.onerror = r; }))));
    // Fundos em CSS: espera cada background-image decodificar.
    const urls = new Set();
    document.querySelectorAll('*').forEach((el) => {
      const m = getComputedStyle(el).backgroundImage.match(/url\("?([^")]+)"?\)/);
      if (m) urls.add(m[1]);
    });
    await Promise.all([...urls].map((u) => new Promise((r) => { const i = new Image(); i.onload = i.onerror = r; i.src = u; })));
  });
}

module.exports = { abrir, RELOGIO };
