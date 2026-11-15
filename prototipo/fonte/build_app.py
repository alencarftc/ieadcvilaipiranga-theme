# Monta o protótipo navegável (um index.html com rotas por #página) a partir de gen.py
import runpy, re, os, shutil
HERE = os.path.dirname(os.path.abspath(__file__))
BASE = os.path.dirname(HERE)
OUT = os.path.join(os.path.dirname(BASE), 'prototipo-navegavel')
shutil.rmtree(os.path.join(OUT, 'img'), ignore_errors=True)
shutil.copytree(os.path.join(BASE, 'img'), os.path.join(OUT, 'img'))
g = runpy.run_path(os.path.join(HERE, 'gen.py'))

def fix(h):
    h = re.sub(r'href="([a-z-]+)\.html#([\w-]+)"', r'href="#\2"', h)
    return re.sub(r'href="([a-z-]+)\.html"', r'href="#\1"', h)

css = open(f'{BASE}/site.css').read()
pages = ''.join(f'<div class="pg" id="pg-{s}" data-title="{t}" data-ativo="{a}" hidden>{fix(b)}</div>' for s, t, a, b in g['PAGES'])
GRP = {'contato': 'Contato', 'pedidos-de-oracao': 'Contato', 'politica-de-privacidade': 'Contato', 'horarios-e-localizacao': 'Horários', 'sobre-nos': 'A Igreja', 'lideranca': 'A Igreja', 'ministerios': 'A Igreja', 'artigos': 'Conteúdo', 'galeria': 'Conteúdo'}
router = '''(function(){var G=%s,first=document.querySelector('.pg');
function show(){var h=decodeURIComponent(location.hash.slice(1))||'inicio',pg=document.getElementById('pg-'+h),t=null;
if(!pg){t=document.getElementById(h);pg=t&&t.closest('.pg')}if(!pg)pg=first;
document.querySelectorAll('.pg').forEach(function(p){p.hidden=p!==pg});
var a=pg.dataset.ativo;document.querySelectorAll('.nav ul a').forEach(function(x){x.classList.toggle('on',x.getAttribute('href')==='#'+a)});
document.querySelectorAll('.nav .grp').forEach(function(x){x.classList.toggle('on',x.textContent.trim()===G[a])});
document.title=pg.dataset.title+' · IEADC Vila Ipiranga';var hd=document.querySelector('.header');hd.classList.remove('aberto');var m=hd.querySelector('.menu');m.textContent='MENU';m.setAttribute('aria-expanded','false');
if(t)t.scrollIntoView();else window.scrollTo(0,0);if(window.trocarVisivel)trocarVisivel(pg)}
window.addEventListener('hashchange',show);show()})();''' % str(GRP).replace("'", '"')
html = f'''<title>Site IEADC Vila Ipiranga</title>
<style>{css}
html{{scroll-behavior:smooth}}</style>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400..800;1,400..800&family=Roboto+Condensed:ital,wght@0,500..800;1,500..800&family=Dancing+Script:wght@600..700&display=swap">
{g["DEFS"]}{fix(g["header"](""))}{pages}{fix(g["RODAPE"])}
<script>{g["JS"]}</script><script>{router}</script>'''
open(f'{OUT}/index.html', 'w').write(html)
print(OUT, len(html))
