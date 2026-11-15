# M0 · Fundação e integração

**Agente:** integrador (o mesmo do começo ao fim). **Onda:** 0 (16/11) e depois contínuo até 30/11.

## Objetivo

Deixar o `main` pronto para os agentes trabalharem em paralelo e cuidar do que é compartilhado: arquivos comuns, ícones, seed, revisão e merge dos PRs.

## Arquivos (só este agente altera)

`functions.php`, `header.php`, `footer.php`, `inc/icones.php`, `inc/menu.php`, `assets/css/prototipo.css`, `assets/js/site.js`, `docker-compose.yml`, `bin/setup.sh`, `bin/setup.ps1`, `bin/wp.sh`, `bin/exportar.sh`, `config/`, `scripts/seed.php`, `scripts/importar_prototipo.py`, `scripts/referencias_specs.py`, `.gitattributes`, `.gitignore`, `README.md`, `docs/`.

## Entregas da Onda 0 (16/11)

1. **Ícones completos.** Levar para `inc/icones.php` todos os ícones do dicionário `I` de `prototipo/fonte/gen.py` com os mesmos nomes (`relogio`, `pin`, `maos`, `megafone`, `degraus`, `coracao`, `seta`…). Hoje só há 15. Mudar a assinatura para `ieadc_icone(string $nome, string $classe = 'ico'): string` (o protótipo usa outras classes em alguns lugares).
2. **Categoria de blocos.** Registrar a categoria `ieadc` ("IEADC Vila Ipiranga") com `block_categories_all`.
3. **Seed modular.** `scripts/seed.php` passa a: (a) desligar kses, (b) rodar em ordem todos os arquivos `scripts/seed/*.php`. Mover o que existe hoje para `scripts/seed/10-midia.php`, `20-paginas.php` e `30-menu.php`, sem mudar o comportamento.
4. **`IEADC_HOJE`.** Passar a variável do `.env` para o contêiner WordPress (`docker-compose.yml`) e definir a constante em `WORDPRESS_CONFIG_EXTRA` quando existir.
5. **Windows.** `bin/setup.ps1` equivalente ao `setup.sh` (PowerShell 5+, chama `docker compose`). Testar no Windows com o Felipe é entrega da Onda 1.
6. **`ajustes.css`.** Criar `assets/css/ajustes.css` vazio (com o cabeçalho de convenção) e carregar depois de `prototipo.css`. Mover para ele o conteúdo de `assets/css/blocksy.css`, em seção `/* === M0 === */`.
7. **Montagem de página por blocos.** Função `ieadc_seed_pagina_blocos(string $caminho, string $json): int` em `scripts/seed/_funcoes.php`: lê `content/blocos/<pagina>.json`, troca nomes de imagem por IDs e caminhos por links, gera `<!-- wp:ieadc/<nome> {attrs} /-->` para cada item, aplica o modelo `larga` e respeita a regra de não sobrescrever (igual à `pagina()` de hoje). Usada pelos P1–P3.
8. **Plano 1.1.** Atualizar a tabela de blocos do plano (seção 6) com os 37 blocos de `mapa-secoes.json` (a divisão B1–B4 está nas specs).

## Entregas contínuas

- Revisar e fazer merge dos PRs dos agentes (o Felipe aprova os de página).
- Adicionar ícones quando um agente pedir.
- Ao fim de cada onda: `./bin/setup.sh` do zero e `./bin/testar.sh` completo no `main`.
- 28–29/11: revisão de textos com o Felipe, lista final de `[a confirmar]` para o pastor (gerada por `./bin/wp.sh eval 'echo …'` que procura `class="pend"` em todo o conteúdo).

## Contratos que fornece

`ieadc_info()`, `ieadc_icone()`, `ieadc_link()`, `ieadc_dado()`, menu `primary`, modelo `page-templates/larga.php`, categoria de blocos `ieadc`, ordem do seed.

## Critérios de aceite (Onda 0)

- [ ] `./bin/setup.sh` num volume vazio termina sem erro e o site mostra as mesmas 28 páginas de hoje.
- [ ] Todo ícone usado no protótipo (`grep -o 'ic(\x27[a-z_-]*' prototipo/fonte/*.py`) existe em `inc/icones.php`.
- [ ] `docker compose exec wordpress php -r 'echo getenv("IEADC_HOJE");'` mostra a data quando definida no `.env`.
- [ ] `php -l` sem erro em todos os PHP.

## Fora do escopo

Blocos, agenda, tipos de conteúdo.
