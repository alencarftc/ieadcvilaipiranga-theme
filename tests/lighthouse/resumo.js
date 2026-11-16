// Resume os relatórios JSON do Lighthouse (um por página e dispositivo) numa tabela e confere a nota mínima.
// Uso: node tests/lighthouse/resumo.js <pasta-com-os-json> <nota-minima>
// Os arquivos se chamam <dispositivo>__<pagina>.json (veja bin/lighthouse.sh). Sai com 1 se alguma nota ficar abaixo do mínimo.
const fs = require('fs');
const path = require('path');

const [pasta, minimoTxt] = process.argv.slice(2);
const minimo = Number(minimoTxt || 100);
const CATS = [['performance', 'Desemp.'], ['accessibility', 'Acessib.'], ['best-practices', 'Prát.'], ['seo', 'SEO']];

const arquivos = fs.readdirSync(pasta).filter((f) => f.endsWith('.json')).sort();
if (!arquivos.length) { console.error('Nenhum relatório em ' + pasta); process.exit(2); }

const pad = (s, n) => String(s).padEnd(n);
const linhas = [];
const falhas = [];
for (const arq of arquivos) {
  const [disp, ...resto] = arq.replace(/\.json$/, '').split('__');
  const r = JSON.parse(fs.readFileSync(path.join(pasta, arq), 'utf8'));
  const nota = Object.fromEntries(CATS.map(([id]) => [id, Math.round(((r.categories[id] || {}).score ?? 0) * 100)]));
  const a = r.audits;
  const lcp = a['largest-contentful-paint'].numericValue / 1000;
  const tbt = a['total-blocking-time'].numericValue;
  const cls = a['cumulative-layout-shift'].numericValue;
  linhas.push({ disp, pagina: resto.join('__'), nota, lcp, tbt, cls });
  for (const [id, rotulo] of CATS) {
    if (nota[id] >= minimo) continue;
    const ruins = (r.categories[id].auditRefs || [])
      .map((ref) => a[ref.id])
      .filter((x) => x && x.score !== null && x.score < 1 && !['informative', 'manual', 'notApplicable'].includes(x.scoreDisplayMode));
    falhas.push({ disp, pagina: resto.join('__'), rotulo, nota: nota[id], ruins });
  }
}

console.log(`\n${pad('dispositivo', 11)} ${pad('página', 36)} ${pad('Desemp.', 8)} ${pad('Acessib.', 9)} ${pad('Prát.', 6)} ${pad('SEO', 5)} ${pad('LCP', 7)} ${pad('TBT', 7)} CLS`);
for (const l of linhas) {
  const n = l.nota;
  console.log(`${pad(l.disp, 11)} ${pad(l.pagina, 36)} ${pad(n.performance, 8)} ${pad(n.accessibility, 9)} ${pad(n['best-practices'], 6)} ${pad(n.seo, 5)} ${pad(l.lcp.toFixed(1) + ' s', 7)} ${pad(Math.round(l.tbt) + ' ms', 7)} ${l.cls.toFixed(3)}`);
}
const menor = {};
for (const l of linhas) for (const [id] of CATS) menor[id] = Math.min(menor[id] ?? 100, l.nota[id]);
console.log(`\nMenor nota: desempenho ${menor.performance}, acessibilidade ${menor.accessibility}, boas práticas ${menor['best-practices']}, SEO ${menor.seo} (mínimo ${minimo}).`);

if (falhas.length) {
  console.log(`\n${falhas.length} nota(s) abaixo de ${minimo}. Auditorias que falharam:`);
  for (const f of falhas) {
    console.log(`\n- ${f.disp} · ${f.pagina} · ${f.rotulo} ${f.nota}`);
    for (const x of f.ruins) console.log(`    ${x.id}: ${x.title}${x.displayValue ? ' (' + x.displayValue + ')' : ''} [nota ${x.score}]`);
  }
  process.exit(1);
}
console.log('Todas as notas atingiram o mínimo.');
