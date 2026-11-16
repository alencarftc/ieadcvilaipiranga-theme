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
- `<link rel="preload" as="image">` só da foto do `hero` de cada página (no celular, a versão de 768 px; `inc/desempenho.php`); emojis, `wp_generator`, RSD e `wlwmanifest` removidos.
- Favicon, ícone do iPhone, `site.webmanifest` e `/favicon.ico` (`inc/identidade.php`; ícones em `assets/img/icones/`, gerados por `scripts/gerar-icones.py`).
- XML-RPC desligado; `X-Pingback` removido; cabeçalhos de segurança em `mu-plugins/ieadc-core/seguranca.php` (ver "Segurança" abaixo).
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

## LiteSpeed Cache (Hostinger): configuração recomendada

O seed **não** grava as opções do LiteSpeed (os nomes mudam entre versões e o plugin só roda na Hostinger): faça no painel, em *LiteSpeed Cache*, e depois rode `LH_URL_BASE=<endereço> ./bin/lighthouse.sh`.

| Onde no painel | Valor | Por quê |
|---|---|---|
| Cache > Cache > **Habilitar cache** | Ligado | A página sai pronta do servidor (resposta de ~300 ms sem cache para dezenas de ms): é o que mais ajuda o LCP no celular |
| Cache > Cache > Cache de usuários logados | Desligado | Quem edita sempre vê a versão atual |
| Cache > Navegador > **Cache do navegador** | Ligado (TTL padrão, 1 ano) | O tema também grava no `.htaccess` o cache de 1 ano para CSS, JS, fontes e imagens (`inc/desempenho.php`); os dois não conflitam |
| Cache > **ESI** | Desligado | Formulários e agenda já são tratados pelo código (tabela acima: formulário nunca vai ao cache; agenda expira à meia-noite). ESI só faria sentido para o nonce dentro de página em cache, que não existe aqui |
| Otimização de página > **Minificar CSS**, **Minificar JS**, **Combinar CSS/JS**, **CSS crítico**, **UCSS**, **CSS assíncrono** | **Desligados** | O tema já entrega o CSS próprio minificado em um arquivo só (`uploads/ieadc-cache/tema-<hash>.css`); o `prototipo.css` é congelado. Ligar reprocessa o que já está pronto e pode quebrar o teste visual. Se um dia compensar, ligue um por vez e rode `./bin/testar.sh visual` |
| Otimização de página > **Lazy load de imagens** | Desligado | As fotos são `background-image` (o plugin não as atinge) e o tema já adia as de baixo da primeira seção |
| Otimização de imagem > **WebP** | Não é preciso | Todas as fotos já são WebP e o WordPress gera os tamanhos de 300, 768, 1024 e 1536 px |
| Cache > Excluir; **Guest Mode** | Nada a cadastrar; Guest Mode desligado | Formulário e agenda já são tratados por `integracoes.php`; o Guest Mode injeta JavaScript próprio e a CSP (abaixo) o bloquearia |

Depois de ligar o cache: abra o site numa janela anônima e confira o cabeçalho `X-LiteSpeed-Cache: hit` na segunda visita; *Gerenciar > Limpar tudo* quando algo parecer velho.

## Segurança: cabeçalhos e CSP (`mu-plugins/ieadc-core/seguranca.php`)

Enviados em toda página pública: `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` (sem câmera, microfone, localização, pagamento e USB), `X-Frame-Options: SAMEORIGIN`, `Cross-Origin-Opener-Policy: same-origin-allow-popups` e, **só quando a página chega por https**, `Strict-Transport-Security: max-age=31536000; includeSubDomains` (sem `preload` por ora: só peça a pré-carga depois de meses de https estável e de conferir os subdomínios). O painel (`/wp-admin`), o login e quem está logado não recebem a CSP.

**CSP** (`ieadc_csp_politica()`): `default-src 'self'`; scripts só do próprio site (mais `'inline-speculation-rules'`, do WordPress); `style-src 'self' 'unsafe-inline'` (as fotos de fundo vão no atributo `style` de cada seção: não dá para tirar sem mudar o HTML do protótipo); imagens `'self' data: https:`; fontes do próprio site; sem quadros, sem objetos; `form-action 'self'`; `frame-ancestors 'self'` e `upgrade-insecure-requests` (https). O site não usa YouTube, mapa embutido nem outra origem: se um bloco novo precisar, acrescente a origem pelo filtro `ieadc_csp_diretivas`.

Três modos na opção `ieadc_csp_modo` (`wp option update ieadc_csp_modo <modo>`): `relatorio` (**padrão**: cabeçalho `Content-Security-Policy-Report-Only`; o navegador só registra no console o que bloquearia), `ativo` (bloqueia de verdade) e `desligado`. Localmente o modo `ativo` foi testado: zero avisos de CSP nas 28 páginas a 390 e 1440 px e os 8 testes de interação passam. **Como promover na homologação:** deixar em `relatorio`; abrir início, contato, pedidos de oração, um artigo e um evento com o console aberto (filtro "Content Security Policy"); sem avisos (o LiteSpeed Cache, o Yoast ou outro plugin podem acrescentar script próprio), `wp option update ieadc_csp_modo ativo`. Se algo aparecer, descubra o plugin e libere só aquela origem pelo filtro, ou deixe em `relatorio`.

**Fase 4 (medição e `.htaccess`).** A CSP libera `googletagmanager.com`, `google-analytics.com` e `analytics.google.com` **somente quando há `gtm_id` ou `ga4_id`** em Configurações (e `clarity.ms` / `connect.facebook.net` só com os IDs do Clarity e do Meta Pixel); sem ID ela é a mesma de antes. Tags novas dentro do Tag Manager precisam dos endereços delas liberados pelo filtro `ieadc_csp_diretivas` (`docs/operacao/rastreamento.md`). A CSP **não vale para quem está logado** (a medição também carrega para eles). O `.htaccess` ganhou, no mesmo bloco do WordPress e na mesma gravação da fase 3 (`flush_rewrite_rules(true)` no seed e nos scripts de implantação): `Options -Indexes` (sem listagem de pastas) e acesso negado a `.env`, `wp-config.php`, `readme.html`, `license.txt`, `xmlrpc.php` e arquivos `.log/.sql/.bak` (testado em `tests/php/medicao/teste-seguranca.php`). **Atenção:** o `xmlrpc.php` bloqueado desliga aplicativos e plugins que o usam (o app do WordPress no celular por XML-RPC, Jetpack); o site não usa. Na hospedagem LiteSpeed, conferir depois do próximo deploy que `/readme.html`, `/xmlrpc.php` e `/wp-config.php` respondem 403.

## Usuários

Contas e papéis seguem os Perfis de acesso da subaba Website. Administrador só para quem mantém o site; os demais como Editor.

## Pendente de medir (precisa do site no ar)

- Lighthouse na homologação e na produção (`LH_URL_BASE=<endereço> ./bin/lighthouse.sh`) e a CSP no console antes de passá-la a `ativo`: ver `docs/operacao/desempenho-e-acessibilidade.md`. O `axe` (`tests/visual/a11y.spec.js`) e o Lighthouse local já rodam.
- Do Blocksy, o front das páginas do site já deixou de carregar o CSS dinâmico, os estilos globais do WordPress, a folha do título e o JavaScript. Fica o `main.min.css` (94 KB, 18 KB comprimido; só 12% é usado nas 28 páginas, medido com o Coverage do Chrome): ele traz o reset e `.screen-reader-text`. Cortá-lo exigiria uma cópia congelada do Blocksy dentro do tema (sem acompanhar as atualizações dele): só vale se, na homologação, o Lighthouse do celular ainda ficar abaixo de 100.
- As fotos do `content/media` têm no máximo 1600 px; o WordPress gera os tamanhos de 300, 768, 1024 e 1536 px na importação (o tema usa o de 768 px no topo, no celular).
