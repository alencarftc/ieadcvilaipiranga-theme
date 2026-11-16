#!/usr/bin/env python3
"""Gera os ícones do site (favicon, apple-touch-icon e os do webmanifest) a partir do emblema do logo, e o logo reduzido do cabeçalho.

Uso (na raiz do repositório): python scripts/gerar-icones.py
Lê  wp-content/themes/ieadc-vila-ipiranga/assets/img/logo_cor.webp (emblema redondo à esquerda, texto à direita)
Grava em  wp-content/themes/ieadc-vila-ipiranga/assets/img/icones/ : icone-32.png, icone-180.png (fundo branco, o iOS não aceita
transparência), icone-192.png e icone-512.png (transparentes); e assets/img/logo_cor-300.webp (logo inteiro em 300 px de largura:
no cabeçalho e no rodapé ele aparece com 38 a 44 px de altura, e o original de 3015 px pesa 59 KB). Precisa do Pillow (pip install pillow).
"""
from pathlib import Path
from PIL import Image

RAIZ = Path(__file__).resolve().parent.parent
IMG = RAIZ / 'wp-content/themes/ieadc-vila-ipiranga/assets/img'
SAIDA = IMG / 'icones'

logo = Image.open(IMG / 'logo_cor.webp').convert('RGBA')
alfa = logo.getchannel('A')
# O emblema é o primeiro bloco opaco da esquerda; o texto começa depois de uma faixa transparente.
largura, altura = logo.size
colunas = [alfa.crop((x, 0, x + 1, altura)).getbbox() is not None for x in range(largura)]
fim = next(x for x in range(20, largura) if not colunas[x])
emblema = logo.crop((0, 0, fim, altura))
caixa = emblema.getchannel('A').getbbox()
emblema = emblema.crop(caixa)
lado = max(emblema.size)
quadro = Image.new('RGBA', (lado, lado), (0, 0, 0, 0))
quadro.paste(emblema, ((lado - emblema.width) // 2, (lado - emblema.height) // 2))


def icone(tamanho, margem, fundo=None):
    miolo = round(tamanho * (1 - 2 * margem))
    img = Image.new('RGBA', (tamanho, tamanho), fundo or (0, 0, 0, 0))
    peq = quadro.resize((miolo, miolo), Image.LANCZOS)
    img.alpha_composite(peq, ((tamanho - miolo) // 2, (tamanho - miolo) // 2))
    return img.convert('RGB') if fundo else img


SAIDA.mkdir(parents=True, exist_ok=True)
icone(32, 0.0).save(SAIDA / 'icone-32.png', optimize=True)
icone(180, 0.08, (255, 255, 255, 255)).save(SAIDA / 'icone-180.png', optimize=True)
icone(192, 0.04).save(SAIDA / 'icone-192.png', optimize=True)
icone(512, 0.04).save(SAIDA / 'icone-512.png', optimize=True)
# Logo reduzido (2x do tamanho exibido: 44 px de altura = 147 px de largura)
LOGO = Image.open(IMG / 'logo_cor.webp').convert('RGBA')
LOGO.resize((300, round(300 * LOGO.height / LOGO.width)), Image.LANCZOS).save(IMG / 'logo_cor-300.webp', quality=90, method=6)
print('ok:', ', '.join(sorted(p.name for p in SAIDA.glob('*.png'))))
