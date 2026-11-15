# B3 · Blocos de listas e cartões

**Onda 2 (19–24/11).** Depende de M1 e M3. `boas-vindas`, `chamadas`, `ministerios-trilho`, `artigos-grade` e `galeria-faixa` primeiro (Início).

## Arquivos

`blocos/boas-vindas/`, `blocos/chamadas/`, `blocos/ministerios-trilho/`, `blocos/ministerios-grade/`, `blocos/artigos-grade/`, `blocos/artigos-categorias/`, `blocos/galeria-faixa/`, `blocos/fotos/`, `blocos/fotos-pendentes/`, `blocos/pastores/`, `blocos/departamentos/`.

## Blocos

| Bloco | Onde | Atributos | Dados do WordPress |
|---|---|---|---|
| `boas-vindas` | Início | `foto`; `eyebrow`, `titulo`; `texto` (texto-longo); `aviso` (texto, opcional); `botoes`; `valores` (lista: `icone`, `texto`) | — |
| `chamadas` | Início, Aceitar Jesus | `chamadas` (lista de 2: `foto`, `titulo`, `estilo` (`''`/`azul`), `botoes`) | — |
| `ministerios-trilho` | Início | cabeçalho com botão | Filhas de `ministerios`: imagem destacada, `ieadc_icone`, título, `ieadc_resumo` |
| `ministerios-grade` | Ministérios | cabeçalho; `maisTexto` | Filhas de `ministerios`: `ieadc_cor`, `ieadc_icone`, `ieadc_quando`, título, `ieadc_resumo` |
| `artigos-grade` | Início (3), Artigos (3), artigo (2 "Continue lendo") | cabeçalho com botão; `quantidade`; `colunas` (`g2`/`g3`); `excluirAtual` (bool) | Posts: imagem destacada, categoria (chip com ícone), selo "Exemplo" se `ieadc_exemplo`, título, resumo |
| `artigos-categorias` | Artigos | cabeçalho; `cartoes` (lista: `chip`, `icone`, `titulo`, `texto`, `tracejado` (bool)) | — |
| `galeria-faixa` | Início | `eyebrow`, `titulo`, `texto`, `botoes`; `fotos` (imagens, 4) | — |
| `fotos` | Galeria | cabeçalho; `fotos` (imagens); `nota` (texto) | — (o Instagram fica para depois do lançamento) |
| `fotos-pendentes` | Crianças, Ensinai | cabeçalho; `icone`; `texto` (rico) | — |
| `pastores` | Liderança | cabeçalho; `pessoas` (lista: `foto` (vazio = iniciais), `cargo`, `nome`, `nota`) | — |
| `departamentos` | Liderança | cabeçalho; `nota` | `ieadc_departamentos()` (M3); líder sem foto = `foto-lider` com ícone e `title="Foto em breve"` |

Ícone de cada categoria de artigo (chip): pegue da referência (`Mensagem`, `Estudo`, `Reflexão`); `Notícia` usa `megafone`.

## Critérios de aceite

- [ ] Markup sem diferenças nas seções desses blocos.
- [ ] Publicar um 4º artigo faz ele aparecer em primeiro na Início e em Artigos.
- [ ] Pôr a foto de um líder no painel (Departamentos) troca o círculo pela foto, recortada em círculo, sem mudar o layout.
- [ ] Nenhuma foto aparece em duas seções da mesma página (teste automático no `markup.spec.js`: URLs de fundo repetidas na página falham, exceto capa de artigo onde o artigo é listado).
