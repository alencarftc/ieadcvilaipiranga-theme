# "Hostinger de mentira" para ensaiar bin/migrar.ps1 sem tocar no servidor real:
# SSH + Apache/PHP + wp-cli, com o WordPress em /var/www/html e o mesmo caminho da Hostinger
# (~/domains/ieadcvilaipiranga.com.br/public_html). Só para ensaio local (veja docs/operacao/lancamento.md).
FROM wordpress:cli-php8.3 AS wpcli

FROM wordpress:php8.3-apache
RUN apt-get update \
 && apt-get install -y --no-install-recommends openssh-server mariadb-client less \
 && rm -rf /var/lib/apt/lists/* \
 && mkdir -p /run/sshd
COPY --from=wpcli /usr/local/bin/wp /usr/local/bin/wp

# Conta do cliente (na Hostinger o Apache/PHP roda com o mesmo usuário dono dos arquivos).
RUN useradd -m -s /bin/bash u123 \
 && usermod -p '*' u123 \
 && mkdir -p /home/u123/domains/ieadcvilaipiranga.com.br \
 && ln -s /var/www/html /home/u123/domains/ieadcvilaipiranga.com.br/public_html \
 && chown -R u123:u123 /home/u123
ENV APACHE_RUN_USER=u123 APACHE_RUN_GROUP=u123

COPY entrypoint.sh /usr/local/bin/entrypoint-hospedagem.sh
RUN chmod +x /usr/local/bin/entrypoint-hospedagem.sh
ENTRYPOINT ["entrypoint-hospedagem.sh"]
CMD ["apache2-foreground"]
