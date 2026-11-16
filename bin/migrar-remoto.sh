#!/usr/bin/env bash
# Roda NO SERVIDOR da Hostinger (chamado por bin/migrar.ps1). Não rode no computador local.
# Uso: migrar-remoto.sh <pasta-do-site> <url> <prefixo-das-tabelas> <email-admin|-> <modo> <urls-locais>
# <modo>: homologacao (aberta, fora do Google) | homologacao-fechada | ensaio (produção fechada) | lancar (produção no ar)
# <urls-locais>: endereços do site local a trocar pelo final, separados por vírgula (o guardado no banco e o do .env).
set -euo pipefail

DEST="$1"; URL="${2%/}"; PREFIXO="$3"; EMAIL="$4"; MODO="$5"; ORIGENS="${6:-http://localhost:8080}"
PACOTE="$(cd "$(dirname "$0")" && pwd)"
SITE="$HOME/$DEST"

recusar() { echo "ERRO: $* Nada foi alterado."; exit 1; }

# Trava de pasta no servidor (repete a do bin/implantar.sh e do bin/atualizar-remoto.sh). A conta de hospedagem tem outras
# pastas em domains/ que nunca podem ser tocadas, e a homologação (hml) é uma SUBPASTA da produção: a comparação é sempre por
# IGUALDADE, nunca por prefixo. Pares aceitos:
#   hml.ieadcvilaipiranga.com.br   domains/ieadcvilaipiranga.com.br/public_html/hml   (e só ele para esse endereço e essa pasta)
#   *.hostingersite.com            domains/<endereço>/public_html                      (homologação de transição)
#   qualquer outro endereço        domains/ieadcvilaipiranga.com.br/public_html        (a produção; o ensaio local usa localhost)
# O bin/implantar.sh manda o domínio principal em DOMINIO_PRODUCAO (só o ensaio local usa outro).
DOMINIO="${DOMINIO_PRODUCAO:-ieadcvilaipiranga.com.br}"
[[ "$DOMINIO" =~ ^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$ ]] || recusar "o domínio principal é inválido."
PASTA_PROD="domains/$DOMINIO/public_html"; PASTA_HML="$PASTA_PROD/hml"
HOST="${URL#*://}"; HOST="${HOST%%/*}"; HOST="${HOST%%:*}"; HOST="$(printf '%s' "$HOST" | tr '[:upper:]' '[:lower:]')"
case "$DEST" in
  /*|*..*|*//*|*\\*|*[[:space:]]*|*~*) recusar "a pasta do site é inválida (não pode começar com /, ter .., // ou espaços)." ;;
esac
[[ "$HOST" =~ ^[a-z0-9-]+(\.[a-z0-9-]+)*$ ]] || recusar "o endereço do site é inválido."
if [ "$HOST" = "hml.$DOMINIO" ]; then
  [ "$DEST" = "$PASTA_HML" ] || recusar "o endereço hml.$DOMINIO só vale com a pasta $PASTA_HML."
elif [ "$DEST" = "$PASTA_HML" ]; then
  recusar "a pasta $PASTA_HML só vale com o endereço hml.$DOMINIO."
elif [ "$DEST" = "$PASTA_PROD" ]; then
  [[ "$HOST" != *.hostingersite.com ]] || recusar "o endereço $HOST não é o da pasta da produção."
elif [[ "$HOST" =~ ^([a-z0-9-]+\.)+hostingersite\.com$ ]] && [[ "$HOST" != *"$DOMINIO"* ]]; then
  [ "$DEST" = "domains/$HOST/public_html" ] || recusar "a pasta do site não corresponde ao endereço $HOST (esperado domains/$HOST/public_html)."
else
  recusar "a pasta $DEST não é uma das pastas deste site."
fi
# Rótulo das cópias de segurança: a homologação aninhada divide o $HOME com a produção e não pode sobrescrever as cópias dela.
ROTULO=""; [ "$DEST" = "$PASTA_HML" ] && ROTULO="hml-"
# O pacote e o site não podem se confundir (o fim do script apaga o pacote).
case "$SITE/" in "$PACOTE"/*) recusar "o pacote não pode ficar acima da pasta do site." ;; esac
[ "$PACOTE" != "$HOME" ] && [ "$PACOTE" != "/" ] || recusar "o pacote precisa estar em uma pasta própria."

[ -f "$SITE/wp-config.php" ] || { echo "ERRO: não achei o WordPress em $SITE. Instale o WordPress pelo hPanel (Sites > WordPress) e confira a pasta."; exit 1; }

# Barreiras entre a produção e a homologação aninhada (hml fica DENTRO da pasta da produção). Lê só o DB_NAME/DB_HOST do
# wp-config.php, sem executar nada. A carga completa importa um banco e troca endereços em TODAS as tabelas: se os dois
# sites dividissem o banco, uma carga numa atingiria a outra.
banco_de() { sed -n "s/^[[:space:]]*define([[:space:]]*['\"]$2['\"][[:space:]]*,[[:space:]]*['\"]\([^'\"]*\)['\"].*/\1/p" "$1" | head -n 1; }
mesmo_banco() {
  local a b
  a="$(banco_de "$1" DB_NAME)@$(banco_de "$1" DB_HOST)"; b="$(banco_de "$2" DB_NAME)@$(banco_de "$2" DB_HOST)"
  [ "$a" != "@" ] && [ "$a" = "$b" ]
}
if [ "$DEST" = "$PASTA_PROD" ] && [ -f "$SITE/hml/wp-config.php" ]; then
  echo "AVISO: há uma homologação (WordPress próprio) em ~/$DEST/hml. Ela NÃO será tocada por esta carga."
  ! mesmo_banco "$SITE/wp-config.php" "$SITE/hml/wp-config.php" || recusar "a produção e a homologação (hml) usam o mesmo banco de dados."
fi
if [ "$DEST" = "$PASTA_HML" ]; then
  # A homologação só escreve dentro da própria pasta: nada de link simbólico, de pasta que não esteja logo abaixo da produção
  # nem de banco igual ao da produção.
  [ -d "$SITE" ] && [ ! -L "$SITE" ] || recusar "a pasta da homologação (hml) não é uma pasta comum."
  [ "$(cd "$SITE" && pwd -P)" = "$(cd "$HOME/$PASTA_PROD" && pwd -P)/hml" ] || recusar "a pasta hml não está logo abaixo da pasta da produção."
  ! { [ -f "$SITE/../wp-config.php" ] && mesmo_banco "$SITE/../wp-config.php" "$SITE/wp-config.php"; } || recusar "a homologação (hml) usa o mesmo banco de dados da produção."
fi
cd "$SITE"
# Hospedagens como a da Hostinger desativam proc_open() no PHP de linha de comando, e o wp-cli precisa dele (db export e
# db import). O -d disable_functions= religa as funções só para este processo.
WPBIN="$(command -v wp)" || { echo "ERRO: não achei o wp-cli neste servidor."; exit 1; }
if php -r 'exit(function_exists("proc_open") ? 0 : 1);'; then
  WP="$WPBIN --path=$SITE"
else
  WP="php -d disable_functions= $WPBIN --path=$SITE"
fi

atual="$($WP config get table_prefix)"
[ "$atual" = "$PREFIXO" ] || { echo "ERRO: o prefixo das tabelas aqui é '$atual' e o local é '$PREFIXO'. Reinstale o WordPress com prefixo '$PREFIXO' ou avise a Comunicação."; exit 1; }

echo "1/8 Cópia de segurança do banco atual"
BACKUP="$HOME/backup-antes-da-migracao-${ROTULO}$(date +%Y-%m-%d-%H%M).sql"
$WP db export "$BACKUP"
echo "    guardada em $BACKUP"

# O modo manutenção do WordPress (.maintenance) expira sozinho em 10 minutos: serve só para a cópia abaixo.
# O site fica fechado de verdade pela opção ieadc_site_fechado (mu-plugin ieadc-core), ligada logo depois do banco.
echo "2/8 Modo manutenção ligado (só durante a cópia)"
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
# O banco local vem com o site aberto: fecha já (visitantes veem "em preparação"; quem tem login vê o site).
$WP option update ieadc_site_fechado 1

echo "5/8 Endereços: $ORIGENS -> $URL"
IFS=',' read -r -a LISTA <<< "$ORIGENS"
for ORIGEM in "${LISTA[@]}"; do
  ORIGEM="${ORIGEM%/}"
  { [ -n "$ORIGEM" ] && [ "$ORIGEM" != "$URL" ]; } || continue
  $WP search-replace "$ORIGEM" "$URL" --all-tables --skip-columns=guid --report-changed-only
  # versão com barras escapadas (JSON dentro dos blocos do editor)
  $WP search-replace "${ORIGEM//\//\\/}" "${URL//\//\\/}" --all-tables --skip-columns=guid --report-changed-only
done
# Garantia: o site local pode ter o endereço de fora do banco (WP_HOME no .env) e o banco guardar outro.
$WP option update home "$URL"
$WP option update siteurl "$URL"

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
# O wp-cli não regrava o .htaccess sozinho ("rewrite flush --hard" só avisa): sem as regras dos links limpos,
# toda página que não é a inicial dá 404. As funções do painel e o filtro got_rewrite fazem o WordPress gravá-las.
$WP eval 'require_once ABSPATH . "wp-admin/includes/file.php"; require_once ABSPATH . "wp-admin/includes/misc.php"; add_filter("got_rewrite", "__return_true"); flush_rewrite_rules(true);'
grep -q RewriteRule "$SITE/.htaccess" 2>/dev/null || echo "AVISO: o .htaccess ficou sem as regras dos links limpos; abra Configurações > Links permanentes no painel e salve."
$WP cache flush || true
$WP litespeed-purge all 2>/dev/null || true
$WP maintenance-mode deactivate || true
case "$MODO" in
  lancar)
    $WP option update ieadc_site_fechado 0
    $WP option update blog_public 1
    echo "SITE NO AR e liberado para o Google: $URL"
    ;;
  homologacao)
    # Para o pastor e a equipe validarem: aberto para quem tem o link, mas fora do Google (blog_public=0 = noindex e robots.txt).
    $WP option update ieadc_site_fechado 0
    $WP option update blog_public 0
    echo "HOMOLOGAÇÃO no ar, aberta para quem tem o link e fora do Google: $URL"
    ;;
  *)
    # ensaio da produção e homologacao-fechada: só quem tem login vê o site.
    $WP option update blog_public 0
    echo "O site está FECHADO para visitantes (página \"em preparação\") e fora do Google: $URL"
    echo "Quem tem login enxerga tudo: entre em $URL/wp-login.php."
    ;;
esac
rm -rf "$PACOTE" "$HOME/ieadc-pacote.tgz"
