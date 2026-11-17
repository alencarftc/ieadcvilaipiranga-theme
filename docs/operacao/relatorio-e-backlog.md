# Relatório de situação e lista de melhorias (16/11/2026)

Escrito a pedido do Felipe, antes de começar a fase 3. Serve também de lista de tarefas para as fases 3 e 4 (cada agente atualiza as marcas `[ ]`/`[x]` ao terminar).

## 1. Quantas fases são e em que pé estamos

São **4 fases**, mais a trilha de infraestrutura (migração, homologação e CI/CD) que corre ao lado.

| Fase | O que é | Situação |
|---|---|---|
| **1** | Data real na página inicial, eventos vindos do módulo de eventos, integração dos plugins, textos amarelos, modelo de artigo novo, manual de manutenção | **Concluída** (PR #36, no ar na homologação) |
| **2** | SEO técnico, dados estruturados para Google e Gemini, `llms.txt`, seção "Avalie a igreja no Google" | **Concluída** (PR #37, no ar na homologação). Estrelas ajustadas na PR #38 (aguarda CI) |
| **3** | Acessibilidade (axe, WCAG 2.1 AA), Lighthouse 100, favicon, desempenho, cabeçalhos de segurança | **Concluída** (branch `feat/site-a11y-perf`, PR aberto): axe sem violações, `bin/lighthouse.sh`, favicon, desempenho do front e cabeçalhos; falta medir na homologação e passar a CSP a `ativo` |
| **4** | Rastreamento (Tag Manager e Analytics com consentimento, Search Console), marketing, UX e o que mais couber (seção 4 abaixo) | **Concluída** (branch `feat/site-melhorias`, PR aberto, não mesclado): ver `plano-melhorias.md`. Falta só o que depende do Felipe (IDs) e do servidor (medir a homologação) |
| Infra | Migração, homologação, CI/CD | Homologação **no ar** e publicada pelo mesmo `implantar.sh` do CI; CI/CD **mesclado, mas inativo** (faltam os segredos); produção **intocada** |

Estimativa honesta de conclusão do pedido inteiro: **cerca de 65%**. Duas das quatro fases estão prontas; o que resta é a maior parte do trabalho "medido" (Lighthouse 100, axe) e a camada de rastreamento e marketing, mais o que só o Felipe pode fornecer.

## 2. O que já foi entregue (com prova)

- **Migração e homologação:** o site migra de ponta a ponta por script, em três modos (homologação, ensaio da produção e lançamento), com trava para a homologação nunca apontar para o domínio principal. Provado num servidor de teste e na hospedagem real. Achou e corrigiu o `proc_open()` desativado da Hostinger.
- **CI/CD (GitHub Actions):** a homologação é publicada depois que os testes passam na `main`; a produção só por release, só com o que a homologação já recebeu **e testou com sucesso** (mesmo commit e mesmo pacote), com a trava conferida em duas revisões (40 casos de recusa simulados). Falta rodar no GitHub (precisa dos segredos).
- **Data e eventos:** a home mostra o mês e os dias de hoje; os testes fixam 06/10/2026 por cabeçalho (só no ambiente local). Eventos só do módulo `ieadc-core`; cadastrar um evento o faz aparecer e apagar o faz sumir (provado).
- **Plugins:** formulários nunca no cache do LiteSpeed (o nonce vence em página cacheada), agenda que expira à meia-noite, Yoast sem título/Open Graph duplicados, UpdraftPlus já agendado (semanal/diário, manter 4). Two Factor e Limit Login documentados.
- **Textos amarelos:** 52 levantados na fase 1; na fase 5B o Felipe respondeu e mandou tratar tudo como definitivo: **nenhum texto amarelo nem aviso de revisão resta** (teste automático). O que ele não soube responder (horários e periodicidade de Família, Missão e Mocidade, atividades das Crianças, matérias do Ensinai, horários dos eventos, escala depois de 02/12 etc.) está em `docs/operacao/perguntas-ao-pastor-robson.md`; o que só ele faz, em `docs/operacao/pendencias-para-o-felipe.md`.
- **SEO e Google/Gemini:** um só grafo JSON-LD por página (Church+Organization, WebSite, WebPage, BreadcrumbList, BlogPosting, Event, FAQPage), títulos ≤ 59 e descrições 74–157 sem repetição, Open Graph e Twitter, `llms.txt`, robots e sitemap; nunca nota ou número de avaliações inventados. Conferido no ar na homologação.
- **Seção "Avalie no Google":** início, Contato e Horários; botão leva ao link do Perfil da Empresa (campo em Configurações) ou, sem ele, à igreja no Google Maps; o campo só aceita endereços do Google.
- **Testes hoje:** php 209 verificações, markup 28, visual 84 (0,000%), interação 8, todos verdes. CI do GitHub verde nas PRs #33 a #37.
- **Lighthouse real na homologação (home):** celular 90 / 98 / 96 / 66 e computador 95 / 98 / 96 / 66 (desempenho, acessibilidade, boas práticas, SEO). O SEO 66 é só o `noindex` proposital da homologação.

## 3. O que falta para 100%

**Comigo (posso fazer sem ajuda):**
- [x] **Fase 3:** zerar as violações do axe (WCAG 2.1 AA) nas 28 páginas e 3 larguras; favicon e ícones (`/favicon.ico` dá 404 e entra no console); imagem do topo menor no celular (LCP 3,3 s), CSS que bloqueia a renderização e CSS do Blocksy sem uso; HSTS, X-Frame-Options e CSP; `bin/lighthouse.sh` com meta 100/100/100/100; revisão e merge.
- [x] **Fase 4:** itens da seção 4 marcados como [feito] (PR aberto); os "[fase 4+]" ficam para depois.
- [ ] Republicar a homologação a cada merge e medir o Lighthouse nela.
- [ ] Rodar o CI de ponta a ponta no GitHub (depende dos segredos, abaixo).

**Com o Felipe (só ele pode):**
- [ ] **Segredos do GitHub:** rodar `bin/cadastrar-segredos-github.sh` (ou autorizar que eu o faça). Sem isso o CI não publica sozinho na homologação.
- [x] **Dados reais:** respondidos em 16/11/2026 e aplicados na fase 5B (e-mail público, grafias e cargos, Ceia, Culto de Oração e de Ensino, ministérios, eventos de nov/dez).
- [ ] **Com o Pastor Robson** (`docs/operacao/perguntas-ao-pastor-robson.md`): o que o Felipe não soube responder (horários, periodicidade, atividades, matérias do Ensinai, escala depois de 02/12, ano da inauguração do templo, fotos e autorizações).
- [ ] **Com o Felipe** (`docs/operacao/pendencias-para-o-felipe.md`): encaminhar o Gmail da igreja, registros DNS, fotos dos líderes, e-mails dos formulários, UpdraftPlus no Drive e o "pode abrir".
- [x] **Google:** link de avaliação, Perfil da Empresa verificado e IDs de medição informados pelo Felipe em 16/11/2026. [ ] Falta o registro TXT do Search Console no DNS (e o envio do `sitemap_index.xml`).
- [ ] **Painel:** autorizar o UpdraftPlus no Google Drive da igreja e conferir os e-mails dos formulários (2FA do Felipe e do pastor já combinado).
- [ ] **O "pode lançar"** para a produção, que hoje já tem outro WordPress em `ieadcvilaipiranga.com.br` (não foi tocado).

## 4. Melhorias cabíveis (a lista que o Felipe pediu)

Marcas: **[feito]**, **[fase 4]**, **[Felipe]** quando depende de dado ou decisão dele.

**Desempenho**
- [feito] Imagem do topo em tamanhos por tela (no celular, a versão de 768 px, 65 KB em vez de 185 KB) e preload da certa; fotos de baixo só carregam perto da tela.
- [feito] Juntar e minificar o CSS próprio; tirar o CSS do Blocksy e do WordPress que o site não usa; CSS crítico se compensar.
- [feito] Imagens com `width`/`height`, `lazy` abaixo da dobra, `fetchpriority` só no LCP.
- [feito] Configuração recomendada do LiteSpeed (cache de página, minificar, WebP, ESI) documentada em `plugins.md` (o seed não grava: os nomes das opções mudam entre versões). [Felipe/orquestrador: ligar no painel da hospedagem].
- [feito] CLS da página inicial por troca de fonte: fontes de reserva com as medidas das reais e preload das fontes em prioridade baixa (`desempenho-e-acessibilidade.md`, seção 5).
- [fase 4+] Fontes só com os pesos usados (subconjunto); `preconnect` só se houver origem externa; verificação em CDN.

**Acessibilidade**
- [feito] axe como teste do projeto (WCAG 2.1 A e AA) e correção de todas as violações, incluindo contraste.
- [feito] Link "pular para o conteúdo", foco visível, menu e carrossel operáveis por teclado, `prefers-reduced-motion`.
- [fase 4] Mensagens de erro dos formulários anunciadas por leitor de tela (`aria-live`), alvos de toque de 44 px, texto alternativo revisado, idioma das citações bíblicas.

**Experiência do usuário**
- [feito] Botões "Como chegar" com Google Maps e Waze a partir do endereço (Horários e localização e Contato); botão flutuante de WhatsApp (campo `whatsapp` ou o telefone do site, que o Felipe confirmou ser WhatsApp). [x]
- [feito] "Adicionar ao calendário" (arquivo `/evento-<id>.ics` e Google Agenda) em cada evento cadastrado. [fase 4+] Nos cultos fixos.
- [feito] Compartilhar artigo (WhatsApp, Facebook, copiar link) e página 404 acolhedora (noindex). [fase 4+] Busca no site.
- [fase 4] Página "Primeira visita" (o que esperar, onde estacionar, horários) [Felipe: conteúdo]; site instalável (webmanifest e ícones: já feitos na fase 3).

**Rastreamento e medição**
- [feito] Google Tag Manager e Google Analytics 4 com Consent Mode v2 e aviso de cookies (LGPD) com aceitar e recusar; só carrega depois do consentimento (`rastreamento.md`). IDs configuráveis em Configurações, vazios por padrão [Felipe: `GTM_ID` e o ID de medição `G-...`].
- [feito] Eventos `ieadc_*` por `dataLayer` (telefone, WhatsApp, Como chegar, "Avaliar no Google", envio e sucesso dos formulários, evento, artigo, compartilhar, calendário), sem dado pessoal.
- [feito] Campos em Configurações para Search Console, Bing Webmaster, Microsoft Clarity e Meta Pixel (vazios, validados). [fase 4+] UTM padrão nos links das redes.

**SEO**
- [feito] Dados estruturados, meta tags, `llms.txt`, sitemap, robots, FAQ visível em Horários.
- [Felipe] Perfil da Empresa verificado (categoria "Igreja evangélica", horários iguais aos do site, fotos, link do site), Search Console e envio do sitemap.
- [feito] Descrição própria e `noindex, follow` nas categorias de artigo. [fase 4+] Redirecionar `www` e `http` para o endereço principal na hospedagem; texto alternativo e nomes de arquivo das fotos; artigos periódicos e uma página local ("igreja no Capão Raso, Curitiba") com conteúdo próprio.

**Segurança**
- [feito] HSTS, X-Frame-Options, Cross-Origin-Opener-Policy e CSP (primeiro só em relatório, sem quebrar o painel).
- [feito] Endurecimento do `.htaccess` (sem listagem de pastas; `.env`, `wp-config.php`, `readme.html`, `license.txt`, `xmlrpc.php` e arquivos `.log/.sql/.bak` bloqueados) e o interruptor de estado do site (aberto ao público e indexável) no painel. [fase 4+] Proteção extra nos formulários (Turnstile opcional), atualização automática de plugins em versões de correção, teste de restauração do backup.
- [Felipe] 2FA ligado para os administradores; backup remoto no Google Drive da igreja.

**Marketing**
- [fase 4] QR code para cartazes e folhetos apontando para a página de visita, com UTM; imagem de compartilhamento por página; kit de textos curtos para as redes.
- [Felipe] Lista de transmissão (WhatsApp) ou newsletter; depoimentos reais com autorização; postagens no Perfil da Empresa; galeria ligada ao Instagram.
- [feito] Seção "Avalie a igreja no Google" (aguarda o link real do Perfil).

**Operação**
- [feito] Guia de monitoramento gratuito com alerta por e-mail e dos ajustes da hospedagem (`monitoramento.md`) [Felipe: criar a conta do monitor]. [fase 4+] Relatório mensal simples de acessos [Felipe: GA4].

## 5. Regras que seguem valendo
Produção só com o "pode lançar"; nunca inventar dado da igreja; HML publicada a cada merge; tudo por PR revisada por um agente revisor; repositório público (nada de senha, IP, usuário SSH ou domínio de hospedagem em arquivo versionado).
