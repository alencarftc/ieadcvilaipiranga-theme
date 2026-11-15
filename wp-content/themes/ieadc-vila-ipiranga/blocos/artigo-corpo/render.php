<?php
/**
 * Bloco ieadc/artigo-corpo (B4). HTML esperado: docs/specs/referencias/artigo-corpo.html.
 * Versículo de abertura + o texto do post (parágrafos e títulos do editor) dentro de
 * <article class="artigo corpo-art">. Quem escreve o artigo só usa parágrafos e títulos;
 * o visual vem do CSS do protótipo. Blocos ieadc/* do próprio post são ignorados aqui
 * (evita repetir o topo, o corpo e a lista de outros artigos).
 *
 * Funciona dos dois jeitos:
 * - no modelo do post (single), chamado com render_block(): o post só tem o texto;
 * - dentro do próprio post (topo + corpo + texto + outros artigos): depois de desenhar o corpo,
 *   os blocos que não são ieadc/* desse post deixam de sair soltos na página (senão o texto sairia duas vezes).
 */
defined('ABSPATH') || exit;
if (!empty($GLOBALS['ieadc_artigo_corpo_rodando'])) {
	return;
}
$a = $attributes;
$post = get_post();
if (!$post) {
	return;
}

$v = trim((string) ($a['versiculo'] ?? ''));
$ref = trim((string) ($a['referencia'] ?? ''));
if ($v === '') {
	$v = trim((string) get_post_meta($post->ID, 'ieadc_versiculo', true));
	$ref = trim((string) get_post_meta($post->ID, 'ieadc_referencia', true));
}

$GLOBALS['ieadc_artigo_corpo_rodando'] = true;
$corpo = '';
if (has_blocks($post->post_content)) {
	foreach (parse_blocks($post->post_content) as $b) {
		if (empty($b['blockName']) && trim((string) $b['innerHTML']) === '') {
			continue;
		}
		if (!empty($b['blockName']) && str_starts_with($b['blockName'], 'ieadc/')) {
			continue;
		}
		$corpo .= render_block($b);
	}
} else {
	$corpo = wpautop($post->post_content);
}
$GLOBALS['ieadc_artigo_corpo_rodando'] = false;

if (doing_filter('the_content') && has_block('ieadc/artigo-corpo', $post)) {
	$GLOBALS['ieadc_artigo_corpo_dono'] = $post->ID;
	if (!function_exists('ieadc_artigo_corpo_esconde')) {
		function ieadc_artigo_corpo_esconde($html, $bloco) {
			$dono = (int) ($GLOBALS['ieadc_artigo_corpo_dono'] ?? 0);
			if (!$dono || !empty($GLOBALS['ieadc_artigo_corpo_rodando']) || get_the_ID() !== $dono || !doing_filter('the_content')) {
				return $html;
			}
			return str_starts_with((string) ($bloco['blockName'] ?? ''), 'ieadc/') ? $html : '';
		}
		add_filter('render_block', 'ieadc_artigo_corpo_esconde', 10, 2);
		// do_blocks roda na prioridade 9 de the_content: na 10 o conteúdo já foi montado.
		add_filter('the_content', function ($c) {
			$GLOBALS['ieadc_artigo_corpo_dono'] = 0;
			return $c;
		}, 10);
	}
}
// Parágrafos e títulos do editor vêm com class="wp-block-paragraph"/"wp-block-heading"; o protótipo usa <p> e <h2> puros.
$corpo = (string) preg_replace_callback('/<(p|h[1-6])\s+class="([^"]*)"/', function ($m) {
	$classes = trim(preg_replace('/(^|\s)wp-block-(paragraph|heading)(?=\s|$)/', ' ', $m[2]));
	return '<' . $m[1] . ($classes !== '' ? ' class="' . $classes . '"' : '');
}, $corpo);
$corpo = trim($corpo);

if ($v === '' && $corpo === '') {
	return;
}
echo ieadc_secao_abre($a, 'sec');
echo '<div class="wrap"><article class="artigo corpo-art">';
if ($v !== '') {
	echo '<blockquote class="versiculo">' . ieadc_kses_rico($v) . ($ref !== '' ? '<cite>' . ieadc_pend($ref) . '</cite>' : '') . '</blockquote>';
}
echo $corpo;
echo '</article></div>' . ieadc_secao_fecha();
