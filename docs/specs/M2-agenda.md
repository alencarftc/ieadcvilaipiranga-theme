# M2 · Agenda

**Onda 1 (17–18/11).** Usado por B2 (semana, eventos, calendário) e B4 (próximas datas nas páginas de culto).

## Objetivo

Calcular em PHP a agenda da igreja pelas mesmas regras do gerador do protótipo (`prototipo/fonte/gen.py`, funções `eventos_do_dia`, `segundo_sabado`, `destaques`, `proximas`, `mes_html`), somando os eventos avulsos cadastrados no tipo `evento`. Com `IEADC_HOJE=2026-10-06` o resultado tem que ser idêntico ao protótipo.

## Arquivos

`wp-content/mu-plugins/ieadc-core/agenda.php`, `tests/php/agenda/*`. Pede ao M0 o `require` em `ieadc-core.php`.

## Regras (fonte: aba Informações gerais; decisões do Felipe de 06/10 e 08/10/2026)

| Quando | Hora | Título (exato) | Categoria | Especial |
|---|---|---|---|---|
| Toda segunda | `20h` | Culto de Oração | `c-culto` | não |
| Toda quarta | `20h` | Culto de Ensino | `c-ensino` | não |
| Toda quinta | `20h` | Culto dirigido pelo Círculo de Oração | `c-culto` | não |
| Toda sexta | `19h30` | Ensinai (Curso de Teologia Ministerial) | `c-ensino` | não |
| Sexta antes do 2º sábado, **meses ímpares** | `22h` | Vigília | `c-vigilia` | sim |
| 2º sábado, **meses pares** | `8h` | Consagração | `c-vigilia` | sim |
| 2º sábado (todo mês) | `19h30` | Ceia do Senhor | `c-ceia` | sim |
| Todo domingo | `9h` | Café comunitário | `c-domingo` | não |
| Todo domingo | `9h30` | EBD (Escola Bíblica Dominical) | `c-ensino` | não |
| Todo domingo | `19h` | Culto dominical: `<tema>` | `c-domingo` | sim |

Tema do domingo pela ordem no mês (`floor((dia - 1) / 7)`): 1º Família, 2º Missões e Evangelismo, 3º Jovens e Adolescentes, 4º Mulheres, 5º Crianças.

"2º sábado" = primeiro sábado do mês + 7 dias. Ordem dos itens no dia: a da tabela; eventos avulsos entram **antes** dos fixos.

Descrições (usadas nos cartões de evento):

| Título | Descrição |
|---|---|
| Vigília | Na sexta-feira que antecede a ceia. Nos meses ímpares. |
| Consagração | No sábado da ceia, pela manhã. Nos meses pares. |
| Ceia do Senhor | Todo 2º sábado do mês. |
| Culto dominical: … | Cada domingo do mês tem um tema no culto dominical. |
| Evento avulso | O resumo (excerpt) do evento |

Links: Vigília e Consagração → `vigilia-e-consagracao`; Ceia → `ceia-do-senhor`; Culto dominical → `culto-dominical`; avulso → `evento_link` ou a página do evento.

## Eventos avulsos (tipo `evento`, campos do M3)

Viram ocorrências em todos os dias de `evento_data` a `evento_data_fim` (ou só `evento_data`), com `hora` = `evento_horario` (texto livre, ex.: `[horário a confirmar]`), `categoria` = `evento_categoria` (padrão `c-culto`), `especial` = sim. Só eventos publicados.

Para os testes, o seed do M3 cadastra o avulso que está no protótipo: Confraternização dos jovens, 17 e 18/10/2026, `[horário a confirmar]`, `c-jovens`, descrição "Dias 17 e 18 de outubro. [Local e programação a confirmar]".

## API (contrato; ver plano, seção 5.1)

```php
function ieadc_hoje(): DateTimeImmutable;                     // America/Sao_Paulo; respeita IEADC_HOJE
function ieadc_agenda_dia(DateTimeImmutable $d): array;       // ocorrências de um dia
function ieadc_agenda(DateTimeImmutable $de, DateTimeImmutable $ate): array;  // inclusive
function ieadc_agenda_destaques(int $n, ?DateTimeImmutable $desde = null): array; // = destaques()
function ieadc_agenda_proximas(array $titulos, int $n = 4): array; // = proximas(); casa título igual ou "título:" no começo
function ieadc_agenda_resumo_mes(int $ano, int $mes): string;    // = resumo de mes_html(): especiais sem "Culto dominical", sem repetir, " · "
```

`Ocorrencia`: `['data' => 'Y-m-d', 'hora' => '20h', 'titulo' => …, 'categoria' => 'c-…', 'especial' => bool, 'descricao' => …, 'link' => URL, 'origem' => 'regra'|ID, 'data_fim' => 'Y-m-d'|null]`.

`ieadc_agenda_destaques`: anda dia a dia a partir de `$desde` (padrão hoje) e pega as ocorrências especiais sem repetir título até juntar `$n`. Para avulsos de vários dias, `data_fim` preenchido (o cartão mostra "até dom 18").

Cache: `wp_cache` por dia; limpa quando um `evento` é salvo.

## Testes (`tests/php/agenda/`)

Com hoje = 06/10/2026:

- [ ] `ieadc_agenda_destaques(3)` = Consagração 10/10 8h, Ceia do Senhor 10/10 19h30, Culto dominical: Missões e Evangelismo 11/10 19h (igual à Início do protótipo).
- [ ] `ieadc_agenda_destaques(6)` igual aos 6 cartões da página Eventos do protótipo (inclui a Confraternização 17–18/10).
- [ ] Outubro/2026 tem Consagração (mês par) e não tem Vigília; novembro/2026 tem Vigília na sexta 13/11 às 22h.
- [ ] O 5º domingo de novembro/2026 (29/11) é "Culto dominical: Crianças".
- [ ] `ieadc_agenda_proximas(['Ceia do Senhor'], 4)` = 10/10, 14/11, 12/12/2026 e 09/01/2027.
- [ ] `ieadc_agenda_resumo_mes(2026, 10)` = o `<small>` de outubro no protótipo.
- [ ] Sem `IEADC_HOJE`, `ieadc_hoje()` usa o fuso de São Paulo (teste com 23h30 de Brasília = dia certo).

## Fora do escopo

HTML (é dos blocos do B2/B4). Cadastro do tipo `evento` (M3).
