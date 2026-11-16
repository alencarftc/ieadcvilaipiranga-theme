# Lançamento e migração

Passo a passo para levar o site local para a Hostinger. O servidor da nuvem não alcança o SSH da Hostinger; tudo roda no seu computador, com o `bin\migrar.ps1`.

## Dois ambientes

| Ambiente | Para quê | Estado do site depois do script |
|---|---|---|
| **Homologação** (o padrão) | Hospedagem alternativa, em um endereço temporário da Hostinger. O pastor e a equipe validam o site aqui. | Aberto para quem tem o link e **fora do Google** (`blog_public=0`). Com `-Fechado`, só para quem tem login. |
| **Produção** (`-Ambiente producao`) | O domínio principal, `ieadcvilaipiranga.com.br`. | Ensaio: **fechado** e fora do Google. Com `-Lancar`: **no ar** e liberado para o Google. |

A homologação vem primeiro: o script só envia para a produção com `-Ambiente producao`, pede para digitar `PRODUCAO` e, no `-Lancar`, também `SIM`. O `-Lancar` só é aceito na produção.

## Antes (uma vez por ambiente)

1. No hPanel da hospedagem, instale o WordPress no endereço do ambiente (Sites > WordPress), em português, com o prefixo de tabelas `wp_`. Anote o e-mail e a senha do administrador. O script falha com uma mensagem clara se não achar o WordPress na pasta.
2. Em Avançado > SSH, deixe o acesso SSH habilitado (servidor, porta e usuário aparecem ali). A pasta do site é, por padrão, `domains/<endereço do site>/public_html`.
3. No `.env` do computador (**não vai para o git**: o repositório é público, então servidor, porta, usuário e endereços de teste ficam só ali; nunca coloque a senha):

   ```
   # Homologação
   HOMOLOG_SERVIDOR=...
   HOMOLOG_PORTA=...
   HOMOLOG_USUARIO=...
   HOMOLOG_URL=https://...        # o endereço temporário da hospedagem de homologação
   # Produção (só quando for usar)
   HOSTINGER_SERVIDOR=...
   HOSTINGER_PORTA=...
   HOSTINGER_USUARIO=...
   ```

   Se a pasta do site não for a padrão, acrescente `HOMOLOG_DESTINO=` ou `HOSTINGER_DESTINO=`; se o endereço da produção não for `https://ieadcvilaipiranga.com.br`, `PRODUCAO_URL=`.
4. No computador: Docker Desktop aberto, site local funcionando (`bin\setup.ps1` já rodado) e o Cliente OpenSSH do Windows (já vem no Windows 10/11).
5. Confira o destino sem enviar nada: `powershell -ExecutionPolicy Bypass -File bin\migrar.ps1 -Conferir` mostra o ambiente, o servidor, a pasta e o endereço final, e testa se a porta do SSH responde.

## Homologação (o ambiente de validação)

```
powershell -ExecutionPolicy Bypass -File bin\migrar.ps1
```

Pede a senha do SSH duas vezes (envio e execução); ela só é digitada no seu terminal. Para a equipe validar: mande o endereço da homologação (`HOMOLOG_URL`). O painel é o `/wp-login.php` desse endereço, com o administrador criado pelo hPanel. Para deixar o site só com login (por exemplo, enquanto falta conteúdo), rode com `-Fechado`: visitantes veem "em preparação" e quem tem login vê tudo.

A cada nova versão do site, rode de novo: o script **substitui** o conteúdo da homologação pelo do computador (e guarda o banco anterior no servidor).

## Produção

Ensaio (fechada e fora do Google; entre em `https://ieadcvilaipiranga.com.br/wp-login.php` com o administrador do hPanel para conferir):

```
powershell -ExecutionPolicy Bypass -File bin\migrar.ps1 -Ambiente producao
```

Lançamento (só depois do "pode lançar"):

```
powershell -ExecutionPolicy Bypass -File bin\migrar.ps1 -Ambiente producao -Lancar
```

Abre o site para todos (desliga `ieadc_site_fechado`) e libera para o Google. Para trocar o e-mail do administrador do site, acrescente `-EmailAdmin email@exemplo.com`.

O fechamento é a opção `ieadc_site_fechado` (mu-plugin `ieadc-core`), que não expira. O modo manutenção do WordPress (`.maintenance`) expira sozinho em 10 minutos e bloqueia também o login, por isso o script só o usa durante a cópia.

## Ensaio sem hospedagem (qualquer dia, quantas vezes quiser)

```
powershell -ExecutionPolicy Bypass -File bin\ensaio-local.ps1             # homologação
powershell -ExecutionPolicy Bypass -File bin\ensaio-local.ps1 -Producao   # ensaio da produção
powershell -ExecutionPolicy Bypass -File bin\ensaio-local.ps1 -Lancar     # lançamento da produção
powershell -ExecutionPolicy Bypass -File bin\ensaio-local.ps1 -Parar      # apaga o servidor de mentira
```

Sobe uma "Hostinger de mentira" no Docker (`tests\migracao`: SSH, Apache, WordPress e MariaDB, com o mesmo caminho `domains/.../public_html`) e roda o `bin\migrar.ps1` de verdade contra ela, com uma chave de ensaio (sem senha, em `content\backup\ensaio-hospedagem`, fora do git). Só fala com esse servidor de mentira. O site migrado abre em `http://localhost:8081` (painel: `admhost` / `Ensaio-Hospedagem-1`) e o script imprime uma conferência: endereço, `blog_public`, site fechado, regras do `.htaccess` e o status HTTP de um visitante. Para testar a fidelidade do site migrado: com o site aberto (homologação ou lançamento), defina a data fixa dos testes no servidor de mentira (`wp config set IEADC_HOJE 2026-10-06`) e rode `WP_URL=http://localhost:8081 ./bin/testar.sh markup` e `visual`.

## O que o script faz e não faz

- Substitui o conteúdo (páginas, artigos, eventos, configurações, fotos, tema e mu-plugin) pelo do computador. **Não leva os usuários locais**: a conta de administrador da Hostinger continua a mesma.
- Remove pedidos de oração de teste e troca os endereços locais pelo endereço final: o que o banco guarda (`http://localhost:8080`) e o do `.env` (`SITE_URL`, por exemplo o IP da máquina para abrir pelo celular). Depois força `home` e `siteurl` para o endereço final.
- Grava as regras dos links limpos no `.htaccess` (o `wp rewrite flush --hard` do wp-cli não faz isso: sem as regras toda página que não é a inicial dá 404).
- A data fixa `IEADC_HOJE` do `.env` local não vai para o servidor: lá a agenda usa a data real.
- Guarda o banco anterior em `~/backup-antes-da-migracao-*.sql` no servidor (para voltar atrás: `wp db import` desse arquivo).
- Instala Blocksy e os plugins (SEO, 2FA, limite de login, LiteSpeed Cache, UpdraftPlus). Configure 2FA, UpdraftPlus (cópia para o Google Drive da igreja) e os e-mails dos formulários no painel depois.
- Na homologação os formulários funcionam de verdade: o que a equipe enviar ao testar chega ao e-mail configurado (hoje o do administrador do WordPress) e pedidos de oração de teste ficam gravados lá. Na próxima migração os pedidos de oração são apagados.
- A cada execução uma pasta temporária (cerca de 35 MB) fica em `content\backup\migracao` (fora do git); apague as antigas quando quiser.
