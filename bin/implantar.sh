#!/usr/bin/env bash
# Implantação do site por SSH, para o GitHub Actions (runner Linux) e para o Git Bash. Veja docs/operacao/ci-cd.md.
#
# Subcomandos (as etapas são separadas porque a produção recebe o MESMO pacote que a homologação recebeu):
#   empacotar            monta o pacote .tgz a partir do site local no ar (docker compose): banco sem usuários, fotos,
#                        tema, mu-plugins e os scripts remotos. Saída em PACOTE_SAIDA.
#   enviar [pacote.tgz]  envia um pacote pronto, instala no servidor, roda a fumaça e, SÓ SE ela passar, grava a opção
#                        ieadc_deploy (resultado "ok"). Se a fumaça falhar, a opção fica marcada com resultado "falhou".
#   fumaca               só os testes de fumaça por HTTP (e a conferência das opções do site, por SSH).
#   ler-deploy           imprime a opção ieadc_deploy (JSON) do servidor; é a única coisa que sai na saída padrão.
#
# Variáveis de ambiente (nenhum valor é gravado em arquivo versionado):
#   ALVO_SERVIDOR, ALVO_PORTA, ALVO_USUARIO   dados do SSH (hPanel > Avançado > SSH)
#   ALVO_CHAVE        arquivo da chave privada SSH (sem senha, só para este fim)
#   ALVO_IMPRESSAO    impressão digital SHA256 da chave do servidor (SHA256:...). Se não bater, recusa e não conecta.
#   ALVO_URL          endereço final do site (http(s)://domínio, sem pasta)
#   ALVO_DESTINO      pasta do site no servidor. Opcional, e só é aceita se for EXATAMENTE a padrão:
#                     domains/<domínio do site>/public_html (a da produção é sempre domains/ieadcvilaipiranga.com.br/public_html).
#   ALVO_EMAIL_ADMIN  e-mail do administrador do site (padrão: não mexe)
#   AMBIENTE          homologacao | producao (padrão na carga completa: deduzido do MODO; obrigatório na carga "codigo").
#                     A homologação nunca aceita o domínio da produção; a produção só aceita o domínio da produção.
#   CARGA             completo (padrão: código + banco, bin/migrar-remoto.sh) | codigo (só tema e mu-plugins, bin/atualizar-remoto.sh)
#   MODO              só na carga completa. homologacao | homologacao-fechada (ambiente homologacao);
#                     ensaio | lancar (ambiente producao). Qualquer outra combinação é recusada.
#   CONFIRMACAO       só no ambiente producao: PRODUCAO (carga "codigo") ou CARGA-INICIAL (carga completa). Se a produção
#                     já recebeu uma carga (opção ieadc_deploy ou ieadc_site_fechado), a carga completa só passa com
#                     SUBSTITUIR-CONTEUDO (apaga o que a equipe editou no painel).
#   PACOTE_SAIDA      onde o "empacotar" grava o .tgz (padrão: content/backup/migracao/ieadc-<data>.tgz)
#   DEPLOY_SHA, DEPLOY_VERSAO, DEPLOY_RUN_ID   o que vai para a opção ieadc_deploy (padrões: SHA do pacote, "-", GITHUB_RUN_ID)
#   ESPERADO          aberto | fechado: força o que a fumaça espera (padrão: deduzido do MODO ou lido do servidor)
#   DOMINIO_PRODUCAO  só para ensaiar contra uma hospedagem de mentira (padrão: ieadcvilaipiranga.com.br); os workflows não o definem
set -euo pipefail
cd "$(dirname "$0")/.."
export MSYS_NO_PATHCONV=1   # Git Bash no Windows não pode reescrever caminhos como /content/backup/...
# No GitHub Actions o domínio principal é sempre este (a variável só existe para ensaiar contra um servidor de mentira).
if [ "${GITHUB_ACTIONS:-}" = true ]; then DOMINIO_PRODUCAO="ieadcvilaipiranga.com.br"; else DOMINIO_PRODUCAO="${DOMINIO_PRODUCAO:-ieadcvilaipiranga.com.br}"; fi

# Mensagens vão para a saída de erro; a saída padrão fica livre para dados (ler-deploy).
info() { echo "$*" >&2; }
erro() { echo "ERRO: $*" >&2; exit 1; }
exigir() { local v; for v in "$@"; do [ -n "${!v:-}" ] || erro "falta a variável $v."; done; }
# O que vai dentro do comando remoto só pode ter caracteres comuns de endereço, caminho e lista.
seguro() { [[ "${!1:-}" =~ ^[A-Za-z0-9._/:@,+=-]+$ ]] || erro "$1 está vazio ou tem caracteres que não posso passar ao servidor com segurança."; }
limpo() { printf '%s' "$1" | tr -c 'A-Za-z0-9._:+-' '_'; }

TMPS=(); SSH_OPTS=()
limpar() { [ "${#TMPS[@]}" -eq 0 ] || rm -rf "${TMPS[@]}"; }
trap limpar EXIT
# Cria um arquivo temporário (apagado ao sair) e devolve o caminho em $TMP_NOVO (sem subshell, para o trap enxergá-lo).
tmp() { TMP_NOVO="$(mktemp)"; TMPS+=("$TMP_NOVO"); }

wp_local() { docker compose run --rm -T --no-deps wpcli wp "$@"; }

# ---------------------------------------------------------------------------------------------------------------------
cmd_empacotar() {
  command -v docker >/dev/null || erro "não achei o docker."
  local prefixo origem guardados origens data base pasta saida sha
  info "» 1/3 Site local no ar"
  prefixo="$(wp_local config get table_prefix 2>/dev/null | tail -n 1 | tr -d '\r[:space:]')"
  [ -n "$prefixo" ] || erro "não consegui ler o prefixo das tabelas; o site local está no ar? (./bin/setup.sh)"
  origem="$(wp_local option get home 2>/dev/null | tail -n 1 | tr -d '\r[:space:]')"; origem="${origem%/}"
  [ -n "$origem" ] || erro "não consegui ler o endereço do site local."
  # O banco pode guardar um endereço (home/siteurl) e o ambiente outro (WP_HOME): troca-se os dois no destino.
  guardados="$(wp_local db query "SELECT option_value FROM ${prefixo}options WHERE option_name IN ('home','siteurl')" --skip-column-names 2>/dev/null | tr -d '\r' | sed 's:/*$::' | grep -E '^https?://' || true)"
  origens="$({ echo "$origem"; echo "$guardados"; } | awk 'NF && !v[$0]++' | paste -sd, -)"

  data="$(date -u +%Y-%m-%d-%H%M%S)"
  base="content/backup/migracao"
  pasta="$base/$data"
  mkdir -p "$pasta/wp-content/themes" "$pasta/wp-content/mu-plugins"
  chmod 777 "$pasta"   # o wp-cli roda como outro usuário (33) e precisa gravar o banco.sql aqui

  info "» 2/3 Banco (sem usuários) e arquivos"
  wp_local db export "/content/backup/migracao/$data/banco.sql" --exclude_tables="${prefixo}users,${prefixo}usermeta" >&2
  [ -s "$pasta/banco.sql" ] || erro "o banco não foi exportado."
  tail -n 5 "$pasta/banco.sql" | grep -q 'Dump completed' || erro "a exportação do banco ficou incompleta."
  docker compose cp wordpress:/var/www/html/wp-content/uploads "$pasta/wp-content/uploads" >&2
  cp -a wp-content/themes/ieadc-vila-ipiranga "$pasta/wp-content/themes/"
  cp -a wp-content/mu-plugins/. "$pasta/wp-content/mu-plugins/"
  cp bin/migrar-remoto.sh bin/atualizar-remoto.sh "$pasta/"
  sha="${DEPLOY_SHA:-$(git rev-parse HEAD 2>/dev/null || echo desconhecido)}"
  {
    echo "PACOTE_PREFIXO=$prefixo"
    echo "PACOTE_ORIGENS=$origens"
    echo "PACOTE_SHA=$sha"
    echo "PACOTE_CRIADO=$(date -u +%Y-%m-%dT%H:%M:%SZ)"
  } > "$pasta/pacote.env"

  info "» 3/3 Pacote"
  saida="${PACOTE_SAIDA:-$base/ieadc-$data.tgz}"
  mkdir -p "$(dirname "$saida")"
  tar -czf "$saida" -C "$pasta" .
  ( cd "$(dirname "$saida")" && sha256sum "$(basename "$saida")" > "$(basename "$saida").sha256" )
  rm -rf "$pasta" 2>/dev/null || true
  info "Pacote pronto: $saida ($(du -h "$saida" | cut -f1)), commit $sha"
  info "$(cut -d' ' -f1 "$saida.sha256")  sha256"
}

# ---------------------------------------------------------------------------------------------------------------------
# Lê uma chave do pacote.env de dentro do .tgz (sem extrair nada em disco).
meta() { { tar -xzOf "$1" ./pacote.env 2>/dev/null || tar -xzOf "$1" pacote.env 2>/dev/null || true; } | sed -n "s/^$2=//p" | head -n 1 | tr -d '\r'; }

# Confere a impressão digital do servidor ANTES de conectar e monta as opções do ssh/scp com a chave do host fixada:
# só a chave que bate com ALVO_IMPRESSAO entra no known_hosts, e o ssh roda com StrictHostKeyChecking=yes.
preparar_ssh() {
  exigir ALVO_SERVIDOR ALVO_PORTA ALVO_USUARIO ALVO_CHAVE ALVO_IMPRESSAO
  [ -f "$ALVO_CHAVE" ] || erro "o arquivo da chave SSH (ALVO_CHAVE) não existe."
  [[ "$ALVO_PORTA" =~ ^[0-9]+$ ]] || erro "ALVO_PORTA deve ser um número."
  [[ "$ALVO_SERVIDOR" =~ ^[A-Za-z0-9._:-]+$ ]] || erro "ALVO_SERVIDOR tem caracteres inválidos."
  [[ "$ALVO_USUARIO" =~ ^[A-Za-z0-9._-]+$ ]] || erro "ALVO_USUARIO tem caracteres inválidos."
  local esperada="${ALVO_IMPRESSAO#SHA256:}" varredura linha parte fp vistas="" tipo="" algoritmos
  esperada="SHA256:${esperada//[[:space:]]/}"
  tmp; varredura="$TMP_NOVO"
  ssh-keyscan -T 15 -p "$ALVO_PORTA" -t ed25519,ecdsa,rsa "$ALVO_SERVIDOR" > "$varredura" 2>/dev/null || true
  [ -s "$varredura" ] || erro "não consegui obter a chave do servidor (porta $ALVO_PORTA fechada ou sem resposta?). Não conectei."
  tmp; KNOWN_HOSTS="$TMP_NOVO"; tmp; parte="$TMP_NOVO"
  while IFS= read -r linha; do
    case "$linha" in ''|'#'*) continue ;; esac
    printf '%s\n' "$linha" > "$parte"
    fp="$(ssh-keygen -lf "$parte" 2>/dev/null | awk '{print $2}')"
    vistas="$vistas $fp"
    if [ "$fp" = "$esperada" ]; then
      printf '%s\n' "$linha" > "$KNOWN_HOSTS"
      tipo="$(printf '%s' "$linha" | awk '{print $2}')"
      break
    fi
  done < "$varredura"
  [ -n "$tipo" ] || erro "a impressão digital do servidor NÃO bate com ALVO_IMPRESSAO. Recusei a conexão (pode ser o servidor errado ou alguém no caminho). Impressões que o servidor apresentou:${vistas:- nenhuma}"
  case "$tipo" in
    ssh-rsa) algoritmos="rsa-sha2-512,rsa-sha2-256,ssh-rsa" ;;
    *) algoritmos="$tipo" ;;
  esac
  SSH_OPTS=(-i "$ALVO_CHAVE" -o IdentitiesOnly=yes -o BatchMode=yes -o StrictHostKeyChecking=yes
    -o "UserKnownHostsFile=$KNOWN_HOSTS" -o GlobalKnownHostsFile=/dev/null -o "HostKeyAlgorithms=$algoritmos"
    -o ConnectTimeout=20 -o ServerAliveInterval=30 -o LogLevel=ERROR)
  info "Chave do servidor conferida ($tipo, $esperada)."
}
ssh_alvo() { ssh -p "$ALVO_PORTA" "${SSH_OPTS[@]}" "$ALVO_USUARIO@$ALVO_SERVIDOR" "$@"; }
scp_alvo() { scp -P "$ALVO_PORTA" "${SSH_OPTS[@]}" "$@"; }


# ---------------------------------------------------------------------------------------------------------------------
# Trava de domínio e de pasta. Na mesma conta SSH moram a homologação, a produção e sites de terceiros (outras pastas em
# domains/): o endereço precisa ser o do ambiente e a pasta só pode ser a padrão desse endereço, nunca outra.
definir_destino() {
  exigir ALVO_URL AMBIENTE
  case "$AMBIENTE" in homologacao|producao) ;; *) erro "AMBIENTE deve ser homologacao ou producao." ;; esac
  URL="${ALVO_URL%/}"
  [[ "$URL" =~ ^https?://([A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*)(:[0-9]+)?$ ]] || erro "ALVO_URL deve ter o formato http(s)://domínio, sem pasta, espaços nem caracteres especiais."
  HOST="$(printf '%s' "${BASH_REMATCH[1]}" | tr '[:upper:]' '[:lower:]')"
  local principal=nao pasta_host="$HOST" esperada
  if [ "$HOST" = "$DOMINIO_PRODUCAO" ] || [ "$HOST" = "www.$DOMINIO_PRODUCAO" ]; then principal=sim; fi
  if [ "$AMBIENTE" = producao ]; then
    [ "$principal" = sim ] || erro "na produção o endereço deve ser o do domínio principal ($DOMINIO_PRODUCAO ou www.$DOMINIO_PRODUCAO)."
    pasta_host="$DOMINIO_PRODUCAO"
  elif [ "$principal" = sim ]; then
    erro "a homologação não pode apontar para o domínio da produção ($DOMINIO_PRODUCAO). Confira o endereço da homologação."
  fi
  esperada="domains/$pasta_host/public_html"
  DESTINO="${ALVO_DESTINO:-$esperada}"
  case "$DESTINO" in
    /*|*..*|*//*|*\\*|*[[:space:]]*|*~*) erro "a pasta do site é inválida: não pode começar com /, ter .., // ou espaços." ;;
  esac
  [ "$DESTINO" = "$esperada" ] || erro "a pasta do site deve ser exatamente $esperada (nenhuma outra pasta da conta é tocada). Nada foi enviado."
  seguro URL; seguro DESTINO
}

# Carga e modo precisam combinar com o ambiente: homologação só abre ou fecha a homologação; a produção só ensaia ou lança.
validar_carga_modo() {
  CARGA="${CARGA:-completo}"
  case "$CARGA" in
    completo)
      exigir MODO
      case "$MODO" in homologacao|homologacao-fechada|ensaio|lancar) ;; *) erro "MODO deve ser homologacao, homologacao-fechada, ensaio ou lancar." ;; esac
      if [ -z "${AMBIENTE:-}" ]; then
        case "$MODO" in homologacao*) AMBIENTE=homologacao ;; *) AMBIENTE=producao ;; esac
      fi
      case "$AMBIENTE:$MODO" in
        homologacao:homologacao|homologacao:homologacao-fechada|producao:ensaio|producao:lancar) ;;
        *) erro "o modo $MODO não combina com o ambiente $AMBIENTE (homologação: homologacao ou homologacao-fechada; produção: ensaio ou lancar)." ;;
      esac ;;
    codigo) exigir AMBIENTE ;;
    *) erro "CARGA deve ser completo ou codigo." ;;
  esac
  case "$AMBIENTE" in homologacao|producao) ;; *) erro "AMBIENTE deve ser homologacao ou producao." ;; esac
}

# Na produção o comando só passa com a confirmação certa (o workflow Produção pede ao Felipe que a digite).
validar_confirmacao() {
  [ "$AMBIENTE" = producao ] || return 0
  if [ "$CARGA" = codigo ]; then
    [ "${CONFIRMACAO:-}" = "PRODUCAO" ] || erro "para a produção (carga codigo) a CONFIRMACAO deve ser PRODUCAO."
  else
    case "${CONFIRMACAO:-}" in
      CARGA-INICIAL|SUBSTITUIR-CONTEUDO) ;;
      *) erro "para a produção (carga completa) a CONFIRMACAO deve ser CARGA-INICIAL (primeira carga) ou SUBSTITUIR-CONTEUDO." ;;
    esac
  fi
}

json_deploy() {
  printf '{"sha":"%s","ambiente":"%s","versao":"%s","quando":"%s","run_id":"%s","carga":"%s","pacote":"%s","resultado":"%s"}' \
    "$(limpo "$DEPLOY_SHA")" "$AMBIENTE" "$(limpo "${DEPLOY_VERSAO:--}")" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" \
    "$(limpo "${DEPLOY_RUN_ID:-${GITHUB_RUN_ID:-local}}")" "$CARGA" "$PACOTE_HASH" "$1"
}
# Grava a opção ieadc_deploy no servidor. resultado: ok (fumaça passou) ou falhou.
registrar_deploy() {
  ssh_alvo "wp --path=\"\$HOME/$DESTINO\" option update ieadc_deploy '$(json_deploy "$1")' --autoload=no" >&2
}

cmd_ler_deploy() {
  definir_destino; preparar_ssh
  ssh_alvo "wp --path=\"\$HOME/$DESTINO\" option get ieadc_deploy" | tr -d '\r'
}

# ---------------------------------------------------------------------------------------------------------------------
# Devolve 0 se tudo passou e 1 se alguma verificação falhou (quem chama decide o que fazer com o ieadc_deploy).
cmd_fumaca() {
  definir_destino
  local base="$URL" falhas=0 esperado="${ESPERADO:-}" fechado="" publico="" opcoes estado cod
  if [ -z "$esperado" ] && [ "${CARGA:-completo}" = "completo" ] && [ -n "${MODO:-}" ]; then
    case "$MODO" in homologacao|lancar) esperado=aberto ;; *) esperado=fechado ;; esac
  fi
  if [ -n "${ALVO_CHAVE:-}" ] && [ -n "${ALVO_IMPRESSAO:-}" ]; then
    [ "${#SSH_OPTS[@]}" -gt 0 ] || preparar_ssh
    opcoes="$(ssh_alvo "w=\"\$HOME/$DESTINO\"; echo \"fechado=\$(wp --path=\"\$w\" option get ieadc_site_fechado 2>/dev/null)\"; echo \"publico=\$(wp --path=\"\$w\" option get blog_public 2>/dev/null)\"")" \
      || { info "ERRO: não consegui consultar as opções do site por SSH."; return 1; }
    fechado="$(printf '%s\n' "$opcoes" | sed -n 's/^fechado=//p' | tr -d '\r[:space:]')"
    publico="$(printf '%s\n' "$opcoes" | sed -n 's/^publico=//p' | tr -d '\r[:space:]')"
    info "Opções no servidor: ieadc_site_fechado=${fechado:-0} blog_public=${publico:-?}"
    if [ -z "$esperado" ]; then
      if [ "${fechado:-0}" = "1" ]; then esperado=fechado; else esperado=aberto; fi
    fi
    if [ "${CARGA:-completo}" = "completo" ] && [ -n "${MODO:-}" ]; then
      local f_ok p_ok
      case "$MODO" in
        lancar) f_ok=0 p_ok=1 ;;
        homologacao) f_ok=0 p_ok=0 ;;
        *) f_ok=1 p_ok=0 ;;
      esac
      if [ "${fechado:-0}" != "$f_ok" ] || [ "$publico" != "$p_ok" ]; then
        info "  FALHOU  modo $MODO: ieadc_site_fechado=${fechado:-0} e blog_public=${publico:-?} (esperado $f_ok e $p_ok)"
        falhas=$((falhas + 1))
      fi
    fi
  fi
  [ -n "$esperado" ] || erro "não sei se o site deve estar aberto ou fechado: informe MODO, ESPERADO ou a chave SSH."
  case "$esperado" in aberto) estado=200 ;; fechado) estado=503 ;; *) erro "ESPERADO deve ser aberto ou fechado." ;; esac

  info "Fumaça em $base (site $esperado: páginas $estado)"
  http() { curl -sS -o /dev/null -L --max-redirs 3 --max-time 30 -A 'ieadc-deploy-fumaca/1.0' -w '%{http_code}' "$1" 2>/dev/null || true; }
  # Tenta algumas vezes: logo depois de limpar o cache o servidor pode demorar a responder.
  checar() {
    local caminho="$1" esperado_http="$2" cod=""
    for _ in 1 2 3 4 5; do
      cod="$(http "$base$caminho")"; [ "$cod" = "$esperado_http" ] && break; sleep 4
    done
    if [ "$cod" = "$esperado_http" ]; then info "  ok      $cod $caminho"; else info "  FALHOU  $caminho: veio ${cod:-sem resposta}, esperado $esperado_http"; falhas=$((falhas + 1)); fi
  }
  checar / "$estado"
  checar /sobre-nos/ "$estado"
  checar /eventos/ "$estado"
  checar /wp-login.php 200   # o painel abre mesmo com o site fechado
  if [ "$esperado" = "aberto" ]; then
    # O Yoast serve o /sitemap_index.xml mesmo com blog_public=0 (homologação); sem o Yoast vale o /wp-sitemap.xml do WordPress.
    cod="$(http "$base/sitemap_index.xml")"
    [ "$cod" = 200 ] || cod="$(http "$base/wp-sitemap.xml")"
    if [ "$cod" = 200 ]; then info "  ok      200 sitemap"; else info "  FALHOU  sitemap: nem /sitemap_index.xml nem /wp-sitemap.xml deram 200"; falhas=$((falhas + 1)); fi
  fi
  if [ "$falhas" -ne 0 ]; then info "ERRO: $falhas verificação(ões) da fumaça falharam em $base."; return 1; fi
  info "Fumaça aprovada."
}

# ---------------------------------------------------------------------------------------------------------------------
cmd_enviar() {
  local pacote="${1:-${PACOTE_SAIDA:-}}" prefixo origens sha_pacote remoto email carga_previa
  [ -n "$pacote" ] && [ -f "$pacote" ] || erro "informe o pacote .tgz (bin/implantar.sh enviar <pacote.tgz>)."
  validar_carga_modo
  definir_destino
  validar_confirmacao

  prefixo="$(meta "$pacote" PACOTE_PREFIXO)"; origens="$(meta "$pacote" PACOTE_ORIGENS)"; sha_pacote="$(meta "$pacote" PACOTE_SHA)"
  [ -n "$prefixo" ] && [ -n "$origens" ] || erro "o pacote não tem pacote.env (foi feito por bin/implantar.sh empacotar?)."
  seguro prefixo; seguro origens
  email="${ALVO_EMAIL_ADMIN:--}"; seguro email
  DEPLOY_SHA="${DEPLOY_SHA:-$sha_pacote}"
  if [ -n "$sha_pacote" ] && [ "$sha_pacote" != "desconhecido" ] && [ "$DEPLOY_SHA" != "$sha_pacote" ]; then
    erro "o pacote foi feito do commit $sha_pacote, mas a implantação é do commit $DEPLOY_SHA."
  fi
  PACOTE_HASH="$(sha256sum "$pacote" | cut -d' ' -f1)"

  preparar_ssh
  # A carga completa troca TODO o conteúdo. Na produção que já recebeu uma carga (inclusive pelo caminho manual, que deixa
  # a opção ieadc_site_fechado), isso apagaria o que a equipe editou no painel: só com a confirmação explícita.
  if [ "$AMBIENTE" = producao ] && [ "$CARGA" = completo ] && [ "${CONFIRMACAO:-}" != "SUBSTITUIR-CONTEUDO" ]; then
    carga_previa="$(ssh_alvo "w=\"\$HOME/$DESTINO\"; wp --path=\"\$w\" core is-installed >/dev/null 2>&1 || exit 3; for o in ieadc_deploy ieadc_site_fechado; do wp --path=\"\$w\" option get \$o >/dev/null 2>&1 && echo \$o; done; true")" \
      || erro "não consegui consultar a produção por SSH (o WordPress de lá não respondeu); por segurança a carga completa foi recusada."
    [ -z "$(printf '%s' "$carga_previa" | tr -d '[:space:]')" ] \
      || erro "a produção já recebeu uma carga completa antes. Repetir apaga o que a equipe editou no painel. Para usar só o código, escolha o modo codigo; para substituir o conteúdo de propósito, a confirmação é SUBSTITUIR-CONTEUDO."
  fi

  info "Implantação: ambiente $AMBIENTE, carga $CARGA${MODO:+, modo $MODO}, commit ${DEPLOY_SHA:0:7}, pacote ${PACOTE_HASH:0:12}"
  registrar_deploy iniciado > /dev/null 2>&1 || true
  info "» 1/4 Envio do pacote"
  scp_alvo "$pacote" "$ALVO_USUARIO@$ALVO_SERVIDOR:ieadc-pacote-$AMBIENTE.tgz" >&2 || erro "o envio falhou (a chave está autorizada no hPanel?)."

  info "» 2/4 Instalação no servidor"
  remoto="rm -rf ~/ieadc-migracao-$AMBIENTE && mkdir ~/ieadc-migracao-$AMBIENTE && tar -xzf ~/ieadc-pacote-$AMBIENTE.tgz -C ~/ieadc-migracao-$AMBIENTE"
  if [ "$CARGA" = completo ]; then
    remoto="$remoto && bash ~/ieadc-migracao-$AMBIENTE/migrar-remoto.sh '$DESTINO' '$URL' '$prefixo' '$email' '$MODO' '$origens'"
  else
    remoto="$remoto && bash ~/ieadc-migracao-$AMBIENTE/atualizar-remoto.sh '$DESTINO' '$URL' '$prefixo'"
  fi
  remoto="$remoto; rm -rf ~/ieadc-migracao-$AMBIENTE ~/ieadc-pacote-$AMBIENTE.tgz"
  if ! ssh_alvo "$remoto" >&2; then
    registrar_deploy falhou > /dev/null 2>&1 || true
    erro "a instalação no servidor falhou. Se foi carga completa, o banco anterior ficou em ~/backup-antes-da-migracao-*.sql no servidor."
  fi

  # O ieadc_deploy só diz "ok" depois da fumaça: é ele que libera a produção. Se a fumaça falhar, fica marcado "falhou".
  info "» 3/4 Testes de fumaça"
  if ! cmd_fumaca; then
    registrar_deploy falhou > /dev/null 2>&1 || true
    erro "a fumaça falhou; a opção ieadc_deploy ficou marcada como falha e a produção não aceitará este commit."
  fi

  info "» 4/4 Registro da implantação (opção ieadc_deploy)"
  registrar_deploy ok || erro "não consegui gravar a opção ieadc_deploy."
  info "Implantação concluída: $URL"
}

# ---------------------------------------------------------------------------------------------------------------------
case "${1:-}" in
  empacotar) cmd_empacotar ;;
  enviar) shift; cmd_enviar "$@" ;;
  fumaca) cmd_fumaca || exit 1 ;;
  ler-deploy) cmd_ler_deploy ;;
  *) sed -n '2,/^set -euo/p' "$0" | sed '$d' >&2; exit 2 ;;
esac
