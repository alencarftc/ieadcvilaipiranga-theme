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

recusar() { echo "ERRO: $* Nada foi alterado."; exit 1; }

# Trava de pasta (a mesma do bin/implantar.sh): a conta de hospedagem tem outras pastas em domains/ que nunca podem ser
# tocadas, e a homologação (hml) é uma SUBPASTA da produção. A pasta tem de ser EXATAMENTE a do par do endereço
# (comparação por igualdade, nunca por prefixo), sem .., // nem / inicial:
#   ieadcvilaipiranga.com.br (ou www.)  domains/ieadcvilaipiranga.com.br/public_html
#   hml.ieadcvilaipiranga.com.br        domains/ieadcvilaipiranga.com.br/public_html/hml
#   *.hostingersite.com (transição)     domains/<endereço>/public_html
# O bin/implantar.sh manda o domínio principal em DOMINIO_PRODUCAO (só o ensaio local usa outro).
DOMINIO="${DOMINIO_PRODUCAO:-ieadcvilaipiranga.com.br}"
[[ "$DOMINIO" =~ ^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$ ]] || recusar "o domínio principal é inválido."
PASTA_PROD="domains/$DOMINIO/public_html"; PASTA_HML="$PASTA_PROD/hml"
HOST="${URL#*://}"; HOST="${HOST%%/*}"; HOST="${HOST%%:*}"; HOST="$(printf '%s' "$HOST" | tr '[:upper:]' '[:lower:]')"
case "$DEST" in
  /*|*..*|*//*|*\\*|*[[:space:]]*|*~*) recusar "a pasta do site é inválida (não pode começar com /, ter .., // ou espaços)." ;;
esac
[[ "$HOST" =~ ^[a-z0-9-]+(\.[a-z0-9-]+)*$ ]] || recusar "o endereço do site é inválido."
if [ "$HOST" = "hml.$DOMINIO" ]; then ESPERADA="$PASTA_HML"
elif [ "$HOST" = "$DOMINIO" ] || [ "$HOST" = "www.$DOMINIO" ]; then ESPERADA="$PASTA_PROD"
elif [[ "$HOST" =~ ^([a-z0-9-]+\.)+hostingersite\.com$ ]] && [[ "$HOST" != *"$DOMINIO"* ]]; then ESPERADA="domains/$HOST/public_html"
else recusar "o endereço $HOST não é de um ambiente deste site."
fi
[ "$DEST" = "$ESPERADA" ] || recusar "a pasta do site não corresponde ao endereço $HOST (esperado $ESPERADA)."
# Rótulo das cópias de segurança: a homologação aninhada divide o $HOME com a produção e não pode apagar as cópias dela.
ROTULO=""; [ "$DEST" = "$PASTA_HML" ] && ROTULO="hml-"
# O pacote e o site não podem se confundir (o fim do script apaga o pacote).
case "$SITE/" in "$PACOTE"/*) recusar "o pacote não pode ficar acima da pasta do site." ;; esac
[ "$PACOTE" != "$HOME" ] && [ "$PACOTE" != "/" ] || recusar "o pacote precisa estar em uma pasta própria."

[ -f "$SITE/wp-config.php" ] || { echo "ERRO: não achei o WordPress em ~/$DEST. Confira a pasta do site no hPanel."; exit 1; }

# Barreiras entre a produção e a homologação aninhada (hml fica DENTRO da pasta da produção).
# Lê só o DB_NAME/DB_HOST do wp-config.php, sem executar nada.
banco_de() { sed -n "s/^[[:space:]]*define([[:space:]]*['\"]$2['\"][[:space:]]*,[[:space:]]*['\"]\([^'\"]*\)['\"].*/\1/p" "$1" | head -n 1; }
mesmo_banco() {
  local a b
  a="$(banco_de "$1" DB_NAME)@$(banco_de "$1" DB_HOST)"; b="$(banco_de "$2" DB_NAME)@$(banco_de "$2" DB_HOST)"
  [ "$a" != "@" ] && [ "$a" = "$b" ]
}
if [ "$DEST" = "$PASTA_PROD" ] && [ -f "$SITE/hml/wp-config.php" ]; then
  echo "AVISO: há uma homologação (WordPress próprio) em ~/$DEST/hml. Ela NÃO será tocada por esta atualização."
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
BACKUP="$HOME/backup-codigo-antes-${ROTULO}$(date +%Y-%m-%d-%H%M%S).tgz"
LISTA=()
[ -d wp-content/themes/ieadc-vila-ipiranga ] && LISTA+=(themes/ieadc-vila-ipiranga)
[ -d wp-content/mu-plugins ] && LISTA+=(mu-plugins)
if [ "${#LISTA[@]}" -gt 0 ]; then
  tar -czf "$BACKUP" -C wp-content "${LISTA[@]}"
  echo "    guardada em ~/${BACKUP#"$HOME"/}"
fi
# Guarda só as 5 cópias de código mais recentes DESTE site (cada uma tem cerca de 1 MB). As da homologação (hml-) e as da
# produção ficam separadas: o padrão exige um algarismo logo depois do rótulo, então a produção nunca apaga as de hml- e vice-versa.
# shellcheck disable=SC2012
ls -1t "$HOME"/backup-codigo-antes-"${ROTULO}"[0-9]*.tgz 2>/dev/null | tail -n +6 | while IFS= read -r velho; do rm -f "$velho"; done

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
# /favicon.ico físico na raiz: a hospedagem (LiteSpeed) responde esse caminho antes do PHP e dava 404 (o rewrite do tema nunca era chamado).
# Copia o PNG de 32 px do tema (o navegador aceita PNG em .ico); só sobrescreve se for diferente. Falha aqui não derruba a implantação.
ICONE="$SITE/wp-content/themes/ieadc-vila-ipiranga/assets/img/icones/icone-32.png"
if [ -f "$ICONE" ] && ! cmp -s "$ICONE" "$SITE/favicon.ico" 2>/dev/null; then
  cp "$ICONE" "$SITE/favicon.ico" && echo "    favicon.ico copiado para a raiz do site" || echo "AVISO: não consegui copiar o favicon.ico para a raiz do site."
fi
$WP cache flush || true
$WP litespeed-purge all 2>/dev/null || true

echo "4/4 Conferência"
$WP theme is-active ieadc-vila-ipiranga || { echo "ERRO: o tema ieadc-vila-ipiranga não está ativo neste site. O código foi copiado, mas a ativação é da carga completa."; exit 1; }
echo "CÓDIGO ATUALIZADO em $URL (o conteúdo e o estado do site, aberto ou fechado, ficaram como estavam)."
rm -rf "$PACOTE" "$HOME/ieadc-pacote.tgz"
