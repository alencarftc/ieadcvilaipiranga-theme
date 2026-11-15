#!/usr/bin/env bash
# Instala e configura o WordPress local do zero (pode rodar de novo sem estragar nada).
# Uso: ./bin/setup.sh
set -euo pipefail
cd "$(dirname "$0")/.."
[ -f .env ] || { echo "Crie o .env a partir do .env.example primeiro."; exit 1; }
set -a; . ./.env; set +a

docker compose up -d db wordpress mailpit
wp() { docker compose run --rm -T wpcli wp "$@"; }

echo "Esperando o WordPress subir..."
for i in $(seq 1 60); do
  docker compose exec -T wordpress test -f /var/www/html/wp-config.php && break
  sleep 2
done

if ! wp core is-installed 2>/dev/null; then
  wp core install --url="$SITE_URL" --title="IEADC Vila Ipiranga" \
    --admin_user="$ADMIN_USUARIO" --admin_password="$ADMIN_SENHA" --admin_email="$ADMIN_EMAIL" --skip-email
fi

# Idioma, fuso, links e comentários
wp language core install pt_BR --activate || true
wp option update timezone_string "America/Sao_Paulo"
wp option update date_format "j \\d\\e F \\d\\e Y"
wp option update time_format "H:i"
wp option update blogdescription "Congregação da Igreja Evangélica Assembleia de Deus em Curitiba"
wp rewrite structure "/%postname%/" --hard
wp option update default_comment_status closed
wp option update default_ping_status closed
wp option update blog_public 0   # local: não indexar

# Tema: Blocksy (pai, do repositório oficial) + tema filho deste repositório
wp theme is-installed blocksy || wp theme install blocksy
wp theme activate ieadc-vila-ipiranga
wp theme delete twentytwentythree twentytwentyfour twentytwentyfive 2>/dev/null || true

# Plugins (poucos). Os de produção ficam instalados, mas desligados no local.
wp plugin delete akismet hello 2>/dev/null || true
wp plugin install wordpress-seo two-factor limit-login-attempts-reloaded --activate
wp plugin install litespeed-cache updraftplus
wp language plugin install pt_BR --all || true
wp language theme install pt_BR --all || true

# Páginas, menu e configurações de leitura
wp eval-file /scripts/seed.php

wp rewrite flush --hard
echo
echo "Pronto: $SITE_URL  (painel: $SITE_URL/wp-admin)"
echo "E-mails dos formulários: http://localhost:8025"
