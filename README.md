# Site da IEADC Vila Ipiranga

WordPress do site **ieadcvilaipiranga.com.br**. Tema pai: [Blocksy](https://creativethemes.com/blocksy/) (oficial, recebe atualizações). Tudo o que é nosso fica neste repositório:

| Pasta | O que é |
|---|---|
| `wp-content/themes/ieadc-vila-ipiranga/` | Tema filho: cabeçalho, menu, rodapé, Roboto e o CSS do protótipo v3 |
| `wp-content/mu-plugins/` | Núcleo do site: dados da igreja, Eventos, Mensagens, Pedidos de oração e formulários |
| `scripts/seed.php` | Cria as páginas da Estrutura do site e o menu principal |
| `prototipo/` | Protótipo v3 aprovado (referência dos testes de fidelidade) |
| `content/paginas/` | Páginas do protótipo convertidas em blocos (versão de transição) |
| `content/media/` | Fotos aprovadas do protótipo (coloridas, sem repetir entre páginas) |
| `content/export/` | Exportação do conteúdo (gerada por `bin/exportar.sh`) |
| `docker-compose.yml`, `bin/` | Ambiente local e atalhos |

Repositório: https://github.com/alencarftc/ieadcvilaipiranga-theme · Protótipo navegável: https://claude.ai/artifact/7QAdRxoBGfwJKPNq7hMSkp (privado do Felipe; cópia em `prototipo/`).

Plano de construção e specs: [`docs/`](docs/README.md). Referência visual: [`prototipo/`](prototipo/) (protótipo v3) (Roboto + Roboto Condensed, marinho `#071946`, azul `#0567B7`, degradê amarelo→vermelho, fotos coloridas sem repetir).

## Rodar na rede de casa

Precisa de [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows, Mac) ou Docker Engine (Linux).

```bash
cp .env.example .env        # troque as senhas e, se quiser abrir em outros aparelhos, o SITE_URL
./bin/setup.sh              # instala WordPress, Blocksy, plugins, páginas e menu
```

- Site: `http://localhost:8080` (ou `http://IP-DA-MAQUINA:8080` nos outros aparelhos; o IP vai no `SITE_URL`).
- Painel: `/wp-admin`, com o usuário e a senha do `.env`.
- E-mails dos formulários (de mentira, só local): `http://localhost:8025`.
- Para deixar o site só nesta máquina, use `SITE_INTERFACE=127.0.0.1` no `.env`.

No Windows, use `powershell -ExecutionPolicy Bypass -File bin\setup.ps1` (ou os comandos `./bin/...` no Git Bash ou no WSL).

Testes (`./bin/testar.sh`): precisam de Node 20+ e, na primeira vez, do navegador do Playwright (`npx playwright install chromium`; se já existe um Chromium do Playwright de outra versão, `IEADC_CHROMIUM=<caminho do chrome ou headless_shell>`). O `.env` fica com `IEADC_HOJE` vazio: o site local mostra o dia real e os testes fixam a data do protótipo (06/10/2026) pelo cabeçalho `X-Ieadc-Hoje`, que só vale no ambiente `local` do WordPress (para ver a agenda de outro dia no navegador, ponha `IEADC_HOJE=AAAA-MM-DD` no `.env` e rode `docker compose up -d`; lembre de esvaziar depois). Os testes abrem o site no `SITE_URL` do `.env`; se a variável `WP_URL` estiver definida, ela vale no lugar.

Atalhos: `./bin/wp.sh <comando>` (wp-cli), `./bin/exportar.sh` (conteúdo para o git), `docker compose down` (desliga; os dados ficam).

## Plugins

Poucos, de propósito:

| Função | Plugin | No local |
|---|---|---|
| SEO | Yoast SEO | ligado |
| Segurança | Two Factor + Limit Login Attempts Reloaded | ligados |
| Cache | LiteSpeed Cache (o servidor da Hostinger é LiteSpeed) | instalado, desligado |
| Backup | UpdraftPlus (além do backup diário da Hostinger) | instalado, desligado |
| Formulários | nenhum: o núcleo (`mu-plugins`) cuida do Fale conosco e dos Pedidos de oração | — |

## Como o conteúdo funciona

- **Dados da igreja** (endereço, telefone, redes, cultos do rodapé): painel em *Configurações > IEADC Vila Ipiranga*. A fonte da verdade é a aba Informações gerais da documentação da Comunicação.
- **Menu**: *Aparência > Menus*. Ícone num item: classe CSS `ico-<nome>` (nomes em `inc/icones.php`). Submenu largo com ícones: classe `sub-cultos` no item pai.
- **Páginas do protótipo** usam o modelo *Página do protótipo (largura total)*.
- **Pedidos de oração** ficam privados no painel (só administradores e o papel *Equipe de oração*), chegam por e-mail e são apagados sozinhos em 90 dias.
- **Formulários**: shortcodes `[ieadc_pedido_oracao]` e `[ieadc_contato]`. Destinatários em *Configurações > IEADC Vila Ipiranga*.
- O site não usa marcação de texto pendente (a antiga classe `pend`, em amarelo, saiu na fase 5B): o que falta fica como texto geral e a pergunta vai para `docs/operacao/perguntas-ao-pastor-robson.md`.

## Migração para a Hostinger (30/11/2026)

Um script faz tudo a partir do computador (Windows): [`docs/operacao/lancamento.md`](docs/operacao/lancamento.md). Para retomar o projeto: [`docs/operacao/continuidade.md`](docs/operacao/continuidade.md) e [`CLAUDE.md`](CLAUDE.md).

## Cronograma

- 16–20/11: fundação (ambiente, tema, tokens, cabeçalho, rodapé, menu)
- 23–27/11: páginas (Início, Horários e localização, Contato e Pedidos de oração primeiro)
- 28–29/11: conteúdo, SEO e ajustes
- 30/11: migração e lançamento
