# Design Brief: Site Institucional — IEADC Vila Ipiranga

*Elaborado por: Felipe Alencar · Data: 2026-09-17 · Status: Rascunho*

> **Atualização (2026-09-18):** nome do pastor confirmado — Pr. Robson Stein. O questionário de coleta de direção foi extraído para um arquivo dedicado: [questions-brief-stakeholder-ieadc-vila-ipiranga.md](questions-brief-stakeholder-ieadc-vila-ipiranga.md).

## 1. Visão Geral do Projeto

- **Nome do projeto:** Site Institucional — IEADC Vila Ipiranga
- **Descrição:** Site institucional para a Assembleia de Deus (IEADC) Vila Ipiranga, criado para ser a porta de entrada digital da igreja — centralizando horários, localização, eventos, conteúdo e formas de conexão, hoje fragmentados entre Instagram, Facebook e uma ficha do Google desatualizada. Igreja liderada pelo Pr. Robson Stein.
- **Tipo de projeto:** Site institucional (WordPress)
- **Cronograma:** [A DEFINIR — não há data de início nem de lançamento fixadas; o ritmo depende do alinhamento de conteúdo com o pastor]
- **Principais marcos:** [A DEFINIR]
- **Considerações de orçamento:** Orçamento mínimo. Hospedagem já paga (Hostinger plano Unlimited, contratado por 4 anos), com domínio grátis no 1º ano. Tema, plugins e demais recursos devem priorizar soluções gratuitas/open-source ou construção do zero — não há verba prevista para licenças pagas.

## 2. Contexto de Negócio

- **Objetivos de negócio:** Aumentar o número de visitantes presenciais nos cultos e eventos da igreja; comunicar que a IEADC Vila Ipiranga é uma igreja alinhada aos princípios do Senhor; ser reconhecida como um lugar acolhedor tanto para quem já é crente quanto para quem está em processo de evangelização.
- **Métricas de sucesso e KPIs:** [A DEFINIR — nenhuma métrica numérica formal ainda; o objetivo hoje é presença e credibilidade percebidas, não uma meta quantitativa]
- **Resultados de negócio almejados:** Mais pessoas encontrando a igreja e comparecendo a cultos/eventos; correção da percepção pública de que a igreja pode não estar mais ativa (hoje o Google exibe informações e fotos antigas que sugerem isso).
- **Alinhamento estratégico:** Não faz parte de uma iniciativa formal maior da igreja — é uma iniciativa voluntária individual, conduzida por um membro sozinho, buscando alinhamento direto com o pastor como principal instância de aprovação.

## 3. Contexto do Usuário

- **Público-alvo / personas:** (1) Membros atuais da igreja, buscando informação prática do dia a dia (horários, eventos, contato); (2) Público em geral pesquisando a igreja online antes de visitar pela primeira vez — incluindo pessoas em processo de evangelização.
- **Objetivos e necessidades do usuário:** Encontrar rapidamente horário de cultos, endereço/como chegar, e entender "quem é" a igreja (ministérios, liderança, eventos) antes de decidir visitar.
- **Dores do usuário a serem resolvidas:**
  - Hoje a igreja é encontrada por três canais com qualidade desigual: Instagram (atualizado), Facebook (atualizado) e Google Maps (desatualizado, com fotos antigas).
  - Ao pesquisar "IEADC Vila Ipiranga" no Google, os resultados (informações e fotos antigas) passam a impressão de que a igreja pode não estar mais funcionando — mesmo com o Instagram aparecendo em primeiro lugar.
  - O horário dos cultos hoje só é encontrável vasculhando Stories específicos do Instagram, que são temporários e não ficam centralizados em nenhum lugar fixo — não é uma informação fácil de achar rapidamente.
- **Considerações sobre a jornada do usuário:** O ponto de entrada mais provável de quem "é de fora" é uma busca no Google ou um link a partir do Instagram/Facebook. O site precisa corrigir a primeira impressão que a busca no Google transmite hoje, e fixar horário/endereço em um lugar permanente e fácil de achar — diferente de um Story que expira.

## 4. Escopo do Projeto

- **Dentro do escopo (Fase 1):** horário de cultos e localização (endereço + mapa); agenda de eventos; apresentação da igreja e liderança (história, pastor(es), ministérios, missão/visão); artigos; galeria de mídia; ministérios; caminho claro para "aceitar Jesus"; contato.
- **Fora do escopo (por enquanto):** doações (depende de alinhamento com a tesouraria — ver [questionário ao pastor](questions-brief-stakeholder-ieadc-vila-ipiranga.md), seção 4); pedidos de oração (com roteamento a obreiro/pastor), rádio ao vivo, e Telepaz (mensagens bíblicas em áudio curtas) — confirmados como itens de fases futuras. A decisão explícita é "começar com o básico que funciona e resolve os problemas atuais" antes de expandir.
- **Fase 1 vs. fases futuras:** Fase 1 é o essencial institucional listado acima. Fases futuras (doações, oração, rádio, Telepaz) não têm data — mas a arquitetura da Fase 1 não deve bloquear a adição desses recursos depois.
- **Dependências e restrições:**
  - Conteúdo (textos, fotos, horários oficiais) ainda não foi coletado — depende de alinhamento com o pastor, que tem agenda concorrida, e possivelmente de obreiros. Quem exatamente vai escrever o copy ainda não está definido.
  - Hospedagem compartilhada (Hostinger Unlimited, PHP/MySQL) — a stack está fixada em WordPress em função dela.
  - Equipe de um único voluntário (o autor deste brief) conduzindo todo o projeto sozinho.

## 5. Requisitos

- **Requisitos funcionais:** as páginas/seções listadas em "Dentro do escopo", mais duas automações explicitamente desejadas para reduzir manutenção manual:
  - Galeria do site sincronizada automaticamente com as fotos publicadas no Instagram da igreja, em vez de upload manual.
  - Quando "pedidos de oração" for implementado (fase futura), o envio deve ser automático a um obreiro/pastor responsável, em vez de exigir checagem manual.
- **Requisitos não funcionais:** leve, funcional, otimizado (performance), eficaz, user-friendly e acessível — **WCAG 2.1 AA** é o padrão de acessibilidade confirmado.
- **Requisitos técnicos:** WordPress (PHP/MySQL) hospedado na Hostinger, plano Unlimited (contratado por 4 anos, domínio grátis no 1º ano). O plano também disponibiliza Node.js, mas essa capacidade não é usada na stack escolhida. Priorizar plugins leves e gratuitos para preservar a performance.
- **Requisitos de conteúdo** — detalhamento do que precisa ser coletado junto ao pastor/obreiros antes ou durante a construção, por seção:
  - **Institucional:** nome oficial completo, endereço completo, telefone/WhatsApp de contato, e-mail, links das redes sociais oficiais.
  - **Horários:** dias e horários de cada culto/atividade regular (ex: culto de domingo, culto de quarta, EBD, sábado) — confirmar quais realmente existem hoje.
  - **Sobre a igreja:** texto de apresentação/história da congregação, missão/visão/valores, denominação (Assembleia de Deus/IEADC), tempo de existência.
  - **Liderança:** pastor titular confirmado — Pr. Robson Stein, apresentado com nome e função como os demais líderes/obreiros, sem biografia individual (ver [questionário ao pastor](questions-brief-stakeholder-ieadc-vila-ipiranga.md)); fotos de todos ainda pendentes.
  - **Ministérios:** lista dos ministérios ativos (ex: louvor, jovens, crianças, casais) com uma descrição breve de cada um.
  - **Eventos:** lista de eventos/agenda próxima, com data, horário, local e descrição.
  - **Artigos:** definir quem escreve, com que frequência, e sobre quais temas.
  - **Mídia/Galeria:** conjunto inicial de fotos aprovadas para o lançamento (cultos, eventos, ambiente), além da integração automática com o Instagram.
  - **Caminho "Aceitar Jesus":** texto explicando o passo de fé, com CTA e, se possível, uma forma de acompanhamento (contato/formulário).
  - **Doações (fase 2 — depende de tesouraria, ver [questionário ao pastor](questions-brief-stakeholder-ieadc-vila-ipiranga.md), seção 4):** dados bancários/PIX ou gateway de pagamento a ser usado, e um texto explicando como e por que doar.
  - **Legal/rodapé:** política de privacidade (relevante por causa da LGPD, já que haverá formulários coletando dados), e termos, se aplicável.
  - **Responsável pela coleta:** dividida em três frentes (detalhe no [questionário ao pastor](questions-brief-stakeholder-ieadc-vila-ipiranga.md)) — direção e conteúdo pessoal com o Pastor Robson Stein; dados operacionais (contato, horários, mídia, doações, legal) com tesouraria/obreiros/mídias sociais, num segundo briefing; itens verificáveis publicamente (redes sociais, endereço, fotos públicas) coletados diretamente pelo autor deste brief.
- **Diretrizes de marca e restrições:** a igreja não possui identidade visual própria definida além do logotipo da denominação (Assembleia de Deus) — o visual de cada culto, evento e confraternização varia. Não existe manual de marca a seguir; a direção visual precisa ser criada como parte do projeto (ver seção 6), sujeita à aprovação do pastor.

## 6. Objetivos de Design

- **Principais objetivos de design:** transmitir que a igreja está ativa, viva e funcionando — corrigindo a impressão hoje deixada pela busca no Google; acolher tanto quem já é da fé quanto quem está em processo de evangelização; tornar informação prática (horário, endereço) instantaneamente encontrável, sem depender de Stories temporários.
- **Princípios de design:**
  - Leveza e performance acima de efeitos visuais.
  - Acessibilidade real (WCAG 2.1 AA), não decorativa.
  - Automação sobre manutenção manual sempre que possível (ex: galeria via Instagram, roteamento automático de pedidos de oração no futuro).
  - Caminho de conversão sempre visível, mas nunca agressivo.
- **Restrições de design:** sem orçamento para assets pagos (fotografia profissional, tema premium); sem identidade visual de marca própria além do logo denominacional — a direção visual precisa ser definida como parte do projeto, não herdada de um manual existente.
- **Marca e considerações de estilo:** você indicou 5 sites de referência de que gosta. Consegui analisar 4 automaticamente (assembleia.org.br, pibcuritiba.org.br, ipb.org.br, abba.org.br); o site da Alcance Curitiba não carregou via fetch automatizado e vale uma checagem manual à parte. Um padrão consistente aparece entre os que você gosta:
  - **Paleta:** neutra e sóbria — predominância de branco/cinza-claro como base, com cor usada em acentos pontuais, nunca dominando o layout. Reforça seriedade institucional sem parecer frio.
  - **Tom de voz:** "formal mas acessível" e "caloroso e acessível" ao mesmo tempo — frases de boas-vindas diretas ("Seja muito bem-vindo à casa do Pai", "Sou Novo por aqui") convivem com linguagem institucional mais sóbria nas áreas administrativas.
  - **Estrutura de home recorrente:** navegação → horários de culto em destaque logo no topo → vídeo/transmissão ao vivo → boas-vindas/sobre → eventos → doação (discreta, nunca como pop-up ou intrusão) → múltiplos canais (Instagram, YouTube, rádio, Spotify).
  - **Segmentação dupla explícita:** um caminho claro para "quem é novo" (Sou Novo Aqui / Jesus / horários), separado de um caminho para membros ativos (Central do Membro, Dízimos, Células) — confirma que a estratégia de dupla audiência que você descreveu (crente + descrente em evangelização) já é um padrão validado nesse tipo de site.
  - **Rádio online e pedidos de oração** aparecem como funcionalidades comuns nesses sites de referência — reforça que os itens do seu roadmap futuro estão alinhados ao que igrejas semelhantes já oferecem.
  - Não foi possível analisar visualmente o Instagram (@ieadc.vilaipiranga) nem o Facebook da igreja de forma automatizada — essas páginas exigem login/JS que a ferramenta de leitura não renderiza. Recomenda-se revisão manual direta dessas duas páginas para extrair a paleta de cores e o tipo de fotografia real da igreja antes de fechar a direção visual definitiva — isso pode acontecer já na etapa seguinte de definição visual (`/impeccable shape` ou construção da primeira superfície).
  - **Referências adicionais dentro da própria Assembleia de Deus (analisadas em 2026-09-18):** adsantoandre.com.br/institucional e assembleia.org.br/sobre confirmam o mesmo padrão sóbrio/neutro das referências anteriores (que eram de outras denominações — Batista, Presbiteriana), agora dentro da própria AD: paleta neutra (branco/cinza/preto), logo denominacional como âncora de identidade, fotos reais da liderança combinadas com alguma ilustração genérica pontual, tom formal e institucional. A página da AD Santo André também revela dois padrões aproveitados no [questionário ao pastor](questions-brief-stakeholder-ieadc-vila-ipiranga.md): liderança auxiliar apresentada só com foto + nome + cargo (sem biografia individual), e uma estrutura formal de Missão/Visão/Valores separada de uma seção de Doutrina/"Em que Cremos" — esta última, por ser doutrina denominacional, é a mais plausível de reaproveitar quase sem alteração.

## 7. Critérios de Sucesso

- **Como o sucesso será medido:** [A DEFINIR — nenhuma ferramenta de mensuração formal definida ainda. Recomenda-se instalar Google Analytics/Search Console e atualizar a ficha do Google Business Profile da igreja como parte do lançamento, já que a busca no Google hoje é uma dor identificada diretamente.]
- **Indicadores-chave de desempenho (KPIs):** [A DEFINIR]
- **Métricas de satisfação do usuário:** [A DEFINIR]
- **Métricas de impacto no negócio:** percepção informal de aumento de visitantes/novos rostos nos cultos e eventos — sem mecanismo formal de rastreamento definido ainda.

## 8. Cronograma e Entregáveis

- **Fases e marcos do projeto:** [A DEFINIR — sem cronograma formal; o ritmo depende da disponibilidade do voluntário solo e da velocidade de alinhamento de conteúdo com o pastor.]
- **Principais entregáveis:** [A DEFINIR — a sequência natural após este brief é: coleta de conteúdo junto ao pastor/obreiros → direção visual → construção do tema WordPress → revisão do pastor → lançamento.]
- **Processo de revisão e aprovação:** o pastor é o aprovador final; ainda não há um processo formal definido (quantas rodadas de revisão, como o feedback será coletado).
- **Cronograma de lançamento:** [A DEFINIR]

## 9. Equipe e Stakeholders

- **Membros da equipe do projeto:** o autor deste brief, como voluntário solo responsável por toda a condução técnica e de design do projeto.
- **Principais stakeholders:** o Pr. Robson Stein (aprovador final); possivelmente obreiros que podem colaborar com a produção de conteúdo/copy (papel ainda não definido).
- **Tomadores de decisão:** o Pr. Robson Stein tem a palavra final sobre o resultado.
- **Plano de comunicação:** dividido em duas rodadas — um [formulário curto de direção](questions-brief-stakeholder-ieadc-vila-ipiranga.md) com o Pr. Robson Stein, e um segundo formulário operacional com tesouraria/obreiros/mídias sociais depois, em vez de uma lista única e informal.

## 10. Riscos e Premissas

- **Riscos potenciais:**
  - Alinhamento com o pastor: agenda concorrida dificulta validação e coleta de direcionamento/conteúdo, podendo atrasar o projeto ou levar a decisões tomadas sem aprovação prévia.
  - Conteúdo pendente: boa parte do copy/material ainda não foi coletado, e quem fica responsável pela coleta (o autor ou obreiros) ainda não está definido.
  - Dependência de atualização manual: sem automação, itens como a galeria de fotos e futuros pedidos de oração podem ficar desatualizados do mesmo jeito que a ficha do Google está hoje — repetindo o problema que o projeto busca resolver.
  - Ausência de identidade visual própria: a igreja não tem uma marca visual definida além do logo denominacional, então a direção visual precisa ser criada, não apenas aplicada.
  - Disponibilidade do voluntário solo: risco considerado baixo pelo próprio autor, desde que o alinhamento de requisitos com o pastor seja resolvido cedo no processo.
- **Premissas assumidas:** o pastor vai aprovar decisões tomadas com base no que o voluntário "acha que ele e a igreja vão gostar", na ausência de um alinhamento mais aprofundado; a hospedagem Hostinger (plano pago de 4 anos) permanece estável e suficiente para as necessidades do site.
- **Estratégias de mitigação:** priorizar automação sempre que possível (galeria sincronizada com Instagram; roteamento automático de pedidos de oração quando essa fase for implementada) para reduzir dependência de atualização manual; usar a lista detalhada de requisitos de conteúdo (seção 5) como pauta objetiva para uma conversa única e eficiente com o pastor, em vez de múltiplas rodadas informais.
- **Questões em aberto:** cronograma/data de lançamento; quem exatamente vai escrever/fornecer o copy (o próprio voluntário, obreiros, ou ambos); ferramenta de mensuração de sucesso; direção visual definitiva (pendente de revisão manual do Instagram/Facebook da igreja e do site de referência que não pôde ser analisado automaticamente).

## 11. Próximos Passos

A coleta de conteúdo/direção junto ao Pr. Robson Stein está detalhada em dois arquivos dedicados: **[questionario-ieadc-vila-ipiranga.md](questionario-ieadc-vila-ipiranga.md)** (o formulário em si — título, descrição e perguntas, pronto para virar Google Form via `scripts/gerar-formulario-pastor.gs`) e **[questions-brief-stakeholder-ieadc-vila-ipiranga.md](questions-brief-stakeholder-ieadc-vila-ipiranga.md)** (o porquê da abordagem, o esboço do Briefing 2 para tesouraria/obreiros/mídias sociais, e a lista do que este autor coleta diretamente).

Sequência após este brief: coleta de direção com o pastor → Briefing 2 com tesouraria/obreiros/mídias sociais → definição da direção visual (`/impeccable shape`) → construção do tema WordPress → revisão do pastor → lançamento.

