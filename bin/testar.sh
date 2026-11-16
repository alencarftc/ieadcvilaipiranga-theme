#!/usr/bin/env bash
# Roda os testes do site.
# Uso: ./bin/testar.sh [php|markup|visual|interacao] [pagina[,pagina…]]
#   sem argumentos: tudo. Exemplo: ./bin/testar.sh visual inicio
# Pré-requisitos: site no ar no endereço do SITE_URL do .env (padrão http://localhost:8080; ./bin/setup.sh) com o ambiente "local" do
# WordPress (o docker-compose.yml já define), Node 20+ e Docker. O .env fica com IEADC_HOJE vazio: o site local mostra o dia real e os
# testes fixam 06/10/2026 pelo cabeçalho X-Ieadc-Hoje (tests/visual/playwright.config.js). O protótipo é servido sozinho em http://localhost:8090 durante os testes.
# Imagens de diferença do teste visual: tests/visual/saida/<pagina>-<largura>-{wp,proto,diff}.png
set -uo pipefail
cd "$(dirname "$0")/.."
export MSYS_NO_PATHCONV=1   # Git Bash no Windows não pode reescrever caminhos como /tests

# Os testes abrem o site no endereço do .env (SITE_URL), igual ao que o celular usa na rede de casa.
# Se o .env tem o IP da máquina, o HTML sai com esse endereço e o teste precisa saber disso. Sem .env: http://localhost:8080.
if [ -z "${WP_URL:-}" ] && [ -f .env ]; then
  WP_URL="$(sed -n 's/^SITE_URL=//p' .env | head -1 | tr -d '\r "'"'")"
  [ -n "$WP_URL" ] && export WP_URL
fi

parte="${1:-tudo}"
[ $# -ge 2 ] && export IEADC_PAGINAS="$2"
case "$parte" in tudo|php|markup|visual|interacao) ;; *) echo "Uso: $0 [php|markup|visual|interacao] [pagina]"; exit 2 ;; esac

falhou=0
passo() { echo; echo "=== $1 ==="; }
roda() { "$@" || falhou=1; }

# Data dos testes: a agenda do site tem de sair igual à do protótipo (06/10/2026). Os testes mandam o cabeçalho X-Ieadc-Hoje,
# que só vale no ambiente local e vence o IEADC_HOJE do .env; o aviso é só para lembrar que o .env deve ficar com ele vazio.
if [ -f .env ] && grep -Eq '^IEADC_HOJE=[^[:space:]]' .env && [ "$parte" != "php" ]; then
  echo "AVISO: .env com IEADC_HOJE preenchido; deixe vazio (o dia real vale e os testes fixam a data por cabeçalho)." >&2
fi

testes_php() {
  passo "php -l (sintaxe de todos os arquivos PHP)"
  local erros=0 arq
  if command -v php >/dev/null 2>&1; then
    while IFS= read -r arq; do
      php -l "$arq" >/dev/null 2>/tmp/ieadc-php-l.txt || { cat /tmp/ieadc-php-l.txt; erros=1; }
    done < <(find wp-content scripts tests -name '*.php' -not -path '*/node_modules/*')
  else
    docker compose run --rm -T --no-deps -v "$PWD:/repo:ro" --entrypoint sh wpcli -c \
      'find /repo/wp-content /repo/scripts /repo/tests -name "*.php" | while read f; do php -l "$f" >/dev/null || exit 1; done' || erros=1
  fi
  [ $erros -eq 0 ] && echo "ok" || return 1

  if [ -f tests/php/agenda/teste-agenda.php ]; then
    passo "testes PHP: agenda (sem WordPress)"
    if command -v php >/dev/null 2>&1; then
      php tests/php/agenda/teste-agenda.php || return 1
    else
      docker compose run --rm -T --no-deps -v "$PWD:/repo:ro" --entrypoint php wpcli /repo/tests/php/agenda/teste-agenda.php || return 1
    fi
  fi

  passo "testes PHP: funções comuns (dentro do WordPress)"
  local t
  for t in tests/php/*/teste-helpers.php; do
    [ -f "$t" ] || continue
    docker compose run --rm -T --no-deps -v "$PWD/tests:/tests:ro" --entrypoint php wpcli "/tests/${t#tests/}" || return 1
  done
}

testes_playwright() {
  [ -d node_modules/@playwright/test ] || { passo "npm ci"; npm ci --no-audit --no-fund || return 1; }
  passo "playwright: $1"
  # Limite de envios dos formulários (5 por hora): zera antes de testar a interação.
  [ "$1" = "interacao" ] && docker compose run --rm -T wpcli wp transient delete --all >/dev/null 2>&1
  npx playwright test -c tests/visual "$1.spec" --reporter=list
}

case "$parte" in
  php) roda testes_php ;;
  markup|visual|interacao) roda testes_playwright "$parte" ;;
  tudo)
    roda testes_php
    roda testes_playwright markup
    roda testes_playwright visual
    roda testes_playwright interacao
    ;;
esac

echo
[ $falhou -eq 0 ] && echo "TUDO CERTO." || echo "HOUVE FALHAS (veja acima)."
exit $falhou
