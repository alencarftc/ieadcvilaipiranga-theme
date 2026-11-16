<?php
// M3: categorias dos artigos, selo "Exemplo", foto de capa e resumo dos 3 artigos do protótipo.
// Fonte: prototipo/fonte/gen.py, lista ART. Os posts são criados por 15-paginas.php.
// Funções ieadc_seed_*: 40-ministerios.php.

$categorias = ['mensagem' => 'Mensagem', 'estudo' => 'Estudo', 'reflexao' => 'Reflexão', 'noticia' => 'Notícia'];
// Descrição própria de cada categoria (fase 4): curta, sem fato novo. Vira a meta description e a prévia ao compartilhar o arquivo da categoria.
// Só preenche se a descrição estiver vazia: o que a equipe escrever no painel (Artigos > Categorias) não é sobrescrito.
$descricoes = [
	'mensagem' => 'Mensagens em texto da IEADC Vila Ipiranga: palavras de ensino e de ânimo para a vida com Deus, para ler em casa ou no celular.',
	'estudo'   => 'Estudos bíblicos da IEADC Vila Ipiranga para ler com calma e aplicar no dia a dia, com os textos da Palavra à vista.',
	'reflexao' => 'Reflexões curtas da IEADC Vila Ipiranga sobre a fé, a oração e a vida em comunidade, para meditar com a Bíblia.',
	'noticia'  => 'Notícias e avisos da IEADC Vila Ipiranga, igreja no Capão Raso, em Curitiba: o que acontece na vida da congregação.',
];
foreach ($categorias as $slug => $nome) {
	$t = get_term_by('slug', $slug, 'category');
	if (!$t) {
		$t = wp_insert_term($nome, 'category', ['slug' => $slug, 'description' => $descricoes[$slug]]);
		is_wp_error($t) ? WP_CLI::warning("Categoria {$nome}: " . $t->get_error_message()) : WP_CLI::log("Categoria criada: {$nome}");
	} elseif (trim((string) $t->description) === '') {
		wp_update_term($t->term_id, 'category', ['description' => $descricoes[$slug]]);
		WP_CLI::log("Descrição da categoria {$nome} gravada.");
	}
}

$artigos = [
	'alcancar-curitiba'     => ['mensagem', 'f_art_tema.webp', 'O tema do ano é também um chamado: a missão começa na porta de casa.'],
	'orai-uns-pelos-outros' => ['reflexao', 'f_art_oracao.webp', 'Na igreja, ninguém precisa carregar o peso sozinho.'],
	'ate-que-ele-venha'     => ['estudo', 'f_biblia.webp', 'O que lembramos, celebramos e esperamos na Ceia do Senhor.'],
];

// Datas de publicação do protótipo (anteriores a IEADC_HOJE), para o bloco artigos-grade
// listar do mais novo ao mais antigo na mesma ordem do protótipo.
$datas = [
	'alcancar-curitiba'     => '2026-10-05 10:00:00',
	'orai-uns-pelos-outros' => '2026-10-04 10:00:00',
	'ate-que-ele-venha'     => '2026-10-03 10:00:00',
];

$forcar = getenv('IEADC_ATUALIZAR') === '1';
$padrao = (int) get_option('default_category');
foreach ($artigos as $slug => [$cat, $foto, $resumo]) {
	$p = get_page_by_path($slug, OBJECT, 'post');
	if (!$p) {
		WP_CLI::warning("Artigo não encontrado: {$slug}");
		continue;
	}
	// Categoria: só troca se o artigo ainda está na categoria padrão (Sem categoria).
	$atuais = wp_get_post_categories($p->ID);
	if ($forcar || !$atuais || $atuais === [$padrao]) {
		$t = get_term_by('slug', $cat, 'category');
		$t && wp_set_post_categories($p->ID, [$t->term_id]);
	}
	ieadc_seed_meta($p->ID, 'ieadc_exemplo', true);
	ieadc_seed_capa($p->ID, $foto);
	if ($forcar || trim($p->post_excerpt) === '') {
		wp_update_post(['ID' => $p->ID, 'post_excerpt' => wp_slash($resumo)]);
	}
	// Data: grava uma vez (marcador em meta) para não sobrescrever edição feita no painel.
	if ($forcar || !get_post_meta($p->ID, 'ieadc_data_seed', true)) {
		$local = $datas[$slug];
		wp_update_post(['ID' => $p->ID, 'post_date' => $local, 'post_date_gmt' => get_gmt_from_date($local), 'edit_date' => true]);
		update_post_meta($p->ID, 'ieadc_data_seed', 1);
	}
	WP_CLI::log("Artigo: {$slug}");
}
