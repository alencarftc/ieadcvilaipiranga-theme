#!/usr/bin/env bash
# Roda NO SERVIDOR da Hostinger (chamado por bin/migrar.ps1). Não rode no computador local.
# Uso: migrar-remoto.sh <pasta-do-site> <url> <prefixo-das-tabelas> <email-admin|-> <modo> <urls-locais>
# <modo>: homologacao (aberta, fora do Google) | homologacao-fechada | ensaio (produção fechada) | lancar (produção no ar)
# <urls-locais>: endereços do site local a trocar pelo final, separados por vírgula (o guardado no banco e o do .env).
set -euo pipefail

# O endereço gravado em home/siteurl é sempre minúsculo.
DEST="$1"; URL="$(printf '%s' "${2%/}" | tr '[:upper:]' '[:lower:]')"; PREFIXO="$3"; EMAIL="$4"; MODO="$5"; ORIGENS="${6:-http://localhost:8080}"
PACOTE="$(cd "$(dirname "$0")" && pwd)"
SITE="$HOME/$DEST"

recusar() { echo "ERRO: $* Nada foi alterado."; exit 1; }

# Trava de pasta no servidor (repete a do bin/implantar.sh e do bin/atualizar-remoto.sh). A conta de hospedagem tem outras
# pastas em domains/ que nunca podem ser tocadas, e a homologação (hml) é uma SUBPASTA da produção: a comparação é sempre por
# IGUALDADE, nunca por prefixo. Pares aceitos:
#   ieadcvilaipiranga.com.br (ou www.)  domains/ieadcvilaipiranga.com.br/public_html       (a produção; o ensaio local do caminho manual usa localhost)
#   hml.ieadcvilaipiranga.com.br        domains/ieadcvilaipiranga.com.br/public_html/hml   (e só ele para esse endereço e essa pasta)
#   *.hostingersite.com                 domains/<endereço>/public_html                      (homologação de transição)
# O bin/implantar.sh manda o domínio principal em DOMINIO_PRODUCAO (só o ensaio local usa outro).
DOMINIO="${DOMINIO_PRODUCAO:-ieadcvilaipiranga.com.br}"
[[ "$DOMINIO" =~ ^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$ ]] || recusar "o domínio principal é inválido."
PASTA_PROD="domains/$DOMINIO/public_html"; PASTA_HML="$PASTA_PROD/hml"
HOST="${URL#*://}"; HOST="${HOST%%/*}"; HOST="${HOST%%:*}"; HOST="$(printf '%s' "$HOST" | tr '[:upper:]' '[:lower:]')"
case "$DEST" in
  /*|*..*|*//*|*\\*|*[[:space:]]*|*~*) recusar "a pasta do site é inválida (não pode começar com /, ter .., // ou espaços)." ;;
esac
[[ "$HOST" =~ ^[a-z0-9-]+(\.[a-z0-9-]+)*$ ]] || recusar "o endereço do site é inválido."
# Produção: o domínio principal (com ou sem www.) e, só no ensaio local do caminho manual (bin/ensaio-local.ps1 usa -Servidor
# 127.0.0.1), localhost e 127.0.0.1. Nenhum outro endereço, nem o de outro site da conta, chega à pasta da produção.
if [ "$HOST" = "hml.$DOMINIO" ]; then ESPERADA="$PASTA_HML"
elif [ "$HOST" = "$DOMINIO" ] || [ "$HOST" = "www.$DOMINIO" ] || [ "$HOST" = localhost ] || [ "$HOST" = 127.0.0.1 ]; then ESPERADA="$PASTA_PROD"
elif [[ "$HOST" =~ ^([a-z0-9-]+\.)+hostingersite\.com$ ]] && [[ "$HOST" != *"$DOMINIO"* ]]; then ESPERADA="domains/$HOST/public_html"
else recusar "o endereço $HOST não é de um ambiente deste site."
fi
[ "$DEST" = "$ESPERADA" ] || recusar "a pasta do site não corresponde ao endereço $HOST (esperado $ESPERADA)."
# Rótulo das cópias de segurança: tudo o que não é a produção (a hml aninhada e a homologação de transição) leva hml-, porque
# divide o $HOME com a produção e não pode apagar nem sobrescrever as cópias dela.
ROTULO=""; [ "$DEST" != "$PASTA_PROD" ] && ROTULO="hml-"
# O pacote e o site não podem se confundir (o fim do script apaga o pacote).
case "$SITE/" in "$PACOTE"/*) recusar "o pacote não pode ficar acima da pasta do site." ;; esac
[ "$PACOTE" != "$HOME" ] && [ "$PACOTE" != "/" ] || recusar "o pacote precisa estar em uma pasta própria."

[ -f "$SITE/wp-config.php" ] || { echo "ERRO: não achei o WordPress em $SITE. Instale o WordPress pelo hPanel (Sites > WordPress) e confira a pasta."; exit 1; }

# Barreiras entre a produção e a homologação aninhada (hml fica DENTRO da pasta da produção). Todas FALHAM FECHADAS: o que não
# der para conferir é recusado, nunca liberado.
# 1) Banco: lê só o DB_NAME literal do wp-config.php (uma única definição, fora de comentários), sem executar nada. A carga completa
#    importa um banco e troca endereços em TODAS as tabelas: se os dois sites dividissem o banco, uma carga numa atingiria a outra.
#    Se o DB_NAME de qualquer um dos dois não puder ser lido (getenv, constante montada, ausência, mais de uma definição), recusa.
nome_banco() {
  local n
  n="$(grep -vE '^[[:space:]]*(//|#|\*|/\*)' "$1" 2> /dev/null | grep -oE "define\([[:space:]]*['\"]DB_NAME['\"][[:space:]]*,[[:space:]]*['\"][^'\"]+['\"]" | sed -E "s/.*,[[:space:]]*['\"]([^'\"]+)['\"]$/\1/")" || true
  [ -n "$n" ] && [ "$(printf '%s\n' "$n" | wc -l)" -eq 1 ] && printf '%s' "$n"
}
bancos_diferentes() {   # bancos_diferentes <wp-config da produção> <wp-config da hml>
  local a b
  a="$(nome_banco "$1")" || recusar "não consegui ler o DB_NAME de $1 (precisa de um define('DB_NAME', '...') literal); por segurança não sigo sem comparar o banco da produção com o da hml."
  b="$(nome_banco "$2")" || recusar "não consegui ler o DB_NAME de $2 (precisa de um define('DB_NAME', '...') literal); por segurança não sigo sem comparar o banco da produção com o da hml."
  [ "$a" != "$b" ] || recusar "a produção e a homologação (hml) usam o mesmo banco de dados ($a)."
}
if [ "$DEST" = "$PASTA_PROD" ] && [ -f "$SITE/hml/wp-config.php" ]; then
  echo "AVISO: há uma homologação (WordPress próprio) em ~/$DEST/hml. Ela NÃO será tocada por esta operação."
  bancos_diferentes "$SITE/wp-config.php" "$SITE/hml/wp-config.php"
fi
# 2) A hml só escreve dentro da própria pasta: nada de link simbólico, de pasta que não esteja logo abaixo da produção nem de banco
#    igual ao da produção (o wp-config da produção, no pai, precisa existir e ser legível).
if [ "$DEST" = "$PASTA_HML" ]; then
  [ -d "$SITE" ] && [ ! -L "$SITE" ] || recusar "a pasta da homologação (hml) não é uma pasta comum."
  [ "$(cd "$SITE" && pwd -P)" = "$(cd "$HOME/$PASTA_PROD" && pwd -P)/hml" ] || recusar "a pasta hml não está logo abaixo da pasta da produção."
  [ -f "$SITE/../wp-config.php" ] || recusar "não achei o wp-config.php da produção logo acima da hml; sem ele não dá para conferir que os bancos são diferentes."
  bancos_diferentes "$SITE/../wp-config.php" "$SITE/wp-config.php"
fi
# 3) Link simbólico dentro do site (até 3 níveis: wp-content, themes, mu-plugins, uploads, plugins...) faria o rm -rf, o cp -a e o
#    wp-cli escreverem FORA da pasta (na hml, por exemplo, em cima da produção). Na produção a subpasta hml não é do site dela e
#    fica de fora da busca. O -H segue só o link da própria pasta do site (na Hostinger e no servidor de teste ela pode ser um).
LINKS="$(find -H "$SITE" -maxdepth 3 -path "$SITE/hml" -prune -o -type l -print 2> /dev/null | head -n 3)"
[ -z "$LINKS" ] || recusar "há link simbólico dentro do site ($(printf '%s' "$LINKS" | tr '\n' ' ')): uma carga escreveria fora da pasta. Remova o link (ou avise a Comunicação) e tente de novo."
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
# A homologação recebe uma carga completa a cada commit: guarda só as 5 cópias de banco mais recentes dela (rótulo hml-). As da
# produção (sem rótulo) nunca são apagadas por este script: o padrão exige o rótulo e um algarismo logo depois.
if [ -n "$ROTULO" ]; then
  # shellcheck disable=SC2012
  ls -1t "$HOME"/backup-antes-da-migracao-"${ROTULO}"[0-9]*.sql 2> /dev/null | tail -n +6 | while IFS= read -r velho; do rm -f "$velho"; done
fi

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
# /favicon.ico físico na raiz: a hospedagem (LiteSpeed) responde esse caminho antes do PHP e dava 404 (o rewrite do tema nunca era chamado).
# Copia o PNG de 32 px do tema (o navegador aceita PNG em .ico); só sobrescreve se for diferente. Falha aqui não derruba a implantação.
ICONE="$SITE/wp-content/themes/ieadc-vila-ipiranga/assets/img/icones/icone-32.png"
if [ -f "$ICONE" ] && ! cmp -s "$ICONE" "$SITE/favicon.ico" 2>/dev/null; then
  cp "$ICONE" "$SITE/favicon.ico" && echo "    favicon.ico copiado para a raiz do site" || echo "AVISO: não consegui copiar o favicon.ico para a raiz do site."
fi
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
