#!/usr/bin/env bash
# Exporta o conteúdo do site para o repositório (content/export) e um backup do banco (content/backup, fora do git).
set -euo pipefail
cd "$(dirname "$0")/.."
data=$(date +%Y-%m-%d)
mkdir -p content/export content/backup
docker compose run --rm -T wpcli wp export --dir=/content/export --filename_format="conteudo.xml" --skip_comments
docker compose run --rm -T wpcli wp db export "/content/backup/banco-$data.sql"
echo "Conteúdo em content/export/conteudo.xml (vai para o git). Banco em content/backup/banco-$data.sql (não vai)."
