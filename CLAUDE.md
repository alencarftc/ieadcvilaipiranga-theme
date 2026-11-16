# Site da IEADC Vila Ipiranga (WordPress)

Leia primeiro: `docs/operacao/continuidade.md` (estado, como trabalhar, fidelidade e revisão) e `docs/specs/00-convencoes.md` (convenções dos blocos). Responda sempre em português do Brasil.

## Links
- Repositório: https://github.com/alencarftc/ieadcvilaipiranga-theme
- Protótipo navegável (referência visual, v3 aprovado): https://claude.ai/artifact/7QAdRxoBGfwJKPNq7hMSkp (publicado como página privada do Felipe; para outras pessoas, ele a compartilha). A cópia versionada, com os mesmos HTMLs usados nos testes, está em `prototipo/` (abra `prototipo/inicio.html` no navegador).

## O que é
Site ieadcvilaipiranga.com.br em WordPress: tema pai **Blocksy**, tema filho `wp-content/themes/ieadc-vila-ipiranga/` (37 blocos dinâmicos `ieadc/*` em `blocos/`), mu-plugin `wp-content/mu-plugins/ieadc-core/` (dados da igreja, Eventos, Mensagens, Pedidos de oração, agenda, formulários). A referência é o **protótipo v3** em `prototipo/`: o objetivo é ficar o mais parecido possível com ele.

## Comandos (rode na raiz; no Windows use Git Bash para `./bin/*.sh`, ou PowerShell para `*.ps1`)
- `./bin/setup.sh` (Windows: `bin\setup.ps1`): sobe o WordPress local em http://localhost:8080 e roda o seed. Precisa de `.env` (copie de `.env.example`); deixe `IEADC_HOJE` vazio (o site local mostra o dia real; os testes fixam 06/10/2026 pelo cabeçalho `X-Ieadc-Hoje`).
- `./bin/testar.sh [php|markup|visual|interacao] [pagina]`: testes de fidelidade ao protótipo. Tudo deve passar (php 106 (agenda 37, funções 44, integrações 15, artigo 10), markup 28, visual 84, interação 8).
- `docker compose run --rm -T -e IEADC_ATUALIZAR=1 wpcli wp eval-file /scripts/seed.php`: reaplica o conteúdo (sobrescreve páginas, artigos e eventos do seed).
- `./bin/wp.sh <comando>` (wp-cli), `bin\migrar.ps1` (envia para a Hostinger, ver `docs/operacao/lancamento.md`).

## Regras
- Fidelidade ao protótipo é critério de aceite: mudança visual só vale se `./bin/testar.sh` continuar verde (HTML igual por seção e imagem dentro de 1,0% a 1440 e 390 px, 1,5% a 900 px).
- `assets/css/prototipo.css` é congelado (só o reset escopado em `.ieadc-ui`); ajustes entram em `assets/css/ajustes.css`, numa seção por módulo. Use `:where()` para não vencer o CSS do protótipo por especificidade.
- Nunca invente dado da igreja: o que falta vai como `[a confirmar]` com a classe `pend` (fundo amarelo). A pergunta vai para `docs/operacao/pendencias-para-o-felipe.md`; texto que muda em relação ao protótipo entra também em `prototipo/*.html` e em `docs/specs/desvios-do-prototipo.md`. Textos e fotos vêm do protótipo; fotos coloridas e sem repetir na página; sem fotos com o pastor João (prefira o Ev. Robson Stein). Termos: "Ceia do Senhor", "Culto de Ensino". Família Roboto.
- Páginas são só blocos `ieadc/*`; não use `wp:html`. Um bloco novo segue `docs/specs/00-convencoes.md` (block.json + render.php, sem build).
- Pedidos de oração são dados pessoais: ficam privados, só e-mail e 90 dias. Nunca versione senhas, `.env`, banco ou `content/backup/`.
- Todo trabalho vai por branch e PR; antes de mesclar, revisão conforme `docs/specs/revisao.md`. Commits em português, terminando com as linhas de coautoria do seu ambiente.
