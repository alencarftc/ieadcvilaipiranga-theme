#!/bin/sh
# Sobe o SSH (só com chave, a chave pública vem de /chave/id_ed25519.pub) e depois o WordPress/Apache de sempre.
set -e
install -d -m 700 -o u123 -g u123 /home/u123/.ssh
if [ -f /chave/id_ed25519.pub ]; then
  install -m 600 -o u123 -g u123 /chave/id_ed25519.pub /home/u123/.ssh/authorized_keys
fi
# O usuário do Apache precisa poder criar arquivos na raiz do site (.maintenance, .htaccess, wp-config.php).
mkdir -p /var/www/html
chown u123:u123 /var/www/html
ssh-keygen -A
/usr/sbin/sshd -e
exec docker-entrypoint.sh "$@"
