#!/usr/bin/env python3
"""Gera as referências das specs a partir do protótipo convertido (content/paginas).

Saídas:
- docs/specs/mapa-secoes.json : para cada página, a lista ordenada de blocos (é o roteiro dos agentes de página)
- docs/specs/referencias/<bloco>.html : todas as ocorrências da seção no protótipo (HTML esperado do bloco)

Uso: python3 scripts/referencias_specs.py
"""
import re, json, pathlib, collections

RAIZ = pathlib.Path(__file__).resolve().parent.parent
PAGS = RAIZ / 'content' / 'paginas'
OUT = RAIZ / 'docs' / 'specs'

def classes(b):
    return set(c for x in re.findall(r'class="([^"]+)"', b) for c in x.split())

def topo(b):
    m = re.match(r'<(section|div|nav)([^>]*)>', b)
    c = re.search(r'class="([^"]*)"', m.group(2)) if m else None
    return set(c.group(1).split()) if c else set()

def bloco(pagina, b):
    t, c = topo(b), classes(b)
    regras = [
        ('h1-home' in c, 'hero-home'),
        ('hero' in t, 'hero'),
        ('horarios' in t, 'semana'),
        ('trocar' in t, 'trocador'),
        ('vizinhos' in c, 'vizinhos'),
        ('fatos' in c, 'fatos'),
        ('min-corpo' in c, 'corpo-indice'),
        ('car-ctrl' in c, 'carrossel'),
        ('passo-unico' in c or 'passos' in c, 'passos'),
        ('boas' in c, 'boas-vindas'),
        ('duplas' in c, 'chamadas'),
        ('galeria-faixa' in c, 'galeria-faixa'),
        ('faixa' in c, 'faixa'),
        ('local' in c, 'local'),
        ('tabela' in c, 'tabela-horarios'),
        ('linha-tempo' in c, 'linha-tempo'),
        ('mvv' in c, 'mvv'),
        ('credo' in c, 'credo'),
        ('dirigentes' in c, 'historia'),
        ('aj-intro' in c, 'aceitar-passos'),
        ('corpo-art' in c, 'artigo-corpo'),
        ('artigo' in c and 'migalha' in c, 'artigo-topo'),
        ('contato-lista' in c, 'contato-lista'),
        ('lateral' in c and 'versiculo' in c, 'pedido-oracao'),
        ('capa' in c and 'sec' not in t, 'capa'),
        ('sem-fotos' in c, 'fotos-pendentes'),
        ('fotos' in c, 'fotos'),
        ('legenda' in c, 'calendario'),
        ('min-grade' in c and pagina == 'horarios-e-localizacao', 'cultos-grade'),
        ('min-grade' in c, 'ministerios-grade'),
        ('trilho' in c, 'ministerios-trilho'),
        ('evento' in c, 'eventos-grade'),
        ('acao' in c and 'cartao' in c, 'artigos-grade'),
        ('dep' in c, 'departamentos'),
        ('lider' in c, 'pastores'),
        ('chip' in c and 'grade' in c, 'artigos-categorias'),
        ('leitura' in c, 'leitura'),
    ]
    for ok, nome in regras:
        if ok:
            return nome
    return 'DESCONHECIDO'

def main():
    mapa, refs = {}, collections.defaultdict(list)
    for arq in sorted(PAGS.glob('*.html')):
        pag = arq.stem
        blocos = re.findall(r'<!-- wp:html -->\n(.*?)\n<!-- /wp:html -->', arq.read_text(encoding='utf-8'), re.S)
        mapa[pag] = []
        for i, b in enumerate(blocos):
            nome = bloco(pag, b)
            mapa[pag].append(nome)
            refs[nome].append((pag, i, b))
    (OUT / 'mapa-secoes.json').write_text(json.dumps(mapa, ensure_ascii=False, indent=1), encoding='utf-8')
    (OUT / 'referencias').mkdir(parents=True, exist_ok=True)
    for nome, lista in sorted(refs.items()):
        partes = [f'<!-- Bloco ieadc/{nome}: {len(lista)} ocorrência(s) no protótipo. Gerado por scripts/referencias_specs.py. -->']
        for pag, i, b in lista:
            partes.append(f'\n<!-- ===== {pag}.html · seção {i + 1} ===== -->\n{b}')
        (OUT / 'referencias' / f'{nome}.html').write_text('\n'.join(partes) + '\n', encoding='utf-8')
    cont = collections.Counter(n for v in mapa.values() for n in v)
    for n, q in sorted(cont.items()):
        print(f'{q:3d} {n}')

if __name__ == '__main__':
    main()
