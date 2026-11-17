# Gera as páginas do protótipo v3 (alta fidelidade) do site da IEADC Vila Ipiranga.
# Textos: subaba Copies do site e aba Informações gerais, sem alteração. Trechos entre colchetes ficam em amarelo.
# Uso: python3 gen.py  → HTMLs estáticos em ../  (e build_app.py monta o protótipo navegável de um arquivo só)
import re, os, calendar, sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from minis import MINIS
from cultos import CULTOS
from PIL import Image
from datetime import date, timedelta

OUT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
HOJE = date(2026, 10, 6)
IG = 'https://www.instagram.com/ieadc.vilaipiranga/'
FB = 'https://www.facebook.com/ieadcvipagina'
YT = 'https://www.youtube.com/channel/UC7la6gW5oIvo83_DQhBp9sQ'
GM = 'https://share.google/9UqZuK0EVcGxwFH63'
SEDE = 'https://assembleiadedeus.org.br/'
END = 'Rua Marechal Althayr Roszanniy, 555 - Capão Raso, Curitiba - PR'

def P(s):
    return re.sub(r'\[([^\]]+)\]', r'<mark class="pend">[\1]</mark>', s)

# Ícones de traço (24 × 24)
I = {
 'oracao': '<path d="M8.5 21v-5.5L5 10.8V5.5a1.3 1.3 0 0 1 2.6 0V10"/><path d="M15.5 21v-5.5l3.5-4.7V5.5a1.3 1.3 0 0 0-2.6 0V10"/><path d="M8.5 15.5 12 12l3.5 3.5"/><path d="M12 3v5"/>',
 'livro': '<path d="M2 5.5c3-1.6 6.5-1.6 10 .8 3.5-2.4 7-2.4 10-.8v13.7c-3-1.6-6.5-1.6-10 .8-3.5-2.4-7-2.4-10-.8z"/><path d="M12 6.3v14"/>',
 'pessoas': '<circle cx="9" cy="8" r="3.2"/><path d="M3 20.5c0-3.4 2.7-6 6-6s6 2.6 6 6"/><circle cx="17.2" cy="9" r="2.5"/><path d="M15.8 14.6c2.9.4 5.2 2.8 5.2 5.9"/>',
 'pessoa': '<circle cx="12" cy="8.5" r="4"/><path d="M4.5 21c0-4.2 3.4-7.3 7.5-7.3s7.5 3.1 7.5 7.3"/>',
 'beca': '<path d="m2 9 10-5 10 5-10 5z"/><path d="M6 11.2v4.6c3.3 2.4 8.7 2.4 12 0v-4.6"/><path d="M22 9v6"/>',
 'cafe': '<path d="M4 9h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5z"/><path d="M17 10.5h1.2a2.5 2.5 0 0 1 0 5H17"/><path d="M8 3.5c0 1.2 1 1.2 1 2.4M12 3.5c0 1.2 1 1.2 1 2.4"/>',
 'igreja': '<path d="M12 2v5M9.5 4.5h5"/><path d="M5 22V12l7-4.5 7 4.5v10z"/><path d="M10 22v-4.5a2 2 0 0 1 4 0V22"/>',
 'calice': '<path d="M7 3h10c0 5.5-2.2 8.5-5 8.5S7 8.5 7 3z"/><path d="M12 11.5V19M8 21h8"/>',
 'chama': '<path d="M12 2.5c2.2 3 5.5 5.3 5.5 9.8a5.5 5.5 0 0 1-11 0c0-2.2 1-3.8 2.2-4.9 0 2 1 3.2 2.3 3.2 0-3.3-.9-5.4 1-8.1z"/>',
 'megafone': '<path d="M3 10.5v3a1 1 0 0 0 1 1h3l9 5.5V4L7 9.5H4a1 1 0 0 0-1 1z"/><path d="M19 9a4 4 0 0 1 0 6"/>',
 'degraus': '<path d="M3 20h5v-4.5h4.5V11H17V6.5h4"/><path d="M17 3.5h4v3"/>',
 'casa': '<path d="m3 11 9-7 9 7"/><path d="M5.5 9.5V20h13V9.5"/><path d="M10 20v-5h4v5"/>',
 'maos': '<path d="M12 21s-7.5-4.6-7.5-10.3A4.2 4.2 0 0 1 12 8.2a4.2 4.2 0 0 1 7.5 2.5C19.5 16.4 12 21 12 21z"/>',
 'globo': '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3.2 3.4 3.2 14.6 0 18M12 3c-3.2 3.4-3.2 14.6 0 18"/>',
 'nota': '<path d="M9 18V5l11-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="17" cy="16" r="3"/>',
 'crianca': '<circle cx="12" cy="5" r="2.6"/><path d="M12 8.5v6.5M7.5 11h9M9 21l3-6 3 6"/>',
 'cal': '<rect x="3" y="5" width="18" height="16" rx="2.5"/><path d="M3 10h18M8 3v4M16 3v4"/>',
 'relogio': '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/>',
 'pin': '<path d="M12 22s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="10" r="2.6"/>',
 'fone': '<path d="M5 3h3.6l1.9 4.8-2.4 1.5a11.5 11.5 0 0 0 5.6 5.6l1.5-2.4L20 14.4V18a2.4 2.4 0 0 1-2.6 2.4A16.6 16.6 0 0 1 2.6 5.6 2.4 2.4 0 0 1 5 3z"/>',
 'email': '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m3.5 7 8.5 6 8.5-6"/>',
 'seta': '<path d="M5 12h14M13 6l6 6-6 6"/>',
 'esq': '<path d="m15 6-6 6 6 6"/>', 'dir': '<path d="m9 6 6 6-6 6"/>', 'baixo': '<path d="m6 9 6 6 6-6"/>',
 'onibus': '<rect x="4" y="3" width="16" height="14.5" rx="3"/><path d="M4 11h16M7.5 21l1.2-3.5M16.5 21l-1.2-3.5"/><circle cx="8" cy="14.3" r=".9"/><circle cx="16" cy="14.3" r=".9"/>',
 'insta': '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.4" cy="6.6" r=".9"/>',
 'yt': '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 5 3-5 3z"/>',
 'fb': '<path d="M14 21v-7h3l.5-3.5H14V8.6c0-1 .4-1.6 1.7-1.6h1.9V3.8A22 22 0 0 0 15 3.6c-2.6 0-4.5 1.6-4.5 4.5v2.4h-3V14h3v7"/>',
 'escudo': '<path d="M12 3 20 6v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="m9 12 2 2 4-4"/>',
 'play': '<circle cx="12" cy="12" r="9"/><path d="m10 8.5 5.5 3.5-5.5 3.5z"/>',
 'fone-ouvido': '<path d="M3.5 18v-5.5a8.5 8.5 0 0 1 17 0V18"/><rect x="3" y="14.5" width="4.5" height="6" rx="1.5"/><rect x="16.5" y="14.5" width="4.5" height="6" rx="1.5"/>',
 'texto': '<path d="M6 3h9l4 4v14H6z"/><path d="M14.5 3v4.5H19M9 12h7M9 16h7"/>',
 'alvo': '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.3"/>',
 'olho': '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
 'ok': '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.8 2.8L16.5 9.5"/>',
 'erro': '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v6M12 16.5v.1"/>',
}
def ic(n, cls='ico'):
    return f'<svg class="{cls}" viewBox="0 0 24 24" aria-hidden="true">{I[n]}</svg>'

SWOOSH = '<svg class="swoosh" viewBox="0 0 260 16" preserveAspectRatio="none" aria-hidden="true"><path d="M3 12C70 3 170 2 257 8"/></svg>'
DEFS = ('<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs><linearGradient id="gr-sol" x1="0" x2="1" y1="0" y2="0">'
        '<stop offset="0" stop-color="#FEB900"/><stop offset=".45" stop-color="#FE7D00"/><stop offset="1" stop-color="#EC4024"/></linearGradient></defs></svg>')

def bg(f): return f'style="background-image:url(img/{f}.webp)"'

# Menu da Estrutura do site
# Menus com subitens também são links (Felipe, 09/10/2026): o título leva à página principal do grupo.
MENU = [('Início', 'inicio', None), ('A Igreja', 'sobre-nos', [('Liderança', 'lideranca'), ('Ministérios', 'ministerios')]),
        ('Horários', 'horarios-e-localizacao', []), ('Eventos', 'eventos', None),
        ('Conteúdo', 'artigos', [('Galeria de fotos e vídeos', 'galeria')]), ('Aceitar Jesus', 'aceitar-jesus', None), ('Contato', 'contato', [('Pedidos de oração', 'pedidos-de-oracao'), ('Política de privacidade', 'politica-de-privacidade')])]
GRUPO = {'contato': 'Contato', 'pedidos-de-oracao': 'Contato', 'politica-de-privacidade': 'Contato', 'horarios-e-localizacao': 'Horários', 'sobre-nos': 'A Igreja', 'lideranca': 'A Igreja', 'ministerios': 'A Igreja', 'artigos': 'Conteúdo', 'galeria': 'Conteúdo'}

ON = ' class="on"'
def header(ativo):
    li = []
    for nome, slug, sub in MENU:
        if sub is not None:
            on = ' on' if GRUPO.get(ativo, '').startswith(nome) or (ativo.startswith('artigo') and nome == 'Conteúdo') else ''
            # Felipe (09/10/2026): sem rótulos soltos; os ministérios abrem a partir do item clicável Ministérios
            # e os cultos aparecem direto no menu Horários.
            def lista_ic(lista, pre):
                return ''.join(f'<li><a href="{pre}{m["slug"]}.html"{ON if pre + m["slug"] == ativo else ""}>{ic(m["icone"])}{m["nome"]}</a></li>' for m in lista)
            itens = ''
            for n, s_ in sub:
                if s_ == 'ministerios':
                    itens += f'<li class="tem-sub"><a href="{s_}.html"{ON if s_ == ativo else ""}>{n}{ic("baixo")}</a><ul class="sub sub3">{lista_ic(MINIS, "ministerios-")}</ul></li>'
                else:
                    itens += f'<li><a href="{s_}.html"{ON if s_ == ativo else ""}>{n}</a></li>'
            if slug == 'horarios-e-localizacao':
                itens += lista_ic(CULTOS, '')
            li.append(f'<li><a class="grp{on}" href="{slug}.html">{nome}{ic("baixo")}</a><ul class="sub{" cultos" if slug == "horarios-e-localizacao" else ""}">{itens}</ul></li>')
        else:
            li.append(f'<li><a href="{slug}.html"{ON if slug == ativo else ""}>{nome}</a></li>')
    return f'''<div class="proto">Protótipo do site · trechos em amarelo aguardam confirmação · formulários não enviam nada</div>
<div class="topline"><div class="wrap"><span class="frase">Uma família de fé no Capão Raso, em Curitiba.</span>
<nav aria-label="Redes sociais"><a href="{IG}">Instagram</a><a href="{FB}">Facebook</a><a href="{YT}">YouTube</a></nav></div></div>
<header class="header"><div class="wrap"><a class="marca" href="inicio.html" aria-label="Página inicial"><img src="img/logo_cor.webp" alt="Assembleia de Deus em Curitiba"><span>VILA<br>IPIRANGA</span></a>
<button class="menu" type="button" aria-expanded="false" aria-controls="nav">MENU</button>
<nav class="nav" id="nav" aria-label="Menu principal"><ul>{"".join(li)}</ul><a class="cta" href="pedidos-de-oracao.html">Pedido de oração</a></nav></div></header>'''

def _mapa_site():
    def lk(n, h): return f'<li><a href="{h}.html">{n}</a></li>'
    def sub(lista, pre): return '<ul class="sub-mapa">' + ''.join(lk(m['nome'], pre + m['slug']) for m in lista) + '</ul>'
    soltos = '<div class="soltos">' + ''.join(f'<h4><a href="{h}.html">{n}</a></h4>' for n, h, x in MENU if x is None) + '</div>'
    cols = [soltos]
    for nome, slug, itens in MENU:
        if itens is None: continue
        li = []
        for n, h in itens:
            extra = sub(MINIS, 'ministerios-') if h == 'ministerios' else ''
            li.append(f'<li><a href="{h}.html">{n}</a>{extra}</li>')
        if slug == 'horarios-e-localizacao':
            li += [lk(c['nome'], c['slug']) for c in CULTOS]
        cols.append(f'<div><h4><a href="{slug}.html">{nome}</a></h4><ul>{"".join(li)}</ul></div>')
    return '<nav class="mapa-site" aria-label="Mapa do site">' + ''.join(cols) + '</nav>'

RODAPE = f'''<footer class="rodape"><div class="wrap"><div class="cols topo-rod">
<div><span class="logo-b"><img src="img/logo_cor.webp" alt="Assembleia de Deus em Curitiba"></span>
<p style="margin-top:21px"><b style="color:#fff">IEADC Vila Ipiranga</b><br>Congregação da Igreja Evangélica Assembleia de Deus em Curitiba</p>
<div class="redes"><a href="{IG}" aria-label="Instagram">{ic("insta")}</a><a href="{FB}" aria-label="Facebook">{ic("fb")}</a><a href="{YT}" aria-label="YouTube">{ic("yt")}</a></div></div>
<div><h4>Cultos</h4><ul><li>Segunda · 20h · Culto de Oração</li><li>Quarta · 20h · Culto de Ensino</li><li>Quinta · 20h · Círculo de Oração</li><li>Sexta · 19h30 · Ensinai</li><li>Domingo · 9h30 EBD · 19h culto</li><li>2º sábado · 19h30 · Ceia do Senhor</li></ul></div>
<div><h4>Contato</h4><ul><li>{END}</li><li>+55 (41) 98838-1994</li><li>ieadcvilaipiranga@gmail.com</li></ul></div>
<div><h4>Outros links</h4><ul><li><a href="{SEDE}">Site da IEADC</a></li><li><a href="{IG}">Instagram</a></li><li><a href="{FB}">Facebook</a></li><li><a href="{YT}">YouTube</a></li></ul></div>
</div>{_mapa_site()}<div class="base"><span>Igreja Sede: Pr. Wagner Tadeu dos Santos Gaby, presidente, e Pr. Aristides Vieira, vice-presidente.</span><span class="tema">2026 · ALCANÇAR CURITIBA PARA CRISTO. NOSSA MISSÃO, NOSSA PAIXÃO.</span></div></div></footer>'''

JS = '''(function(){var h=document.querySelector('.header'),b=h&&h.querySelector('.menu');
if(b)b.addEventListener('click',function(){var o=h.classList.toggle('aberto');b.setAttribute('aria-expanded',o);b.textContent=o?'FECHAR':'MENU'});
document.querySelectorAll('form[data-ok]').forEach(function(f){f.addEventListener('submit',function(e){e.preventDefault();var m=document.getElementById(f.dataset.ok);if(m){m.hidden=false;m.scrollIntoView({block:'nearest'})}f.reset()})});
document.querySelectorAll('[data-carrossel]').forEach(function(c){var p=c.querySelector('.pista'),s=p.children,d=c.querySelectorAll('.pontos button'),sec=c.closest('section'),i=0;
function ir(n){i=(n+s.length)%s.length;p.scrollTo({left:s[i].offsetLeft-p.offsetLeft,behavior:'smooth'})}
p.addEventListener('scroll',function(){var n=Math.round(p.scrollLeft/(s[0].offsetWidth+13));if(n!==i){i=n}d.forEach(function(b,k){b.classList.toggle('on',k===i)})},{passive:true});
d.forEach(function(b,k){b.addEventListener('click',function(){ir(k)})});
var a=sec.querySelector('.car-ant'),z=sec.querySelector('.car-prox');if(a)a.addEventListener('click',function(){ir(i-1)});if(z)z.addEventListener('click',function(){ir(i+1)})});
window.trocarVisivel=function(r){(r||document).querySelectorAll('.trocar a.on').forEach(function(a){var t=a.parentElement;t.scrollLeft=a.offsetLeft-t.offsetLeft-(t.clientWidth-a.offsetWidth)/2})};trocarVisivel()})();'''

PAGES = []
def page(slug, titulo, corpo, ativo=None):
    PAGES.append((slug, titulo, ativo or slug, corpo))
    html = f'''<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{titulo} · IEADC Vila Ipiranga</title><link rel="stylesheet" href="fontes.css"><link rel="stylesheet" href="site.css"></head>
<body>{DEFS}{header(ativo or slug)}<main>{corpo}</main>{RODAPE}<script>{JS}</script></body></html>'''
    open(os.path.join(OUT, slug + '.html'), 'w').write(html)

def btn(t, href, cls='', icone='seta'):
    return f'<a class="btn {cls}" href="{href}">{t}{ic(icone) if icone else ""}</a>'

def cab(eyebrow, titulo, texto='', direita='', lead=False):
    t = f'<p class="{"lead" if lead else "txt"}">{P(texto)}</p>' if texto else ''
    return f'<div class="cab"><div><span class="eyebrow">{eyebrow}</span><h2 class="h2">{titulo}</h2>{SWOOSH}{t}</div>{direita}</div>'

def hero(eyebrow, titulo, texto, botoes='', foto=None, script='', migalha=''):
    mg = f'<div class="migalha">{migalha}</div>' if migalha else ''
    if not foto:
        return f'''<section class="hero sem-foto"><div class="wrap"><div class="conteudo">{mg}<span class="eyebrow">{eyebrow}</span>
<h1 class="h1">{titulo}</h1>{SWOOSH}<p class="lead">{P(texto)}</p>{botoes}</div></div></section>'''
    sc = f'<div class="script script-home">{script}</div>' if script else ''
    return f'''<section class="hero pagina"><div class="foto" style="background-image:url(img/{foto}.webp);background-position:{foco(foto)}"></div>
<div class="wrap"><div class="conteudo">{mg}<span class="eyebrow">{eyebrow}</span><h1 class="h1">{titulo}</h1>{SWOOSH}<p class="lead">{P(texto)}</p>{botoes}{sc}</div></div></section>'''

# Ponto de foco de cada foto de capa (evita cortar cabeças). Fotos em pé mostram a parte de cima.
FOCO = {'f_mp_criancas': 'center 12%', 'f_mp_ensinai': 'center 30%', 'f_mp_missao': 'center 65%', 'f_mp_circulo': 'center 55%',
        'f_mp_familia': 'center 40%', 'f_cx_147': 'center 45%', 'f_cx_310': 'center 30%', 'f_cx_4': 'center 25%', 'f_cx_300': 'center 30%'}
def foco(f):
    if f in FOCO: return FOCO[f]
    w, h = Image.open(os.path.join(OUT, 'img', f + '.webp')).size
    return 'center 18%' if h > w else 'center 35%'

# Programação (aba Informações gerais)
SEMANA = [('SEG', 'oracao', 'Culto de Oração', '', '20h', 'culto-de-oracao'), ('QUA', 'livro', 'Culto de Ensino', '', '20h', 'culto-de-ensino'),
          ('QUI', 'pessoas', 'Culto dirigido pelo Círculo de Oração', '', '20h', 'culto-do-circulo-de-oracao'), ('SEX', 'beca', 'Ensinai', '(Curso de Teologia Ministerial)', '19h30', 'ministerios-ensinai'),
          ('DOM', 'cafe', 'Café comunitário', '', '9h', 'culto-dominical'), ('DOM', 'livro', 'EBD', '(Escola Bíblica Dominical)', '9h30', 'ministerios-escola-biblica-dominical'), ('DOM', 'igreja', 'Culto dominical', '', '19h', 'culto-dominical')]
DIAS_CULTO = ('SEG', 'QUA', 'QUI', 'SEX', 'DOM')
def tema_dia(a):
    return a.replace(' (quando houver)', '<small>quando houver</small>')
TEMAS = [('1º domingo', 'Família'), ('2º domingo', 'Missões e Evangelismo'), ('3º domingo', 'Jovens e Adolescentes'), ('4º domingo', 'Mulheres'), ('5º domingo (quando houver)', 'Crianças')]
VALORES = [('livro', 'Ensino genuíno da Palavra de Deus'), ('chama', 'Fé pentecostal'), ('megafone', 'Evangelização'), ('degraus', 'Discipulado'),
           ('casa', 'Família'), ('pessoas', 'Comunhão'), ('maos', 'Amor ao próximo')]

def dias_html():
    out = []
    prox_dia = next(DSEM[(HOJE.weekday() + k) % 7] for k in range(7) if DSEM[(HOJE.weekday() + k) % 7] in DIAS_CULTO)
    marcado = False
    for d, i, m, sm, t, href in [x for x in SEMANA if x[0] != 'DOM']:
        s = f'<small>{sm}</small>' if sm else ''
        prox = d == prox_dia and not marcado
        marcado = marcado or prox
        tag = '<span class="tag">Próximo</span>' if prox else ''
        out.append(f'<a class="dia{" hoje" if prox else ""}" href="{href}.html">{tag}{ic(i)}<span class="d">{d}</span><span class="m">{m}{s}</span><span class="t">{t}</span></a>')
    # Domingo: os 3 encontros num só cartão
    dom = [x for x in SEMANA if x[0] == 'DOM']
    linhas = ''.join(f'<a href="{href}.html">{ic(i)}<span class="m">{m}{f"<small>{sm}</small>" if sm else ""}</span><span class="t">{t}</span></a>' for d, i, m, sm, t, href in dom)
    prox = prox_dia == 'DOM' and not marcado
    tag = '<span class="tag">Próximo</span>' if prox else ''
    out.append(f'<div class="dia domingo{" hoje" if prox else ""}">{tag}<div class="lado-dom">{ic("igreja")}<span class="d">DOM</span></div><div class="lista-dom">{linhas}</div></div>')
    return f'<div class="dias">{"".join(out)}</div>'

# Agenda gerada pelas regras da programação
MESES = ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro']
DSEM = ['SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB', 'DOM']
def segundo_sabado(a, m):
    d = date(a, m, 1)
    while d.weekday() != 5: d += timedelta(1)
    return d + timedelta(7)
def eventos_do_dia(d):
    ev = []
    wd = d.weekday()
    if wd == 0: ev.append(('20h', 'Culto de Oração', 'c-culto', False))
    if wd == 2: ev.append(('20h', 'Culto de Ensino', 'c-ensino', False))
    if wd == 3: ev.append(('20h', 'Culto dirigido pelo Círculo de Oração', 'c-culto', False))
    if wd == 4: ev.append(('19h30', 'Ensinai (Curso de Teologia Ministerial)', 'c-ensino', False))
    ceia = segundo_sabado(d.year, d.month)
    if d.month % 2 == 1 and d == ceia - timedelta(1): ev.append(('22h', 'Vigília', 'c-vigilia', True))
    if d == ceia:
        if d.month % 2 == 0: ev.append(('8h', 'Consagração', 'c-vigilia', True))
        ev.append(('19h30', 'Ceia do Senhor', 'c-ceia', True))
    if wd == 6:
        n = (d.day - 1) // 7
        ev += [('9h', 'Café comunitário', 'c-domingo', False), ('9h30', 'EBD (Escola Bíblica Dominical)', 'c-ensino', False),
               ('19h', f'Culto dominical: {TEMAS[n][1]}', 'c-domingo', True)]
    for ini, fim, h, t, c, _ in EXTRAS:
        if ini <= d <= fim: ev.insert(0, (h, t, c, True))
    return ev
# Eventos avulsos informados pelo Felipe em 06/10/2026
EXTRAS = [(date(2026, 10, 17), date(2026, 10, 18), '[horário a confirmar]', 'Confraternização dos jovens', 'c-jovens', 'Dias 17 e 18 de outubro. [Local e programação a confirmar]')]
DESC = {'Vigília': 'Na sexta-feira que antecede a ceia. Nos meses ímpares.', 'Consagração': 'No sábado da ceia, pela manhã. Nos meses pares.',
        'Ceia do Senhor': 'Todo 2º sábado do mês.'}
DESC.update({t: dsc for _, _, _, t, _, dsc in EXTRAS})
LINK_EV = {'Vigília': 'vigilia-e-consagracao', 'Consagração': 'vigilia-e-consagracao', 'Ceia do Senhor': 'ceia-do-senhor', 'Ensaio dos jovens': 'ministerios-mocidade', 'Confraternização dos jovens': 'ministerios-mocidade'}
def destaques(n, desde=HOJE):
    out, d = [], desde
    while len(out) < n:
        for h, t, c, esp in eventos_do_dia(d):
            if esp and not any(x[2] == t for x in out): out.append((d, h, t, c))
        d += timedelta(1)
    return out[:n]
CHIP = {'c-ceia': ('sol', 'Ceia e preparação'), 'c-vigilia': ('sol', 'Ceia e preparação'), 'c-jovens': ('verde', 'Mocidade'), 'c-domingo': ('', 'Culto dominical')}
def ev_cartao(d, h, t, c):
    desc = DESC.get(t) or ('Cada domingo do mês tem um tema no culto dominical.')
    fim = next((f for i, f, _, tt, _, _ in EXTRAS if tt == t and i == d and f != d), None)
    dn = str(d.day)
    hoje = '<span class="hoje-tag">Hoje</span>' if d == HOJE else ''
    cls, nome = CHIP.get(c, ('', 'Evento'))
    href = LINK_EV.get(t) or ('culto-dominical' if t.startswith('Culto dominical') else 'eventos')
    return f'''<a class="cartao evento" href="{href}.html"><div class="data"><span class="dw">{DSEM[d.weekday()]}</span><span class="dn">{dn}</span><span class="mm">{MESES[d.month][:3].upper()}</span>{f'<span class="ate">até {DSEM[fim.weekday()].lower()} {fim.day}</span>' if fim else ""}</div>
<div class="corpo"><div class="chips"><span class="chip {cls}">{nome}</span>{hoje}</div>
<h3 class="h4">{t}</h3><span class="hora">{ic("relogio")}{P(h)}</span><p class="small">{P(desc)}</p><span class="mais">Saiba mais{ic("seta")}</span></div></a>'''
def mes_html(a, m):
    cal = calendar.Calendar(0).monthdatescalendar(a, m)
    cels = []
    for sem in cal:
        for d in sem:
            if d.month != m: cels.append('<div class="cel fora"></div>'); continue
            evs = eventos_do_dia(d)
            itens = ''.join(f'<div class="ev{" esp" if esp else ""}"><i class="{c}"></i><span><b>{P(h)}</b> {t}</span></div>' for h, t, c, esp in evs)
            cls = ' hoje' if d == HOJE else ('' if evs else ' vazio')
            cels.append(f'<div class="cel{cls}"><span class="n" data-dia=" · {DSEM[d.weekday()].capitalize()}">{d.day}</span>{itens}</div>')
    esp = [t for s_ in cal for d in s_ if d.month == m for _, t, _, e in eventos_do_dia(d) if e and not t.startswith('Culto dominical')]
    resumo = ' · '.join(dict.fromkeys(esp))
    aberto = ' open' if (a, m) == (HOJE.year, HOJE.month) else ''
    return f'''<details class="mes"{aberto}><summary><span class="tit">{MESES[m].capitalize()} de {a}</span><small>{resumo}</small><span class="abre">{ic("baixo")}</span></summary>
<div class="semana cab-sem">{"".join(f"<div>{x}</div>" for x in DSEM)}</div><div class="semana">{"".join(cels)}</div></details>'''

# Ministérios (Copies, sem alteração)
MIN = [
 ('Círculo de Oração', 'oracao', 'f_min_circulo', 'f_mp_circulo', 'Reúne as irmãs da igreja para orar pela congregação, pelas famílias e pelos pedidos recebidos. Dirige o culto de quinta-feira, às 20h.', 'Abigail Tostes Monteiro, Luciana Santos de Jesus Stein. Regentes do coral: Érica Tostes (1ª) e Raquel Oliveira (2ª)', 'Quinta · 20h'),
 ('Escola Bíblica Dominical', 'livro', 'f_min_ebd', 'f_mp_ebd', 'Estuda a Bíblia em classes, todos os domingos às 9h30. O tema muda a cada trimestre. Responsáveis: Pb. Manoel Lima e Dc. Jorge Fernandez.', 'Pb. Manoel Lima, Dc. Jorge Fernandez', 'Domingo · 9h30'),
 ('Ensinai', 'beca', 'f_min_ensinai', 'f_mp_ensinai', 'Curso de Teologia Ministerial para quem deseja se aprofundar no estudo da Bíblia e se preparar para servir. As aulas são às sextas-feiras, às 19h30.', '[a confirmar]', 'Sexta · 19h30'),
 ('Família', 'casa', 'p_familia', 'f_mp_familia', 'Cuida dos casais e das famílias da igreja, para que cada lar viva a fé no dia a dia. No 1º domingo do mês, o culto dominical tem o tema Família. [Outros encontros a confirmar]', 'Coop. Bruno Bernadoque e Tácila Bernadoque, Ev. Robson Stein e Luciana Stein', '1º domingo · 19h'),
 ('Missão e Evangelismo', 'globo', 'p_missoes', 'f_mp_missao', 'Leva o evangelho para além das portas da igreja, em sintonia com o tema do ano: alcançar Curitiba para Cristo. No 2º domingo do mês, o culto dominical tem o tema Missões e Evangelismo. [Ações e encontros a confirmar]', 'Coop. José Luiz Nascimento, Pr. Evandro Oliveira', '2º domingo · 19h'),
 ('Mocidade', 'pessoas', 'p_jovens', 'f_mp_mocidade', 'Reúne os jovens e adolescentes para crescerem juntos na fé, em comunhão e no serviço. No 3º domingo do mês, o culto dominical é dedicado a eles. [Encontros próprios a confirmar]', 'Anderson Sena e Evelin Sena, Leonardo Morais da Silva', '3º domingo · 19h'),
 ('Crianças', 'crianca', 'f_min_criancas', 'f_mp_criancas', 'Ensina a Palavra de Deus às crianças, de um jeito adequado a cada idade. Quando o mês tem 5º domingo, o culto dominical, às 19h, é dedicado a elas. Aos domingos, às 9h30, há classes da EBD para crianças.', 'Míriam Lima, Myrela Lima e equipe', '5º domingo · 19h'),
]
def primeira_frase(t): return t.split('. ')[0] + '.'

# Artigos de exemplo (textos novos, marcados como exemplo; versículos na ARA)
ART = [
 ('artigo-alcancar-curitiba', 'Mensagem', 'Alcançar Curitiba para Cristo', 'f_art_tema', 'O tema do ano é também um chamado: a missão começa na porta de casa.',
  [('v', 'mas recebereis poder, ao descer sobre vós o Espírito Santo, e sereis minhas testemunhas tanto em Jerusalém como em toda a Judeia e Samaria e até aos confins da terra.', 'Atos 1.8'),
   ('p', 'Neste ano, a IEADC caminha com um tema que é também um chamado: "Alcançar Curitiba para Cristo. Nossa missão, nossa paixão." Antes de subir aos céus, Jesus deixou aos discípulos uma promessa e uma tarefa.'),
   ('p', 'Para os discípulos, Jerusalém era a cidade onde eles estavam. Para nós, é o Capão Raso, o nosso bairro, a nossa Curitiba. A missão começa na porta de casa: no vizinho, no colega de trabalho, na família que ainda não conhece o amor de Deus.'),
   ('h', 'O poder vem antes da missão'),
   ('p', 'Jesus não mandou os discípulos saírem pelas próprias forças. Primeiro viria o poder do Espírito Santo, depois o testemunho. Por isso a oração e a busca a Deus vêm antes de qualquer ação.'),
   ('h', 'Testemunha conta o que viu'),
   ('p', 'Ser testemunha não exige discursos difíceis. Basta contar o que Jesus fez na sua vida, com palavras simples e com atitudes que confirmem essas palavras.'),
   ('h', 'Como participar'),
   ('p', 'Ore pelo nome de uma pessoa que você quer ver em Cristo e convide alguém para um culto. No 2º domingo do mês, o culto dominical tem o tema Missões e Evangelismo: é uma boa ocasião para trazer um amigo.')]),
 ('artigo-orai-uns-pelos-outros', 'Reflexão', 'Orai uns pelos outros', 'f_art_oracao', 'Na igreja, ninguém precisa carregar o peso sozinho.',
  [('v', 'Confessai, pois, os vossos pecados uns aos outros e orai uns pelos outros, para serdes curados. Muito pode, por sua eficácia, a súplica do justo.', 'Tiago 5.16'),
   ('p', 'A oração faz parte da rotina da igreja. A semana começa com o Culto de Oração, na segunda-feira, e na quinta-feira o culto é dirigido pelo Círculo de Oração.'),
   ('h', 'Orar juntos fortalece'),
   ('p', 'Quando Tiago escreve "orai uns pelos outros", ele descreve uma igreja em que ninguém carrega o peso sozinho. Orar por alguém é dizer: eu me importo com você.'),
   ('h', 'A súplica do justo tem força'),
   ('p', 'Não é a eloquência que dá força à oração, mas a fé de quem confia em Deus e vive em obediência a Ele.'),
   ('h', 'Podemos orar por você'),
   ('p', 'Se você passa por um momento difícil, envie o seu pedido de oração pelo site. Ele chega apenas ao Círculo de Oração e nunca é publicado.')]),
 ('artigo-ate-que-ele-venha', 'Estudo', 'Até que ele venha', 'f_biblia', 'O que lembramos, celebramos e esperamos na Ceia do Senhor.',
  [('v', 'Porque, todas as vezes que comerdes este pão e beberdes o cálice, anunciais a morte do Senhor, até que ele venha.', '1 Coríntios 11.26'),
   ('p', 'No 2º sábado de cada mês, às 19h30, a igreja se reúne para a Ceia do Senhor. É um momento de memória, comunhão e gratidão.'),
   ('h', 'Memória'),
   ('p', 'Jesus disse: "fazei isto em memória de mim" (1 Co 11.24). O pão e o cálice lembram o corpo entregue e o sangue derramado na cruz por nós.'),
   ('h', 'Comunhão'),
   ('p', 'À mesa do Senhor, a igreja se reconhece como uma só família, reunida pelo mesmo sacrifício.'),
   ('h', 'Preparação'),
   ('p', 'Paulo orienta: "Examine-se, pois, o homem a si mesmo, e, assim, coma do pão, e beba do cálice" (1 Co 11.28). Nos meses pares há vigília na sexta-feira que antecede a ceia; nos ímpares, consagração no sábado da ceia, pela manhã.')]),
]
def art_cartao(a):
    slug, cat, tit, foto, resumo, _ = a
    return f'''<a class="cartao" href="{slug}.html"><div class="img" {bg(foto)}></div><div class="corpo"><div style="display:flex;gap:8px;flex-wrap:wrap"><span class="chip">{ic("texto")}{cat}</span><span class="chip ex">Exemplo</span></div>
<h3 class="h3" style="font-size:24px">{tit}</h3><p class="txt">{resumo}</p><span class="mais acao">Ler artigo{ic("seta")}</span></div></a>'''

MAPA = f'''<div class="mapa"><svg viewBox="0 0 640 440" aria-hidden="true"><rect width="640" height="440" fill="#DCEBF8"/>
<g fill="none" stroke="#fff" stroke-linecap="round"><path d="M-20 300 L660 180" stroke-width="22"/><path d="M180 -20 L300 460" stroke-width="16"/><path d="M-20 90 L660 40" stroke-width="12"/>
<path d="M470 -20 L520 460" stroke-width="12"/><path d="M-20 400 L660 360" stroke-width="10"/><path d="M60 -20 L120 460" stroke-width="8"/></g>
<rect x="330" y="225" width="96" height="70" rx="10" fill="#BFE3C6"/><text x="378" y="314" text-anchor="middle" font-family="Roboto,Arial" font-size="12" fill="#2C6B3A">Praça Nova República</text>
<text x="32" y="292" font-family="Roboto,Arial" font-size="12" fill="#45536B" transform="rotate(-10 32 292)">R. Bortolo Gusso</text>
<text x="192" y="36" font-family="Roboto,Arial" font-size="12" fill="#45536B" transform="rotate(76 192 36)">R. Prof. João Mazzarotto</text></svg>
<div class="pin"><b>IEADC Vila Ipiranga</b>{ic("pin")}</div></div>'''

ENC = 'Encontre o seu <span class="azul">lugar</span>'
# ---------- Página inicial (ordem da Estrutura do site) ----------
prox = destaques(3)
h = f'''<section class="hero"><div class="foto" {bg("f_hero")}></div>
<div class="wrap"><div class="conteudo"><span class="eyebrow">Bem-vindo à</span><h1 class="h1 h1-home"><span class="sigla">IEADC</span><span class="nome">Vila Ipiranga</span></h1><svg class="swoosh grosso" viewBox="0 0 260 16" preserveAspectRatio="none" aria-hidden="true"><path d="M3 12C70 3 170 2 257 8"/></svg>
<p class="lead">Uma família de fé no Capão Raso, em Curitiba.</p><div class="acesso"><div class="btns-hero"><a class="bh prim" href="horarios-e-localizacao.html"><span class="bi">{ic("relogio")}</span><span class="bt"><b>Ver horários</b><small>Cultos da semana</small></span>{ic("seta")}</a><a class="bh sec" href="horarios-e-localizacao.html#como-chegar"><span class="bi">{ic("pin")}</span><span class="bt"><b>Como chegar</b><small>Endereço e mapa</small></span>{ic("seta")}</a></div><div class="script script-home">Venha nos visitar.</div></div></div></div></section>
<section class="horarios"><div class="wrap"><div class="painel"><div><span class="eyebrow">Horários</span><h2 class="h2">Venha cultuar <span class="azul">conosco</span></h2>{SWOOSH}
<p class="txt" style="margin-top:21px">Veja os dias e horários dos cultos e como chegar.</p><div class="btns">{btn("Ver horários", "horarios-e-localizacao.html")}</div></div>{dias_html()}</div></div></section>
<section class="sec" style="padding-bottom:0"><div class="wrap"><div class="faixa"><div class="foto" {bg("f_band")}></div><div class="dentro">
<div><span class="eyebrow claro">Culto dominical · 19h</span><h2 class="h2">Temas do culto dominical</h2>{SWOOSH}<div class="btns">{btn("Ver agenda", "eventos.html", "branco")}</div></div>
<ul class="temas">{"".join(f"<li><b>{tema_dia(a)}</b><span>{b}</span></li>" for a, b in TEMAS)}</ul>
<div class="vidro">{ic("calice")}<div><b>Ceia do Senhor</b><span>2º sábado do mês, às 19h30.</span></div></div></div></div></div></section>
<section class="sec glow"><div class="wrap boas"><div class="moldura"><div class="img" {bg("p_fachada")}></div>
<svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"><path d="M50 1C99 1 99 99 50 99" fill="none" stroke="url(#gr-sol)" stroke-width="5" vector-effect="non-scaling-stroke"/></svg></div>
<div><span class="eyebrow">Boas-vindas</span><h2 class="h2">A paz do Senhor!</h2>{SWOOSH}
<div class="txt" style="margin-top:21px"><p>Que alegria ter você aqui. A IEADC Vila Ipiranga é uma congregação da Assembleia de Deus em Curitiba onde a Palavra é ensinada, a oração é prioridade e cada família é recebida com carinho. Neste ano, nosso tema é "Alcançar Curitiba para Cristo. Nossa missão, nossa paixão." Confira nossos horários e venha cultuar a Deus conosco.</p></div>
<span class="aviso">Opção 2 das boas-vindas. <mark class="pend">[o pastor escolhe entre as duas opções]</mark></span>
<div class="btns">{btn("Conheça a nossa história", "sobre-nos.html", "out")}</div></div>
<ul class="valores">{"".join(f"<li>{ic(i)}{v}</li>" for i, v in VALORES)}</ul></div></section>
<section class="sec gelo curva-top"><div class="wrap">{cab("Eventos", "Próximos eventos", "Cultos especiais, confraternizações e tudo o que vem por aí.", btn("Ver agenda", "eventos.html"))}
<div class="grade g3">{"".join(ev_cartao(*e) for e in prox)}</div></div></section>
<section class="sec"><div class="wrap duplas">
<div class="chamada" {bg("p_oracao")}><span class="eyebrow claro">Aceitar Jesus</span><h2 class="h2">Quer seguir Jesus?</h2><div class="fio"></div><p class="txt">Dê o primeiro passo. Estamos aqui para caminhar com você.</p><div class="btns">{btn("Saiba como", "aceitar-jesus.html", "branco")}</div></div>
<div class="chamada azul" {bg("f_oracao_home")}><span class="eyebrow claro">Pedidos de oração</span><h2 class="h2">Podemos orar por você?</h2><div class="fio"></div><p class="txt">Envie o seu pedido. Ele chega só a quem vai orar por você.</p><div class="btns">{btn("Enviar pedido", "pedidos-de-oracao.html", "sol")}</div></div></div></section>
<section class="sec gelo"><div class="wrap">{cab("Artigos e conteúdo", "Palavra e reflexões", "Mensagens e estudos da nossa liderança.", btn("Ver conteúdos", "artigos.html"))}
<div class="grade g3">{"".join(art_cartao(a) for a in ART)}</div></div></section>
<section class="sec"><div class="wrap">{cab("Ministérios", ENC, "Conheça os ministérios da igreja e descubra onde você pode servir.", btn("Ver ministérios", "ministerios.html"))}
<div class="trilho">{"".join(f'<a class="cartao min-cartao" href="ministerios-{sl}.html"><div class="img" {bg(f)}><span class="icone">{ic(i)}</span></div><div class="corpo"><h3 class="h4">{n}</h3><p class="small">{primeira_frase(t)}</p></div></a>' for (n, i, f, _, t, _, _), sl in zip(MIN, [m["slug"] for m in MINIS]))}</div></div></section>
<section style="padding-bottom:var(--s5)"><div class="wrap"><div class="galeria-faixa"><div class="lado"><span class="eyebrow claro">Galeria</span><h2 class="h2">Momentos da nossa igreja</h2>{SWOOSH}
<p class="txt">Fotos dos cultos e dos eventos, direto do nosso Instagram.</p><div class="btns">{btn("Ver galeria", "galeria.html", "branco")}</div>
</div>
<div class="mosaico"><div {bg("f_gal1")}></div><div {bg("f_gal2")}></div><div {bg("f_gal3")}></div><div {bg("f_gal4")}></div></div></div></div></section>
<section class="sec gelo glow"><div class="wrap local"><div><span class="eyebrow">Localização</span><h2 class="h2">Onde estamos</h2>{SWOOSH}
<div class="endereco"><div>{ic("pin")}<span>{END}<br>Junto à Praça Nova República.</span></div></div>
<div class="btns">{btn("Como chegar", GM, "", "pin")}</div></div>{MAPA}</div></section>'''
page('inicio', 'Início', h)

# ---------- Sobre nós ----------
HIST = ['A IEADC Vila Ipiranga é uma das congregações da Igreja Evangélica Assembleia de Deus em Curitiba (IEADC), no bairro Capão Raso. A Assembleia de Deus chegou a Curitiba em 1929, com o pastor Bruno Skolimowski, e hoje reúne mais de 160 igrejas na cidade. Desde 2011, a IEADC é presidida pelo pastor Wagner Tadeu dos Santos Gaby.',
 'A história da Vila Ipiranga começou em 1975. Naquele ano, a Assembleia de Deus já tinha várias congregações no Campo de Curitiba, organizadas em distritos. A Vila Ipiranga ainda era um ponto de pregação e fazia parte do distrito 135, com as congregações do Capão Raso (hoje Pinheirinho), do Parque Industrial e da Santa Amélia (hoje Fazendinha), entre outras. O responsável pelo distrito era o pastor Paulino Ramalho.',
 'Ainda em 1975, a igreja comprou o primeiro terreno, onde já havia uma casinha de madeira. Ali começaram os cultos e, em 14 de agosto de 1975, nasceu a "igrejinha da Vila Ipiranga", como era conhecida. O primeiro dirigente foi o presbítero José Monteiro (in memoriam).',
 'Em 1986, foi lançada a pedra fundamental do templo atual. A cerimônia reuniu obreiros, membros da diretoria e da congregação, entre eles o pastor Catarino Nunes Pires [a confirmar a grafia] (in memoriam), o pastor Levir Bueno Polidoro e o pastor Paulino Ramalho, agora como dirigente da congregação. Durante a obra, os cultos aconteciam no porão, que hoje é a parte de baixo da igreja, onde ficam a cozinha, os banheiros e as salas da Escola Bíblica Dominical dos jovens e das crianças. O culto de inauguração do templo [ano a confirmar] contou com o pastor-presidente José Pimentel de Carvalho (in memoriam).',
 'Em 2025, a Vila Ipiranga completou 50 anos. Com gratidão, a igreja segue anunciando o evangelho no bairro e acolhendo cada pessoa que chega.']
DIRIG = ['pastor Paulino Ramalho', 'presbítero José Monteiro (in memoriam)', 'presbítero José Henrique de Oliveira', 'presbítero Nilvo Reis', 'pastor Carlos Rodrigues da Costa',
 'pastor Sérgio Luiz [sobrenome a confirmar]', 'pastor Wilton Oliveira', 'presbítero Ivan Monteiro', 'pastor Luís Carlos Alves', 'pastor Messias da Costa [sobrenome a confirmar]',
 'presbítero Jorge [sobrenome a confirmar]', 'presbítero Onofre de Oliveira', 'evangelista Esdras Souza', 'presbítero João Messias', 'presbítero [nome a confirmar] Nunes',
 'pastor Ademir Pereira da Silva', 'pastor Geziel Maciel', 'pastor Victor Marciano', 'presbítero Moisés Matias Correia', 'Ev. Robson Stein, pastor da igreja local (atual)']
MARCOS = [('1975', 'Ponto de pregação', 'Distrito 135, sob o pastor Paulino Ramalho.'), ('14/08/1975', 'A igrejinha', 'Os cultos começam na casinha de madeira.'),
          ('1986', 'Pedra fundamental', 'Começa a obra do templo atual.'), ('[ano a confirmar]', 'Inauguração', 'Culto com o pastor-presidente José Pimentel de Carvalho.'), ('2025', '50 anos', 'Meio século no Capão Raso.')]
CREDO = open(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'credo.txt')).read().strip().split('\n')
s = hero('A Igreja · Sobre nós', 'Sobre nós', HIST[0].split('. ')[0] + '.', f'<div class="btns">{btn("Nossa história", "#historia")}{btn("Declaração de fé", "#cremos", "out", "livro")}</div>', 'p_templo', 'Desde 1975.')
s += f'''<section class="sec"><div class="wrap">{cab("Linha do tempo", "Como a igreja começou e cresceu")}
<div class="linha-tempo">{"".join(f"<div class=marco><b>{P(a)}</b><span>{b}</span><p>{c}</p></div>" for a, b, c in MARCOS)}</div></div></section>
<section class="sec gelo curva-top" id="historia"><div class="wrap" style="display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:55px;align-items:start" data-cols>
<div class="leitura"><span class="eyebrow">Apresentação e história</span><h2 class="h2" style="margin-top:8px">Nossa história</h2>{SWOOSH}{"".join(f"<p>{P(x)}</p>" for x in HIST)}</div>
<div><div class="capa" style="margin-top:0;aspect-ratio:4/3;background-image:url(img/f_sobre_hist.webp)"></div><div class="cartao" style="margin-top:34px;padding:34px"><span class="eyebrow">Desde o início, cerca de 20 dirigentes conduziram a congregação</span>
<ul class="dirigentes" style="columns:1;margin-top:13px">{"".join(f"<li>{P(x)}</li>" for x in reversed(DIRIG))}</ul></div></div></div></section>
<section class="sec"><div class="wrap">{cab("Missão, visão e valores", "O que nos move")}<div class="mvv">
<div>{ic("alvo")}<h3>Missão</h3><p>Evangelizar, discipular e adorar a Deus, levando pessoas a Cristo, e transformar vidas pela Palavra.</p></div>
<div>{ic("olho")}<h3>Visão</h3><p>Ser uma igreja unida, relevante e atuante, expandindo o Reino de Deus em todas as regiões geográficas de nosso país.</p></div>
<div>{ic("maos")}<h3>Valores</h3><ul class="valores">{"".join(f"<li>{ic(i)}{v}</li>" for i, v in VALORES)}</ul></div></div></div></section>
<section class="sec gelo" id="cremos"><div class="wrap" style="display:grid;grid-template-columns:minmax(0,360px) minmax(0,1fr);gap:55px;align-items:start" data-cols>
<div style="position:sticky;top:120px"><span class="eyebrow">Declaração de fé</span><h2 class="h2" style="margin-top:8px">Cremos no que a Bíblia ensina.</h2>{SWOOSH}<p class="txt" style="margin-top:21px">Esta é a declaração de fé das Assembleias de Deus, que a IEADC Vila Ipiranga professa.</p></div>
<ol class="credo">{"".join(f"<li><span>{x}</span></li>" for x in CREDO)}</ol></div></section>'''
page('sobre-nos', 'Sobre nós', s)

# ---------- Liderança ----------
DEP = [('Assistência Social e Visita aos Lares', 'Josué Anselmo e Ester Anselmo, Jessé Otacílio Maciel'), ('Bazar', 'Adriana Paulista e equipe'),
 ('Círculo de Oração', 'Abigail Tostes Monteiro, Luciana Santos de Jesus Stein. Regentes do coral: Érica Tostes (1ª) e Raquel Oliveira (2ª)'),
 ('Compras de Materiais', 'Matheus Bernadoque, Bruno Bernadoque'), ('Crianças', 'Míriam Lima, Myrela Lima e equipe'), ('Escola Bíblica', 'Pb. Manoel Lima, Dc. Jorge Fernandez'),
 ('Eventos', 'Evelin Sena, Míriam Lima'), ('Família', 'Coop. Bruno Bernadoque e Tácila Bernadoque, Ev. Robson Stein e Luciana Stein'),
 ('Manutenção da Igreja', 'Jessé Maciel, José Luiz Oliveira'), ('Mídia/Comunicação', '[a definir]'), ('Missão/Evangelismo', 'Coop. José Luiz Nascimento, Pr. Evandro Oliveira'),
 ('Mocidade', 'Anderson Sena e Evelin Sena, Leonardo Morais da Silva'), ('Música/Instrumental', 'Coop. Adriano Martins, Jessé O. Maciel e equipe'),
 ('Patrimônio', 'Dc. Jorge Fernandez'), ('Recepção e Auxílio Logístico', 'Priscila Thuler'), ('Secretaria', 'Coop. Adriano Martins'),
 ('Som/Mídia', 'Leonardo Morais, Daniel Junior, Yohanan Maia e Michel Lima'), ('Tesouraria', 'Alex Sanderson Maia, Matheus Bernadoque')]
def pessoas(txt):
    """Separa os nomes de um departamento; 'e equipe' vira observação."""
    equipe = txt.endswith(' e equipe')
    txt = txt.replace(' e equipe', '').replace('. Regentes do coral: ', ', ').replace('ª)', 'ª regente do coral)')
    nomes = [n.strip() for parte in txt.split(', ') for n in re.split(r' e (?=[A-ZÁÉÍÓÚ])', parte) if n.strip()]
    return nomes, equipe
def lideres_lado(txt):
    if txt.startswith('['): return f'<p style="margin-top:8px;color:#fff;font:600 19px/1.4 var(--f-tit)">{P(txt)}</p>'
    nomes, equipe = pessoas(txt)
    li = ''.join(f'<li><span class="foto-lider" title="Foto em breve">{ic("pessoa")}</span><span>{P(n)}</span></li>' for n in nomes)
    return f'<ul class="lideres-lado">{li}</ul>' + ('<p class="equipe">e equipe</p>' if equipe else '')
def dep_card(d, p):
    if p.startswith('['): return f'<div class="dep"><h3 class="h4">{d}</h3><p>{P(p)}</p></div>'
    nomes, equipe = pessoas(p)
    fotos = ''.join(f'<li><span class="foto-lider" title="Foto em breve">{ic("pessoa")}</span><span>{P(n)}</span></li>' for n in nomes)
    return f'<div class="dep"><h3 class="h4">{d}</h3><ul class="pessoas">{fotos}</ul>{"<p>e equipe</p>" if equipe else ""}</div>'
l = hero('A Igreja · Liderança', 'Liderança', 'Conheça quem serve à frente da IEADC Vila Ipiranga.')
l += f'''<section class="sec"><div class="wrap">{cab("Liderança local", "Pastoreio")}<div class="grade g2">
<div class="lider"><span class="avatar">RS</span><div><span class="eyebrow">Pastor da igreja local</span><h3 class="h3" style="margin-top:8px">Ev. Robson Stein</h3><p class="note" style="margin-top:8px">Foto <mark class="pend">[a confirmar]</mark></p></div></div>
<div class="lider"><span class="avatar">ML</span><div><span class="eyebrow">2º dirigente</span><h3 class="h3" style="margin-top:8px">Pb. Manoel Lima</h3><p class="note" style="margin-top:8px">Foto <mark class="pend">[a confirmar]</mark></p></div></div></div></div></section>
<section class="sec gelo curva-top"><div class="wrap">{cab("Departamentos", "Líderes de cada departamento")}
<p class="note" style="margin:var(--s3) 0 var(--s4)">As fotos dos líderes serão publicadas em breve.</p>
<div class="grade g3">{"".join(dep_card(d, p) for d, p in DEP)}</div></div></section>'''
page('lideranca', 'Liderança', l)

# ---------- Ministérios ----------
ATUAL = ' class="on" aria-current="page"'
def trocador(atual):
    itens = ''.join(f'<a href="ministerios-{m["slug"]}.html"{ATUAL if m["slug"] == atual else ""}>{ic(m["icone"])}<span>{m["nome"]}</span></a>' for m in MINIS)
    return f'<nav class="trocar" aria-label="Escolha um ministério"><div class="wrap"><span class="rot">Ministérios</span><div class="trilha">{itens}</div></div></nav>'
CORES = ['c1', 'c2', 'c3', 'c4', 'c5', 'c6', 'c7']
m = hero('A Igreja · Ministérios', 'Ministérios', 'Cada ministério cuida de uma parte da vida da igreja. Conheça e venha participar.', f'<div class="btns">{btn("Fale conosco", "contato.html")}</div>', 'f_min_hero', 'Encontre o seu lugar.')
m += trocador('')
m += f'''<section class="sec"><div class="wrap">{cab("Conheça cada ministério", "Escolha um ministério", "Toque em um ministério para ver como ele funciona, quando se reúne e como participar.")}
<div class="min-grade">{"".join(f'<a class="min-tile {c}" href="ministerios-{x["slug"]}.html"><span class="ic">{ic(x["icone"])}</span><span class="chip">{ic("relogio")}{x["fatos"][0][2]}</span><h3>{x["nome"]}</h3><p>{P(primeira_frase(x["curto"]))}</p><span class="mais">Conhecer o ministério{ic("seta")}</span></a>' for x, c in zip(MINIS, CORES))}</div></div></section>'''
page('ministerios', 'Ministérios', m)

def pagina_ministerio(k, x):
    ant, prox_ = MINIS[k - 1], MINIS[(k + 1) % len(MINIS)]
    fatos = ''.join(f'<div class="fato">{ic(i)}<span class="r">{r}</span><b>{P(v)}</b></div>' for i, r, v in x['fatos'])
    funciona = ''.join(f'<p>{P(t)}</p>' for t in x['funciona'])
    objetivos = ''.join(f'<li>{ic("ok")}<span>{P(o)}</span></li>' for o in x['objetivos'])
    encontros = ''.join(f'<li><b>{P(q)}</b><span>{P(o)}</span></li>' for q, o in x['encontros'])
    passos = ''.join(f'<li><span class="num">{n}</span><p>{P(t)}</p></li>' for n, t in enumerate(x['passos'], 1))
    passos_html = (f'<div class="passo-unico">{ic("pessoas")}<p>{P(x["passos"][0])}</p><a class="btn" href="contato.html">Fale conosco{ic("seta")}</a></div>' if len(x['passos']) == 1 else f'<ol class="passos">{passos}</ol>')
    if x['fotos']:
        slides = ''.join(f'<figure class="slide" {bg(f)}><figcaption>{x["nome"]} · foto {n} de {len(x["fotos"])}</figcaption></figure>' for n, f in enumerate(x['fotos'], 1))
        pontos = ''.join(f'<button type="button" aria-label="Foto {n}"{ON if n == 1 else ""}></button>' for n in range(1, len(x['fotos']) + 1))
        galeria = f'''<section class="sec gelo curva-top" id="fotos-{x["slug"]}"><div class="wrap">{cab("Galeria", "Fotos do ministério", "", '<div class="car-ctrl"><button type="button" class="car-ant" aria-label="Foto anterior">' + ic("esq") + '</button><button type="button" class="car-prox" aria-label="Próxima foto">' + ic("dir") + '</button></div>')}
<div class="carrossel" data-carrossel><div class="pista">{slides}</div><div class="pontos">{pontos}</div></div></div></section>'''
    else:
        galeria = f'''<section class="sec gelo curva-top" id="fotos-{x["slug"]}"><div class="wrap">{cab("Galeria", "Fotos do ministério")}
<div class="cartao sem-fotos">{ic("escudo")}<p class="txt">As fotos das crianças só são publicadas com a autorização de imagem dos pais ou responsáveis. <mark class="pend">[fotos autorizadas a confirmar]</mark></p></div></div></section>'''
    sl = x['slug']
    corpo = hero(f'Ministérios · {x["nome"]}', x['nome'], x['curto'], f'<div class="btns">{btn("Como participar", "#participar-" + sl)}{btn("Ver fotos", "#fotos-" + sl, "out", "dir")}</div>', x['foto'], migalha='<a href="ministerios.html">Ministérios</a> › ' + x['nome'])
    corpo += trocador(sl)
    corpo += f'''<section class="sec" style="padding-bottom:var(--s6)"><div class="wrap"><div class="fatos">{fatos}</div></div></section>
<section class="sec" style="padding-top:0"><div class="wrap min-corpo"><article class="leitura min-texto">
<span class="aviso">Texto-base para a liderança do ministério revisar. Trechos em amarelo aguardam confirmação.</span>
<h2 class="h2" id="funciona-{sl}">Como funciona</h2>{SWOOSH}{funciona}
<h2 class="h2" id="porque-{sl}">Por que existe</h2>{SWOOSH}<blockquote class="versiculo">{x["verso"][0]}<cite>{x["verso"][1]} (ARA)</cite></blockquote><p>{x["porque"]}</p>
<h2 class="h2" id="paraque-{sl}">Para quê</h2>{SWOOSH}<ul class="objetivos">{objetivos}</ul>
<h2 class="h2" id="quando-{sl}">Quando nos encontramos</h2>{SWOOSH}<ul class="encontros">{encontros}</ul>
</article><aside class="min-lado"><div class="indice"><span class="eyebrow">Nesta página</span><a href="#funciona-{sl}">Como funciona</a><a href="#porque-{sl}">Por que existe</a><a href="#paraque-{sl}">Para quê</a><a href="#quando-{sl}">Quando nos encontramos</a><a href="#fotos-{sl}">Fotos</a><a href="#participar-{sl}">Como participar</a></div>
<div class="lateral"><span class="eyebrow claro">Quem lidera</span>{lideres_lado(x["fatos"][3][2])}<div class="btns">{btn("Fale conosco", "contato.html", "branco")}</div></div></aside></div></section>
{galeria}
<section class="sec" id="participar-{sl}"><div class="wrap">{cab("Como participar", "Dê o primeiro passo")}{passos_html}</div></section>
<section style="padding-bottom:var(--secao)"><div class="wrap"><div class="vizinhos"><a href="ministerios-{ant["slug"]}.html"><span class="ic">{ic("esq")}</span><span><small>Ministério anterior</small><b>{ant["nome"]}</b></span></a>
<a href="ministerios.html" class="todos">{ic("pessoas")}<span>Todos os ministérios</span></a>
<a href="ministerios-{prox_["slug"]}.html" class="dir"><span><small>Próximo ministério</small><b>{prox_["nome"]}</b></span><span class="ic">{ic("dir")}</span></a></div></div></section>'''
    page('ministerios-' + sl, x['nome'], corpo, 'ministerios')
for k, x in enumerate(MINIS): pagina_ministerio(k, x)

# ---------- Cultos: uma página por culto fixo ----------
def trocador_cultos(atual):
    itens = ''.join(f'<a href="{c["slug"]}.html"{ATUAL if c["slug"] == atual else ""}>{ic(c["icone"])}<span>{c["nome"]}</span></a>' for c in CULTOS)
    return f'<nav class="trocar" aria-label="Escolha um culto"><div class="wrap"><span class="rot">Cultos</span><div class="trilha">{itens}</div></div></nav>'
def proximas(regras, n=4):
    out, d = [], HOJE
    while len(out) < n and d < HOJE + timedelta(400):
        for h, t, c, _ in eventos_do_dia(d):
            if any(t == r or t.startswith(r + ':') for r in regras): out.append((d, h, t))
        d += timedelta(1)
    return out
def carrossel(nome, fotos, ancora):
    slides = ''.join(f'<figure class="slide" {bg(f)}><figcaption>{nome} · foto {n} de {len(fotos)}</figcaption></figure>' for n, f in enumerate(fotos, 1))
    pontos = ''.join(f'<button type="button" aria-label="Foto {n}"{ON if n == 1 else ""}></button>' for n in range(1, len(fotos) + 1))
    ctrl = '<div class="car-ctrl"><button type="button" class="car-ant" aria-label="Foto anterior">' + ic("esq") + '</button><button type="button" class="car-prox" aria-label="Próxima foto">' + ic("dir") + '</button></div>'
    return f'''<section class="sec gelo curva-top" id="{ancora}"><div class="wrap">{cab("Galeria", "Fotos", "", ctrl)}
<div class="carrossel" data-carrossel><div class="pista">{slides}</div><div class="pontos">{pontos}</div></div></div></section>'''
def pagina_culto(k, x):
    ant, prox_ = CULTOS[k - 1], CULTOS[(k + 1) % len(CULTOS)]
    sl = x['slug']
    fatos = ''.join(f'<div class="fato">{ic(i)}<span class="r">{r}</span><b>{P(v)}</b></div>' for i, r, v in x['fatos'])
    funciona = ''.join(f'<p>{P(t)}</p>' for t in x['funciona'])
    esperar = ''.join(f'<li>{ic("ok")}<span>{P(o)}</span></li>' for o in x['esperar'])
    datas = ''.join(f'<li><b>{DSEM[d.weekday()].capitalize()}, {d.day} de {MESES[d.month]}</b><span>{P(h)} · {t}{" · hoje" if d == HOJE else ""}</span></li>' for d, h, t in proximas(x['regra']))
    passos = ''.join(f'<li><span class="num">{n}</span><p>{P(t)}</p></li>' for n, t in enumerate(x['passos'], 1))
    extra = ''
    if sl == 'culto-dominical':
        ligas = {'Família': 'ministerios-familia', 'Missões e Evangelismo': 'ministerios-missao-e-evangelismo', 'Jovens e Adolescentes': 'ministerios-mocidade', 'Crianças': 'ministerios-criancas'}
        extra = '<h2 class="h2" id="temas-' + sl + '">Temas do mês</h2>' + SWOOSH + '<ul class="encontros">' + ''.join(
            f'<li><b>{a}</b><span>{f"<a class=lk href={ligas[b]}.html>{b}</a>" if b in ligas else b}</span></li>' for a, b in TEMAS) + '</ul>'
    corpo = hero(f'Cultos · {x["nome"]}', x['nome'], x['curto'], f'<div class="btns">{btn("Próximas datas", "#datas-" + sl, "", "cal")}{btn("Como participar", "#participar-" + sl, "out")}</div>', x['foto'],
                 migalha='<a href="horarios-e-localizacao.html">Horários</a> › ' + x['nome'])
    corpo += trocador_cultos(sl)
    corpo += f'''<section class="sec" style="padding-bottom:var(--s6)"><div class="wrap"><div class="fatos">{fatos}</div></div></section>
<section class="sec" style="padding-top:0"><div class="wrap min-corpo"><article class="leitura min-texto">
<span class="aviso">Texto-base para o pastor revisar. Trechos em amarelo aguardam confirmação.</span>
<h2 class="h2" id="como-{sl}">Como é o culto</h2>{SWOOSH}{funciona}
<h2 class="h2" id="porque-{sl}">Por que nos reunimos</h2>{SWOOSH}<blockquote class="versiculo">{x["verso"][0]}<cite>{x["verso"][1]} (ARA)</cite></blockquote><p>{x["porque"]}</p>
<h2 class="h2" id="esperar-{sl}">O que esperar</h2>{SWOOSH}<ul class="objetivos">{esperar}</ul>{extra}
<h2 class="h2" id="datas-{sl}">Próximas datas</h2>{SWOOSH}<ul class="encontros">{datas}</ul>
</article><aside class="min-lado"><div class="indice"><span class="eyebrow">Nesta página</span><a href="#como-{sl}">Como é o culto</a><a href="#porque-{sl}">Por que nos reunimos</a><a href="#esperar-{sl}">O que esperar</a><a href="#datas-{sl}">Próximas datas</a><a href="#fotos-{sl}">Fotos</a><a href="#participar-{sl}">Como participar</a></div>
<div class="lateral"><span class="eyebrow claro">Endereço</span><p style="margin-top:8px;color:#fff;font:600 17px/1.45 var(--f-tit)">{END}</p><div class="btns">{btn("Como chegar", GM, "branco", "pin")}</div></div></aside></div></section>
{carrossel(x["nome"], x["fotos"], "fotos-" + sl)}
<section class="sec" id="participar-{sl}"><div class="wrap">{cab("Como participar", "Venha cultuar conosco")}<ol class="passos">{passos}</ol></div></section>
<section style="padding-bottom:var(--secao)"><div class="wrap"><div class="vizinhos"><a href="{ant["slug"]}.html"><span class="ic">{ic("esq")}</span><span><small>Culto anterior</small><b>{ant["nome"]}</b></span></a>
<a href="horarios-e-localizacao.html" class="todos">{ic("relogio")}<span>Todos os horários</span></a>
<a href="{prox_["slug"]}.html" class="dir"><span><small>Próximo culto</small><b>{prox_["nome"]}</b></span><span class="ic">{ic("dir")}</span></a></div></div></section>'''
    page(sl, x['nome'], corpo, 'horarios-e-localizacao')
for k, x in enumerate(CULTOS): pagina_culto(k, x)

# ---------- Horários e localização ----------
SEM_T = [('Segunda-feira', 'Culto de Oração', '20h'), ('Quarta-feira', 'Culto de Ensino', '20h'), ('Quinta-feira', 'Culto dirigido pelo Círculo de Oração', '20h'),
         ('Sexta-feira', 'Ensinai (Curso de Teologia Ministerial)', '19h30'), ('Domingo', 'Café comunitário', '9h'), ('Domingo', 'EBD (Escola Bíblica Dominical)', '9h30'), ('Domingo', 'Culto dominical', '19h')]
MEN_T = [('2º sábado do mês', 'Ceia do Senhor', '19h30'), ('Sexta-feira que antecede a ceia (meses ímpares)', 'Vigília', '22h'), ('Sábado da ceia, pela manhã (meses pares)', 'Consagração', '8h')]
def tabela(cab_, linhas): return f'<div class="tabela"><table><tr>{"".join(f"<th>{c}</th>" for c in cab_)}</tr>{"".join("<tr>" + "".join(f"<td>{c}</td>" for c in r) + "</tr>" for r in linhas)}</table></div>'
hl = hero('Horários e localização', 'Horários e localização', 'Você é bem-vindo em qualquer culto. Não precisa avisar antes: é só chegar.', f'<div class="btns">{btn("Como chegar", "#como-chegar", "", "pin")}</div>', 'f_hor_hero', 'É só chegar.')
hl += f'''<section class="horarios"><div class="wrap"><div class="painel"><div><span class="eyebrow">Toda semana</span><h2 class="h2">Programação fixa semanal</h2>{SWOOSH}</div>{dias_html()}</div></div></section>
<section class="sec"><div class="wrap">{cab("Conheça cada culto", "Como são os nossos cultos", "Toque em um culto para ver como ele é, o que esperar e as próximas datas.")}
<div class="min-grade">{"".join(f'<a class="min-tile {c}" href="{x["slug"]}.html"><span class="ic">{ic(x["icone"])}</span><span class="chip">{ic("relogio")}{x["fatos"][0][2] if x["slug"] != "vigilia-e-consagracao" else "Uma vez por mês"}</span><h3>{x["nome"]}</h3><p>{x["curto"]}</p><span class="mais">Conhecer o culto{ic("seta")}</span></a>' for x, c in zip(CULTOS, CORES))}</div></div></section>
<section class="sec" style="padding-top:0"><div class="wrap grade g2" style="gap:55px;align-items:start"><div>{cab("Toda semana", "Dias e horários")}{tabela(["Dia", "Evento", "Horário"], SEM_T)}</div>
<div>{cab("Todo mês", "Programação mensal")}{tabela(["Quando", "Evento", "Horário"], MEN_T)}<p class="note" style="margin-top:21px">Vigília e consagração se revezam mês a mês: nos meses pares há consagração e, nos ímpares, vigília.</p></div></div></section>
<section><div class="wrap"><div class="faixa"><div class="foto" {bg("p_ensino")}></div><div class="dentro">
<div><span class="eyebrow claro">Culto dominical · 19h</span><h2 class="h2">Temas do culto dominical</h2>{SWOOSH}</div>
<ul class="temas">{"".join(f"<li><b>{tema_dia(a)}</b><span>{b}</span></li>" for a, b in TEMAS)}</ul><div class="vidro">{ic("calice")}<div><b>Ceia do Senhor</b><span>2º sábado do mês, às 19h30.</span></div></div></div></div></div></section>
<section class="sec" id="como-chegar"><div class="wrap local"><div><span class="eyebrow">Como chegar</span><h2 class="h2">Onde estamos</h2>{SWOOSH}
<div class="endereco"><div>{ic("pin")}<span>{END}<br>Estamos junto à Praça Nova República.</span></div>
<div>{ic("onibus")}<span>De ônibus, as linhas 030 Interbairros III, 625 Gramados e 665 Vila Rex param a 5 ou 6 minutos a pé da igreja, nas ruas Bortolo Gusso e Professor João Mazzarotto. Os terminais Pinheirinho e Capão Raso ficam a cerca de 1,5 km.</span></div></div>
<div class="linhas"><span>{ic("onibus")}030 Interbairros III</span><span>{ic("onibus")}625 Gramados</span><span>{ic("onibus")}665 Vila Rex</span></div>
<div class="btns">{btn("Abrir no Google Maps", GM, "", "pin")}</div></div>{MAPA}</div></section>'''
page('horarios-e-localizacao', 'Horários e localização', hl)

# ---------- Eventos ----------
LEG = [('c-culto', 'Cultos da semana'), ('c-ensino', 'Ensino e EBD'), ('c-domingo', 'Domingo'), ('c-ceia', 'Ceia do Senhor'), ('c-vigilia', 'Vigília e consagração'), ('c-jovens', 'Mocidade')]
e = hero('Agenda', 'Eventos', 'Cultos especiais, confraternizações e eventos da igreja. Todos são bem-vindos.', f'<div class="btns">{btn("Ver horários", "horarios-e-localizacao.html", "out", "relogio")}</div>', 'f_eventos_hero')
e += f'''<section class="sec"><div class="wrap">{cab("Próximos eventos", "Em destaque")}<div class="grade g3">{"".join(ev_cartao(*x) for x in destaques(6))}</div></div></section>
<section class="sec gelo curva-top"><div class="wrap">{cab("Agenda", "Cultos, confraternizações e eventos", "Agenda montada a partir da programação fixa semanal, dos temas do culto dominical e da programação mensal.")}
<div class="legenda">{"".join(f"<span><i class={c}></i>{t}</span>" for c, t in LEG)}</div>{mes_html(2026, 10)}{mes_html(2026, 11)}{mes_html(2026, 12)}
<div class="grade g2" style="margin-top:55px"><div class="cartao" style="padding:34px"><span class="eyebrow">Confraternizações e eventos dos departamentos</span><p class="txt" style="margin-top:13px">As datas das confraternizações e eventos de cada departamento são apresentadas pelas lideranças na reunião de início de ano. <mark class="pend">[datas de 2027 a confirmar]</mark></p></div>
<div class="cartao" style="padding:34px;border-style:dashed"><span class="eyebrow">Quando a agenda estiver vazia</span><p class="txt" style="margin-top:13px">Nenhum evento especial marcado por enquanto. Os cultos da semana seguem normalmente.</p></div></div></div></section>'''
page('eventos', 'Eventos', e)

# ---------- Aceitar Jesus ----------
aj = hero('Aceitar Jesus', 'Seja muito bem-vindo à família de Deus!', 'Que bom que você chegou até aqui. Esta página explica, com calma, o que significa aceitar Jesus e como dar esse passo.', '', 'f_aceitar_hero', 'Você não está sozinho.')
# Texto-base escrito a pedido do Felipe (09/10/2026) para o pastor revisar; versículos na ARA.
AJ_PASSOS = [('coracao', 'Deus ama você', 'Antes de qualquer coisa, saiba disto: Deus conhece você pelo nome e ama você como você é. Foi por amor que Ele enviou Jesus.', ('Porque Deus amou ao mundo de tal maneira que deu o seu Filho unigênito, para que todo o que nele crê não pereça, mas tenha a vida eterna.', 'João 3.16')),
 ('pessoas', 'Todos precisamos de Deus', 'A Bíblia chama de pecado tudo o que nos afasta de Deus. Ninguém consegue se aproximar dEle por esforço próprio, e é isso que deixa o coração vazio, por mais que a vida pareça cheia.', ('Pois todos pecaram e carecem da glória de Deus.', 'Romanos 3.23')),
 ('igreja', 'Jesus fez o caminho', 'Jesus morreu na cruz em nosso lugar e ressuscitou ao terceiro dia. Ele pagou o preço que nós não podíamos pagar e abriu o caminho de volta para Deus.', ('Porque o salário do pecado é a morte, mas o dom gratuito de Deus é a vida eterna em Cristo Jesus, nosso Senhor.', 'Romanos 6.23')),
 ('maos', 'A salvação é um presente', 'Você não precisa merecer nem mudar sozinho antes de vir. Aceitar Jesus é receber esse presente pela fé: crer nEle de coração e confessá-lo como Senhor da sua vida.', ('Se, com a tua boca, confessares Jesus como Senhor e, em teu coração, creres que Deus o ressuscitou dentre os mortos, serás salvo.', 'Romanos 10.9'))]
AJ_MUDA = ['Seus pecados são perdoados e você ganha a vida eterna', 'Você passa a ter uma relação pessoal com Deus, por meio da oração e da Palavra', 'Você começa uma vida nova, com Jesus guiando seus passos', 'Você ganha uma família na fé, que vai caminhar ao seu lado']
aj += f'''<section class="sec glow"><div class="wrap"><div class="aj-intro">{cab("O que é aceitar Jesus", "Uma decisão que muda a vida")}
<p class="txt">Aceitar Jesus não é entrar para uma religião nem seguir uma lista de regras. É começar um relacionamento com Deus, que ama você e quer estar perto. Veja, em quatro passos, o que a Bíblia diz.</p></div>
<ol class="aj-passos">{"".join(f'<li><span class="num">{k + 1}</span><div><h3 class="h4">{t}</h3><p>{d}</p><blockquote class="versiculo">{v[0]}<cite>{v[1]} (ARA)</cite></blockquote></div></li>' for k, (i_, t, d, v) in enumerate(AJ_PASSOS))}</ol>
<div class="aj-muda"><h2 class="h3">O que muda quando você aceita Jesus</h2><ul class="objetivos">{"".join(f'<li>{ic("ok")}<span>{m}</span></li>' for m in AJ_MUDA)}</ul>
<blockquote class="versiculo">E, assim, se alguém está em Cristo, é nova criatura; as coisas antigas já passaram; eis que se fizeram novas.<cite>2 Coríntios 5.17 (ARA)</cite></blockquote></div>
<details class="aj-decisao"><summary><span class="aj-pergunta"><span class="eyebrow claro">Por que hoje</span><span class="h3">Você não precisa ter todas as respostas para dar o primeiro passo.</span><span class="aj-sub">Se o seu coração deseja seguir Jesus, este é o momento. Ele está de braços abertos para receber você.</span></span><span class="btn sol aj-btn">Quero aceitar Jesus{ic("seta")}</span></summary>
<div class="carta"><span class="eyebrow">Uma palavra do pastor</span>
<p>Quero me alegrar com você por essa decisão tão especial de entregar sua vida a Jesus. Hoje começa uma nova caminhada, e quero que saiba: você não está sozinho.</p>
<p>Busque a Deus em oração, conheça Sua Palavra e permita que Ele conduza seus passos. Nós, da IEADC Vila Ipiranga, estamos de braços abertos para receber você e caminhar ao seu lado.</p>
<p>Seja bem-vindo! Que Deus abençoe sua nova caminhada com Cristo.</p><p>Um grande abraço,</p><div class="ass">Ev. Robson</div></div></details></div></section>
'''
aj += f'''<section style="padding-bottom:clamp(89px,10vw,144px)"><div class="wrap duplas"><div class="chamada" {bg("f_g05")}><h2 class="h2">Quer conversar com alguém da igreja?</h2><div class="fio"></div><div class="btns">{btn("Fale conosco", "contato.html", "branco")}</div></div>
<div class="chamada azul" {bg("f_g06")}><h2 class="h2">Venha nos visitar.</h2><div class="fio"></div><div class="btns">{btn("Ver horários", "horarios-e-localizacao.html", "sol")}</div></div></div></section>'''
page('aceitar-jesus', 'Aceitar Jesus', aj)

# ---------- Artigos e conteúdo ----------
ar = hero('Conteúdo', 'Artigos e conteúdo', 'Mensagens, reflexões e estudos escritos e gravados pela liderança da igreja.', '', 'f_midia_hero')
ar += f'''<section class="sec"><div class="wrap">{cab("Publicações", "Palavra e reflexões", "Os três textos abaixo são exemplos para mostrar o modelo da página. Cada publicação é aprovada pelo pastor antes de ir ao ar.")}
<div class="grade g3">{"".join(art_cartao(a) for a in ART)}</div></div></section>
<section class="sec gelo curva-top"><div class="wrap">{cab("Textos, áudios e vídeos", "Outros formatos")}<div class="grade g3">
<div class="cartao" style="padding:34px"><span class="chip">{ic("fone-ouvido")}Áudios</span><h3 class="h3" style="margin-top:13px">Mensagens em áudio</h3><p class="txt" style="margin-top:8px"><mark class="pend">[a confirmar]</mark></p></div>
<div class="cartao" style="padding:34px;border-style:dashed"><span class="eyebrow">Quando ainda não houver publicações</span><p class="txt" style="margin-top:13px">Em breve, mensagens e estudos da nossa liderança.</p></div></div></div></section>'''
page('artigos', 'Artigos e conteúdo', ar)
for a in ART:
    slug, cat, tit, foto, resumo, blocos = a
    corpo = ''
    for b in blocos:
        if b[0] == 'v': corpo += f'<blockquote class="versiculo">{b[1]}<cite>{b[2]} (ARA)</cite></blockquote>'
        elif b[0] == 'h': corpo += f'<h2>{b[1]}</h2>'
        else: corpo += f'<p>{b[1]}</p>'
    outros = [x for x in ART if x[0] != slug]
    pg = f'''<section class="sec glow" style="padding-bottom:0"><div class="wrap"><div class="artigo"><div class="migalha"><a href="artigos.html">Artigos e conteúdo</a> › {cat}</div>
<div style="display:flex;gap:8px;flex-wrap:wrap"><span class="chip">{ic("texto")}{cat}</span><span class="chip ex">Artigo de exemplo</span></div>
<h1 class="h1" style="margin-top:13px">{tit}</h1>{SWOOSH}<p class="lead" style="margin-top:21px">{resumo}</p>
<div class="autor"><span class="avatar">?</span><span>Autor <mark class="pend">[a confirmar]</mark> · <mark class="pend">[data da publicação]</mark></span></div></div>
<div class="capa" {bg(foto)}></div></div></section>
<section class="sec" style="padding-top:var(--s6)"><div class="wrap"><article class="artigo corpo-art">{corpo}</article></div></section>
<section class="sec gelo curva-top"><div class="wrap">{cab("Continue lendo", "Outras publicações")}<div class="grade g2">{"".join(art_cartao(x) for x in outros)}</div></div></section>'''
    page(slug, tit, pg, 'artigos')

# ---------- Galeria ----------
GAL = ['f_g01', 'f_g02', 'f_g03', 'f_g04', 'f_g07', 'f_g08', 'f_g09', 'f_g10', 'f_g11', 'f_g12']
g = hero('Conteúdo · Galeria', 'Galeria de fotos e vídeos', 'Momentos da nossa igreja: cultos, eventos e comunhão.', f'<div class="btns">{btn("Seguir no Instagram", IG, "", "insta")}</div>', 'f_galeria_hero')
g += f'''<section class="sec"><div class="wrap">{cab("@ieadc.vilaipiranga", "Momentos da nossa igreja", "As fotos vêm do Instagram da igreja.")}
<div class="fotos">{"".join(f"<div {bg(x)}></div>" for x in GAL)}</div>
<p class="note" style="margin-top:21px">Fotos de crianças e adolescentes seguem a autorização de imagem dos pais ou responsáveis.</p></div></section>'''
page('galeria', 'Galeria de fotos e vídeos', g)

# ---------- Contato ----------
def campo(i, l, area=False, req=True):
    tag = f'<textarea id="{i}"{" required" if req else ""}></textarea>' if area else f'<input id="{i}"{" required" if req else ""}>'
    return f'<div class="campo"><label for="{i}">{l}</label>{tag}</div>'
c = hero('Contato', 'Contato', 'Quer falar com a igreja? Mande uma mensagem pelo formulário ou use um dos contatos abaixo.', '', None)
c += f'''<section class="sec"><div class="wrap" style="display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:55px;align-items:start" data-cols>
<form class="form" data-ok="ok-contato"><div><span class="eyebrow">Formulário</span><h2 class="h2" style="margin-top:8px">Mande uma mensagem</h2>{SWOOSH}</div>
{campo("c-nome", "Nome")}{campo("c-contato", "E-mail ou telefone")}{campo("c-msg", "Mensagem", True)}
<div><button class="btn" type="submit">Enviar mensagem{ic("seta")}</button></div>
<div class="msg" id="ok-contato" hidden>{ic("ok")}<span>Mensagem enviada. Obrigado pelo contato! Vamos responder assim que possível.</span></div>
<div class="msg erro">{ic("erro")}<span><b>Mensagem de erro (exemplo)</b>Não foi possível enviar. Confira os campos e tente de novo.</span></div></form>
<aside class="lateral"><span class="eyebrow claro">Contatos</span><h2 class="h3" style="margin-top:8px">Fale com a igreja</h2>
<div class="contato-lista"><div>{ic("fone")}<p><b>1° dirigente: Ev. Robson Stein</b><span>+55 (41) 98838-1994<br>ieadcvilaipiranga@gmail.com</span></p></div>
<div>{ic("fone")}<p><b>2º dirigente: Pb. Manoel Lima</b><span>+55 (41) 98444-5001<br>E-mail <mark class="pend">[a definir]</mark></span></p></div>
<div>{ic("pin")}<p><b>Endereço</b><span>{END}</span></p></div>
<div>{ic("insta")}<p><b>Redes sociais</b><span><a href="{IG}">Instagram</a> · <a href="{FB}">Facebook</a> · <a href="{YT}">YouTube</a></span></p></div></div></aside></div></section>
<section style="padding-bottom:clamp(89px,10vw,144px)"><div class="wrap"><div class="capa" style="margin-top:0;background-image:url(img/f_contato_hero.webp)"></div></div></section>'''
page('contato', 'Contato', c)

# ---------- Pedidos de oração ----------
po = hero('Pedidos de oração', 'Podemos orar por você.', 'Escreva o seu pedido abaixo. Ele chega apenas ao Círculo de Oração e nunca é publicado.', '', 'f_pedidos_hero', 'Orai uns pelos outros.')
po += f'''<section class="sec glow"><div class="wrap" style="display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:55px;align-items:start" data-cols>
<form class="form" data-ok="ok-pedido">{campo("p-nome", "Seu nome (pode ser só o primeiro nome)")}{campo("p-pedido", "Seu pedido", True)}
<p class="note">Evite citar nomes ou detalhes de outras pessoas sem que elas saibam.</p>
<div class="campo"><label>Quer que alguém da igreja entre em contato com você?</label><div class="opcoes"><label><input type="radio" name="contato" id="p-sim">Sim</label><label><input type="radio" name="contato" id="p-nao" checked>Não</label></div></div>
{campo("p-fone", "Telefone ou e-mail (só se quiser contato)", req=False)}
<label class="aceite" for="p-ok"><input type="checkbox" id="p-ok" required><span>Autorizo a IEADC Vila Ipiranga a receber este pedido para oração, conforme a <a href="politica-de-privacidade.html" style="color:var(--royal);text-decoration:underline">política de privacidade</a>.</span></label>
<div><button class="btn" type="submit">Enviar pedido{ic("seta")}</button></div>
<div class="msg" id="ok-pedido" hidden>{ic("ok")}<span>Recebemos o seu pedido. Estaremos orando por você.</span></div>
<div class="msg erro">{ic("erro")}<span><b>Mensagem de erro (exemplo)</b>Não foi possível enviar. Confira os campos e tente de novo.</span></div></form>
<aside class="lateral"><span class="eyebrow claro">Quem ora por você</span><h2 class="h3" style="margin-top:8px">Círculo de Oração</h2>
<p style="margin-top:13px;color:#D6E2EE;font:400 17px/26px var(--f-txt)">Reúne as irmãs da igreja para orar pela congregação, pelas famílias e pelos pedidos recebidos. Dirige o culto de quinta-feira, às 20h.</p>
<blockquote class="versiculo" style="background:rgba(255,255,255,.1);color:#fff;margin-top:34px">Orai uns pelos outros, para serdes curados.<cite style="color:var(--amarelo)">Tiago 5.16 (ARA)</cite></blockquote></aside></div></section>'''
page('pedidos-de-oracao', 'Pedidos de oração', po)

# ---------- Política de privacidade ----------
POL = open(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'politica.txt')).read().strip().split('\n')
itens = ''.join(f'<h3>{t}.</h3><p>{r}</p>' for t, _, r in (x.partition('. ') for x in POL[1:]))
pp = hero('Política de privacidade', 'Política de privacidade', 'Última atualização: [data da publicação]')
pp += f'<section class="sec"><div class="wrap"><div class="leitura"><p style="margin-top:0">{POL[0]}</p>{itens}</div></div></section>'
page('politica-de-privacidade', 'Política de privacidade', pp)
print('ok', len(PAGES))
