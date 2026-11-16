# SEO e Google: o que já está pronto e o que só você pode fazer

Escrito em 16/11/2026 (fase 2 do plano de melhorias). Para o Felipe e para quem cuida do site, sem termos técnicos.

**Em uma frase:** o site já "conversa" bem com o Google (títulos, descrições, mapa do site e dados da igreja em formato que o Google lê). O que falta depende de contas que só você controla: o **Perfil da Empresa no Google** (o cartão da igreja na Pesquisa e no Maps) e o **Google Search Console** (o painel do Google para o site). É nesses dois lugares, mais do que no site, que o Google e o Gemini buscam endereço, telefone, horários e avaliações.

## 1. O que o site já faz sozinho

- Cada página tem **título único** (até 60 letras) e **descrição** (70 a 160 letras) e uma foto para quando o link é compartilhado (WhatsApp, Facebook etc.). A descrição é o campo **Resumo** da página no painel; se estiver vazio, vale o texto de abertura.
- O Google recebe, em cada página, os **dados da igreja em formato de máquina**: nome, endereço, telefone, e-mail, Instagram, Facebook, YouTube, site da sede e os horários dos cultos da semana. Tudo vem de *Configurações > IEADC Vila Ipiranga* (mudou lá, muda para o Google). Também recebe a "migalha" de cada página, cada artigo e **cada evento cadastrado** (data, hora e local).
- A página **Horários e localização** tem perguntas e respostas visíveis (onde fica, horários, Ceia, contato) e o mesmo texto vai marcado para o Google.
- O **mapa do site** (`/sitemap_index.xml`), o `robots.txt` e um resumo da igreja em `/llms.txt` (texto curto para leitores automáticos) estão no ar.
- Há uma seção **"Já esteve conosco? Conte como foi."** na página inicial, em Contato e em Horários e localização, com o botão **Avaliar no Google**.
- O site **não mostra nota nem número de avaliações** (não temos esses dados e não se deve inventar).

## 2. O que só você pode fazer (nesta ordem)

### A. Perfil da Empresa no Google (o mais importante)

1. Entre com uma conta Google da igreja (de preferência uma conta da liderança, não pessoal) em **business.google.com**. Procure "IEADC Vila Ipiranga". Se já existir, peça para **ser o proprietário**; se não, crie.
2. **Verifique** o perfil (o Google pede um código por correio, telefone ou vídeo; siga o que ele oferecer).
3. Preencha **igual ao site** (veja a tabela da seção 4): nome, endereço, telefone, site, horários e categoria principal **"Igreja evangélica"**.
4. Acrescente **fotos** reais (fachada, culto, ambientes; sem o pastor João e só com autorização de quem aparece) e uma descrição curta com as palavras do próprio site.
5. **Pegue o link de avaliação**: no perfil, procure **"Pedir avaliações"** (ou "Compartilhar formulário de avaliação") e copie o link. Cole em *Configurações > IEADC Vila Ipiranga > Link para avaliar no Google*. Também vale o endereço `search.google.com/local/writereview?placeid=...` ou só o código do lugar (começa com `ChIJ`). Sem esse campo, o botão do site abre a igreja no Google Maps, de onde a pessoa também consegue avaliar.
6. Não ofereça brinde nem peça "só nota máxima": o Google proíbe e pode apagar as avaliações.

### B. Google Search Console

1. Entre em **search.google.com/search-console** com a mesma conta e escolha **Adicionar propriedade > Domínio**, digitando `ieadcvilaipiranga.com.br`.
2. O Google mostra um texto para colar num registro **TXT do DNS** (no Registro.br ou na hospedagem). Quem administra o domínio faz isso em poucos minutos; depois clique em **Verificar**.
3. Em **Sitemaps**, envie `https://ieadcvilaipiranga.com.br/sitemap_index.xml`.
4. Depois do lançamento, use **Inspeção de URL** na página inicial e clique em **Solicitar indexação**. Volte em uma semana para ver as páginas indexadas e os avisos.
5. Para conferir os dados da igreja: abra **search.google.com/test/rich-results**, cole o endereço da página inicial e veja se aparece "Organização"/"Igreja" sem erros. (Os quadros de perguntas dos resultados do Google hoje só aparecem para alguns sites; a marcação continua útil para o Google entender a página.)

## 3. Como o Google e o Gemini escolhem o que mostrar

- Para **dados de lugar** (endereço, telefone, horário, fotos, avaliações), a Pesquisa, o Maps e o Gemini usam principalmente o **Perfil da Empresa**, e conferem com o **site** e com outros lugares da internet. Quando as informações **batem**, o Google confia; quando divergem (um telefone antigo, um horário diferente), ele erra ou esconde.
- O site ajuda com **dados estruturados** (já feito) e com texto claro nas páginas. O Google informa que os recursos de IA dele seguem as mesmas regras de sempre da Pesquisa: **não existe marcação especial para IA**. O `llms.txt` é só uma cortesia, não conte com ele.
- O que mais pesa e só depende de manter em dia: **nome, endereço e telefone iguais em todo lugar** (site, Perfil da Empresa, Instagram, Facebook, YouTube e site da sede), **horários atualizados**, **fotos** e **avaliações** recentes.

## 4. Dados que devem ser idênticos em todo lugar

| Dado | Como está no site (*Configurações > IEADC Vila Ipiranga*) |
|---|---|
| Nome | IEADC Vila Ipiranga (nome completo: Congregação da Igreja Evangélica Assembleia de Deus em Curitiba) |
| Endereço | Rua Marechal Althayr Roszanniy, 555 - Capão Raso, Curitiba - PR |
| Telefone | +55 (41) 98838-1994 |
| Horários | Segunda 20h (Culto de Oração) · Quarta 20h (Culto de Ensino) · Quinta 20h (Círculo de Oração) · Sexta 19h30 (Ensinai) · Domingo 9h30 (EBD) e 19h (culto) · 2º sábado 19h30 (Ceia do Senhor) |
| Redes | as três do rodapé do site |

Mudou algo (horário, telefone, endereço)? Mude **primeiro no painel do site** e **no mesmo dia** no Perfil da Empresa.

## 5. Onde mexer no painel do site

- *Configurações > IEADC Vila Ipiranga*: endereço, telefone, e-mail, redes, cultos, **Link para avaliar no Google** e, se quiser, **latitude e longitude** (opcionais; ajudam o Google a achar a igreja no mapa).
- *Páginas > editar > Resumo*: a descrição que o Google mostra. 
- *Eventos > Adicionar novo*: cada evento vira página própria e entra no Google como evento.
- *Posts*: cada artigo vai para o Google com título, data e foto; use foto de destaque.

## 6. Pendências (só você tem o dado)

Estão em `docs/operacao/pendencias-para-o-felipe.md`, no bloco "SEO e Google": link de avaliação (ou código do lugar), propriedade do Perfil da Empresa, verificação do Search Console, e-mail público e, se quiser, as coordenadas.

## Campos novos da fase 4: verificação e medição

- **Search Console e Bing sem mexer no DNS:** em *Configurações > IEADC Vila Ipiranga* há os campos *Código do Google Search Console* e *Código do Bing Webmaster Tools*. Cole o código do método "Tag HTML" (pode colar a meta tag inteira); o site imprime a meta tag certa. Depois, "Verificar" no serviço e enviar o `sitemap_index.xml`.
- **Arquivos de categoria** (`/category/mensagem/` etc.) ficam **fora do Google** (`noindex, follow`) e fora do mapa do site: com poucos artigos eles só repetiriam a lista de Conteúdos. Cada categoria tem descrição própria (meta description e prévia ao compartilhar), editável em *Artigos > Categorias*. Para indexar as categorias no futuro, quando houver muitos artigos: `add_filter('ieadc_arquivos_indexaveis', '__return_true')`.
- **Medição (Analytics, Tag Manager) e aviso de cookies:** `docs/operacao/rastreamento.md`.
