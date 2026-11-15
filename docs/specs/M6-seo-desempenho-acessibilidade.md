# M6 · SEO, desempenho, acessibilidade e segurança

**Ondas 3–4 (25–29/11).** Começa quando as páginas do P1 estiverem no `main`.

## Arquivos

`inc/seo.php`, `assets/css/ajustes.css` (seção `M6`), `scripts/seed/90-configuracoes.php`, `docs/operacao/plugins.md`.

## Entregas

1. **SEO (Yoast):** título e descrição de cada página (`Página · IEADC Vila Ipiranga`; descrição a partir do `lead` do `hero`), Open Graph com a foto do topo, dados estruturados `Church` (nome, endereço, telefone, horários de culto da agenda, redes), sitemap. Configurar pelo seed (`wp option update wpseo_titles …`), sem clicar no painel.
2. **Desempenho:** `loading="lazy"` não serve para fundos; carregar com prioridade só a foto do `hero` (`<link rel="preload" as="image">` da foto do topo de cada página) e converter as demais para tamanhos `large` (1600 px). Fontes já são locais com `preload`. Desligar no front o CSS e JS do Blocksy que não usamos (verificar com Coverage do Chrome). Meta: Lighthouse celular ≥ 85.
3. **Acessibilidade:** contraste AA em todos os textos (atenção a `.note`, `.small` e textos sobre foto), foco visível, `aria-current` no menu, pular para o conteúdo, rótulos dos formulários, `prefers-reduced-motion`. Meta: Lighthouse ≥ 95 e `axe` sem erros sérios (`tests/visual/a11y.spec.js`).
4. **Segurança:** Two Factor obrigatório para administradores, Limit Login Attempts com 4 tentativas, `DISALLOW_FILE_EDIT` (já), XML-RPC desligado, cabeçalhos (`X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`), usuários pelos Perfis de acesso da subaba Website.
5. **Plugins:** `docs/operacao/plugins.md` com o que cada um faz e as configurações aplicadas.

## Critérios de aceite

- [ ] Lighthouse (celular) nas 4 páginas do P1: desempenho ≥ 85, acessibilidade ≥ 95, boas práticas ≥ 95, SEO ≥ 95.
- [ ] `axe` sem violações "serious" ou "critical" em todas as páginas.
- [ ] Teste do Google de resultados ricos reconhece o `Church` (rodar na migração, M7).
- [ ] Nenhuma mudança visual: `./bin/testar.sh visual` continua passando.
