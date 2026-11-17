#!/usr/bin/env bash
# Testes OFFLINE das travas do pipeline de implantação (sem rede, sem docker, sem servidor, sem credenciais).
# Rodam no CI (workflow Testes, por ./bin/testar.sh ci) e no computador: ./bin/testar.sh ci   ou   bash tests/ci/guardas.sh
#
# O que provam (docs/operacao/ci-cd.md):
#  1. bin/implantar.sh (subcomando "conferir"): quais pares endereço + pasta a homologação e a produção aceitam, e a lista de
#     recusas (domínio principal na homologação, outras pastas e subdomínios, variações de caminho, modo e confirmação errados).
#     A homologação nova (hml.ieadcvilaipiranga.com.br) é uma SUBPASTA da produção: as comparações são por igualdade.
#  2. bin/atualizar-remoto.sh e bin/migrar-remoto.sh (que rodam no servidor): a mesma trava de pasta, repetida lá.
#  3. Simulação com um HOME de mentira e um wp-cli de mentira: a produção nunca toca a pasta hml, a homologação nunca toca
#     nada acima dela, as cópias de segurança de uma não apagam as da outra e o banco repartido é recusado.
#  4. bin/migrar.ps1 (caminho manual): a mesma trava no PowerShell (só se houver pwsh ou powershell).
#  5. bin/cadastrar-segredos-github.sh: só cadastra HML_URL e HML_DESTINO se o par for aceito (com gh e ssh de mentira).
# shellcheck disable=SC2012  # os "ls | wc -l" contam arquivos de nome conhecido (cópias de segurança de mentira)
set -uo pipefail
cd "$(dirname "$0")/../.." || exit 1
TMP="$(mktemp -d)"
TIL='~'   # o til literal (a trava recusa ~ na pasta)
trap 'rm -rf "$TMP"' EXIT

total=0; falhas=0
PROD=ieadcvilaipiranga.com.br
HML=hml.$PROD
P_PROD=domains/$PROD/public_html
P_HML=$P_PROD/hml

falhou() { falhas=$((falhas + 1)); echo "  FALHOU  $1"; [ -z "${2:-}" ] || printf '%s\n' "$2" | head -n 4 | sed 's/^/            /'; }
passou() { :; }
secao() { echo "== $1"; }

# --- 1. bin/implantar.sh conferir -------------------------------------------------------------------------------------
# imp ok|no "descrição" "trecho esperado na saída" VAR=valor...
imp() {
  local esperado="$1" nome="$2" trecho="$3" saida rc; shift 3
  total=$((total + 1))
  saida="$(env -i PATH="$PATH" HOME="$TMP" "$@" bash bin/implantar.sh conferir 2>&1)"; rc=$?
  if [ "$esperado" = ok ]; then
    if [ "$rc" -eq 0 ] && [[ "$saida" == *"TRAVAS OK"* ]] && [[ "$saida" == *"$trecho"* ]]; then passou; else falhou "deveria ACEITAR: $nome" "$saida"; fi
  else
    if [ "$rc" -ne 0 ] && [[ "$saida" == *"ERRO"* ]] && [[ "$saida" != *"TRAVAS OK"* ]] && [[ "$saida" == *"$trecho"* ]]; then passou; else falhou "deveria RECUSAR: $nome" "$saida"; fi
  fi
}
H() { imp "$1" "$2" "$3" AMBIENTE=homologacao CARGA=codigo "${@:4}"; }   # homologação, só código
P() { imp "$1" "$2" "$3" AMBIENTE=producao CARGA=codigo CONFIRMACAO=PRODUCAO "${@:4}"; }   # produção, só código

secao "implantar.sh: homologação, pares aceitos"
H ok "hml, pasta padrão" "pasta=$P_HML" ALVO_URL="https://$HML"
H ok "hml, pasta exata" "pasta=$P_HML" ALVO_URL="https://$HML" ALVO_DESTINO="$P_HML"
H ok "hml com porta (ensaio local)" "pasta=$P_HML" ALVO_URL="http://$HML:8082" ALVO_DESTINO="$P_HML"
H ok "hml em http" "host=$HML" ALVO_URL="http://$HML"
H ok "hml com barra final na URL" "host=$HML" ALVO_URL="https://$HML/"
H ok "hml com o host em maiúsculas (DNS não diferencia; a pasta é que é exata)" "host=$HML pasta=$P_HML" ALVO_URL="https://HML.IeadcViLaIpiranga.COM.BR"
H ok "hml, carga completa, modo homologacao (ambiente deduzido)" "pasta=$P_HML" CARGA=completo MODO=homologacao ALVO_URL="https://$HML"
H ok "hml, carga completa, modo homologacao-fechada" "pasta=$P_HML" CARGA=completo MODO=homologacao-fechada ALVO_URL="https://$HML"
H ok "hostingersite (transição), pasta padrão" "pasta=domains/abc123.hostingersite.com/public_html" ALVO_URL="https://abc123.hostingersite.com"
H ok "hostingersite (transição), pasta exata" "host=abc123.hostingersite.com" ALVO_URL="https://abc123.hostingersite.com" ALVO_DESTINO="domains/abc123.hostingersite.com/public_html"
H ok "hostingersite com mais de um rótulo" "pasta=domains/a.b.hostingersite.com/public_html" ALVO_URL="https://a.b.hostingersite.com"
H ok "ensaio local com outro domínio principal (DOMINIO_PRODUCAO)" "pasta=domains/teste.local/public_html/hml" DOMINIO_PRODUCAO=teste.local ALVO_SERVIDOR=127.0.0.1 ALVO_URL="http://hml.teste.local:8082"
H ok "GITHUB_ACTIONS=true usa sempre o domínio real (par real)" "pasta=$P_HML" GITHUB_ACTIONS=true DOMINIO_PRODUCAO=teste.local ALVO_SERVIDOR=127.0.0.1 ALVO_URL="https://$HML"

secao "implantar.sh: homologação, domínio principal e variações de endereço"
H no "domínio principal" "não pode apontar para o domínio da produção" ALVO_URL="https://$PROD"
H no "www do domínio principal" "não pode apontar para o domínio da produção" ALVO_URL="https://www.$PROD"
H no "domínio principal com porta" "não pode apontar para o domínio da produção" ALVO_URL="http://$PROD:8081"
H no "domínio principal em maiúsculas" "não pode apontar para o domínio da produção" ALVO_URL="https://IEADCVILAIPIRANGA.COM.BR"
H no "domínio principal com a pasta hml" "não pode apontar para o domínio da produção" ALVO_URL="https://$PROD" ALVO_DESTINO="$P_HML"
H no "domínio principal com a pasta da produção" "não pode apontar para o domínio da produção" ALVO_URL="https://$PROD" ALVO_DESTINO="$P_PROD"
H no "outro subdomínio (staging)" "só aceita o endereço" ALVO_URL="https://staging.$PROD" ALVO_DESTINO="$P_HML"
H no "outro subdomínio (teste), pasta da produção" "só aceita o endereço" ALVO_URL="https://teste.$PROD" ALVO_DESTINO="$P_PROD"
H no "hml2" "só aceita o endereço" ALVO_URL="https://hml2.$PROD" ALVO_DESTINO="$P_HML"
H no "subdomínio de segundo nível (a.hml)" "só aceita o endereço" ALVO_URL="https://a.$HML" ALVO_DESTINO="$P_HML"
H no "xhml" "só aceita o endereço" ALVO_URL="https://x$HML" ALVO_DESTINO="$P_HML"
H no "hml-ieadc... (hífen no lugar do ponto)" "só aceita o endereço" ALVO_URL="https://hml-$PROD"
H no "hmlieadc... (sem ponto)" "só aceita o endereço" ALVO_URL="https://hml$PROD"
H no "hml com o TLD errado (.com)" "só aceita o endereço" ALVO_URL="https://hml.ieadcvilaipiranga.com"
H no "hml colado em outro domínio (.evil.com)" "só aceita o endereço" ALVO_URL="https://$HML.evil.com"
H no "hml dentro de hostingersite (contém o domínio principal)" "só aceita o endereço" ALVO_URL="https://$HML.hostingersite.com"
H no "domínio principal dentro de hostingersite" "só aceita o endereço" ALVO_URL="https://$PROD.hostingersite.com"
H no "hostingersite.com sem rótulo" "só aceita o endereço" ALVO_URL="https://hostingersite.com"
H no "hostingersite.com.br" "só aceita o endereço" ALVO_URL="https://abc.hostingersite.com.br"
H no "hostingersite.com seguido de outro domínio" "só aceita o endereço" ALVO_URL="https://abc.hostingersite.com.evil.com"
H no "colado em hostingersite (abchostingersite.com)" "só aceita o endereço" ALVO_URL="https://abchostingersite.com"
H no "localhost" "só aceita o endereço" ALVO_URL="http://localhost:8080"
H no "endereço IP" "só aceita o endereço" ALVO_URL="http://127.0.0.1"
H no "domínio qualquer (antes aceito)" "só aceita o endereço" ALVO_URL="https://outro.com.br"
H no "URL com caminho" "formato" ALVO_URL="https://$HML/hml"
H no "URL com usuário" "formato" ALVO_URL="https://alguem@$HML"
H no "URL com query" "formato" ALVO_URL="https://$HML/?a=1"
H no "URL com espaço" "formato" ALVO_URL="https://$HML "
H no "URL com ftp" "formato" ALVO_URL="ftp://$HML"
H no "URL com porta inválida" "formato" ALVO_URL="https://$HML:80abc"
H no "URL com ponto final no host" "formato" ALVO_URL="https://$HML."
H no "URL com ponto no início do host" "formato" ALVO_URL="https://.$HML"
H no "URL vazia" "falta a variável ALVO_URL" ALVO_URL=
H no "URL ausente" "falta a variável ALVO_URL"

secao "implantar.sh: homologação, variações da pasta (o prefixo da produção é o caminho da hml)"
for v in \
  "$P_PROD|a pasta da produção" \
  "$P_HML/|hml com barra final" \
  "$P_HML//|hml com duas barras finais" \
  "$P_HML/..|hml/.." \
  "$P_HML/../..|hml/../.." \
  "$P_PROD/./hml|public_html/./hml" \
  "/$P_HML|barra no início" \
  "/home/u123/$P_HML|caminho absoluto" \
  "domains//$PROD/public_html/hml|// no meio" \
  "$P_PROD//hml|// antes de hml" \
  "$P_PROD/HML|HML em maiúsculas" \
  "$P_PROD/Hml|Hml" \
  "DOMAINS/$PROD/public_html/hml|DOMAINS em maiúsculas" \
  "domains/IEADCVILAIPIRANGA.COM.BR/public_html/hml|domínio da pasta em maiúsculas" \
  "domains/$PROD/PUBLIC_HTML/hml|PUBLIC_HTML em maiúsculas" \
  "$P_HML |espaço no fim" \
  " $P_HML|espaço no início" \
  "domains/$PROD/public_html/h ml|espaço no meio" \
  "domains\\$PROD\\public_html\\hml|barras invertidas" \
  "$TIL/$P_HML|til" \
  "$P_HML/wp-content|dentro da hml" \
  "$P_PROD/hml2|hml2" \
  "$P_PROD/hml-old|hml-old" \
  "$P_PROD/hmlx|hmlx" \
  "$P_PROD/xhml|xhml" \
  "$P_PROD/hml.bak|hml.bak" \
  "domains/$HML/public_html|pasta derivada do host hml" \
  "domains/$HML/public_html/hml|pasta do host hml com hml" \
  "domains/$PROD/hml|domains/<domínio>/hml" \
  "domains/terceiro.com.br/public_html|pasta de terceiro" \
  "domains/terceiro.com.br/public_html/hml|hml de terceiro" \
  "$P_HML/../../../terceiro.com.br/public_html|escapando para terceiro" \
  "public_html/hml|sem domains/" \
  "$PROD/public_html/hml|sem domains/ (2)" \
  "domains/$PROD/public_html/hml/..|.. no fim da pasta hml" \
  "domains/$PROD/public_html/hml\$(id)|substituição de comando" \
  "domains/$PROD/public_html/hml;ls|ponto e vírgula" \
  "domains/$PROD/public_html/hml'|aspas" \
  "domains/$PROD/public_html/hml*|curinga" ; do
  H no "hml com a pasta: ${v#*|}" "" ALVO_URL="https://$HML" ALVO_DESTINO="${v%%|*}"
done
H no "hml com a pasta terminando em nova linha" "" ALVO_URL="https://$HML" ALVO_DESTINO="$P_HML"$'\n'
H no "hostingersite com a pasta da hml" "pasta do site deve ser exatamente" ALVO_URL="https://abc.hostingersite.com" ALVO_DESTINO="$P_HML"
H no "hostingersite com a pasta da produção" "pasta do site deve ser exatamente" ALVO_URL="https://abc.hostingersite.com" ALVO_DESTINO="$P_PROD"
H no "hostingersite com a pasta de outro host" "pasta do site deve ser exatamente" ALVO_URL="https://abc.hostingersite.com" ALVO_DESTINO="domains/def.hostingersite.com/public_html"
H no "hostingersite com subpasta" "pasta do site deve ser exatamente" ALVO_URL="https://abc.hostingersite.com" ALVO_DESTINO="domains/abc.hostingersite.com/public_html/hml"

secao "implantar.sh: ambiente, carga e modo"
imp no "AMBIENTE ausente" "falta a variável AMBIENTE" CARGA=codigo ALVO_URL="https://$HML"
imp no "AMBIENTE=Homologacao" "AMBIENTE deve ser" AMBIENTE=Homologacao CARGA=codigo ALVO_URL="https://$HML"
imp no "AMBIENTE=staging" "AMBIENTE deve ser" AMBIENTE=staging CARGA=codigo ALVO_URL="https://$HML"
imp no "AMBIENTE=hml" "AMBIENTE deve ser" AMBIENTE=hml CARGA=codigo ALVO_URL="https://$HML"
imp no "homologação com MODO=lancar" "não combina" AMBIENTE=homologacao CARGA=completo MODO=lancar ALVO_URL="https://$HML"
imp no "homologação com MODO=ensaio" "não combina" AMBIENTE=homologacao CARGA=completo MODO=ensaio ALVO_URL="https://$HML"
imp no "carga completa sem MODO" "falta a variável MODO" CARGA=completo ALVO_URL="https://$HML"
imp no "CARGA inválida" "CARGA deve ser" AMBIENTE=homologacao CARGA=banana ALVO_URL="https://$HML"
imp no "MODO inválido" "MODO deve ser" CARGA=completo MODO=staging ALVO_URL="https://$HML"
imp no "DOMINIO_PRODUCAO com caracteres inválidos" "DOMINIO_PRODUCAO tem caracteres inválidos" DOMINIO_PRODUCAO='a;b' AMBIENTE=homologacao CARGA=codigo ALVO_URL="https://$HML"
imp no "GITHUB_ACTIONS=true ignora o DOMINIO_PRODUCAO de teste" "só aceita o endereço" GITHUB_ACTIONS=true DOMINIO_PRODUCAO=teste.local ALVO_SERVIDOR=127.0.0.1 AMBIENTE=homologacao CARGA=codigo ALVO_URL="https://hml.teste.local"
# O override do domínio principal só vale contra o servidor do próprio computador (S3): na máquina do Felipe, com o servidor real,
# ele faria o conferir e o envio aceitarem pares de um domínio de terceiros.
imp no "DOMINIO_PRODUCAO sem ALVO_SERVIDOR (conferir)" "DOMINIO_PRODUCAO só vale" DOMINIO_PRODUCAO=teste.local AMBIENTE=homologacao CARGA=codigo ALVO_URL="https://hml.teste.local"
imp no "DOMINIO_PRODUCAO com servidor remoto" "DOMINIO_PRODUCAO só vale" DOMINIO_PRODUCAO=teste.local ALVO_SERVIDOR=ssh.hospedagem.example AMBIENTE=homologacao CARGA=codigo ALVO_URL="https://hml.teste.local"
imp no "DOMINIO_PRODUCAO com servidor 127.0.0.1.evil.com" "DOMINIO_PRODUCAO só vale" DOMINIO_PRODUCAO=teste.local ALVO_SERVIDOR=127.0.0.1.evil.com AMBIENTE=homologacao CARGA=codigo ALVO_URL="https://hml.teste.local"
imp no "DOMINIO_PRODUCAO com servidor 128.0.0.1" "DOMINIO_PRODUCAO só vale" DOMINIO_PRODUCAO=teste.local ALVO_SERVIDOR=128.0.0.1 AMBIENTE=homologacao CARGA=codigo ALVO_URL="https://hml.teste.local"
imp no "DOMINIO_PRODUCAO de terceiro (domínio real de outro site) com servidor remoto, produção" "DOMINIO_PRODUCAO só vale" DOMINIO_PRODUCAO=terceiro.com.br ALVO_SERVIDOR=ssh.hospedagem.example AMBIENTE=producao CARGA=codigo CONFIRMACAO=PRODUCAO ALVO_URL="https://terceiro.com.br"
imp ok "DOMINIO_PRODUCAO com servidor ::1" "pasta=domains/teste.local/public_html/hml" DOMINIO_PRODUCAO=teste.local ALVO_SERVIDOR=::1 AMBIENTE=homologacao CARGA=codigo ALVO_URL="https://hml.teste.local"
# O endereço que vai para home/siteurl é minúsculo, sem barra final e sem porta (a porta só existe no ensaio local) (S10).
H ok "URL em maiúsculas vira minúscula" "url=https://hml.ieadcvilaipiranga.com.br" ALVO_URL="https://HML.IeadcViLaIpiranga.COM.BR"
H ok "URL com barra final" "url=https://hml.ieadcvilaipiranga.com.br" ALVO_URL="https://hml.ieadcvilaipiranga.com.br/"
H ok "URL com porta fora do ensaio local perde a porta" "url=https://hml.ieadcvilaipiranga.com.br" ALVO_URL="https://hml.ieadcvilaipiranga.com.br:8443"
H ok "URL com porta 443 perde a porta" "url=https://hml.ieadcvilaipiranga.com.br" ALVO_URL="https://hml.ieadcvilaipiranga.com.br:443"
H ok "URL com porta no ensaio local (servidor 127.0.0.1) mantém a porta" "url=http://hml.ieadcvilaipiranga.com.br:8082" ALVO_SERVIDOR=127.0.0.1 ALVO_URL="http://HML.ieadcvilaipiranga.com.br:8082/"
H no "esquema em maiúsculas" "formato" ALVO_URL="HTTPS://$HML"
P ok "produção em maiúsculas vira minúscula" "url=https://ieadcvilaipiranga.com.br" ALVO_URL="https://IEADCVilaIpiranga.COM.BR/"
P ok "produção com www e porta perde a porta" "url=https://www.ieadcvilaipiranga.com.br" ALVO_URL="https://WWW.ieadcvilaipiranga.com.br:8443"

secao "implantar.sh: produção"
P ok "domínio principal, pasta padrão" "pasta=$P_PROD" ALVO_URL="https://$PROD"
P ok "www, pasta padrão (a pasta é a do domínio sem www)" "pasta=$P_PROD" ALVO_URL="https://www.$PROD"
P ok "pasta exata" "host=$PROD" ALVO_URL="https://$PROD" ALVO_DESTINO="$P_PROD"
imp ok "carga completa, ensaio, CARGA-INICIAL" "pasta=$P_PROD" CARGA=completo MODO=ensaio CONFIRMACAO=CARGA-INICIAL ALVO_URL="https://$PROD"
imp ok "carga completa, lançar, SUBSTITUIR-CONTEUDO" "pasta=$P_PROD" CARGA=completo MODO=lancar CONFIRMACAO=SUBSTITUIR-CONTEUDO ALVO_URL="https://$PROD"
P ok "ensaio local com outro domínio principal" "pasta=domains/teste.local/public_html" DOMINIO_PRODUCAO=teste.local ALVO_SERVIDOR=localhost ALVO_URL="http://teste.local:8082"
P no "endereço da homologação (hml)" "na produção o endereço deve ser" ALVO_URL="https://$HML"
P no "endereço da hml com a pasta da hml" "na produção o endereço deve ser" ALVO_URL="https://$HML" ALVO_DESTINO="$P_HML"
P no "domínio principal com a pasta da hml" "pasta do site deve ser exatamente" ALVO_URL="https://$PROD" ALVO_DESTINO="$P_HML"
P no "domínio principal com a pasta da hml e barra" "pasta do site deve ser exatamente" ALVO_URL="https://$PROD" ALVO_DESTINO="$P_HML/"
P no "domínio principal com a pasta dentro da hml" "pasta do site deve ser exatamente" ALVO_URL="https://$PROD" ALVO_DESTINO="$P_HML/wp-content"
P no "domínio principal com a pasta e barra final" "pasta do site deve ser exatamente" ALVO_URL="https://$PROD" ALVO_DESTINO="$P_PROD/"
P no "domínio principal com a pasta do www" "pasta do site deve ser exatamente" ALVO_URL="https://$PROD" ALVO_DESTINO="domains/www.$PROD/public_html"
P no "domínio principal com pasta de terceiro" "pasta do site deve ser exatamente" ALVO_URL="https://$PROD" ALVO_DESTINO="domains/terceiro.com.br/public_html"
P no "domínio principal com a pasta em maiúsculas" "pasta do site deve ser exatamente" ALVO_URL="https://$PROD" ALVO_DESTINO="DOMAINS/$PROD/public_html"
P no "domínio principal com .." "inválida" ALVO_URL="https://$PROD" ALVO_DESTINO="$P_PROD/hml/.."
P no "endereço hostingersite" "na produção o endereço deve ser" ALVO_URL="https://abc.hostingersite.com"
P no "localhost" "na produção o endereço deve ser" ALVO_URL="http://localhost:8080"
P no "outro subdomínio" "na produção o endereço deve ser" ALVO_URL="https://staging.$PROD"
P no "domínio colado (.evil.com)" "na produção o endereço deve ser" ALVO_URL="https://$PROD.evil.com"
imp no "sem CONFIRMACAO (código)" "CONFIRMACAO deve ser PRODUCAO" AMBIENTE=producao CARGA=codigo ALVO_URL="https://$PROD"
imp no "CONFIRMACAO errada (código com CARGA-INICIAL)" "CONFIRMACAO deve ser PRODUCAO" AMBIENTE=producao CARGA=codigo CONFIRMACAO=CARGA-INICIAL ALVO_URL="https://$PROD"
imp no "CONFIRMACAO em minúsculas" "CONFIRMACAO deve ser PRODUCAO" AMBIENTE=producao CARGA=codigo CONFIRMACAO=producao ALVO_URL="https://$PROD"
imp no "carga completa com CONFIRMACAO=PRODUCAO" "CONFIRMACAO deve ser CARGA-INICIAL" CARGA=completo MODO=ensaio CONFIRMACAO=PRODUCAO ALVO_URL="https://$PROD"
imp no "carga completa sem CONFIRMACAO" "CONFIRMACAO deve ser CARGA-INICIAL" CARGA=completo MODO=lancar ALVO_URL="https://$PROD"
imp no "produção com MODO=homologacao" "não combina" AMBIENTE=producao CARGA=completo MODO=homologacao CONFIRMACAO=CARGA-INICIAL ALVO_URL="https://$PROD"
imp no "produção com MODO=homologacao-fechada" "não combina" AMBIENTE=producao CARGA=completo MODO=homologacao-fechada CONFIRMACAO=CARGA-INICIAL ALVO_URL="https://$PROD"

# --- 1b. GTM_ID e CLARITY_ID (fase 4): validados, só na produção e nunca com payload ------------------------------------------
# Os IDs de medição são gravados no servidor dentro de um comando "wp eval" (PHP): por isso o formato é validado ANTES de abrir
# qualquer conexão e só a produção os aceita. O teste usa "enviar" com um pacote mínimo e sem ALVO_SERVIDOR: se a validação passa,
# o script para logo depois, em "falta a variável ALVO_SERVIDOR" (nunca conecta); se a validação recusa, a mensagem é a do ID.
secao "implantar.sh: GTM_ID e CLARITY_ID (formato, só na produção, payloads)"
MINI_DIR="$TMP/mini"; mkdir -p "$MINI_DIR"
printf 'PACOTE_PREFIXO=wp_\nPACOTE_ORIGENS=http://localhost:8080\nPACOTE_SHA=desconhecido\n' > "$MINI_DIR/pacote.env"
tar -czf "$TMP/mini.tgz" -C "$MINI_DIR" pacote.env
# med ok|no "descrição" "trecho" VAR=valor...
med() {
  local esperado="$1" nome="$2" trecho="$3" saida rc; shift 3
  total=$((total + 1))
  saida="$(env -i PATH="$PATH" HOME="$TMP" "$@" bash bin/implantar.sh enviar "$TMP/mini.tgz" 2>&1)"; rc=$?
  if [ "$esperado" = ok ]; then
    if [ "$rc" -ne 0 ] && [[ "$saida" == *"falta a variável ALVO_SERVIDOR"* ]] && [[ "$saida" != *"GTM_ID"* ]] && [[ "$saida" != *"CLARITY_ID"* ]]; then passou; else falhou "deveria ACEITAR o ID (validação passa e para sem conectar): $nome" "$saida"; fi
  else
    if [ "$rc" -ne 0 ] && [[ "$saida" == *"ERRO"* ]] && [[ "$saida" == *"$trecho"* ]] && [[ "$saida" != *"falta a variável ALVO_SERVIDOR"* ]] && [[ "$saida" != *"Chave do servidor"* ]]; then passou; else falhou "deveria RECUSAR o ID: $nome" "$saida"; fi
  fi
}
MP=(AMBIENTE=producao CARGA=codigo CONFIRMACAO=PRODUCAO ALVO_URL="https://$PROD")          # produção
MH=(AMBIENTE=homologacao CARGA=codigo ALVO_URL="https://$HML" ALVO_DESTINO="$P_HML")         # homologação aninhada
MT=(AMBIENTE=homologacao CARGA=codigo ALVO_URL="https://abc123.hostingersite.com")           # homologação de transição
med ok "sem GTM_ID nem CLARITY_ID (produção)" "" "${MP[@]}"
med ok "GTM_ID vazio e CLARITY_ID vazio (produção)" "" "${MP[@]}" GTM_ID= CLARITY_ID=
med ok "GTM-ABCD (mínimo, 4)" "" "${MP[@]}" GTM_ID=GTM-ABCD
med ok "GTM-ABCDEFGHIJKL (máximo, 12)" "" "${MP[@]}" GTM_ID=GTM-ABCDEFGHIJKL
med ok "GTM-A1B2C3D (7, o formato comum)" "" "${MP[@]}" GTM_ID=GTM-A1B2C3D
med ok "CLARITY_ID abcdef (mínimo, 6)" "" "${MP[@]}" CLARITY_ID=abcdef
med ok "CLARITY_ID abc123def456 (máximo, 12)" "" "${MP[@]}" CLARITY_ID=abc123def456
med ok "GTM_ID e CLARITY_ID juntos" "" "${MP[@]}" GTM_ID=GTM-ABCD1234 CLARITY_ID=k4l9m2n7p1
for v in 'GTM-ABC' 'GTM-ABCDEFGHIJKLM' 'gtm-ABCD' 'GTM-abcd' 'GTM_ABCD' 'GTM-' 'GTM' 'ABCD' ' GTM-ABCD' 'GTM-ABCD ' 'GTM-AB CD' 'GTM-ABCD-1' \
  'GTM-ABCD;ls' 'GTM-ABCD$(id)' 'GTM-ABCD`id`' "GTM-ABCD'" 'GTM-ABCD";system("id");//' 'GTM-ABCD";update_option("siteurl","x");//' 'GTM-ABCD\' \
  'GTM-ABCD|id' 'GTM-ABCD&id' 'GTM-ABCD>x' 'GTM-<script>' 'GTM-ABCÀ' 'GTM-ABCD%0a' 'GTM-ABCD\n' '../GTM-ABCD' 'GTM-ABCD/../x' '*' 'GTM-*' ; do
  med no "GTM_ID malicioso/inválido na produção: $v" "GTM_ID fora do formato" "${MP[@]}" GTM_ID="$v"
done
med no "GTM_ID com nova linha no fim" "GTM_ID fora do formato" "${MP[@]}" GTM_ID="GTM-ABCD"$'\n'
med no "GTM_ID com nova linha e comando" "GTM_ID fora do formato" "${MP[@]}" GTM_ID="GTM-ABCD"$'\n'"touch /tmp/x"
med no "GTM_ID com nova linha no início" "GTM_ID fora do formato" "${MP[@]}" GTM_ID=$'\n'"GTM-ABCD"
for v in 'ABCDEF' 'abcde' 'abcdefghijklm' 'abc-def' 'abc def' 'abc_def' 'abcdef;ls' 'abcdef$(id)' 'abcdef`id`' "abcdef'" 'abcdef";system("id");//' 'abcdef";update_option("siteurl","x");//' \
  'abcdef\' 'abcdef|id' 'abcdef&id' 'abcdef>x' 'abcdéf' 'abcdef%0a' 'abcdef\n' '../abcdef' '*' 'abc*' 'GTM-ABCD' ; do
  med no "CLARITY_ID malicioso/inválido na produção: $v" "CLARITY_ID fora do formato" "${MP[@]}" CLARITY_ID="$v"
done
med no "CLARITY_ID com nova linha no fim" "CLARITY_ID fora do formato" "${MP[@]}" CLARITY_ID="abcdef"$'\n'
med no "CLARITY_ID com nova linha e comando" "CLARITY_ID fora do formato" "${MP[@]}" CLARITY_ID="abcdef"$'\n'"touch /tmp/x"
# Fora da produção, nem o ID certo passa (a homologação recebe visitas de teste e não entra nas estatísticas).
med no "GTM_ID válido na homologação aninhada (hml)" "só vale na produção" "${MH[@]}" GTM_ID=GTM-ABCD1234
med no "CLARITY_ID válido na homologação aninhada (hml)" "só vale na produção" "${MH[@]}" CLARITY_ID=abcdef123456
med no "GTM_ID válido na homologação de transição" "só vale na produção" "${MT[@]}" GTM_ID=GTM-ABCD1234
med no "CLARITY_ID válido na homologação de transição" "só vale na produção" "${MT[@]}" CLARITY_ID=abcdef123456
med no "GTM_ID e CLARITY_ID válidos na hml" "só vale na produção" "${MH[@]}" GTM_ID=GTM-ABCD1234 CLARITY_ID=abcdef123456
med no "GTM_ID malicioso na hml (a validação do formato vem primeiro)" "GTM_ID fora do formato" "${MH[@]}" GTM_ID='GTM-ABCD";system("id");//'
med no "CLARITY_ID malicioso na hml" "CLARITY_ID fora do formato" "${MH[@]}" CLARITY_ID='abcdef";system("id");//'
med no "GTM_ID válido na homologação com MODO completo" "só vale na produção" AMBIENTE=homologacao CARGA=completo MODO=homologacao ALVO_URL="https://$HML" GTM_ID=GTM-ABCD1234
# Um destino que a trava recusa continua recusado, com ou sem ID (a trava de destino vem antes).
med no "produção com a pasta da hml e GTM_ID válido" "pasta do site deve ser exatamente" "${MP[@]}" ALVO_DESTINO="$P_HML" GTM_ID=GTM-ABCD1234
med no "produção com o endereço da hml e GTM_ID válido" "na produção o endereço deve ser" AMBIENTE=producao CARGA=codigo CONFIRMACAO=PRODUCAO ALVO_URL="https://$HML" GTM_ID=GTM-ABCD1234
med no "produção sem confirmação e GTM_ID válido" "CONFIRMACAO" AMBIENTE=producao CARGA=codigo ALVO_URL="https://$PROD" GTM_ID=GTM-ABCD1234

# --- 1c. A fumaça confere o host FINAL (S1) -------------------------------------------------------------------------------------
# O curl segue redirecionamentos. A hml é uma subpasta da produção: se o endereço da hml caísse na pasta da produção, o WordPress
# dela redirecionaria ao domínio principal e a fumaça "passaria" validando a produção. Com um curl de mentira que responde 200
# mas diz que o endereço final é outro, a fumaça tem de falhar (sem esperar as 5 tentativas) e dizer por quê.
secao "implantar.sh fumaca: o host final da resposta precisa ser o do ambiente"
CURLS="$TMP/curl-falso"; mkdir -p "$CURLS"
cat > "$CURLS/curl" <<'FIM'
#!/usr/bin/env bash
# curl de mentira: responde FAKE_COD (padrão 200) e informa como endereço FINAL o host FAKE_HOST_FINAL com o mesmo caminho.
for u; do :; done
resto="${u#*://}"; case "$resto" in */*) cam="/${resto#*/}" ;; *) cam=/ ;; esac
printf '%s https://%s%s' "${FAKE_COD:-200}" "$FAKE_HOST_FINAL" "$cam"
FIM
chmod +x "$CURLS/curl"
# fum ok|no "descrição" "trecho" VAR=valor...   (ESPERADO=aberto: sem chave SSH, a fumaça só faz as chamadas HTTP)
fum() {
  local esperado="$1" nome="$2" trecho="$3" saida rc; shift 3
  total=$((total + 1))
  saida="$(env -i PATH="$CURLS:$PATH" HOME="$TMP" ESPERADO=aberto "$@" bash bin/implantar.sh fumaca 2>&1)"; rc=$?
  if [ "$esperado" = ok ]; then
    if [ "$rc" -eq 0 ] && [[ "$saida" == *"Fumaça aprovada"* ]]; then passou; else falhou "a fumaça deveria APROVAR: $nome" "$saida"; fi
  else
    if [ "$rc" -ne 0 ] && [[ "$saida" == *"$trecho"* ]] && [[ "$saida" != *"Fumaça aprovada"* ]]; then passou; else falhou "a fumaça deveria FALHAR: $nome" "$saida"; fi
  fi
}
FH=(AMBIENTE=homologacao ALVO_URL="https://$HML")
FP=(AMBIENTE=producao ALVO_URL="https://$PROD")
fum ok "hml: o host final é o da hml" "" "${FH[@]}" FAKE_HOST_FINAL="$HML"
fum ok "hml: o host final em maiúsculas é o da hml" "" "${FH[@]}" FAKE_HOST_FINAL="HML.IeadcViLaIpiranga.com.br"
fum no "hml que cai na produção (redireciona ao domínio principal)" "OUTRO endereço" "${FH[@]}" FAKE_HOST_FINAL="$PROD"
fum no "hml que cai na produção (redireciona ao www)" "OUTRO endereço" "${FH[@]}" FAKE_HOST_FINAL="www.$PROD"
fum no "hml que redireciona a outro subdomínio" "OUTRO endereço" "${FH[@]}" FAKE_HOST_FINAL="staging.$PROD"
fum no "hml que redireciona a outro domínio" "OUTRO endereço" "${FH[@]}" FAKE_HOST_FINAL="evil.com"
fum no "hml que redireciona a um host colado (.evil.com)" "OUTRO endereço" "${FH[@]}" FAKE_HOST_FINAL="$HML.evil.com"
fum no "hml que redireciona ao a.hml" "OUTRO endereço" "${FH[@]}" FAKE_HOST_FINAL="a.$HML"
fum no "transição que redireciona à produção" "OUTRO endereço" AMBIENTE=homologacao ALVO_URL="https://abc.hostingersite.com" FAKE_HOST_FINAL="$PROD"
fum ok "transição: o host final é o próprio" "" AMBIENTE=homologacao ALVO_URL="https://abc.hostingersite.com" FAKE_HOST_FINAL="abc.hostingersite.com"
fum ok "produção: o host final é o domínio principal" "" "${FP[@]}" FAKE_HOST_FINAL="$PROD"
fum ok "produção: o host final é o www do domínio principal" "" "${FP[@]}" FAKE_HOST_FINAL="www.$PROD"
fum no "produção que redireciona à hml" "OUTRO endereço" "${FP[@]}" FAKE_HOST_FINAL="$HML"
fum no "produção que redireciona a outro domínio" "OUTRO endereço" "${FP[@]}" FAKE_HOST_FINAL="evil.com"
fum no "produção que redireciona a outro site da conta" "OUTRO endereço" "${FP[@]}" FAKE_HOST_FINAL="isabellaeneto.com.br"
fum no "o redirecionamento falha também no sitemap" "sitemap: foi redirecionado" "${FH[@]}" FAKE_HOST_FINAL="$PROD"
# --- 2. Os scripts que rodam no servidor, só a trava de pasta -------------------------------------------------------------
# Cada execução usa um pacote e um HOME vazios (copiados para a pasta temporária: os scripts apagam o próprio pacote no fim).
secao "atualizar-remoto.sh e migrar-remoto.sh: a trava repetida no servidor"
n_pac=0
novo_pacote() {   # cria $TMP/pacotes/N com os dois scripts e um pacote mínimo; deixa o caminho em $PAC
  n_pac=$((n_pac + 1)); PAC="$TMP/pacotes/$n_pac"
  mkdir -p "$PAC/wp-content/themes/ieadc-vila-ipiranga" "$PAC/wp-content/mu-plugins/ieadc-core" "$PAC/wp-content/uploads"
  cp bin/atualizar-remoto.sh bin/migrar-remoto.sh "$PAC/"
  echo "/* tema novo */" > "$PAC/wp-content/themes/ieadc-vila-ipiranga/style.css"
  mkdir -p "$PAC/wp-content/themes/ieadc-vila-ipiranga/assets/img/icones"; echo icone > "$PAC/wp-content/themes/ieadc-vila-ipiranga/assets/img/icones/icone-32.png"   # o favicon.ico da raiz (fase 4)
  echo "<?php // mu-plugin novo" > "$PAC/wp-content/mu-plugins/ieadc-core/core.php"
  echo foto > "$PAC/wp-content/uploads/foto.jpg"; echo "-- Dump completed" > "$PAC/banco.sql"
}
# rem ok|no script "descrição" "trecho" pasta url [VAR=valor...]   (HOME vazio: quem passa a trava para em "não achei o WordPress")
rem() {
  local esperado="$1" script="$2" nome="$3" trecho="$4" pasta="$5" url="$6" saida rc; shift 6
  total=$((total + 1)); novo_pacote
  local casa="$TMP/casas/$n_pac"; mkdir -p "$casa"
  if [ "$script" = atualizar ]; then
    saida="$(env -i PATH="$PATH" HOME="$casa" "$@" bash "$PAC/atualizar-remoto.sh" "$pasta" "$url" wp_ 2>&1)"; rc=$?
  else
    saida="$(env -i PATH="$PATH" HOME="$casa" "$@" bash "$PAC/migrar-remoto.sh" "$pasta" "$url" wp_ - homologacao http://localhost:8080 2>&1)"; rc=$?
  fi
  if [ "$esperado" = ok ]; then
    if [ "$rc" -ne 0 ] && [[ "$saida" == *"não achei o WordPress"* ]]; then passou; else falhou "$script deveria ACEITAR (parar só por falta do WordPress): $nome" "$saida"; fi
  else
    if [ "$rc" -ne 0 ] && [[ "$saida" == *"ERRO"* ]] && [[ "$saida" != *"não achei o WordPress"* ]] && [[ "$saida" == *"$trecho"* ]]; then passou; else falhou "$script deveria RECUSAR: $nome" "$saida"; fi
  fi
}
for s in atualizar migrar; do
  rem ok $s "hml: endereço e pasta do par" "" "$P_HML" "https://$HML"
  rem ok $s "produção: domínio principal" "" "$P_PROD" "https://$PROD"
  rem ok $s "produção: www" "" "$P_PROD" "https://www.$PROD"
  rem ok $s "hostingersite (transição)" "" "domains/abc.hostingersite.com/public_html" "https://abc.hostingersite.com"
  rem ok $s "ensaio local com outro domínio principal" "" "domains/teste.local/public_html/hml" "http://hml.teste.local:8082" DOMINIO_PRODUCAO=teste.local
  rem no $s "produção com o endereço da hml" "" "$P_PROD" "https://$HML"
  rem no $s "hml com o domínio principal" "" "$P_HML" "https://$PROD"
  rem no $s "hml com o www" "" "$P_HML" "https://www.$PROD"
  rem no $s "hml com endereço hostingersite" "" "$P_HML" "https://abc.hostingersite.com"
  rem no $s "hml com localhost" "" "$P_HML" "http://localhost:8081"
  rem no $s "hml com outro subdomínio" "" "$P_HML" "https://staging.$PROD"
  rem no $s "endereço hml com a pasta da produção" "" "$P_PROD" "https://$HML"
  rem no $s "endereço hml com a pasta derivada do host" "" "domains/$HML/public_html" "https://$HML"
  rem no $s "hml com barra final" "" "$P_HML/" "https://$HML"
  rem no $s "hml em maiúsculas" "" "$P_PROD/HML" "https://$HML"
  rem no $s "hml com .." "" "$P_HML/../.." "https://$HML"
  rem no $s "hml com caminho absoluto" "" "/home/u123/$P_HML" "https://$HML"
  rem no $s "hml com //" "" "domains//$PROD/public_html/hml" "https://$HML"
  rem no $s "hml com espaço" "" "$P_HML " "https://$HML"
  rem no $s "dentro da hml" "" "$P_HML/wp-content" "https://$HML"
  rem no $s "hml2" "" "$P_PROD/hml2" "https://$HML"
  rem no $s "pasta de terceiro, endereço principal" "" "domains/terceiro.com.br/public_html" "https://$PROD"
  rem no $s "pasta de terceiro, endereço hml" "" "domains/terceiro.com.br/public_html" "https://$HML"
  rem no $s "pasta de terceiro, endereço hostingersite" "" "domains/terceiro.com.br/public_html" "https://abc.hostingersite.com"
  rem no $s "hostingersite com a pasta da produção" "" "$P_PROD" "https://abc.hostingersite.com"
  rem no $s "hostingersite com a pasta da hml" "" "$P_HML" "https://abc.hostingersite.com"
  rem no $s "hostingersite que contém o domínio principal" "" "domains/$PROD.hostingersite.com/public_html" "https://$PROD.hostingersite.com"
  rem no $s "pasta da produção com .." "" "$P_PROD/hml/.." "https://$PROD"
  rem no $s "endereço inválido" "" "$P_PROD" "https://$PROD;ls"
  rem no $s "DOMINIO_PRODUCAO inválido" "inválido" "$P_PROD" "https://$PROD" DOMINIO_PRODUCAO='a;b'
done
# A pasta da produção só aceita o domínio principal (com ou sem www.) e, no ensaio local do caminho manual, localhost e 127.0.0.1;
# qualquer outro endereço, inclusive de outro site da mesma conta, é recusado nos dois scripts (S2).
for s in atualizar migrar; do
  rem ok $s "ensaio local do caminho manual (localhost, pasta da produção)" "" "$P_PROD" "http://localhost:8081"
  rem ok $s "ensaio local do caminho manual (127.0.0.1, pasta da produção)" "" "$P_PROD" "http://127.0.0.1:8081"
  rem no $s "subdomínio qualquer com a pasta da produção" "" "$P_PROD" "https://staging.$PROD"
  rem no $s "outro site da conta com a pasta da produção" "" "$P_PROD" "https://isabellaeneto.com.br"
  rem no $s "evil.com com a pasta da produção" "" "$P_PROD" "https://evil.com"
  rem no $s "www.hml com a pasta da produção" "" "$P_PROD" "https://www.$HML"
  rem no $s "a.hml com a pasta da produção" "" "$P_PROD" "https://a.$HML"
  rem no $s "hml colado em outro domínio com a pasta da produção" "" "$P_PROD" "https://$HML.evil.com"
  rem no $s "localhost.evil.com com a pasta da produção" "" "$P_PROD" "https://localhost.evil.com"
  rem no $s "127.0.0.1.evil.com com a pasta da produção" "" "$P_PROD" "https://127.0.0.1.evil.com"
  rem no $s "outro IP com a pasta da produção" "" "$P_PROD" "http://10.0.0.1"
  rem no $s "hostingersite com a pasta da produção" "" "$P_PROD" "https://abc.hostingersite.com"
  rem no $s "localhost com a pasta da hml" "" "$P_HML" "http://localhost:8081"
done

# --- 3. Simulação: HOME de mentira e wp-cli de mentira ------------------------------------------------------------------
secao "simulação no servidor: a produção nunca toca a hml e a hml nunca toca o pai"
FALSOS="$TMP/falsos"; mkdir -p "$FALSOS"
cat > "$FALSOS/wp" <<'FIM'
#!/usr/bin/env bash
# wp-cli de mentira: registra o caminho de cada chamada e imita só o que os scripts leem ou gravam.
caminho=""; args=()
for a in "$@"; do case "$a" in --path=*) caminho="${a#--path=}" ;; *) args+=("$a") ;; esac; done
echo "$caminho :: ${args[*]}" >> "$WP_LOG"
case "${args[0]:-} ${args[1]:-}" in
  "config get") echo wp_ ;;
  "eval "*) [ -z "$caminho" ] || echo 'RewriteRule . /index.php [L]' > "$caminho/.htaccess" ;;
  "user list") echo 1 ;;
  "option get") case "${args[2]:-}" in blog_public | ieadc_site_fechado) echo 0 ;; esac ;;   # o que a fumaça lê (homologação aberta e fora do Google)
  "db export") echo "-- Dump completed" > "${args[2]}" ;;
  "maintenance-mode activate") [ -z "$caminho" ] || : > "$caminho/.maintenance" ;;
  "maintenance-mode deactivate") [ -z "$caminho" ] || rm -f "$caminho/.maintenance" ;;
esac
exit 0
FIM
printf '#!/bin/sh\nexit 0\n' > "$FALSOS/php"; chmod +x "$FALSOS/wp" "$FALSOS/php"

hash_dir() {   # hash do conteúdo e da lista de arquivos de uma pasta (opcional: subpasta a ignorar, ex.: ./public_html/hml)
  ( cd "$1" && find . -path "${2:-./__nada__}" -prune -o -print | LC_ALL=C sort | while IFS= read -r f; do
      if [ -d "$f" ]; then echo "d $f"; else echo "$(sha256sum < "$f" | cut -c1-16) $f"; fi
    done | sha256sum | cut -c1-16 )
}
montar_casa() {   # montar_casa <pasta> [banco-da-hml]  (banco da hml: o nome do DB_NAME dela; padrão u1_hml)
  local c="$1" banco_hml="${2:-u1_hml}" site="$1/$P_PROD"
  mkdir -p "$site/wp-content/themes/ieadc-vila-ipiranga" "$site/wp-content/mu-plugins/ieadc-core" "$site/wp-content/uploads" "$site/wp-content/plugins" \
           "$site/hml/wp-content/themes/ieadc-vila-ipiranga" "$site/hml/wp-content/mu-plugins/ieadc-core" "$site/hml/wp-content/uploads" "$c/domains/terceiro.com.br/public_html"
  printf "<?php\ndefine( 'DB_NAME', 'u1_prod' );\ndefine( 'DB_HOST', 'localhost' );\n" > "$site/wp-config.php"
  printf "<?php\ndefine( 'DB_NAME', '%s' );\ndefine( 'DB_HOST', 'localhost' );\n" "$banco_hml" > "$site/hml/wp-config.php"
  echo "/* tema antigo da produção */" > "$site/wp-content/themes/ieadc-vila-ipiranga/style.css"
  echo "/* tema antigo da hml */" > "$site/hml/wp-content/themes/ieadc-vila-ipiranga/style.css"
  echo "<?php // antigo" > "$site/wp-content/mu-plugins/ieadc-core/core.php"; echo "<?php // antigo hml" > "$site/hml/wp-content/mu-plugins/ieadc-core/core.php"
  echo "prod" > "$site/index.php"; echo "hml" > "$site/hml/index.php"; echo "RewriteRule antigo" > "$site/.htaccess"; echo "RewriteRule hml antigo" > "$site/hml/.htaccess"
  echo "foto da produção" > "$site/wp-content/uploads/a.jpg"; echo "foto da hml" > "$site/hml/wp-content/uploads/b.jpg"
  echo "site de terceiro" > "$c/domains/terceiro.com.br/public_html/index.html"
}
# sim <script> <nome> <pasta> <url> <casa>   roda o script no HOME de mentira; deixa a saída em $SAIDA, o código em $RC e o log do wp em $WP_LOG
sim() {
  local script="$1" pasta="$3" url="$4" casa="$5"
  novo_pacote; WP_LOG="$TMP/wp-$n_pac.log"; : > "$WP_LOG"
  if [ "$script" = atualizar ]; then
    SAIDA="$(env -i PATH="$FALSOS:$PATH" HOME="$casa" WP_LOG="$WP_LOG" bash "$PAC/atualizar-remoto.sh" "$pasta" "$url" wp_ 2>&1)"; RC=$?
  else
    SAIDA="$(env -i PATH="$FALSOS:$PATH" HOME="$casa" WP_LOG="$WP_LOG" bash "$PAC/migrar-remoto.sh" "$pasta" "$url" wp_ - homologacao http://localhost:8080 2>&1)"; RC=$?
  fi
}
confere() {   # confere "descrição" <comando de teste...>
  local nome="$1"; shift; total=$((total + 1))
  if "$@" > /dev/null 2>&1; then passou; else falhou "$nome" "${SAIDA:-}"; fi
}
so_caminho() { [ -s "$WP_LOG" ] && ! grep -v -F -- "$1 :: " "$WP_LOG" | grep -q .; }   # todas as chamadas do wp usaram esse --path

for s in atualizar migrar; do
  # --- produção (com a hml dentro dela) ---
  C="$TMP/casa-prod-$s"; montar_casa "$C"; SITE_P="$C/$P_PROD"
  h_hml="$(hash_dir "$SITE_P/hml")"; h_ter="$(hash_dir "$C/domains/terceiro.com.br")"; h_prod="$(hash_dir "$SITE_P" ./hml)"
  sim $s prod "$P_PROD" "https://$PROD" "$C"
  confere "$s/produção: terminou bem" test "$RC" -eq 0
  confere "$s/produção: avisou que há uma hml e não a tocou" grep -q "NÃO será tocada" <<< "$SAIDA"
  confere "$s/produção: a pasta hml ficou idêntica (conteúdo e lista de arquivos)" test "$(hash_dir "$SITE_P/hml")" = "$h_hml"
  confere "$s/produção: o .htaccess da hml não mudou" grep -qx "RewriteRule hml antigo" "$SITE_P/hml/.htaccess"
  confere "$s/produção: a pasta de terceiro ficou idêntica" test "$(hash_dir "$C/domains/terceiro.com.br")" = "$h_ter"
  confere "$s/produção: a própria produção foi atualizada" test "$(hash_dir "$SITE_P" ./hml)" != "$h_prod"
  confere "$s/produção: tema novo na produção" grep -q "tema novo" "$SITE_P/wp-content/themes/ieadc-vila-ipiranga/style.css"
  confere "$s/produção: o .htaccess da produção foi regravado" grep -q "index.php" "$SITE_P/.htaccess"
  confere "$s/produção: todas as chamadas do wp-cli foram na pasta da produção" so_caminho "$SITE_P"
  confere "$s/produção: o favicon.ico (fase 4) foi para a raiz da produção e não para a hml" bash -c '[ -f "$1/favicon.ico" ] && [ ! -e "$1/hml/favicon.ico" ]' _ "$SITE_P"
  confere "$s/produção: nada sobrou na hml (sem .maintenance)" test ! -e "$SITE_P/hml/.maintenance"

  # --- hml (dentro da pasta da produção) ---
  C="$TMP/casa-hml-$s"; montar_casa "$C"; SITE_P="$C/$P_PROD"; SITE_H="$SITE_P/hml"
  h_fora="$(hash_dir "$C/domains" ./$PROD/public_html/hml)"; h_hml="$(hash_dir "$SITE_H")"
  sim $s hml "$P_HML" "https://$HML" "$C"
  confere "$s/hml: terminou bem" test "$RC" -eq 0
  confere "$s/hml: nada fora da pasta hml mudou (produção e terceiro: conteúdo e lista de arquivos)" test "$(hash_dir "$C/domains" ./$PROD/public_html/hml)" = "$h_fora"
  confere "$s/hml: o .htaccess da produção não mudou" grep -qx "RewriteRule antigo" "$SITE_P/.htaccess"
  confere "$s/hml: a própria hml foi atualizada" test "$(hash_dir "$SITE_H")" != "$h_hml"
  confere "$s/hml: tema novo na hml" grep -q "tema novo" "$SITE_H/wp-content/themes/ieadc-vila-ipiranga/style.css"
  confere "$s/hml: o .htaccess da hml foi regravado" grep -q "index.php" "$SITE_H/.htaccess"
  confere "$s/hml: todas as chamadas do wp-cli foram na pasta hml" so_caminho "$SITE_H"
  confere "$s/hml: o favicon.ico (fase 4) foi para a raiz da hml e não para a produção" bash -c '[ -f "$1/hml/favicon.ico" ] && [ ! -e "$1/favicon.ico" ]' _ "$SITE_P"
  confere "$s/hml: sem .maintenance no pai" test ! -e "$SITE_P/.maintenance"
  if [ "$s" = atualizar ]; then
    confere "$s/hml: a cópia de código tem o rótulo hml-" bash -c "ls '$C'/backup-codigo-antes-hml-*.tgz"
    confere "$s/hml: nenhuma cópia sem rótulo (as da produção)" bash -c "! ls '$C'/backup-codigo-antes-[0-9]*.tgz"
  else
    confere "$s/hml: a cópia do banco tem o rótulo hml-" bash -c "ls '$C'/backup-antes-da-migracao-hml-*.sql"
    confere "$s/hml: nenhuma cópia do banco sem rótulo (as da produção)" bash -c "! ls '$C'/backup-antes-da-migracao-[0-9]*.sql"
  fi

  # --- banco repartido ---
  C="$TMP/casa-banco1-$s"; montar_casa "$C" u1_prod; SITE_P="$C/$P_PROD"; h_antes="$(hash_dir "$C")"
  sim $s prod "$P_PROD" "https://$PROD" "$C"
  confere "$s/produção recusa a hml com o mesmo banco" bash -c "[ $RC -ne 0 ] && grep -q 'mesmo banco' <<< \"\$1\"" _ "$SAIDA"
  confere "$s/produção recusa sem alterar nada" test "$(hash_dir "$C")" = "$h_antes"
  C="$TMP/casa-banco2-$s"; montar_casa "$C" u1_prod; h_antes="$(hash_dir "$C")"
  sim $s hml "$P_HML" "https://$HML" "$C"
  confere "$s/hml recusa o mesmo banco da produção" bash -c "[ $RC -ne 0 ] && grep -q 'mesmo banco' <<< \"\$1\"" _ "$SAIDA"
  confere "$s/hml recusa sem alterar nada" test "$(hash_dir "$C")" = "$h_antes"

  # --- hml que é um link simbólico (poderia apontar para fora) ---
  C="$TMP/casa-link-$s"; montar_casa "$C"; SITE_P="$C/$P_PROD"
  mkdir -p "$C/fora/wp-content" && cp "$SITE_P/hml/wp-config.php" "$C/fora/" && rm -rf "$SITE_P/hml" && ln -s "$C/fora" "$SITE_P/hml" 2> /dev/null
  if [ -L "$SITE_P/hml" ]; then
    sim $s hml "$P_HML" "https://$HML" "$C"
    confere "$s/hml como link simbólico é recusada" bash -c "[ $RC -ne 0 ] && grep -q 'não é uma pasta comum' <<< \"\$1\"" _ "$SAIDA"
  else
    echo "  (pulado: este sistema não cria links simbólicos)"
  fi

  # --- produção sem hml: sem aviso ---
  C="$TMP/casa-so-prod-$s"; montar_casa "$C"; rm -rf "$C/$P_PROD/hml"
  sim $s prod "$P_PROD" "https://$PROD" "$C"
  confere "$s/produção sem hml: termina bem e não avisa nada sobre hml" bash -c "[ $RC -eq 0 ] && ! grep -q 'NÃO será tocada' <<< \"\$1\"" _ "$SAIDA"

  # --- hml sem WordPress (staging ainda não criado) ---
  C="$TMP/casa-semwp-$s"; montar_casa "$C"; rm -f "$C/$P_PROD/hml/wp-config.php"; h_antes="$(hash_dir "$C")"
  sim $s hml "$P_HML" "https://$HML" "$C"
  confere "$s/hml sem wp-config.php: para sem alterar nada" bash -c "[ $RC -ne 0 ] && grep -q 'não achei o WordPress' <<< \"\$1\"" _ "$SAIDA"
  confere "$s/hml sem wp-config.php: nada foi alterado" test "$(hash_dir "$C")" = "$h_antes"
done

# --- S4, S5, S7, S10: as barreiras que falham fechadas, links simbólicos, rótulo da transição e URL em minúsculas ---------------
wp_config() {   # wp_config <arquivo> <conteúdo do wp-config.php>
  printf '%s\n' "<?php" "$2" > "$1"
}
# recusa_sim <script> <nome> <casa> prod|hml <trecho>: roda e confere que recusou com a mensagem e sem alterar nada
recusa_sim() {
  local s="$1" nome="$2" casa="$3" alvo="$4" trecho="$5" antes
  antes="$(hash_dir "$casa")"
  if [ "$alvo" = prod ]; then sim "$s" prod "$P_PROD" "https://$PROD" "$casa"; else sim "$s" hml "$P_HML" "https://$HML" "$casa"; fi
  confere "$s: recusa ($nome)" bash -c '[ "$1" -ne 0 ] && [[ "$2" == *"$3"* ]]' _ "$RC" "$SAIDA" "$trecho"
  confere "$s: recusa sem alterar nada ($nome)" test "$(hash_dir "$casa")" = "$antes"
}
for s in atualizar migrar; do
  # S4: o banco falha fechado. Sem DB_NAME literal legível em qualquer um dos dois wp-config, recusa; só o nome conta (não o host).
  for alvo in prod hml; do
    C="$TMP/casa-db-getenv-$alvo-$s"; montar_casa "$C"; wp_config "$C/$P_PROD/wp-config.php" "define( 'DB_NAME', getenv('WORDPRESS_DB_NAME') );"
    recusa_sim $s "produção com DB_NAME vindo de getenv, alvo $alvo" "$C" $alvo "não consegui ler o DB_NAME"
    C="$TMP/casa-db-hmlgetenv-$alvo-$s"; montar_casa "$C"; wp_config "$C/$P_PROD/hml/wp-config.php" "define( 'DB_NAME', getenv('WORDPRESS_DB_NAME') );"
    recusa_sim $s "hml com DB_NAME vindo de getenv, alvo $alvo" "$C" $alvo "não consegui ler o DB_NAME"
    C="$TMP/casa-db-ausente-$alvo-$s"; montar_casa "$C"; wp_config "$C/$P_PROD/hml/wp-config.php" "define( 'DB_USER', 'u1' );"
    recusa_sim $s "hml sem DB_NAME, alvo $alvo" "$C" $alvo "não consegui ler o DB_NAME"
    C="$TMP/casa-db-comentado-$alvo-$s"; montar_casa "$C"; wp_config "$C/$P_PROD/hml/wp-config.php" "// define( 'DB_NAME', 'u1_hml' );"
    recusa_sim $s "hml com o DB_NAME só em comentário, alvo $alvo" "$C" $alvo "não consegui ler o DB_NAME"
    C="$TMP/casa-db-duplo-$alvo-$s"; montar_casa "$C"; wp_config "$C/$P_PROD/wp-config.php" "define( 'DB_NAME', 'u1_prod' ); define( 'DB_NAME', 'outro' );"
    recusa_sim $s "produção com duas definições de DB_NAME, alvo $alvo" "$C" $alvo "não consegui ler o DB_NAME"
    C="$TMP/casa-db-mesmonome-$alvo-$s"; montar_casa "$C" u1_prod; wp_config "$C/$P_PROD/hml/wp-config.php" "define( 'DB_NAME', 'u1_prod' );
define( 'DB_HOST', '127.0.0.1' );"
    recusa_sim $s "mesmo DB_NAME com DB_HOST diferente, alvo $alvo" "$C" $alvo "mesmo banco"
  done
  C="$TMP/casa-db-semprod-$s"; montar_casa "$C"; rm -f "$C/$P_PROD/wp-config.php"
  recusa_sim $s "hml sem o wp-config da produção no pai" "$C" hml "não achei o wp-config.php da produção"   # sem ele não dá para comparar os bancos
  # Aceita: o wp-config padrão da Hostinger (aspas simples ou duplas, vários define na mesma linha, comentários) continua funcionando.
  C="$TMP/casa-db-ok-$s"; montar_casa "$C"
  wp_config "$C/$P_PROD/wp-config.php" "/** The name of the database for WordPress */
// define( 'DB_NAME', 'comentado' );
define('DB_NAME', 'u1_prod'); define('DB_USER', 'u1'); define('DB_PASSWORD', 'x');"
  wp_config "$C/$P_PROD/hml/wp-config.php" "/** banco do staging */
define( \"DB_NAME\", \"u1_hml\" ); define( 'DB_HOST', 'localhost' );"
  sim $s hml "$P_HML" "https://$HML" "$C"
  confere "$s: wp-config padrão da Hostinger (define na mesma linha, aspas duplas, comentário) continua aceito na hml" test "$RC" -eq 0
  sim $s prod "$P_PROD" "https://$PROD" "$C"
  confere "$s: wp-config padrão da Hostinger continua aceito na produção" test "$RC" -eq 0

  # S5: link simbólico dentro do site (até 3 níveis) é recusado: o rm -rf e o cp -a escreveriam fora da pasta.
  C="$TMP/casa-lnk-uploads-$s"; montar_casa "$C"; SITE_P="$C/$P_PROD"
  rm -rf "$SITE_P/hml/wp-content/uploads"; ln -s ../../wp-content/uploads "$SITE_P/hml/wp-content/uploads" 2> /dev/null
  if [ -L "$SITE_P/hml/wp-content/uploads" ]; then
    recusa_sim $s "hml com wp-content/uploads apontando para a produção" "$C" hml "link simbólico"
    h_hml_ant="$(hash_dir "$SITE_P/hml")"
    sim $s prod "$P_PROD" "https://$PROD" "$C"
    confere "$s: a produção segue normalmente quando o link simbólico está só na hml (a pasta hml não é dela)" test "$RC" -eq 0
    confere "$s: ... e a hml continua intacta" test "$(hash_dir "$SITE_P/hml")" = "$h_hml_ant"
    C="$TMP/casa-lnk-mu-$s"; montar_casa "$C"; SITE_P="$C/$P_PROD"
    rm -rf "$SITE_P/hml/wp-content/mu-plugins"; ln -s ../../wp-content/mu-plugins "$SITE_P/hml/wp-content/mu-plugins"
    recusa_sim $s "hml com mu-plugins apontando para a produção" "$C" hml "link simbólico"
    C="$TMP/casa-lnk-prod-$s"; montar_casa "$C"; SITE_P="$C/$P_PROD"
    rm -rf "$SITE_P/wp-content/themes"; ln -s ../hml/wp-content/themes "$SITE_P/wp-content/themes"
    recusa_sim $s "produção com wp-content/themes apontando para a hml" "$C" prod "link simbólico"
    C="$TMP/casa-lnk-plugins-$s"; montar_casa "$C"; SITE_P="$C/$P_PROD"
    rm -rf "$SITE_P/hml/wp-content/plugins"; ln -s ../../wp-content/plugins "$SITE_P/hml/wp-content/plugins"
    recusa_sim $s "hml com plugins apontando para a produção" "$C" hml "link simbólico"
  else
    echo "  (pulado: este sistema não cria links simbólicos)"
  fi

  # S7: a homologação de transição (*.hostingersite.com) também leva o rótulo hml- nas cópias de segurança.
  C="$TMP/casa-legado-$s"; montar_casa "$C"; LEG="$C/domains/abc.hostingersite.com/public_html"
  mkdir -p "$LEG/wp-content/themes/ieadc-vila-ipiranga" "$LEG/wp-content/mu-plugins"; wp_config "$LEG/wp-config.php" "define( 'DB_NAME', 'u1_leg' );"
  : > "$C/backup-antes-da-migracao-2020-01-01-0000.sql"; : > "$C/backup-codigo-antes-2020-01-01-000000.tgz"
  sim $s legado "domains/abc.hostingersite.com/public_html" "https://abc.hostingersite.com" "$C"
  confere "$s: homologação de transição termina bem" test "$RC" -eq 0
  if [ "$s" = atualizar ]; then
    confere "$s: a cópia da homologação de transição tem o rótulo hml-" bash -c "ls '$C'/backup-codigo-antes-hml-*.tgz"
    confere "$s: ... e a cópia da produção continua lá, intacta" test -e "$C/backup-codigo-antes-2020-01-01-000000.tgz"
  else
    confere "$s: a cópia do banco da homologação de transição tem o rótulo hml-" bash -c "ls '$C'/backup-antes-da-migracao-hml-*.sql"
    confere "$s: ... e a cópia da produção continua lá, intacta" test -e "$C/backup-antes-da-migracao-2020-01-01-0000.sql"
  fi
done

# S7: os dumps do banco da homologação (um por carga completa) ficam nos 5 mais recentes; os da produção nunca são apagados.
C="$TMP/casa-dumps"; montar_casa "$C"
for i in 1 2 3 4 5 6; do
  : > "$C/backup-antes-da-migracao-2020-01-0$i-0000.sql"; : > "$C/backup-antes-da-migracao-hml-2020-01-0$i-0000.sql"
  touch -d "2020-01-0$i 00:00:00" "$C/backup-antes-da-migracao-2020-01-0$i-0000.sql" "$C/backup-antes-da-migracao-hml-2020-01-0$i-0000.sql"
done
sim migrar hml "$P_HML" "https://$HML" "$C"
confere "dumps: depois da carga da hml ficam 5 da hml" test "$(ls "$C"/backup-antes-da-migracao-hml-*.sql | wc -l)" -eq 5
confere "dumps: a carga da hml não apagou nenhum da produção (6)" test "$(ls "$C"/backup-antes-da-migracao-[0-9]*.sql | wc -l)" -eq 6
confere "dumps: o mais novo da hml foi guardado e o mais antigo saiu" bash -c '[ ! -e "$1/backup-antes-da-migracao-hml-2020-01-01-0000.sql" ] && [ -e "$1/backup-antes-da-migracao-hml-2020-01-06-0000.sql" ]' _ "$C"
sim migrar prod "$P_PROD" "https://$PROD" "$C"
confere "dumps: a carga da produção não apaga nenhum dump (7 da produção)" test "$(ls "$C"/backup-antes-da-migracao-[0-9]*.sql | wc -l)" -eq 7
confere "dumps: a carga da produção não mexeu nos da hml (5)" test "$(ls "$C"/backup-antes-da-migracao-hml-*.sql | wc -l)" -eq 5

# S10: o endereço gravado em home/siteurl pelo servidor é sempre minúsculo, mesmo que chegue em maiúsculas.
C="$TMP/casa-url-minuscula"; montar_casa "$C"
sim migrar hml "$P_HML" "https://HML.IeadcViLaIpiranga.COM.BR" "$C"
confere "S10: o servidor aceita o endereço em maiúsculas e grava home em minúsculas" bash -c '[ "$1" -eq 0 ] && grep -q ":: option update home https://hml.ieadcvilaipiranga.com.br$" "$2"' _ "$RC" "$WP_LOG"
confere "S10: ... e siteurl em minúsculas" grep -q ":: option update siteurl https://hml.ieadcvilaipiranga.com.br$" "$WP_LOG"
confere "S10: nenhuma letra maiúscula foi gravada em home/siteurl" bash -c '! grep -E "option update (home|siteurl) .*[A-Z]" "$1"' _ "$WP_LOG"

# As cópias de código (5 por site) não se apagam entre si: a produção e a hml dividem o mesmo HOME.
C="$TMP/casa-copias"; montar_casa "$C"
for i in 1 2 3 4 5 6; do
  : > "$C/backup-codigo-antes-2020-01-0$i-000000.tgz"; : > "$C/backup-codigo-antes-hml-2020-01-0$i-000000.tgz"
  touch -d "2020-01-0$i 00:00:00" "$C/backup-codigo-antes-2020-01-0$i-000000.tgz" "$C/backup-codigo-antes-hml-2020-01-0$i-000000.tgz"
done
sim atualizar prod "$P_PROD" "https://$PROD" "$C"
confere "cópias: depois da produção ficam 5 da produção" test "$(ls "$C"/backup-codigo-antes-[0-9]*.tgz | wc -l)" -eq 5
confere "cópias: a produção não apagou nenhuma da hml (6)" test "$(ls "$C"/backup-codigo-antes-hml-*.tgz | wc -l)" -eq 6
sim atualizar hml "$P_HML" "https://$HML" "$C"
confere "cópias: depois da hml ficam 5 da hml" test "$(ls "$C"/backup-codigo-antes-hml-*.tgz | wc -l)" -eq 5
confere "cópias: a hml não apagou nenhuma da produção (5)" test "$(ls "$C"/backup-codigo-antes-[0-9]*.tgz | wc -l)" -eq 5

# --- 3b. enviar de ponta a ponta, com ssh e scp de mentira ---------------------------------------------------------------------
# O servidor de mentira é o HOME de mentira: o ssh de mentira roda o comando remoto com bash e o scp de mentira copia o pacote para lá.
# Prova que uma instalação RECUSADA pelas barreiras do servidor derruba o envio (o código de saída do script remoto não pode ser
# engolido pela limpeza que vem depois dele) e que nenhum ieadc_deploy "ok" é gravado nesse caso.
secao "implantar.sh enviar: instalação recusada no servidor derruba o envio (ssh e scp de mentira)"
SSHS="$TMP/ssh-falso"; mkdir -p "$SSHS"
printf '#!/bin/sh\necho "127.0.0.1 ssh-ed25519 AAAAfalso"\n' > "$SSHS/ssh-keyscan"
printf '#!/bin/sh\necho "256 SHA256:falsa servidor (ED25519)"\n' > "$SSHS/ssh-keygen"
cat > "$SSHS/ssh" <<'FIM'
#!/usr/bin/env bash
# ssh de mentira: pula as opções, ignora usuário@host e roda o comando no HOME de mentira ($FAKE_HOME).
while [ $# -gt 0 ]; do case "$1" in -p | -i | -o) shift 2 ;; -*) shift ;; *) break ;; esac; done
shift
exec env HOME="$FAKE_HOME" bash -c "$1"
FIM
cat > "$SSHS/scp" <<'FIM'
#!/usr/bin/env bash
# scp de mentira: copia o arquivo para o HOME de mentira (o destino vem como usuário@host:caminho).
while [ $# -gt 2 ]; do case "$1" in -P | -i | -o) shift 2 ;; -*) shift ;; *) break ;; esac; done
cp "$1" "$FAKE_HOME/${2#*:}"
FIM
chmod +x "$SSHS/"*
novo_pacote; printf 'PACOTE_PREFIXO=wp_\nPACOTE_ORIGENS=http://localhost:8080\nPACOTE_SHA=desconhecido\n' > "$PAC/pacote.env"; tar -czf "$TMP/e2e.tgz" -C "$PAC" .
: > "$TMP/chave-falsa"
# e2e <casa> VAR=valor...: roda "enviar" contra a casa de mentira (SAIDA, RC e WP_LOG)
e2e() {
  local casa="$1"; shift
  WP_LOG="$TMP/wp-e2e-$((++n_pac)).log"; : > "$WP_LOG"
  SAIDA="$(env -i PATH="$SSHS:$FALSOS:$CURLS:$PATH" HOME="$TMP" FAKE_HOME="$casa" WP_LOG="$WP_LOG" ALVO_SERVIDOR=127.0.0.1 ALVO_PORTA=22 ALVO_USUARIO=u123 \
    ALVO_CHAVE="$TMP/chave-falsa" ALVO_IMPRESSAO=SHA256:falsa AMBIENTE=homologacao ALVO_URL="https://$HML" ALVO_DESTINO="$P_HML" FAKE_HOST_FINAL="$HML" \
    "$@" bash bin/implantar.sh enviar "$TMP/e2e.tgz" 2>&1)"; RC=$?
}
grava_ok() { grep -q 'option update ieadc_deploy .*"resultado":"ok"' "$WP_LOG"; }
grava_falhou() { grep -q 'option update ieadc_deploy .*"resultado":"falhou"' "$WP_LOG"; }
C="$TMP/casa-e2e-ok"; montar_casa "$C"
e2e "$C" CARGA=codigo
confere "e2e: código na hml termina bem, a fumaça passa e o ieadc_deploy fica ok" bash -c '[ "$1" -eq 0 ] && [[ "$2" == *"Implantação concluída"* ]]' _ "$RC" "$SAIDA"
confere "e2e: ... e o registro ok foi gravado" grava_ok
C="$TMP/casa-e2e-ok2"; montar_casa "$C"
e2e "$C" CARGA=completo MODO=homologacao
confere "e2e: carga completa na hml termina bem" bash -c '[ "$1" -eq 0 ] && [[ "$2" == *"Implantação concluída"* ]]' _ "$RC" "$SAIDA"
for carga in codigo completo; do
  C="$TMP/casa-e2e-banco-$carga"; montar_casa "$C" u1_prod   # a hml no mesmo banco da produção: o servidor recusa
  e2e "$C" CARGA=$carga MODO=homologacao
  confere "e2e ($carga): recusa do servidor (mesmo banco) derruba o envio" bash -c '[ "$1" -ne 0 ] && [[ "$2" == *"a instalação no servidor falhou"* ]] && [[ "$2" == *"mesmo banco"* ]]' _ "$RC" "$SAIDA"
  confere "e2e ($carga): ... e nenhum ieadc_deploy ok foi gravado (ficou falhou)" bash -c '! grep -q "\"resultado\":\"ok\"" "$1" && grep -q "\"resultado\":\"falhou\"" "$1"' _ "$WP_LOG"
  confere "e2e ($carga): ... e a fumaça nem chegou a rodar" bash -c '[[ "$1" != *"Fumaça"* ]]' _ "$SAIDA"
  C="$TMP/casa-e2e-semwp-$carga"; montar_casa "$C"; rm -f "$C/$P_PROD/hml/wp-config.php"   # a hml sem WordPress
  e2e "$C" CARGA=$carga MODO=homologacao
  confere "e2e ($carga): hml sem WordPress derruba o envio" bash -c '[ "$1" -ne 0 ] && [[ "$2" == *"a instalação no servidor falhou"* ]]' _ "$RC" "$SAIDA"
  C="$TMP/casa-e2e-link-$carga"; montar_casa "$C"; rm -rf "$C/$P_PROD/hml/wp-content/uploads"; ln -s ../../wp-content/uploads "$C/$P_PROD/hml/wp-content/uploads" 2> /dev/null
  if [ -L "$C/$P_PROD/hml/wp-content/uploads" ]; then
    e2e "$C" CARGA=$carga MODO=homologacao
    confere "e2e ($carga): link simbólico na hml derruba o envio" bash -c '[ "$1" -ne 0 ] && [[ "$2" == *"link simbólico"* ]]' _ "$RC" "$SAIDA"
    confere "e2e ($carga): ... e a produção não recebeu nada pelo link" bash -c '[ ! -e "$1/wp-content/uploads/foto.jpg" ] && [ "$(cat "$1/wp-content/uploads/a.jpg")" = "foto da produção" ]' _ "$C/$P_PROD"
  fi
done

# --- 4. bin/migrar.ps1 (caminho manual) -------------------------------------------------------------------------------
secao "migrar.ps1: a mesma trava no PowerShell"
PS=""; for c in pwsh powershell powershell.exe; do command -v "$c" > /dev/null 2>&1 && { PS="$c"; break; }; done
if [ -z "$PS" ]; then
  echo "  (pulado: não há pwsh nem powershell neste sistema)"
else
  mkdir -p "$TMP/ps/bin"; cp bin/migrar.ps1 "$TMP/ps/bin/"   # sem .env ao lado: nada do computador de quem roda entra no teste
  # psc ok|no "descrição" ambiente url [pasta]
  psc() {
    local esperado="$1" nome="$2" amb="$3" url="$4" saida rc args
    # PS_SRV troca o servidor (padrão: um que não é do computador); PS_TRECHO exige um trecho na saída (ex.: o endereço final).
    args=(-Ambiente "$amb" -Servidor "${PS_SRV:-servidor.invalido}" -Porta 22 -Usuario u123 -Url "$url" -Conferir -SemRede)
    [ $# -lt 5 ] || [ -z "$5" ] || args+=(-Destino "$5")
    total=$((total + 1))
    saida="$(cd "$TMP/ps" && "$PS" -NoProfile -ExecutionPolicy Bypass -File bin/migrar.ps1 "${args[@]}" 2>&1)"; rc=$?
    if [ "$esperado" = ok ]; then
      if [ "$rc" -eq 0 ] && [[ "$saida" == *"TRAVAS OK"* ]] && [[ "$saida" == *"${PS_TRECHO:-}"* ]]; then passou; else falhou "migrar.ps1 deveria ACEITAR: $nome" "$saida"; fi
    else
      if [ "$rc" -ne 0 ] && [[ "$saida" != *"TRAVAS OK"* ]] && [[ "$saida" == *"${PS_TRECHO:-}"* ]]; then passou; else falhou "migrar.ps1 deveria RECUSAR: $nome" "$saida"; fi
    fi
  }
  psc ok "hml, pasta padrão" homologacao "https://$HML"
  psc ok "hml, pasta exata" homologacao "https://$HML" "$P_HML"
  psc ok "hostingersite, pasta padrão" homologacao "https://abc123.hostingersite.com"
  psc ok "produção, pasta padrão" producao "https://$PROD"
  psc ok "produção, pasta exata" producao "https://$PROD" "$P_PROD"
  psc no "domínio principal na homologação" homologacao "https://$PROD"
  psc no "www na homologação" homologacao "https://www.$PROD"
  psc no "domínio principal com a pasta hml" homologacao "https://$PROD" "$P_HML"
  psc no "hml com a pasta da produção" homologacao "https://$HML" "$P_PROD"
  psc no "hml com barra final" homologacao "https://$HML" "$P_HML/"
  psc no "hml em maiúsculas" homologacao "https://$HML" "$P_PROD/HML"
  psc no "hml com .." homologacao "https://$HML" "$P_HML/../.."
  psc no "hml com caminho absoluto" homologacao "https://$HML" "/home/u123/$P_HML"
  psc no "hml com //" homologacao "https://$HML" "domains//$PROD/public_html/hml"
  psc no "hml com a pasta derivada do host" homologacao "https://$HML" "domains/$HML/public_html"
  psc no "hml com pasta de terceiro" homologacao "https://$HML" "domains/terceiro.com.br/public_html"
  psc no "hml2" homologacao "https://hml2.$PROD" "$P_HML"
  psc no "outro subdomínio (staging)" homologacao "https://staging.$PROD" "$P_HML"
  psc no "hml colado em outro domínio" homologacao "https://$HML.evil.com" "$P_HML"
  psc no "hostingersite que contém o domínio principal" homologacao "https://$PROD.hostingersite.com"
  psc no "hostingersite com a pasta hml" homologacao "https://abc.hostingersite.com" "$P_HML"
  psc no "localhost na homologação" homologacao "http://localhost:8081"
  psc no "domínio qualquer na homologação" homologacao "https://outro.com.br"
  psc no "URL com caminho" homologacao "https://$HML/hml"
  psc no "produção com o endereço da hml" producao "https://$HML"
  psc no "produção com a pasta da hml" producao "https://$PROD" "$P_HML"
  psc no "produção com a pasta da hml e barra" producao "https://$PROD" "$P_HML/"
  psc no "produção com pasta de terceiro" producao "https://$PROD" "domains/terceiro.com.br/public_html"
  # S2: na produção só vale o domínio principal; localhost e 127.0.0.1 só com o servidor do próprio computador (ensaio local).
  PS_SRV=127.0.0.1 psc ok "produção em ensaio local (localhost, servidor 127.0.0.1)" producao "http://localhost:8081" "$P_PROD"
  PS_SRV=127.0.0.1 psc ok "produção em ensaio local (127.0.0.1, servidor 127.0.0.1)" producao "http://127.0.0.1:8081" "$P_PROD"
  PS_SRV=localhost psc ok "produção em ensaio local (servidor localhost)" producao "http://localhost:8081" "$P_PROD"
  psc no "produção com localhost contra um servidor que não é o do computador" producao "http://localhost:8081" "$P_PROD"
  PS_SRV=ssh.hospedagem.example psc no "produção com 127.0.0.1 contra um servidor remoto" producao "http://127.0.0.1:8081" "$P_PROD"
  psc no "produção com outro site da conta" producao "https://isabellaeneto.com.br" "$P_PROD"
  psc no "produção com evil.com" producao "https://evil.com" "$P_PROD"
  psc no "produção com www.hml" producao "https://www.$HML" "$P_PROD"
  psc no "produção com a.hml" producao "https://a.$HML" "$P_PROD"
  psc no "produção com outro subdomínio" producao "https://staging.$PROD" "$P_PROD"
  psc no "produção com hostingersite" producao "https://abc.hostingersite.com" "$P_PROD"
  psc no "produção com localhost.evil.com" producao "https://localhost.evil.com" "$P_PROD"
  PS_SRV=127.0.0.1 psc no "produção com a pasta da hml até no ensaio local" producao "http://localhost:8081" "$P_HML"
  # S10: o endereço gravado é minúsculo, sem barra final e sem porta (a porta só existe no ensaio local).
  PS_TRECHO="final: https://hml.ieadcvilaipiranga.com.br" psc ok "URL em maiúsculas vira minúscula" homologacao "https://HML.IeadcViLaIpiranga.COM.BR"
  PS_TRECHO="final: https://hml.ieadcvilaipiranga.com.br" psc ok "URL com barra final" homologacao "https://$HML/"
  PS_TRECHO="final: https://hml.ieadcvilaipiranga.com.br" psc ok "URL com porta fora do ensaio local perde a porta" homologacao "https://$HML:8443"
  PS_TRECHO="final: https://www.ieadcvilaipiranga.com.br" psc ok "produção em maiúsculas, www e porta" producao "https://WWW.ieadcvilaipiranga.com.br:8443/"
  PS_SRV=127.0.0.1 PS_TRECHO="final: http://localhost:8081" psc ok "no ensaio local a porta fica" producao "http://LOCALHOST:8081/" "$P_PROD"
fi

# --- 5. bin/cadastrar-segredos-github.sh -------------------------------------------------------------------------------------
secao "cadastrar-segredos-github.sh: só cadastra o par HML_URL + HML_DESTINO aceito"
cadastrar() {   # cadastrar <nome> <linhas do .env da hml...>   deixa em $SEGREDOS os nomes gravados e em $RC o código
  local nome="$1"; shift
  local r="$TMP/cad-$nome"; mkdir -p "$r/bin" "$r/stubs" "$r/content/backup/deploy-hml" "$r/content/backup/deploy-prod"
  cp bin/cadastrar-segredos-github.sh bin/implantar.sh "$r/bin/"
  printf 'chave-falsa\n' > "$r/content/backup/deploy-hml/id_ed25519"; printf 'chave-falsa\n' > "$r/content/backup/deploy-prod/id_ed25519"
  { echo "HOMOLOG_SERVIDOR=servidor.invalido"; echo "HOMOLOG_PORTA=22"; echo "HOMOLOG_USUARIO=u123"; printf '%s\n' "$@"; } > "$r/.env"
  cat > "$r/stubs/gh" <<'FIM'
#!/usr/bin/env bash
# gh de mentira: grava só o NOME do segredo (o valor, que vem pela entrada padrão, nunca é guardado).
if [ "$1 $2" = "secret set" ]; then echo "$3" >> "$GH_LOG"; cat > /dev/null; fi
if [ "$1 $2" = "secret delete" ]; then echo "apagar:$3" >> "$GH_LOG"; fi
if [ "$1 $2" = "variable set" ]; then echo "variavel:$3" >> "$GH_LOG"; fi
exit 0
FIM
  printf '#!/bin/sh\necho "servidor.invalido ssh-ed25519 AAAAfalso"\n' > "$r/stubs/ssh-keyscan"
  printf '#!/bin/sh\ncat > /dev/null\necho "256 SHA256:falsa servidor.invalido (ED25519)"\n' > "$r/stubs/ssh-keygen"
  chmod +x "$r/stubs/"*; GH_LOG="$r/gh.log"; : > "$GH_LOG"
  SAIDA="$(cd "$r" && env -i PATH="$r/stubs:$PATH" HOME="$r" GH_LOG="$GH_LOG" REPO=dono/repo bash bin/cadastrar-segredos-github.sh homologacao 2>&1)"; RC=$?
  SEGREDOS="$(tr '\n' ' ' < "$GH_LOG")"
}
total=$((total + 1)); cadastrar a "HOMOLOG_URL=https://$HML" "HOMOLOG_DESTINO=$P_HML"
if [ "$RC" -eq 0 ] && [[ "$SEGREDOS" == *"HML_URL "* ]] && [[ "$SEGREDOS" == *"HML_DESTINO "* ]]; then passou; else falhou "cadastrar: o par exato da hml deveria gravar HML_URL e HML_DESTINO" "$SAIDA $SEGREDOS"; fi
total=$((total + 1)); cadastrar b "HOMOLOG_URL=https://$HML"
if [ "$RC" -eq 0 ] && [[ "$SEGREDOS" == *"HML_URL "* ]] && [[ " ${SEGREDOS//apagar:HML_DESTINO/} " != *" HML_DESTINO "* ]] && [[ "$SEGREDOS" == *"apagar:HML_DESTINO"* ]]; then passou; else falhou "cadastrar: sem HOMOLOG_DESTINO não grava HML_DESTINO e APAGA o segredo antigo (S6)" "$SAIDA $SEGREDOS"; fi
total=$((total + 1)); cadastrar a2 "HOMOLOG_URL=https://$HML" "HOMOLOG_DESTINO=$P_HML"
if [[ "$SEGREDOS" != *"apagar:"* ]]; then passou; else falhou "cadastrar: com HOMOLOG_DESTINO o HML_DESTINO não é apagado" "$SAIDA $SEGREDOS"; fi
total=$((total + 1)); cadastrar c "HOMOLOG_URL=https://$HML" "HOMOLOG_DESTINO=$P_PROD"
if [ "$RC" -ne 0 ] && [ -z "$SEGREDOS" ]; then passou; else falhou "cadastrar: a pasta da produção como destino da hml deve ser recusada sem gravar nada" "$SAIDA $SEGREDOS"; fi
total=$((total + 1)); cadastrar d "HOMOLOG_URL=https://$PROD"
if [ "$RC" -ne 0 ] && [ -z "$SEGREDOS" ]; then passou; else falhou "cadastrar: o domínio principal como HOMOLOG_URL deve ser recusado sem gravar nada" "$SAIDA $SEGREDOS"; fi
total=$((total + 1)); cadastrar e "HOMOLOG_URL=https://staging.$PROD"
if [ "$RC" -ne 0 ] && [ -z "$SEGREDOS" ]; then passou; else falhou "cadastrar: outro subdomínio deve ser recusado sem gravar nada" "$SAIDA $SEGREDOS"; fi
total=$((total + 1)); cadastrar f "HOMOLOG_URL=https://abc.hostingersite.com"
if [ "$RC" -eq 0 ] && [[ "$SEGREDOS" == *"HML_URL "* ]] && [[ "$SEGREDOS" == *"apagar:HML_DESTINO"* ]]; then passou; else falhou "cadastrar: o endereço hostingersite (transição) continua aceito e apaga o HML_DESTINO da hml" "$SAIDA $SEGREDOS"; fi
total=$((total + 1)); cadastrar g "HOMOLOG_URL=https://$HML" "HOMOLOG_DESTINO=$P_HML/"
if [ "$RC" -ne 0 ] && [ -z "$SEGREDOS" ]; then passou; else falhou "cadastrar: pasta com barra final deve ser recusada sem gravar nada" "$SAIDA $SEGREDOS"; fi

echo
if [ "$falhas" -eq 0 ]; then echo "GUARDAS OK: $total casos, 0 falhas."; else echo "GUARDAS COM FALHAS: $falhas de $total casos."; fi
[ "$falhas" -eq 0 ]
