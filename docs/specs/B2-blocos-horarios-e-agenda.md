# B2 · Blocos de horários, local e agenda

**Onda 2 (19–22/11).** Depende de M1 e M2. `semana` e `local` primeiro (P1 usa na Início e em Horários).

## Arquivos

`blocos/semana/`, `blocos/tabela-horarios/`, `blocos/faixa/`, `blocos/local/`, `blocos/cultos-grade/`, `blocos/eventos-grade/`, `blocos/calendario/`.

## `ieadc/semana` (Início, Horários)

Painel com a programação fixa. Os cartões são **dados**, não regra de agenda (o voluntário edita).

| Atributo | Campo | Observação |
|---|---|---|
| `eyebrow`, `titulo`, `texto`, `botaoTexto`, `botaoLink` | cabeçalho | Na Início há texto e botão; em Horários, não |
| `dias` | lista: `sigla` (SEG…), `icone`, `titulo`, `sub` (small), `hora`, `link` | Os dias de semana |
| `domingo` | lista: `icone`, `titulo`, `sub`, `hora`, `link` | Os 3 encontros do domingo, no cartão largo |

Cada `<a class="dia">` e cada item do domingo leva `data-dia` (0–6) e `data-hora` (`HH:MM`), calculados da sigla e da hora. O selo "Próximo" é do `site.js` (M0), não do PHP.

## `ieadc/tabela-horarios` (Horários)

Duas colunas, cada uma com cabeçalho e tabela. Atributos: `colunas` (lista: `eyebrow`, `titulo`, `linhas` (texto-longo: uma linha por `Dia | Evento | Horário`)), `cabecalhos` (texto: `Dia|Evento|Horário`).

## `ieadc/faixa` (Início: temas do domingo; Horários: faixa com foto)

| Atributo | Campo |
|---|---|
| `foto`, `foco` | imagem, texto |
| `eyebrow`, `titulo` | texto |
| `itens` | lista: `rotulo` (`1º domingo`), `sub` (small opcional), `valor` (`Família`) |
| `destaqueIcone`, `destaqueTitulo`, `destaqueTexto` | caixa de vidro (Ceia do Senhor) |
| `botoes` | lista (as ocorrências com botões) |

Confira as 2 ocorrências da referência; os temas do domingo são os mesmos do M2 (texto editável aqui).

## `ieadc/local` (Início, Horários)

| Atributo | Campo |
|---|---|
| `eyebrow`, `titulo` | texto |
| `linhasEndereco` | lista: `icone`, `texto` (rico) |
| `onibus` | lista: `texto` (linhas de ônibus; vazio = não mostra) |
| `botoes` | lista |
| `rotuloPin` | texto (no mapa) |

O endereço padrão vem de `ieadc_info('endereco')` quando o campo estiver vazio. O mapa é o SVG ilustrado do protótipo (`MAPA` em `gen.py`), copiado para `blocos/local/mapa.svg.php`. **Sem** iframe do Google Maps (privacidade; o botão leva ao Maps).

## `ieadc/cultos-grade` (Horários)

Cabeçalho + `min-grade` com um `min-tile` por página de culto (M3: `ieadc_icone`, `ieadc_quando`, `ieadc_resumo`, `ieadc_cor`). Atributo `maisTexto` ("Conhecer o culto").

## `ieadc/eventos-grade` (Início: 3; Eventos: 6)

Cabeçalho + cartões de `ieadc_agenda_destaques($quantidade)`. Atributos: `quantidade`, cabeçalho. Cartão igual ao `ev_cartao` do gerador: dia da semana, dia, mês (3 letras maiúsculas), "até sáb 18" para avulsos de vários dias, chip pela categoria (`c-ceia`/`c-vigilia` → `sol` "Ceia e preparação"; `c-jovens` → `verde` "Mocidade"; `c-domingo` → "Culto dominical"; outros → "Evento"), selo "Hoje" quando a data é hoje.

## `ieadc/calendario` (Eventos)

Cabeçalho, legenda, e `meses` (número, padrão 3) blocos `<details class="mes">` a partir do mês atual; só o mês atual aberto. Células iguais ao `mes_html` do gerador (`fora`, `vazio`, `hoje`, `ev esp`). Resumo do mês: `ieadc_agenda_resumo_mes()`. Depois dos meses, os dois cartões de rodapé (`notaTitulo`, `notaTexto`, `vazioTitulo`, `vazioTexto`).

## Critérios de aceite

- [ ] Markup sem diferenças nas seções desses blocos com `IEADC_HOJE=2026-10-06`.
- [ ] Cadastrar um evento avulso no painel faz ele aparecer em `eventos-grade` e no `calendario` sem editar a página.
- [ ] Mudar a data do sistema para 2026-11-20 (`IEADC_HOJE`) abre novembro no calendário e os destaques mudam.
