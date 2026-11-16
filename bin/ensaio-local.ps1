# Ensaio local da migração: sobe uma "Hostinger de mentira" (tests\migracao: SSH + Apache + WordPress + MariaDB em
# contêineres) e roda bin\migrar.ps1 contra ela, de ponta a ponta. Não toca no servidor real e não pede senha
# (usa uma chave de ensaio, em content\backup\ensaio-hospedagem, fora do git). O site migrado abre em http://localhost:8081.
#   powershell -ExecutionPolicy Bypass -File bin\ensaio-local.ps1            # homologação (o padrão): aberta para quem tem o link, fora do Google
#   powershell -ExecutionPolicy Bypass -File bin\ensaio-local.ps1 -Producao  # ensaio da produção: fechada ("em preparação") e fora do Google
#   powershell -ExecutionPolicy Bypass -File bin\ensaio-local.ps1 -Lancar    # lançamento da produção: no ar e liberada para o Google
#   powershell -ExecutionPolicy Bypass -File bin\ensaio-local.ps1 -Parar     # desliga e apaga o servidor de mentira
# -Producao e -Lancar só valem para o servidor de mentira: o endereço e a porta são fixos aqui.
param([switch]$Producao, [switch]$Lancar, [switch]$Parar)
$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')

$compose = 'tests\migracao\docker-compose.yml'
$pastaChave = Join-Path (Get-Location).Path 'content\backup\ensaio-hospedagem'
$chave = Join-Path $pastaChave 'id_ed25519'
$url = 'http://localhost:8081'
$site = '/var/www/html'

# No PowerShell 5 o progresso que o docker escreve em stderr não pode virar erro: por isso o 'Continue' aqui.
function Compose {
  $ErrorActionPreference = 'Continue'
  & docker compose -p ieadc-hospedagem-teste -f $compose @args
  if ($LASTEXITCODE -ne 0) { throw "docker compose $args falhou" }
}
# Comando no servidor de mentira, como o usuário do cliente (u123), sem derrubar o script se falhar.
function Remoto {
  $ErrorActionPreference = 'Continue'
  & docker compose -p ieadc-hospedagem-teste -f $compose exec -T -u u123 servidor @args 2>&1 | ForEach-Object { "$_" }
}
function RemotoOk {
  $ErrorActionPreference = 'Continue'
  & docker compose -p ieadc-hospedagem-teste -f $compose exec -T -u u123 servidor @args *> $null
  return ($LASTEXITCODE -eq 0)
}

if ($Parar) { Compose down -v; Write-Host 'Servidor de mentira desligado e apagado.'; exit 0 }

foreach ($c in 'docker', 'ssh', 'scp', 'ssh-keygen') {
  if (-not (Get-Command $c -ErrorAction SilentlyContinue)) { throw "Não achei o comando '$c' (Docker Desktop e Cliente OpenSSH do Windows)." }
}

Write-Host '1/4 Chave de ensaio'
if (-not (Test-Path $chave)) {
  New-Item -ItemType Directory -Force -Path $pastaChave | Out-Null
  # cmd /c: o PowerShell 5 perde o argumento vazio (-N "") ao chamar o ssh-keygen direto.
  cmd /c "ssh-keygen -q -t ed25519 -N `"`" -f `"$chave`" -C ensaio-hospedagem"
  if ($LASTEXITCODE -ne 0) { throw 'ssh-keygen falhou' }
}

Write-Host '2/4 Servidor de mentira (a primeira vez demora: constrói a imagem)'
Compose up -d --build
for ($i = 0; $i -lt 90; $i++) { if (RemotoOk test -f "$site/wp-config.php") { break }; Start-Sleep 2 }
if (-not (RemotoOk test -f "$site/wp-config.php")) { throw 'O WordPress do servidor de mentira não subiu.' }
# Na Hostinger o wp-config.php tem os dados do banco escritos nele; aqui a imagem os lê de variáveis de ambiente,
# que a sessão SSH não herda. Grava valores fixos, como no servidor de verdade.
foreach ($par in @(@('DB_HOST', 'banco'), @('DB_NAME', 'hospedagem'), @('DB_USER', 'hospedagem'), @('DB_PASSWORD', 'hospedagem'))) {
  Remoto wp --path=$site config set $par[0] $par[1] | Out-Null
}
# Instalação "de fábrica", como a que o hPanel entrega antes da migração.
for ($i = 0; $i -lt 30; $i++) {
  if (RemotoOk wp --path=$site core is-installed) { break }
  Remoto wp --path=$site core install "--url=$url" '--title=Hospedagem de teste' --admin_user=admhost --admin_password=Ensaio-Hospedagem-1 --admin_email=admin-hospedagem@example.com --skip-email | Out-Null
  Start-Sleep 3
}
if (-not (RemotoOk wp --path=$site core is-installed)) { throw 'Não consegui instalar o WordPress de fábrica no servidor de mentira.' }

# O migrar.ps1 chama "ssh" e "scp" pelo nome; estas funções acrescentam a chave de ensaio e ignoram o known_hosts do usuário.
$opcoes = @('-i', $chave, '-o', 'BatchMode=yes', '-o', 'StrictHostKeyChecking=no', '-o', 'UserKnownHostsFile=NUL', '-o', 'LogLevel=ERROR')
function ssh { & ssh.exe @opcoes @args }
function scp { & scp.exe @opcoes @args }
# Na produção o migrar.ps1 pede para digitar PRODUCAO (e SIM no lançamento). Aqui, que só fala com o servidor de
# mentira, as respostas já vêm prontas.
if ($Producao -or $Lancar) { function Read-Host { param($Prompt) if ("$Prompt" -match 'PRODUCAO') { 'PRODUCAO' } else { 'SIM' } } }
for ($i = 0; $i -lt 30; $i++) { ssh -p 2222 u123@127.0.0.1 true 2>$null; if ($LASTEXITCODE -eq 0) { break }; Start-Sleep 2 }
if ($LASTEXITCODE -ne 0) { throw 'O SSH do servidor de mentira não respondeu em 127.0.0.1:2222.' }

Write-Host '3/4 bin\migrar.ps1 contra o servidor de mentira'
$params = @{ Servidor = '127.0.0.1'; Porta = 2222; Usuario = 'u123'; Url = $url; Destino = 'domains/ieadcvilaipiranga.com.br/public_html' }
if ($Lancar) { $params['Ambiente'] = 'producao'; $params['Lancar'] = $true }
elseif ($Producao) { $params['Ambiente'] = 'producao' }
& (Join-Path $PSScriptRoot 'migrar.ps1') @params

Write-Host '4/4 Conferência'
function Opcao($nome) { ([string](Remoto wp --path=$site option get $nome | Select-Object -Last 1)).Trim() }
Write-Host ("  home / siteurl .......... {0} / {1}" -f (Opcao 'home'), (Opcao 'siteurl'))
Write-Host ("  blog_public ............. {0}  (0 = fora do Google)" -f (Opcao 'blog_public'))
Write-Host ("  em manutenção ........... {0}" -f $(if (RemotoOk test -f "$site/.maintenance") { 'sim' } else { 'não' }))
Write-Host ("  tema ativo .............. {0}" -f ([string](Remoto wp --path=$site theme list --status=active --field=name | Select-Object -Last 1)).Trim())
Write-Host ("  páginas publicadas ...... {0}" -f ([string](Remoto wp --path=$site post list --post_type=page --post_status=publish --format=count | Select-Object -Last 1)).Trim())
Write-Host ("  pedidos de oração ....... {0}" -f ([string](Remoto wp --path=$site post list --post_type=pedido_oracao --post_status=any --format=count | Select-Object -Last 1)).Trim())
Write-Host ('  site fechado (opção) .... {0}  (1 = visitantes veem "em preparação")' -f (Opcao 'ieadc_site_fechado'))
Write-Host ("  regras no .htaccess ..... {0}" -f ([string](Remoto grep -c RewriteRule "$site/.htaccess" | Select-Object -Last 1)).Trim())
function Codigo($caminho) {
  try { (Invoke-WebRequest -UseBasicParsing -Uri "$url$caminho" -MaximumRedirection 0 -ErrorAction Stop).StatusCode }
  catch { if ($_.Exception.Response) { [int]$_.Exception.Response.StatusCode } else { $_.Exception.Message } }
}
Write-Host '  visitante sem login (esperado: homologação e lançamento 200 em tudo; ensaio da produção 503 nas páginas e na API; o login é sempre 200):'
foreach ($c in '/', '/sobre-nos/', '/wp-json/wp/v2/pages', '/wp-login.php') { Write-Host ("    {0,-22} HTTP {1}" -f $c, (Codigo $c)) }
Write-Host "Painel do servidor de mentira: $url/wp-login.php (admhost / Ensaio-Hospedagem-1). Para desligar: bin\ensaio-local.ps1 -Parar"
