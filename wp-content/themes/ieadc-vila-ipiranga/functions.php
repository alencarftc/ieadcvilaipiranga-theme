<?php
/**
 * Tema filho IEADC Vila Ipiranga (pai: Blocksy).
 *
 * O cabeçalho e o rodapé são os do protótipo v3 (header.php e footer.php deste tema);
 * o resto (páginas, artigos, arquivos) usa o Blocksy com os tokens do protótipo.
 */
defined('ABSPATH') || exit;

define('IEADC_TEMA_VERSAO', wp_get_theme()->get('Version'));

require __DIR__ . '/inc/icones.php';
require __DIR__ . '/inc/menu.php';
// M1: helpers antes de blocos (carregados só quando existirem).
foreach (['helpers', 'blocos'] as $ieadc_inc) { if (is_file(__DIR__ . "/inc/{$ieadc_inc}.php")) require_once __DIR__ . "/inc/{$ieadc_inc}.php"; }

add_action('after_setup_theme', function () {
	register_nav_menus([
		'primary' => 'Menu principal (cabeçalho e mapa do site)',
	]);
	add_theme_support('editor-styles');
	add_editor_style(['assets/css/fontes.css', 'assets/css/prototipo.css', 'assets/css/ajustes.css']);
});

add_action('wp_enqueue_scripts', function () {
	$base = get_stylesheet_directory_uri() . '/assets';
	wp_enqueue_style('ieadc-fontes', $base . '/css/fontes.css', [], IEADC_TEMA_VERSAO);
	wp_enqueue_style('ieadc-prototipo', $base . '/css/prototipo.css', ['ieadc-fontes'], IEADC_TEMA_VERSAO);
	wp_enqueue_style('ieadc-ajustes', $base . '/css/ajustes.css', ['ieadc-prototipo'], IEADC_TEMA_VERSAO);
	wp_enqueue_script('ieadc-site', $base . '/js/site.js', [], IEADC_TEMA_VERSAO, ['in_footer' => true, 'strategy' => 'defer']);
}, 20);

/* Pré-carrega as duas fontes mais usadas para o texto não "pular". */
add_action('wp_head', function () {
	$f = get_stylesheet_directory_uri() . '/assets/fonts/';
	foreach (['Roboto-normal-latin.woff2', 'RobotoCondensed-normal-latin.woff2'] as $arq) {
		echo '<link rel="preload" href="' . esc_url($f . $arq) . '" as="font" type="font/woff2" crossorigin>' . "\n";
	}
}, 1);

/* Categoria dos blocos próprios no editor. */
add_filter('block_categories_all', function (array $cats): array {
	array_unshift($cats, ['slug' => 'ieadc', 'title' => 'IEADC Vila Ipiranga', 'icon' => null]);
	return $cats;
});

/* O Blocksy não precisa montar o cabeçalho e o rodapé dele: usamos os nossos. */
add_filter('blocksy:header:enabled', '__return_false');
add_filter('blocksy:footer:enabled', '__return_false');

/** Link de uma página pelo slug, com fallback para /slug/. */
function ieadc_link(string $slug): string {
	$p = get_page_by_path($slug);
	return $p ? get_permalink($p) : home_url('/' . $slug . '/');
}

/** Dado da igreja (vem do mu-plugin ieadc-core; aqui só um fallback seguro). */
function ieadc_dado(string $chave): string {
	return function_exists('ieadc_info') ? (string) ieadc_info($chave) : '';
}
add_filter('run_wptexturize', '__return_false'); // aspas e travessões iguais ao protótipo (sem texturização)
