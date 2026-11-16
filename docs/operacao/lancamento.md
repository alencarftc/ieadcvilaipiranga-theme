# Lançamento e migração

Passo a passo para levar o site local para a Hostinger. O servidor da nuvem não alcança o SSH da Hostinger; tudo roda no seu computador, com o `bin\migrar.ps1`.

## Dois ambientes

| Ambiente | Para quê | Estado do site depois do script |
|---|---|---|
| **Homologação** (o padrão) | O staging da Hostinger, `https://hml.ieadcvilaipiranga.com.br`, que fica na **subpasta `hml` da produção** (`domains/ieadcvilaipiranga.com.br/public_html/hml`, WordPress e banco próprios). O pastor e a equipe validam o site aqui. (De transição, ainda é aceito um endereço `*.hostingersite.com` de hospedagem alternativa.) | Aberto para quem tem o link e **fora do Google** (`blog_public=0`). Com `-Fechado`, só para quem tem login. |
| **Produção** (`-Ambiente producao`) | O domínio principal, `ieadcvilaipiranga.com.br`. | Ensaio: **fechado** e fora do Google. Com `-Lancar`: **no ar** e liberado para o Google. |

A homologação vem primeiro: o script só envia para a produção com `-Ambiente producao`, pede para digitar `PRODUCAO` e, no `-Lancar`, também `SIM`. O `-Lancar` só é aceito na produção.

## Antes (uma vez por ambiente)

1. No hPanel da hospedagem, instale o WordPress no endereço do ambiente (Sites > WordPress), em português, com o prefixo de tabelas `wp_`. Para a homologação, crie o **staging** do domínio (`hml.ieadcvilaipiranga.com.br`) pelo recurso da Hostinger; ele nasce com WordPress e banco próprios. Anote o e-mail e a senha do administrador. O script falha com uma mensagem clara se não achar o WordPress na pasta. **Não use os botões "Sync" nem "Push to live" do staging**: eles copiam entre a homologação e a produção sem passar pelas travas (veja `docs/operacao/ci-cd.md`).
2. Em Avançado > SSH, deixe o acesso SSH habilitado (servidor, porta e usuário aparecem ali). A pasta do site é `domains/ieadcvilaipiranga.com.br/public_html` (produção) e `domains/ieadcvilaipiranga.com.br/public_html/hml` (homologação, o staging).
3. No `.env` do computador (**não vai para o git**: o repositório é público, então servidor, porta, usuário e endereços de teste ficam só ali; nunca coloque a senha):

   ```
   # Homologação
   HOMOLOG_SERVIDOR=...
   HOMOLOG_PORTA=...
   HOMOLOG_USUARIO=...
   HOMOLOG_URL=https://hml.ieadcvilaipiranga.com.br
   HOMOLOG_DESTINO=domains/ieadcvilaipiranga.com.br/public_html/hml
   # Produção (só quando for usar)
   HOSTINGER_SERVIDOR=...
   HOSTINGER_PORTA=...
   HOSTINGER_USUARIO=...
   ```

   A homologação só aceita **um destes dois pares exatos** de endereço e pasta (qualquer outro é recusado antes de conectar): `https://hml.ieadcvilaipiranga.com.br` com `domains/ieadcvilaipiranga.com.br/public_html/hml` (a hml fica **dentro** da pasta da produção, por isso a comparação é por igualdade), ou, de transição, `https://<algo>.hostingersite.com` com `domains/<algo>.hostingersite.com/public_html` (sem `HOMOLOG_DESTINO`). A produção só aceita `domains/ieadcvilaipiranga.com.br/public_html` (`HOSTINGER_DESTINO`, opcional) e, se o endereço dela não for `https://ieadcvilaipiranga.com.br`, `PRODUCAO_URL=`.
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

## Estado do site: fechado, aberto fora do Google e no ar

Desde a fase 4 o estado do site se muda **no painel, sem refazer a carga**: *Configurações > IEADC Vila Ipiranga*, caixa **Estado do site** no alto da tela (só aparece para administradores; usa nonce como o resto da tela). Ela mostra o estado de hoje em palavras e tem **dois interruptores**:

| Interruptor | Opção do WordPress que grava | Marcado | Desmarcado |
|---|---|---|---|
| **Site aberto ao público** | `ieadc_site_fechado` (marcado = 0) | visitantes veem o site | visitantes veem a página "em preparação" (503, sem indexação); quem tem login vê o site normalmente |
| **Permitir que o Google indexe o site** | `blog_public` (marcado = 1) | o Google pode indexar | pedido de não indexar (noindex e `robots.txt` fechado) |

Regra: **fechado nunca é indexado**. Com o site fechado, o segundo interruptor fica desmarcado e desabilitado. As três combinações possíveis, com o texto que a tela mostra:

1. **Hoje: fechado.** (aberto desmarcado) Só quem tem login vê o site. É o estado do ensaio de produção e de quem valida tudo antes de abrir.
2. **Hoje: aberto, fora do Google.** (aberto marcado, Google desmarcado) Qualquer pessoa com o link abre o site, mas o Google não indexa. É o estado da homologação, e serve para testar com ferramentas externas que precisam de acesso público: PageSpeed, Teste de Resultados Rich do Google, prévia do WhatsApp e do Facebook, medição do Analytics com visitantes reais.
3. **Hoje: no ar e indexável.** (os dois marcados) O site está lançado.

Fechado, o site responde **503 para visitantes** em tudo: páginas, `/wp-json/`, `/robots.txt` e também os mapas do site (`/sitemap_index.xml` do Yoast, `/post-sitemap.xml`, `/main-sitemap.xsl` e `/wp-sitemap.xml`), que listariam os endereços; quem tem login continua recebendo 200 (provado em `tests/php/medicao/teste-seguranca.php`). Ao salvar, o site esvazia o cache do LiteSpeed (páginas e `robots.txt` podem estar em cache e mostrariam o estado antigo). **Uma atualização do código (`CARGA=codigo`, `bin/atualizar-remoto.sh`) não muda este estado**: ele fica nas opções do banco, que a atualização não toca. A carga completa (`bin/migrar-remoto.sh`) sobrescreve o banco e volta ao estado do MODO escolhido; por isso, na produção, depois da carga inicial use só atualizações de código e este painel.

Passo a passo para validar a medição na produção com o site fechado: aplique o `GTM_ID` (`docs/operacao/rastreamento.md`), entre no site com o seu login (ele não é bloqueado), aceite o aviso de cookies, confira o Tag Manager (modo de visualização) e o Analytics (DebugView). Quando estiver satisfeito, marque **Site aberto ao público** (e, para o Google, **Permitir que o Google indexe**), salve e confira a página inicial numa janela anônima.

## Ensaio sem hospedagem (qualquer dia, quantas vezes quiser)

```
powershell -ExecutionPolicy Bypass -File bin\ensaio-local.ps1             # homologação
powershell -ExecutionPolicy Bypass -File bin\ensaio-local.ps1 -Producao   # ensaio da produção
powershell -ExecutionPolicy Bypass -File bin\ensaio-local.ps1 -Lancar     # lançamento da produção
powershell -ExecutionPolicy Bypass -File bin\ensaio-local.ps1 -Parar      # apaga o servidor de mentira
```

Sobe uma "Hostinger de mentira" no Docker (`tests\migracao`: SSH, Apache, WordPress e MariaDB, com o mesmo caminho `domains/.../public_html`) e roda o `bin\migrar.ps1` de verdade contra ela, com uma chave de ensaio (sem senha, em `content\backup\ensaio-hospedagem`, fora do git). Só fala com esse servidor de mentira. O site migrado abre em `http://localhost:8081` (painel: `admhost` / `Ensaio-Hospedagem-1`) e o script imprime uma conferência: endereço, `blog_public`, site fechado, regras do `.htaccess` e o status HTTP de um visitante. Para testar a fidelidade do site migrado: com o site aberto (homologação ou lançamento), defina a data fixa dos testes no servidor de mentira (`wp config set IEADC_HOJE 2026-10-06`) e rode `WP_URL=http://localhost:8081 ./bin/testar.sh markup` e `visual`. A agenda do protótipo mostra a "Confraternização dos jovens" (17 e 18/10/2026), que o site de verdade não tem: o `testar.sh` a cria só no banco local; no servidor de mentira crie-a antes com `wp eval-file tests/fixtures/evento-prototipo.php criar` (e `remover` depois).

Esse ensaio simples tem a produção e um único WordPress. Para ensaiar a homologação **aninhada** (um segundo WordPress dentro de `public_html/hml`, com outro banco) e provar que uma implantação nunca muda a outra, use `bash tests/migracao/aninhado/provar.sh` (veja `docs/operacao/ci-cd.md`; projeto `ieadc-ci-teste`, portas 2223 e 8082).

## O que o script faz e não faz

- Substitui o conteúdo (páginas, artigos, eventos, configurações, fotos, tema e mu-plugin) pelo do computador. **Não leva os usuários locais**: a conta de administrador da Hostinger continua a mesma.
- Remove pedidos de oração de teste e troca os endereços locais pelo endereço final: o que o banco guarda (`http://localhost:8080`) e o do `.env` (`SITE_URL`, por exemplo o IP da máquina para abrir pelo celular). Depois força `home` e `siteurl` para o endereço final.
- Grava as regras dos links limpos no `.htaccess` (o `wp rewrite flush --hard` do wp-cli não faz isso: sem as regras toda página que não é a inicial dá 404).
- A data fixa `IEADC_HOJE` do `.env` local não vai para o servidor: lá a agenda usa a data real.
- Guarda o banco anterior em `~/backup-antes-da-migracao-*.sql` no servidor (para voltar atrás: `wp db import` desse arquivo).
- Instala Blocksy e os plugins (SEO, 2FA, limite de login, LiteSpeed Cache, UpdraftPlus). Configure 2FA, UpdraftPlus (cópia para o Google Drive da igreja) e os e-mails dos formulários no painel depois.
- Na homologação os formulários funcionam de verdade: o que a equipe enviar ao testar vai para os e-mails de *Configurações > IEADC Vila Ipiranga* ou, se estiverem vazios, para o `admin_email` **que veio do banco local** (o script só o troca com `-EmailAdmin`). Por isso, na homologação rode com `-EmailAdmin <e-mail da equipe>` ou preencha os dois e-mails no painel. Pedidos de oração de teste ficam gravados lá e são apagados na próxima migração.
- **A conta de hospedagem pode ter várias pastas** (a do domínio principal, a da homologação em `public_html/hml` e as de outros sites). O script só escreve na pasta do par endereço + pasta escolhido (veja o `.env` acima) e a homologação **recusa** o domínio principal (`ieadcvilaipiranga.com.br`) e qualquer outro endereço ou pasta que não seja o par exato, mesmo que o `.env` ou um parâmetro aponte para ele. No servidor, a produção avisa se há uma `hml` e **não a toca**; a `hml` não escreve nada acima de si mesma.
- A Hostinger desativa `proc_open()` no PHP de linha de comando, e o wp-cli precisa dele para `db export` e `db import`. O `migrar-remoto.sh` detecta isso e roda o wp-cli com `php -d disable_functions=`.
- Reenviar sobre um site que já está no ar: o cache do LiteSpeed é esvaziado antes das opções finais; se aparecer página antiga, esvazie o cache pelo painel (LiteSpeed Cache > Gerenciar > Limpar tudo).
- A cada execução uma pasta temporária (cerca de 35 MB) fica em `content\backup\migracao` (fora do git); apague as antigas quando quiser.
