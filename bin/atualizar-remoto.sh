#!/usr/bin/env bash
# Roda NO SERVIDOR (chamado por bin/implantar.sh com CARGA=codigo). Não rode no computador local.
# Atualiza só o CÓDIGO do site (tema e mu-plugins). Não toca no banco de conteúdo (páginas, artigos, eventos, opções,
# usuários), nas fotos enviadas pelo painel, nem no estado do site (aberto ou fechado): serve para quando a produção já
# tem conteúdo editado no painel e uma carga completa (bin/migrar-remoto.sh) apagaria o trabalho da equipe.
# Uso: atualizar-remoto.sh <pasta-do-site> <url> <prefixo-das-tabelas>
set -euo pipefail

DEST="$1"; URL="${2%/}"; PREFIXO="$3"
PACOTE="$(cd "$(dirname "$0")" && pwd)"
SITE="$HOME/$DEST"

# Trava de pasta (a mesma do bin/implantar.sh): a conta de hospedagem tem outras pastas em domains/ que nunca podem ser
# tocadas. A pasta tem de ser exatamente domains/<domínio da URL>/public_html (sem www.), sem .., // nem / inicial.
HOST="${URL#*://}"; HOST="${HOST%%/*}"; HOST="${HOST%%:*}"; HOST="$(printf '%s' "$HOST" | tr '[:upper:]' '[:lower:]')"
case "$DEST" in
  /*|*..*|*//*|*\\*|*[[:space:]]*|*~*) echo "ERRO: a pasta do site é inválida (não pode começar com /, ter .., // ou espaços). Nada foi alterado."; exit 1 ;;
esac
[[ "$HOST" =~ ^[a-z0-9-]+(\.[a-z0-9-]+)*$ ]] || { echo "ERRO: o endereço do site é inválido. Nada foi alterado."; exit 1; }
if [ "$DEST" != "domains/$HOST/public_html" ] && [ "$DEST" != "domains/${HOST#www.}/public_html" ]; then
  echo "ERRO: a pasta do site não corresponde ao endereço $HOST (esperado domains/${HOST#www.}/public_html). Nada foi alterado."; exit 1
fi

[ -f "$SITE/wp-config.php" ] || { echo "ERRO: não achei o WordPress em ~/$DEST. Confira a pasta do site no hPanel."; exit 1; }
cd "$SITE"
# Hospedagens como a da Hostinger desativam proc_open() no PHP de linha de comando; o -d religa só para este processo
# (mesmo cuidado do bin/migrar-remoto.sh).
WPBIN="$(command -v wp)" || { echo "ERRO: não achei o wp-cli neste servidor."; exit 1; }
if php -r 'exit(function_exists("proc_open") ? 0 : 1);'; then
  WP="$WPBIN --path=$SITE"
else
  WP="php -d disable_functions= $WPBIN --path=$SITE"
fi

atual="$($WP config get table_prefix)"
[ "$atual" = "$PREFIXO" ] || { echo "ERRO: o prefixo das tabelas aqui é '$atual' e o do pacote é '$PREFIXO'. Nada foi alterado."; exit 1; }

echo "1/4 Cópia do código atual (para voltar atrás)"
BACKUP="$HOME/backup-codigo-antes-$(date +%Y-%m-%d-%H%M%S).tgz"
LISTA=()
[ -d wp-content/themes/ieadc-vila-ipiranga ] && LISTA+=(themes/ieadc-vila-ipiranga)
[ -d wp-content/mu-plugins ] && LISTA+=(mu-plugins)
if [ "${#LISTA[@]}" -gt 0 ]; then
  tar -czf "$BACKUP" -C wp-content "${LISTA[@]}"
  echo "    guardada em ~/${BACKUP#"$HOME"/}"
fi
# Guarda só as 5 cópias de código mais recentes (cada uma tem cerca de 1 MB).
# shellcheck disable=SC2012
ls -1t "$HOME"/backup-codigo-antes-*.tgz 2>/dev/null | tail -n +6 | while IFS= read -r velho; do rm -f "$velho"; done

echo "2/4 Tema e mu-plugins"
mkdir -p wp-content/themes wp-content/mu-plugins
# O tema novo é copiado ao lado e só então troca de lugar: o intervalo sem tema é de milissegundos.
rm -rf wp-content/themes/.ieadc-novo
cp -a "$PACOTE/wp-content/themes/ieadc-vila-ipiranga" wp-content/themes/.ieadc-novo
rm -rf wp-content/themes/ieadc-vila-ipiranga
mv wp-content/themes/.ieadc-novo wp-content/themes/ieadc-vila-ipiranga
rm -rf wp-content/mu-plugins/ieadc-core wp-content/mu-plugins/ieadc-core.php
cp -a "$PACOTE/wp-content/mu-plugins/." wp-content/mu-plugins/

echo "3/4 Links permanentes e cache"
# Mesma gravação do .htaccess da carga completa (o wp-cli sozinho não grava as regras dos links limpos).
$WP eval 'require_once ABSPATH . "wp-admin/includes/file.php"; require_once ABSPATH . "wp-admin/includes/misc.php"; add_filter("got_rewrite", "__return_true"); flush_rewrite_rules(true);'
grep -q RewriteRule "$SITE/.htaccess" 2>/dev/null || echo "AVISO: o .htaccess ficou sem as regras dos links limpos; abra Configurações > Links permanentes no painel e salve."
$WP cache flush || true
$WP litespeed-purge all 2>/dev/null || true

echo "4/4 Conferência"
$WP theme is-active ieadc-vila-ipiranga || { echo "ERRO: o tema ieadc-vila-ipiranga não está ativo neste site. O código foi copiado, mas a ativação é da carga completa."; exit 1; }
echo "CÓDIGO ATUALIZADO em $URL (o conteúdo e o estado do site, aberto ou fechado, ficaram como estavam)."
rm -rf "$PACOTE" "$HOME/ieadc-pacote.tgz"
