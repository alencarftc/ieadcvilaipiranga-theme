<?php
/**
 * Bloco ieadc/ministerios-trilho (B3). HTML esperado: docs/specs/referencias/ministerios-trilho.html.
 * Dados: páginas filhas de "ministerios" (imagem destacada, ieadc_icone, título, ieadc_resumo).
 */
defined('ABSPATH') || exit;
$a   = $attributes;
$pai = get_page_by_path('ministerios');
$ministerios = $pai ? get_pages([
	'parent'      => $pai->ID,
	'sort_column' => 'menu_order,ID',
	'post_status' => 'publish',
]) : [];

echo ieadc_secao_abre($a, 'sec');
echo '<div class="wrap">' . ieadc_cab($a);
if ($ministerios) {
	echo '<div class="trilho">';
	foreach ($ministerios as $m) {
		$icone  = (string) get_post_meta($m->ID, 'ieadc_icone', true);
		$resumo = (string) get_post_meta($m->ID, 'ieadc_resumo', true);
		echo '<a class="cartao min-cartao" href="' . esc_url(get_permalink($m)) . '">';
		echo '<div class="img" ' . ieadc_fundo((int) get_post_thumbnail_id($m)) . '>';
		if ($icone) {
			echo '<span class="icone">' . ieadc_icone($icone) . '</span>';
		}
		echo '</div><div class="corpo"><h3 class="h4">' . esc_html(get_the_title($m)) . '</h3>';
		if ($resumo !== '') {
			echo '<p class="small">' . ieadc_pend($resumo) . '</p>';
		}
		echo '</div></a>';
	}
	echo '</div>';
}
echo '</div>' . ieadc_secao_fecha();
