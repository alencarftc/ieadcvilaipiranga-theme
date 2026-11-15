# Plano de construção do site IEADC Vila Ipiranga

Versão 1 · 15/11/2026 · em revisão pelo Felipe. Depois de aprovado, gera as specs em `docs/specs/`.

Lançamento: **30/11/2026** em **ieadcvilaipiranga.com.br** (Hostinger).

---

## 1. Objetivo e fonte da verdade

Construir o site em WordPress com **alta fidelidade ao protótipo v3 aprovado**, editável pelo painel por voluntários, desenvolvido em paralelo por vários agentes.

| Assunto | Fonte da verdade | Onde fica no repositório |
|---|---|---|
| Visual, estrutura e textos das páginas | Protótipo v3 (aprovado em 06–09/10/2026) | `prototipo/` (HTML, CSS, fontes e o gerador `prototipo/fonte/gen.py`) |
| Fotos | Fotos escolhidas no protótipo (coloridas, sem repetir) | `content/media/` |
| Textos do site | Subaba *Copies do site* da documentação da Comunicação | já aplicados no protótipo |
| Dados da igreja (endereço, horários, regras da agenda) | Aba *Informações gerais* da documentação | `ieadc_info()` e módulo Agenda |

Regras que não mudam:

- Família Roboto + Roboto Condensed; a frase cursiva (Dancing Script) só onde o protótipo usa.
- Fotos sempre coloridas, sem filtro, sem repetir entre seções e páginas. Sem fotos com o pastor João; Ev. Robson Stein de preferência.
- Texto não confirmado fica **entre colchetes com fundo amarelo** (classe `pend`). Nunca inventar dado da igreja.
- Sem chamadas de transmissão ao vivo (só o link do canal nas redes).
- Pastor: "Ev. Robson Stein, pastor da igreja local". Usar "Ceia do Senhor" e "Culto de Ensino". Endereço com número 555.

## 2. Estado atual (base pronta em 15/11)

Já está no repositório e roda no Docker:

- Tema filho `ieadc-vila-ipiranga` sobre o **Blocksy original**: topo com frase e redes, cabeçalho, menu de 3 níveis com ícones, botão do menu no celular, rodapé com mapa do site. Conferido com prints em 1440 px e 390 px.
- CSS do protótipo (`assets/css/prototipo.css`) com o reset limitado a `.ieadc-ui` para não quebrar o Blocksy.
- Núcleo em `mu-plugins/ieadc-core`: dados da igreja (Configurações > IEADC Vila Ipiranga), tipos Eventos, Mensagens e Pedidos de oração (privado, apagado em 90 dias), formulários `[ieadc_contato]` e `[ieadc_pedido_oracao]`.
- Ambiente local: WordPress 7.1 + PHP 8.3 + MariaDB 11.4 + wp-cli + Mailpit, com `bin/setup.sh`.
- **Versão de transição das páginas:** as 28 páginas do protótipo foram convertidas (`scripts/importar_prototipo.py`) em blocos "HTML personalizado" com as fotos na biblioteca de mídia. O resultado já é fiel ao protótipo, mas o voluntário editaria HTML cru. O plano abaixo troca isso por blocos próprios com campos.

## 3. Decisões técnicas

| Tema | Decisão | Por quê |
|---|---|---|
| Tema | Blocksy original (pai, atualizado pelo WordPress) + tema filho nosso | Recebe correções de segurança; o que é nosso fica isolado |
| Repositório | `alencarftc/ieadcvilaipiranga-theme` passa a ser o repositório do projeto. A cópia renomeada do Blocksy sai (fica no histórico do git) | Pedido do Felipe em 15/11 |
| Seções das páginas | **Blocos próprios dinâmicos** `ieadc/<nome>`: `block.json` + `render.php`, sem etapa de build (sem Node para compilar) | O HTML gerado é igual ao do protótipo (fidelidade) e os textos, fotos e links viram campos no editor (editável por voluntários) |
| Editor dos blocos | Um único script genérico (`blocos/_editor/editor.js`, JS puro com `wp.element`) monta os campos a partir dos atributos do `block.json` e mostra a prévia com `ServerSideRender` | Cada agente só escreve `block.json` e `render.php`; ninguém mexe em JS de editor |
| CSS | `prototipo.css` é **congelado** (cópia do protótipo). Ajustes só em `assets/css/ajustes.css`, uma seção por módulo, com comentário | Evita conflito entre agentes e desvio do protótipo |
| Listas que mudam (eventos, artigos, ministérios, cultos, departamentos) | Blocos que consultam o WordPress (agenda, posts, páginas filhas, tipo Departamento) | O voluntário cadastra uma vez e a lista se atualiza |
| Agenda | Calculada em PHP pelas regras da igreja + eventos avulsos do tipo Evento | Igual ao gerador do protótipo; nada de cadastrar culto fixo toda semana |
| Formulários | Do núcleo, sem plugin | Menos plugins; retenção de 90 dias já resolvida |
| Plugins | Yoast SEO, Two Factor, Limit Login Attempts Reloaded, LiteSpeed Cache (só produção), UpdraftPlus (só produção) | Lista curta pedida pelo Felipe |
| Ambiente do Felipe | Windows + Docker Desktop. Scripts em Bash rodam no Git Bash; o módulo M0 entrega também `bin/setup.ps1` | Funciona sem WSL |
| Versões | WordPress 7.1, PHP 8.3, MariaDB 11.4; Hostinger com PHP 8.3 | Igual em local e produção |

## 4. Estrutura do repositório

```
docs/                          plano, specs, decisões, guia do voluntário
prototipo/                     protótipo v3 (referência visual; não editar)
content/media/                 fotos do protótipo (importadas pelo seed)
content/paginas/               conteúdo de transição (gerado; some no fim da Onda 3)
content/export/                exportação do conteúdo (bin/exportar.sh)
scripts/seed.php               orquestra o seed; chama scripts/seed/*.php em ordem
scripts/seed/NN-<modulo>.php   seed de cada módulo (um arquivo por dono)
tests/visual/                  testes de fidelidade (Playwright)
tests/php/                     testes de unidade (agenda, formulários)
wp-content/themes/ieadc-vila-ipiranga/
  functions.php header.php footer.php
  inc/                         icones.php, menu.php, blocos.php (registro automático)
  assets/css/prototipo.css     congelado
  assets/css/ajustes.css       ajustes por módulo
  assets/js/site.js            comportamento (menu, carrossel, selo "Próximo")
  blocos/_editor/              editor genérico (M1)
  blocos/<nome>/               block.json + render.php (um dono por pasta)
  page-templates/larga.php
wp-content/mu-plugins/ieadc-core/
  info.php tipos.php pedidos.php
  agenda.php                   (M2)
  departamentos.php            (M3)
docker-compose.yml bin/ config/
```

## 5. Contratos entre módulos

Estes contratos são definidos na Onda 0 e **não mudam sem PR próprio aprovado**. Assim cada agente trabalha sem esperar o outro.

### 5.1 Funções PHP (núcleo e tema)

| Função | Dono | Retorno |
|---|---|---|
| `ieadc_info(?string $chave)` | M0 | Dados da igreja (array ou string) |
| `ieadc_icone(string $nome, string $classe = 'ico')` | M0 | `<svg>` do ícone. Todos os ícones do `I` do `gen.py` entram em `inc/icones.php` |
| `ieadc_link(string $slug)` | M0 | URL da página pelo caminho (`ministerios/mocidade`) |
| `ieadc_hoje(): DateTimeImmutable` | M2 | Data de hoje em America/Sao_Paulo. Se `IEADC_HOJE` estiver definido (ex.: `2026-10-06`), usa essa data (testes) |
| `ieadc_agenda(DateTimeImmutable $de, DateTimeImmutable $ate): array` | M2 | Lista de `Ocorrencia` ordenada por data e hora |
| `ieadc_agenda_destaques(int $n, ?DateTimeImmutable $desde = null): array` | M2 | Próximos eventos especiais (Ceia, Vigília, Consagração, culto dominical temático, avulsos), sem repetir título, como `destaques()` do gerador |
| `ieadc_agenda_proximas(string $titulo, int $n): array` | M2 | Próximas datas de um culto (páginas de culto) |
| `ieadc_departamentos(): array` | M3 | Departamentos com nome, líderes e foto |

`Ocorrencia` (array): `data` (Y-m-d), `hora` (texto do protótipo: `20h`, `19h30`, `[horário a confirmar]`), `titulo`, `categoria` (`c-culto`, `c-ensino`, `c-domingo`, `c-ceia`, `c-vigilia`, `c-jovens`), `especial` (bool), `descricao`, `link` (URL ou vazio), `origem` (`regra` ou ID do Evento).

### 5.2 Blocos

- Nome `ieadc/<nome>`, pasta `blocos/<nome>/`, `apiVersion: 3`, categoria `ieadc`, `"render": "file:./render.php"`.
- O registro é automático (`inc/blocos.php` registra toda pasta com `block.json`). Ninguém edita `functions.php` para registrar bloco.
- Atributos seguem o vocabulário do editor genérico: `string` (campo), `string` com `"ieadc": {"campo": "texto-longo"}` (área de texto), `"campo": "imagem"` (ID da mídia), `"campo": "link"` (URL), `"campo": "icone"` (lista de ícones), `array` de objetos com `"campo": "lista"` (itens repetidos com sub-campos).
- `render.php` reproduz **exatamente** o HTML da seção no protótipo (mesmas tags, classes e ordem). Texto vazio não gera tag vazia.
- Texto com `[...]` vira `<span class="pend">[...]</span>` pela função `ieadc_pend()` (M1).
- Imagem: `ieadc_img_url($id, 'large')`; fundo com `style="background-image:url(...)"` igual ao protótipo, com `background-position` como atributo opcional.

### 5.3 Conteúdo e dados

| Dado | Onde fica | Campos |
|---|---|---|
| Evento avulso | tipo `evento` | `evento_data`, `evento_data_fim`, `evento_horario`, `evento_local`, `evento_categoria`, `evento_link` |
| Artigo | post comum | categoria: Mensagem, Estudo, Notícia; imagem destacada; resumo |
| Mensagem em vídeo/áudio | tipo `mensagem` | `mensagem_pregador`, `mensagem_url` (YouTube) |
| Ministério | página filha de *Ministérios* | metadados `ieadc_icone`, `ieadc_resumo`, `ieadc_cor` (c1…c7), imagem destacada (cartão) |
| Culto fixo | página (filha de *Horários e localização* no menu) | metadados `ieadc_icone`, `ieadc_resumo`, `ieadc_regra` (título usado na agenda) |
| Departamento | tipo `departamento` | título, `lideres` (texto), foto (imagem destacada; vazio = círculo com ícone, como hoje) |
| Pedido de oração | tipo `pedido_oracao` (privado) | já pronto |

### 5.4 Seed

`scripts/seed.php` só orquestra. Cada módulo entrega `scripts/seed/NN-<modulo>.php`, que pode rodar várias vezes sem duplicar nada e **não sobrescreve conteúdo editado no painel** (exceto com `IEADC_ATUALIZAR=1`). Ordem: `10-midia`, `20-paginas`, `30-menu`, `40-ministerios`, `50-cultos`, `60-departamentos`, `70-artigos`, `80-eventos`.

## 6. Módulos e donos

Cada linha é um agente. "Toca" é a lista fechada de arquivos que ele pode alterar. Qualquer outro arquivo exige PR separado do dono.

| ID | Módulo | Depende de | Toca |
|---|---|---|---|
| **M0** | Fundação e integração (agente integrador) | — | `functions.php`, `header.php`, `footer.php`, `inc/icones.php`, `inc/menu.php`, `assets/css/prototipo.css`, `docker-compose.yml`, `bin/`, `config/`, `scripts/seed.php`, `docs/` |
| **M1** | Infra de blocos + testes de fidelidade | M0 | `inc/blocos.php`, `blocos/_editor/`, `inc/helpers.php`, `tests/visual/`, `.github/workflows/` |
| **M2** | Agenda | M0 | `mu-plugins/ieadc-core/agenda.php`, `tests/php/agenda/` |
| **M3** | Tipos de conteúdo e campos | M0 | `mu-plugins/ieadc-core/tipos.php`, `departamentos.php`, `scripts/seed/40..80-*.php` |
| **B1** | Blocos de topo e navegação | M1 | `blocos/{hero,hero-home,cab,trilha,vizinhos,trocador}/` |
| **B2** | Blocos de horários e local | M1, M2 | `blocos/{semana,tabela-horarios,temas-domingo,local,faixa}/` |
| **B3** | Blocos de listas e cartões | M1, M2, M3 | `blocos/{eventos-grade,calendario,artigos-grade,ministerios-grade,cultos-grade,chamadas,trilho,galeria-faixa,departamentos}/` |
| **B4** | Blocos de conteúdo longo | M1 | `blocos/{corpo-indice,fatos,passos,leitura,linha-tempo,mvv,carrossel,aceitar-passos,contato-lista,capa,fotos,boas-vindas,artigo}/` |
| **P1** | Páginas prioritárias | B1–B4 | `scripts/seed/20-paginas-p1.php` (Início, Horários e localização, Fale conosco, Pedidos de oração) |
| **P2** | Páginas da Igreja | B1–B4 | `scripts/seed/20-paginas-p2.php` (Sobre nós, Liderança, Ministérios + 7) |
| **P3** | Demais páginas | B1–B4 | `scripts/seed/20-paginas-p3.php` (6 cultos, Eventos, Artigos + 3, Galeria, Aceitar Jesus, Política) |
| **M6** | SEO, desempenho, acessibilidade, segurança | P1 | `inc/seo.php`, `assets/css/ajustes.css` (seção M6), configuração dos plugins no seed |
| **M7** | Migração e lançamento | todos | `bin/migrar.sh`, `docs/operacao/` |

Mapa seção do protótipo → bloco (inventário feito nas 28 páginas):

| Seção no protótipo (`class`) | Bloco | Páginas |
|---|---|---|
| `hero` (home) | `hero-home` | Início |
| `hero pagina`, `hero sem-foto` | `hero` | 24 páginas |
| `trilha` | `trilha` | cultos e ministérios |
| `vizinhos`, `trocar` | `vizinhos`, `trocador` | cultos e ministérios |
| `sec … cab` | `cab` (cabeçalho de seção, usado dentro de outros blocos via função `ieadc_cab()`) | todas |
| `horarios … dias` | `semana` | Início, Horários |
| `tabela` | `tabela-horarios` | Horários |
| `faixa` | `temas-domingo` / `faixa` | Início, Horários |
| `local`, `endereco` | `local` | Início, Horários, Fale conosco |
| `fatos` | `fatos` | cultos e ministérios |
| `min-corpo / indice / lateral` | `corpo-indice` | cultos e ministérios |
| `passo-unico` | `passos` | ministérios |
| `car-ctrl` (carrossel) | `carrossel` | cultos, ministérios, artigos |
| `grade g3` de eventos / `legenda` | `eventos-grade` / `calendario` | Início, Eventos |
| `grade g3` de artigos / `g2` relacionados | `artigos-grade` | Início, Artigos, artigo |
| `min-grade` | `ministerios-grade` / `cultos-grade` | Ministérios, Horários |
| `duplas / chamada` | `chamadas` | Início, Aceitar Jesus |
| `trilho` | `trilho` (ministérios na home) | Início |
| `galeria-faixa`, `fotos` | `galeria-faixa`, `fotos` | Início, Galeria |
| `boas / moldura` | `boas-vindas` | Início |
| `linha-tempo`, `mvv`, `leitura`, `capa` | blocos de mesmo nome | Sobre nós, Política, Fale conosco |
| `aj-intro` | `aceitar-passos` | Aceitar Jesus |
| `contato-lista` | `contato-lista` | Fale conosco |
| `grade g2` de líderes | `departamentos` | Liderança |
| `artigo / migalha` | `artigo` (modelo de post) | 3 artigos |

## 7. Ondas e calendário

Os agentes de uma mesma onda trabalham ao mesmo tempo. A onda seguinte começa quando os contratos que ela usa estão no `main`, não quando a onda anterior inteira termina.

| Onda | Datas | Agentes em paralelo | Saída que libera a próxima |
|---|---|---|---|
| **0 · Contratos** | 16/11 | M0 | Plano aprovado, specs escritas, contratos (seção 5) no `main`, base atual no `main` |
| **1 · Infra** | 17–18/11 | M1, M2, M3 (3 agentes) | Registro de blocos + editor genérico + testes visuais (M1); agenda com testes (M2); tipos e campos (M3) |
| **2 · Blocos** | 19–24/11 | B1, B2, B3, B4 (4 agentes) | Cada bloco com teste de markup verde; B1 e B2 primeiro, pois as páginas prioritárias dependem deles |
| **3 · Páginas** | 23–27/11 | P1, P2, P3 (3 agentes) + M6 a partir de 25/11 | Cada página com teste visual verde nos 3 tamanhos; P1 termina até 25/11 |
| **4 · Conteúdo e ajustes** | 28–29/11 | M0 + M6 | Textos revisados, `[a confirmar]` restantes listados, SEO, Lighthouse |
| **5 · Lançamento** | 30/11 | M7 | Site no ar, checklist da seção 9 completa |

Caminho crítico: M1 (editor genérico e testes) → B1/B2 → P1. Se M1 atrasar, as páginas seguem com a versão de transição (blocos HTML), que já está fiel, e a troca para blocos próprios acontece página a página.

## 8. Como os agentes trabalham

1. Cada agente parte de uma spec (`docs/specs/<ID>-<nome>.md`) e cria a branch `feat/<id>-<nome>` a partir do `main`.
2. Só altera os arquivos da coluna "Toca". Precisa mudar contrato ou arquivo de outro dono? Abre issue/PR pequeno para o dono, sem bloquear o próprio trabalho (usa um stub local).
3. Antes do PR roda: `php -l` em tudo, testes PHP do módulo, testes visuais das páginas que usam seus blocos.
4. PR pequeno, um módulo por vez, com prints antes/depois anexados (gerados pelo teste visual). O agente integrador (M0) revisa e faz o merge; o Felipe aprova os PRs de página.
5. Conflitos: `scripts/seed.php`, `functions.php` e `prototipo.css` só o M0 altera. Ajustes de CSS vão em `ajustes.css`, na seção do módulo.

## 9. Critérios de aceite (fidelidade e qualidade)

Todos verificáveis por script em `tests/`.

**Markup** (por bloco): o HTML do bloco renderizado no WordPress é igual ao trecho do protótipo depois de normalizar espaços, URLs de imagem (`img/x.webp` ↔ URL da mídia), links internos (`x.html` ↔ `/x/`) e datas da agenda. Diferença tolerada: zero, exceto atributos de acessibilidade a mais.

**Visual** (por página, com a data congelada em 06/10/2026 por `IEADC_HOJE` e o relógio do navegador congelado):

| Largura | Página inteira | Diferença máxima de pixels |
|---|---|---|
| 1440 px | sim | 1,0% |
| 900 px | sim | 1,5% |
| 390 px | sim | 1,0% |

O protótipo é renderizado pelo próprio teste a partir de `prototipo/` (sem a faixa amarela "Protótipo do site"), então a referência é sempre a versão atual. As diferenças acima do limite geram imagem de diferença anexada ao PR.

**Interação:** menu do celular abre e fecha (inclusive com Esc), submenus por teclado, carrosséis com botões e pontos, selo "Próximo" no culto certo, formulários enviam e mostram as mensagens de sucesso e erro do protótipo.

**Editor:** todo texto, foto e link visível de cada bloco pode ser trocado no painel sem HTML; a prévia no editor é a mesma do site.

**Qualidade:** Lighthouse no celular com desempenho ≥ 85, acessibilidade ≥ 95, boas práticas ≥ 95, SEO ≥ 95; sem erros no console; sem avisos PHP com `WP_DEBUG` ligado; WCAG 2.1 AA (contraste, foco visível, rótulos).

**Lançamento:** HTTPS, indexação liberada, Search Console com sitemap, 2FA para administradores, backup testado (restaurar uma cópia), formulários chegando no e-mail real, página de privacidade publicada, LiteSpeed Cache ligado.

## 10. Specs (próximo passo, depois da aprovação)

Uma spec por módulo da seção 6 (13 specs), em `docs/specs/`, todas no mesmo modelo:

1. Objetivo e páginas afetadas
2. Arquivos que o agente toca (e os que não toca)
3. Contratos usados e fornecidos (assinaturas exatas)
4. Para cada bloco: atributos do `block.json`, HTML esperado (copiado do protótipo), trecho do protótipo de referência (arquivo e seção)
5. Dados do seed
6. Critérios de aceite específicos e comandos de teste
7. Fora do escopo

## 11. Riscos e como tratar

| Risco | Tratamento |
|---|---|
| Textos e dados `[a confirmar]` não chegarem até 28/11 | Vão ao ar marcados em amarelo; lista final enviada ao pastor em 28/11 |
| Fotos dos líderes ainda não tiradas | Bloco Departamentos mostra o círculo com ícone até a foto chegar |
| Editor genérico mais lento que o previsto | Páginas usam a versão de transição; troca por blocos página a página |
| Ambiente de nuvem não baixa do wordpress.org | Testes na nuvem usam uma cópia local do Blocksy; o `setup.sh` no Windows baixa o original |
| Fins de linha do Windows quebrarem scripts | `.gitattributes` com `eol=lf` para `*.sh` e `*.php` |
| Data do servidor muda a agenda e quebra o teste visual | `IEADC_HOJE` no ambiente de teste |
| Acesso à Hostinger no dia | Felipe libera o acesso SSH/SFTP até 28/11 |

## 12. O que depende do Felipe

1. Aprovar este plano (ou pedir mudanças) para eu escrever as specs.
2. Aprovar o PR que troca o conteúdo do `ieadcvilaipiranga-theme` (sai a cópia do Blocksy, entra o projeto).
3. Rodar `bin/setup.ps1` (ou `bin/setup.sh` no Git Bash) no Windows quando a Onda 1 terminar, para conferir na rede de casa.
4. Até 28/11: acesso à Hostinger (SSH ou SFTP + painel) e o e-mail que recebe pedidos de oração e o Fale conosco.
5. Revisar os PRs de página (Onda 3).
