# Desempenho e acessibilidade (fase 3)

Como medir, o que o tema já faz e o que depende do servidor. Escrito em 16/11/2026, no fim da fase 3 (`feat/site-a11y-perf`).

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

- **Foto do topo (o LCP)**: no celular (até 768 px) o `preload` e o fundo usam a versão de 768 px do WordPress (65 KB em vez de 185 KB); acima disso, a original.
- **Fotos de fundo abaixo da primeira seção** só carregam 900 px antes de aparecer (a página inicial passa de 2,4 MB e 32 pedidos para cerca de 250 KB e 10 pedidos). Só vale com JavaScript e `@media (scripting:enabled)`; sem isso tudo carrega como antes.
- **CSS do tema** (fontes, protótipo, ajustes) minificado em um arquivo só, `uploads/ieadc-cache/tema-<hash>.css`, criado na primeira visita (o hash muda quando um dos três muda; se não der para gravar, o tema usa os três arquivos). O `prototipo.css` congelado não é alterado.
- **Sem o que não é usado**: nas páginas e artigos do site saem o CSS dinâmico do Blocksy, os estilos globais e do editor de blocos do WordPress e o JavaScript do Blocksy (o HTML cai de 91 KB para 48 KB). Fica só o `main.min.css` do Blocksy (reset e `.screen-reader-text`; 12% dele é usado, ver `docs/operacao/plugins.md`).
- **Imagens**: logo reduzido (8 KB em vez de 59 KB) com `width`/`height`; as outras fotos são fundos CSS (sem `<img>`, então sem `loading`/`fetchpriority`; só a do topo tem `preload` com prioridade alta).
- **Cache**: `.htaccess` com 1 ano para CSS, JS, fontes e imagens; `Cache-Control` próprio no `/favicon.ico` e no `site.webmanifest`.
- **Fontes**: as duas mais usadas já têm `preload` e `font-display: swap`; nenhuma origem externa (nada de `preconnect`).
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
