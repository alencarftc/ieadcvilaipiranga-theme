<?php
/**
 * Bloco ieadc/linha-tempo (B4). HTML esperado: docs/specs/referencias/linha-tempo.html.
 */
defined('ABSPATH') || exit;
$a = $attributes;
$marcos = array_filter((array) ($a['marcos'] ?? []), fn($m) => trim((string) ($m['quando'] ?? '')) !== '' || trim((string) ($m['titulo'] ?? '')) !== '');

echo ieadc_secao_abre($a, 'sec');
echo '<div class="wrap">' . ieadc_cab($a);
if ($marcos) {
	echo '<div class="linha-tempo">';
	foreach ($marcos as $m) {
		echo '<div class="marco"><b>' . ieadc_pend((string) ($m['quando'] ?? '')) . '</b><span>' . ieadc_pend((string) ($m['titulo'] ?? '')) . '</span>';
		if (trim((string) ($m['texto'] ?? '')) !== '') {
			echo '<p>' . ieadc_kses_rico($m['texto']) . '</p>';
		}
		echo '</div>';
	}
	echo '</div>';
}
echo '</div>' . ieadc_secao_fecha();
