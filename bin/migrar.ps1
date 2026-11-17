# Envia o site local para a Hostinger. Rode no Windows, na pasta do repositório, com o site local funcionando.
# Dois ambientes. O padrão é a HOMOLOGAÇÃO; a produção só com -Ambiente producao (nunca por engano):
#   homologacao  onde o pastor e a equipe validam o site. Fica aberta para quem tem o link e fora do Google. No .env:
#                HOMOLOG_SERVIDOR, HOMOLOG_PORTA, HOMOLOG_USUARIO e HOMOLOG_URL (e HOMOLOG_DESTINO). Só são aceitos dois pares:
#                https://hml.ieadcvilaipiranga.com.br com a pasta domains/ieadcvilaipiranga.com.br/public_html/hml (staging,
#                subpasta da produção) ou, de transição, https://<algo>.hostingersite.com com domains/<algo>.hostingersite.com/public_html.
#   producao     o domínio principal (https://ieadcvilaipiranga.com.br). No .env: HOSTINGER_SERVIDOR, HOSTINGER_PORTA e
#                HOSTINGER_USUARIO (e PRODUCAO_URL, se o endereço for outro).
# Uso:
#   powershell -ExecutionPolicy Bypass -File bin\migrar.ps1                              # homologação: aberta e fora do Google
#   powershell -ExecutionPolicy Bypass -File bin\migrar.ps1 -Fechado                     # homologação só para quem tem login
#   powershell -ExecutionPolicy Bypass -File bin\migrar.ps1 -Ambiente producao           # produção em ensaio: fechada e fora do Google
#   powershell -ExecutionPolicy Bypass -File bin\migrar.ps1 -Ambiente producao -Lancar   # produção NO AR e liberada para o Google
# A senha do SSH é pedida duas vezes (envio e execução) e só é digitada aqui, no seu terminal.
param(
  [ValidateSet('homologacao', 'producao')][string]$Ambiente = 'homologacao',
  [string]$Servidor = '',
  [int]$Porta = 0,
  [string]$Usuario = '',
  [string]$Destino = '',   # padrão: a pasta do par do endereço (ou HOMOLOG_DESTINO / HOSTINGER_DESTINO no .env, se for exatamente a do par)
  [string]$Url = '',       # padrão: HOMOLOG_URL (homologação) ou https://ieadcvilaipiranga.com.br (produção)
  [string]$EmailAdmin = '-',
  [switch]$Conferir,       # só confere as travas, mostra o destino e testa a porta do SSH; não envia nada nem pede senha
  [switch]$SemRede,        # com -Conferir: não testa a porta (usado pelos testes de CI, sem conexão)
  [switch]$Fechado,        # homologação: deixa o site só para quem tem login
  [switch]$Lancar          # produção: coloca no ar e libera para o Google
)
$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')

if ($Lancar -and $Ambiente -ne 'producao') { throw 'O -Lancar é só da produção (-Ambiente producao). A homologação já fica aberta para quem tem o link.' }
if ($Fechado -and $Ambiente -ne 'homologacao') { throw 'O -Fechado é só da homologação; a produção em ensaio já fica fechada.' }

# Servidor, porta e usuário do SSH ficam no .env (que não vai para o git), não neste arquivo.
$env_ = @{}
if (Test-Path .env) {
  foreach ($linha in (Get-Content .env -Encoding UTF8)) {
    if ($linha -match '^\s*([A-Z_]+)\s*=\s*(.*?)\s*$') { $env_[$Matches[1]] = $Matches[2].Trim('"', "'") }
  }
}
$pre = if ($Ambiente -eq 'homologacao') { 'HOMOLOG' } else { 'HOSTINGER' }
if (-not $Servidor) { $Servidor = [string]$env_["${pre}_SERVIDOR"] }
if (-not $Usuario)  { $Usuario  = [string]$env_["${pre}_USUARIO"] }
if (-not $Porta) {
  $p = 0
  if (-not [int]::TryParse([string]$env_["${pre}_PORTA"], [ref]$p)) { $p = 0 }
  $Porta = $p
}
if (-not $Servidor -or -not $Porta -or -not $Usuario) { throw "Faltam ${pre}_SERVIDOR, ${pre}_PORTA e ${pre}_USUARIO no .env (veja o hPanel > Avançado > SSH), ou passe -Servidor, -Porta e -Usuario." }
if (-not $Url) {
  $Url = if ($Ambiente -eq 'homologacao') { [string]$env_['HOMOLOG_URL'] } else { [string]$env_['PRODUCAO_URL'] }
  if (-not $Url -and $Ambiente -eq 'producao') { $Url = 'https://ieadcvilaipiranga.com.br' }
}
if (-not $Url) { throw 'Falta HOMOLOG_URL no .env (o endereço da hospedagem de homologação) ou o parâmetro -Url.' }
$Url = $Url.TrimEnd('/')
if ($Url -notmatch '^https?://[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*(:[0-9]+)?$') { throw "O endereço do site ($Url) deve ter o formato http(s)://domínio, sem pasta, espaços nem caracteres especiais (confira HOMOLOG_URL no .env ou o parâmetro -Url)." }
# Trava de endereço e de pasta (a mesma do bin/implantar.sh). A conta de hospedagem tem a produção, a homologação e sites de
# terceiros: o endereço e a pasta só podem ser um destes pares EXATOS (igualdade, nunca prefixo: a homologação hml é uma
# SUBPASTA da produção). Um .env ou um parâmetro errado não pode enviar à produção sem a confirmação PRODUCAO.
#   producao     ieadcvilaipiranga.com.br (ou www.)   domains/ieadcvilaipiranga.com.br/public_html
#   homologacao  hml.ieadcvilaipiranga.com.br         domains/ieadcvilaipiranga.com.br/public_html/hml
#   homologacao  *.hostingersite.com (transição)      domains/<endereço>/public_html
$dominioProducao = 'ieadcvilaipiranga.com.br'
$hostUrl = ([uri]$Url).Host.ToLowerInvariant()
$pastaProducao = "domains/$dominioProducao/public_html"; $pastaHml = "$pastaProducao/hml"; $hostHml = "hml.$dominioProducao"
$hostPrincipal = ($hostUrl -ceq $dominioProducao) -or ($hostUrl -ceq "www.$dominioProducao")
# localhost e 127.0.0.1 só existem no ensaio local (bin\ensaio-local.ps1 usa -Servidor 127.0.0.1): contra qualquer outro servidor
# eles nunca são o endereço de um site.
$servidorLocal = @('127.0.0.1', 'localhost', '::1') -contains ([string]$Servidor).ToLowerInvariant()
$hostLocal = @('localhost', '127.0.0.1') -contains $hostUrl
# O endereço gravado em home/siteurl é sempre minúsculo, sem barra final e sem porta (a porta só existe no ensaio local).
$uriNorm = [uri]$Url
$Url = $uriNorm.Scheme + '://' + $hostUrl + $(if ($servidorLocal -and -not $uriNorm.IsDefaultPort) { ':' + $uriNorm.Port } else { '' })
if (-not $Destino) { $Destino = [string]$env_["${pre}_DESTINO"] }
if (-not $Destino) {
  $Destino = if ($hostUrl -ceq $hostHml) { $pastaHml } elseif ($Ambiente -eq 'producao' -and ($hostPrincipal -or $hostLocal)) { $pastaProducao } else { "domains/$hostUrl/public_html" }
}
if ($Destino -cmatch '(^/)|\.\.|//|\\|\s|~') { throw "A pasta do site ($Destino) é inválida: não pode começar com /, ter .., //, barra invertida, espaços nem ~." }
if ($Ambiente -eq 'producao') {
  if (-not ($hostPrincipal -or ($hostLocal -and $servidorLocal))) { throw "Na produção o endereço deve ser $dominioProducao ou www.$dominioProducao (localhost e 127.0.0.1 só no ensaio local, com -Servidor 127.0.0.1). Recebi $hostUrl." }
  if ($Destino -cne $pastaProducao) { throw "Na produção a pasta do site deve ser exatamente $pastaProducao (recebi $Destino). Nenhuma outra pasta da conta é tocada." }
} else {
  if ($hostPrincipal) { throw "A homologação não pode apontar para $dominioProducao (endereço $Url, pasta $Destino). Para enviar à produção use -Ambiente producao." }
  if ($hostUrl -ceq $hostHml) { $esperada = $pastaHml }
  elseif ($hostUrl -cmatch '^([a-z0-9-]+\.)+hostingersite\.com$' -and $hostUrl -cnotlike "*$dominioProducao*") { $esperada = "domains/$hostUrl/public_html" }
  else { throw "A homologação só aceita o endereço $hostHml (pasta $pastaHml) ou um endereço *.hostingersite.com (pasta domains/<endereço>/public_html). Recebi $hostUrl." }
  if ($Destino -cne $esperada) { throw "A pasta do site da homologação deve ser exatamente $esperada (recebi $Destino). Nenhuma outra pasta da conta é tocada." }
}

$modo = if ($Ambiente -eq 'homologacao') { if ($Fechado) { 'homologacao-fechada' } else { 'homologacao' } } elseif ($Lancar) { 'lancar' } else { 'ensaio' }
# Atenção: a função não pode se chamar "Docker" (o PowerShell não diferencia maiúsculas e ela chamaria a si mesma).
function Compose {
  $ErrorActionPreference = 'Continue'  # o docker escreve o progresso em stderr; no PowerShell 5 isso não pode virar erro
  & docker compose @args
  if ($LASTEXITCODE -ne 0) { throw "docker compose $args falhou" }
}

Write-Host "Ambiente: $Ambiente   Modo: $modo   Destino: ${Usuario}@${Servidor}:$Destino   Endereço final: $Url"
if ($Conferir) {
  Write-Host 'TRAVAS OK'
  if ($SemRede) { Write-Host 'Nada foi enviado nem conectado (-SemRede).'; exit 0 }
  $responde = (Test-NetConnection -ComputerName $Servidor -Port $Porta -WarningAction SilentlyContinue).TcpTestSucceeded
  Write-Host ('Porta SSH {0}:{1} {2}. Nada foi enviado.' -f $Servidor, $Porta, $(if ($responde) { 'responde' } else { 'NÃO responde' }))
  exit 0
}
foreach ($c in 'docker', 'ssh', 'scp', 'tar') {
  if (-not (Get-Command $c -ErrorAction SilentlyContinue)) { throw "Não achei o comando '$c'. Ele vem com o Docker Desktop e o Windows 10/11 (Recursos opcionais > Cliente OpenSSH)." }
}
if ($Ambiente -eq 'producao') {
  $r = Read-Host "Isto envia o site para a PRODUÇÃO ($Url). Digite PRODUCAO para continuar"
  if ($r -ne 'PRODUCAO') { Write-Host 'Cancelado.'; exit 0 }
}
if ($Lancar) {
  $r = Read-Host 'Isto coloca o site NO AR e libera para o Google. Digite SIM para continuar'
  if ($r -ne 'SIM') { Write-Host 'Cancelado.'; exit 0 }
}

Write-Host '1/5 Site local no ar'
Compose up -d db wordpress | Out-Null
$prefixo = ([string](Compose run --rm -T wpcli wp config get table_prefix 2>$null | Select-Object -Last 1)).Trim()
if (-not $prefixo) { throw 'Não consegui ler o prefixo das tabelas; o site local está funcionando?' }
$origem = ([string](Compose run --rm -T wpcli wp option get home 2>$null | Select-Object -Last 1)).Trim().TrimEnd('/')
if (-not $origem) { throw 'Não consegui ler o endereço do site local.' }
# O banco pode guardar um endereço (ex.: http://localhost:8080) e o .env outro (WP_HOME, ex.: o IP da máquina para abrir
# pelo celular). O "wp option get home" devolve o do .env; troca os dois, senão o site novo apontaria para o endereço antigo.
$guardados = @(Compose run --rm -T wpcli wp db query "SELECT option_value FROM ${prefixo}options WHERE option_name IN ('home','siteurl')" --skip-column-names 2>$null | ForEach-Object { "$_".Trim().TrimEnd('/') } | Where-Object { $_ -match '^https?://' })
$origens = ((@($origem) + $guardados) | Select-Object -Unique) -join ','

$data = Get-Date -Format 'yyyy-MM-dd-HHmmss'   # com segundos: uma segunda tentativa no mesmo minuto não pode cair na pasta da anterior
$pasta = Join-Path 'content\backup\migracao' $data
New-Item -ItemType Directory -Force -Path "$pasta\wp-content\themes", "$pasta\wp-content\mu-plugins" | Out-Null

Write-Host '2/5 Banco (sem usuários) e arquivos'
Compose run --rm -T wpcli wp db export "/content/backup/migracao/$data/banco.sql" --exclude_tables="${prefixo}users,${prefixo}usermeta"
Compose cp wordpress:/var/www/html/wp-content/uploads "$pasta\wp-content\uploads"
Copy-Item -Recurse wp-content\themes\ieadc-vila-ipiranga "$pasta\wp-content\themes\"
Copy-Item -Recurse wp-content\mu-plugins\* "$pasta\wp-content\mu-plugins\"
Copy-Item bin\migrar-remoto.sh "$pasta\"

Write-Host '3/5 Pacote'
$pacote = Join-Path (Resolve-Path 'content\backup\migracao') "ieadc-$data.tgz"
tar -czf $pacote -C $pasta .
if ($LASTEXITCODE -ne 0) { throw 'tar falhou' }

Write-Host '4/5 Envio (digite a senha do SSH)'
scp -P $Porta $pacote "${Usuario}@${Servidor}:ieadc-pacote.tgz"
if ($LASTEXITCODE -ne 0) { throw 'scp falhou (o SSH está habilitado no hPanel? usuário e porta corretos?)' }

Write-Host '5/5 Instalação no servidor (digite a senha do SSH de novo)'
$remoto = "rm -rf ~/ieadc-migracao && mkdir ~/ieadc-migracao && tar -xzf ~/ieadc-pacote.tgz -C ~/ieadc-migracao && bash ~/ieadc-migracao/migrar-remoto.sh '$Destino' '$Url' '$prefixo' '$EmailAdmin' '$modo' '$origens'"
ssh -p $Porta "${Usuario}@${Servidor}" $remoto
if ($LASTEXITCODE -ne 0) { throw 'A instalação no servidor falhou. Leia a mensagem acima; o banco anterior ficou guardado em ~/backup-antes-da-migracao-*.sql no servidor.' }
Write-Host "Pronto. Confira: $Url"
