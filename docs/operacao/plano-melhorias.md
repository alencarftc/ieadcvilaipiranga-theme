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

- [ ] **Fase 1** (`feat/site-p1`): data real na página inicial (testes fixam a data por cabeçalho), eventos vindos do módulo de eventos, integração dos plugins (cache x formulários, Yoast sem duplicar, UpdraftPlus), textos amarelos resolvidos, modelo de artigo novo, manual `manutencao.md`, `pendencias-para-o-felipe.md`.
- [ ] **Fase 2** (`feat/site-seo`): SEO técnico e dados estruturados (Church, WebSite, BreadcrumbList, Event, Article), Open Graph e Twitter, canonical, sitemap, robots, informações para Google e Gemini, seção com botão para avaliar a igreja no Google (campo `google_avaliacao` em Configurações; sem ele, link para a igreja no Google Maps).
- [ ] **Fase 3** (`feat/site-a11y-perf`): axe (WCAG 2.1 AA) como teste do projeto e Lighthouse 100 (desempenho, acessibilidade, boas práticas e SEO, celular e computador).
- [ ] **Fase 4** (`feat/site-melhorias`): melhorias de desempenho, UX, rastreamento (GA4 com consentimento, Search Console, eventos de clique), segurança (cabeçalhos, CSP), marketing (compartilhamento, WhatsApp, calendário .ics) e o que mais couber.

## Registro dos agentes

(Cada agente acrescenta aqui, em poucas linhas, o que fez, o que deixou pendente e qualquer armadilha nova.)
