<?php
/**
 * Bloco ieadc/galeria-faixa (B3). HTML esperado: docs/specs/referencias/galeria-faixa.html.
 */
defined('ABSPATH') || exit;
$a = $attributes;
$botoes = array_filter((array) ($a['botoes'] ?? []), fn($b) => !empty($b['texto']));
$fotos  = array_filter(array_map('intval', (array) ($a['fotos'] ?? [])));

echo ieadc_secao_abre($a, '');
echo '<div class="wrap"><div class="galeria-faixa"><div class="lado">';
if (!empty($a['eyebrow'])) {
	echo '<span class="eyebrow claro">' . ieadc_pend($a['eyebrow']) . '</span>';
}
echo '<h2 class="h2">' . ieadc_kses_rico((string) ($a['titulo'] ?? '')) . '</h2>' . ieadc_swoosh();
if (!empty($a['texto'])) {
	echo '<p class="txt">' . ieadc_pend($a['texto']) . '</p>';
}
if ($botoes) {
	echo '<div class="btns">';
	foreach ($botoes as $b) {
		echo ieadc_btn($b['texto'], (string) ($b['link'] ?? ''), (string) ($b['estilo'] ?? ''));
	}
	echo '</div>';
}
echo '</div>';
if ($fotos) {
	echo '<div class="mosaico">';
	foreach ($fotos as $id) {
		echo '<div ' . ieadc_fundo($id) . '></div>';
	}
	echo '</div>';
}
echo '</div></div>' . ieadc_secao_fecha();
