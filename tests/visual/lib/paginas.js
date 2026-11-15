// Páginas do teste e endereços no WordPress e no protótipo.
const fs = require('fs');
const path = require('path');

const RAIZ = path.resolve(__dirname, '..', '..', '..');
const MAPA = JSON.parse(fs.readFileSync(path.join(RAIZ, 'docs', 'specs', 'mapa-secoes.json'), 'utf8'));
const WP = (process.env.WP_URL || 'http://localhost:8080').replace(/\/$/, '');
const PROTO = (process.env.PROTO_URL || 'http://localhost:8090').replace(/\/$/, '');

const MINIS = ['circulo-de-oracao', 'escola-biblica-dominical', 'ensinai', 'familia',
  'missao-e-evangelismo', 'mocidade', 'criancas'];

/** Endereço no WordPress de uma página do protótipo (mesma regra de scripts/importar_prototipo.py). */
function endereco(nome) {
  if (nome === 'inicio') return '/';
  if (nome.startsWith('ministerios-') && MINIS.includes(nome.slice(12))) return `/ministerios/${nome.slice(12)}/`;
  if (nome.startsWith('artigo-')) return `/${nome.slice(7)}/`;
  return `/${nome}/`;
}

/** Páginas a testar: todas do mapa ou as filtradas por IEADC_PAGINAS (separadas por vírgula). */
function paginas() {
  const todas = Object.keys(MAPA).sort();
  const filtro = (process.env.IEADC_PAGINAS || '').split(',').map((s) => s.trim()).filter(Boolean);
  if (!filtro.length) return todas;
  const desconhecidas = filtro.filter((p) => !MAPA[p]);
  if (desconhecidas.length) throw new Error(`Página fora de mapa-secoes.json: ${desconhecidas.join(', ')}`);
  return filtro;
}

module.exports = {
  RAIZ, MAPA, MINIS, WP, PROTO, endereco, paginas,
  urlWp: (nome) => WP + endereco(nome),
  urlProto: (nome) => `${PROTO}/${nome}.html`,
};
