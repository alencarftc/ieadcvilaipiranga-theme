# Medição de acessos (Google Tag Manager, Analytics 4) e aviso de cookies

Escrito em 16/11/2026 (fase 4). Para quem cuida do site e do Google, em linguagem simples. O que o site faz, o que é preciso configurar no Tag Manager e como conferir. **Nada aqui vale enquanto os campos estiverem vazios**: sem ID, o site não mede nada, não mostra aviso de cookies e fica igual ao protótipo.

## 1. O que o site faz

Em *Configurações > IEADC Vila Ipiranga* há campos de medição, todos **vazios por padrão** (cada um é validado: formato errado vira vazio):

| Campo | Formato | O que liga |
|---|---|---|
| ID do Google Tag Manager (`gtm_id`) | `GTM-XXXXXXX` | O Tag Manager (o caminho recomendado). |
| ID de medição do Google Analytics 4 (`ga4_id`) | `G-XXXXXXXXXX` | O Analytics direto, sem Tag Manager. **Se os dois estiverem preenchidos, vale só o Tag Manager** (senão cada visita seria contada duas vezes): configure o Analytics dentro do GTM. |
| ID do Microsoft Clarity (`clarity_id`) | 6 a 12 letras minúsculas e números | Mapa de calor e gravações anônimas. Carrega só depois do "Aceitar" (ver a seção 5). |
| ID do Meta Pixel (`meta_pixel_id`) | só dígitos | Medição de anúncios no Facebook e no Instagram (opcional; muda a política de privacidade). |
| Código do Google Search Console (`search_console`) | só o código, `google-site-verification=CODIGO` ou a meta tag inteira | Imprime `<meta name="google-site-verification">` só com o código (depois do `=`). **Só é preciso se a verificação for pelo método "Tag HTML"**: pelo registro DNS (TXT) o campo fica vazio. Não usa cookie nem aviso. |
| Código do Bing Webmaster Tools (`bing_verificacao`) | 32 letras e números | Opcional. O Bing Webmaster Tools pode **importar o site do Search Console**, então normalmente não há nada a fazer aqui. Se verificar por "Tag HTML", imprime `<meta name="msvalidate.01">`. |

**Com qualquer um dos quatro primeiros preenchido** o site passa a:

1. Mostrar um **aviso de cookies** (barra no pé da tela, com "Aceitar" e "Recusar" iguais em tamanho e cor, texto em português e link para a política de privacidade). O foco do teclado vai para o aviso quando ele aparece e o Tab sai dele sem prender. Quem escolhe não vê o aviso de novo (a escolha fica no navegador por 180 dias) e pode **mudar de ideia** pelo botão "Mudar minha escolha de cookies" da política de privacidade.
2. Definir o **Consent Mode v2** como "negado" antes de qualquer coisa (`ad_storage`, `analytics_storage`, `ad_user_data` e `ad_personalization`).
3. **Só depois do "Aceitar"**: liberar apenas `analytics_storage` (os de anúncio seguem negados: o site não faz publicidade) e carregar o `gtm.js` (ou o `gtag.js`, o Clarity e o Pixel, conforme os IDs). **"Recusar" não carrega nada.** Quem aceita e depois recusa para de enviar eventos e perde os cookies de medição.
4. Acrescentar à **política de privacidade** o parágrafo "Cookies de medição de acessos" (só existe com ID; sem ID a política fica como estava) e liberar na **política de conteúdo (CSP)** só os endereços necessários (`googletagmanager.com`, `google-analytics.com`, `analytics.google.com`; `clarity.ms` e `connect.facebook.net` só se esses IDs existirem).

**Não há o `<noscript>` do GTM** (o quadro invisível que o Google sugere no começo do `<body>`): o aviso de cookies depende de JavaScript, então quem não tem JavaScript nunca aceita e nada deve carregar. Sem o aviso aceito, nada do Google é carregado, de propósito.

**Usuários logados também são medidos** (o fechamento do site libera tudo para quem tem login e é assim que a equipe valida a medição antes de abrir). Veja a seção 6 para separar as visitas de teste.

## 2. Passo a passo no Google Tag Manager

Você precisa do container (`GTM-XXXXXXX`) e de uma propriedade do Google Analytics 4 com o **ID de medição** (`G-XXXXXXXXXX`: Analytics > Administrador > Fluxos de dados > o fluxo da web).

1. **Colocar o ID no site.** Na produção, o ID entra pelo deploy: no GitHub, em *Settings > Secrets and variables > Actions > Variables*, crie a variável de repositório `GTM_ID` com o `GTM-XXXXXXX` (ele é público, aparece no código da página). Na próxima publicação o `bin/implantar.sh` grava o campo `gtm_id` no servidor. Também dá para digitar no campo *ID do Google Tag Manager*, em *Configurações > IEADC Vila Ipiranga*. A homologação nunca recebe o ID (visitas de teste da equipe sujariam as estatísticas).
2. **Criar a tag do Google Analytics.** No Tag Manager: *Tags > Nova > Configuração (Tag do Google)* com o **ID da tag** `G-XXXXXXXXXX`. Em *Acionamento*, escolha **Inicialização - All Pages** (Initialization). Em *Configurações avançadas > Configurações de consentimento*, marque **"Exigir consentimento adicional para o disparo da tag"** e inclua `analytics_storage`. (Assim a tag só envia com a permissão dada no aviso.)
3. **Criar as variáveis dos eventos.** *Variáveis > Variáveis definidas pelo usuário > Nova > Variável da camada de dados*, uma para cada parâmetro: `local`, `tipo`, `destino` e `rede` (o nome da variável da camada de dados é o próprio nome do parâmetro).
4. **Criar um acionador e uma tag para cada evento.** Para cada linha da tabela da seção 3:
   - *Acionadores > Novo > Evento personalizado*, nome do evento igual ao da tabela (por exemplo `ieadc_clique_telefone`), "Este acionador é disparado em: Todos os eventos personalizados";
   - *Tags > Nova > Evento do Google Analytics (Evento do GA4)*, a **Tag de configuração** criada no passo 2, **Nome do evento** igual ao da tabela e, em *Parâmetros do evento*, o nome e a variável dos parâmetros que o evento tem (por exemplo `local` = `{{local}}`), com o acionador criado.
5. **Testar antes de publicar.** Clique em **Visualizar** (modo Preview, Tag Assistant): abra o site, aceite o aviso e veja a tag do Analytics disparar na inicialização e os eventos aparecerem ao clicar. No Analytics, *Administrador > DebugView* mostra os eventos em tempo real. O modo de visualização do GTM e o DebugView **não poluem os relatórios** do Analytics.
6. **Publicar** o container (*Enviar*), dando um nome à versão.
7. No Analytics, marque como **eventos-chave** os que importam (`ieadc_formulario_sucesso`, `ieadc_clique_telefone`, `ieadc_clique_whatsapp`).

Sem Tag Manager (só `ga4_id`), o site já envia a visualização de página e os mesmos eventos pelo `gtag('event', ...)`; nada a configurar além do ID.

## 3. Eventos que o site envia (nomes estáveis)

Todos entram no `dataLayer` como `{event: 'ieadc_...', parâmetros}` (ou `gtag('event', ...)` no Analytics direto), **só depois do "Aceitar"** e **nunca com dado pessoal**: só o tipo de ação. Nenhum número, e-mail, nome ou texto digitado vai junto.

| Evento | Quando | Parâmetros |
|---|---|---|
| `ieadc_clique_telefone` | clique em um link de telefone (`tel:`) | `local`: `cabecalho`, `rodape` ou `conteudo` |
| `ieadc_clique_whatsapp` | clique no WhatsApp (botão flutuante ou link `wa.me`) | `local`: `flutuante`, `rodape`, `cabecalho` ou `conteudo` |
| `ieadc_clique_como_chegar` | clique em "Como chegar", Google Maps ou Waze | `destino`: `google_maps`, `waze` ou `pagina` |
| `ieadc_avaliar_google` | clique em "Avaliar no Google" | nenhum |
| `ieadc_formulario_enviado` | envio do formulário (a pessoa clicou em enviar) | `tipo`: `pedido` (pedido de oração) ou `contato` |
| `ieadc_formulario_sucesso` | a página voltou com a confirmação de envio | `tipo`: `pedido` ou `contato` |
| `ieadc_clique_evento` | clique no cartão de um evento | nenhum |
| `ieadc_clique_artigo` | clique no cartão de um artigo | nenhum |
| `ieadc_compartilhar` | compartilhar um artigo | `rede`: `whatsapp`, `facebook` ou `copiar_link` |
| `ieadc_adicionar_calendario` | "Adicionar ao calendário" de um evento | `tipo`: `ics` ou `google_agenda` |

Observação: `ieadc_formulario_enviado` pode se perder quando a página troca logo depois do clique; `ieadc_formulario_sucesso` é o número confiável de envios.

## 4. O que muda na política de privacidade e na CSP

- **Política de privacidade:** com algum serviço ligado ganha o parágrafo "Cookies de medição de acessos" (quais serviços, que só começam depois do "Aceitar", que o site informa só o tipo da ação e o botão para mudar a escolha). Com o Meta Pixel ligado sai a frase "O site não usa cookies de publicidade". Sem ID nada muda.
- **CSP (`ieadc-core/medicao.php`, filtro `ieadc_csp_diretivas`):** os endereços do Google só entram na política quando há `gtm_id` ou `ga4_id`. **Se você criar outras tags dentro do Tag Manager** (Google Ads, Hotjar, pixel de outra rede), a CSP precisa liberar os endereços delas, senão o navegador bloqueia (hoje a CSP está em modo só relatório: o console avisa, mas nada quebra; ver `docs/operacao/plugins.md`).

## 5. Microsoft Clarity e outros serviços

- **Microsoft Clarity (o Felipe vai usar).** Coloque o ID do projeto (6 a 12 letras minúsculas e números; aparece nas configurações do projeto no painel do Clarity) no campo *ID do Microsoft Clarity*, em *Configurações > IEADC Vila Ipiranga*, ou, na produção, na variável do repositório `CLARITY_ID` no GitHub (*Settings > Secrets and variables > Actions > Variables*): o `bin/implantar.sh` grava o campo `clarity_id` no servidor, como faz com o `GTM_ID` (só na produção; a homologação recusa). Nunca grave o ID no código, no seed nem em teste.
  - Só carrega **depois do "Aceitar"** do mesmo aviso de cookies, pelo carregador oficial (`https://www.clarity.ms/tag/<id>`), e **só se houver ID**. Logo depois de carregar, o site envia a API de consentimento do Clarity (`clarity('consentv2', {analytics_Storage: 'granted', ad_Storage: 'denied'})`); quem recusar depois de aceitar tem os cookies do Clarity apagados (`clarity('consent', false)`).
  - A CSP libera `clarity.ms` (`www`, `scripts` e `c.clarity.ms`) e `c.bing.com` **somente quando há `clarity_id`**.
  - **Alternativa:** o Clarity também pode entrar como uma **tag dentro do Tag Manager** (modelo de tag "Microsoft Clarity" da galeria, ou HTML personalizado), disparada só com `analytics_storage` concedido: assim herda o consentimento do aviso. **Use um ou outro, não os dois** (senão a visita é gravada duas vezes): com a tag no GTM, deixe o campo `clarity_id` vazio.
  - No painel do Clarity, use o modo de mascaramento **estrito** (esconde o texto digitado e o conteúdo dos campos) e evite gravar as páginas de formulário. O aviso e a política passam a citar o Clarity.
  - **As visitas de teste entram nas estatísticas e nas gravações** (a equipe logada também é medida); no Clarity, use *Filtros > ... > Excluir IP* ou o filtro de IP do projeto para a rede da igreja (nomes de tela aproximados).
- **Meta Pixel:** só use com um motivo claro (anúncios). Ele pode ligar a visita ao perfil da pessoa no Facebook/Instagram; por isso a política muda. Os sinais de anúncio do Consent Mode seguem negados.
- **Search Console:** o Felipe verifica por **registro DNS (TXT)**; nesse caso o campo do site fica vazio. O campo aceita o texto `google-site-verification=CODIGO` (como o Google mostra no TXT) ou só o código e imprime a meta tag com o código depois do `=`, caso um dia a verificação passe a ser por "Tag HTML". Depois da verificação, enviar o `sitemap_index.xml`.
- **Bing Webmaster Tools:** importa do Search Console; nada a fazer. O campo existe só para quem preferir verificar por "Tag HTML".

## 6. Visitas de teste e conferência

- **As visitas de teste entram nas estatísticas** (a equipe logada é medida como qualquer pessoa). Para separar: no Analytics, *Administrador > Fluxos de dados > Configurar definições de tag > Definir tráfego interno*, cadastre o endereço de rede da igreja e, em *Administrador > Filtros de dados*, ative o filtro **Tráfego interno** (primeiro em "Teste", depois "Ativo"). Quem acessa de fora (4G, casa) não é filtrado por esse método: nesse caso, use as visitas de teste só com o **modo de visualização do GTM** e o **DebugView**, que não vão para os relatórios.
- **Conferir no navegador:** aceite o aviso e, no console (F12), digite `dataLayer`: deve mostrar `consent default` (tudo `denied`), `consent update` (`analytics_storage: granted`) e, depois de clicar em um telefone, `{event: 'ieadc_clique_telefone', local: ...}`. Na aba Rede aparece `gtm.js?id=GTM-...`. Recuse e recarregue: nenhuma requisição ao Google.
- **Testes automáticos:** `./bin/testar.sh consentimento` (usa o ID de teste `GTM-TESTE0000`, que só existe durante o teste, e intercepta as requisições ao Google) e `./bin/testar.sh php` (saída com e sem ID, validação dos formatos, CSP).

## 7. Resumo dos arquivos

`wp-content/mu-plugins/ieadc-core/medicao.php` (IDs, validação, verificação, CSP), `wp-content/themes/ieadc-vila-ipiranga/inc/medicao.php` (aviso, JSON de configuração, política), `assets/js/medicao.js` (consentimento, carga e eventos), `bin/implantar.sh` (variáveis `GTM_ID` e `CLARITY_ID`).
