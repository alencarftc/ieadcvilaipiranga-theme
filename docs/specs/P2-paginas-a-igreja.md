# P2 · Páginas da Igreja

**Onda 3 (24–27/11).** Sobre nós, Liderança, Ministérios e as 7 páginas de ministério.

## Arquivos

`scripts/seed/20-paginas-p2.php`, `content/blocos/{sobre-nos,lideranca,ministerios,ministerios-*}.json`.

## Páginas

| Página (protótipo) | Endereço | Blocos, na ordem |
|---|---|---|
| `sobre-nos.html` | `/sobre-nos/` | `hero` → `linha-tempo` → `historia` → `mvv` → `credo` |
| `lideranca.html` | `/lideranca/` | `hero` → `pastores` → `departamentos` |
| `ministerios.html` | `/ministerios/` | `hero` → `ministerios-grade` → `trocador` |
| `ministerios-circulo-de-oracao.html` | `/ministerios/circulo-de-oracao/` | `hero` → `fatos` → `corpo-indice` → `carrossel` → `passos` → `vizinhos` → `trocador` |
| `ministerios-escola-biblica-dominical.html` | `/ministerios/escola-biblica-dominical/` | `hero` → `fatos` → `corpo-indice` → `carrossel` → `passos` → `vizinhos` → `trocador` |
| `ministerios-ensinai.html` | `/ministerios/ensinai/` | `hero` → `fatos` → `corpo-indice` → `fotos-pendentes` → `passos` → `vizinhos` → `trocador` |
| `ministerios-familia.html` | `/ministerios/familia/` | `hero` → `fatos` → `corpo-indice` → `carrossel` → `passos` → `vizinhos` → `trocador` |
| `ministerios-missao-e-evangelismo.html` | `/ministerios/missao-e-evangelismo/` | `hero` → `fatos` → `corpo-indice` → `carrossel` → `passos` → `vizinhos` → `trocador` |
| `ministerios-mocidade.html` | `/ministerios/mocidade/` | `hero` → `fatos` → `corpo-indice` → `carrossel` → `passos` → `vizinhos` → `trocador` |
| `ministerios-criancas.html` | `/ministerios/criancas/` | `hero` → `fatos` → `corpo-indice` → `fotos-pendentes` → `passos` → `vizinhos` → `trocador` |

## Como montar uma página

1. O conteúdo de cada página fica em `content/blocos/<pagina>.json`: lista ordenada de `{"bloco": "ieadc/<nome>", "attrs": {…}}`. Imagens pelo nome do arquivo (`"foto": "f_hero.webp"`); o seed troca pelo ID da mídia. Links internos pelo caminho (`"link": "horarios-e-localizacao"`).
2. Copie **todos** os textos da referência (`docs/specs/referencias/<bloco>.html`, procure a seção da página) para os atributos. Não reescreva nada; `[a confirmar]` continua igual.
3. O seed `scripts/seed/20-paginas-p2.php` chama `ieadc_seed_pagina_blocos($caminho, $json)` (fornecida pelo M0), que gera o conteúdo com os comentários de bloco, aplica o modelo `larga` e só sobrescreve página que ainda tem o conteúdo de transição (ou com `IEADC_ATUALIZAR=1`).
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
