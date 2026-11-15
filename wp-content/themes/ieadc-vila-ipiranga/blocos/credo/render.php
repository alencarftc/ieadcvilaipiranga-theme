<?php
/**
 * Bloco ieadc/credo (B4). HTML esperado: docs/specs/referencias/credo.html.
 * Declaração de fé: título fixo (sticky) à esquerda e lista numerada à direita.
 */
defined('ABSPATH') || exit;
$a = $attributes;
$itens = array_values(array_filter(array_map(fn($i) => trim((string) $i), (array) ($a['itens'] ?? [])), 'strlen'));

echo ieadc_secao_abre($a, 'sec gelo');
echo '<div class="wrap" style="display:grid;grid-template-columns:minmax(0,360px) minmax(0,1fr);gap:55px;align-items:start" data-cols>';
echo '<div style="position:sticky;top:120px">';
if (!empty($a['eyebrow'])) {
	echo '<span class="eyebrow">' . ieadc_pend($a['eyebrow']) . '</span>';
}
if (!empty($a['titulo'])) {
	echo '<h2 class="h2" style="margin-top:8px">' . ieadc_kses_rico($a['titulo']) . '</h2>';
}
echo ieadc_swoosh();
if (trim((string) ($a['texto'] ?? '')) !== '') {
	echo '<p class="txt" style="margin-top:21px">' . ieadc_kses_rico($a['texto']) . '</p>';
}
echo '</div>';
if ($itens) {
	echo '<ol class="credo">';
	foreach ($itens as $i) {
		echo '<li><span>' . ieadc_kses_rico($i) . '</span></li>';
	}
	echo '</ol>';
}
echo '</div>' . ieadc_secao_fecha();
