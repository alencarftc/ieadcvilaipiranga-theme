# Pendências para o Felipe (dados que só a liderança tem)

Atualizado em 16/11/2026. São os trechos que ainda aparecem em **amarelo** no site e que dependem de um fato real. Nada foi inventado: enquanto não houver resposta, o amarelo continua (curto e neutro). Quem responde pode mandar por mensagem; a Comunicação grava no painel (*Páginas > editar a página > bloco indicado*). O que cada resposta **destrava** está na última coluna. O que já foi resolvido sem dado novo está em `docs/specs/desvios-do-prototipo.md`.

Formato geral da resposta: o texto exatamente como deve aparecer no site (frase completa, já com a pontuação). Para horário, `20h` ou `19h30`; para data, `dia/mês/ano`; para nome de pessoa, nome e sobrenome com o título (Pb., Pr., Ev., Coop., Dc.).

## Contato e liderança

| Página | Pergunta | Formato da resposta | Destrava |
|---|---|---|---|
| Contato | Qual é o e-mail público da igreja? | um endereço (`nome@dominio`) | Linha "E-mail" do Contato, dados estruturados do Google (Church) e, em *Configurações > IEADC Vila Ipiranga*, o e-mail que recebe os formulários |
| Liderança | Quem é o responsável pelo departamento de Mídia/Comunicação? | nome completo com título | Linha "Mídia/Comunicação [a definir]" da lista de departamentos |
| Liderança | Fotos do Ev. Robson Stein e do Pb. Manoel Lima (com a autorização deles) | arquivo JPG ou PNG, rosto visível, pelo menos 800 px de largura | Os cartões de Pastoreio hoje mostram só as iniciais (RS e ML) |

## Sobre nós (história)

| Página | Pergunta | Formato da resposta | Destrava |
|---|---|---|---|
| Sobre nós | Em que ano o templo atual foi inaugurado? (aparece na linha do tempo e no texto da história) | `AAAA` | Remove os dois "[ano a confirmar]" |
| Sobre nós | Grafia correta de "pastor Catarino Nunes Pires" | nome como está na ata | Remove "[grafia a confirmar]" |
| Sobre nós | Nome completo do "presbítero [...] Nunes" da lista de dirigentes | nome e sobrenome | Remove "[nome a confirmar]" |
| Sobre nós | Sobrenome do "presbítero Jorge", do "pastor Messias" (consta "da Costa", confirmar) e do "pastor Sérgio Luiz" | sobrenomes | Remove os três "[sobrenome a confirmar]" |

## Cultos

| Página | Pergunta | Formato da resposta | Destrava |
|---|---|---|---|
| Ceia do Senhor | Quem pode participar dos elementos (pão e cálice) e que orientação dar a visitantes? | 1 a 3 frases aprovadas pelo pastor | "Quem participa [a confirmar]" e "[orientações para visitantes a confirmar]" |
| Culto de Ensino | Há temas ou séries de estudo em andamento? | lista curta, ou "não há série fixa" | "[temas de estudo a confirmar]" |
| Culto de Oração | Quem dirige o culto e como é a ordem (oração, louvor, palavra)? | nome(s) ou "a liderança da igreja"; ordem em 1 frase | "Quem dirige [a confirmar]" e "[ordem do culto a confirmar]" |
| Culto do Círculo de Oração | (opcional) O coral participa toda semana? | sim, não ou "às vezes" | Só para detalhar o texto; a página já está completa sem isso |

## Ministérios

| Página | Pergunta | Formato da resposta | Destrava |
|---|---|---|---|
| Círculo de Oração | Dias e horários dos ensaios do coral; há outros encontros de oração? | dia da semana e horário; ou "não há" | "Ensaios do coral [a confirmar]" e "Outros encontros de oração [a confirmar]" |
| Crianças | Para quais idades é o ministério? Como são as classes e quais as atividades especiais? | faixa de idade (ex.: "de 3 a 11 anos") e 1 a 2 frases | "[faixa etária a confirmar]" e "[classes e atividades a confirmar]" |
| Ensinai | Quem lidera o Ensinai? | nomes com título | Os dois "Quem lidera [a confirmar]" (a orientação "procure a liderança" já está no ar) |
| Ensinai | Duração, disciplinas, forma de inscrição, certificado e data de início da próxima turma | frases curtas e a data (`dia/mês/ano`) | "[duração e inscrição a confirmar]" e "Início da próxima turma [a confirmar]" |
| Escola Bíblica Dominical | Como são as classes e faixas etárias? Qual o material de estudo? | classes (lista curta) e nome do material | "[classes a confirmar]" e "[material de estudo a confirmar]" |
| Família | Que outros encontros existem (casais, confraternizações)? Quando? | nome do encontro e periodicidade/data | "[outros encontros a confirmar]" e "Encontros de casais e confraternizações [a confirmar]" |
| Missão e Evangelismo | Que ações existem (saídas de evangelismo, visitas) e como a igreja apoia missionários? Quando? | 1 a 2 frases e periodicidade | "[ações do ministério a confirmar]", "Saídas de evangelismo [a confirmar]" e "Apoiar a obra missionária [a confirmar]" |
| Mocidade | Que encontros próprios, retiros e eventos a Mocidade faz? Quando? | nome e data/periodicidade | "[encontros e retiros a confirmar]" e "Encontros próprios da Mocidade [a confirmar]" |
| Crianças e Ensinai | Quando houver fotos, a autorização de imagem dos pais ou responsáveis está arquivada? Quais fotos podem entrar? | sim/não e as fotos (WebP ou JPG, coloridas) | Galeria dos dois ministérios (hoje só o aviso de autorização) |

## Eventos e agenda

| Página | Pergunta | Formato da resposta | Destrava |
|---|---|---|---|
| Eventos | Datas das confraternizações e eventos de cada departamento em 2027 (definidas na reunião de início de ano) | evento, `dia/mês/ano`, horário, local | Cadastrar em *Eventos > Adicionar novo*; aparecem sozinhos na página inicial, em Eventos e no calendário. O texto "Quando forem definidas, as datas aparecem no calendário acima." já está no ar |

## Revisão do pastor e painel (não são textos amarelos)

| Assunto | Pergunta | Destrava |
|---|---|---|
| Aviso "Texto-base para o pastor revisar. Trechos em amarelo aguardam confirmação." (13 páginas de cultos e ministérios) | O pastor revisou os textos? Se sim, a Comunicação apaga o campo "Aviso" do bloco de texto de cada página | Páginas sem o aviso de revisão para o lançamento |
| Backup (UpdraftPlus) | Qual conta Google (Drive da igreja) recebe as cópias? A própria pessoa entra com a conta em *Configurações > UpdraftPlus Backups*; a senha não passa por ninguém | Backup fora do servidor (o agendamento semanal/diário já está gravado) |
| E-mails dos formulários | Para quais e-mails vão os pedidos de oração e as mensagens de contato? | *Configurações > IEADC Vila Ipiranga*: os formulários hoje não têm destinatário real |
| Manual da Comunicação (`docs/operacao/manutencao.md`) | Quem mantém o site (nome e contato), e-mail da equipe de oração, conta Google do backup e frequência do backup da Hostinger (hPanel) | Trechos "[a confirmar]" do manual |
| Dois fatores (2FA) | Quem são os administradores que vão ligar o código por e-mail? | Login do painel protegido (ver `docs/operacao/plugins.md`) |

## SEO e Google (fase 2)

O passo a passo, em linguagem simples, está em `docs/operacao/seo-e-google.md`. O site já está pronto para receber estes dados; sem eles ele funciona, só não fica completo.

| Assunto | Pergunta | Formato da resposta | Destrava |
|---|---|---|---|
| Link de avaliação | Qual é o link de avaliação do Perfil da Empresa no Google da igreja? (no perfil: "Pedir avaliações") Ou o código do lugar (`ChIJ…`) | o link completo, ou o código | *Configurações > IEADC Vila Ipiranga > Link para avaliar no Google*: o botão "Avaliar no Google" passa a abrir direto a janela de avaliação (hoje abre a igreja no Google Maps) |
| Perfil da Empresa | A igreja já tem o Perfil da Empresa verificado? Qual conta Google é a proprietária? | sim/não e o e-mail da conta (a senha não passa por ninguém) | Nome, endereço, telefone e horários iguais aos do site; categoria "Igreja evangélica"; fotos |
| Search Console | Quem administra o DNS do domínio (Registro.br ou hospedagem) para colar o registro TXT de verificação? | nome de quem faz | Enviar o `sitemap_index.xml` e acompanhar a indexação |
| E-mail público | (já listado em Contato) qual é o e-mail público? | `nome@dominio` | Também atualiza os dados da igreja para o Google e o `/llms.txt` (hoje usam o e-mail do rodapé) |
| Coordenadas (opcional) | Latitude e longitude da igreja (no Google Maps: clicar com o botão direito no ponto da igreja e copiar os números) | dois números, ex.: `-25.4999` e `-49.2999` | *Configurações > IEADC Vila Ipiranga > Latitude e Longitude*: o Google localiza a igreja no mapa com mais precisão |
