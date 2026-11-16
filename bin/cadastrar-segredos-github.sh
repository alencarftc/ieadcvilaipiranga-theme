#!/usr/bin/env bash
# Cadastra no GitHub os segredos dos environments `homologacao` e `producao` que o pipeline (docs/operacao/ci-cd.md) usa.
# Lê o .env (HOMOLOG_*, incluindo HOMOLOG_URL e, se existir, HOMOLOG_DESTINO; e HOSTINGER_* para a produção) e as chaves de
# deploy em content/backup/ (fora do git). O par HOMOLOG_URL + HOMOLOG_DESTINO é conferido antes (bin/implantar.sh conferir).
# NÃO imprime nenhum valor; o GitHub guarda os segredos cifrados e eles nunca mais podem ser lidos de volta.
# Uso (na raiz do projeto, com `gh auth login` feito e permissão de administrador no repositório):
#   ./bin/cadastrar-segredos-github.sh             # os dois environments
#   ./bin/cadastrar-segredos-github.sh homologacao # só a homologação
set -euo pipefail
cd "$(dirname "$0")/.."
alvo="${1:-tudo}"
repo="${REPO:-$(gh repo view --json nameWithOwner -q .nameWithOwner)}"

getv() { sed -n "s/^$1=//p" .env | head -1 | tr -d '\r"'"'"; }
segredo() { # nome environment valor
  printf '%s' "$3" | gh secret set "$1" --env "$2" --repo "$repo" > /dev/null && echo "ok  $2 / $1"
}
impressao() { # servidor porta -> SHA256:... (confira com o hPanel antes de confiar)
  ssh-keyscan -p "$2" -t ed25519 "$1" 2> /dev/null | ssh-keygen -lf - | awk '{print $2}'
}

if [ "$alvo" = tudo ] || [ "$alvo" = homologacao ]; then
  srv="$(getv HOMOLOG_SERVIDOR)"; porta="$(getv HOMOLOG_PORTA)"; usr="$(getv HOMOLOG_USUARIO)"; url="$(getv HOMOLOG_URL)"
  # HOMOLOG_DESTINO é opcional (sem ele vale a pasta padrão do endereço). O par endereço + pasta é conferido AQUI, pelas
  # mesmas travas do bin/implantar.sh (sem conectar em nada), antes de gravar qualquer segredo: só o par aceito segue.
  dest="$(getv HOMOLOG_DESTINO)"
  [ -n "$srv" ] && [ -n "$porta" ] && [ -n "$usr" ] && [ -n "$url" ] || { echo "ERRO: faltam HOMOLOG_SERVIDOR, HOMOLOG_PORTA, HOMOLOG_USUARIO ou HOMOLOG_URL no .env."; exit 1; }
  AMBIENTE=homologacao CARGA=codigo ALVO_URL="$url" ALVO_DESTINO="$dest" ./bin/implantar.sh conferir > /dev/null \
    || { echo "ERRO: o par HOMOLOG_URL + HOMOLOG_DESTINO do .env não é um destino aceito da homologação (veja a mensagem acima). Nenhum segredo foi gravado."; exit 1; }
  imp="$(impressao "$srv" "$porta")"; echo "impressão digital do servidor da homologação: $imp (confira no hPanel)"
  segredo HML_SERVIDOR homologacao "$srv"; segredo HML_PORTA homologacao "$porta"; segredo HML_USUARIO homologacao "$usr"
  segredo HML_URL homologacao "$url"; segredo HML_IMPRESSAO homologacao "$imp"
  # Só se existir no .env: o par exato da homologação aninhada (hml) precisa do HML_DESTINO; o endereço antigo não.
  [ -z "$dest" ] || segredo HML_DESTINO homologacao "$dest"
  gh secret set HML_SSH_CHAVE --env homologacao --repo "$repo" < content/backup/deploy-hml/id_ed25519 > /dev/null && echo "ok  homologacao / HML_SSH_CHAVE"
fi

if [ "$alvo" = tudo ] || [ "$alvo" = producao ]; then
  # Se a produção ficar na mesma conta SSH da homologação, os HOSTINGER_* podem ficar vazios: valem os HOMOLOG_*.
  srv="$(getv HOSTINGER_SERVIDOR)"; porta="$(getv HOSTINGER_PORTA)"; usr="$(getv HOSTINGER_USUARIO)"
  srv="${srv:-$(getv HOMOLOG_SERVIDOR)}"; porta="${porta:-$(getv HOMOLOG_PORTA)}"; usr="${usr:-$(getv HOMOLOG_USUARIO)}"
  [ -n "$srv" ] && [ -n "$porta" ] && [ -n "$usr" ] || { echo "ERRO: faltam servidor, porta ou usuário da produção no .env."; exit 1; }
  imp="$(impressao "$srv" "$porta")"; echo "impressão digital do servidor da produção: $imp (confira no hPanel)"
  segredo PROD_SERVIDOR producao "$srv"; segredo PROD_PORTA producao "$porta"; segredo PROD_USUARIO producao "$usr"
  segredo PROD_IMPRESSAO producao "$imp"
  gh secret set PROD_SSH_CHAVE --env producao --repo "$repo" < content/backup/deploy-prod/id_ed25519 > /dev/null && echo "ok  producao / PROD_SSH_CHAVE"
fi
if [ "$alvo" = tudo ] || [ "$alvo" = homologacao ]; then
  gh variable set HML_ATIVA --body true --repo "$repo" && echo "ok  variável HML_ATIVA=true (liga a publicação automática da homologação)"
fi
echo "Pronto. Os environments exigem: homologacao (só a main) e producao (aprovação do Felipe, só a main)."
