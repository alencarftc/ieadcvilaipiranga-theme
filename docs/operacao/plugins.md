# Plugins e configurações (M6)

Poucos plugins, todos do wordpress.org (no ambiente de nuvem o wordpress.org é bloqueado: instalar no servidor local e na Hostinger). As opções abaixo são aplicadas por `scripts/seed/90-configuracoes.php`.

| Plugin | Para quê | Configuração aplicada |
|---|---|---|
| **Yoast SEO** | Título, descrição, Open Graph, canonical e sitemap (`/sitemap_index.xml`) | Título `Página · IEADC Vila Ipiranga`; descrição pelo resumo; autor, anexos e tags fora do índice. O tema (`inc/seo.php`) entrega a descrição (o `lead` do `hero`) e a foto do topo ao Yoast. Sem Yoast, o próprio tema imprime título, descrição, canonical e Open Graph. |
| **Limit Login Attempts Reloaded** | Bloqueia tentativas de senha | 4 tentativas, bloqueio de 20 min; 3 bloqueios → 24 h |
| **Two Factor** | Segundo fator no login | Código por e-mail para administradores. Ligar com `IEADC_2FA=1` ao rodar o seed (precisa de e-mail funcionando; localmente use o Mailpit) |
| **LiteSpeed Cache** (só na Hostinger) | Cache de página e de imagens | Ver abaixo |
| **UpdraftPlus** | Backup e restauração | Arquivos semanais, banco diário, 4 cópias; destino remoto no painel |

## Já feito pelo tema (`inc/seo.php`, sem plugin)

- Dados estruturados (JSON-LD, `inc/seo-schema.php`), um só grafo por página, sem tipo repetido: `Church` (também `Organization`; nome, endereço, telefone, e-mail, redes, igreja sede, horários semanais do resumo de cultos em Configurações > IEADC Vila Ipiranga, sem horário de término inventado; "2º sábado" fica só na descrição), `WebSite`, `WebPage` (`FAQPage` em Horários e localização), `BreadcrumbList` nas páginas internas (a migalha do tema), `BlogPosting` nos artigos e `Event` para cada evento cadastrado. Com o Yoast ativo o grafo é o dele (`wpseo_schema_graph`: a Organização e a Pessoa dele saem, a igreja entra com o mesmo `@id` `/#organization`); sem o Yoast o tema monta o mesmo grafo. Nunca há `AggregateRating` nem `Review` (não há avaliação real).
- Título até 60 caracteres, descrição de 70 a 160 (campo "Resumo" da página; sem ele, o texto de abertura), `og:`/`twitter:` completos com a foto do topo (ou a da página inicial), `theme-color`, `rel=sitemap`, `max-image-preview:large` e `/llms.txt` (resumo curto e factual, gerado dos dados de Configurações). Ver `docs/operacao/seo-e-google.md`.
- `<link rel="preload" as="image">` só da foto do `hero` de cada página; emojis, `wp_generator`, RSD e `wlwmanifest` removidos.
- XML-RPC desligado; `X-Pingback` removido; cabeçalhos `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`.
- Lista de usuários escondida da API REST e do sitemap para visitantes; busca e anexos com `noindex`.
- `DISALLOW_FILE_EDIT` já vem do `docker-compose.yml` (repetir no `wp-config.php` da Hostinger).

## O que é automático e o que o administrador faz no painel

O código que liga os plugins ao site está em `wp-content/mu-plugins/ieadc-core/integracoes.php` (cache, eventos), em `wp-content/themes/ieadc-vila-ipiranga/inc/seo.php` (Yoast) e em `scripts/seed/90-configuracoes.php` (opções). Os plugins são instalados por `bin/setup.sh` e `bin/setup.ps1` (local) e por `bin/migrar-remoto.sh` (servidor); sem o plugin, o código simplesmente não faz nada.

| Plugin | Automático (já vem pronto) | O administrador faz no painel |
|---|---|---|
| **LiteSpeed Cache** | Páginas com formulário (`/pedidos-de-oracao/`, `/contato/`) **nunca** vão para o cache: o nonce do formulário vence em poucas horas e o envio falharia. Páginas com agenda (inicial, eventos, cultos com "próximas datas") expiram à meia-noite de São Paulo, para mostrar sempre o mês e os dias de hoje. Cadastrar, editar, despublicar ou apagar um evento limpa o cache na hora. O cabeçalho `X-Ieadc-Cache` da resposta mostra a decisão (`nao-cachear (formulario ieadc)`, `expira-meia-noite (…s)`; página comum não tem o cabeçalho). | Ativar o plugin (`Plugins`) e ligar o cache: *LiteSpeed Cache > Cache > Habilitar cache = Ligado*. Deixar minificação de CSS/JS **desligada** até medir. Se algo parecer velho depois de uma edição: *LiteSpeed Cache > Gerenciar > Limpar tudo*. |
| **Yoast SEO** | Título `Página · IEADC Vila Ipiranga` (a inicial usa o nome e a frase do topo), descrição (resumo, senão o texto de abertura da página), `og:` e `twitter:` com o mesmo título e a mesma descrição, imagem de compartilhamento (foto de destaque ou foto do topo; uma escolhida no próprio Yoast vale mais), canonical, sitemap em `/sitemap_index.xml`. **Sem tag duplicada**: com o Yoast ativo o tema só entrega os dados; sem o Yoast, o tema imprime uma de cada (e tira o canonical do WordPress). | Nada obrigatório. Pular o assistente de configuração do Yoast (as opções já vêm do seed). Para trocar a descrição ou a imagem de uma página: caixa do Yoast no fim da edição da página. Os dados estruturados já vêm do tema (ver acima); Search Console e Perfil da Empresa: `docs/operacao/seo-e-google.md`. O seed desliga o recurso `llms.txt` do Yoast (ele grava um arquivo físico; o do tema vem do WordPress). |
| **UpdraftPlus** | Agendamento gravado pelo seed: **arquivos toda semana, banco todo dia, 4 cópias de cada**, sem armazenamento remoto (só preenche o que ainda está em branco ou em "manual"; não desfaz o que o administrador escolheu). | Escolher o destino das cópias: *Configurações > UpdraftPlus Backups > Configurações > Escolha o armazenamento remoto* (Google Drive da igreja, entrando com a conta Google). Clicar em *Fazer backup agora* uma vez e conferir que o arquivo chegou ao Drive. Sem destino remoto, as cópias ficam só no servidor, o que **não** protege de problema na hospedagem. |
| **Limit Login Attempts Reloaded** | 4 tentativas erradas bloqueiam o endereço por 20 minutos; depois de 3 bloqueios, por 24 horas. | Conferir em *Configurações > Limit Login Attempts*. Opcional: pôr o e-mail da Comunicação para receber avisos de bloqueio. Não é preciso criar conta no serviço de nuvem do plugin. |
| **Two Factor** | O plugin fica ativo. Localmente, `IEADC_2FA=1` ao rodar o seed liga o código por e-mail para os administradores (precisa de e-mail funcionando; o Mailpit serve). | **Cada administrador** liga o seu: *Usuários > Perfil > Opções de dois fatores > E-mail* (marcar e definir como principal) e testar sair e entrar de novo. Só depois que os e-mails do servidor chegarem de verdade (teste com *Esqueci a senha*). Guarde os códigos de recuperação. |

## LiteSpeed Cache (Hostinger)

Cache de página ligado; minificar CSS/JS **desligado** até medir (o protótipo é um CSS só e o teste visual precisa passar); WebP já é o formato das fotos. Depois de ligar, rodar Lighthouse no celular. Páginas com formulário e agenda já são tratadas pelo código (tabela acima), então **não é preciso** cadastrar exceções à mão em *Cache > Excluir*. Um envio de formulário (POST) o plugin já ignora.

## Usuários

Contas e papéis seguem os Perfis de acesso da subaba Website. Administrador só para quem mantém o site; os demais como Editor.

## Pendente de medir (precisa do site no ar)

- Lighthouse celular nas 4 páginas do P1 (metas na spec M6) e `axe` (`tests/visual/a11y.spec.js`, ainda não existe).
- Coverage do Chrome para decidir o que do Blocksy/core pode sair do front (`global-styles`, `wp-block-library-theme`): não foi desligado porque pode mexer em layout dos blocos.
- As fotos do `content/media` já têm no máximo 1600 px, então não há conversão de tamanho a fazer.
