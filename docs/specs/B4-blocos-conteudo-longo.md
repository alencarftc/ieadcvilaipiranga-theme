# B4 · Blocos de conteúdo longo

**Onda 2 (19–24/11).** Depende de M1 (e de M2 só para as "Próximas datas" do `corpo-indice`). `pedido-oracao`, `contato-lista` e `capa` primeiro (P1).

## Arquivos

`blocos/pedido-oracao/`, `blocos/contato-lista/`, `blocos/capa/`, `blocos/fatos/`, `blocos/corpo-indice/`, `blocos/passos/`, `blocos/carrossel/`, `blocos/leitura/`, `blocos/linha-tempo/`, `blocos/mvv/`, `blocos/credo/`, `blocos/historia/`, `blocos/aceitar-passos/`, `blocos/artigo-topo/`, `blocos/artigo-corpo/`.

## Blocos

| Bloco | Onde | Atributos |
|---|---|---|
| `pedido-oracao` | Pedidos de oração | (formulário = `do_shortcode('[ieadc_pedido_oracao]')`); lateral: `eyebrow`, `titulo`, `texto`, `versiculo`, `referencia` |
| `contato-lista` | Fale conosco | (formulário = `[ieadc_contato]`); lateral: `eyebrow`, `titulo`, `itens` (lista: `icone`, `titulo`, `texto` (rico)) |
| `capa` | Fale conosco | `foto`, `foco` |
| `fatos` | 13 (cultos e ministérios) | `fatos` (lista: `icone`, `rotulo`, `valor` (rico)) |
| `corpo-indice` | 13 (cultos e ministérios) | `aviso`; `secoes` (lista: `ancora`, `titulo`, `tipo` (`texto`, `versiculo+texto`, `objetivos`, `datas`), `texto` (texto-longo/rico), `versiculo`, `referencia`, `itens` (lista de texto)); lateral: `indiceTitulo`, `lideresTitulo`, `lideres` (texto-longo), `botoes`. Tipo `datas` usa `ieadc_agenda_proximas()` com a meta `ieadc_regra` da página. Fase 5C: nos tipos `objetivos` e `encontros` o campo `texto`, se preenchido, vira uma nota depois da lista; links `<a href>` nos textos e nos itens recebem `class="lk"` (e `rel="noopener"` quando apontam para outro site) |
| `passos` | 13 | cabeçalho; `passos` (lista de texto); `variante` (`passos` = `<ol class="passos">` numerado / `passo-unico` = um cartão) |
| `carrossel` | 11 | cabeçalho; `fotos` (imagens); `legenda` (texto: "<Nome> · foto {n}") |
| `leitura` | Política de privacidade | `conteudo` (rico, aceita também `<h3>` e `<p>`) |
| `linha-tempo` | Sobre nós | cabeçalho; `marcos` (lista: `quando`, `titulo`, `texto`) |
| `mvv` | Sobre nós | cabeçalho; `missao`, `visao` (texto); `valores` (lista: `icone`, `texto`) |
| `credo` | Sobre nós | `eyebrow`, `titulo`, `texto`; `itens` (lista de texto). Texto em `prototipo/fonte/credo.txt` |
| `historia` | Sobre nós | `eyebrow`, `titulo`, `texto` (texto-longo); `foto`; `dirigentesTitulo`; `dirigentes` (lista de texto, do atual ao mais antigo) |
| `aceitar-passos` | Aceitar Jesus | cabeçalho + `texto`; `passos` (lista: `titulo`, `texto`, `versiculo`, `referencia`); o que muda (`mudaTitulo`, `muda` lista); botão "Quero aceitar Jesus"; carta do pastor em `<details>` (`cartaTitulo`, `carta` texto-longo). Ver a referência inteira: a ordem é obrigatória (decisão de 09/10) |
| `artigo-topo` | 3 artigos | `migalha`; o resto vem do post: categoria, selo exemplo, título, resumo (`lead`), autor e data (do próprio post), imagem destacada |
| `artigo-corpo` | 3 artigos | `versiculo`, `referencia`; o corpo vem do conteúdo do post (blocos core: parágrafo e título nível 2) renderizado dentro de `<article class="artigo corpo-art">` |

Para os artigos, o modelo de post (`single.php` do tema filho ou o template `larga`) monta: `artigo-topo` + `artigo-corpo` + `artigos-grade` (B3, `excluirAtual`). Combine com o B3 o nome do atributo.

## Critérios de aceite

- [ ] Markup sem diferenças nas seções desses blocos.
- [ ] O formulário de pedido de oração envia (Mailpit recebe) e mostra a mensagem de sucesso no lugar certo; com erro, mostra a mensagem de erro do protótipo.
- [ ] Mudar a data com `IEADC_HOJE` muda as "Próximas datas" das páginas de culto.
- [ ] Um voluntário consegue escrever um artigo novo só com parágrafos e títulos e ele sai com o visual dos artigos de exemplo.
