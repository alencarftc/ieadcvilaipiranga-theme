# Lançamento e migração

Passo a passo para colocar o site local na Hostinger. O servidor da nuvem não alcança o SSH da Hostinger; tudo roda no seu computador.

## Antes (uma vez)

1. No hPanel, instale o WordPress no domínio (Sites > WordPress), em português, com o prefixo de tabelas `wp_`. Anote o e-mail e a senha do administrador.
2. Em Avançado > SSH, deixe o acesso SSH habilitado (usuário e porta aparecem ali). Confira a pasta do site (padrão `domains/ieadcvilaipiranga.com.br/public_html`).
3. No `.env` do computador (não vai para o git), acrescente as três linhas que aparecem no hPanel em Avançado > SSH: `HOSTINGER_SERVIDOR=...`, `HOSTINGER_PORTA=...` e `HOSTINGER_USUARIO=...`.
4. No computador: Docker Desktop aberto, site local funcionando (`bin\setup.ps1` já rodado) e o Cliente OpenSSH do Windows (já vem no Windows 10/11).

## Ensaio (28/11)

```
powershell -ExecutionPolicy Bypass -File bin\migrar.ps1
```

Pede a senha do SSH duas vezes. O site vai para o domínio em **modo manutenção** e **fora do Google**; entre em `https://ieadcvilaipiranga.com.br/wp-login.php` com o administrador do hPanel para conferir as páginas.

## Lançamento (30/11)

```
powershell -ExecutionPolicy Bypass -File bin\migrar.ps1 -Lancar
```

Pede para digitar SIM. Tira da manutenção e libera para o Google. Para trocar o e-mail do administrador do site, acrescente `-EmailAdmin email@exemplo.com`.

## O que o script faz e não faz

- Substitui o conteúdo (páginas, artigos, eventos, configurações, fotos, tema e mu-plugin) pelo do computador. **Não leva os usuários locais**: a conta de administrador da Hostinger continua a mesma.
- Remove pedidos de oração de teste e troca o endereço local pelo endereço final.
- Guarda o banco anterior em `~/backup-antes-da-migracao-*.sql` no servidor (para voltar atrás: `wp db import` desse arquivo).
- Instala Blocksy e os plugins (SEO, 2FA, limite de login, LiteSpeed Cache, UpdraftPlus). Configure 2FA, UpdraftPlus (cópia para o Google Drive da igreja) e os e-mails dos formulários no painel depois.
- Na primeira vez que roda, uma pasta temporária fica em `content\backup\migracao` (fora do git).
