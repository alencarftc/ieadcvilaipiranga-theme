# Pendências para o Felipe

Atualizado em 16/11/2026, no fim da fase 5B. Tudo o que o Felipe respondeu nesse dia já está aplicado no site e saiu desta lista (conteúdo, nomes e cargos, e-mail público, avaliação no Google, medição, Search Console, backup e 2FA). O site não tem mais texto em amarelo nem aviso de revisão: o que o Felipe não soube responder ficou como texto geral e virou pergunta em [`perguntas-ao-pastor-robson.md`](perguntas-ao-pastor-robson.md).

Aqui ficam só os passos que dependem dele.

## Antes de abrir ao público

| # | O que fazer | Como | Destrava |
|---|---|---|---|
| 1 | **Encaminhar o Gmail da igreja** (`ieadcvilaipiranga@gmail.com`) para o Pr. Robson | No Gmail da igreja: *Configurações > Encaminhamento e POP/IMAP > Adicionar um endereço de encaminhamento*, confirmar pelo e-mail que o pastor recebe e criar o filtro "encaminhar tudo" | O e-mail público do site (rodapé, Contato, política de privacidade e dados do Google) chega a quem responde |
| 2 | **Registros DNS** do domínio | Quem registra o DNS e cuida da hospedagem é o Felipe: apontar o domínio para a hospedagem no dia do lançamento e criar o registro **TXT** do Search Console (o código vem do próprio Search Console; depois, enviar o `sitemap_index.xml`). O Bing importa o site do Search Console | Site no endereço oficial e indexação pelo Google |
| 3 | **Fotos dos líderes** | Receber de cada líder (com autorização) uma foto JPG ou PNG, rosto visível, de 800 px de largura ou mais, e trocar nas páginas (*Liderança*: pastoreio e departamentos). Enquanto isso, os cartões mostram iniciais e ícone, com o aviso "As fotos dos líderes serão publicadas em breve." | Liderança com fotos |
| 4 | **E-mails dos formulários** | Pedir aos responsáveis um e-mail para os **pedidos de oração** e outro para o **Fale conosco** e gravar em *Configurações > IEADC Vila Ipiranga*. Sem isso os formulários enviam para o e-mail do administrador do site | Mensagens e pedidos chegando a quem responde |
| 5 | **UpdraftPlus no Google Drive da igreja** | No painel: *Configurações > UpdraftPlus Backups > Configurações > Google Drive*, autorizar entrando **ele mesmo** com a conta da igreja (a senha não passa por ninguém) e rodar um backup de teste. O agendamento (semanal e diário, 4 cópias) já vem gravado | Backup fora do servidor |
| 6 | **Ligar o 2FA (duas etapas)** do Felipe e do pastor | Cada um liga o seu em *Usuários > Perfil > Opções de dois fatores > E-mail* e testa sair e entrar de novo (passo a passo em `plugins.md`). Só depois que os e-mails do servidor chegarem de verdade (teste com *Esqueci a senha*) | Login seguro antes de abrir |
| 7 | **Relação das matérias do Ensinai** | O Felipe se ofereceu a passar a lista das matérias e dos livros do curso básico (2 anos, desde agosto/2026) para entrar na página do Ensinai | Página do Ensinai completa |
| 8 | **Abrir ao público** | Só com o "pode abrir" dele. Os interruptores *aberto ao público* e *permitir que o Google indexe* ficam em *Configurações > IEADC Vila Ipiranga > Estado do site* (ver `lancamento.md`) | Site no ar |

## Lançamento: carga do conteúdo novo

O texto das páginas, o menu, os departamentos e os eventos ficam **no banco de dados**, não no código. Por isso, só publicar o código não faz o conteúdo novo da fase 5B chegar aos ambientes:

- **Homologação:** o conteúdo novo exige uma **carga completa** na homologação (`bin\migrar.ps1`, modo completo), para o pastor e a equipe validarem as páginas já sem marcações amarelas.
- **Produção:** o conteúdo novo exige uma **carga completa** com a confirmação **`SUBSTITUIR-CONTEUDO`**, **antes de abrir ao público**, e só com o "pode lançar" do Felipe (a carga substitui o conteúdo que existir lá).
- Nada disso foi enviado a servidor nenhum: o trabalho da fase 5B está só na branch `feat/fase5-conteudo` e no WordPress local.

## O que o Felipe continua cuidando (já combinado)

- O Felipe mantém o site até haver outra pessoa designada.
- Quando houver novos eventos, cadastrá-los em *Eventos > Adicionar novo* (aparecem sozinhos na página inicial, em Eventos e no calendário). A escala do Culto de Ensino (página do culto) precisa ser atualizada a cada série; as datas que já passaram podem ser apagadas no bloco de texto.
