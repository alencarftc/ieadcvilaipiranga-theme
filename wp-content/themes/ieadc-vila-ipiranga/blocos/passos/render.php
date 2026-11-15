<?php
/**
 * Bloco ieadc/passos (B4). HTML esperado: docs/specs/referencias/passos.html.
 * "Como participar": passos numerados (<ol class="passos">) ou, com um passo só, um cartão com botão.
 */
defined('ABSPATH') || exit;
$a = $attributes;
$passos = array_values(array_filter(array_map(fn($p) => trim((string) $p), (array) ($a['passos'] ?? [])), 'strlen'));

echo ieadc_secao_abre($a, 'sec');
echo '<div class="wrap">' . ieadc_cab($a);
if (($a['variante'] ?? 'passos') === 'passo-unico' && $passos) {
	echo '<div class="passo-unico">' . (!empty($a['icone']) ? ieadc_icone($a['icone']) : '') . '<p>' . ieadc_kses_rico($passos[0]) . '</p>';
	echo ieadc_btn((string) ($a['unicoBotaoTexto'] ?? ''), (string) ($a['unicoBotaoLink'] ?? ''));
	echo '</div>';
} elseif ($passos) {
	echo '<ol class="passos">';
	foreach ($passos as $n => $p) {
		echo '<li><span class="num">' . ($n + 1) . '</span><p>' . ieadc_kses_rico($p) . '</p></li>';
	}
	echo '</ol>';
}
echo '</div>' . ieadc_secao_fecha();
