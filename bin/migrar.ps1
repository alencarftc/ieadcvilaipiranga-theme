# Envia o site local para a Hostinger. Rode no Windows, na pasta do repositório, com o site local funcionando.
# Ensaio (site continua em manutenção e fora do Google):
#   powershell -ExecutionPolicy Bypass -File bin\migrar.ps1
# Lançamento (tira da manutenção e libera para o Google):
#   powershell -ExecutionPolicy Bypass -File bin\migrar.ps1 -Lancar
# A senha do SSH é pedida duas vezes (envio e execução) e só é digitada aqui, no seu terminal.
param(
  [string]$Servidor = '185.211.7.39',
  [int]$Porta = 65002,
  [string]$Usuario = 'u184534870',
  [string]$Destino = 'domains/ieadcvilaipiranga.com.br/public_html',
  [string]$Url = 'https://ieadcvilaipiranga.com.br',
  [string]$EmailAdmin = '-',
  [switch]$Lancar
)
$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')

foreach ($c in 'docker', 'ssh', 'scp', 'tar') {
  if (-not (Get-Command $c -ErrorAction SilentlyContinue)) { throw "Não achei o comando '$c'. Ele vem com o Docker Desktop e o Windows 10/11 (Recursos opcionais > Cliente OpenSSH)." }
}
$modo = if ($Lancar) { 'lancar' } else { 'ensaio' }
function Docker { docker compose @args; if ($LASTEXITCODE -ne 0) { throw "docker compose $args falhou" } }

Write-Host "Modo: $modo   Destino: ${Usuario}@${Servidor}:$Destino   Endereço final: $Url"
if ($Lancar) {
  $r = Read-Host 'Isto coloca o site NO AR e libera para o Google. Digite SIM para continuar'
  if ($r -ne 'SIM') { Write-Host 'Cancelado.'; exit 0 }
}

Write-Host '1/5 Site local no ar'
Docker up -d db wordpress | Out-Null
$prefixo = (docker compose run --rm -T wpcli wp config get table_prefix 2>$null | Select-Object -Last 1).Trim()
if (-not $prefixo) { throw 'Não consegui ler o prefixo das tabelas; o site local está funcionando?' }

$data = Get-Date -Format 'yyyy-MM-dd-HHmm'
$pasta = Join-Path 'content\backup\migracao' $data
New-Item -ItemType Directory -Force -Path "$pasta\wp-content\themes", "$pasta\wp-content\mu-plugins" | Out-Null

Write-Host '2/5 Banco (sem usuários) e arquivos'
Docker run --rm -T wpcli wp db export "/content/backup/migracao/$data/banco.sql" --exclude_tables="${prefixo}users,${prefixo}usermeta"
Docker cp wordpress:/var/www/html/wp-content/uploads "$pasta\wp-content\uploads"
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
$remoto = "rm -rf ~/ieadc-migracao && mkdir ~/ieadc-migracao && tar -xzf ~/ieadc-pacote.tgz -C ~/ieadc-migracao && bash ~/ieadc-migracao/migrar-remoto.sh '$Destino' '$Url' '$prefixo' '$EmailAdmin' '$modo'"
ssh -p $Porta "${Usuario}@${Servidor}" $remoto
if ($LASTEXITCODE -ne 0) { throw 'A instalação no servidor falhou. Leia a mensagem acima; o banco anterior ficou guardado em ~/backup-antes-da-migracao-*.sql no servidor.' }
Write-Host "Pronto. Confira: $Url"
