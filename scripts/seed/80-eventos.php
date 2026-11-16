<?php
// M3: eventos avulsos. Quem cadastra os eventos depois do lançamento é a equipe de Comunicação (painel > Eventos), e a agenda
// (mu-plugin ieadc-core/agenda.php) mostra só os que ainda vão acontecer.
//
// Fase 5B (16/11/2026): os eventos abaixo são os que o Felipe informou (datas e descrições dele). Horário e local ficam vazios
// quando ele não os informou: a agenda esconde o campo vazio, e a pergunta está em docs/operacao/perguntas-ao-pastor-robson.md.
// A "Confraternização dos jovens" (17 e 18 de outubro, exemplo do protótipo) já passou: ela existe só como dado de teste, criado e
// apagado por tests/fixtures/evento-prototipo.php (bin/testar.sh), porque a agenda do protótipo de 06/10/2026 a mostra.
//
// Este arquivo apaga restos desse dado de teste (se um teste foi interrompido) e cria os eventos que ainda não existem
// (procura pelo endereço do evento; evento já editado no painel não é alterado, exceto com IEADC_ATUALIZAR=1).
// Acrescente itens em $eventos só com dados confirmados pela liderança.

$eventos = [
	[
		'slug' => 'confra-infantil', 'titulo' => 'Confra infantil', 'resumo' => 'Confraternização infantil da igreja.',
		'data' => '2026-11-28', 'data_fim' => '', 'horario' => '', 'local' => '', 'categoria' => 'c-culto', 'link' => '',
	],
	[
		'slug' => 'almoco-da-igreja', 'titulo' => 'Almoço da igreja', 'resumo' => 'Almoço de comunhão da igreja.',
		'data' => '2026-12-03', 'data_fim' => '', 'horario' => '', 'local' => '', 'categoria' => 'c-culto', 'link' => '',
	],
	[
		'slug' => 'ceunem-biblico', 'titulo' => 'Céunem Bíblico',
		'resumo' => 'Desafio em formato de prova sobre o livro de João, no estilo do ENEM, com 90 perguntas e alternativas de A a D.',
		'data' => '2026-12-13', 'data_fim' => '', 'horario' => '', 'local' => '', 'categoria' => 'c-ensino', 'link' => '',
	],
	[
		'slug' => 'cantata-de-natal', 'titulo' => 'Cantata de Natal', 'resumo' => 'Cantata de Natal da igreja.',
		'data' => '2026-12-13', 'data_fim' => '', 'horario' => 'À noite', 'local' => '', 'categoria' => 'c-culto', 'link' => '',
	],
];

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
