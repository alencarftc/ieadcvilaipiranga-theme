# Envia o site local para a Hostinger. Rode no Windows, na pasta do repositório, com o site local funcionando.
# Ensaio (site continua em manutenção e fora do Google):
#   powershell -ExecutionPolicy Bypass -File bin\migrar.ps1
# Lançamento (tira da manutenção e libera para o Google):
#   powershell -ExecutionPolicy Bypass -File bin\migrar.ps1 -Lancar
# A senha do SSH é pedida duas vezes (envio e execução) e só é digitada aqui, no seu terminal.
param(
  [string]$Servidor = '',
  [int]$Porta = 0,
  [string]$Usuario = '',
  [string]$Destino = 'domains/ieadcvilaipiranga.com.br/public_html',
  [string]$Url = 'https://ieadcvilaipiranga.com.br',
  [string]$EmailAdmin = '-',
  [switch]$Lancar
)
$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')

# Servidor, porta e usuário do SSH ficam no .env (que não vai para o git), não neste arquivo:
#   HOSTINGER_SERVIDOR=...   HOSTINGER_PORTA=...   HOSTINGER_USUARIO=...
$env_ = @{}
if (Test-Path .env) {
  foreach ($linha in (Get-Content .env -Encoding UTF8)) {
    if ($linha -match '^\s*(HOSTINGER_[A-Z_]+)\s*=\s*(.*?)\s*$') { $env_[$Matches[1]] = $Matches[2].Trim('"', "'") }
  }
}
if (-not $Servidor) { $Servidor = [string]$env_['HOSTINGER_SERVIDOR'] }
if (-not $Usuario)  { $Usuario  = [string]$env_['HOSTINGER_USUARIO'] }
if (-not $Porta) {
  $p = 0
  if (-not [int]::TryParse([string]$env_['HOSTINGER_PORTA'], [ref]$p)) { $p = 0 }
  $Porta = $p
}
if (-not $Servidor -or -not $Porta -or -not $Usuario) { throw 'Faltam HOSTINGER_SERVIDOR, HOSTINGER_PORTA e HOSTINGER_USUARIO no .env (veja o hPanel > Avançado > SSH), ou passe -Servidor, -Porta e -Usuario.' }

foreach ($c in 'docker', 'ssh', 'scp', 'tar') {
  if (-not (Get-Command $c -ErrorAction SilentlyContinue)) { throw "Não achei o comando '$c'. Ele vem com o Docker Desktop e o Windows 10/11 (Recursos opcionais > Cliente OpenSSH)." }
}
$modo = if ($Lancar) { 'lancar' } else { 'ensaio' }
# Atenção: a função não pode se chamar "Docker" (o PowerShell não diferencia maiúsculas e ela chamaria a si mesma).
function Compose {
  $ErrorActionPreference = 'Continue'  # o docker escreve o progresso em stderr; no PowerShell 5 isso não pode virar erro
  & docker compose @args
  if ($LASTEXITCODE -ne 0) { throw "docker compose $args falhou" }
}

Write-Host "Modo: $modo   Destino: ${Usuario}@${Servidor}:$Destino   Endereço final: $Url"
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

$data = Get-Date -Format 'yyyy-MM-dd-HHmm'
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
$remoto = "rm -rf ~/ieadc-migracao && mkdir ~/ieadc-migracao && tar -xzf ~/ieadc-pacote.tgz -C ~/ieadc-migracao && bash ~/ieadc-migracao/migrar-remoto.sh '$Destino' '$Url' '$prefixo' '$EmailAdmin' '$modo' '$origem'"
ssh -p $Porta "${Usuario}@${Servidor}" $remoto
if ($LASTEXITCODE -ne 0) { throw 'A instalação no servidor falhou. Leia a mensagem acima; o banco anterior ficou guardado em ~/backup-antes-da-migracao-*.sql no servidor.' }
Write-Host "Pronto. Confira: $Url"
