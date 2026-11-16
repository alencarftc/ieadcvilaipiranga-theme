<?php
/**
 * Registro automático dos blocos ieadc/* (spec M1).
 *
 * Cada pasta blocos/<nome>/ com block.json vira um bloco dinâmico (render.php).
 * blocos/_editor/ não tem block.json: é o editor genérico, não um bloco.
 * O editor de todos eles é o genérico de blocos/_editor/editor.js: os campos do painel
 * saem dos atributos do block.json e da chave "ieadc" de cada atributo.
 */
defined('ABSPATH') || exit;

/** Pastas dos blocos do tema (blocos/<nome>/block.json), em ordem alfabética. */
function ieadc_blocos_pastas(): array {
	$pastas = [];
	foreach (glob(get_stylesheet_directory() . '/blocos/*/block.json') ?: [] as $json) {
		$pastas[] = dirname($json);
	}
	sort($pastas);
	return $pastas;
}

add_action('init', function () {
	$base = get_stylesheet_directory_uri() . '/blocos/_editor/';
	$dir = get_stylesheet_directory() . '/blocos/_editor/';
	$versao = static fn(string $arq) => is_file($dir . $arq) ? (string) filemtime($dir . $arq) : IEADC_TEMA_VERSAO;

	wp_register_script(
		'ieadc-blocos-editor',
		$base . 'editor.js',
		['wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-i18n', 'wp-data', 'wp-url'],
		$versao('editor.js'),
		true
	);
	wp_register_style('ieadc-blocos-editor', $base . 'editor.css', ['wp-components'], $versao('editor.css'));

	foreach (ieadc_blocos_pastas() as $pasta) {
		$tipo = register_block_type($pasta, [
			'editor_script_handles' => ['ieadc-blocos-editor'],
			'editor_style_handles'  => ['ieadc-blocos-editor'],
		]);
		if (!$tipo && defined('WP_DEBUG') && WP_DEBUG) {
			trigger_error('IEADC: bloco não registrado em ' . esc_html($pasta), E_USER_WARNING);
		}
	}
});

/*
 * Dados para o editor genérico: os blocos ieadc/* com os atributos do block.json
 * (inclusive a chave "ieadc" de cada um) e os ícones de inc/icones.php para o campo "icone".
 */
add_action('enqueue_block_editor_assets', function () {
	$blocos = [];
	foreach (WP_Block_Type_Registry::get_instance()->get_all_registered() as $nome => $tipo) {
		if (str_starts_with($nome, 'ieadc/')) {
			$blocos[$nome] = ['title' => $tipo->title, 'icon' => $tipo->icon, 'attributes' => $tipo->attributes ?: new stdClass()];
		}
	}
	$icones = [];
	foreach (function_exists('ieadc_icones') ? array_keys(ieadc_icones()) : [] as $nome) {
		$icones[$nome] = ieadc_icone($nome);
	}
	wp_add_inline_script(
		'ieadc-blocos-editor',
		'window.ieadcBlocos = ' . wp_json_encode($blocos ?: new stdClass()) . ';window.ieadcIcones = ' . wp_json_encode($icones ?: new stdClass()) . ';',
		'before'
	);
	// O painel lateral fica fora do iframe do editor: o CSS dos campos vai para os dois lados.
	wp_enqueue_style('ieadc-blocos-editor');
});

/* ---------- Modelo do artigo novo ----------
   Posts > Adicionar post já abre com os blocos do artigo (como em content/blocos/artigo-*.json): topo, corpo, um
   parágrafo vazio para o texto e "Continue lendo". Sem isto, um post só com parágrafos sai no layout padrão do Blocksy.
   O modelo não trava nada: a equipe pode escrever mais parágrafos e títulos. Só vale para artigos novos. */
function ieadc_artigo_modelo(): array {
	return [
		['ieadc/artigo-topo', []],
		['ieadc/artigo-corpo', []],
		['core/paragraph', ['placeholder' => 'Escreva o artigo aqui. Para mais parágrafos ou títulos, continue abaixo deste.']],
		['ieadc/artigos-grade', [
			'secao'        => 'sec gelo curva-top',
			'eyebrow'      => 'Continue lendo',
			'titulo'       => 'Outras publicações',
			'texto'        => '',
			'botaoTexto'   => '',
			'quantidade'   => 2,
			'colunas'      => 'g2',
			'excluirAtual' => true,
		]],
	];
}
add_filter('register_post_type_args', function (array $args, string $tipo): array {
	if ($tipo === 'post') {
		$args['template'] = ieadc_artigo_modelo();
		$args['template_lock'] = false;
	}
	return $args;
}, 10, 2);
