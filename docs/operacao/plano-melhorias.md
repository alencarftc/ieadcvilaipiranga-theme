# Plano de melhorias e passagem de bastão entre agentes

Escrito em 16/11/2026 (02h53) a pedido do Felipe: finalizar tudo o que foi pedido **sem a revisão dele**, depois propor e aplicar as melhorias cabíveis (desempenho, acessibilidade, UX, rastreamento, SEO, segurança, marketing), até as 10h do mesmo dia. Cada fase é feita por **um agente de cada vez**, que lê este arquivo, `CLAUDE.md` e `docs/operacao/continuidade.md`, faz o trabalho numa branch própria, abre o PR e **atualiza a lista abaixo** antes de terminar.

## Regras que não mudam

- Não invente dado da igreja (nome, ano, horário, telefone, e-mail, endereço, número, programação). Texto genérico que não afirma fato novo pode entrar; o que exige fato real vai para `docs/operacao/pendencias-para-o-felipe.md` (pergunta objetiva, página, formato da resposta).
- O protótipo `prototipo/` é a referência dos testes. Mudança aprovada (texto de pendência resolvida, data real) entra **também** nos HTMLs de `prototipo/` e em `docs/specs/referencias/`, e é registrada em `docs/specs/desvios-do-prototipo.md`, para o `./bin/testar.sh` seguir verde (php, markup 28, visual 84, interação 8).
- Fotos coloridas, sem repetir na página e sem o pastor João. Termos: "Ceia do Senhor", "Culto de Ensino". Pedidos de oração são privados.
- Nada de senha, `.env`, banco ou `content/backup/` no git (o repositório é público). Nada de IP, usuário SSH ou domínio de hospedagem em arquivo versionado.
- **Produção só com o "pode lançar" do Felipe.** A homologação (HML) é republicada a cada merge na `main` (pelo CI do GitHub ou, se ele falhar, por `bin\migrar.ps1` com a chave de deploy). Quem republica é o orquestrador, não o agente de fase.
- Economia: um agente de desenvolvimento por vez; ler só o necessário (grep, trechos); rodar o teste da página mexida e só no fim a bateria inteira; relatório final curto. Revisor Sonnet lê só o diff e roda `./bin/testar.sh`.

## Ambiente local (compartilhado)

- Site em `http://localhost:8080` (responde em qualquer endereço usado pelo navegador, inclusive o IP da máquina, para o celular). `.env` com `SITE_URL=http://localhost:8080`. A data real é a do dia; os testes fixam 06/10/2026 por cabeçalho (fase 1).
- Testes: `export IEADC_CHROMIUM='C:\Users\alenc\AppData\Local\ms-playwright\chromium_headless_shell-1161\chrome-win\headless_shell.exe'` e `./bin/testar.sh [php|markup|visual|interacao] [pagina]`.
- Não rode `docker compose down`, nem mexa nos contêineres `ieadc-hospedagem-teste-*`; não conecte a servidores reais; não imprima o `.env`.

## Fases e situação

- [x] **Fase 1** (`feat/site-p1`, PR aberto, **não mesclado**): data real na página inicial (testes fixam a data por cabeçalho `X-Ieadc-Hoje`), eventos vindos só do módulo de eventos (seed sem exemplo; dado de teste à parte), integração dos plugins (cache x formulários, agenda que vira à meia-noite, Yoast sem duplicar, UpdraftPlus), textos amarelos (20 resolvidos, 32 encurtados), modelo de artigo novo, manual `manutencao.md` versionado, `pendencias-para-o-felipe.md` e `desvios-do-prototipo.md`.
- [ ] **Fase 2** (`feat/site-seo`): SEO técnico e dados estruturados (Church, WebSite, BreadcrumbList, Event, Article), Open Graph e Twitter, canonical, sitemap, robots, informações para Google e Gemini, seção com botão para avaliar a igreja no Google (campo `google_avaliacao` em Configurações; sem ele, link para a igreja no Google Maps).
- [ ] **Fase 3** (`feat/site-a11y-perf`): axe (WCAG 2.1 AA) como teste do projeto e Lighthouse 100 (desempenho, acessibilidade, boas práticas e SEO, celular e computador).
- [ ] **Fase 4** (`feat/site-melhorias`): melhorias de desempenho, UX, rastreamento (GA4 com consentimento, Search Console, eventos de clique), segurança (cabeçalhos, CSP), marketing (compartilhamento, WhatsApp, calendário .ics) e o que mais couber.

## Registro dos agentes

(Cada agente acrescenta aqui, em poucas linhas, o que fez, o que deixou pendente e qualquer armadilha nova.)

### Fase 1 (`feat/site-p1`), 16/11/2026
- **Feito:** T1 data real (`ieadc_hoje()` aceita `X-Ieadc-Hoje` só no ambiente local; `.env` com `IEADC_HOJE` vazio); T2 eventos só do módulo (seed sem exemplo, `tests/fixtures/evento-prototipo.php` criado/apagado pelo `testar.sh`); T3 `ieadc-core/integracoes.php`, filtros do Yoast em `inc/seo.php`, seed do UpdraftPlus e `docs/operacao/plugins.md`; T4 textos amarelos, `docs/specs/desvios-do-prototipo.md` e `docs/operacao/pendencias-para-o-felipe.md`; T5 modelo do artigo novo; T6 manual.
- **Pendente (depende do Felipe):** as respostas de `pendencias-para-o-felipe.md` (e-mail público, anos e sobrenomes de Sobre nós, regras da Ceia, dados dos ministérios, fotos dos pastores, agenda de 2027) e o que é feito no painel (destino do backup, 2FA, e-mails dos formulários).
- **Armadilhas novas:** o site local agora mostra o dia real (a home não é mais igual ao protótipo no navegador; os testes mandam o cabeçalho); `prototipo/fonte/*.py` não foi atualizado e não deve regerar `prototipo/*.html`; o evento do protótipo só existe durante `testar.sh` (se um teste for interrompido, `wp eval-file tests/fixtures/evento-prototipo.php remover` ou o seed o apagam); `wp-content/mu-plugins/ieadc-core/integracoes.php` usa ganchos do LiteSpeed que só existem com o plugin ativo (na Hostinger).
- **Para o orquestrador:** o PR da fase 1 não foi mesclado nem publicado em servidor.
