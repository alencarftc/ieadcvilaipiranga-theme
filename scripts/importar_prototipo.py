#!/usr/bin/env python3
"""Converte as páginas do protótipo v3 em conteúdo de blocos do WordPress.

Uso: python3 scripts/importar_prototipo.py /caminho/para/prototipo-v3

Gera content/paginas/<arquivo>.html: cada <section> do <main> vira um bloco
"HTML personalizado", editável no painel. Ajustes feitos na conversão:
- imagens img/x.webp viram {{img:x.webp}} (o seed.php troca pela URL da mídia);
- links entre páginas viram os endereços do WordPress;
- formulários viram os shortcodes do núcleo ([ieadc_contato], [ieadc_pedido_oracao]);
- o selo "Próximo" dos cultos sai do HTML e é calculado no navegador (site.js).
"""
import re, sys, pathlib

RAIZ = pathlib.Path(__file__).resolve().parent.parent
SAIDA = RAIZ / 'content' / 'paginas'

MINIS = ['circulo-de-oracao', 'escola-biblica-dominical', 'ensinai', 'familia',
         'missao-e-evangelismo', 'mocidade', 'criancas']

def endereco(arquivo: str) -> str:
    nome = arquivo[:-5]
    if nome == 'inicio':
        return '/'
    if nome.startswith('ministerios-') and nome[12:] in MINIS:
        return f'/ministerios/{nome[12:]}/'
    if nome.startswith('artigo-'):
        return f'/{nome[7:]}/'
    return f'/{nome}/'

DIAS = {'DOM': 0, 'SEG': 1, 'TER': 2, 'QUA': 3, 'QUI': 4, 'SEX': 5, 'SAB': 6, 'SÁB': 6}

def hora(t: str) -> str:
    m = re.match(r'(\d{1,2})h(\d{2})?', t)
    return f'{int(m.group(1)):02d}:{m.group(2) or "00"}' if m else ''

def marcar_cultos(html: str) -> str:
    html = html.replace('<span class="tag">Próximo</span>', '')
    html = re.sub(r'class="dia hoje"', 'class="dia"', html)
    def um(m):
        bloco = m.group(0)
        d = re.search(r'<span class="d">([^<]+)</span>', bloco)
        t = re.search(r'<span class="t">([^<]+)</span>', bloco)
        if d and t and d.group(1) in DIAS:
            return bloco.replace('<a class="dia"', f'<a class="dia" data-dia="{DIAS[d.group(1)]}" data-hora="{hora(t.group(1))}"', 1)
        return bloco
    html = re.sub(r'<a class="dia"[^>]*>.*?</a>', um, html, flags=re.S)
    # Domingo: cada encontro da lista tem o seu horário
    def dom(m):
        return re.sub(r'<a href="([^"]+)">(.*?<span class="t">([^<]+)</span>)</a>',
                      lambda a: f'<a href="{a.group(1)}" data-dia="0" data-hora="{hora(a.group(3))}">{a.group(2)}</a>', m.group(0), flags=re.S)
    return re.sub(r'<div class="lista-dom">.*?</div>', dom, html, flags=re.S)

def converter(arq: pathlib.Path) -> str:
    h = arq.read_text(encoding='utf-8')
    main = re.search(r'<main>(.*)</main>', h, re.S).group(1)
    main = re.sub(r'\bimg/([\w.-]+\.webp)', r'{{img:\1}}', main)
    main = re.sub(r'href="([\w-]+\.html)(#[\w-]+)?"', lambda m: f'href="{endereco(m.group(1))}{m.group(2) or ""}"', main)
    if 'data-ok="ok-contato"' in main:
        main = re.sub(r'<form class="form" data-ok="ok-contato">.*?</form>', '[ieadc_contato]', main, flags=re.S)
    if 'data-ok="ok-pedido"' in main:
        main = re.sub(r'<form class="form" data-ok="ok-pedido">.*?</form>', '[ieadc_pedido_oracao]', main, flags=re.S)
    main = marcar_cultos(main)
    # Elementos de primeiro nível na ordem do protótipo (<section> e <nav class="trocar">).
    partes = [m.group(0) for m in re.finditer(r'<(section|nav)\b.*?</\1>', main, re.S)]
    resto = re.sub(r'<(section|nav)\b.*?</\1>', '', main, flags=re.S).strip()
    if resto:
        raise SystemExit(f'{arq.name}: conteúdo fora de <section>/<nav>: {resto[:80]}')
    return '\n\n'.join(f'<!-- wp:html -->\n{p.strip()}\n<!-- /wp:html -->' for p in partes) + '\n'

if __name__ == '__main__':
    origem = pathlib.Path(sys.argv[1])
    SAIDA.mkdir(parents=True, exist_ok=True)
    for arq in sorted(origem.glob('*.html')):
        (SAIDA / arq.name).write_text(converter(arq), encoding='utf-8')
        print('ok', arq.name, '->', endereco(arq.name))
