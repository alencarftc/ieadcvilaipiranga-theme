<?php
/**
 * Funções do seed (carregadas por scripts/seed.php antes dos arquivos NN-*.php).
 */

const TPL_LARGA = 'page-templates/larga.php';


/** Importa as fotos de content/media uma única vez e devolve [arquivo => URL]. */
function midias(): array {
	$mapa = [];
	foreach (glob('/content/media/*.webp') as $arq) {
		$nome = basename($arq);
		$ja = get_posts(['post_type' => 'attachment', 'meta_key' => '_ieadc_origem', 'meta_value' => $nome, 'fields' => 'ids', 'posts_per_page' => 1]);
		if ($ja) {
			$id = $ja[0];
		} else {
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$tmp = wp_tempnam($nome);
			copy($arq, $tmp);
			$id = media_handle_sideload(['name' => $nome, 'tmp_name' => $tmp], 0);
			if (is_wp_error($id)) {
				WP_CLI::warning("$nome: " . $id->get_error_message());
				continue;
			}
			update_post_meta($id, '_ieadc_origem', $nome);
		}
		$mapa[$nome] = wp_make_link_relative(wp_get_attachment_url($id));
	}
	WP_CLI::log(count($mapa) . ' fotos na biblioteca de mídia.');
	return $mapa;
}

/** Conteúdo convertido do protótipo (content/paginas), com as fotos trocadas pela mídia. */
function do_prototipo(string $arquivo): string {
	static $midia = null;
	$caminho = '/content/paginas/' . $arquivo . '.html';
	if (!is_file($caminho)) {
		return '';
	}
	$midia ??= midias();
	return preg_replace_callback('/\{\{img:([\w.-]+)\}\}/', fn($m) => $midia[$m[1]] ?? '', file_get_contents($caminho));
}

function provisorio(string $titulo, string $extra = ''): string {
	return "<!-- wp:group {\"className\":\"wrap\",\"layout\":{\"type\":\"constrained\"}} -->\n<div class=\"wp-block-group wrap\">"
		. "<!-- wp:heading {\"level\":1,\"className\":\"h1\"} -->\n<h1 class=\"wp-block-heading h1\">{$titulo}</h1>\n<!-- /wp:heading -->\n"
		. "<!-- wp:paragraph -->\n<p>Página em montagem. Volte em breve.</p>\n<!-- /wp:paragraph -->\n"
		. $extra . "</div>\n<!-- /wp:group -->";
}

/**
 * Cria a página (ou atualiza, se ela ainda tem o texto provisório ou se IEADC_ATUALIZAR=1).
 * $origem = nome do arquivo do protótipo (sem .html); vazio = página provisória.
 */
function pagina(string $slug, string $titulo, int $pai = 0, string $origem = '', string $tipo = 'page', string $extra = ''): int {
	$caminho = $pai ? get_page_uri($pai) . '/' . $slug : $slug;
	$p = get_page_by_path($caminho, OBJECT, $tipo);
	$conteudo = $origem ? do_prototipo($origem) : '';
	$tpl = $conteudo ? TPL_LARGA : 'default';
	$conteudo = $conteudo ?: provisorio($titulo, $extra);

	$forcar = getenv('IEADC_ATUALIZAR') === '1';
	// A política de privacidade que o WordPress cria na instalação (rascunho com o texto-modelo, mesmo endereço da nossa)
	// ainda não foi editada por ninguém: o seed a assume e publica.
	$modelo_wp = $p && $p->post_status === 'draft' && str_contains($p->post_content, 'privacy-policy-tutorial');
	if ($p && !$forcar && !$modelo_wp && !(str_contains($p->post_content, 'Página em montagem. Volte em breve.') || str_contains($p->post_content, '[Provisório: página em montagem'))) {
		return $p->ID; // já editada no painel: não mexe
	}
	$dados = [
		'post_type'    => $tipo,
		'post_status'  => 'publish',
		'post_name'    => $slug,
		'post_title'   => $titulo,
		'post_parent'  => $pai,
		'post_content' => $conteudo,
		'meta_input'   => ['_wp_page_template' => $tpl],
	];
	if ($p) {
		if ($p->post_content === $conteudo) {
			return $p->ID;
		}
		$dados['ID'] = $p->ID;
	}
	$id = wp_insert_post(wp_slash($dados));
	WP_CLI::log(($p ? 'Atualizada' : 'Criada') . ": /{$caminho}/");
	return $id;
}

/** Ministérios na ordem do menu: slug => [título, ícone]. */
function ieadc_seed_minis(): array {
	return [
	'circulo-de-oracao'           => ['Círculo de Oração', 'oracao'],
	'escola-biblica-dominical'    => ['Escola Bíblica Dominical', 'livro'],
	'ensinai'                     => ['Ensinai', 'capelo'],
	'familia'                     => ['Família', 'casa'],
	'missao-e-evangelismo'        => ['Missão e Evangelismo', 'globo'],
	'mocidade'                    => ['Mocidade', 'pessoas'],
	'criancas'                    => ['Crianças', 'crianca'],
	];
}

/** Cultos fixos na ordem do menu: slug => [título, ícone]. */
function ieadc_seed_cultos(): array {
	return [
	'culto-de-oracao'            => ['Culto de Oração', 'oracao'],
	'culto-de-ensino'            => ['Culto de Ensino', 'livro'],
	'culto-do-circulo-de-oracao' => ['Culto do Círculo de Oração', 'pessoas'],
	'culto-dominical'            => ['Culto dominical', 'igreja'],
	'ceia-do-senhor'             => ['Ceia do Senhor', 'calice'],
	'vigilia-e-consagracao'      => ['Vigília e consagração', 'chama'],
	];
}

/**
 * Monta uma página com blocos ieadc/* a partir de content/blocos/<arquivo>.json (usado pelos P1–P3).
 *
 * Formato do JSON: [{"bloco": "ieadc/hero", "attrs": {...}}, ...]
 * - Atributo de imagem pelo nome do arquivo ("f_hero.webp") vira o ID da mídia.
 *   Vale para strings terminadas em .webp/.jpg/.png e para listas delas, em qualquer nível.
 * - Atributo de link pelo caminho sem barras ("horarios-e-localizacao") fica como está:
 *   o bloco resolve com ieadc_link().
 *
 * $caminho: caminho da página no WordPress (ex.: "ministerios/mocidade"); $tipo: page ou post.
 * Só sobrescreve página com o conteúdo de transição (ou com IEADC_ATUALIZAR=1). Devolve o ID.
 */
function ieadc_seed_pagina_blocos(string $caminho, string $json, string $tipo = 'page'): int {
	static $midia = null;
	$midia ??= midias_ids();
	$p = get_page_by_path($caminho, OBJECT, $tipo);
	if (!$p) {
		WP_CLI::warning("Página não encontrada: {$caminho}");
		return 0;
	}
	$itens = json_decode(file_get_contents($json), true);
	if (!is_array($itens)) {
		WP_CLI::error("JSON inválido: {$json}");
	}
	$troca = function ($v) use (&$troca, $midia) {
		if (is_array($v)) {
			return array_map($troca, $v);
		}
		if (is_string($v) && preg_match('/^[\w.-]+\.(webp|jpe?g|png)$/', $v)) {
			return $midia[$v] ?? 0;
		}
		return $v;
	};
	$html = '';
	foreach ($itens as $item) {
		$attrs = $troca($item['attrs'] ?? []);
		$html .= get_comment_delimited_block_content($item['bloco'], $attrs, '') . "\n\n";
	}
	$transicao = str_contains($p->post_content, '<!-- wp:html -->') || (str_contains($p->post_content, 'Página em montagem. Volte em breve.') || str_contains($p->post_content, '[Provisório: página em montagem'));
	if (!$transicao && getenv('IEADC_ATUALIZAR') !== '1') {
		WP_CLI::log("Mantida (editada no painel): /{$caminho}/");
		return $p->ID;
	}
	wp_update_post(wp_slash(['ID' => $p->ID, 'post_content' => trim($html)]));
	update_post_meta($p->ID, '_wp_page_template', TPL_LARGA);
	WP_CLI::log("Montada com blocos: /{$caminho}/");
	return $p->ID;
}

/** [arquivo => ID] das fotos importadas por midias(). */
function midias_ids(): array {
	$mapa = [];
	foreach (get_posts(['post_type' => 'attachment', 'meta_key' => '_ieadc_origem', 'posts_per_page' => -1, 'fields' => 'ids']) as $id) {
		$mapa[get_post_meta($id, '_ieadc_origem', true)] = $id;
	}
	return $mapa;
}
