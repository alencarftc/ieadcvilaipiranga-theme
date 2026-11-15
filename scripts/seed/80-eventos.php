<?php
// M3: eventos avulsos do protótipo (gen.py, lista EXTRAS). Usados pela agenda (M2).
// Cria só se ainda não existe um evento com o mesmo slug (em qualquer status, inclusive na lixeira).

$eventos = [
	[
		'slug'      => 'confraternizacao-dos-jovens',
		'titulo'    => 'Confraternização dos jovens',
		'resumo'    => 'Dias 17 e 18 de outubro. [Local e programação a confirmar]',
		'data'      => '2026-10-17',
		'data_fim'  => '2026-10-18',
		'horario'   => '[horário a confirmar]',
		'local'     => '',
		'categoria' => 'c-jovens',
		'link'      => 'ministerios/mocidade',
	],
];

foreach ($eventos as $e) {
	$ja = get_posts(['post_type' => 'evento', 'name' => $e['slug'], 'post_status' => array_keys(get_post_stati()), 'posts_per_page' => 1, 'fields' => 'ids']);
	if ($ja && getenv('IEADC_ATUALIZAR') !== '1') {
		continue;
	}
	$id = wp_insert_post(wp_slash([
		'ID'           => $ja[0] ?? 0,
		'post_type'    => 'evento',
		'post_status'  => 'publish',
		'post_name'    => $e['slug'],
		'post_title'   => $e['titulo'],
		'post_excerpt' => $e['resumo'],
		'post_content' => '<!-- wp:paragraph --><p>' . esc_html($e['resumo']) . '</p><!-- /wp:paragraph -->',
		'meta_input'   => [
			'evento_data'      => $e['data'],
			'evento_data_fim'  => $e['data_fim'],
			'evento_horario'   => $e['horario'],
			'evento_local'     => $e['local'],
			'evento_categoria' => $e['categoria'],
			'evento_link'      => $e['link'],
		],
	]), true);
	is_wp_error($id) ? WP_CLI::warning($e['titulo'] . ': ' . $id->get_error_message()) : WP_CLI::log('Evento: ' . $e['titulo']);
}
