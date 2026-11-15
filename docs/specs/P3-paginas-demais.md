# P3 · Demais páginas

**Onda 3 (24–27/11).** Os 6 cultos, Eventos, Artigos e os 3 artigos de exemplo, Galeria, Aceitar Jesus e Política de privacidade.

## Arquivos

`scripts/seed/20-paginas-p3.php`, `content/blocos/{culto-*,ceia-do-senhor,vigilia-e-consagracao,eventos,artigos,galeria,aceitar-jesus,politica-de-privacidade}.json`.

## Páginas

| Página (protótipo) | Endereço | Blocos, na ordem |
|---|---|---|
| `culto-de-oracao.html` | `/culto-de-oracao/` | `hero` → `fatos` → `corpo-indice` → `carrossel` → `passos` → `vizinhos` → `trocador` |
| `culto-de-ensino.html` | `/culto-de-ensino/` | `hero` → `fatos` → `corpo-indice` → `carrossel` → `passos` → `vizinhos` → `trocador` |
| `culto-do-circulo-de-oracao.html` | `/culto-do-circulo-de-oracao/` | `hero` → `fatos` → `corpo-indice` → `carrossel` → `passos` → `vizinhos` → `trocador` |
| `culto-dominical.html` | `/culto-dominical/` | `hero` → `fatos` → `corpo-indice` → `carrossel` → `passos` → `vizinhos` → `trocador` |
| `ceia-do-senhor.html` | `/ceia-do-senhor/` | `hero` → `fatos` → `corpo-indice` → `carrossel` → `passos` → `vizinhos` → `trocador` |
| `vigilia-e-consagracao.html` | `/vigilia-e-consagracao/` | `hero` → `fatos` → `corpo-indice` → `carrossel` → `passos` → `vizinhos` → `trocador` |
| `eventos.html` | `/eventos/` | `hero` → `eventos-grade` → `calendario` |
| `artigos.html` | `/artigos/` | `hero` → `artigos-grade` → `artigos-categorias` |
| `artigo-alcancar-curitiba.html` | `/alcancar-curitiba/` | `artigo-topo` → `artigo-corpo` → `artigos-grade` |
| `artigo-ate-que-ele-venha.html` | `/ate-que-ele-venha/` | `artigo-topo` → `artigo-corpo` → `artigos-grade` |
| `artigo-orai-uns-pelos-outros.html` | `/orai-uns-pelos-outros/` | `artigo-topo` → `artigo-corpo` → `artigos-grade` |
| `galeria.html` | `/galeria/` | `hero` → `fotos` |
| `aceitar-jesus.html` | `/aceitar-jesus/` | `hero` → `aceitar-passos` → `chamadas` |
| `politica-de-privacidade.html` | `/politica-de-privacidade/` | `hero` → `leitura` |

Os 3 artigos são **posts**: o JSON preenche só `artigo-topo` (migalha) e `artigo-corpo` (versículo); o corpo do texto vai como parágrafos e títulos no conteúdo do post, e o resto vem do post (ver B4).

## Como montar uma página

1. O conteúdo de cada página fica em `content/blocos/<pagina>.json`: lista ordenada de `{"bloco": "ieadc/<nome>", "attrs": {…}}`. Imagens pelo nome do arquivo (`"foto": "f_hero.webp"`); o seed troca pelo ID da mídia. Links internos pelo caminho (`"link": "horarios-e-localizacao"`).
2. Copie **todos** os textos da referência (`docs/specs/referencias/<bloco>.html`, procure a seção da página) para os atributos. Não reescreva nada; `[a confirmar]` continua igual.
3. O seed `scripts/seed/20-paginas-p3.php` chama `ieadc_seed_pagina_blocos($caminho, $json)` (fornecida pelo M0), que gera o conteúdo com os comentários de bloco, aplica o modelo `larga` e só sobrescreve página que ainda tem o conteúdo de transição (ou com `IEADC_ATUALIZAR=1`).
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
