# B1 · Blocos de topo e navegação

**Onda 2 (19–20/11). Prioridade:** todas as páginas usam o `hero`; P1 começa quando ele estiver pronto.

Leia antes: [convenções](00-convencoes.md). HTML esperado: `referencias/<bloco>.html`.

## Arquivos

`blocos/hero-home/`, `blocos/hero/`, `blocos/trocador/`, `blocos/vizinhos/`.

## `ieadc/hero-home` (1 ocorrência: Início)

| Atributo | Campo | Exemplo da referência |
|---|---|---|
| `foto` | imagem | `f_hero` |
| `eyebrow` | texto | Bem-vindo à |
| `sigla`, `nome` | texto | IEADC / Vila Ipiranga |
| `lead` | texto | Uma família de fé no Capão Raso, em Curitiba. |
| `botoes` | lista: `icone`, `titulo`, `sub`, `link`, `estilo` (`prim`/`sec`) | Ver horários · Cultos da semana / Como chegar · Endereço e mapa |
| `frase` | texto | Venha nos visitar. (cursiva) |

## `ieadc/hero` (24 ocorrências)

Três variantes, pela classe da seção: `hero pagina` (com foto), `hero sem-foto`, e com `migalha` (cultos e ministérios).

| Atributo | Campo | Observação |
|---|---|---|
| `variante` | opção: `pagina`, `sem-foto` | |
| `foto`, `foco` | imagem, texto | `foco` = `background-position` (ex.: `center 18%`) |
| `migalha` | lista: `texto`, `link` | Último item sem link. Aparece só se preenchida |
| `eyebrow`, `titulo` | texto, rico | `titulo` aceita `<br>` e `<span class="azul">` |
| `lead` | texto-longo | |
| `botoes` | lista: `texto`, `link`, `estilo` (`''`, `out`, `branco`, `sol`), `icone` | |
| `frase` | texto | Frase cursiva (`script script-home`) |
| `extra` | rico | Conteúdo raro que algumas páginas têm abaixo do lead (ver referência); usar só se a referência pedir |

Confira as 24 ocorrências da referência: o bloco precisa cobrir todas sem HTML cru.

## `ieadc/trocador` (14 ocorrências: cultos e ministérios)

Sem campos de texto: lista as páginas do grupo.

| Atributo | Campo | Observação |
|---|---|---|
| `grupo` | opção: `cultos`, `ministerios` | `ministerios` = filhas de `ministerios` por `menu_order`; `cultos` = páginas com meta `ieadc_regra` (M3) na ordem do menu |
| `rotulo` | texto | "Cultos" / "Ministérios" |

A página atual ganha `class="on" aria-current="page"`. Ícone do item = meta `ieadc_icone`.

## `ieadc/vizinhos` (13 ocorrências)

Anterior e próximo do mesmo grupo (circular), mais o link "Todos".

| Atributo | Campo | Observação |
|---|---|---|
| `grupo` | opção: `cultos`, `ministerios` | |
| `rotuloAnterior`, `rotuloProximo` | texto | "Culto anterior"/"Próximo culto" ou "Ministério anterior"/"Próximo ministério" |
| `todosTexto`, `todosLink` | texto, link | "Todos os horários" → horários |

## Critérios de aceite

- [ ] `./bin/testar.sh markup` sem diferenças nas seções desses blocos em todas as páginas do `mapa-secoes.json` (com as páginas montadas pelo seu seed de teste local ou pelo P1–P3).
- [ ] Trocar a foto, o título e um botão do `hero` no editor muda o site; prévia no editor igual.
- [ ] Reordenar um ministério (ordem da página) muda o `trocador` e os `vizinhos` sem editar os blocos.
