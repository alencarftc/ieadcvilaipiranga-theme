# M1 · Infra de blocos, editor genérico e testes de fidelidade

**Onda 1 (17–18/11). Caminho crítico:** B1–B4 dependem disto.

## Objetivo

1. Registrar automaticamente todos os blocos `ieadc/*`.
2. Dar a eles um editor no painel sem que cada agente escreva JavaScript.
3. Entregar as funções comuns de renderização.
4. Entregar os testes que provam a fidelidade ao protótipo.

## Arquivos

`inc/blocos.php`, `inc/helpers.php`, `blocos/_editor/editor.js`, `blocos/_editor/editor.css`, `tests/visual/**`, `tests/php/bootstrap.php`, `bin/testar.sh`, `package.json` (só dependências de teste), `.github/workflows/testes.yml`. Pede ao M0 uma linha em `functions.php` para carregar `inc/blocos.php` e `inc/helpers.php`.

## 1. Registro automático (`inc/blocos.php`)

- No `init`, para cada `blocos/*/block.json` (exceto `_editor`), `register_block_type($pasta)`.
- Registrar o script `ieadc-blocos-editor` (`blocos/_editor/editor.js`, dependências `wp-blocks`, `wp-element`, `wp-components`, `wp-block-editor`, `wp-server-side-render`, `wp-i18n`, `wp-data`) e associá-lo a todos os blocos `ieadc/*` como `editor_script`.
- Carregar `prototipo.css` e `ajustes.css` no editor (`add_editor_style`, já existe) para a prévia ficar igual ao site.

## 2. Editor genérico (`blocos/_editor/editor.js`)

JavaScript puro (sem JSX e sem build). Para cada bloco `ieadc/*` registrado no servidor:

- `edit`: `InspectorControls` com um painel "Conteúdo" e os campos gerados a partir de `attributes` e da chave `ieadc` (vocabulário em [00-convencoes](00-convencoes.md)); no centro, `ServerSideRender` com `block` e `attributes`.
- `save`: `null` (bloco dinâmico).
- Campo `lista`: cada item num `PanelBody` recolhível com os sub-campos, e botões Adicionar, Remover, Subir e Descer.
- Campo `imagem` e `imagens`: `MediaUpload` + miniatura.
- Campo `link`: `URLInput` (busca páginas do site).
- Atributos `secao`, `secaoEstilo` e `ancora` ficam num painel "Avançado" recolhido.

## 3. Funções comuns (`inc/helpers.php`)

| Função | Faz |
|---|---|
| `ieadc_pend(string $texto): string` | Só escapa o texto (até a fase 5B trocava cada `[...]` por `<mark class="pend">[...]</mark>`; não marca mais) |
| `ieadc_kses_rico(string $html): string` | Permite só `b`, `strong`, `i`, `em`, `br`, `a[href]`, `span[class=azul]` e `small` (a `mark.pend` e a marcação de `[...]` saíram na fase 5B) |
| `ieadc_paragrafos(string $texto, string $classe = ''): string` | Linha em branco separa `<p>` |
| `ieadc_img(int $id, string $tamanho = 'full'): string` | URL da mídia (vazio se não houver) |
| `ieadc_fundo(int $id, string $foco = '', string $extra = ''): string` | Atributo `style` completo de fundo, igual ao protótipo |
| `ieadc_cab(array $a): string` | Cabeçalho de seção (HTML em [00-convencoes](00-convencoes.md)) |
| `ieadc_swoosh(string $classe = 'swoosh'): string` | O traço sob títulos |
| `ieadc_secao_abre(array $a, string $padrao): string` / `ieadc_secao_fecha()` | `<section class id style>` com os atributos comuns |
| `ieadc_btn(string $texto, string $link, string $classe = '', string $icone = 'seta'): string` | Botão igual ao `btn()` do gerador |

Testes PHP para cada função (`tests/php/helpers/`).

## 4. Testes de fidelidade (`tests/visual/`)

Playwright (Node 20+), rodando contra `http://localhost:8080` e contra o protótipo servido de `prototipo/` (as requisições a `img/*` são atendidas por `content/media/*`).

- **`markup.spec.js`:** para cada página de `docs/specs/mapa-secoes.json`, pega o `<main>` do WordPress e o do protótipo, divide nas seções de primeiro nível e compara uma a uma depois de normalizar: espaços em branco, ordem de atributos, URL de imagem → nome do arquivo, `x.html` → `/x/`, atributos `data-dia`, `data-hora` e o selo "Próximo" (calculados no navegador). Falha mostra o diff da seção.
- **`visual.spec.js`:** screenshot da página inteira em 1440, 900 e 390 px no WordPress e no protótipo (a faixa `.proto` escondida, fontes carregadas, animações desligadas, relógio do navegador em 06/10/2026 10:00 de Brasília). Compara com `pixelmatch` e falha acima de 1,0% (1440 e 390) ou 1,5% (900). Salva `tests/visual/saida/<pagina>-<largura>-{wp,proto,diff}.png`.
- **`interacao.spec.js`:** menu do celular (abre, fecha, Esc), submenus por teclado (Tab), carrossel (botões e pontos), formulários (sucesso e erro), selo "Próximo".
- **`bin/testar.sh [php|markup|visual|interacao] [pagina]`:** roda tudo ou uma parte.
- **CI (`.github/workflows/testes.yml`):** em PR, sobe o `docker compose`, roda o setup com `IEADC_HOJE=2026-10-06` e `./bin/testar.sh`. Anexa as imagens de diferença como artefato.

## Critérios de aceite

- [ ] Um bloco de exemplo `blocos/_exemplo/` (removido no fim) com um campo de cada tipo aparece no editor, salva e renderiza; prévia igual ao site.
- [ ] Com as páginas de transição atuais (HTML do protótipo), `markup` e `visual` passam em todas as 28 páginas. Isso prova que o teste está calibrado: o que falhar depois é do bloco, não do teste.
- [ ] `interacao` passa na Início e em Pedidos de oração.
- [ ] CI roda em menos de 15 minutos.

## Fora do escopo

Os blocos em si (B1–B4).
