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
foreach (['helpers', 'blocos', 'seo', 'identidade', 'desempenho', 'medicao', 'marketing'] as $ieadc_inc) { if (is_file(__DIR__ . "/inc/{$ieadc_inc}.php")) require_once __DIR__ . "/inc/{$ieadc_inc}.php"; }

add_action('after_setup_theme', function () {
	register_nav_menus([
		'primary' => 'Menu principal (cabeçalho e mapa do site)',
	]);
	add_theme_support('editor-styles');
	add_editor_style(['assets/css/fontes.css', 'assets/css/prototipo.css', 'assets/css/ajustes.css']);
});

add_action('wp_enqueue_scripts', function () {
	$base = get_stylesheet_directory_uri() . '/assets';
	$dir  = get_stylesheet_directory() . '/assets';
	$ver  = fn(string $arq): string => IEADC_TEMA_VERSAO . '.' . (is_file("$dir/$arq") ? filemtime("$dir/$arq") : 0); // muda quando o arquivo muda
	// Fontes + protótipo + ajustes: um arquivo minificado (inc/desempenho.php); se não der para gravá-lo, os três originais.
	$pacote = function_exists('ieadc_css_pacote') ? ieadc_css_pacote() : '';
	if ($pacote !== '') {
		wp_enqueue_style('ieadc-tema', $pacote, [], null);
	} else {
		wp_enqueue_style('ieadc-fontes', $base . '/css/fontes.css', [], $ver('css/fontes.css'));
		wp_enqueue_style('ieadc-prototipo', $base . '/css/prototipo.css', ['ieadc-fontes'], $ver('css/prototipo.css'));
		wp_enqueue_style('ieadc-ajustes', $base . '/css/ajustes.css', ['ieadc-prototipo'], $ver('css/ajustes.css'));
	}
	wp_enqueue_script('ieadc-site', $base . '/js/site.js', [], $ver('js/site.js'), ['in_footer' => true, 'strategy' => 'defer']);
}, 20);

/* Pré-carrega as duas fontes mais usadas, com prioridade BAIXA (fase 4). Com prioridade alta, os 93 KB disputavam a banda com o CSS (que bloqueia a renderização) no
   celular com rede lenta; como as fontes de reserva têm as medidas das reais (fontes.css, "... Fallback"), a troca de fonte não desloca nada e elas podem chegar depois.
   Medido (Lighthouse, 4G lenta): sem preload o desempenho local cai de 98 para 96 (as fontes passam a ser pedidas só depois do CSS); com prioridade baixa fica em 98. */
add_action('wp_head', function () {
	$f = get_stylesheet_directory_uri() . '/assets/fonts/';
	foreach (['Roboto-normal-latin.woff2', 'RobotoCondensed-normal-latin.woff2'] as $arq) {
		echo '<link rel="preload" href="' . esc_url($f . $arq) . '" as="font" type="font/woff2" crossorigin fetchpriority="low">' . "\n";
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
