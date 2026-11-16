# Prompt para retomar o projeto no Claude Code

Abra o Claude Code na pasta do repositório (`ieadcvilaipiranga-theme`) e cole o texto abaixo.

```
Você vai continuar o site da IEADC Vila Ipiranga (WordPress, tema filho do Blocksy), que já está construído e passa em todos os testes de fidelidade ao protótipo. Responda sempre em português do Brasil.

1. Leia CLAUDE.md, docs/operacao/continuidade.md e docs/operacao/lancamento.md. Em seguida, docs/specs/revisao.md e docs/specs/00-convencoes.md.
2. Confirme o ambiente: o .env existe com IEADC_HOJE=2026-10-06 e o site local responde em http://localhost:8080 (se não, rode bin\setup.ps1 no Windows). Rode ./bin/testar.sh e informe o resultado; o esperado é php 40, markup 28, visual 84 e interação 8 passando.
3. Trabalhe nas pendências da seção 2 de docs/operacao/continuidade.md, nesta ordem: (a) ensaio de migração com bin\migrar.ps1 (o script nunca rodou de verdade: conserte o que quebrar, sem colocar o site no ar); (b) docs/operacao/manutencao.md para a equipe de Comunicação (publicar artigo e evento, trocar foto, ler pedidos de oração, backup e restauração); (c) Lighthouse e axe no domínio, corrigindo o que for do tema; (d) lançamento com -Lancar, somente depois que o Felipe disser "pode lançar".
4. Regras que não podem quebrar: o site deve ficar o mais parecido possível com o protótipo em prototipo/; não invente dados da igreja (use [a confirmar] com a classe pend); fotos coloridas, sem repetir na página e sem fotos com o pastor João; não coloque senhas, .env, banco ou content/backup no git; pedidos de oração são privados.
5. Todo trabalho vai em branch e PR. Antes de mesclar, faça a revisão de docs/specs/revisao.md com um subagente revisor (Sonnet, lendo só o diff e rodando ./bin/testar.sh); nunca mescle o que você mesmo escreveu sem essa revisão. Se uma ação for bloqueada por permissão, não contorne: peça ao Felipe a aprovação, citando a ação e o alvo.
6. Pergunte ao Felipe o mínimo possível. Só leve a ele o que realmente bloqueia: senha ou acesso da Hostinger, e-mails reais dos formulários, dados da igreja que não existem em lugar nenhum e o "pode lançar".
7. No fim de cada etapa, atualize docs/operacao/continuidade.md (estado e pendências) e resuma o que foi feito, o que foi testado e o que falta.
```
