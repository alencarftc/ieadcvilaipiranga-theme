<?php
/**
 * Dado de teste: o evento avulso que aparece na agenda do protótipo de 06/10/2026 ("Confraternização dos jovens",
 * 17 e 18 de outubro). O site de verdade NÃO tem esse evento (a data passou e era exemplo);
 * os testes de fidelidade o criam antes de rodar e o apagam no fim (bin/testar.sh).
 *
 * Uso (dentro do contêiner wp-cli, com tests/ montado em /tests):
 *   wp eval-file /tests/fixtures/evento-prototipo.php criar|remover
 */
defined('ABSPATH') || exit;

$acao = $args[0] ?? 'criar';
$todos = get_posts(['post_type' => 'evento', 'post_status' => 'any', 'meta_key' => '_ieadc_fixture_teste', 'meta_value' => '1', 'posts_per_page' => -1, 'fields' => 'ids']);

if ($acao === 'remover') {
	foreach ($todos as $id) {
		wp_delete_post($id, true);
	}
	WP_CLI::log(count($todos) . ' evento(s) de teste removido(s).');
	return;
}

$dados = [
	'post_type'    => 'evento',
	'post_status'  => 'publish',
	'post_name'    => 'confraternizacao-dos-jovens',
	'post_title'   => 'Confraternização dos jovens',
	'post_excerpt' => 'Dias 17 e 18 de outubro.',
	'post_content' => '<!-- wp:paragraph --><p>Dias 17 e 18 de outubro.</p><!-- /wp:paragraph -->',
	'meta_input'   => [
		'_ieadc_fixture_teste' => '1',
		'evento_data'          => '2026-10-17',
		'evento_data_fim'      => '2026-10-18',
		'evento_horario'       => '',
		'evento_local'         => '',
		'evento_categoria'     => 'c-jovens',
		'evento_link'          => 'ministerios/mocidade',
	],
];
if ($todos) {
	$dados['ID'] = $todos[0];
}
$id = wp_insert_post(wp_slash($dados), true);
if (is_wp_error($id)) {
	WP_CLI::error('Não criou o evento de teste: ' . $id->get_error_message());
}
WP_CLI::log("Evento de teste pronto (ID $id).");
