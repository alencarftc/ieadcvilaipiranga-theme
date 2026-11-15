<?php
/**
 * Bloco ieadc/semana (B2). HTML esperado: docs/specs/referencias/semana.html.
 * Os cartões são dados (o voluntário edita), não regra da agenda. data-dia (0 = domingo … 6 = sábado)
 * e data-hora (HH:MM) saem da sigla e do horário; o selo "Próximo" é do site.js (M0).
 */
defined('ABSPATH') || exit;
$a = $attributes;

$num_dia = static function (string $sigla): string {
	$mapa = ['DOM' => 0, 'SEG' => 1, 'TER' => 2, 'QUA' => 3, 'QUI' => 4, 'SEX' => 5, 'SÁB' => 6, 'SAB' => 6];
	$s = mb_strtoupper(trim($sigla));
	return isset($mapa[$s]) ? (string) $mapa[$s] : '';
};
// "20h" → "20:00", "19h30" → "19:30", "9h" → "09:00"; outro formato → vazio (sem data-hora).
$hora_hhmm = static function (string $hora): string {
	return preg_match('/^\s*(\d{1,2})\s*[h:]\s*(\d{2})?\s*$/i', $hora, $m)
		? sprintf('%02d:%02d', (int) $m[1], (int) ($m[2] ?? 0))
		: '';
};
$dados = static function (string $dia, string $hora) use ($hora_hhmm): string {
	$hhmm = $hora_hhmm($hora);
	return ($dia !== '' && $hhmm !== '') ? ' data-dia="' . esc_attr($dia) . '" data-hora="' . esc_attr($hhmm) . '"' : '';
};
$miolo = static function (array $x): string {
	$h = '<span class="m">' . ieadc_pend((string) ($x['titulo'] ?? ''));
	if (trim((string) ($x['sub'] ?? '')) !== '') {
		$h .= '<small>' . ieadc_pend((string) $x['sub']) . '</small>';
	}
	$h .= '</span>';
	if (trim((string) ($x['hora'] ?? '')) !== '') {
		$h .= '<span class="t">' . ieadc_pend((string) $x['hora']) . '</span>';
	}
	return $h;
};

echo ieadc_secao_abre($a, 'horarios');
echo '<div class="wrap"><div class="painel"><div>';
if (trim((string) ($a['eyebrow'] ?? '')) !== '') {
	echo '<span class="eyebrow">' . ieadc_kses_rico((string) $a['eyebrow']) . '</span>';
}
if (trim((string) ($a['titulo'] ?? '')) !== '') {
	echo '<h2 class="h2">' . ieadc_kses_rico((string) $a['titulo']) . '</h2>';
}
echo ieadc_swoosh();
if (trim((string) ($a['texto'] ?? '')) !== '') {
	echo '<p class="txt" style="margin-top:21px">' . ieadc_kses_rico((string) $a['texto']) . '</p>';
}
$botao = ieadc_btn((string) ($a['botaoTexto'] ?? ''), (string) ($a['botaoLink'] ?? ''));
if ($botao !== '') {
	echo '<div class="btns">' . $botao . '</div>';
}
echo '</div><div class="dias">';

foreach ((array) ($a['dias'] ?? []) as $d) {
	if (trim((string) ($d['titulo'] ?? '')) === '') {
		continue;
	}
	$link = trim((string) ($d['link'] ?? ''));
	$tag  = $link !== '' ? 'a' : 'div';
	echo '<' . $tag . ' class="dia"' . $dados($num_dia((string) ($d['sigla'] ?? '')), (string) ($d['hora'] ?? ''));
	echo $link !== '' ? ' href="' . esc_url(ieadc_url($link)) . '">' : '>';
	echo ieadc_icone((string) ($d['icone'] ?? ''));
	echo '<span class="d">' . esc_html((string) ($d['sigla'] ?? '')) . '</span>';
	echo $miolo($d) . '</' . $tag . '>';
}

$domingo = array_filter((array) ($a['domingo'] ?? []), fn($x) => trim((string) ($x['titulo'] ?? '')) !== '');
if ($domingo) {
	echo '<div class="dia domingo"><div class="lado-dom">' . ieadc_icone((string) ($a['domingoIcone'] ?? 'igreja')) . '<span class="d">DOM</span></div><div class="lista-dom">';
	foreach ($domingo as $x) {
		$link = trim((string) ($x['link'] ?? ''));
		$tag  = $link !== '' ? 'a' : 'div';
		echo '<' . $tag . ($link !== '' ? ' href="' . esc_url(ieadc_url($link)) . '"' : '') . $dados('0', (string) ($x['hora'] ?? '')) . '>';
		echo ieadc_icone((string) ($x['icone'] ?? '')) . $miolo($x) . '</' . $tag . '>';
	}
	echo '</div></div>';
}
echo '</div></div></div>' . ieadc_secao_fecha();
