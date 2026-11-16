#!/usr/bin/env bash
# Mede o site com o Lighthouse (desempenho, acessibilidade, boas práticas e SEO), no celular e no computador,
# e sai com erro se alguma nota ficar abaixo de LH_MIN (padrão 100), listando as auditorias que falharam.
#
# Uso: ./bin/lighthouse.sh [pagina ...]      (sem argumentos: as páginas-chave abaixo; "pagina" é o caminho, ex.: / ou /contato/)
# Variáveis:
#   LH_URL_BASE   site a medir (padrão: SITE_URL do .env ou http://localhost:8080; para a homologação, o endereço dela)
#   LH_MIN        nota mínima de 0 a 100 (padrão 100)
#   LH_DISPOSITIVOS  "mobile desktop" (padrão), ou só um deles
#   IEADC_CHROMIUM   caminho do Chromium/Chrome (vira CHROME_PATH); sem ele o Lighthouse procura o Chrome instalado
#   LH_VERSAO     versão do Lighthouse (padrão 12.8.2: a 13 exige Node 22.19 ou mais)
# Relatórios (JSON e HTML) em tests/lighthouse/saida/<data-hora>/.
# Local: o site precisa estar no ar (./bin/setup.sh) e indexável (Configurações > Leitura sem "Pedir aos mecanismos de busca..."):
# na homologação o SEO fica em 66 de propósito (noindex); na produção deve ir a 100.
set -uo pipefail
cd "$(dirname "$0")/.."
export MSYS_NO_PATHCONV=1

if [ -z "${LH_URL_BASE:-}" ] && [ -f .env ]; then
  LH_URL_BASE="$(sed -n 's/^SITE_URL=//p' .env | head -1 | tr -d '\r "'"'")"
fi
base="${LH_URL_BASE:-http://localhost:8080}"; base="${base%/}"
minimo="${LH_MIN:-100}"
versao="${LH_VERSAO:-12.8.2}"
dispositivos="${LH_DISPOSITIVOS:-mobile desktop}"
[ -n "${IEADC_CHROMIUM:-}" ] && export CHROME_PATH="$IEADC_CHROMIUM"

# Páginas-chave: início, institucional, ministérios (lista e uma página), eventos, contato, horários, um artigo e a política.
if [ $# -gt 0 ]; then paginas=("$@")
else paginas=(/ /sobre-nos/ /ministerios/ /ministerios/mocidade/ /eventos/ /contato/ /horarios-e-localizacao/ /alcancar-curitiba/ /politica-de-privacidade/)
fi

saida="tests/lighthouse/saida/$(date +%Y%m%d-%H%M%S)"
mkdir -p "$saida"
codigo="$(curl -s -o /dev/null -w '%{http_code}' --max-time 20 "$base/")"
case "$codigo" in 2*|3*) ;; *) echo "O site não respondeu em $base (suba com ./bin/setup.sh ou ajuste LH_URL_BASE)." >&2; exit 2 ;; esac
echo "Lighthouse $versao em $base (mínimo $minimo): ${#paginas[@]} página(s) x $dispositivos"

erros=0
for disp in $dispositivos; do
  extra=()
  [ "$disp" = "desktop" ] && extra=(--preset=desktop)
  for pg in "${paginas[@]}"; do
    nome="$(echo "$pg" | sed 's#^/##; s#/$##; s#/#_#g')"; nome="${nome:-inicio}"
    echo "  -> $disp $pg"
    npx --yes "lighthouse@$versao" "$base$pg" --quiet "${extra[@]}" \
      --only-categories=performance,accessibility,best-practices,seo \
      --output=json --output=html --output-path="$saida/${disp}__${nome}" \
      --chrome-flags="--headless=new --no-sandbox --disable-gpu" >/dev/null 2>"$saida/${disp}__${nome}.log" \
      || { echo "     FALHOU ao medir (veja $saida/${disp}__${nome}.log)" >&2; erros=1; }
    # o Lighthouse grava <path>.report.json / .report.html quando há dois formatos
    [ -f "$saida/${disp}__${nome}.report.json" ] && mv "$saida/${disp}__${nome}.report.json" "$saida/${disp}__${nome}.json"
    [ -f "$saida/${disp}__${nome}.report.html" ] && mv "$saida/${disp}__${nome}.report.html" "$saida/${disp}__${nome}.html"
  done
done

node tests/lighthouse/resumo.js "$saida" "$minimo" || erros=1
echo "Relatórios completos: $saida"
exit $erros
