# Specs de desenvolvimento

Cada agente recebe **uma** spec, cria a branch `feat/<id>-<nome>` a partir do `main` e abre um PR. Leia antes a [convenções](00-convencoes.md) (vale para todos) e o [plano](../plano/00-plano-de-construcao.md).

| Spec | Módulo | Onda | Depende de | Datas |
|---|---|---|---|---|
| [M0](M0-fundacao-e-integracao.md) | Fundação e integração | 0 → 5 | — | 16/11 e contínuo |
| [M1](M1-infra-de-blocos-e-testes.md) | Infra de blocos, editor genérico e testes de fidelidade | 1 | M0 | 17–18/11 |
| [M2](M2-agenda.md) | Agenda (regras da igreja + eventos avulsos) | 1 | M0 | 17–18/11 |
| [M3](M3-tipos-de-conteudo.md) | Tipos de conteúdo, campos e seed de dados | 1 | M0 | 17–18/11 |
| [B1](B1-blocos-topo-e-navegacao.md) | Blocos de topo e navegação (4) | 2 | M1 | 19–20/11 |
| [B2](B2-blocos-horarios-e-agenda.md) | Blocos de horários, local e agenda (7) | 2 | M1, M2 | 19–22/11 |
| [B3](B3-blocos-listas-e-cartoes.md) | Blocos de listas e cartões (11) | 2 | M1, M3 | 19–24/11 |
| [B4](B4-blocos-conteudo-longo.md) | Blocos de conteúdo longo (15) | 2 | M1 | 19–24/11 |
| [P1](P1-paginas-prioritarias.md) | Início, Horários e localização, Fale conosco, Pedidos de oração | 3 | B1–B4 | 23–25/11 |
| [P2](P2-paginas-a-igreja.md) | Sobre nós, Liderança, Ministérios + 7 | 3 | B1–B4 | 24–27/11 |
| [P3](P3-paginas-demais.md) | 6 cultos, Eventos, Artigos + 3, Galeria, Aceitar Jesus, Política | 3 | B1–B4 | 24–27/11 |
| [M6](M6-seo-desempenho-acessibilidade.md) | SEO, desempenho, acessibilidade e segurança | 3–4 | P1 | 25–29/11 |
| [M7](M7-migracao-e-lancamento.md) | Migração e lançamento | 5 | todos | 28–30/11 |

Arquivos de apoio (gerados por `scripts/referencias_specs.py`, não editar à mão):

- [`mapa-secoes.json`](mapa-secoes.json): para cada página do protótipo, a ordem dos blocos. É o roteiro dos agentes P1–P3.
- [`referencias/<bloco>.html`](referencias/): todas as ocorrências de cada seção no protótipo. É o **HTML esperado** de cada bloco (37 blocos).
