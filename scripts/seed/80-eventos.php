<?php
// M3: eventos avulsos. Quem cadastra os eventos depois do lançamento é a equipe de Comunicação (painel > Eventos), e a agenda
// (mu-plugin ieadc-core/agenda.php) mostra só os que ainda vão acontecer.
//
// Fase 5B (16/11/2026): os eventos abaixo são os que o Felipe informou (datas e descrições dele). Fase 5C (17/11/2026): o Felipe
// respondeu horário, local, quem pode participar e inscrição de cada um (o horário do Almoço da igreja ainda não foi definido: fica
// vazio e a agenda esconde o campo vazio). O local é a própria igreja (nome e endereço de Configurações). Os detalhes vão no texto
// da página do evento ('detalhes', um parágrafo por item); 'resumo' é o texto curto dos cartões e da descrição para o Google.
// A "Confraternização dos jovens" (17 e 18 de outubro, exemplo do protótipo) já passou: ela existe só como dado de teste, criado e
// apagado por tests/fixtures/evento-prototipo.php (bin/testar.sh), porque a agenda do protótipo de 06/10/2026 a mostra.
//
// Este arquivo apaga restos desse dado de teste (se um teste foi interrompido) e cria os eventos que ainda não existem
// (procura pelo endereço do evento; evento já editado no painel não é alterado, exceto com IEADC_ATUALIZAR=1).
// Acrescente itens em $eventos só com dados confirmados pela liderança. Outros eventos: não há padrão, a igreja os define ao longo do ano.

$nome_igreja = function_exists('ieadc_info') ? trim((string) ieadc_info('nome')) : '';
$endereco    = function_exists('ieadc_info') ? trim((string) ieadc_info('endereco')) : '';
$local       = ($nome_igreja !== '' ? $nome_igreja : 'IEADC Vila Ipiranga') . ($endereco !== '' ? ', ' . $endereco : '');

$eventos = [
	[
		'slug' => 'confra-infantil', 'titulo' => 'Confra infantil',
		'resumo' => 'Confraternização infantil da igreja, para crianças de 0 a 8 anos. Os adultos podem acompanhar e todos são bem-vindos.',
		'detalhes' => [
			'Confraternização infantil da IEADC Vila Ipiranga, para crianças de 0 a 8 anos. Os adultos podem acompanhar, e todos são bem-vindos.',
			'Quando: sábado, 28 de novembro de 2026, às 19h30.',
			'Onde: ' . $local . '.',
			'Não é preciso fazer inscrição nem levar nada.',
		],
		'data' => '2026-11-28', 'data_fim' => '', 'horario' => '19h30', 'local' => $local, 'categoria' => 'c-culto', 'link' => '',
	],
	[
		'slug' => 'almoco-da-igreja', 'titulo' => 'Almoço da igreja',
		'resumo' => 'Almoço da igreja, exclusivo para membros. A lista de participantes deve ser passada antes, e a oferta é voluntária.',
		'detalhes' => [
			'Almoço da IEADC Vila Ipiranga, exclusivo para os membros da igreja.',
			'Quando: quinta-feira, 3 de dezembro de 2026. O horário ainda não foi definido.',
			'Onde: ' . $local . '.',
			'A lista de participantes deve ser passada antes. A oferta é voluntária.',
			'O culto de quinta-feira, dirigido pelo Círculo de Oração, acontece normalmente nesse dia.',
		],
		'data' => '2026-12-03', 'data_fim' => '', 'horario' => '', 'local' => $local, 'categoria' => 'c-culto', 'link' => '',
	],
	[
		'slug' => 'ceunem-biblico', 'titulo' => 'Céunem Bíblico',
		'resumo' => 'Desafio em formato de prova sobre o livro de João, no estilo do ENEM, com 90 perguntas e alternativas de A a D. Gratuito, com inscrição.',
		'detalhes' => [
			'Desafio em formato de prova sobre o livro de João, no estilo do ENEM, com 90 perguntas e alternativas de A a D.',
			'Quando: domingo, 13 de dezembro de 2026, às 9h30, no mesmo horário da Escola Bíblica Dominical.',
			'Onde: ' . $local . '.',
			'Quem pode participar: jovens, adolescentes e adultos.',
			'É preciso se inscrever. A participação é gratuita.',
			'Não há orientação específica para o estudo do livro de João.',
		],
		'data' => '2026-12-13', 'data_fim' => '', 'horario' => '9h30', 'local' => $local, 'categoria' => 'c-ensino', 'link' => '',
	],
	[
		'slug' => 'cantata-de-natal', 'titulo' => 'Cantata de Natal',
		'resumo' => 'Cantata de Natal apresentada pelos departamentos da Vila Ipiranga, no mesmo horário do culto dominical das 19h.',
		'detalhes' => [
			'Cantata de Natal apresentada pelos departamentos da Vila Ipiranga.',
			'Quando: domingo, 13 de dezembro de 2026, às 19h, no mesmo horário do culto dominical.',
			'Onde: ' . $local . '.',
		],
		'data' => '2026-12-13', 'data_fim' => '', 'horario' => '19h', 'local' => $local, 'categoria' => 'c-culto', 'link' => '',
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
	// Texto da página do evento: um parágrafo por item de 'detalhes' (sem 'detalhes', só o resumo).
	$paragrafos = array_map(
		static fn($par) => '<!-- wp:paragraph --><p>' . esc_html($par) . '</p><!-- /wp:paragraph -->',
		$e['detalhes'] ?? [$e['resumo']]
	);
	$id = wp_insert_post(wp_slash([
		'ID'           => $ja[0] ?? 0,
		'post_type'    => 'evento',
		'post_status'  => 'publish',
		'post_name'    => $e['slug'],
		'post_title'   => $e['titulo'],
		'post_excerpt' => $e['resumo'],
		'post_content' => implode("\n\n", $paragrafos),
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
