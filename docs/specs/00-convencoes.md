# Convenções (valem para todas as specs)

## Ambiente

- `cp .env.example .env` e `./bin/setup.sh` (Git Bash no Windows). Site em `http://localhost:8080`.
- Testes: `./bin/testar.sh` (entregue pelo M1) roda `php -l`, testes PHP e testes visuais. `./bin/testar.sh visual inicio` testa uma página.
- Data dos testes: o site local mostra o dia real; os testes mandam o cabeçalho `X-Ieadc-Hoje: 2026-10-06` (só vale no ambiente `local`) e com ela a agenda do site sai igual à do protótipo. `IEADC_HOJE` no `.env` fica vazio.

## Git e PR

- Branch `feat/<id>-<nome>` (ex.: `feat/b1-hero`). Um PR por spec; PRs grandes podem ser quebrados por bloco.
- Só altere os arquivos listados em "Arquivos" da sua spec. Precisa de algo fora disso? Use um stub local e abra issue para o dono.
- Mensagens de commit e textos em português.
- PR com: o que entrega, prints do teste visual (antes/depois quando houver), resultado do `./bin/testar.sh`.

## Blocos (`ieadc/<nome>`)

Pasta `wp-content/themes/ieadc-vila-ipiranga/blocos/<nome>/` com dois arquivos:

```
block.json   render.php
```

`block.json` mínimo:

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "ieadc/<nome>",
  "title": "<Nome em português>",
  "category": "ieadc",
  "icon": "<dashicon>",
  "description": "<uma frase>",
  "supports": { "html": false, "align": false },
  "attributes": { },
  "render": "file:./render.php"
}
```

### Atributos comuns de seção (todos os blocos que geram `<section>`)

| Atributo | Tipo | Uso |
|---|---|---|
| `secao` | string | Classes do `<section>` (`sec`, `sec gelo curva-top`, `sec glow`…). Padrão = o da primeira ocorrência na referência |
| `secaoEstilo` | string | Estilo inline do `<section>` quando a referência tem (`padding-top:0`…) |
| `ancora` | string | `id` do `<section>` |

### Cabeçalho de seção (`cab`)

Quase toda seção começa com o mesmo cabeçalho. Use os atributos `eyebrow`, `titulo`, `texto`, `botaoTexto`, `botaoLink` e a função `ieadc_cab($attrs)` do M1, que gera exatamente:

```html
<div class="cab"><div><span class="eyebrow">…</span><h2 class="h2">…</h2><svg class="swoosh" …></svg><p class="txt">…</p></div><a class="btn " href="…">…<svg class="ico">…</svg></a></div>
```

(o `<p>` e o `<a>` só aparecem se preenchidos).

### Vocabulário de campos do editor genérico (M1)

Cada atributo pode ter uma chave `"ieadc"` que diz ao editor qual campo mostrar:

| `"ieadc": {"campo": …}` | Campo no painel | Tipo do atributo |
|---|---|---|
| `"texto"` (padrão para string) | Campo de uma linha | string |
| `"texto-longo"` | Área de texto (parágrafos separados por linha em branco) | string |
| `"rico"` | Área de texto que aceita `<b>`, `<i>`, `<a>`, `<br>`, `<span class="azul">` | string |
| `"imagem"` | Seletor da biblioteca de mídia | number (ID) |
| `"imagens"` | Galeria (vários IDs, ordenáveis) | array de number |
| `"link"` | Link para página do site ou URL | string |
| `"icone"` | Lista dos ícones de `inc/icones.php` | string |
| `"opcao"`, com `"opcoes": [...]` | Lista suspensa | string |
| `"lista"`, com `"itens": {sub-atributos}` | Itens repetidos (adicionar, remover, reordenar) | array de objetos |

Rótulo do campo: `"ieadc": {"rotulo": "Texto do botão"}`. Ajuda: `"ajuda": "…"`.

### Regras do `render.php`

1. O HTML gerado é **igual** ao da referência (`docs/specs/referencias/<nome>.html`): mesmas tags, classes, ordem e estilos inline. Espaços e quebras de linha não importam.
2. Escape tudo: `esc_html`, `esc_attr`, `esc_url`; campos `"rico"` passam por `ieadc_kses_rico()`.
3. Texto com `[...]` vira `<mark class="pend">[...]</mark>` por `ieadc_pend()` (M1). Nunca escreva o `<mark>` à mão.
4. Imagem de fundo: `style="background-image:url(<?php echo esc_url(ieadc_img($id)); ?>)"`, com `background-position` quando o atributo `foco` estiver preenchido.
5. Ícone: `ieadc_icone('nome')`. Se o ícone não existir em `inc/icones.php`, peça ao M0 (os nomes estão no `I = {…}` de `prototipo/fonte/gen.py`).
6. Links internos com `ieadc_link('caminho')`, nunca URL fixa.
7. Campo vazio não gera tag vazia.
8. Bloco que lê dados (agenda, posts, páginas) recebe o "como" por atributo (quantidade, filtro) e o "o quê" do WordPress.

### Teste de markup de cada bloco

O M1 entrega `tests/visual/markup.spec.js`: renderiza a página do WordPress e compara o HTML normalizado de cada seção com a mesma seção do protótipo (`mapa-secoes.json` diz qual é qual). Seu bloco está pronto quando a comparação das páginas que o usam dá **zero diferenças**.

## CSS

- `assets/css/prototipo.css` é congelado (cópia do protótipo). Não edite.
- Ajuste necessário vai em `assets/css/ajustes.css`, dentro de um comentário `/* === <ID da spec>: <bloco> === */`, com o motivo.

## Textos e dados

- Textos vêm da referência (que já tem os textos aprovados da Copies). Não reescreva.
- `[a confirmar]` continua `[a confirmar]` até o dado chegar.
- Fotos: só as de `content/media/`, na mesma posição do protótipo. Nada de foto repetida entre páginas.
