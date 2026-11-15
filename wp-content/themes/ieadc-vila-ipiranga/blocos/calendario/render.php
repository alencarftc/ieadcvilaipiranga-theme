<?php
/**
 * Bloco ieadc/calendario (B2). HTML esperado: docs/specs/referencias/calendario.html.
 * $meses blocos <details class="mes"> a partir do mês de hoje (só ele aberto), com as células do
 * mes_html() do gerador. Ocorrências de ieadc_agenda_dia() e resumo de ieadc_agenda_resumo_mes() (M2).
 */
defined('ABSPATH') || exit;
$a = $attributes;
$qtd   = min(24, max(0, (int) ($a['meses'] ?? 3)));
$tem   = function_exists('ieadc_agenda_dia') && function_exists('ieadc_hoje');
$hoje  = $tem ? ieadc_hoje() : null;
$siglas = ['SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB', 'DOM'];
$nomes  = ['', 'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

$mes_html = static function (int $ano, int $mes, bool $aberto) use ($hoje, $siglas, $nomes): string {
	$ini   = new DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mes), $hoje->getTimezone());
	$dias  = (int) $ini->format('t');
	$antes = (int) $ini->format('N') - 1;                                   // células vazias antes do dia 1 (semana começa na segunda)
	$depois = 7 - (int) $ini->modify('last day of this month')->format('N'); // e depois do último dia
	$hoje_ymd = $hoje->format('Y-m-d');

	$cels = str_repeat('<div class="cel fora"></div>', $antes);
	for ($k = 0; $k < $dias; $k++) {
		$d   = $ini->modify("+{$k} days");
		$evs = ieadc_agenda_dia($d);
		$itens = '';
		foreach ($evs as $e) {
			$hora = trim((string) ($e['hora'] ?? ''));
			$itens .= '<div class="ev' . (!empty($e['especial']) ? ' esp' : '') . '"><i class="' . esc_attr((string) $e['categoria']) . '"></i><span>'
				. ($hora !== '' ? '<b>' . ieadc_pend($hora) . '</b> ' : '') . ieadc_pend((string) $e['titulo']) . '</span></div>';
		}
		$cls = $d->format('Y-m-d') === $hoje_ymd ? ' hoje' : ($evs ? '' : ' vazio');
		$sigla = $siglas[(int) $d->format('N') - 1];
		$cels .= '<div class="cel' . $cls . '"><span class="n" data-dia=" · ' . esc_attr(mb_substr($sigla, 0, 1) . mb_strtolower(mb_substr($sigla, 1))) . '">' . ($k + 1) . '</span>' . $itens . '</div>';
	}
	$cels .= str_repeat('<div class="cel fora"></div>', $depois);

	$resumo = function_exists('ieadc_agenda_resumo_mes') ? ieadc_agenda_resumo_mes($ano, $mes) : '';
	$h  = '<details class="mes"' . ($aberto ? ' open' : '') . '><summary><span class="tit">' . $nomes[$mes] . ' de ' . $ano . '</span>';
	$h .= $resumo !== '' ? '<small>' . ieadc_pend($resumo) . '</small>' : '';
	$h .= '<span class="abre">' . ieadc_icone('baixo') . '</span></summary>';
	$h .= '<div class="semana cab-sem"><div>' . implode('</div><div>', $siglas) . '</div></div><div class="semana">' . $cels . '</div></details>';
	return $h;
};

echo ieadc_secao_abre($a, 'sec gelo curva-top');
echo '<div class="wrap">' . ieadc_cab($a);

$legenda = array_filter((array) ($a['legenda'] ?? []), fn($l) => trim((string) ($l['texto'] ?? '')) !== '');
if ($legenda) {
	echo '<div class="legenda">';
	foreach ($legenda as $l) {
		echo '<span><i class="' . esc_attr((string) ($l['classe'] ?? '')) . '"></i>' . ieadc_pend((string) $l['texto']) . '</span>';
	}
	echo '</div>';
}

if ($tem) {
	$m = new DateTimeImmutable($hoje->format('Y-m-01'), $hoje->getTimezone());
	for ($i = 0; $i < $qtd; $i++, $m = $m->modify('+1 month')) {
		echo $mes_html((int) $m->format('Y'), (int) $m->format('n'), $i === 0);
	}
}

$cartao = static function (string $titulo, string $texto, string $estilo): string {
	if (trim($titulo) === '' && trim($texto) === '') {
		return '';
	}
	$h = '<div class="cartao" style="' . esc_attr($estilo) . '">';
	if (trim($titulo) !== '') {
		$h .= '<span class="eyebrow">' . ieadc_pend($titulo) . '</span>';
	}
	if (trim($texto) !== '') {
		$h .= '<p class="txt" style="margin-top:13px">' . ieadc_kses_rico($texto) . '</p>';
	}
	return $h . '</div>';
};
$notas = $cartao((string) ($a['notaTitulo'] ?? ''), (string) ($a['notaTexto'] ?? ''), 'padding:34px')
	. $cartao((string) ($a['vazioTitulo'] ?? ''), (string) ($a['vazioTexto'] ?? ''), 'padding:34px;border-style:dashed');
if ($notas !== '') {
	echo '<div class="grade g2" style="margin-top:55px">' . $notas . '</div>';
}
echo '</div>' . ieadc_secao_fecha();
