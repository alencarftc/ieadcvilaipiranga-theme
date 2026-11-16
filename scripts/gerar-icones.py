#!/usr/bin/env python3
"""Gera os ícones do site (favicon, apple-touch-icon e os do webmanifest) a partir do emblema do logo.

Uso (na raiz do repositório): python scripts/gerar-icones.py
Lê  wp-content/themes/ieadc-vila-ipiranga/assets/img/logo_cor.webp (emblema redondo à esquerda, texto à direita)
Grava em  wp-content/themes/ieadc-vila-ipiranga/assets/img/icones/ : icone-32.png, icone-180.png (fundo branco, o iOS não aceita
transparência), icone-192.png e icone-512.png (transparentes). Precisa do Pillow (pip install pillow).
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
print('ok:', ', '.join(sorted(p.name for p in SAIDA.glob('*.png'))))
