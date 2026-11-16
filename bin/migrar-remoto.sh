#!/usr/bin/env bash
# Roda NO SERVIDOR da Hostinger (chamado por bin/migrar.ps1). Não rode no computador local.
# Uso: migrar-remoto.sh <pasta-do-site> <url> <prefixo-das-tabelas> <email-admin|-> <ensaio|lancar>
set -euo pipefail

DEST="$1"; URL="${2%/}"; PREFIXO="$3"; EMAIL="$4"; MODO="$5"
PACOTE="$(cd "$(dirname "$0")" && pwd)"
SITE="$HOME/$DEST"

[ -f "$SITE/wp-config.php" ] || { echo "ERRO: não achei o WordPress em $SITE. Instale o WordPress pelo hPanel (Sites > WordPress) e confira a pasta."; exit 1; }
cd "$SITE"
WP="wp --path=$SITE"

atual="$($WP config get table_prefix)"
[ "$atual" = "$PREFIXO" ] || { echo "ERRO: o prefixo das tabelas aqui é '$atual' e o local é '$PREFIXO'. Reinstale o WordPress com prefixo '$PREFIXO' ou avise a Comunicação."; exit 1; }

echo "1/8 Cópia de segurança do banco atual"
BACKUP="$HOME/backup-antes-da-migracao-$(date +%Y-%m-%d-%H%M).sql"
$WP db export "$BACKUP"
echo "    guardada em $BACKUP"

echo "2/8 Modo manutenção ligado"
$WP maintenance-mode activate || true

echo "3/8 Arquivos (tema, mu-plugins, fotos)"
rm -rf wp-content/themes/ieadc-vila-ipiranga
cp -a "$PACOTE/wp-content/themes/ieadc-vila-ipiranga" wp-content/themes/
mkdir -p wp-content/mu-plugins wp-content/uploads
rm -rf wp-content/mu-plugins/ieadc-core wp-content/mu-plugins/ieadc-core.php
cp -a "$PACOTE/wp-content/mu-plugins/." wp-content/mu-plugins/
cp -a "$PACOTE/wp-content/uploads/." wp-content/uploads/

echo "4/8 Banco (sem usuários: a conta de administrador daqui continua a mesma)"
$WP db import "$PACOTE/banco.sql"

echo "5/8 Endereços: localhost -> $URL"
$WP search-replace "http://localhost:8080" "$URL" --all-tables --skip-columns=guid --report-changed-only

echo "6/8 Limpeza do que é de teste e autoria dos conteúdos"
ids="$($WP post list --post_type=pedido_oracao --post_status=any --format=ids)"
[ -n "$ids" ] && $WP post delete $ids --force || true
ADMIN="$($WP user list --role=administrator --field=ID | head -1)"
[ -n "$ADMIN" ] && $WP db query "UPDATE ${PREFIXO}posts SET post_author=$ADMIN WHERE post_author=1 OR post_author NOT IN (SELECT ID FROM ${PREFIXO}users)"

echo "7/8 Tema pai, tema do site e plugins"
$WP theme is-installed blocksy || $WP theme install blocksy
$WP theme activate ieadc-vila-ipiranga
$WP language core install pt_BR || true
$WP site switch-language pt_BR || true
$WP plugin install wordpress-seo two-factor limit-login-attempts-reloaded litespeed-cache updraftplus --activate \
  || echo "AVISO: algum plugin não instalou; instale pelo painel (Plugins > Adicionar novo)."
[ "$EMAIL" != "-" ] && $WP option update admin_email "$EMAIL" --skip-plugins || true

echo "8/8 Finalização ($MODO)"
$WP rewrite flush --hard
$WP cache flush || true
$WP litespeed-purge all 2>/dev/null || true
if [ "$MODO" = "lancar" ]; then
  $WP option update blog_public 1
  $WP maintenance-mode deactivate
  echo "SITE NO AR e liberado para o Google: $URL"
else
  $WP option update blog_public 0
  echo "ENSAIO concluído. O site continua em manutenção e fora do Google: $URL"
  echo "Para ver como administrador, entre em $URL/wp-login.php."
fi
rm -rf "$PACOTE" "$HOME/ieadc-pacote.tgz"
