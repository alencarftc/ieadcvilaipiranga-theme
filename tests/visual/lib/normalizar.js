// Normalização do HTML para o teste de markup.
// `serializarSecoes` roda DENTRO do navegador (page.evaluate), sobre o HTML que o servidor entregou
// (JavaScript da página desligado), e devolve cada seção de primeiro nível já normalizada.

/* eslint-disable no-undef */
function serializarSecoes(opcoes) {
  const { minis, base } = opcoes;

  // x.html#y → endereço do WordPress (mesma regra de lib/paginas.js e scripts/importar_prototipo.py)
  function endereco(nome) {
    if (nome === 'inicio') return '/';
    if (nome.startsWith('ministerios-') && minis.includes(nome.slice(12))) return `/ministerios/${nome.slice(12)}/`;
    if (nome.startsWith('artigo-')) return `/${nome.slice(7)}/`;
    return `/${nome}/`;
  }
  function link(v) {
    let s = v.trim();
    if (base && s.startsWith(base)) s = s.slice(base.length) || '/';
    s = s.replace(/^(?:\.\/)?([\w-]+)\.html(#[\w-]*)?$/, (_, n, h) => endereco(n) + (h || ''));
    return s;
  }
  // Imagem: só o nome do arquivo, sem os sufixos que o WordPress acrescenta.
  function arquivo(u) {
    const nome = u.trim().replace(/^['"]|['"]$/g, '').split(/[?#]/)[0].split('/').pop();
    return nome.replace(/-scaled(?=\.)/, '').replace(/-\d+x\d+(?=\.)/, '');
  }
  function estilo(v) {
    return v.replace(/url\(([^)]*)\)/g, (_, u) => `url(${arquivo(u)})`)
      .split(';').map((d) => d.trim()).filter(Boolean)
      .map((d) => d.replace(/\s*:\s*/, ':').replace(/\s+/g, ' ')).join(';');
  }

  // O WordPress troca aspas retas por curvas (wptexturize) em todo conteúdo, inclusive o dos blocos.
  function texto(v) {
    return v.replace(/[\u201C\u201D\u2033]/g, '"').replace(/[\u2018\u2019\u2032]/g, "'")
      .replace(/\s+/g, ' ').replace(/ [\u2013\u2014] /g, ' - ').trim()
      // "\u00daltima atualiza\u00e7\u00e3o: <data>" muda a cada altera\u00e7\u00e3o da p\u00e1gina (\u00e9 a data real): compara s\u00f3 o texto.
      .replace(/(\u00daltima atualiza\u00e7\u00e3o:) \d{1,2} de \p{L}+ de \d{4}/u, '$1 <data>');
  }

  const IGNORAR = new Set(['data-dia', 'data-hora']);
  // Formulários: o do protótipo é demonstrativo; o do WordPress (shortcodes do ieadc-core) envia de verdade.
  // Compara só o que a pessoa vê: rótulos, campos (id, tipo, required, placeholder), textos e botões.
  // Grupo de opções: o WordPress usa <fieldset>/<legend> (acessível) onde o protótipo tem <div>/<label>.
  const IGNORAR_FORM = new Set(['action', 'method', 'name', 'autocomplete', 'maxlength', 'data-ok', 'novalidate', 'value']);
  function atributos(el) {
    const lista = [];
    const noForm = !!el.closest('form');
    for (const a of el.attributes) {
      if (IGNORAR.has(a.name)) continue;
      if (noForm && IGNORAR_FORM.has(a.name)) continue;
      if (el.tagName === 'FORM' && a.name === 'id') continue;
      if (noForm && a.name === 'id' && el.type === 'radio') continue;
      let v = a.value;
      if (a.name === 'class') {
        let cl = v.split(/\s+/).filter(Boolean);
        if (cl.includes('dia')) cl = cl.filter((c) => c !== 'hoje');
        if (el.tagName === 'FORM') cl = cl.filter((c) => c !== 'ieadc-ui');
        v = cl.join(' ');
        if (!v) continue;
      } else if (a.name === 'href') v = link(v);
      else if (a.name === 'src') v = arquivo(v);
      else if (a.name === 'style') v = estilo(v);
      else v = v.replace(/\s+/g, ' ').trim();
      lista.push([a.name, v]);
    }
    lista.sort((x, y) => (x[0] < y[0] ? -1 : x[0] > y[0] ? 1 : 0));
    return lista.map(([n, v]) => (v === '' ? ` ${n}` : ` ${n}="${v}"`)).join('');
  }

  const VAZIOS = new Set(['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'source', 'track', 'wbr']);
  function ignorarElemento(el) {
    // selo "Próximo" (calculado no navegador)
    if (el.tagName === 'SPAN' && el.classList.contains('tag') && el.textContent.trim() === 'Próximo') return true;
    if (el.parentElement && el.parentElement.closest('form')) {
      if (el.tagName === 'INPUT' && el.type === 'hidden') return true; // ação, nonce, endereço de volta
      if (el.getAttribute('aria-hidden') === 'true' && el.querySelector('input')) return true; // armadilha anti-spam
      if (el.classList.contains('msg')) return true; // avisos de sucesso/erro (o protótipo mostra exemplos)
    }
    return false;
  }

  // Uma linha por tag ou texto, indentada: o diff fica legível.
  function serializar(no, nivel, linhas) {
    const pad = '  '.repeat(nivel);
    for (const filho of no.childNodes) {
      if (filho.nodeType === 3) {
        const t = texto(filho.nodeValue);
        if (t) linhas.push(pad + t);
      } else if (filho.nodeType === 1) {
        if (ignorarElemento(filho)) continue;
        let tag = filho.tagName.toLowerCase();
        if (filho.closest('form') && tag === 'fieldset') tag = 'div';
        if (filho.closest('form') && tag === 'legend') tag = 'label';
        linhas.push(`${pad}<${tag}${atributos(filho)}>`);
        if (!VAZIOS.has(tag)) {
          serializar(filho.tagName === 'TEMPLATE' ? filho.content : filho, nivel + 1, linhas);
          linhas.push(`${pad}</${tag}>`);
        }
      }
    }
  }

  const raiz = document.querySelector('main .ieadc-pagina') || document.querySelector('main');
  if (!raiz) return null;
  return [...raiz.children].map((el) => {
    const linhas = [];
    const copia = document.createElement('div');
    copia.appendChild(el.cloneNode(true));
    serializar(copia, 0, linhas);
    return { tag: el.tagName.toLowerCase(), classe: el.getAttribute('class') || '', html: linhas.join('\n') };
  });
}

module.exports = { serializarSecoes };
