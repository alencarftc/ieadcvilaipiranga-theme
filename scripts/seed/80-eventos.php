<?php
// M3: eventos avulsos. O site NÃO nasce com evento de exemplo: quem cadastra os eventos é a equipe de Comunicação
// (painel > Eventos), e a agenda (mu-plugin ieadc-core/agenda.php) mostra só os que ainda vão acontecer.
//
// Antes havia aqui a "Confraternização dos jovens" (17 e 18 de outubro de 2026, horário e local a confirmar), copiada do
// protótipo. A data já passou e o evento era dado de exemplo; ele agora existe só como dado de teste, criado e apagado por
// tests/fixtures/evento-prototipo.php (bin/testar.sh), porque a agenda do protótipo de 06/10/2026 o mostra.
//
// Este arquivo apaga restos desse dado de teste (se um teste foi interrompido) e deixa o espaço pronto para eventos
// fixos de verdade: acrescente itens em $eventos só com dados confirmados pela liderança (data, horário, local).

$eventos = [];

// Restos do dado de teste (meta _ieadc_fixture_teste): nunca devem ir para o site de verdade.
$restos = get_posts(['post_type' => 'evento', 'post_status' => 'any', 'meta_key' => '_ieadc_fixture_teste', 'meta_value' => '1', 'posts_per_page' => -1, 'fields' => 'ids']);
foreach ($restos as $id_resto) {
	wp_delete_post($id_resto, true);
	WP_CLI::log("Evento de teste removido (ID $id_resto).");
}

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
