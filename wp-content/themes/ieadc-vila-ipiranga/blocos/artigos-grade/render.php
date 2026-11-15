<?php
/**
 * Bloco ieadc/artigos-grade (B3). HTML esperado: docs/specs/referencias/artigos-grade.html.
 * Dados: posts publicados, do mais novo para o mais antigo (imagem destacada, categoria, ieadc_exemplo, título, resumo).
 */
defined('ABSPATH') || exit;
$a = $attributes;

$args = [
	'post_type'           => 'post',
	'post_status'         => 'publish',
	'posts_per_page'      => max(1, (int) ($a['quantidade'] ?? 3)),
	'orderby'             => ['date' => 'DESC', 'ID' => 'DESC'],
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
];
$atual = get_the_ID();
if (!empty($a['excluirAtual']) && $atual && get_post_type($atual) === 'post') {
	$args['post__not_in'] = [$atual];
}
$artigos = get_posts($args);

/* Ícone do chip por categoria; Notícia usa o megafone. */
$icones = ['mensagem' => 'texto', 'estudo' => 'texto', 'reflexao' => 'texto', 'noticia' => 'megafone'];
$colunas = in_array($a['colunas'] ?? '', ['g2', 'g3'], true) ? $a['colunas'] : 'g3';
$mais    = (string) ($a['maisTexto'] ?? '');

echo ieadc_secao_abre($a, 'sec gelo');
echo '<div class="wrap">' . ieadc_cab($a);
if ($artigos) {
	echo '<div class="grade ' . esc_attr($colunas) . '">';
	foreach ($artigos as $p) {
		$cats = array_values(array_filter(get_the_category($p->ID), fn($t) => (int) $t->term_id !== (int) get_option('default_category')));
		$cat  = $cats[0] ?? null;
		$resumo = has_excerpt($p) ? $p->post_excerpt : wp_trim_words(wp_strip_all_tags(strip_shortcodes($p->post_content)), 24);
		echo '<a class="cartao" href="' . esc_url(get_permalink($p)) . '">';
		echo '<div class="img" ' . ieadc_fundo((int) get_post_thumbnail_id($p)) . '></div>';
		echo '<div class="corpo">';
		$exemplo = (bool) get_post_meta($p->ID, 'ieadc_exemplo', true);
		if ($cat || $exemplo) {
			echo '<div style="display:flex;gap:8px;flex-wrap:wrap">';
			if ($cat) {
				echo '<span class="chip">' . ieadc_icone($icones[$cat->slug] ?? 'texto') . esc_html($cat->name) . '</span>';
			}
			if ($exemplo) {
				echo '<span class="chip ex">Exemplo</span>';
			}
			echo '</div>';
		}
		echo '<h3 class="h3" style="font-size:24px">' . esc_html(get_the_title($p)) . '</h3>';
		if (trim($resumo) !== '') {
			echo '<p class="txt">' . esc_html($resumo) . '</p>';
		}
		if ($mais !== '') {
			echo '<span class="mais acao">' . esc_html($mais) . ieadc_icone('seta') . '</span>';
		}
		echo '</div></a>';
	}
	echo '</div>';
}
echo '</div>' . ieadc_secao_fecha();
