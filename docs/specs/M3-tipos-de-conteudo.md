# M3 · Tipos de conteúdo, campos e dados

**Onda 1 (17–18/11).** Usado por B3 (listas) e M2 (eventos avulsos).

## Objetivo

Modelar o conteúdo que muda (eventos, artigos, ministérios, cultos, departamentos) para os voluntários cadastrarem pelo painel, e semear os dados do protótipo.

## Arquivos

`wp-content/mu-plugins/ieadc-core/tipos.php`, `wp-content/mu-plugins/ieadc-core/departamentos.php`, `wp-content/mu-plugins/ieadc-core/campos.php`, `scripts/seed/40-ministerios.php`, `50-cultos.php`, `60-departamentos.php`, `70-artigos.php`, `80-eventos.php`.

## Modelo

| Conteúdo | Onde | Campos (meta, `show_in_rest`) | Painel |
|---|---|---|---|
| Evento avulso | tipo `evento` (já existe) | `evento_data`, `evento_data_fim` (Y-m-d), `evento_horario` (texto), `evento_local`, `evento_categoria` (`c-culto`, `c-ensino`, `c-domingo`, `c-ceia`, `c-vigilia`, `c-jovens`), `evento_link` | Caixa "Quando e onde" na lateral |
| Artigo | post | categorias **Mensagem**, **Estudo**, **Reflexão**, **Notícia**; meta `ieadc_exemplo` (bool: mostra o selo "Exemplo") | — |
| Mensagem em vídeo/áudio | tipo `mensagem` (já existe) | `mensagem_pregador`, `mensagem_url` | — |
| Ministério | página filha de `ministerios` | `ieadc_icone`, `ieadc_resumo` (1 frase), `ieadc_quando` (ex.: "Quinta-feira, às 20h"), `ieadc_cor` (`c1`…`c7`), imagem destacada = foto do cartão | Caixa "Cartão do ministério" |
| Culto fixo | página (as 6 de cultos) | `ieadc_icone`, `ieadc_resumo`, `ieadc_quando`, `ieadc_cor`, `ieadc_regra` (títulos da agenda, um por linha) | Caixa "Cartão do culto" |
| Departamento | tipo novo `departamento` (não público, `show_ui`) | título; `lideres` (um por linha); `complemento` (ex.: "e equipe"); `ordem`; foto de cada líder: meta `fotos_lideres` (IDs na mesma ordem; vazio = círculo com ícone) | Lista ordenável |

Caixas do painel feitas em PHP (`add_meta_box`), com `nonce` e `sanitize_*`. Sem plugin de campos.

Função de leitura: `ieadc_departamentos(): array` → `[['nome', 'lideres' => [['nome', 'foto' => ID|0]], 'complemento']]`, ordenado por `ordem`.

## Seed (dados do protótipo)

Fonte: `prototipo/fonte/minis.py` (ministérios), `cultos.py` (cultos), `gen.py` (`DEP`, `ART`, `EXTRAS`). Copiar os valores exatamente.

- **40-ministerios:** meta das 7 páginas filhas (ícone, resumo, quando, cor `c1`…`c7` na ordem do menu) e imagem destacada (`f_mp_<slug>` em `content/media`).
- **50-cultos:** meta das 6 páginas de culto (ícone, resumo = `curto`, quando, cor, regra).
- **60-departamentos:** todos os itens de `DEP` com líderes separados por `pessoas()` do gerador.
- **70-artigos:** categoria e `ieadc_exemplo = 1` dos 3 artigos; imagem destacada (`f_art_tema`, `f_art_oracao`, `f_biblia`); resumo.
- **80-eventos:** Confraternização dos jovens (dados no M2).

Tudo idempotente e sem sobrescrever o que foi editado no painel.

## Critérios de aceite

- [ ] Depois do seed, `./bin/wp.sh post list --post_type=departamento` lista todos os departamentos do protótipo, na ordem.
- [ ] Editar o resumo de um ministério no painel e rodar o seed de novo não desfaz a edição.
- [ ] Caixas do painel salvam e mostram os valores; campos aparecem na REST API.
- [ ] Usuário "Equipe de oração" não vê Departamentos nem Eventos.

## Fora do escopo

HTML das listas (B3), regras da agenda (M2).
