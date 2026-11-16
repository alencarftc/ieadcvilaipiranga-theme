#!/usr/bin/env bash
# Trava da PRODUÇÃO: roda no job "trava-hml" do workflow Produção (veja docs/operacao/ci-cd.md). Só faz leituras.
# Regra de ouro: a produção fica sempre atrás da homologação. Se qualquer item falhar, o workflow para com uma
# mensagem em português e nada é criado nem enviado.
# Entradas (variáveis): VERSAO, MODO (codigo|completo), CONFIRMACAO, GITHUB_REF, GITHUB_SHA, GITHUB_REPOSITORY, GH_TOKEN,
# mais as ALVO_* da homologação (as mesmas do bin/implantar.sh) com AMBIENTE=homologacao e ALVO_CHAVE.
# Saída: sha, run_hml e pacote no arquivo GITHUB_OUTPUT (se existir).
set -euo pipefail
cd "$(dirname "$0")/.."

falha() { echo "::error::$*"; exit 1; }
for v in VERSAO MODO CONFIRMACAO GITHUB_REF GITHUB_SHA GITHUB_REPOSITORY; do
  [ -n "${!v:-}" ] || falha "falta a variável $v."
done
R="$GITHUB_REPOSITORY"; SHA="$GITHUB_SHA"

echo "1. Entradas"
[[ "$VERSAO" =~ ^v[0-9]+\.[0-9]+\.[0-9]+$ ]] || falha "A versão deve ter o formato vX.Y.Z (ex.: v1.0.0)."
case "$MODO" in
  codigo) certas="PRODUCAO" ;;
  completo) certas="CARGA-INICIAL SUBSTITUIR-CONTEUDO" ;;
  *) falha "Modo inválido: use codigo ou completo." ;;
esac
ok=0
for c in $certas; do [ "$CONFIRMACAO" = "$c" ] && ok=1; done
[ "$ok" = 1 ] || falha "Confirmação errada: no modo $MODO digite ${certas// / ou }."
[ "$GITHUB_REF" = refs/heads/main ] || falha "A produção só recebe a main. Rode este workflow na branch main."

echo "2. O commit é a ponta da main, passou nos testes e a versão é nova"
ponta="$(gh api "repos/$R/commits/main" --jq .sha)" || falha "Não consegui consultar a main no GitHub."
[ "$ponta" = "$SHA" ] || falha "A main já avançou (${ponta:0:7}). Espere a homologação receber o commit mais novo e dispare de novo."
verdes="$(gh run list -R "$R" -w Testes -c "$SHA" -b main -e push -s success --json databaseId --jq length)" || falha "Não consegui consultar os testes no GitHub."
[ "$verdes" -ge 1 ] || falha "O workflow Testes não passou no commit ${SHA:0:7}."
existe="$(gh api "repos/$R/git/matching-refs/tags/$VERSAO" --jq "[.[] | select(.ref == \"refs/tags/$VERSAO\")] | length")" || falha "Não consegui consultar as tags no GitHub."
[ "$existe" -eq 0 ] || falha "A tag $VERSAO já existe. Escolha uma versão nova (se uma tentativa anterior foi recusada, apague a release e a tag dela)."

echo "3. A homologação roda exatamente este commit e a implantação dela deu certo"
json="$(AMBIENTE=homologacao ./bin/implantar.sh ler-deploy)" \
  || falha "Não consegui ler a opção ieadc_deploy da homologação por SSH. A homologação já recebeu alguma implantação? A chave, a porta e a impressão digital estão certas? Veja as mensagens acima."
echo "$json" | jq -e . > /dev/null 2>&1 || falha "A opção ieadc_deploy da homologação está ilegível. Rode o workflow Homologação de novo."
sha_hml="$(jq -r '.sha // empty' <<< "$json")"
amb_hml="$(jq -r '.ambiente // empty' <<< "$json")"
res_hml="$(jq -r '.resultado // empty' <<< "$json")"
run_hml="$(jq -r '.run_id // empty' <<< "$json")"
pacote="$(jq -r '.pacote // empty' <<< "$json")"
[ "$amb_hml" = homologacao ] || falha "A opção ieadc_deploy do endereço de homologação não é de um ambiente de homologação."
[ "$res_hml" = ok ] || falha "A última implantação na homologação não terminou bem (resultado: ${res_hml:-desconhecido}). Corrija e rode o workflow Homologação de novo."
[ "$sha_hml" = "$SHA" ] || falha "A homologação está no commit ${sha_hml:0:7}, não em ${SHA:0:7}. Espere o workflow Homologação terminar neste commit e dispare de novo."
{ [[ "$run_hml" =~ ^[0-9]+$ ]] && [[ "$pacote" =~ ^[0-9a-f]{64}$ ]]; } || falha "A implantação da homologação não registrou a execução e o pacote. Rode o workflow Homologação de novo."

echo "4. A execução do GitHub que implantou a homologação"
run="$(gh api "repos/$R/actions/runs/$run_hml")" || falha "Não consegui consultar a execução $run_hml da Homologação no GitHub."
r_status="$(jq -r '.status // empty' <<< "$run")"
r_concl="$(jq -r '.conclusion // empty' <<< "$run")"
r_sha="$(jq -r '.head_sha // empty' <<< "$run")"
r_path="$(jq -r '.path // empty' <<< "$run")"
{ [ "$r_status" = completed ] && [ "$r_concl" = success ]; } || falha "A execução $run_hml da Homologação não terminou com sucesso (situação: ${r_status:-?}, resultado: ${r_concl:-?})."
[ "$r_sha" = "$SHA" ] || falha "A execução $run_hml da Homologação é do commit ${r_sha:0:7}, não de ${SHA:0:7}."
[ "${r_path%%@*}" = ".github/workflows/homologacao.yml" ] || falha "A execução $run_hml não é do workflow Homologação (é de ${r_path:-desconhecido})."

echo "5. O pacote dessa execução"
# O pacote que a homologação recebeu tem de existir como artifact DAQUELA execução (e não ter expirado).
achou="$(gh api "repos/$R/actions/runs/$run_hml/artifacts" | jq -r --arg n "pacote-hml-$SHA" '[.artifacts[] | select(.name == $n and (.expired | not))] | length')" \
  || falha "Não consegui consultar os artifacts da execução $run_hml."
[ "$achou" -ge 1 ] || falha "Não há artifact pacote-hml-${SHA:0:7}... válido na execução $run_hml (expirou?). Rode o workflow Homologação de novo."

if [ -n "${GITHUB_OUTPUT:-}" ]; then
  {
    echo "sha=$SHA"
    echo "run_hml=$run_hml"
    echo "pacote=$pacote"
  } >> "$GITHUB_OUTPUT"
fi
echo "Trava liberada: homologação no commit ${SHA:0:7}, execução $run_hml, pacote ${pacote:0:12}."
