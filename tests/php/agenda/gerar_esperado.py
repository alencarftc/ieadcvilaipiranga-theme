# Gera dados/esperado-2026-2027.json: a saída de eventos_do_dia() do gerador do protótipo
# (prototipo/fonte/gen.py) para todos os dias de 2026 e 2027. O teste da agenda compara o PHP com ela.
# Uso (na raiz do repositório): python3 -I tests/php/agenda/gerar_esperado.py prototipo/fonte/gen.py > tests/php/agenda/dados/esperado-2026-2027.json
# Precisa do Pillow (o gen.py importa PIL). Só roda a parte de definições do gen.py, sem gerar páginas.
import sys, json
from datetime import date, timedelta
src = open(sys.argv[1], encoding='utf-8').read().split('\n')
end = next(i for i, l in enumerate(src) if l.startswith('# Ministérios'))
ns = {'__file__': sys.argv[1]}
exec('\n'.join(src[:end]), ns)
out = {}
d = date(2026, 1, 1)
while d <= date(2027, 12, 31):
    evs = ns['eventos_do_dia'](d)
    if evs: out[d.isoformat()] = [[h, t, c, e] for h, t, c, e in evs]
    d += timedelta(1)
print(json.dumps(out, ensure_ascii=False, separators=(',', ':')))
