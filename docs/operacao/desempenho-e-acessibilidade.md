# Desempenho e acessibilidade (fase 3)

Como medir, o que o tema já faz e o que depende do servidor. Escrito em 16/11/2026, no fim da fase 3 (`feat/site-a11y-perf`); atualizado na fase 6 (seção 6: celular a 100).

## 1. Acessibilidade (WCAG 2.1 nível AA, mais o tamanho do alvo de toque do 2.2) com o axe

```bash
export IEADC_CHROMIUM='C:\Users\alenc\AppData\Local\ms-playwright\chromium_headless_shell-1161\chrome-win\headless_shell.exe'   # Windows
./bin/testar.sh a11y                # tudo: 113 testes
./bin/testar.sh a11y contato        # só uma página (as de mapa-secoes.json)
```

`tests/visual/a11y.spec.js` (usa `@axe-core/playwright` 4.14.0, versão fixa) faz:

- **axe nas 28 páginas, a 390, 900 e 1440 px** (84 testes), com as regras `wcag2a`, `wcag2aa`, `wcag21a`, `wcag21aa`, `wcag22aa` (traz o `target-size`, alvo de toque de 24 px, que o Lighthouse cobra) e `best-practice`. A página é rolada até o fim antes (as fotos de baixo só carregam perto da tela). Se falhar, a mensagem lista, por regra: impacto, seletor, trecho do HTML e o motivo.
- **Agenda em outras datas** (18 testes): início, eventos e horários a 390 e 900 px com as datas 16/11/2026, 24/12/2026 e 10/01/2027. Os testes de fidelidade fixam 06/10/2026, e o "hoje" da agenda muda de aparência conforme o dia (o Lighthouse, que roda com a data real, achou um laranja de 2,6:1 que o axe com data fixa não via).
- **Teclado** (1440 px): o primeiro Tab leva ao link "Pular para o conteúdo", que aparece na tela e leva a `<main id="main">`; em 4 páginas, até 60 Tabs sem foco em elemento invisível, sem controle sem nome, sem `tabindex` positivo e **sempre com indicador de foco visível**; o submenu abre por teclado.
- **Menu do celular** (390 px): botão MENU com `aria-expanded`, abre com Enter e com Espaço, o Tab entra no menu aberto, Esc fecha e devolve o foco ao botão; fechado, os links do menu não entram no fluxo do teclado.
- **Carrossel**: setas e pontos com nome acessível, funcionam com Enter, e **nada gira sozinho** (WCAG 2.2.2: não há movimento automático, logo não há o que pausar).
- **`prefers-reduced-motion`**: sem rolagem suave, sem animação nem transição acima de 50 ms e o carrossel salta direto.

Resultado local (16/11/2026): **84 de 84 combinações página x largura e as 18 de agenda sem nenhuma violação** e os 11 testes de teclado e movimento verdes (113 no total). Defeitos achados e corrigidos: região que rola sem foco de teclado (`scrollable-region-focusable`, carrosséis), conteúdo fora de região (`region`, a faixa do topo), alvo de toque de 10 px nos pontos do carrossel (`target-size`), laranja do "hoje" da agenda no celular (`color-contrast`, 2,6:1) e, pelo teste de teclado, dois links "Pular para o conteúdo" seguidos (o do tema saiu). Detalhes em `docs/specs/desvios-do-prototipo.md`, seção "Acessibilidade e desempenho (fase 3)".

**O que o axe não mede (revisão manual).** Texto sobre degradê, foto ou pseudoelemento aparece no axe como "incompleto" (não falha nem passa). Na página inicial são os títulos em degradê azul ("Vila Ipiranga" e "Venha nos visitar.", texto grande: o fim claro do degradê foi escurecido para 3:1, ver desvios), o texto dos cartões de ministério sobre foto, as legendas do carrossel sobre foto (`figcaption`, com degradê marinho por baixo) e os "SÁB/DOM" dos cartões de evento. Ao trocar uma foto por outra bem clara, confira essas legendas. O axe também não substitui um teste com leitor de tela (NVDA ou VoiceOver): vale fazer um, uma vez, na homologação.

## 2. Lighthouse

```bash
./bin/lighthouse.sh                         # 9 páginas-chave, celular e computador, no site local
./bin/lighthouse.sh / /contato/             # só estas
LH_MIN=95 ./bin/lighthouse.sh               # nota mínima (padrão 100)
LH_DISPOSITIVOS=mobile ./bin/lighthouse.sh  # só um dispositivo
LH_URL_BASE=https://<homologação> ./bin/lighthouse.sh   # mede a homologação (ou a produção)
```

Roda `npx --yes lighthouse@12.8.2` (a 13 exige Node 22.19 ou mais) com `IEADC_CHROMIUM` como Chrome (`CHROME_PATH`), `--headless=new --no-sandbox`, categorias desempenho, acessibilidade, boas práticas e SEO. O celular usa o padrão do Lighthouse (4G lenta simulada, CPU 4x mais lenta, tela de 412 px); o computador usa `--preset=desktop`. Imprime a tabela (4 notas, LCP, TBT, CLS), as auditorias que falharam em cada nota abaixo do mínimo e sai com erro (código 1). Relatórios JSON e HTML em `tests/lighthouse/saida/<data-hora>/` (fora do git). Páginas medidas: início, sobre-nos, ministerios, ministerios/mocidade, eventos, contato, horarios-e-localizacao, um artigo (alcancar-curitiba) e politica-de-privacidade.

### Onde medir e o que cada lugar mostra

| Lugar | Para quê | Observações |
|---|---|---|
| **Local** (Docker, http://localhost:8080) | Conferir o que o **código** do tema entrega: HTML, CSS, imagens, acessibilidade, boas práticas, SEO | Apache sem cache de página (a resposta leva ~300 ms), http/1.1 sem TLS, sem LiteSpeed. O SEO só chega a 100 com o site indexável (`blog_public = 1`, que é o padrão do seed; `IEADC_INDEXAR=0` o esconde). Os números de desempenho do celular são piores que os do servidor real. |
| **Homologação** (Hostinger, LiteSpeed, HTTP/2) | Medir o que o visitante terá: cache de página, compressão, HTTP/2 | **O SEO fica em 66 de propósito** (`noindex`, auditoria `is-crawlable`): é o único item que não vai a 100 aí. Use `LH_MIN=100` só para as outras três notas, ou aceite a falha do SEO. |
| **Produção** | Medição final depois do lançamento | Indexável: as quatro notas devem ir a 100. Rodar de novo depois de ligar o LiteSpeed Cache e a CSP. |

### O que fica por conta do servidor (o tema não controla)

- **HTTP/2 ou HTTP/3, TLS e compressão (gzip ou brotli)**: a Hostinger com LiteSpeed já entrega. O `.htaccess` que o WordPress grava leva compressão e cache de 1 ano para estáticos (`inc/desempenho.php`, filtro `mod_rewrite_rules`; vale depois do próximo `flush_rewrite_rules(true)`: o seed e o `migrar-remoto.sh` o fazem), mas o servidor precisa ter os módulos.
- **Cache de página** (LiteSpeed Cache): é ele que baixa a resposta do HTML de centenas para dezenas de milissegundos (TTFB e LCP). Configuração em `docs/operacao/plugins.md`.
- **CDN**: não há. Se um dia houver (por exemplo Cloudflare), ela só pode ajudar; confira a CSP e o cache das fotos.
- **Redirecionamento `http` para `https` e `www` para o domínio principal**: na hospedagem (HSTS só é enviado em https).
- **Cabeçalhos de segurança**: o tema envia; a CSP começa em `Content-Security-Policy-Report-Only` (ver `docs/operacao/plugins.md`, "Segurança").

## 3. O que o tema já faz pelo desempenho

Todo o código está em `wp-content/themes/ieadc-vila-ipiranga/inc/desempenho.php`, `assets/css/ajustes.css` ("Fase 3") e `assets/js/site.js`. O HTML dos blocos não mudou (markup 28 e visual 84 passam).

- **Foto do topo (o LCP)**: até 520 px de tela, o fundo usa uma versão sob medida de 640 px (WebP, qualidade 76, ~30 KB; a capa dos artigos já recortada em 16:10), gerada na primeira visita em `uploads/ieadc-cache` (fase 6, seção 6); de 521 a 768 px, a versão de 768 px do WordPress (65 KB em vez de 185 KB); acima disso, a original (com `preload` no computador).
- **Fotos de fundo abaixo da primeira seção** só carregam 900 px antes de aparecer (a página inicial passa de 2,4 MB e 32 pedidos para cerca de 250 KB e 10 pedidos). Só vale com JavaScript e `@media (scripting:enabled)`; sem isso tudo carrega como antes.
- **CSS do tema** (fontes de reserva, protótipo, ajustes) minificado em um arquivo só, `uploads/ieadc-cache/tema-<hash>.css`, criado na primeira visita (o hash muda quando um dos três arquivos de origem muda; se não der para gravar, o tema usa os três arquivos). As fontes reais ficam em `fontes-<hash>.css`, carregado depois da página (fase 6, seção 6). O `prototipo.css` congelado não é alterado.
- **Sem o que não é usado**: nas páginas e artigos do site saem o CSS dinâmico do Blocksy, os estilos globais e do editor de blocos do WordPress e o JavaScript do Blocksy (o HTML cai de 91 KB para 48 KB). Fica só o `main.min.css` do Blocksy (reset e `.screen-reader-text`; 12% dele é usado, ver `docs/operacao/plugins.md`).
- **Imagens**: logo reduzido (8 KB em vez de 59 KB) com `width`/`height`; as outras fotos são fundos CSS (sem `<img>`, então sem `loading`/`fetchpriority`; só a do topo tem `preload` com prioridade alta).
- **Cache**: `.htaccess` com 1 ano para CSS, JS, fontes e imagens (imagens eram 30 dias até a fase 6), por tipo (`mod_expires`) e por extensão (`mod_headers`); `Cache-Control` próprio no `/favicon.ico` e no `site.webmanifest`.
- **Fontes**: as reais (Roboto, Roboto Condensed e Dancing Script) carregam **depois** da página (fase 6: `media="print"` até o `load`, sem `preload`) e o primeiro desenho usa as fontes de reserva com as medidas das reais (CLS 0); `font-display: swap`; nenhuma origem externa (nada de `preconnect`).
- **Ícones** (`inc/identidade.php`): favicon, ícone do iPhone e `site.webmanifest`; `/favicon.ico` responde 200 (antes dava 404 e entrava no console).

## 4. Notas locais (16/11/2026, `./bin/lighthouse.sh`, `lighthouse@12.8.2`, site Docker sem cache de página)

Desempenho / acessibilidade / boas práticas / SEO. **Computador: 100 / 100 / 100 / 100 nas 9 páginas** (LCP de 0,4 a 0,7 s, TBT 0 ms, CLS 0).

| Página | Celular | LCP (celular) |
|---|---|---|
| início | 98 / 100 / 100 / 100 | 2,2 s |
| sobre-nos | 98 / 100 / 100 / 100 | 2,2 s |
| ministerios | 98 / 100 / 100 / 100 | 2,2 s |
| ministerios/mocidade | 97 / 100 / 100 / 100 | 2,3 s |
| eventos | 99 / 100 / 100 / 100 | 1,9 s |
| contato | 100 / 100 / 100 / 100 | 1,8 s |
| horarios-e-localizacao | 98 / 100 / 100 / 100 | 2,2 s |
| artigo (alcancar-curitiba) | 98 / 100 / 100 / 100 | 2,2 s |
| politica-de-privacidade | 100 / 100 / 100 / 100 | 1,8 s |

Acessibilidade, boas práticas e SEO ficam em 100 nas 18 medições. **O desempenho do celular fica entre 97 e 100** (TBT 0, CLS 0): o que sobra é LCP de ~2,2 s e FCP de ~1,4 s sob 4G lenta simulada. Antes das mudanças da fase 3, a mesma página inicial dava 78 (celular) com 2,4 MB e 32 pedidos; hoje são ~250 KB e 10 pedidos. Referência na homologação **antes** da fase 3 (home): celular 90 / 98 / 96 / 66 e computador 95 / 98 / 96 / 66.

O que impede o 100 no celular **local** e onde está o resto:

- **Resposta do servidor de ~300 ms** (WordPress sem cache de página no Docker): o LiteSpeed Cache na hospedagem a reduz para dezenas de milissegundos, o que melhora diretamente FCP e LCP. É a principal razão de esperar mais que 98 na homologação ligada ao cache.
- **Dois CSS bloqueando a renderização**: o do tema (14 KB comprimido) e o `main.min.css` do Blocksy (17 KB). Tirar o do Blocksy por inteiro rendeu só 1 ponto no celular (97 para 98) e exigiria uma cópia congelada dele no tema; ficou como está (ver `docs/operacao/plugins.md`).
- Medições variam 1 ponto entre execuções; no celular, `ministerios/mocidade` ainda acusa um "forced reflow" de `site.js` (posição inicial dos botões de troca) de efeito pequeno.
- **Preload da foto do topo**: medido, no celular ele piora o LCP (disputa banda com o CSS), por isso só vale para o computador.

Para refazer: `./bin/lighthouse.sh` (8 a 9 minutos para as 18 medições). Para a homologação ou a produção: `LH_URL_BASE=<endereço> ./bin/lighthouse.sh` e compare com esta tabela.

## 5. Fase 4: CLS da página inicial (troca de fonte) e o que depende do servidor

**Problema medido na homologação** (Hostinger, LiteSpeed, HTTP/2): página inicial no celular com desempenho 92 (LCP 2,7 s, CLS 0,08, Speed Index 4,3 s) e no computador 96 (CLS 0,029); as outras páginas davam 98 a 100. Local o CLS dava 0 porque as fontes chegam na hora; no servidor real elas chegam depois do primeiro desenho e a troca de fonte (`font-display: swap`) **empurrava o texto**: o Arial, usado enquanto o Roboto não chega, é mais largo que o Roboto Condensed (títulos) e o texto quebrava em linhas diferentes.

**Como foi reproduzido sem a homologação:** rede lenta e fontes atrasadas em 1,5 s no Playwright, registrando os `layout-shift` com o elemento que mexeu. Antes: CLS 0,068 no celular (o `.lead` do topo passava de 2 para 1 linha e o resto subia 27 px). Depois: 0,0000 no celular e 0,0003 no computador, em início, sobre-nos, ministérios/mocidade, horários e artigo.

**O que foi feito:**
- **Fontes de reserva com as medidas das reais** (`fontes.css`, "... Fallback"; `ajustes.css` redefine `--f-tit`, `--f-txt` e `--theme-font-family`): `Roboto Fallback`, `Roboto Condensed Fallback` e `Dancing Script Fallback` usam `local(Arial)` (ou Liberation Sans/Arimo, no Linux) com `size-adjust`, `ascent-override` e `descent-override` calculados com o texto real do site (razão da largura da fonte real sobre a do Arial: Roboto 98,7% em 400-599 e 92,4% em 600-900; Roboto Condensed 88% e 83%; Dancing Script 75,5%). Com a fonte carregada nada muda de aparência (markup 28 e visual 84 passam). No Android o `sans-serif` já é o Roboto, então a reserva nem entra.
- **Preload das fontes com `fetchpriority="low"`**: com prioridade alta, as duas fontes (93 KB) disputavam a banda com o CSS no celular com rede lenta. Sem preload nenhum o Lighthouse local caía de 98 para 96 (as fontes só seriam pedidas depois do CSS); com prioridade baixa fica em 98.
- **Medido e descartado** (devtools throttling e simulação): preload da foto do topo no celular (87 contra 90: disputa banda com o CSS, mesmo com latência real), tirar o `main.min.css` do Blocksy junto com o preload (98, LCP 2,3 s) e preload sem fonte (96). O LCP local fica em 2,2 s (CSS de 33 KB e a foto de 64 KB, que só é descoberta depois do CSS).

**Notas locais (16/11/2026, `./bin/lighthouse.sh /`, Docker sem cache de página):** celular 98 / 100 / 100 / 100 (LCP 2,2 s, TBT 0, **CLS 0,000**); computador **100 / 100 / 100 / 100** (LCP 0,7 s, CLS 0,000).

**O que depende do servidor (para confirmar com `LH_URL_BASE=<endereço> ./bin/lighthouse.sh /` depois da publicação):**
1. **CLS:** deve cair de 0,08 (celular) e 0,029 (computador) para perto de 0. Se sobrar algum, a causa é do servidor (por exemplo, o LiteSpeed Cache minificando ou adiando CSS: desligue "Load CSS Asynchronously" e "Critical CSS" no plugin, ver `docs/operacao/plugins.md`).
2. **LCP e Speed Index (2,7 s e 4,3 s):** o LCP é a foto do topo, descoberta só depois do CSS; o que mais o reduz é a **resposta do HTML** (TTFB): com o cache de página do LiteSpeed ligado cai de centenas para dezenas de milissegundos. Confirmar também que a foto do topo sai do cache do navegador com `Cache-Control` longo (o `.htaccess` já pede 30 dias) e em HTTP/2.
3. **Speed Index 4,3 s:** vem de a foto e as fontes chegarem tarde na rede real; o primeiro desenho agora já traz o texto na largura certa, então o que sobra é o tempo da foto. Se continuar alto na homologação, as próximas alavancas são um CSS crítico inline só do topo (não feito: arriscaria deslocamento de layout nas 28 páginas) e uma versão menor da foto do topo do celular (a de 768 px tem 64 KB; uma de 560 px teria cerca de 35 KB, mas exigiria regerar as miniaturas no servidor).

## 6. Fase 6: celular a 100 em desempenho

**Meta.** Desempenho do celular em 100 nas páginas-chave, mantendo acessibilidade, boas práticas e SEO em 100 (na homologação o SEO fica em 66 só por causa do `noindex`). Ponto de partida: local, celular 97 a 100 (mocidade 97; início, sobre-nos, ministérios, horários e artigo 98; eventos 99; contato e política 100) e computador 100; na homologação, antes da fase 6, celular de 92 (artigo) a 100 (contato e eventos) e computador 98 a 99.

### 6.1 O que decide a nota (e por que a foto sozinha não bastava)

O Lighthouse do celular não mede o carregamento real: ele **simula** uma 4G lenta (Lantern: 150 ms de ida e volta, 1,6 Mbps, CPU 4x mais lenta) a partir de um carregamento sem limites. Duas regras dessa simulação explicam quase tudo:

- O **LCP** é a média de dois grafos: o otimista (CSS, fontes, imagens) e o pessimista (**todos** os pedidos que começaram antes do LCP observado). Em ambos, a banda de 1,6 Mbps é **dividida por igual** entre os pedidos que estão em andamento. Quanto mais bytes pedidos juntos, mais tarde o último termina.
- O **FCP** inclui, como se bloqueassem a renderização, os pedidos de **prioridade alta**. Fonte descoberta só quando o CSS chega (o Dancing Script da frase em script, o itálico do Roboto nos versículos e artigos) sai com prioridade alta; as duas fontes pré-carregadas com `fetchpriority="low"` não.

No artigo "Alcançar Curitiba" (mobile, local), a simulação mostrava: FCP 1,59 s (CSS de 34 KB e depois o Roboto itálico, de 46 KB) e LCP 2,19 s, com as fontes (42 + 51 + 46 KB) terminando por último. **Reduzir a capa de 150 KB para 29 KB, sozinho, não mexeu no LCP (2,19 s)**: quem segurava eram as fontes. Sem nenhuma fonte, a mesma página mede FCP 1,11 s e LCP 1,56 s.

Como ver isso: `lighthouse --gather-mode=<pasta>` salva o carregamento, e `LanternLargestContentfulPaint.request(...)` (do próprio Lighthouse 12.8.2) devolve início e fim de cada pedido nos grafos otimista e pessimista. Foi assim que as causas acima apareceram (os scripts de apoio não estão no repositório).

### 6.2 O que foi feito

| Técnica | Onde | Efeito medido (local, celular) |
|---|---|---|
| **Fontes reais carregadas depois da página.** O CSS do tema leva só as fontes de reserva (`local(Arial)` com as medidas das reais); as `@font-face` com arquivo vão para `uploads/ieadc-cache/fontes-<hash>.css` (1 KB), ligado com `media="print"` e posto em `all` por `site.js` 150 ms depois do `load` (na hora, para quem já abriu o site neste navegador, pela marca `ieadc-fontes` no `localStorage`; sem JavaScript, `<noscript>`). Sem preload de fonte. | `inc/desempenho.php` (`ieadc_css_arquivos()`, `ieadc_fontes_tag()`), `assets/js/site.js`, `functions.php` | **FCP de 1,44 e 1,59 s para 1,11 s e LCP de 2,19 s para 1,26 a 1,56 s** em todas as páginas medidas; CLS 0,000 a 0,0004 (`cls.js` com rede lenta e fontes atrasadas) |
| **Foto do topo sob medida até 520 px de tela**: WebP de 640 px, qualidade 76, gerado na primeira visita em `uploads/ieadc-cache/topo-<id>-...webp` (capa dos artigos recortada em 16:10 central, que é o que o `.capa` já mostrava; topo das páginas só reduzido). De 521 a 768 px segue a de 768 px do WordPress; acima, a original. Sem permissão de gravação ou sem WebP no editor de imagem, cai na de 768 px. | `inc/desempenho.php` (`ieadc_foto_estreita()`, `ieadc_foto_do_topo()`) | Capa do artigo de 150 KB para 29 KB; topo das páginas de 65 KB para 31 KB. Visual: no máximo 0,38% de diferença (limite 1,0%) |
| **Cache de 1 ano** para imagens (eram 30 dias; o Lighthouse trata menos de ~100 dias como cache curto), fontes, CSS e JS, por tipo (`mod_expires`) e por extensão (`mod_headers`: o mod_expires sozinho deixou de valer na hospedagem LiteSpeed em 5 a 6 arquivos). Dentro de `<IfModule>`, idempotente, no mesmo bloco do WordPress | `inc/desempenho.php` (filtro `mod_rewrite_rules`) | `uses-long-cache-ttl` sem itens |

### 6.3 Medido e descartado

| Ideia | Resultado | Por quê |
|---|---|---|
| Preload do Dancing Script e do itálico com `fetchpriority="low"` (tirar o bloqueio do FCP) | FCP 1,59 para 1,11 s, mas **LCP piorou (2,19 para 2,39 s)** | Mais 90 KB pedidos ao mesmo tempo no começo: o CSS terminava em 1,41 s em vez de 1,11 s |
| Preload da foto do topo no celular com `fetchpriority="high"` (agora com a foto de 29 a 31 KB) | LCP sem ganho: início 1,26 para 1,49 s, artigo 1,56 para 1,49 s, horários 1,26 para 1,56 s | Na simulação a foto já começa logo depois do HTML (o Lantern usa a ordem de dependência observada, não a ordem em que o navegador a descobre). O ganho possível é só na rede real; não compensa o risco na medição. O computador mantém o preload (original, `media="(min-width:769px)"`) |
| Tirar o `main.min.css` do Blocksy sem substituto | FCP 1,11 para 0,96 s, mas a altura das páginas muda 42 px e o visual passa a 12 a 34% de diferença | O reset do Blocksy (margens, `box-sizing`, tabelas, botões) é usado |
| Tirar o `main.min.css` e levar uma **cópia reduzida de 13 KB** (só o que a cobertura de CSS do Chrome marcou nas 31 páginas a 390, 900 e 1440 px, mais as regras só de elemento) no começo do CSS do tema | Visual igual (diferenças idênticas às de antes), FCP 1,11 para 0,96 s e LCP 1,26 para 1,14 s, **mas o TBT sobe de ~15 para ~100 ms e o desempenho cai de 100 para 99** em início, horários, mocidade e contato | Com o FCP simulado menor, a tarefa longa de estilo e layout da primeira pintura (~170 ms observados) passa a cair depois dele e entra no TBT (peso 30%). Voltando o Blocksy, o TBT volta a 13 a 20 ms. Descartado; o script que gerou a cópia foi removido |

### 6.4 Atenção (o que mudou para quem usa o site)

- **Primeira visita:** o texto aparece primeiro na fonte de reserva (Arial com as medidas do Roboto) e troca para o Roboto, o Roboto Condensed e o Dancing Script cerca de 0,15 s depois de a página terminar de carregar. Nada se desloca (CLS 0,000 a 0,0004), só mudam as formas das letras. Quem já abriu o site neste navegador as recebe na hora (estão no cache). No Android o Roboto já existe no aparelho e a troca nem aparece.
- **Marca no `localStorage`** (`ieadc-fontes`, o endereço do arquivo de fontes): só serve para pular a espera; não identifica ninguém e não é cookie. Se o navegador bloquear o armazenamento, o site funciona do mesmo jeito (só perde o atalho).
- **Voltar ao comportamento anterior** (fontes no CSS do tema, com preload): `add_filter('ieadc_fontes_adiar', '__return_false');` no tema. Sem o `<link>` e o `<noscript>`; o CSS do tema volta a levar as fontes reais.
- **Primeira visita depois de um deploy:** o tema grava o CSS, o CSS das fontes e a foto do topo de cada página em `uploads/ieadc-cache` (apagar a pasta é seguro; o HTML em cache do LiteSpeed é esvaziado a cada deploy). O nome leva um hash: o navegador baixa o novo e o cache longo é seguro.
- **Foto do topo trocada no painel:** a derivada leva a data e o tamanho da original no nome, então uma foto nova gera outra; a antiga fica na pasta (poucos KB).


### 6.5 Notas locais (17/11/2026, `./bin/lighthouse.sh`, `lighthouse@12.8.2`, site Docker sem cache de página)

Antes = o `main` em 6c34cf7 (medido em 16/11/2026 com o mesmo script e o mesmo site); depois = o commit da fase 6. Acessibilidade, boas práticas e SEO ficam em 100 nas 18 medições.

mobile
| Página | Desempenho antes | depois | FCP antes | depois | LCP antes | depois | TBT antes | depois | KB antes | depois | pedidos antes | depois | A11y/BP/SEO depois |
| inicio | 98 | 100 | 1.4 s | 1.1 s | 2.2 s | 1.3 s | 0 ms | 24 ms | 254 | 222 | 10 | 11 | 100/100/100 |
| sobre-nos | 98 | 100 | 1.4 s | 1.1 s | 2.2 s | 1.3 s | 0 ms | 0 ms | 246 | 220 | 10 | 11 | 100/100/100 |
| ministerios | 98 | 100 | 1.4 s | 1.1 s | 2.2 s | 1.6 s | 0 ms | 0 ms | 249 | 220 | 10 | 11 | 100/100/100 |
| ministerios_mocidade | 97 | 100 | 1.6 s | 1.1 s | 2.3 s | 1.5 s | 0 ms | 0 ms | 247 | 221 | 9 | 10 | 100/100/100 |
| eventos | 99 | 100 | 1.1 s | 1.1 s | 1.9 s | 1.4 s | 0 ms | 0 ms | 193 | 172 | 8 | 9 | 100/100/100 |
| contato | 100 | 100 | 1.1 s | 1.1 s | 1.8 s | 1.3 s | 0 ms | 0 ms | 146 | 146 | 7 | 8 | 100/100/100 |
| horarios-e-localizacao | 98 | 100 | 1.4 s | 1.1 s | 2.2 s | 1.3 s | 0 ms | 30 ms | 277 | 233 | 10 | 11 | 100/100/100 |
| alcancar-curitiba | 98 | 100 | 1.6 s | 1.1 s | 2.2 s | 1.5 s | 0 ms | 0 ms | 340 | 222 | 9 | 10 | 100/100/100 |
| politica-de-privacidade | 100 | 100 | 1.1 s | 1.1 s | 1.8 s | 1.3 s | 0 ms | 0 ms | 145 | 146 | 7 | 8 | 100/100/100 |

desktop
| Página | Desempenho antes | depois | FCP antes | depois | LCP antes | depois | TBT antes | depois | KB antes | depois | pedidos antes | depois | A11y/BP/SEO depois |
| inicio | 100 | 100 | 0.5 s | 0.3 s | 0.7 s | 0.6 s | 0 ms | 0 ms | 524 | 525 | 11 | 12 | 100/100/100 |
| sobre-nos | 100 | 100 | 0.5 s | 0.3 s | 0.6 s | 0.5 s | 0 ms | 0 ms | 408 | 409 | 11 | 12 | 100/100/100 |
| ministerios | 100 | 100 | 0.5 s | 0.3 s | 0.6 s | 0.5 s | 0 ms | 0 ms | 306 | 306 | 10 | 11 | 100/100/100 |
| ministerios_mocidade | 100 | 100 | 0.5 s | 0.3 s | 0.6 s | 0.5 s | 0 ms | 0 ms | 329 | 330 | 9 | 10 | 100/100/100 |
| eventos | 100 | 100 | 0.3 s | 0.3 s | 0.6 s | 0.5 s | 0 ms | 0 ms | 266 | 267 | 8 | 9 | 100/100/100 |
| contato | 100 | 100 | 0.3 s | 0.3 s | 0.5 s | 0.4 s | 0 ms | 0 ms | 207 | 208 | 8 | 9 | 100/100/100 |
| horarios-e-localizacao | 100 | 100 | 0.5 s | 0.3 s | 0.6 s | 0.5 s | 0 ms | 0 ms | 327 | 328 | 10 | 11 | 100/100/100 |
| alcancar-curitiba | 100 | 100 | 0.5 s | 0.3 s | 0.6 s | 0.4 s | 0 ms | 0 ms | 392 | 393 | 9 | 10 | 100/100/100 |
| politica-de-privacidade | 100 | 100 | 0.3 s | 0.3 s | 0.4 s | 0.3 s | 0 ms | 0 ms | 145 | 146 | 7 | 8 | 100/100/100 |

Leitura: o LCP e o FCP do celular caem porque as fontes saem do caminho (6.1) e as fotos ficam menores; o computador já estava em 100 e continua. Na homologação o número depende também da resposta do servidor (documento de ~700 ms sem cache quente) e do cache do LiteSpeed: medir com `LH_URL_BASE=<endereço> ./bin/lighthouse.sh` depois do merge e comparar com esta tabela.
