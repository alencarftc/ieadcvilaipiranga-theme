# Manutenção do site (para a equipe de Comunicação)

Guia do dia a dia do site da IEADC Vila Ipiranga: publicar artigo e evento, trocar uma foto, ler os pedidos de oração, mudar os dados da igreja e cuidar do backup. Não precisa saber programar. Quem mantém a parte técnica (servidor, plugins, migração) usa `docs/operacao/lancamento.md` e `docs/operacao/plugins.md`.

## Antes de tudo: quatro regras

1. **Não invente dado da igreja.** Endereço, horário, nome de pastor, telefone: só o que a liderança confirmou. Onde falta informação o site mostra `[a confirmar]` em fundo amarelo. Deixe assim até o dado chegar; depois troque o texto (o amarelo some sozinho quando os colchetes saem). O que ainda falta, página por página, está em `docs/operacao/pendencias-para-o-felipe.md`.
2. **Fotos:** coloridas, sem repetir a mesma foto na mesma página e sem fotos em que apareça o pastor João (regra do projeto; prefira as do Ev. Robson Stein). Em fotos de crianças, confirme antes a autorização dos responsáveis.
3. **Pedidos de oração são privados.** Só quem tem acesso ao painel lê, nunca compartilhe em grupos nem imprima sem necessidade (veja a seção 5).
4. **Termos:** "Ceia do Senhor" e "Culto de Ensino".

## 1. Entrar no painel

Endereço: `https://ieadcvilaipiranga.com.br/wp-login.php` (depois do lançamento). Use o seu usuário e a sua senha; cada pessoa tem a própria conta. Se o site pedir um código, ele chega no e-mail da conta (segundo fator).

| Papel | Quem | O que vê |
|---|---|---|
| Administrador | só quem mantém o site | tudo, inclusive plugins e backup |
| Editor | Comunicação | Posts (artigos), Páginas, Eventos, Mensagens, Mídia |
| Equipe de oração | quem cuida dos pedidos | só **Pedidos de oração** |

Quem cria as contas é o administrador (menu **Usuários**). Quem mantém o site: **[a confirmar: nome e contato]**.

O menu da esquerda, em resumo:

| Menu | Para quê |
|---|---|
| **Posts** | Os artigos do site |
| **Páginas** | Cada página do site (Início, Sobre nós, Ministérios, cultos...) |
| **Eventos** | Eventos avulsos (retiro, conferência, confraternização) |
| **Mensagens** | Pregações e estudos em vídeo ou áudio |
| **Departamentos** | Os departamentos que aparecem na página de Liderança |
| **Pedidos de oração** | Pedidos recebidos pelo formulário (privado) |
| **Mídia** | A biblioteca de fotos |
| **Configurações > IEADC Vila Ipiranga** | Endereço, telefone, redes sociais, cultos do rodapé, e-mails dos formulários |

## 2. Publicar um artigo

1. **Posts > Adicionar novo.** O artigo já abre com os blocos certos: *Topo do artigo*, *Corpo do artigo*, um parágrafo vazio e *Artigos (grade)* ("Continue lendo"). Não apague nem mova os blocos que têm o nome do tema. Escreva o texto no parágrafo (e em novos parágrafos e títulos logo abaixo dele).
2. **Título:** escreva no campo do título, no alto.
3. **Painel da direita** (ícone de engrenagem, aba *Post*):
   - **Categorias:** marque uma (Mensagem, Estudo, Reflexão ou Notícia). Ela aparece no selo do topo do artigo.
   - **Resumo:** uma ou duas frases. Vira o texto de abertura do artigo e a descrição que o Google mostra.
   - **Imagem destacada:** a foto de capa do artigo. Colorida e que não apareça em outro artigo.
   - **Caixa "Artigo":** *Versículo de abertura* (aparece em destaque antes do texto), *Referência* (por exemplo, `Tiago 5.16 (ARA)`). Deixe **desmarcado** o selo "Exemplo": ele existe só nos três artigos de exemplo do protótipo.
4. Clique em **Pré-visualizar** e confira como ficou no celular e no computador.
5. Clique em **Publicar**. Para publicar numa data futura, escolha a data na linha de publicação do painel da direita antes de confirmar.

Autor e data aparecem sozinhos: o autor é o nome do usuário que publicou, e a data é a da publicação (os três artigos de exemplo mostram "Comunicação IEADC Vila Ipiranga" e a data do próprio post). Se o nome da conta estiver incompleto, corrija em **Usuários > Perfil > Exibir o nome publicamente como**.

O artigo aparece na página **Artigos** e nas listas "Continue lendo" dos outros artigos.

> Se o artigo abrir com o visual padrão do tema (sem o topo azul e sem o selo da categoria), os blocos do tema não estavam no post. Toque em **+**, procure a categoria **IEADC Vila Ipiranga** e acrescente *Topo do artigo* no início, *Corpo do artigo* logo depois e *Artigos (grade)* no fim. Avise quem mantém o site.

## 3. Publicar um evento

Cultos fixos (segunda, quarta, quinta, sexta, domingo, Ceia no 2º sábado, vigília e consagração) **não** são cadastrados: a agenda do site os calcula sozinha. Cadastre só o que foge da rotina.

1. **Eventos > Adicionar novo.**
2. **Título** e, se quiser, um texto de descrição e um **Resumo**.
3. Caixa **Quando e onde** (painel da direita):
   - **Data** e, em eventos de mais de um dia, **Até**.
   - **Horário** (por exemplo, `19h30`) e **Local** (por exemplo, `No templo da igreja`). Sem local, não invente: deixe `[a confirmar]`.
   - **Categoria (cor na agenda):** Culto, Ensino, Domingo, Ceia do Senhor, Vigília e consagração ou Jovens.
   - **Link (opcional):** página do site (por exemplo, `ministerios/mocidade`) ou um endereço completo. Vazio = a página do próprio evento.
4. **Publicar.** O evento entra, sem mais nenhum passo, em **Próximos eventos** (página inicial e página **Eventos**) e no calendário do mês da data; o site limpa a cópia guardada das páginas (cache) na hora. Evento que já passou **some sozinho** dos próximos eventos (a página dele continua existindo). A página inicial mostra sempre o mês e os dias de hoje. Para tirar um evento do ar, **Eventos > Todos os eventos**, passe o mouse no evento e clique em **Lixeira**.

## 4. Trocar uma foto

**Foto dentro de uma página** (topo, boas-vindas, mosaico...):

1. **Páginas > Todas as páginas**, passe o mouse na página e clique em **Editar**.
2. Clique na seção que tem a foto. No painel da direita (engrenagem, aba *Bloco*), abra **Conteúdo**.
3. No campo da foto (por exemplo, *Foto de fundo* ou *Foto da moldura*), clique em **Trocar foto**, escolha uma da **Biblioteca de mídia** ou envie uma nova (aba *Enviar arquivos*) e confirme. Nos mosaicos, o campo é **Fotos** (*Editar fotos*): dá para acrescentar, remover e mudar a ordem.
4. **Atualizar** (botão azul do alto) para publicar a mudança.

**Foto do cartão de um ministério ou de um culto** e **foto de capa de um artigo**: é a **Imagem destacada** da própria página ou do post (painel da direita).

Cuidados com a foto nova:

- Largura de até 1600 px e o menor peso possível; fotos enormes deixam o site lento no celular.
- Em **Mídia**, abra a foto nova e preencha o **Texto alternativo** (descreva o que se vê, em uma frase). Ajuda quem usa leitor de tela e o Google.
- Confira as regras de fotos do início deste guia e se ela já não está em outro lugar da mesma página.

## 5. Ler os pedidos de oração

1. **Pedidos de oração > Pedidos recebidos.** Cada linha tem o nome e a data do pedido.
2. Clique no pedido para ler o texto. Na caixa **Contato** (à direita) aparecem se a pessoa quer contato e o telefone ou e-mail dela.
3. Quando o pedido chegar, um e-mail também avisa o endereço configurado em **Configurações > IEADC Vila Ipiranga > E-mail que recebe os pedidos de oração**. **[a confirmar: e-mail da equipe de oração]**

Cuidados:

- Os pedidos ficam **só no painel**: não aparecem no site nem no Google. Só administradores e a *Equipe de oração* os veem.
- O site **apaga sozinho cada pedido 90 dias depois do envio**.
- Para apagar antes, mande o pedido para a **Lixeira** e, na aba **Lixeira** da lista, use **Excluir permanentemente** (ou **Esvaziar a lixeira**). Só assim o pedido sai de vez: na lixeira ele ainda existe.
- Não copie pedidos para conversas, planilhas ou documentos fora do painel. Quem quiser contato é chamado só pelo telefone ou e-mail que a pessoa deixou.
- Se a pessoa pedir para apagar o pedido dela, apague na hora e de vez.

O **Fale conosco** funciona diferente: a mensagem vai só por e-mail, para o endereço de **E-mail que recebe o Fale conosco**, e o site não guarda nada. **[a confirmar: e-mail]**

## 6. Dados da igreja (rodapé e formulários)

**Configurações > IEADC Vila Ipiranga**: nome, frase do topo, endereço, telefone, e-mail público, Instagram, Facebook, YouTube, linha da Igreja Sede, tema do ano, lista de cultos do rodapé (um por linha) e os dois e-mails que recebem os formulários. A fonte da verdade é a aba *Informações gerais* da documentação da Comunicação: mudou lá, mude aqui. Clique em **Salvar alterações**.

### Estado do site e medição (administradores)

- **Estado do site** (caixa no alto da tela): dois interruptores, *Site aberto ao público* e *Permitir que o Google indexe o site*. Mostra "Hoje: fechado", "Hoje: aberto, fora do Google" ou "Hoje: no ar e indexável". Fechado nunca é indexado. Os três estados e quando usar cada um: `docs/operacao/lancamento.md`, seção "Estado do site". Ao salvar, o cache do LiteSpeed é esvaziado.
- **WhatsApp:** o botão flutuante e os links de WhatsApp usam o telefone do site (confirmado como WhatsApp). Para usar outro número, preencha *WhatsApp da igreja (com DDD)*; telefone fixo não vira WhatsApp.
- **Medição e verificação:** *ID do Google Tag Manager*, *ID de medição do Google Analytics 4*, *Código do Google Search Console*, *Código do Bing Webmaster Tools*, *ID do Microsoft Clarity* e *ID do Meta Pixel* (todos vazios por padrão; vazio = recurso desligado). Com um ID de medição o site mostra o aviso de cookies. Passo a passo e eventos: `docs/operacao/rastreamento.md`.

## 7. Backup e restauração

O site tem **duas camadas** de proteção:

1. **Backup da Hostinger**, automático, pelo hPanel. Frequência e prazo dependem do plano: **[a confirmar no hPanel]**.
2. **UpdraftPlus**, no painel do WordPress, com cópia para o **Google Drive da igreja** (conta: **[a confirmar]**).

### Configurar o UpdraftPlus (uma vez, pelo administrador)

1. **Configurações > UpdraftPlus Backups > aba Configurações** (é o nome do menu do plugin).
2. Frequência: o agendamento **já vem gravado**: **Arquivos** toda semana e **Banco de dados** todo dia, guardando 4 cópias de cada. Confira, e só mude se o administrador combinar outra coisa.
3. Armazenamento remoto: escolha **Google Drive**, salve e siga o assistente (**Autenticar com o Google**) entrando com a conta da igreja. Ao voltar ao painel, conclua a configuração.
4. Confirme que *Incluir seu banco de dados no backup* está marcado e clique em **Salvar alterações**.

### Fazer um backup agora

Antes de uma mudança grande (trocar várias páginas, atualizar plugins): **Configurações > UpdraftPlus Backups > aba Backup/restauração > Fazer backup agora**, marque banco de dados e arquivos e confirme. Quando terminar, o backup aparece em **Backups existentes**.

### Restaurar

> **Atenção:** restaurar volta o site ao estado do backup. Tudo o que foi publicado depois (artigos, eventos, pedidos de oração recebidos) se perde. Faça antes **Fazer backup agora** e, se tiver dúvida, chame quem mantém o site.

1. **Configurações > UpdraftPlus Backups > Backup/restauração > Backups existentes.**
2. Na linha do backup desejado, clique em **Restaurar**.
3. Marque o que restaurar: **Banco de dados** (páginas, artigos, eventos, configurações), **Envios** (fotos), **Temas** e **Plugins**. Para voltar o conteúdo ao dia anterior, bastam **Banco de dados** e **Envios**.
4. Confirme com **Restaurar**, espere o fim e abra o site para conferir. Se o painel sumir, o backup da Hostinger (hPanel) restaura o site inteiro.

## 8. Se algo der errado

- **Página em branco ou erro:** limpe o cache do navegador, entre no site por uma janela anônima e, se persistir, chame quem mantém o site com o endereço da página e o que você fez antes.
- **Mudei uma página e o visitante ainda vê a antiga:** o site guarda uma cópia das páginas (cache) e limpa sozinho quando você atualiza uma página ou um evento. Se mesmo assim demorar, peça ao administrador: **LiteSpeed Cache > Gerenciar > Limpar tudo**. Páginas com formulário e com agenda não ficam presas: as de formulário nunca são guardadas e as de agenda se renovam à meia-noite.
- **Errei um texto:** em **Páginas** ou **Posts**, abra a página e use **Revisões** (no painel da direita) para voltar a uma versão anterior.
- **Apaguei sem querer:** na **Lixeira** de Páginas, Posts ou Eventos, passe o mouse e use **Restaurar**. Por padrão, a lixeira se esvazia sozinha depois de 30 dias.
- **Formulário não chega:** os e-mails dos formulários dependem de **Configurações > IEADC Vila Ipiranga**; confira os dois endereços e a caixa de spam.
- **Bloqueado no login:** o site bloqueia por alguns minutos depois de 4 tentativas erradas; espere 20 minutos. O administrador pode ajudar.
