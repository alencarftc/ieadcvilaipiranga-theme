# Continuidade do projeto (para o Claude Code)

Escrito em 16/11/2026, quando todos os módulos de construção estavam no `main`. Serve para quem retomar o trabalho sem o histórico das conversas.

## Links
- Repositório: https://github.com/alencarftc/ieadcvilaipiranga-theme
- Protótipo navegável (referência visual, v3 aprovado): https://claude.ai/artifact/7QAdRxoBGfwJKPNq7hMSkp (publicado como página privada do Felipe; para outras pessoas, ele a compartilha). A cópia versionada, com os mesmos HTMLs usados nos testes, está em `prototipo/` (abra `prototipo/inicio.html` no navegador).

## 1. Estado

| Módulo | O que é | Estado |
|---|---|---|
| M0 | Fundação: Docker, tema filho, cabeçalho/rodapé/menu, seed, setup no Windows | pronto |
| M1 | Infra dos blocos (`inc/blocos.php`, `inc/helpers.php`, editor genérico) e testes (`bin/testar.sh`) | pronto |
| M2 | Agenda calculada pelas regras da igreja (`mu-plugins/ieadc-core/agenda.php`) | pronto |
| M3 | Tipos de conteúdo, categorias, metas de página e artigo, seeds 40 a 80 | pronto |
| B1 a B4 | 37 blocos (topo, horários e agenda, listas e cartões, conteúdo longo) | prontos |
| P1 a P3 | As 28 páginas montadas só com blocos | prontas |
| M6 | SEO, desempenho, acessibilidade e segurança (`inc/seo.php`, `docs/operacao/plugins.md`) | pronto; **Lighthouse e axe com o site no ar ainda não rodaram** |
| M7 | Migração e lançamento | `bin/migrar.ps1` e roteiro prontos, **ensaio de 28/11 e lançamento de 30/11 pendentes**; falta `docs/operacao/manutencao.md` |

Fotos do site, pastor João e sedes: ver as regras em `CLAUDE.md`. Lançamento: 30/11/2026. Hospedagem Hostinger (contratada por 4 anos); SSH: servidor, porta e usuário ficam no `.env` do Felipe (`HOSTINGER_*`, fora do git; o repositório é público); a senha só com ele.

## 2. Pendências conhecidas

1. Ensaio de migração (28/11) e lançamento (30/11): `docs/operacao/lancamento.md`. O `bin/migrar.ps1` **nunca rodou de verdade** (nem no Windows nem na Hostinger); corrija o que quebrar.
2. `docs/operacao/manutencao.md` (como publicar artigo e evento, trocar foto, ler pedidos de oração, backup e restauração com UpdraftPlus).
3. Lighthouse e axe no domínio; decidir o corte de CSS do Blocksy (Coverage do Chrome).
4. Configurar no painel: 2FA para os administradores, UpdraftPlus com cópia para o Google Drive da igreja, e-mails reais dos formulários (hoje vazios em *Configurações > IEADC Vila Ipiranga*).
5. Conteúdo com `[a confirmar]` (classe `pend`): depende da liderança; não preencha por conta própria.
6. O rótulo de lista do editor mostra "CHAMADAS (2) (2)" (contador duplicado, no editor genérico de `blocos/_editor/`).
7. Branches remotas antigas (feat/m1-*, feat/b1-*, fix/*, etc.) ficaram sem apagar porque o proxy da nuvem bloqueia; apague pelo GitHub.

## 3. Como o site é montado

- **Páginas**: cada página é uma lista de blocos `ieadc/<nome>` com atributos em JSON (`content/blocos/<pagina>.json`), gravada por `scripts/seed/20-paginas-p*.php`. A ordem das seções de cada página está em `docs/specs/mapa-secoes.json`.
- **Bloco**: pasta em `wp-content/themes/ieadc-vila-ipiranga/blocos/<nome>/` com `block.json` e `render.php`; sem build. O editor do painel gera os campos a partir dos atributos (`blocos/_editor/`). Convenções em `docs/specs/00-convencoes.md`; atributos de cada ocorrência em `/docs/specs/B1..B4-*.md`.
- **Dados da igreja**: *Configurações > IEADC Vila Ipiranga* (`ieadc_info()`); padrões em `mu-plugins/ieadc-core/info.php`.
- **Agenda** (cultos e eventos do mês): `ieadc_agenda*()`. Regras: Ceia no 2º sábado, consagração nos meses pares e vigília nos ímpares, etc. (`docs/specs/M2-agenda.md`). A data é `IEADC_HOJE` quando definida (testes: 2026-10-06).
- **Seed**: `scripts/seed.php` roda `scripts/seed/NN-*.php` em ordem; só troca conteúdo ainda não editado, a não ser com `IEADC_ATUALIZAR=1`.
- **CSS**: `assets/css/prototipo.css` (congelado) + `ajustes.css` (por módulo). O reset do protótipo vale só dentro de `.ieadc-ui`.

## 4. Fidelidade ao protótipo: como é verificada

`./bin/testar.sh` sobe o protótipo em http://localhost:8090 e compara, para as 28 páginas:

1. **php**: `php -l` em todos os arquivos e os testes da agenda (`tests/php/`).
2. **markup**: HTML de cada seção (`docs/specs/mapa-secoes.json`) do WordPress contra o do protótipo, sem JavaScript. Mostra o diff (`-` protótipo, `+` WordPress). Ignora só o que é funcional (action, nonce, honeypot, name, maxlength, avisos dos formulários).
3. **visual**: captura da página inteira a 1440, 900 e 390 px e `pixelmatch`; limite 1,0% (1440 e 390) e 1,5% (900). Imagens em `tests/visual/saida/<pagina>-<largura>-{wp,proto,diff}.png`. **Abra a imagem de diferença mesmo quando passa**: fonte, cor, recorte da foto e quebra de linha do título.
4. **interação**: formulários (chega no Mailpit, http://localhost:8025), carrossel, menu e o selo "Próximo".

Para uma página: `./bin/testar.sh visual inicio` (ou `IEADC_PAGINAS=inicio,contato`). Para comparar à mão, o protótipo também abre direto de `prototipo/*.html`.

### Armadilhas já encontradas (o Blocksy interfere no CSS)
- Resets com seletor `.ieadc-ui a` vencem classes do protótipo; use `:where(.ieadc-ui) a`.
- O Blocksy imprime `body{background-color:#FAFBFC}` depois do nosso CSS (`html body{background:...}` resolve), dá `margin-bottom:8px` a `label`, margem negativa a `legend` e estilo próprio a `button[type=submit]`, `textarea` e `blockquote`.
- Cor dos títulos: marinho só fora de `.ieadc-ui`; dentro, o título herda (branco nos cartões e faixas escuras).
- `wptexturize` desligado (aspas e travessões iguais ao protótipo).
- Depois de apagar e recriar `wp-content/mu-plugins`, o container fica com o mount velho: `docker compose up -d --force-recreate wordpress`.
- Limite de 5 envios por hora no formulário: rodar `testar.sh` várias vezes seguidas faz os testes de envio falharem; apague o transient (`wp transient delete --all`).
- Na nuvem, `wordpress.org` é bloqueado (plugins não instalam; Blocksy entra de cópia local pelo `docker-compose.override.yml`, não versionado) e o Docker Hub pode dar 429 (use `mirror.gcr.io/library/<imagem>` e `docker tag`).

## 5. Processo de revisão

Resumo (completo em `docs/specs/revisao.md`): o Felipe não revisa PRs. Cada PR passa por um revisor, **que nunca é o autor**:

1. Escopo: o diff só mexe nos arquivos da spec.
2. `./bin/testar.sh` verde (e `./bin/testar.sh` do zero no fim de cada onda: `docker compose down -v` + `./bin/setup.sh`).
3. Fidelidade (B e P): markup sem diferença, visual dentro dos limites, olhando as imagens de diferença.
4. Critérios de aceite da spec; textos e fotos sem mudança e sem repetir; editor: abrir 2 blocos no painel e trocar um texto e uma foto.
5. Comentário em português no PR e *squash merge*. Três rodadas sem fechar: chamar o integrador.

Economia: revisor em Sonnet lendo só o diff; Opus só se a primeira rodada falhar; no máximo 2 desenvolvimentos ao mesmo tempo. Ações bloqueadas por permissão não se contornam: peça a aprovação do Felipe, citando a ação exata.

## 6. Histórico (PRs no GitHub)

#2 a #5 fundação e specs; #6, #8, #12, #14, #15, #19 correções de fidelidade (ordem das seções, formulário, CSS do Blocksy); #7, #9, #11 agenda; #10 conteúdos; #16, #18 B3; #17 M1; #21 B1; #22 B2; #24 B4; #25 a #28 páginas e M6; #29 setup sem wordpress.org; #30 migração.
