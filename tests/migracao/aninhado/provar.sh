#!/usr/bin/env bash
# Prova ao vivo, num servidor de teste ISOLADO, de que o pipeline implanta na homologação ANINHADA (hml, subpasta da produção)
# sem nunca tocar na produção, e na produção sem nunca tocar na hml. Não toca em nenhuma hospedagem real.
#
#   bash tests/migracao/aninhado/provar.sh subir    sobe o servidor (projeto ieadc-ci-teste, SSH 2223, web 8082) e instala os dois WordPress
#   bash tests/migracao/aninhado/provar.sh provar   roda a prova (precisa do site local em http://localhost:8080, só LIDO, e do .env do setup)
#   bash tests/migracao/aninhado/provar.sh parar    apaga o servidor de teste e a chave descartável (docker compose down -v do próprio projeto)
#   bash tests/migracao/aninhado/provar.sh          subir + provar (não apaga: rode "parar" no fim)
#
# Pré-requisitos: docker, ssh, scp, curl e bash (Git Bash no Windows). Os endereços de teste (hml.ieadcvilaipiranga.com.br:8082)
# são resolvidos para 127.0.0.1 só pelo curl (CURL_HOME com um .curlrc), sem editar o arquivo hosts.
# shellcheck disable=SC2016  # as verificações em "bash -c '...'" usam aspas simples de propósito (os valores chegam em $1 e $2)
set -uo pipefail
cd "$(dirname "$0")/../../.." || exit 1
export MSYS_NO_PATHCONV=1
RAIZ="$PWD"
COMPOSE=(docker compose -p ieadc-ci-teste -f tests/migracao/aninhado/docker-compose.yml)
CHAVE_DIR="$RAIZ/content/backup/ci-teste-chave"; CHAVE="$CHAVE_DIR/id_ed25519"
PORTA_SSH=2223; PORTA_WEB=8082
PROD=ieadcvilaipiranga.com.br; HML=hml.$PROD
P_PROD=domains/$PROD/public_html; P_HML=$P_PROD/hml
URL_PROD="http://$PROD:$PORTA_WEB"; URL_HML="http://$HML:$PORTA_WEB"
SITE_PROD=/var/www/html; SITE_HML=/var/www/html/hml   # onde as duas pastas moram dentro do servidor de teste
TRABALHO="${PROVA_TMP:-$(mktemp -d)}"
CURLH="$TRABALHO/curlhome"
if command -v cygpath > /dev/null 2>&1; then CURLH="$(cygpath -m "$CURLH")"; fi   # o curl do Git Bash é nativo do Windows e quer o caminho dele

ok=0; ko=0
passa() { ok=$((ok + 1)); echo "  ok      $1"; }
falha() { ko=$((ko + 1)); echo "  FALHOU  $1"; [ -z "${2:-}" ] || printf '%s\n' "$2" | tail -n 4 | cut -c1-200 | sed 's/^/            /'; }
confere() { local n="$1"; shift; if "$@" > /dev/null 2>&1; then passa "$n"; else falha "$n"; fi; }
passo() { echo; echo "== $1"; }
R() { "${COMPOSE[@]}" exec -T -u u123 -e HOME=/home/u123 servidor "$@"; }         # comando no servidor de teste, como o cliente (u123)
wpr() { local p="$1"; shift; R wp --path="$p" "$@"; }                              # wp-cli numa pasta do servidor de teste
opcao() { wpr "$1" option get "$2" 2> /dev/null | tr -d '\r'; }                    # opcao <pasta> <nome>
http() { CURL_HOME="$CURLH" curl -s -o /dev/null -L --max-redirs 3 --max-time 30 -w '%{http_code}' "$1" 2> /dev/null || true; }
saida_tem() { [[ "$SAIDA" == *"$1"* ]]; }
rc0_e_saida_tem() { [ "$RC" -eq 0 ] && saida_tem "$1"; }
rc_erro_e_saida_tem() { [ "$RC" -ne 0 ] && saida_tem "$1"; }
http_igual() { [ "$(http "$1")" = "$2" ]; }
existe_na_casa() { R sh -c "ls $1 > /dev/null 2>&1"; }
nao_existe_na_casa() { ! existe_na_casa "$1"; }

# ---------------------------------------------------------------------------------------------------------------------
subir() {
  passo "Servidor de teste (ieadc-ci-teste): produção em public_html e a hml aninhada em public_html/hml"
  mkdir -p "$CHAVE_DIR"
  [ -f "$CHAVE" ] || ssh-keygen -q -t ed25519 -N "" -f "$CHAVE" -C ci-teste-descartavel
  chmod 600 "$CHAVE" 2> /dev/null || true
  "${COMPOSE[@]}" up -d --build 2>&1 | tail -n 3
  local par
  for _ in $(seq 1 60); do R test -f $SITE_PROD/wp-config.php 2> /dev/null && break; sleep 2; done
  # Como no servidor de verdade, o wp-config.php tem os dados do banco escritos nele (a sessão SSH não herda as variáveis).
  for par in "DB_HOST banco" "DB_NAME hospedagem" "DB_USER hospedagem" "DB_PASSWORD hospedagem"; do
    # shellcheck disable=SC2086
    wpr $SITE_PROD config set $par > /dev/null 2>&1
  done
  wpr $SITE_PROD core is-installed 2> /dev/null || wpr $SITE_PROD core install "--url=$URL_PROD" --title="Produção de teste" \
    --admin_user=admhost --admin_password=Ensaio-Hospedagem-1 --admin_email=admin-hospedagem@example.com --skip-email > /dev/null 2>&1
  # A hml: WordPress próprio na subpasta, com banco próprio no mesmo MariaDB (como o staging da Hostinger).
  R test -f $SITE_HML/wp-config.php 2> /dev/null || {
    R sh -c "mkdir -p $SITE_HML && cp -a /usr/src/wordpress/. $SITE_HML/" \
      && wpr $SITE_HML config create --dbname=hospedagem_hml --dbuser=hospedagem --dbpass=hospedagem --dbhost=banco --dbprefix=wp_ --skip-check > /dev/null 2>&1
  }
  wpr $SITE_HML core is-installed 2> /dev/null || wpr $SITE_HML core install "--url=$URL_HML" --title="Homologação de teste" \
    --admin_user=admhml --admin_password=Ensaio-Hospedagem-1 --admin_email=admin-hml@example.com --skip-email > /dev/null 2>&1
  R sh -c 'mkdir -p /home/u123/domains/terceiro.com.br/public_html && echo "site de terceiro" > /home/u123/domains/terceiro.com.br/public_html/index.html'
  confere "WordPress da produção instalado (banco hospedagem)" wpr $SITE_PROD core is-installed
  confere "WordPress da hml instalado em outra pasta e em outro banco (hospedagem_hml)" test "$(wpr $SITE_HML config get DB_NAME | tr -d '\r')" = hospedagem_hml
}

parar() {
  "${COMPOSE[@]}" down -v 2>&1 | tail -n 4
  rm -rf "$CHAVE_DIR"
  echo "Servidor de teste apagado (contêineres, rede, volumes e chave descartável)."
}

# ---------------------------------------------------------------------------------------------------------------------
# Hashes (conteúdo e lista de arquivos) para provar quem não mudou.
h_sem_hml() { R sh -c "cd $SITE_PROD && { find . -path ./hml -prune -o -type f -print0 | LC_ALL=C sort -z | xargs -0 sha256sum; find . -path ./hml -prune -o -print | LC_ALL=C sort; } | sha256sum | cut -c1-16"; }
h_hml() { R sh -c "cd $SITE_HML && { find . -type f -print0 | LC_ALL=C sort -z | xargs -0 sha256sum; find . | LC_ALL=C sort; } | sha256sum | cut -c1-16"; }
h_terceiro() { R sh -c "cd /home/u123/domains/terceiro.com.br && { find . -type f -print0 | LC_ALL=C sort -z | xargs -0 sha256sum; find . | LC_ALL=C sort; } | sha256sum | cut -c1-16"; }
casa() { R sh -c "ls -1 /home/u123 | LC_ALL=C sort | tr '\n' ' '"; }

# imp VAR=valor... -- subcomando [args]: roda o bin/implantar.sh contra o servidor de teste (SAIDA = saída, RC = código)
imp() {
  local vars=()
  while [ $# -gt 0 ] && [ "$1" != "--" ]; do vars+=("$1"); shift; done
  shift
  SAIDA="$(env ALVO_SERVIDOR=127.0.0.1 ALVO_PORTA="$PORTA_SSH" ALVO_USUARIO=u123 ALVO_CHAVE="$CHAVE" ALVO_IMPRESSAO="$IMP" CURL_HOME="$CURLH" \
    DEPLOY_RUN_ID=424242 "${vars[@]}" ./bin/implantar.sh "$@" 2>&1)"; RC=$?
}
# recusa "descrição" VAR=valor... -- subcomando [args]: tem de recusar ANTES de conectar (sem "Chave do servidor conferida")
recusa() {
  local nome="$1"; shift; imp "$@"
  if [ "$RC" -ne 0 ] && saida_tem ERRO && ! saida_tem "Chave do servidor conferida"; then passa "recusou sem conectar: $nome"; else falha "deveria recusar: $nome" "$SAIDA"; fi
}
# no servidor: a mesma recusa, chamando direto o script remoto pelo SSH (sem a trava do cliente)
ssh_t() { ssh -i "$CHAVE" -p "$PORTA_SSH" -o IdentitiesOnly=yes -o BatchMode=yes -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o LogLevel=ERROR u123@127.0.0.1 "$@"; }
recusa_remota() {   # recusa_remota script "descrição" pasta url
  local script="$1" nome="$2" pasta="$3" url="$4" saida rc
  saida="$(ssh_t "bash ~/prova-recusa/$script-remoto.sh '$pasta' '$url' wp_ - homologacao http://localhost:8080" 2>&1)"; rc=$?
  if [ "$rc" -ne 0 ] && [[ "$saida" == *ERRO* ]] && [[ "$saida" == *"Nada foi alterado"* ]]; then passa "o servidor recusou ($script): $nome"; else falha "o servidor deveria recusar ($script): $nome" "$saida"; fi
}

provar() {
  passo "Preparo"
  mkdir -p "$CURLH"
  printf 'resolve = "%s:%s:127.0.0.1"\nresolve = "%s:%s:127.0.0.1"\n' "$PROD" "$PORTA_WEB" "$HML" "$PORTA_WEB" > "$CURLH/.curlrc"
  IMP="$(ssh-keyscan -p "$PORTA_SSH" -t ed25519 127.0.0.1 2> /dev/null | ssh-keygen -lf - | awk '{print $2}')"
  [ -n "$IMP" ] || { echo "ERRO: o servidor de teste não responde no SSH (rode: bash tests/migracao/aninhado/provar.sh subir)"; return 1; }
  [ "$(http http://localhost:8080/)" = 200 ] || { echo "ERRO: o site local (http://localhost:8080) não está no ar; ele é a origem do pacote (só lido)."; return 1; }
  [ "$(http "$URL_HML/")" != 000 ] || { echo "ERRO: o endereço $URL_HML não chega ao servidor de teste (CURL_HOME/.curlrc?)."; return 1; }
  PACOTE="$TRABALHO/pacote/ieadc-teste.tgz"
  PACOTE_SAIDA="$PACOTE" ./bin/implantar.sh empacotar 2>&1 | tail -n 3
  [ -s "$PACOTE" ] || { echo "ERRO: o pacote não foi gerado."; return 1; }
  git_sha="$(git rev-parse HEAD)"
  R sh -c 'rm -rf /home/u123/prova-recusa && mkdir -p /home/u123/prova-recusa'
  scp -P "$PORTA_SSH" -i "$CHAVE" -o IdentitiesOnly=yes -o BatchMode=yes -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o LogLevel=ERROR \
    bin/atualizar-remoto.sh bin/migrar-remoto.sh u123@127.0.0.1:prova-recusa/ > /dev/null

  passo "Estado de partida (produção sem a hml, a hml, a pasta de terceiro e o HOME)"
  H_PROD0="$(h_sem_hml)"; H_HML0="$(h_hml)"; H_TER0="$(h_terceiro)"; CASA0="$(casa)"
  echo "  produção(sem hml)=$H_PROD0 hml=$H_HML0 terceiro=$H_TER0"

  passo "Recusas ao vivo, no cliente (bin/implantar.sh enviar contra o servidor de teste: recusa antes de qualquer conexão)"
  H=(AMBIENTE=homologacao MODO=homologacao CARGA=completo)
  recusa "hml com a pasta da produção"       "${H[@]}" ALVO_URL="$URL_HML" ALVO_DESTINO="$P_PROD" -- enviar "$PACOTE"
  recusa "hml com barra final na pasta"      "${H[@]}" ALVO_URL="$URL_HML" ALVO_DESTINO="$P_HML/" -- enviar "$PACOTE"
  recusa "hml em maiúsculas"                 "${H[@]}" ALVO_URL="$URL_HML" ALVO_DESTINO="$P_PROD/HML" -- enviar "$PACOTE"
  recusa "hml/.."                            "${H[@]}" ALVO_URL="$URL_HML" ALVO_DESTINO="$P_HML/.." -- enviar "$PACOTE"
  recusa "hml com caminho absoluto"          "${H[@]}" ALVO_URL="$URL_HML" ALVO_DESTINO="/home/u123/$P_HML" -- enviar "$PACOTE"
  recusa "hml com a pasta de terceiro"       "${H[@]}" ALVO_URL="$URL_HML" ALVO_DESTINO="domains/terceiro.com.br/public_html" -- enviar "$PACOTE"
  recusa "domínio principal na homologação"  "${H[@]}" ALVO_URL="$URL_PROD" -- enviar "$PACOTE"
  recusa "domínio principal com a pasta hml" "${H[@]}" ALVO_URL="$URL_PROD" ALVO_DESTINO="$P_HML" -- enviar "$PACOTE"
  recusa "www na homologação"                "${H[@]}" ALVO_URL="http://www.$PROD:$PORTA_WEB" -- enviar "$PACOTE"
  recusa "outro subdomínio (staging)"        "${H[@]}" ALVO_URL="http://staging.$PROD:$PORTA_WEB" ALVO_DESTINO="$P_HML" -- enviar "$PACOTE"
  recusa "hml2"                              "${H[@]}" ALVO_URL="http://hml2.$PROD:$PORTA_WEB" ALVO_DESTINO="$P_HML" -- enviar "$PACOTE"
  recusa "a.hml"                             "${H[@]}" ALVO_URL="http://a.$HML:$PORTA_WEB" ALVO_DESTINO="$P_HML" -- enviar "$PACOTE"
  recusa "localhost na homologação"          "${H[@]}" ALVO_URL="http://localhost:$PORTA_WEB" ALVO_DESTINO="$P_PROD" -- enviar "$PACOTE"
  recusa "hostingersite com a pasta hml"     "${H[@]}" ALVO_URL="http://abc.hostingersite.com:$PORTA_WEB" ALVO_DESTINO="$P_HML" -- enviar "$PACOTE"
  recusa "homologação com MODO=lancar"       AMBIENTE=homologacao MODO=lancar CARGA=completo ALVO_URL="$URL_HML" -- enviar "$PACOTE"
  recusa "produção com o endereço da hml"    AMBIENTE=producao MODO=ensaio CARGA=completo CONFIRMACAO=CARGA-INICIAL ALVO_URL="$URL_HML" -- enviar "$PACOTE"
  recusa "produção com a pasta da hml"       AMBIENTE=producao MODO=ensaio CARGA=completo CONFIRMACAO=CARGA-INICIAL ALVO_URL="$URL_PROD" ALVO_DESTINO="$P_HML" -- enviar "$PACOTE"
  recusa "produção com a pasta dentro da hml (código)" AMBIENTE=producao CARGA=codigo CONFIRMACAO=PRODUCAO ALVO_URL="$URL_PROD" ALVO_DESTINO="$P_HML/wp-content" -- enviar "$PACOTE"
  recusa "produção sem confirmação"          AMBIENTE=producao MODO=ensaio CARGA=completo ALVO_URL="$URL_PROD" -- enviar "$PACOTE"
  recusa "ler-deploy: homologação com o domínio principal" AMBIENTE=homologacao ALVO_URL="$URL_PROD" -- ler-deploy
  recusa "ler-deploy: produção com o endereço da hml"      AMBIENTE=producao ALVO_URL="$URL_HML" -- ler-deploy
  recusa "fumaça: homologação com a pasta da produção"     AMBIENTE=homologacao MODO=homologacao ALVO_URL="$URL_HML" ALVO_DESTINO="$P_PROD" -- fumaca
  confere "as recusas não mudaram nada (produção, hml, terceiro, HOME)" test "$(h_sem_hml)$(h_hml)$(h_terceiro)$(casa)" = "$H_PROD0$H_HML0$H_TER0$CASA0"

  passo "Recusas ao vivo, no servidor (os scripts remotos chamados direto pelo SSH, sem a trava do cliente)"
  for s in atualizar migrar; do
    recusa_remota $s "produção com o endereço da hml"        "$P_PROD" "http://$HML:$PORTA_WEB"
    recusa_remota $s "hml com o domínio principal"           "$P_HML" "$URL_PROD"
    recusa_remota $s "hml com outro subdomínio"              "$P_HML" "http://staging.$PROD:$PORTA_WEB"
    recusa_remota $s "hml com barra final"                   "$P_HML/" "$URL_HML"
    recusa_remota $s "hml em maiúsculas"                     "$P_PROD/HML" "$URL_HML"
    recusa_remota $s "hml com .."                            "$P_HML/../.." "$URL_HML"
    recusa_remota $s "hml com caminho absoluto"              "/home/u123/$P_HML" "$URL_HML"
    recusa_remota $s "hml2"                                  "$P_PROD/hml2" "$URL_HML"
    recusa_remota $s "dentro da hml"                         "$P_HML/wp-content" "$URL_HML"
    recusa_remota $s "pasta de terceiro com o endereço hml"  "domains/terceiro.com.br/public_html" "$URL_HML"
    recusa_remota $s "pasta de terceiro com o domínio principal" "domains/terceiro.com.br/public_html" "$URL_PROD"
  done
  # Banco repartido: a hml apontando para o banco da produção é recusada (e nada é escrito).
  wpr $SITE_HML config set DB_NAME hospedagem > /dev/null 2>&1
  recusa_remota atualizar "hml com o MESMO banco da produção" "$P_HML" "$URL_HML"
  recusa_remota migrar "hml com o MESMO banco da produção" "$P_HML" "$URL_HML"
  wpr $SITE_HML config set DB_NAME hospedagem_hml > /dev/null 2>&1
  confere "wp-config da hml restaurado (banco hospedagem_hml)" test "$(opcao_cfg DB_NAME)" = hospedagem_hml
  H_HML0="$(h_hml)"   # o wp config set reescreve o wp-config da hml: novo ponto de partida da hml
  confere "as recusas do servidor não mudaram a produção nem o terceiro" test "$(h_sem_hml)$(h_terceiro)" = "$H_PROD0$H_TER0"
  R sh -c 'rm -rf /home/u123/prova-recusa'

  passo "1. Implantar na HML aninhada (homologação, carga completa)"
  imp AMBIENTE=homologacao MODO=homologacao CARGA=completo ALVO_URL="$URL_HML" ALVO_DESTINO="$P_HML" DEPLOY_SHA="$git_sha" -- enviar "$PACOTE"
  echo "$SAIDA" | grep -E "Implantação:|Fumaça|  ok  |FALHOU|Implantação concluída|ERRO" | cut -c1-150 | sed 's/^/    /'
  confere "implantação na hml terminou bem (código 0 e fumaça aprovada)" rc0_e_saida_tem "Fumaça aprovada"
  for c in / /sobre-nos/ /eventos/ /wp-login.php; do
    confere "hml responde 200 em $c" http_igual "$URL_HML$c" 200
  done
  confere "hml: blog_public=0 (fora do Google)" test "$(opcao $SITE_HML blog_public)" = 0
  confere "hml: ieadc_site_fechado=0 (aberta para quem tem o link)" test "$(opcao $SITE_HML ieadc_site_fechado)" = 0
  J="$(opcao $SITE_HML ieadc_deploy)"
  confere "hml: ieadc_deploy gravado (ambiente homologacao, resultado ok, sha do commit)" bash -c '[[ "$1" == *"\"ambiente\":\"homologacao\""* && "$1" == *"\"resultado\":\"ok\""* && "$1" == *"$2"* ]]' _ "$J" "$git_sha"
  confere "produção: NÃO tem ieadc_deploy (o registro foi só na hml)" test -z "$(opcao $SITE_PROD ieadc_deploy)"
  confere "produção (tudo fora da hml) IDÊNTICA antes e depois da implantação na hml" test "$(h_sem_hml)" = "$H_PROD0"
  confere "pasta de terceiro idêntica" test "$(h_terceiro)" = "$H_TER0"
  confere "a hml mudou (foi mesmo implantada)" test "$(h_hml)" != "$H_HML0"
  confere "cópia do banco com o rótulo hml- no HOME" existe_na_casa "/home/u123/backup-antes-da-migracao-hml-*.sql"
  confere "nenhuma cópia sem rótulo (as da produção) criada pela hml" nao_existe_na_casa "/home/u123/backup-antes-da-migracao-[0-9]*.sql"
  H_HML1="$(h_hml)"; H_PROD1="$(h_sem_hml)"

  passo "2. ler-deploy da hml aninhada e a trava da produção lendo esse ieadc_deploy"
  imp AMBIENTE=homologacao ALVO_URL="$URL_HML" ALVO_DESTINO="$P_HML" -- ler-deploy
  echo "    $SAIDA" | cut -c1-260
  confere "ler-deploy devolve o JSON da hml (sha, homologacao, ok)" bash -c '[[ "$1" == *"\"ambiente\":\"homologacao\""* && "$1" == *"\"resultado\":\"ok\""* && "$1" == *"$2"* ]]' _ "$SAIDA" "$git_sha"
  trava_na_rede "$git_sha"

  passo "3. Implantar na PRODUÇÃO do servidor de teste (ensaio fechado) com a hml ao lado"
  imp AMBIENTE=producao MODO=ensaio CARGA=completo CONFIRMACAO=CARGA-INICIAL ALVO_URL="$URL_PROD" DEPLOY_SHA="$git_sha" -- enviar "$PACOTE"
  echo "$SAIDA" | grep -E "Implantação:|Fumaça|  ok  |FALHOU|Implantação concluída|ERRO|AVISO" | cut -c1-150 | sed 's/^/    /'
  confere "implantação na produção (ensaio) terminou bem" rc0_e_saida_tem "Fumaça aprovada"
  confere "o servidor avisou que há uma hml e não a tocou" saida_tem "NÃO será tocada"
  confere "a pasta hml ficou IDÊNTICA (conteúdo e lista de arquivos) depois da implantação na produção" test "$(h_hml)" = "$H_HML1"
  confere "pasta de terceiro idêntica" test "$(h_terceiro)" = "$H_TER0"
  confere "a produção mudou (foi mesmo implantada)" test "$(h_sem_hml)" != "$H_PROD1"
  confere "produção: ieadc_deploy gravado como producao" bash -c '[[ "$1" == *"\"ambiente\":\"producao\""* ]]' _ "$(opcao $SITE_PROD ieadc_deploy)"
  confere "hml: ieadc_deploy continua o da homologação" bash -c '[[ "$1" == *"\"ambiente\":\"homologacao\""* ]]' _ "$(opcao $SITE_HML ieadc_deploy)"
  confere "hml: siteurl continua o endereço da hml" test "$(opcao $SITE_HML siteurl)" = "$URL_HML"
  confere "produção: siteurl é o endereço da produção" test "$(opcao $SITE_PROD siteurl)" = "$URL_PROD"
  confere "produção em ensaio responde 503 a visitantes" http_igual "$URL_PROD/" 503
  confere "a hml continua respondendo 200" http_igual "$URL_HML/" 200
  imp AMBIENTE=producao ALVO_URL="$URL_PROD" -- ler-deploy
  confere "ler-deploy da produção devolve ambiente producao" bash -c '[[ "$1" == *"\"ambiente\":\"producao\""* ]]' _ "$SAIDA"
  H_PROD2="$(h_sem_hml)"

  passo "4. Só código (atualizar-remoto.sh) na hml e na produção"
  imp AMBIENTE=homologacao CARGA=codigo ALVO_URL="$URL_HML" ALVO_DESTINO="$P_HML" DEPLOY_SHA="$git_sha" -- enviar "$PACOTE"
  confere "código na hml terminou bem" test "$RC" -eq 0
  [ "$RC" -eq 0 ] || echo "$SAIDA" | tail -n 5 | cut -c1-200
  confere "código na hml: nada fora da hml mudou (produção e terceiro)" test "$(h_sem_hml)$(h_terceiro)" = "$H_PROD2$H_TER0"
  # O código do pacote é o mesmo da carga completa, então os arquivos da hml ficam iguais: o que muda é a opção ieadc_deploy (no banco da hml).
  confere "código na hml: ieadc_deploy da hml registra a carga codigo (ok)" bash -c '[[ "$1" == *"\"carga\":\"codigo\""* && "$1" == *"\"resultado\":\"ok\""* && "$1" == *"\"ambiente\":\"homologacao\""* ]]' _ "$(opcao $SITE_HML ieadc_deploy)"
  confere "código na hml: a produção continua com o ieadc_deploy da carga completa (nada registrado nela)" bash -c '[[ "$1" == *"\"carga\":\"completo\""* ]]' _ "$(opcao $SITE_PROD ieadc_deploy)"
  confere "código na hml: cópia de código com o rótulo hml-" existe_na_casa "/home/u123/backup-codigo-antes-hml-*.tgz"
  H_HML3="$(h_hml)"
  imp AMBIENTE=producao CARGA=codigo CONFIRMACAO=PRODUCAO ALVO_URL="$URL_PROD" DEPLOY_SHA="$git_sha" -- enviar "$PACOTE"
  confere "código na produção terminou bem" test "$RC" -eq 0
  [ "$RC" -eq 0 ] || echo "$SAIDA" | tail -n 5 | cut -c1-200
  confere "código na produção: a hml ficou IDÊNTICA" test "$(h_hml)" = "$H_HML3"
  confere "código na produção: terceiro idêntico" test "$(h_terceiro)" = "$H_TER0"
  confere "código na produção: a cópia de código dela (sem rótulo) existe e a da hml continua" existe_na_casa "/home/u123/backup-codigo-antes-[0-9]*.tgz /home/u123/backup-codigo-antes-hml-*.tgz"

  echo
  if [ "$ko" -eq 0 ]; then echo "PROVA ANINHADA OK: $ok verificações, 0 falhas."; else echo "PROVA ANINHADA COM FALHAS: $ko de $((ok + ko)) verificações."; fi
  [ "$ko" -eq 0 ]
}
opcao_cfg() { wpr $SITE_HML config get "$1" 2> /dev/null | tr -d '\r'; }

# A trava da produção (bin/trava-producao.sh) lendo o ieadc_deploy da hml aninhada. Roda dentro de um contêiner da MESMA rede do
# servidor de teste (precisa do jq e fala com o servidor pelo nome "servidor", porta 22), com um gh de mentira que devolve respostas
# coerentes com o commit implantado: nada vai ao GitHub. Os casos negativos usam o mesmo contêiner.
trava_na_rede() {
  local sha="$1" falsos="$TRABALHO/falsos" n; n="$(date +%s)"; mkdir -p "$falsos"
  cat > "$falsos/gh" <<'FIM'
#!/usr/bin/env bash
# gh de mentira (só para a prova): respostas coerentes com o commit $FALSO_SHA e a execução 424242.
a="$*"
case "$a" in
  *"commits/main"*) echo "$FALSO_SHA" ;;
  *"run list"*) echo 1 ;;
  *"matching-refs"*) echo 0 ;;
  *"/artifacts"*) printf '{"artifacts":[{"name":"pacote-hml-%s","expired":false}]}\n' "$FALSO_SHA" ;;
  *"actions/runs/"*) printf '{"status":"completed","conclusion":"success","head_sha":"%s","path":".github/workflows/homologacao.yml@refs/heads/main"}\n' "$FALSO_SHA" ;;
  *) echo "gh de mentira: chamada inesperada: $a" >&2; exit 1 ;;
esac
FIM
  cat > "$falsos/rodar-trava.sh" <<'FIM'
#!/usr/bin/env bash
# Dentro do contêiner: prepara a chave, instala o jq e roda a trava com as variáveis recebidas.
set -u
apt-get update -qq > /dev/null 2>&1; apt-get install -y -qq jq > /dev/null 2>&1
cp /chave/id_ed25519 /tmp/chave && chmod 600 /tmp/chave
cp /falsos/gh /usr/local/bin/gh && chmod +x /usr/local/bin/gh
export ALVO_CHAVE=/tmp/chave
cd /repo && bash bin/trava-producao.sh
FIM
  trava() {   # trava <sha-do-github> [-e VAR=valor...]
    local sha_gh="$1"; shift
    SAIDA="$(docker run --rm --network ieadc-ci-teste_default -v "$(cygpath -m "$RAIZ"):/repo:ro" -v "$(cygpath -m "$CHAVE_DIR"):/chave:ro" \
      -v "$(cygpath -m "$falsos"):/falsos:ro" --entrypoint bash \
      -e "VERSAO=v9.$((n % 1000)).0" -e MODO=codigo -e CONFIRMACAO=PRODUCAO -e GITHUB_REF=refs/heads/main -e "GITHUB_SHA=$sha_gh" -e "FALSO_SHA=$sha_gh" \
      -e GITHUB_REPOSITORY=dono/repo -e GH_TOKEN=falso -e AMBIENTE=homologacao -e ALVO_SERVIDOR=servidor -e ALVO_PORTA=22 -e ALVO_USUARIO=u123 \
      -e "ALVO_IMPRESSAO=$IMP" -e "ALVO_URL=$URL_HML" -e "ALVO_DESTINO=$P_HML" "$@" \
      ieadc-ci-teste-servidor /falsos/rodar-trava.sh 2>&1)"; RC=$?
  }
  trava "$sha"
  confere "trava-producao.sh lê o ieadc_deploy da hml aninhada e libera (mesmo commit, execução ok, pacote)" rc0_e_saida_tem "Trava liberada"
  [ "$RC" -eq 0 ] || echo "$SAIDA" | tail -n 6 | cut -c1-200 | sed 's/^/    /'
  trava "0000000000000000000000000000000000000000"
  confere "trava-producao.sh recusa quando a hml está em outro commit" rc_erro_e_saida_tem "A homologação está no commit"
  trava "$sha" -e "ALVO_URL=$URL_PROD" -e "ALVO_DESTINO=$P_PROD"
  confere "trava-producao.sh recusa a produção no lugar da homologação" rc_erro_e_saida_tem "não pode apontar para o domínio da produção"
  trava "$sha" -e "ALVO_DESTINO=$P_PROD"
  confere "trava-producao.sh recusa a pasta da produção para a hml" rc_erro_e_saida_tem "deve ser exatamente"
}

case "${1:-tudo}" in
  subir) subir ;;
  provar) provar ;;
  parar) parar ;;
  tudo) subir && provar ;;
  *) sed -n '2,/^set -uo/p' "$0" | sed '$d'; exit 2 ;;
esac
