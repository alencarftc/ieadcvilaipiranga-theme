<?php
/**
 * Bloco ieadc/fatos (B4). HTML esperado: docs/specs/referencias/fatos.html.
 * Faixa de fatos rápidos das páginas de culto e de ministério.
 */
defined('ABSPATH') || exit;
$a = $attributes;
$fatos = array_filter((array) ($a['fatos'] ?? []), fn($f) => trim((string) ($f['rotulo'] ?? '')) !== '' || trim((string) ($f['valor'] ?? '')) !== '');
if (!$fatos) {
	return;
}
echo ieadc_secao_abre($a, 'sec');
echo '<div class="wrap"><div class="fatos">';
foreach ($fatos as $f) {
	echo '<div class="fato">' . (!empty($f['icone']) ? ieadc_icone($f['icone']) : '');
	echo '<span class="r">' . ieadc_pend((string) ($f['rotulo'] ?? '')) . '</span>';
	echo '<b>' . ieadc_kses_rico((string) ($f['valor'] ?? '')) . '</b></div>';
}
echo '</div></div>' . ieadc_secao_fecha();
