# Site da IEADC Vila Ipiranga (WordPress)

Leia primeiro: `docs/operacao/continuidade.md` (estado, como trabalhar, fidelidade e revisão) e `docs/specs/00-convencoes.md` (convenções dos blocos). Responda sempre em português do Brasil.

## Links
- Repositório: https://github.com/alencarftc/ieadcvilaipiranga-theme
- Protótipo navegável (referência visual, v3 aprovado): https://claude.ai/artifact/7QAdRxoBGfwJKPNq7hMSkp (publicado como página privada do Felipe; para outras pessoas, ele a compartilha). A cópia versionada, com os mesmos HTMLs usados nos testes, está em `prototipo/` (abra `prototipo/inicio.html` no navegador).

## O que é
Site ieadcvilaipiranga.com.br em WordPress: tema pai **Blocksy**, tema filho `wp-content/themes/ieadc-vila-ipiranga/` (39 blocos dinâmicos `ieadc/*` em `blocos/`), mu-plugin `wp-content/mu-plugins/ieadc-core/` (dados da igreja, Eventos, Mensagens, Pedidos de oração, agenda, formulários). A referência é o **protótipo v3** em `prototipo/`: o objetivo é ficar o mais parecido possível com ele.

## Comandos (rode na raiz; no Windows use Git Bash para `./bin/*.sh`, ou PowerShell para `*.ps1`)
- `./bin/setup.sh` (Windows: `bin\setup.ps1`): sobe o WordPress local em http://localhost:8080 e roda o seed. Precisa de `.env` (copie de `.env.example`); deixe `IEADC_HOJE` vazio (o site local mostra o dia real; os testes fixam 06/10/2026 pelo cabeçalho `X-Ieadc-Hoje`).
- `./bin/testar.sh [ci|php|markup|visual|interacao|a11y] [pagina]`: testes de fidelidade ao protótipo e de acessibilidade, mais as travas offline do pipeline de implantação (`ci`, `tests/ci/guardas.sh`: pares aceitos de endereço + pasta da homologação `hml.ieadcvilaipiranga.com.br` e da produção, recusas e simulação no servidor; sem site e sem docker). Tudo deve passar (ci 286, php 220 (agenda 37, funções 44, integrações 15, artigo 10, SEO 114), markup 28, visual 84, interação 8, a11y 113: axe WCAG 2.1 AA e 2.2 AA (target-size) nas 28 páginas a 390, 900 e 1440 px e na agenda com outras datas, teclado e movimento reduzido).
- `./bin/lighthouse.sh [caminho ...]`: Lighthouse 12.8.2 (celular e computador) nas páginas-chave, com tabela e falha abaixo de `LH_MIN` (padrão 100); `LH_URL_BASE` mede a homologação. Usa `IEADC_CHROMIUM` como Chrome. Ver `docs/operacao/desempenho-e-acessibilidade.md`.
- `docker compose run --rm -T -e IEADC_ATUALIZAR=1 wpcli wp eval-file /scripts/seed.php`: reaplica o conteúdo (sobrescreve páginas, artigos e eventos do seed).
- `./bin/wp.sh <comando>` (wp-cli), `bin\migrar.ps1` (envia para a Hostinger, ver `docs/operacao/lancamento.md`).

## Regras
- Fidelidade ao protótipo é critério de aceite: mudança visual só vale se `./bin/testar.sh` continuar verde (HTML igual por seção e imagem dentro de 1,0% a 1440 e 390 px, 1,5% a 900 px).
- `assets/css/prototipo.css` é congelado (só o reset escopado em `.ieadc-ui`); ajustes entram em `assets/css/ajustes.css`, numa seção por módulo. Use `:where()` para não vencer o CSS do protótipo por especificidade.
- Nunca invente dado da igreja: o que falta vai como `[a confirmar]` com a classe `pend` (fundo amarelo). A pergunta vai para `docs/operacao/pendencias-para-o-felipe.md`; texto que muda em relação ao protótipo entra também em `prototipo/*.html` e em `docs/specs/desvios-do-prototipo.md`. Textos e fotos vêm do protótipo; fotos coloridas e sem repetir na página; sem fotos com o pastor João (prefira o Ev. Robson Stein). Termos: "Ceia do Senhor", "Culto de Ensino". Família Roboto.
- Páginas são só blocos `ieadc/*`; não use `wp:html`. Um bloco novo segue `docs/specs/00-convencoes.md` (block.json + render.php, sem build).
- SEO e Google (fase 2): dados estruturados e metatags em `inc/seo.php` e `inc/seo-schema.php` (um só grafo; nunca `AggregateRating`/`Review`; nada de dado inventado); o que só o Felipe faz está em `docs/operacao/seo-e-google.md`. O botão "Avaliar no Google" usa o campo `google_avaliacao` de Configurações.
- Acessibilidade e desempenho (fase 3): o axe (`tests/visual/a11y.spec.js`) precisa passar sem violação; cabeçalhos de segurança e CSP em `ieadc-core/seguranca.php` (CSP em modo só-relatório até a homologação confirmar); desempenho em `inc/desempenho.php` (CSS do tema em um arquivo minificado em `uploads/ieadc-cache`, topo menor no celular, fotos de baixo adiadas, sem CSS/JS do Blocksy que não é usado) e ícones em `inc/identidade.php`. Os desvios do protótipo estão em `docs/specs/desvios-do-prototipo.md`.
- Implantação: a homologação é `https://hml.ieadcvilaipiranga.com.br`, SUBPASTA da produção (`domains/ieadcvilaipiranga.com.br/public_html/hml`). Só os pares exatos de endereço + pasta de `docs/operacao/ci-cd.md` passam nas travas (`bin/implantar.sh`, scripts remotos e `bin/migrar.ps1`; cada uma tem casos em `tests/ci/guardas.sh`); nunca use os botões Sync/Push to live da Hostinger.
- Pedidos de oração são dados pessoais: ficam privados, só e-mail e 90 dias. Nunca versione senhas, `.env`, banco ou `content/backup/`.
- Todo trabalho vai por branch e PR; antes de mesclar, revisão conforme `docs/specs/revisao.md`. Commits em português, terminando com as linhas de coautoria do seu ambiente.
