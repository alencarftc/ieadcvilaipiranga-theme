<?php
/**
 * Bloco ieadc/fotos (B3). HTML esperado: docs/specs/referencias/fotos.html.
 * As fotos são escolhidas no painel (o Instagram fica para depois do lançamento).
 */
defined('ABSPATH') || exit;
$a = $attributes;
$fotos = array_filter(array_map('intval', (array) ($a['fotos'] ?? [])));

echo ieadc_secao_abre($a, 'sec');
echo '<div class="wrap">' . ieadc_cab($a);
if ($fotos) {
	echo '<div class="fotos">';
	foreach ($fotos as $id) {
		echo '<div ' . ieadc_fundo($id) . '></div>';
	}
	echo '</div>';
}
if (!empty($a['nota'])) {
	echo '<p class="note" style="margin-top:21px">' . ieadc_pend($a['nota']) . '</p>';
}
echo '</div>' . ieadc_secao_fecha();
