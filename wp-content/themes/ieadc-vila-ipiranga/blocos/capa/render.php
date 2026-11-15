<?php
/**
 * Bloco ieadc/capa (B4). HTML esperado: docs/specs/referencias/capa.html.
 * Foto larga (.capa) sozinha numa seção. Sem foto, não sai nada.
 */
defined('ABSPATH') || exit;
$a = $attributes;
$url = ieadc_img((int) ($a['foto'] ?? 0));
if ($url === '') {
	return;
}
$estilo = 'margin-top:0;background-image:url(' . esc_url($url) . ')';
if (trim((string) ($a['foco'] ?? '')) !== '') {
	$estilo .= ';background-position:' . trim($a['foco']);
}
echo ieadc_secao_abre($a, '');
echo '<div class="wrap"><div class="capa" style="' . esc_attr($estilo) . '"></div></div>';
echo ieadc_secao_fecha();
