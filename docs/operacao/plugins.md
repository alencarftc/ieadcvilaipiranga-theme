# Plugins e configurações (M6)

Poucos plugins, todos do wordpress.org (no ambiente de nuvem o wordpress.org é bloqueado: instalar no servidor local e na Hostinger). As opções abaixo são aplicadas por `scripts/seed/90-configuracoes.php`.

| Plugin | Para quê | Configuração aplicada |
|---|---|---|
| **Yoast SEO** | Título, descrição, Open Graph, canonical e sitemap (`/sitemap_index.xml`) | Título `Página · IEADC Vila Ipiranga`; descrição pelo resumo; autor, anexos e tags fora do índice. O tema (`inc/seo.php`) entrega a descrição (o `lead` do `hero`) e a foto do topo ao Yoast. Sem Yoast, o próprio tema imprime título, descrição, canonical e Open Graph. |
| **Limit Login Attempts Reloaded** | Bloqueia tentativas de senha | 4 tentativas, bloqueio de 20 min; 3 bloqueios → 24 h |
| **Two Factor** | Segundo fator no login | Código por e-mail para administradores. Ligar com `IEADC_2FA=1` ao rodar o seed (precisa de e-mail funcionando; localmente use o Mailpit) |
| **LiteSpeed Cache** (só na Hostinger) | Cache de página e de imagens | Ver abaixo |

## Já feito pelo tema (`inc/seo.php`, sem plugin)

- JSON-LD `Church` na página inicial (nome, endereço, telefone, redes e horários semanais tirados do resumo de cultos em Configurações > IEADC Vila Ipiranga; "2º sábado" e linhas compostas ficam só no texto).
- `<link rel="preload" as="image">` só da foto do `hero` de cada página; emojis, `wp_generator`, RSD e `wlwmanifest` removidos.
- XML-RPC desligado; `X-Pingback` removido; cabeçalhos `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`.
- Lista de usuários escondida da API REST e do sitemap para visitantes; busca e anexos com `noindex`.
- `DISALLOW_FILE_EDIT` já vem do `docker-compose.yml` (repetir no `wp-config.php` da Hostinger).

## LiteSpeed Cache (Hostinger)

Cache de página ligado; não cachear `/pedidos-de-oracao/` e `/contato/` com formulário enviado (o plugin já ignora POST); minificar CSS/JS **desligado** até medir (o protótipo é um CSS só e o teste visual precisa passar); WebP já é o formato das fotos. Depois de ligar, rodar Lighthouse no celular.

## Usuários

Contas e papéis seguem os Perfis de acesso da subaba Website. Administrador só para quem mantém o site; os demais como Editor.

## Pendente de medir (precisa do site no ar)

- Lighthouse celular nas 4 páginas do P1 (metas na spec M6) e `axe` (`tests/visual/a11y.spec.js`, ainda não existe).
- Coverage do Chrome para decidir o que do Blocksy/core pode sair do front (`global-styles`, `wp-block-library-theme`): não foi desligado porque pode mexer em layout dos blocos.
- As fotos do `content/media` já têm no máximo 1600 px, então não há conversão de tamanho a fazer.
