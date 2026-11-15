// Serve o protótipo (prototipo/) em http://localhost:8090; img/* vem de content/media/.
// Uso: node tests/visual/servidor-prototipo.js [porta]
const http = require('http');
const fs = require('fs');
const path = require('path');

const RAIZ = path.resolve(__dirname, '..', '..');
const PROTO = path.join(RAIZ, 'prototipo');
const MIDIA = path.join(RAIZ, 'content', 'media');
// O logo não está em content/media: vem do tema (assets/img).
const TEMA_IMG = path.join(RAIZ, 'wp-content', 'themes', 'ieadc-vila-ipiranga', 'assets', 'img');
const PORTA = Number(process.argv[2] || process.env.PROTO_PORTA || 8090);
const TIPOS = {
  '.html': 'text/html; charset=utf-8', '.css': 'text/css', '.js': 'text/javascript',
  '.webp': 'image/webp', '.png': 'image/png', '.jpg': 'image/jpeg', '.svg': 'image/svg+xml',
  '.woff2': 'font/woff2', '.json': 'application/json',
};

http.createServer((req, res) => {
  let url = decodeURIComponent(new URL(req.url, 'http://x').pathname);
  if (url === '/') url = '/inicio.html';
  let arq = path.join(PROTO, path.normalize(url).replace(/^([/\\])+/, ''));
  if (url.startsWith('/img/')) {
    arq = path.join(MIDIA, path.basename(url));
    if (!fs.existsSync(arq)) arq = path.join(TEMA_IMG, path.basename(url));
  }
  if (![PROTO, MIDIA, TEMA_IMG].some((d) => arq.startsWith(d))) { res.writeHead(403).end(); return; }
  fs.readFile(arq, (erro, dados) => {
    if (erro) { res.writeHead(404).end('não encontrado'); return; }
    res.writeHead(200, { 'Content-Type': TIPOS[path.extname(arq)] || 'application/octet-stream' });
    res.end(dados);
  });
}).listen(PORTA, () => console.log(`Protótipo em http://localhost:${PORTA}`));
