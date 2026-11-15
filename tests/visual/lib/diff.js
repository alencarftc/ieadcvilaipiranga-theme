// Diff de linhas (LCS) no formato unificado, para mostrar a diferença de uma seção.
function diffLinhas(a, b, contexto = 3) {
  const x = a.split('\n'), y = b.split('\n');
  const n = x.length, m = y.length;
  if (n * m > 4e7) return `(seção grande demais para diff: ${n} × ${m} linhas)`;
  const t = Array.from({ length: n + 1 }, () => new Uint32Array(m + 1));
  for (let i = n - 1; i >= 0; i--) for (let j = m - 1; j >= 0; j--)
    t[i][j] = x[i] === y[j] ? t[i + 1][j + 1] + 1 : Math.max(t[i + 1][j], t[i][j + 1]);
  const ops = [];
  let i = 0, j = 0;
  while (i < n || j < m) {
    if (i < n && j < m && x[i] === y[j]) { ops.push([' ', x[i]]); i++; j++; }
    else if (j < m && (i === n || t[i][j + 1] >= t[i + 1][j])) { ops.push(['+', y[j]]); j++; }
    else { ops.push(['-', x[i]]); i++; }
  }
  const mudou = ops.map((o) => o[0] !== ' ');
  const saida = [];
  let pulou = false;
  ops.forEach((o, k) => {
    const perto = mudou.slice(Math.max(0, k - contexto), k + contexto + 1).some(Boolean);
    if (perto) { saida.push(`${o[0]} ${o[1]}`); pulou = false; }
    else if (!pulou) { saida.push('  …'); pulou = true; }
  });
  return saida.join('\n');
}

module.exports = { diffLinhas };
