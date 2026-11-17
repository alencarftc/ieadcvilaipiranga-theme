<?php
// M3: departamentos da página Liderança, na ordem do protótipo.
// Fonte: prototipo/fonte/gen.py, lista DEP, com os líderes separados por pessoas() ("e equipe" vira complemento).
// Cria só o que falta (procura pelo título); departamento já existente não é alterado (exceto com IEADC_ATUALIZAR=1).
// Fase 5B: o departamento "Mídia/Comunicação" (que dizia só "a definir") saiu da lista; se ele ainda existe sem líder definido, o seed o apaga.

$departamentos = [
	['Assistência Social e Visita aos Lares', ['Josué Anselmo', 'Ester Anselmo', 'Jessé Otacílio Maciel'], ''],
	['Bazar', ['Adriana Paulista'], 'e equipe'],
	['Círculo de Oração', ['Abigail Tostes Monteiro', 'Luciana Santos de Jesus Stein', 'Érica Tostes (1ª regente do coral)', 'Raquel Oliveira (2ª regente do coral)'], ''],
	['Compras de Materiais', ['Matheus Bernadoque', 'Bruno Bernadoque'], ''],
	['Crianças', ['Míriam Lima', 'Myrela Lima'], 'e equipe'],
	['Escola Bíblica', ['Pb. Manoel Lima', 'Dc. Jorge Fernandez'], ''],
	['Eventos', ['Evelin Sena', 'Míriam Lima'], ''],
	['Família', ['Coop. Bruno Bernadoque', 'Tácila Bernadoque', 'Pr. Robson Stein', 'Luciana Stein'], ''],
	['Manutenção da Igreja', ['Jessé Maciel', 'José Luiz Oliveira'], ''],
	['Missão/Evangelismo', ['Coop. José Luiz Nascimento', 'Pr. Evandro Oliveira'], ''],
	['Mocidade', ['Anderson Sena', 'Evelin Sena', 'Leonardo Morais da Silva'], ''],
	['Música/Instrumental', ['Coop. Adriano Martins', 'Jessé O. Maciel'], 'e equipe'],
	['Patrimônio', ['Dc. Jorge Fernandez'], ''],
	['Recepção e Auxílio Logístico', ['Priscila Thuler'], ''],
	['Secretaria', ['Coop. Adriano Martins'], ''],
	['Som/Mídia', ['Leonardo Morais', 'Daniel Junior', 'Yohanan Maia', 'Michel Lima'], ''],
	['Tesouraria', ['Alex Sanderson Maia', 'Matheus Bernadoque'], ''],
];

$existentes = [];
foreach (get_posts(['post_type' => 'departamento', 'post_status' => 'any', 'posts_per_page' => -1]) as $d) {
	// Mídia/Comunicação sem líder real (só o "a definir" antigo) não existe mais.
	if ($d->post_title === 'Mídia/Comunicação' && trim((string) get_post_meta($d->ID, 'lideres', true)) === '[a definir]') {
		wp_delete_post($d->ID, true);
		WP_CLI::log('Departamento removido: Mídia/Comunicação.');
		continue;
	}
	$existentes[$d->post_title] = $d->ID;
}
$forcar = getenv('IEADC_ATUALIZAR') === '1';
$criados = 0;
foreach ($departamentos as $i => [$nome, $lideres, $complemento]) {
	$ordem = $i + 1;
	$id = $existentes[$nome] ?? 0;
	if ($id && !$forcar) {
		continue; // já existe: o painel manda
	}
	$id = wp_insert_post(wp_slash([
		'ID'          => $id,
		'post_type'   => 'departamento',
		'post_status' => 'publish',
		'post_title'  => $nome,
		'menu_order'  => $ordem,
	]), true);
	if (is_wp_error($id)) {
		WP_CLI::warning("{$nome}: " . $id->get_error_message());
		continue;
	}
	update_post_meta($id, 'lideres', implode("\n", $lideres));
	update_post_meta($id, 'complemento', $complemento);
	update_post_meta($id, 'ordem', $ordem);
	if (!metadata_exists('post', $id, 'fotos_lideres')) {
		update_post_meta($id, 'fotos_lideres', []);
	}
	$criados++;
}
WP_CLI::log("Departamentos: {$criados} gravado(s), " . count($departamentos) . ' no protótipo.');
