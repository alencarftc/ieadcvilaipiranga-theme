# Instala e configura o WordPress local do zero no Windows (PowerShell 5+ e Docker Desktop).
# Pode rodar de novo sem estragar nada. Uso: powershell -ExecutionPolicy Bypass -File bin\setup.ps1
$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')
if (-not (Test-Path .env)) { Write-Host 'Crie o .env a partir do .env.example primeiro.'; exit 1 }

# Lê o .env
$cfg = @{}
Get-Content .env | Where-Object { $_ -match '^\s*[A-Z_]+=' } | ForEach-Object {
  $k, $v = $_ -split '=', 2; $cfg[$k.Trim()] = $v.Trim()
}

function WP { docker compose run --rm -T wpcli wp @args; if ($LASTEXITCODE -ne 0) { throw "wp $args falhou" } }
function WPTalvez { docker compose run --rm -T wpcli wp @args 2>$null; $global:LASTEXITCODE = 0 }

docker compose up -d db wordpress mailpit
Write-Host 'Esperando o WordPress subir...'
for ($i = 0; $i -lt 60; $i++) {
  docker compose exec -T wordpress test -f /var/www/html/wp-config.php 2>$null
  if ($LASTEXITCODE -eq 0) { break }; Start-Sleep 2
}

docker compose run --rm -T wpcli wp core is-installed 2>$null
if ($LASTEXITCODE -ne 0) {
  WP core install "--url=$($cfg.SITE_URL)" '--title=IEADC Vila Ipiranga' "--admin_user=$($cfg.ADMIN_USUARIO)" "--admin_password=$($cfg.ADMIN_SENHA)" "--admin_email=$($cfg.ADMIN_EMAIL)" --skip-email
}

WPTalvez language core install pt_BR --activate
WP option update timezone_string 'America/Sao_Paulo'
WP option update date_format 'j \d\e F \d\e Y'
WP option update time_format 'H:i'
WP option update blogdescription 'Congregação da Igreja Evangélica Assembleia de Deus em Curitiba'
WP rewrite structure '/%postname%/' --hard
WP option update default_comment_status closed
WP option update default_ping_status closed
WP option update blog_public 0

docker compose run --rm -T wpcli wp theme is-installed blocksy 2>$null
if ($LASTEXITCODE -ne 0) { WP theme install blocksy }
WP theme activate ieadc-vila-ipiranga
WPTalvez theme delete twentytwentythree twentytwentyfour twentytwentyfive

WPTalvez plugin delete akismet hello
WP plugin install wordpress-seo two-factor limit-login-attempts-reloaded --activate
WP plugin install litespeed-cache updraftplus
WPTalvez language plugin install pt_BR --all
WPTalvez language theme install pt_BR --all

WP eval-file /scripts/seed.php
WP rewrite flush --hard
Write-Host ''
Write-Host "Pronto: $($cfg.SITE_URL)  (painel: $($cfg.SITE_URL)/wp-admin)"
Write-Host 'E-mails dos formulários: http://localhost:8025'
