<?php
/**
 * Bloco ieadc/artigo-topo (B4). HTML esperado: docs/specs/referencias/artigo-topo.html.
 * Lê o post atual: categoria, selo de exemplo (meta ieadc_exemplo), título, resumo (excerpt),
 * autor e data, imagem destacada. Nos artigos de exemplo, autor e data ficam "[a confirmar]".
 */
defined('ABSPATH') || exit;
$a = $attributes;
$post = get_post();
if (!$post) {
	return;
}
$exemplo = (bool) get_post_meta($post->ID, 'ieadc_exemplo', true);
// A categoria padrão do WordPress ("Sem categoria") não aparece.
$cats = array_values(array_filter(get_the_category($post->ID), fn($c) => (int) $c->term_id !== (int) get_option('default_category')));
$cat = $cats ? $cats[0]->name : '';
$capa = (string) get_the_post_thumbnail_url($post->ID, 'full');

echo ieadc_secao_abre($a, 'sec glow');
echo '<div class="wrap"><div class="artigo">';
if (trim((string) ($a['migalha'] ?? '')) !== '') {
	echo '<div class="migalha"><a href="' . esc_url(ieadc_url((string) ($a['migalhaLink'] ?? 'artigos'))) . '">' . esc_html($a['migalha']) . '</a>' . ($cat !== '' ? ' › ' . esc_html($cat) : '') . '</div>';
}
if ($cat !== '' || $exemplo) {
	echo '<div style="display:flex;gap:8px;flex-wrap:wrap">';
	if ($cat !== '') {
		echo '<span class="chip">' . (!empty($a['icone']) ? ieadc_icone($a['icone']) : '') . esc_html($cat) . '</span>';
	}
	if ($exemplo && trim((string) ($a['selo'] ?? '')) !== '') {
		echo '<span class="chip ex">' . esc_html($a['selo']) . '</span>';
	}
	echo '</div>';
}
echo '<h1 class="h1" style="margin-top:13px">' . ieadc_pend(get_the_title($post)) . '</h1>' . ieadc_swoosh();
if (has_excerpt($post)) {
	echo '<p class="lead" style="margin-top:21px">' . ieadc_pend(get_the_excerpt($post)) . '</p>';
}

// Autor e data. Exemplo: ainda sem autor nem data definidos.
if ($exemplo) {
	$avatar = '?';
	$linha = 'Autor <mark class="pend">[a confirmar]</mark> · <mark class="pend">[data da publicação]</mark>';
} else {
	$nome = trim((string) get_the_author_meta('display_name', (int) $post->post_author));
	$partes = array_values(array_filter(preg_split('/\s+/u', $nome), fn($w) => $w !== '' && !str_ends_with($w, '.')));
	$avatar = $partes ? mb_strtoupper(mb_substr($partes[0], 0, 1) . (count($partes) > 1 ? mb_substr(end($partes), 0, 1) : '')) : '?';
	$linha = ($nome !== '' ? esc_html($nome) : 'Autor <mark class="pend">[a confirmar]</mark>') . ' · ' . esc_html(get_the_date('', $post));
}
echo '<div class="autor"><span class="avatar">' . esc_html($avatar) . '</span><span>' . $linha . '</span></div>';
echo '</div>';
if ($capa !== '') {
	echo '<div class="capa" style="' . esc_attr('background-image:url(' . esc_url($capa) . ')') . '"></div>';
}
echo '</div>' . ieadc_secao_fecha();
