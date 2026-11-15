<?php
/**
 * Bloco ieadc/ministerios-grade (B3). HTML esperado: docs/specs/referencias/ministerios-grade.html.
 * Dados: páginas filhas de "ministerios" (ieadc_cor, ieadc_icone, ieadc_quando, título, ieadc_resumo).
 */
defined('ABSPATH') || exit;
$a   = $attributes;
$pai = get_page_by_path('ministerios');
$ministerios = $pai ? get_pages([
	'parent'      => $pai->ID,
	'sort_column' => 'menu_order,ID',
	'post_status' => 'publish',
]) : [];
$mais = (string) ($a['maisTexto'] ?? '');

echo ieadc_secao_abre($a, 'sec');
echo '<div class="wrap">' . ieadc_cab($a);
if ($ministerios) {
	echo '<div class="min-grade">';
	foreach ($ministerios as $m) {
		$cor    = (string) get_post_meta($m->ID, 'ieadc_cor', true);
		$icone  = (string) get_post_meta($m->ID, 'ieadc_icone', true);
		$quando = (string) get_post_meta($m->ID, 'ieadc_quando', true);
		$resumo = (string) get_post_meta($m->ID, 'ieadc_resumo', true);
		echo '<a class="min-tile' . ($cor ? ' ' . esc_attr($cor) : '') . '" href="' . esc_url(get_permalink($m)) . '">';
		if ($icone) {
			echo '<span class="ic">' . ieadc_icone($icone) . '</span>';
		}
		if ($quando !== '') {
			echo '<span class="chip">' . ieadc_icone('relogio') . ieadc_pend($quando) . '</span>';
		}
		echo '<h3>' . esc_html(get_the_title($m)) . '</h3>';
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
