<?php
/**
 * Bloco ieadc/cultos-grade (B2). HTML esperado: docs/specs/referencias/cultos-grade.html.
 * Dados: as páginas de culto fixo (ieadc_paginas_culto(), M3), na ordem da lista, com os campos
 * do "Cartão do culto" (ieadc_cor, ieadc_icone, ieadc_quando, ieadc_resumo).
 */
defined('ABSPATH') || exit;
$a = $attributes;
$slugs = function_exists('ieadc_paginas_culto') ? ieadc_paginas_culto() : [];
$cultos = [];
foreach ($slugs as $slug) {
	$p = get_page_by_path($slug);
	if ($p && $p->post_status === 'publish') {
		$cultos[] = $p;
	}
}
$mais = (string) ($a['maisTexto'] ?? '');

echo ieadc_secao_abre($a, 'sec');
echo '<div class="wrap">' . ieadc_cab($a);
if ($cultos) {
	echo '<div class="min-grade">';
	foreach ($cultos as $c) {
		$cor    = (string) get_post_meta($c->ID, 'ieadc_cor', true);
		$icone  = (string) get_post_meta($c->ID, 'ieadc_icone', true);
		$quando = (string) get_post_meta($c->ID, 'ieadc_quando', true);
		$resumo = (string) get_post_meta($c->ID, 'ieadc_resumo', true);
		echo '<a class="min-tile' . ($cor ? ' ' . esc_attr($cor) : '') . '" href="' . esc_url(get_permalink($c)) . '">';
		if ($icone) {
			echo '<span class="ic">' . ieadc_icone($icone) . '</span>';
		}
		if ($quando !== '') {
			echo '<span class="chip">' . ieadc_icone('relogio') . ieadc_pend($quando) . '</span>';
		}
		echo '<h3>' . esc_html(get_the_title($c)) . '</h3>';
		if ($resumo !== '') {
			echo '<p>' . ieadc_pend($resumo) . '</p>';
		}
		if ($mais !== '') {
			echo '<span class="mais">' . esc_html($mais) . ieadc_icone('seta') . '</span>';
		}
		echo '</a>';
	}
	echo '</div>';
}
echo '</div>' . ieadc_secao_fecha();
