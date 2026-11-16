<?php
/**
 * Modelo do artigo novo (Posts > Adicionar post abre com os blocos do artigo).
 * Rode com ./bin/testar.sh php (chamado por bin/testar.sh).
 */
require dirname(__DIR__) . '/bootstrap.php';

echo "modelo do artigo novo\n";
$tipo = get_post_type_object('post');
$modelo = $tipo->template ?? [];
ieadc_igual(['ieadc/artigo-topo', 'ieadc/artigo-corpo', 'core/paragraph', 'ieadc/artigos-grade'], array_column($modelo, 0), 'o tipo post tem o modelo: topo, corpo, parágrafo vazio e Continue lendo');
ieadc_ok(empty($tipo->template_lock), 'o modelo não trava a edição');
ieadc_ok(empty(get_post_type_object('page')->template), 'páginas não têm modelo');
ieadc_ok(empty(get_post_type_object('evento')->template), 'eventos não têm modelo');

// Monta o conteúdo do modelo como o editor faria e confere o desenho no site (class="artigo").
$conteudo = '';
foreach ($modelo as [$nome, $attrs]) {
	$conteudo .= get_comment_delimited_block_content($nome, $attrs, $nome === 'core/paragraph' ? '<p>Texto de teste do modelo.</p>' : '');
}
$id = wp_insert_post(['post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Artigo de teste do modelo (apagar)', 'post_content' => $conteudo], true);
ieadc_ok(!is_wp_error($id), 'post de teste criado', is_wp_error($id) ? $id->get_error_message() : null);
if (!is_wp_error($id)) {
	$resp = wp_remote_get(get_permalink($id) ? str_replace(home_url(), getenv('IEADC_TESTE_URL') ?: 'http://wordpress', get_permalink($id)) : '', ['timeout' => 20, 'redirection' => 0]);
	if (is_wp_error($resp)) {
		echo '  pulou: sem acesso HTTP ao site (' . $resp->get_error_message() . ")\n";
	} else {
		$html = wp_remote_retrieve_body($resp);
		ieadc_ok(wp_remote_retrieve_response_code($resp) === 200, 'o artigo novo abre (200)');
		ieadc_ok(str_contains($html, 'class="artigo"') && str_contains($html, 'class="artigo corpo-art"'), 'renderiza com class="artigo" (topo) e corpo do artigo');
		preg_match('#<main.*?</main>#s', $html, $m);
		$main = $m[0] ?? '';
		ieadc_igual(1, substr_count($main, 'Texto de teste do modelo.'), 'o parágrafo do modelo aparece uma só vez no corpo da página');
		ieadc_ok(str_contains($html, 'Continue lendo'), 'a lista "Continue lendo" aparece');
	}
	wp_delete_post($id, true);
	ieadc_ok(get_post($id) === null, 'post de teste apagado');
}
ieadc_teste_fim();
