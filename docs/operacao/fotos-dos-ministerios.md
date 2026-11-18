# Fotos dos ministérios e da Ceia (18/11/2026)

O Felipe reuniu fotos reais da igreja, uma pasta por ministério ou página, e elas substituíram as fotos que estavam no site. Este arquivo guarda o que foi feito, para quem for trocar fotos de novo.

## Regra principal: nenhuma foto de criança

O Felipe pediu que **fotos de crianças não sejam publicadas** (repositório público e site). Por isso:

- A pasta **Infantil** não foi usada: a página **Crianças** (e o cartão dela em Ministérios) continua com as fotos que já tinha.
- Nas demais pastas, só entraram fotos em que **nenhuma criança aparece**. Onde não sobrou foto segura, o arquivo antigo ficou (lista abaixo).
- A conferência foi visual, foto a foto. Se, depois de publicado, alguém notar uma criança (ou adolescente sem autorização) em qualquer foto, avise qual é e ela sai no mesmo dia.
- As autorizações de imagem ficam guardadas no Drive da igreja (informação do Felipe). A página Crianças mantém o aviso de autorização de imagem; o Ensinai não precisa de aviso.

## Como foi feito

- Cada foto nova ocupa o **mesmo arquivo** de `content/media/` que a antiga (mesmo nome), então páginas, blocos, `scripts/seed/` e protótipo (`prototipo/` lê as imagens de `content/media/`) continuam iguais. Nenhum bloco mudou.
- Cada arquivo novo mantém a **mesma proporção** (largura por altura) do antigo, para o layout não mudar: as fotos foram recortadas (sem distorcer) em torno do que importa na cena. O lado maior tem no máximo 1600 px (foto do alto da página) ou 1280 px (demais), em WebP com qualidade 80 (68 nas que passavam de 200 KB).
- As fotos do Instagram e do WhatsApp têm cerca de 1080 px de largura: na tela grande o alto da página (hero) fica um pouco menos nítido que uma foto original da câmera. Para a nitidez máxima, mande os arquivos originais (do celular ou da câmera).

## Onde cada foto entrou

| Pasta enviada | Página | Arquivos em `content/media/` (o que a foto ocupa) |
|---|---|---|
| Santa Ceia | Ceia do Senhor | `f_cx_147` (alto da página), `f_cx_307`, `f_cx_141`, `f_cx_146`, `f_cx_242` (carrossel) |
| Círculo de oração | Ministério Círculo de Oração | `f_mp_circulo` (alto), `f_mx_117`, `f_mx_132`, `f_mx_133`, `f_mx_241`, `f_min_circulo` (cartão em Ministérios) |
| ENSINAI | Ministério Ensinai | `f_mp_ensinai` (alto), `f_min_ensinai` (cartão) |
| EBD | Escola Bíblica Dominical | `f_mp_ebd` (alto), `f_mx_0`, `f_min_ebd` (cartão) |
| Família | Ministério Família | `f_mp_familia` (alto), `f_mx_41`, `f_mx_82`, `f_mx_90`, `f_mx_31`, `f_mx_52`, `p_familia` (cartão) |
| Missões e Evangelismo | Ministério Missão e Evangelismo | `f_mp_missao` (alto), `f_mx_213` |
| Juvenil (a cópia dentro de Missões e Evangelismo, com as 4 fotos HEIC já em JPG) | Ministério Mocidade | `f_mx_198`, `f_mx_226`, `f_mx_237`, `f_mx_248`, `f_mx_257`, `p_jovens` (cartão) |

Cada foto aparece só uma vez em cada página. O cartão de Ministérios e a Família reaproveitam fotos da própria pasta em outra página, o que é permitido (a regra é não repetir foto na mesma página).

## O que continua com a foto antiga (e por quê)

| Arquivo | Onde aparece | Motivo |
|---|---|---|
| `f_mp_criancas`, `f_min_criancas` | Crianças (alto e cartão) | só havia fotos de crianças |
| `f_mx_21` | Escola Bíblica Dominical | a pasta tinha só 2 fotos para 4 posições |
| `f_mx_228`, `p_missoes` | Missão e Evangelismo (galeria e cartão) | as fotos restantes mostravam crianças ou uma pessoa em situação de rua |
| `f_mp_mocidade` | Mocidade (alto) | as fotos restantes eram de oração com choro, ou mostravam uma criança junto do grupo |

Para trocar essas, mande mais fotos (sem crianças) dessas pastas.

## Fotos que ficaram de fora e por quê

- **Pessoa em situação de rua sendo abençoada** (pasta Missões e Evangelismo): não foi usada, porque a pessoa pode ser identificada e não há consentimento dela.
- **Pregador em close** (pasta Missões e Evangelismo, com bandeiras ao fundo): não entrou nas posições de destaque. Não foi possível conferir a regra "sem fotos com o pastor João", porque as fotos não trazem nomes.
- As demais fotos da pasta que não ocuparam posição ficam com o Felipe, para uma galeria futura. A página Galeria não foi alterada.

## Cuidados

- Os jovens da Mocidade incluem adolescentes. As fotos da Mocidade só devem ficar se as autorizações (guardadas no Drive) cobrirem os adolescentes.
- **Repositório público:** os arquivos de foto ficam no histórico do Git, que é público. Se uma pessoa pedir para remover a foto, apagar o arquivo não basta (o histórico guarda); peça ajuda para reescrever o histórico.
