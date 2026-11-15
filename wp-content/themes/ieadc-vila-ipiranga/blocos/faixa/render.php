<?php
/**
 * Bloco ieadc/faixa (B2). HTML esperado: docs/specs/referencias/faixa.html.
 * Faixa azul com foto: cabeçalho claro (com botões opcionais), lista de temas e caixa de vidro.
 */
defined('ABSPATH') || exit;
$a = $attributes;

echo ieadc_secao_abre($a, '');
echo '<div class="wrap"><div class="faixa">';
$fundo = ieadc_fundo((int) ($a['foto'] ?? 0), (string) ($a['foco'] ?? ''));
if ($fundo !== '') {
	echo '<div class="foto" ' . $fundo . '></div>';
}
echo '<div class="dentro"><div>';
if (trim((string) ($a['eyebrow'] ?? '')) !== '') {
	echo '<span class="eyebrow claro">' . ieadc_kses_rico((string) $a['eyebrow']) . '</span>';
}
if (trim((string) ($a['titulo'] ?? '')) !== '') {
	echo '<h2 class="h2">' . ieadc_kses_rico((string) $a['titulo']) . '</h2>';
}
echo ieadc_swoosh();
$botoes = '';
foreach ((array) ($a['botoes'] ?? []) as $b) {
	$botoes .= ieadc_btn((string) ($b['texto'] ?? ''), (string) ($b['link'] ?? ''), 'branco');
}
if ($botoes !== '') {
	echo '<div class="btns">' . $botoes . '</div>';
}
echo '</div>';

$itens = array_filter((array) ($a['itens'] ?? []), fn($i) => trim((string) ($i['rotulo'] ?? '')) !== '' || trim((string) ($i['valor'] ?? '')) !== '');
if ($itens) {
	echo '<ul class="temas">';
	foreach ($itens as $i) {
		$sub = trim((string) ($i['sub'] ?? ''));
		echo '<li><b>' . ieadc_pend((string) ($i['rotulo'] ?? '')) . ($sub !== '' ? '<small>' . ieadc_pend($sub) . '</small>' : '') . '</b>';
		echo '<span>' . ieadc_pend((string) ($i['valor'] ?? '')) . '</span></li>';
	}
	echo '</ul>';
}

$dt = trim((string) ($a['destaqueTitulo'] ?? ''));
$dx = trim((string) ($a['destaqueTexto'] ?? ''));
if ($dt !== '' || $dx !== '') {
	echo '<div class="vidro">' . ieadc_icone((string) ($a['destaqueIcone'] ?? '')) . '<div>';
	if ($dt !== '') {
		echo '<b>' . ieadc_pend($dt) . '</b>';
	}
	if ($dx !== '') {
		echo '<span>' . ieadc_kses_rico($dx) . '</span>';
	}
	echo '</div></div>';
}
echo '</div></div></div>' . ieadc_secao_fecha();
