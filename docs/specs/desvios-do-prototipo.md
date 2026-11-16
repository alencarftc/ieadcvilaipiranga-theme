# Desvios do protótipo

O protótipo v3 (`prototipo/`) é a referência dos testes de fidelidade. Quando uma mudança aprovada **precisa** diferir dele (texto de pendência resolvida, data real), ela entra nos dois lados ao mesmo tempo: nos blocos (`content/blocos/*.json`, padrões em `blocos/*/block.json`) e nos HTMLs de referência (`prototipo/*.html`, `docs/specs/referencias/*.html` e `content/paginas/*.html`), para `./bin/testar.sh` seguir verde. Este arquivo registra cada desvio: página, antes, depois e motivo.

Regra usada na fase 1 (16/11/2026), decidida sem a revisão do Felipe: **(A)** o que se resolve sem fato novo foi reescrito em português correto, sem afirmar nada que não esteja nas fontes do repositório; **(B)** o que exige dado real (nomes, anos, horários, regras, datas) **não foi inventado**: o amarelo ficou o menor possível, com frase curta e neutra, e a pergunta está em `docs/operacao/pendencias-para-o-felipe.md`.

O gerador antigo do protótipo (`prototipo/fonte/*.py`) **não** foi atualizado: os HTMLs de `prototipo/` passam a ser a fonte da verdade e não devem ser regerados por ele.

## Textos amarelos resolvidos (A)

| Página | Antes | Depois | Motivo |
|---|---|---|---|
| Início (boas-vindas) | `Opção 2 das boas-vindas. [o pastor escolhe entre as duas opções]` | removido (fica a opção 1, o texto "A paz do Senhor!") | A opção 2 era aviso de trabalho; a opção 1 é a aprovada no protótipo. Campo "Aviso" do bloco fica vazio. |
| Artigos de exemplo (Alcançar Curitiba, Até que ele venha, Orai uns pelos outros) | `Autor [a confirmar] · [data da publicação]`, avatar `?` | `Comunicação IEADC Vila Ipiranga · 5, 3 e 4 de outubro de 2026` (data real do post), avatar `CI` | Quem publica é a Comunicação; a data é a do próprio post. Artigos novos usam o autor do WordPress. |
| Artigos | Cartão Áudios: `[a confirmar]` | `As mensagens em áudio ainda não estão disponíveis nesta página.` | Não afirma data nem formato; só avisa que o espaço existe. |
| Culto de Ensino | Quem ensina: `[a confirmar]` | `A liderança da igreja` | A própria página já diz "O ensino é conduzido pela liderança da igreja". |
| Culto do Círculo de Oração | `… louva a Deus no culto. [Confirmar se o coral participa toda semana]` | `… louva a Deus no culto.` | Era uma anotação de trabalho; a frase já não afirma frequência. |
| Eventos | `… reunião de início de ano. [datas de 2027 a confirmar]` | `… reunião de início de ano. Quando forem definidas, as datas aparecem no calendário acima.` | O calendário mostra os eventos cadastrados; o texto descreve isso. |
| Liderança | `Foto [a confirmar]` sob Ev. Robson Stein e Pb. Manoel Lima | removido (ficam as iniciais RS e ML) | Aviso interno para o visitante não faz sentido; as fotos reais estão nas perguntas ao Felipe. |
| Ministérios: Família, Missão e Evangelismo, Mocidade (texto de abertura) | `… [Outros encontros a confirmar]`, `… [Ações e encontros a confirmar]`, `… [Encontros próprios a confirmar]` | removidos | Repetiam o aviso que já está no corpo da página. |
| Ministérios: Crianças e Ensinai (galeria) | `… autorização de imagem dos pais ou responsáveis. [fotos autorizadas a confirmar]` | `… autorização de imagem dos pais ou responsáveis.` | A regra continua; a pendência (fotos) foi para a lista de perguntas. |
| Ministérios: Ensinai (Como participar) | `Procure os líderes do Ensinai: [nomes a confirmar]. Eles vão receber você e explicar como participar.` | `Procure a liderança do Ensinai. Ela vai receber você e explicar como participar.` | Orienta o visitante sem citar nomes. |
| Política de privacidade | `Última atualização: [data da publicação]` | `Última atualização: <data da última alteração da página>` (hoje, 16 de novembro de 2026) | Data real: o bloco `hero` troca `{atualizacao}` pela data de modificação da página. O teste de markup ignora o valor da data (`tests/visual/lib/normalizar.js`). |

## Textos amarelos encurtados (B, aguardam dado real)

O amarelo continua, só mais curto (a pergunta completa está em `pendencias-para-o-felipe.md`).

| Página | Antes | Depois |
|---|---|---|
| Ceia do Senhor | `[Quem pode participar dos elementos e orientações para visitantes a confirmar]` | `[orientações para visitantes a confirmar]` |
| Culto de Ensino | `[Temas ou séries de estudo a confirmar]` | `[temas de estudo a confirmar]` |
| Culto de Oração | `[Ordem do culto e dirigente a confirmar]` | `[ordem do culto a confirmar]` |
| Círculo de Oração (ministério) | `[Dias e horários dos ensaios do coral a confirmar]` | `[ensaios do coral a confirmar]` |
| Crianças | `[Classes por idade e atividades especiais a confirmar]` | `[classes e atividades a confirmar]` |
| Ensinai | `[Duração do curso, disciplinas, forma de inscrição e certificado a confirmar]` | `[duração e inscrição a confirmar]` |
| Escola Bíblica Dominical | `[Classes e faixas etárias a confirmar]`; `[Material de estudo usado a confirmar]` | `[classes a confirmar]`; `[material de estudo a confirmar]` |
| Família | `[Encontros de casais, confraternizações e outras ações a confirmar]` | `[outros encontros a confirmar]` |
| Missão e Evangelismo | `[Ações de evangelismo, visitas e apoio a missionários a confirmar]` | `[ações do ministério a confirmar]` |
| Mocidade | `[Encontros próprios, retiros e eventos a confirmar]` | `[encontros e retiros a confirmar]` |
| Sobre nós | `pastor Catarino Nunes Pires [a confirmar a grafia]` | `pastor Catarino Nunes Pires [grafia a confirmar]` |

Ficaram como estavam (já mínimos): `[a confirmar]` nos quadros "Quem participa" (Ceia), "Quem dirige" (Culto de Oração), "Quem lidera" (Ensinai), nas linhas "Quando nos encontramos" dos ministérios e em "Apoiar a obra missionária"; `[a definir]` do e-mail (Contato) e de Mídia/Comunicação (Liderança); anos e sobrenomes de Sobre nós.

## Data real e eventos (fase 1)

| Onde | Protótipo | Site | Motivo |
|---|---|---|---|
| Todas as páginas com agenda | Gerado em 06/10/2026 | Mostra o dia real; os testes mandam o cabeçalho `X-Ieadc-Hoje: 2026-10-06` | Em uso normal a página inicial mostra o mês e os dias de hoje. O cabeçalho só vale no ambiente `local`. |
| Início, Eventos e calendário | "Confraternização dos jovens" (17 e 18 de outubro, horário e local a confirmar) | O evento **não** existe no site de verdade (data passada, era exemplo); `bin/testar.sh` o cria antes dos testes e o apaga no fim (`tests/fixtures/evento-prototipo.php`) | Evento só vem do cadastro (tipo Eventos). O teste de fidelidade precisa dele para igualar o protótipo. |
