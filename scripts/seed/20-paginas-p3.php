<?php
// P3: cultos, Eventos, Artigos, Galeria, Aceitar Jesus, Política de privacidade e os 3 artigos de exemplo,
// montados com blocos ieadc/* (conteúdo em content/blocos/<pagina>.json).
// Troca o HTML de transição do protótipo pelos blocos; conteúdo já editado no painel fica como está
// (exceto com IEADC_ATUALIZAR=1).

$paginas = [];
foreach (array_keys(ieadc_seed_cultos()) as $slug) {
	$paginas[$slug] = $slug;
}
foreach (['eventos', 'artigos', 'galeria', 'aceitar-jesus', 'politica-de-privacidade'] as $slug) {
	$paginas[$slug] = $slug;
}
foreach ($paginas as $caminho => $arquivo) {
	ieadc_seed_pagina_blocos($caminho, '/content/blocos/' . $arquivo . '.json');
}

/*
 * Artigos (posts): topo + corpo + outras publicações (content/blocos/artigo-<slug>.json) e, depois deles,
 * o texto do artigo em parágrafos e títulos (content/blocos/artigo-<slug>-corpo.html), como um voluntário
 * escreveria no editor. O versículo de abertura vai para os campos do post (caixa "Artigo" no painel,
 * metas ieadc_versiculo e ieadc_referencia), que o bloco artigo-corpo lê quando não tem atributo.
 */
foreach (['alcancar-curitiba', 'ate-que-ele-venha', 'orai-uns-pelos-outros'] as $slug) {
	$p = get_page_by_path($slug, OBJECT, 'post');
	if (!$p) {
		WP_CLI::warning("Artigo não encontrado: {$slug}");
		continue;
	}
	$transicao = str_contains($p->post_content, '<!-- wp:html -->') || (str_contains($p->post_content, 'Página em montagem. Volte em breve.') || str_contains($p->post_content, '[Provisório: página em montagem'));
	if (!$transicao && getenv('IEADC_ATUALIZAR') !== '1') {
		WP_CLI::log("Mantido (editado no painel): /{$slug}/");
		continue;
	}
	$itens = json_decode(file_get_contents("/content/blocos/artigo-{$slug}.json"), true);
	if (!is_array($itens)) {
		WP_CLI::error("JSON inválido: artigo-{$slug}.json");
	}
	$html = '';
	foreach ($itens as $item) {
		$attrs = $item['attrs'] ?? [];
		if ($item['bloco'] === 'ieadc/artigo-corpo') {
			foreach (['versiculo', 'referencia'] as $k) {
				if (isset($attrs[$k])) {
					update_post_meta($p->ID, 'ieadc_' . $k, $attrs[$k]);
					unset($attrs[$k]);
				}
			}
		}
		$html .= get_comment_delimited_block_content($item['bloco'], $attrs, '') . "\n\n";
	}
	$html .= trim(file_get_contents("/content/blocos/artigo-{$slug}-corpo.html"));
	wp_update_post(wp_slash(['ID' => $p->ID, 'post_content' => $html]));
	update_post_meta($p->ID, '_wp_page_template', TPL_LARGA);
	WP_CLI::log("Montado com blocos: /{$slug}/");
}
