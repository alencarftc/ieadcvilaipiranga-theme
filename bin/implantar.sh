#!/usr/bin/env bash
# Implantação do site por SSH, para o GitHub Actions (runner Linux) e para o Git Bash. Veja docs/operacao/ci-cd.md.
#
# Subcomandos (as etapas são separadas porque a produção recebe o MESMO pacote que a homologação recebeu):
#   empacotar            monta o pacote .tgz a partir do site local no ar (docker compose): banco sem usuários, fotos,
#                        tema, mu-plugins e os scripts remotos. Saída em PACOTE_SAIDA.
#   enviar [pacote.tgz]  envia um pacote pronto, instala no servidor, grava a opção ieadc_deploy e roda a fumaça.
#   fumaca               só os testes de fumaça por HTTP (e a conferência das opções do site, por SSH).
#   ler-deploy           imprime a opção ieadc_deploy (JSON) do servidor; é a única coisa que sai na saída padrão.
#
# Variáveis de ambiente (nenhum valor é gravado em arquivo versionado):
#   ALVO_SERVIDOR, ALVO_PORTA, ALVO_USUARIO   dados do SSH (hPanel > Avançado > SSH)
#   ALVO_CHAVE        arquivo da chave privada SSH (sem senha, só para este fim)
#   ALVO_IMPRESSAO    impressão digital SHA256 da chave do servidor (SHA256:...). Se não bater, recusa e não conecta.
#   ALVO_URL          endereço final do site (https://...)
#   ALVO_DESTINO      pasta do site no servidor (padrão: domains/<endereço do site>/public_html)
#   ALVO_EMAIL_ADMIN  e-mail do administrador do site (padrão: não mexe)
#   CARGA             completo (padrão: código + banco, bin/migrar-remoto.sh) | codigo (só tema e mu-plugins, bin/atualizar-remoto.sh)
#   MODO              só na carga completa: homologacao | homologacao-fechada | ensaio | lancar (veja migrar-remoto.sh)
#   AMBIENTE          homologacao | producao (padrão: deduzido do MODO; obrigatório na carga "codigo")
#   CONFIRMACAO       só no ambiente producao: CARGA-INICIAL (carga completa) ou PRODUCAO (carga "codigo")
#   PACOTE_SAIDA      onde o "empacotar" grava o .tgz (padrão: content/backup/migracao/ieadc-<data>.tgz)
#   DEPLOY_SHA, DEPLOY_VERSAO, DEPLOY_RUN_ID   o que vai para a opção ieadc_deploy (padrões: SHA do pacote, "-", GITHUB_RUN_ID)
#   ESPERADO          aberto | fechado: força o que a fumaça espera (padrão: deduzido do MODO ou lido do servidor)
set -euo pipefail
cd "$(dirname "$0")/.."
export MSYS_NO_PATHCONV=1   # Git Bash no Windows não pode reescrever caminhos como /content/backup/...

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

definir_destino() {
  exigir ALVO_URL
  URL="${ALVO_URL%/}"
  local host="${URL#*://}"; host="${host%%/*}"; host="${host%%:*}"
  DESTINO="${ALVO_DESTINO:-domains/$host/public_html}"
  seguro URL; seguro DESTINO
}
# Lê uma opção do WordPress no servidor (vazio se não existir).
opcao_remota() { ssh_alvo "wp --path=\"\$HOME/$DESTINO\" option get $1 2>/dev/null || true" | tr -d '\r[:space:]'; }

cmd_ler_deploy() {
  definir_destino; preparar_ssh
  ssh_alvo "wp --path=\"\$HOME/$DESTINO\" option get ieadc_deploy" | tr -d '\r'
}

# ---------------------------------------------------------------------------------------------------------------------
cmd_fumaca() {
  definir_destino
  local base="$URL" falhas=0 esperado="${ESPERADO:-}" fechado="" publico="" estado
  if [ -z "$esperado" ] && { [ "${CARGA:-completo}" = "completo" ] && [ -n "${MODO:-}" ]; }; then
    case "$MODO" in homologacao|lancar) esperado=aberto ;; *) esperado=fechado ;; esac
  fi
  if [ -n "${ALVO_CHAVE:-}" ] && [ -n "${ALVO_IMPRESSAO:-}" ]; then
    [ "${#SSH_OPTS[@]}" -gt 0 ] || preparar_ssh
    fechado="$(opcao_remota ieadc_site_fechado)"; publico="$(opcao_remota blog_public)"
    info "Opções no servidor: ieadc_site_fechado=${fechado:-0} blog_public=${publico:-?}"
    if [ -z "$esperado" ]; then [ "${fechado:-0}" = "1" ] && esperado=fechado || esperado=aberto; fi
    if [ "${CARGA:-completo}" = "completo" ] && [ -n "${MODO:-}" ]; then
      case "$MODO" in
        lancar) { [ "${fechado:-0}" = "0" ] && [ "$publico" = "1" ]; } || { info "  FALHOU modo lancar: ieadc_site_fechado=${fechado:-0} e blog_public=$publico (esperado 0 e 1)"; falhas=$((falhas + 1)); } ;;
        homologacao) { [ "${fechado:-0}" = "0" ] && [ "$publico" = "0" ]; } || { info "  FALHOU modo homologacao: ieadc_site_fechado=${fechado:-0} e blog_public=$publico (esperado 0 e 0)"; falhas=$((falhas + 1)); } ;;
        *) { [ "${fechado:-0}" = "1" ] && [ "$publico" = "0" ]; } || { info "  FALHOU modo $MODO: ieadc_site_fechado=${fechado:-0} e blog_public=$publico (esperado 1 e 0)"; falhas=$((falhas + 1)); } ;;
      esac
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
    local cod
    cod="$(http "$base/sitemap_index.xml")"
    [ "$cod" = 200 ] || cod="$(http "$base/wp-sitemap.xml")"
    if [ "$cod" = 200 ]; then info "  ok      200 sitemap"; else info "  FALHOU  sitemap: nem /sitemap_index.xml nem /wp-sitemap.xml deram 200"; falhas=$((falhas + 1)); fi
  fi
  [ "$falhas" -eq 0 ] || erro "$falhas verificação(ões) da fumaça falharam em $base."
  info "Fumaça aprovada."
}

# ---------------------------------------------------------------------------------------------------------------------
cmd_enviar() {
  local pacote="${1:-${PACOTE_SAIDA:-}}" prefixo origens sha_pacote hash json quando remoto email
  [ -n "$pacote" ] && [ -f "$pacote" ] || erro "informe o pacote .tgz (bin/implantar.sh enviar <pacote.tgz>)."
  CARGA="${CARGA:-completo}"
  case "$CARGA" in
    completo) exigir MODO
      case "$MODO" in homologacao|homologacao-fechada|ensaio|lancar) ;; *) erro "MODO deve ser homologacao, homologacao-fechada, ensaio ou lancar." ;; esac
      AMBIENTE="${AMBIENTE:-$(case "$MODO" in homologacao*) echo homologacao ;; *) echo producao ;; esac)}" ;;
    codigo) exigir AMBIENTE ;;
    *) erro "CARGA deve ser completo ou codigo." ;;
  esac
  case "$AMBIENTE" in homologacao|producao) ;; *) erro "AMBIENTE deve ser homologacao ou producao." ;; esac
  # Na produção, o comando só passa com a confirmação certa (o workflow de release pede ao Felipe que a digite).
  if [ "$AMBIENTE" = producao ]; then
    local certa="PRODUCAO"
    if [ "$CARGA" = completo ]; then certa="CARGA-INICIAL"; fi
    [ "${CONFIRMACAO:-}" = "$certa" ] || erro "para a produção (carga $CARGA) a CONFIRMACAO deve ser $certa."
  fi
  definir_destino

  prefixo="$(meta "$pacote" PACOTE_PREFIXO)"; origens="$(meta "$pacote" PACOTE_ORIGENS)"; sha_pacote="$(meta "$pacote" PACOTE_SHA)"
  [ -n "$prefixo" ] && [ -n "$origens" ] || erro "o pacote não tem pacote.env (foi feito por bin/implantar.sh empacotar?)."
  seguro prefixo; seguro origens
  email="${ALVO_EMAIL_ADMIN:--}"; seguro email
  DEPLOY_SHA="${DEPLOY_SHA:-$sha_pacote}"
  if [ -n "$sha_pacote" ] && [ "$sha_pacote" != "desconhecido" ] && [ "$DEPLOY_SHA" != "$sha_pacote" ]; then
    erro "o pacote foi feito do commit $sha_pacote, mas a implantação é do commit $DEPLOY_SHA."
  fi
  hash="$(sha256sum "$pacote" | cut -d' ' -f1)"

  preparar_ssh
  info "Implantação: ambiente $AMBIENTE, carga $CARGA${MODO:+, modo $MODO}, commit ${DEPLOY_SHA:0:7}, pacote ${hash:0:12}"
  info "» 1/4 Envio do pacote"
  scp_alvo "$pacote" "$ALVO_USUARIO@$ALVO_SERVIDOR:ieadc-pacote.tgz" >&2 || erro "o envio falhou (a chave está autorizada no hPanel?)."

  info "» 2/4 Instalação no servidor"
  remoto="rm -rf ~/ieadc-migracao && mkdir ~/ieadc-migracao && tar -xzf ~/ieadc-pacote.tgz -C ~/ieadc-migracao"
  if [ "$CARGA" = completo ]; then
    remoto="$remoto && bash ~/ieadc-migracao/migrar-remoto.sh '$DESTINO' '$URL' '$prefixo' '$email' '$MODO' '$origens'"
  else
    remoto="$remoto && bash ~/ieadc-migracao/atualizar-remoto.sh '$DESTINO' '$URL' '$prefixo'"
  fi
  ssh_alvo "$remoto" >&2 || erro "a instalação no servidor falhou. Se foi carga completa, o banco anterior ficou em ~/backup-antes-da-migracao-*.sql no servidor."

  info "» 3/4 Registro da implantação (opção ieadc_deploy)"
  quando="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
  json="{\"sha\":\"$(limpo "$DEPLOY_SHA")\",\"ambiente\":\"$AMBIENTE\",\"versao\":\"$(limpo "${DEPLOY_VERSAO:--}")\",\"quando\":\"$quando\",\"run_id\":\"$(limpo "${DEPLOY_RUN_ID:-${GITHUB_RUN_ID:-local}}")\",\"carga\":\"$CARGA\",\"pacote\":\"$hash\"}"
  ssh_alvo "wp --path=\"\$HOME/$DESTINO\" option update ieadc_deploy '$json' --autoload=no" >&2 || erro "não consegui gravar a opção ieadc_deploy."

  info "» 4/4 Testes de fumaça"
  cmd_fumaca
  info "Implantação concluída: $URL"
}

# ---------------------------------------------------------------------------------------------------------------------
case "${1:-}" in
  empacotar) cmd_empacotar ;;
  enviar) shift; cmd_enviar "$@" ;;
  fumaca) cmd_fumaca ;;
  ler-deploy) cmd_ler_deploy ;;
  *) sed -n '2,25p' "$0" >&2; exit 2 ;;
esac
