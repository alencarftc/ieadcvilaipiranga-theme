-- Banco próprio da homologação aninhada (a produção do servidor de mentira usa o banco "hospedagem").
CREATE DATABASE IF NOT EXISTS hospedagem_hml CHARACTER SET utf8mb4;
GRANT ALL PRIVILEGES ON hospedagem_hml.* TO 'hospedagem'@'%';
FLUSH PRIVILEGES;
