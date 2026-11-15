<?php
/**
 * Bloco ieadc/mvv (B4). HTML esperado: docs/specs/referencias/mvv.html.
 * Três cartões: Missão, Visão e Valores (lista com ícones).
 */
defined('ABSPATH') || exit;
$a = $attributes;
$valores = array_filter((array) ($a['valores'] ?? []), fn($v) => trim((string) ($v['texto'] ?? '')) !== '');

echo ieadc_secao_abre($a, 'sec');
echo '<div class="wrap">' . ieadc_cab($a) . '<div class="mvv">';
if (trim((string) ($a['missao'] ?? '')) !== '') {
	echo '<div>' . ieadc_icone('alvo') . '<h3>Missão</h3><p>' . ieadc_kses_rico($a['missao']) . '</p></div>';
}
if (trim((string) ($a['visao'] ?? '')) !== '') {
	echo '<div>' . ieadc_icone('olho') . '<h3>Visão</h3><p>' . ieadc_kses_rico($a['visao']) . '</p></div>';
}
if ($valores) {
	echo '<div>' . ieadc_icone('maos') . '<h3>Valores</h3><ul class="valores">';
	foreach ($valores as $v) {
		echo '<li>' . (!empty($v['icone']) ? ieadc_icone($v['icone']) : '') . ieadc_pend($v['texto']) . '</li>';
	}
	echo '</ul></div>';
}
echo '</div></div>' . ieadc_secao_fecha();
