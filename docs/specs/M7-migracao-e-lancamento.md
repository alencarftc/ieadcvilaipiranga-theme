# M7 · Migração e lançamento

**Onda 5 (28–30/11).** Precisa do acesso à Hostinger (Felipe, até 28/11).

## Arquivos

`bin/migrar.sh`, `docs/operacao/lancamento.md`, `docs/operacao/manutencao.md`.

## Passos

**28/11 (ensaio):**
1. Na Hostinger: confirmar PHP 8.3, criar o banco, instalar WordPress em pt-BR e ligar o modo manutenção.
2. `bin/migrar.sh`: exporta o banco local (`wp db export`), troca as URLs (`wp search-replace http://localhost:8080 https://ieadcvilaipiranga.com.br --all-tables`), envia `wp-content/themes/ieadc-vila-ipiranga`, `wp-content/mu-plugins` e `wp-content/uploads` por SFTP/rsync, importa o banco, instala o Blocksy e os plugins pelo wp-cli da Hostinger.
3. Conferir todas as páginas no domínio (ainda em manutenção) com `./bin/testar.sh visual` apontando para a produção (`SITE_URL`).

**30/11 (lançamento):**
4. Repetir a migração com o conteúdo final.
5. Ligar LiteSpeed Cache e UpdraftPlus (cópia para o Google Drive da igreja); permitir indexação; sitemap no Search Console; e-mails dos formulários para os destinatários reais (testar enviando um pedido e uma mensagem).
6. Desligar o modo manutenção. Conferir HTTPS, redirecionamento de `www`, 404 e celular.
7. `docs/operacao/manutencao.md`: como publicar artigo, evento, trocar foto, ler pedidos de oração, fazer backup e restaurar.

## Critérios de aceite

- [ ] Checklist de lançamento do plano (seção 9) completa e anexada ao PR.
- [ ] Restaurar um backup do UpdraftPlus num ambiente local funciona.
- [ ] Felipe consegue entrar no painel com 2FA.
