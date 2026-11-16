# P1 · Páginas prioritárias

**Onda 3 (23–25/11).** Primeiras a ficar prontas: são as que o visitante mais usa. Dependem de B1, B2 (`semana`, `faixa`, `local`, `eventos-grade`, `tabela-horarios`, `cultos-grade`), B3 (Início) e B4 (`pedido-oracao`, `contato-lista`, `capa`).

## Arquivos

`scripts/seed/20-paginas-p1.php`, `content/blocos/{inicio,horarios-e-localizacao,contato,pedidos-de-oracao}.json`.

## Páginas

| Página (protótipo) | Endereço | Blocos, na ordem |
|---|---|---|
| `inicio.html` | `/` | `hero-home` → `semana` → `faixa` → `boas-vindas` → `eventos-grade` → `chamadas` → `artigos-grade` → `ministerios-trilho` → `galeria-faixa` → `local` |
| `horarios-e-localizacao.html` | `/horarios-e-localizacao/` | `hero` → `semana` → `cultos-grade` → `tabela-horarios` → `faixa` → `local` |
| `contato.html` | `/contato/` | `hero` → `contato-lista` → `capa` |
| `pedidos-de-oracao.html` | `/pedidos-de-oracao/` | `hero` → `pedido-oracao` |

Na Início, o `hero-home` substitui o título da página; o título do WordPress continua "Início" (SEO do M6).

## Como montar uma página

1. O conteúdo de cada página fica em `content/blocos/<pagina>.json`: lista ordenada de `{"bloco": "ieadc/<nome>", "attrs": {…}}`. Imagens pelo nome do arquivo (`"foto": "f_hero.webp"`); o seed troca pelo ID da mídia. Links internos pelo caminho (`"link": "horarios-e-localizacao"`).
2. Copie **todos** os textos da referência (`docs/specs/referencias/<bloco>.html`, procure a seção da página) para os atributos. Não reescreva nada (na fase 5B os `[a confirmar]` saíram do conteúdo).
3. O seed `scripts/seed/20-paginas-p1.php` chama `ieadc_seed_pagina_blocos($caminho, $json)` (fornecida pelo M0), que gera o conteúdo com os comentários de bloco, aplica o modelo `larga` e só sobrescreve página que ainda tem o conteúdo de transição (ou com `IEADC_ATUALIZAR=1`).
4. Rode `./bin/testar.sh markup <pagina>` e `./bin/testar.sh visual <pagina>` até passarem.
5. No PR, anexe os 3 prints (1440, 900, 390) de cada página e a imagem de diferença.

## Critérios de aceite (cada página)

- [ ] `markup` sem diferenças.
- [ ] `visual` dentro do limite (1,0% em 1440 e 390; 1,5% em 900).
- [ ] Nenhum bloco "HTML personalizado" restante na página.
- [ ] Todo texto e foto da página editável pelo painel (abrir o editor e conferir cada bloco).
- [ ] Felipe aprova o PR.

## Fora do escopo

Criar ou mudar blocos (peça ao dono: B1–B4). Mudar textos aprovados.
