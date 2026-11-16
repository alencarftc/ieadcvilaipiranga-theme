# Monitoramento do site e ajustes da hospedagem

Escrito em 16/11/2026 (fase 4). Para quem cuida do site, em linguagem simples. Serve para saber **na hora** se o site saiu do ar, e para conferir o que deve estar ligado na hospedagem. Nenhum passo exige mexer no código.

## 1. Avisar por e-mail quando o site cair (grátis)

O jeito mais simples é um serviço de monitoramento gratuito, como o **UptimeRobot** (ou um equivalente: Better Stack, StatusCake, Freshping). Ele visita o site a cada poucos minutos e manda um e-mail se não houver resposta.

1. Crie uma conta gratuita no serviço (com um e-mail da igreja que alguém lê todo dia). A senha fica só com você.
2. Crie um monitor novo:
   - **Tipo:** "HTTP(s)" (monitor de endereço da web).
   - **Nome:** `Site IEADC Vila Ipiranga`.
   - **Endereço (URL):** o endereço do site, com `https://` (por exemplo, a página inicial). Para saber se o WordPress responde de verdade, use também um segundo monitor na página `https://.../contato/` (assim um erro só na página aparece).
   - **Intervalo:** o menor que o plano grátis permitir (5 minutos é suficiente).
3. Em **contatos de alerta**, confirme o e-mail (o serviço manda um link de confirmação) e marque-o no monitor. Se quiser, acrescente um segundo e-mail (por exemplo, o da Comunicação).
4. Salve e espere o primeiro ciclo: o monitor deve ficar **verde** ("Up"). Para testar o alerta, pause o monitor e veja se o serviço não reclama; para testar de verdade, use a opção de "alerta de teste" do próprio serviço.
5. Quando o site estiver **fechado** (a página "em preparação" responde com o código 503) o monitor mostrará queda: isso é esperado. Pause o monitor enquanto o site estiver fechado e reative no dia em que abrir.

Dica: marque no monitor a opção de **verificar a validade do certificado HTTPS** (se existir): ela avisa dias antes de o certificado vencer.

## 2. O que o Felipe confere no painel da hospedagem (hPanel)

Os nomes das telas mudam com o tempo; procure pelos termos abaixo (nada aqui depende de uma tela específica).

| O que | Como deve ficar | Por quê |
|---|---|---|
| **Forçar HTTPS** (SSL) | Ligado para o domínio (todo acesso `http://` vai para `https://`). | O site usa HSTS e a CSP presume HTTPS; sem isso a primeira visita pode ir sem cadeado. |
| **Certificado SSL** | Ativo e com renovação automática. | O monitor da seção 1 avisa se vencer. |
| **Backups** | Backup automático da hospedagem ligado **e** o UpdraftPlus configurado para um destino fora do servidor (`docs/operacao/manutencao.md`, seção 7). | Se o servidor falhar, o conteúdo não se perde. Teste uma restauração uma vez. |
| **Versão do PHP** | **PHP 8.3** (ou a mais nova que o WordPress e os plugins aceitem). | Segurança e velocidade. |
| **Cache do site** (LiteSpeed) | Ligado, com o plugin LiteSpeed Cache configurado como em `docs/operacao/plugins.md`. | É ele que faz o site responder em dezenas de milissegundos. |
| **Atualizações automáticas** | WordPress (versões de segurança) e plugins conferidos toda semana. | Corrige falhas conhecidas. |
| **Contas e acessos** | Autenticação em duas etapas nas contas de administrador; chaves SSH só para o deploy. | Evita invasão por senha vazada. |
| **E-mail do administrador** | Um e-mail que alguém lê (os avisos de atualização e de falha chegam nele). | Sem isso, avisos ficam sem resposta. |

## 3. Se o monitor avisar que o site caiu

1. Abra o endereço no celular, fora do Wi-Fi, para saber se é o site ou a sua conexão.
2. Veja o painel da hospedagem (aviso de manutenção, cota de disco ou de memória estourada).
3. Se o painel estiver normal, o último passo conhecido é voltar ao código anterior (`docs/operacao/lancamento.md`, "Voltar atrás") ou chamar o suporte da hospedagem informando o horário do alerta.
