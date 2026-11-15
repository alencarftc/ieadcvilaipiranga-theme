<?php
/**
 * Cria as páginas da Estrutura do site e o menu principal (rodado por bin/setup.sh com wp eval-file).
 * Pode rodar várias vezes: só cria o que falta e não sobrescreve texto já editado no painel.
 */

const TPL_LARGA = 'page-templates/larga.php';

// Conteúdo com HTML do protótipo (svg, style): o seed roda sem usuário, então desliga o filtro de HTML.
kses_remove_filters();

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
		. "<!-- wp:paragraph {\"className\":\"pend\"} -->\n<p class=\"pend\">[Provisório: página em montagem, segue o protótipo v3.]</p>\n<!-- /wp:paragraph -->\n"
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
	if ($p && !$forcar && !str_contains($p->post_content, '[Provisório: página em montagem')) {
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


$ids = [];
$ids['inicio']    = pagina('inicio', 'Início', 0, 'inicio');
$ids['sobre']     = pagina('sobre-nos', 'Sobre nós', 0, 'sobre-nos');
$ids['lideranca'] = pagina('lideranca', 'Liderança', 0, 'lideranca');
$ids['minis']     = pagina('ministerios', 'Ministérios', 0, 'ministerios');
$minis = [
	'circulo-de-oracao'           => ['Círculo de Oração', 'oracao'],
	'escola-biblica-dominical'    => ['Escola Bíblica Dominical', 'livro'],
	'ensinai'                     => ['Ensinai', 'capelo'],
	'familia'                     => ['Família', 'casa'],
	'missao-e-evangelismo'        => ['Missão e Evangelismo', 'globo'],
	'mocidade'                    => ['Mocidade', 'pessoas'],
	'criancas'                    => ['Crianças', 'crianca'],
];
foreach ($minis as $slug => [$t]) {
	$ids['m-' . $slug] = pagina($slug, $t, $ids['minis'], 'ministerios-' . $slug);
}
$ids['horarios'] = pagina('horarios-e-localizacao', 'Horários e localização', 0, 'horarios-e-localizacao');
$cultos = [
	'culto-de-oracao'            => ['Culto de Oração', 'oracao'],
	'culto-de-ensino'            => ['Culto de Ensino', 'livro'],
	'culto-do-circulo-de-oracao' => ['Culto do Círculo de Oração', 'pessoas'],
	'culto-dominical'            => ['Culto dominical', 'igreja'],
	'ceia-do-senhor'             => ['Ceia do Senhor', 'calice'],
	'vigilia-e-consagracao'      => ['Vigília e consagração', 'chama'],
];
foreach ($cultos as $slug => [$t]) {
	$ids['c-' . $slug] = pagina($slug, $t, 0, $slug);
}
$ids['eventos']   = pagina('eventos', 'Eventos', 0, 'eventos');
$ids['artigos']   = pagina('artigos', 'Artigos', 0, 'artigos');
$ids['galeria']   = pagina('galeria', 'Galeria de fotos e vídeos', 0, 'galeria');
$ids['aceitar']   = pagina('aceitar-jesus', 'Aceitar Jesus', 0, 'aceitar-jesus');
$ids['contato']   = pagina('contato', 'Fale conosco', 0, 'contato');
$ids['pedidos']   = pagina('pedidos-de-oracao', 'Pedidos de oração', 0, 'pedidos-de-oracao');
$ids['politica']  = pagina('politica-de-privacidade', 'Política de privacidade', 0, 'politica-de-privacidade');

// Leitura: página inicial fixa, artigos na página Artigos, política oficial do WordPress.
update_option('show_on_front', 'page');
update_option('page_on_front', $ids['inicio']);
update_option('page_for_posts', 0); // a página Artigos tem layout próprio

// Artigos de exemplo do protótipo (posts).
foreach (['alcancar-curitiba' => 'Alcançar Curitiba para Cristo', 'ate-que-ele-venha' => 'Até que ele venha', 'orai-uns-pelos-outros' => 'Orai uns pelos outros'] as $slug => $t) {
	pagina($slug, $t, 0, 'artigo-' . $slug, 'post');
}
update_option('wp_page_for_privacy_policy', $ids['politica']);

// Some com o "Olá, mundo!" e a "Página de exemplo" da instalação.
foreach (['ola-mundo', 'hello-world'] as $s) {
	$p = get_page_by_path($s, OBJECT, 'post');
	$p && wp_delete_post($p->ID, true);
}
foreach (['pagina-exemplo', 'sample-page'] as $s) {
	$p = get_page_by_path($s);
	$p && wp_delete_post($p->ID, true);
}

// Menu principal (estrutura aprovada em 06/10 e 09/10/2026).
$nome_menu = 'Menu principal';
$menu = wp_get_nav_menu_object($nome_menu);
if (!$menu) {
	$menu_id = wp_create_nav_menu($nome_menu);
	$add = function (int $page_id, int $pai = 0, string $classes = '', string $titulo = '') use ($menu_id): int {
		return wp_update_nav_menu_item($menu_id, 0, [
			'menu-item-object-id' => $page_id,
			'menu-item-object'    => 'page',
			'menu-item-type'      => 'post_type',
			'menu-item-parent-id' => $pai,
			'menu-item-classes'   => $classes,
			'menu-item-title'     => $titulo,
			'menu-item-status'    => 'publish',
		]);
	};
	$add($ids['inicio']);
	$igreja = $add($ids['sobre'], 0, '', 'A Igreja');
	$add($ids['lideranca'], $igreja);
	$mi = $add($ids['minis'], $igreja);
	foreach ($minis as $slug => [$t, $ico]) {
		$add($ids['m-' . $slug], $mi, 'ico-' . $ico);
	}
	$hor = $add($ids['horarios'], 0, 'sub-cultos', 'Horários');
	foreach ($cultos as $slug => [$t, $ico]) {
		$add($ids['c-' . $slug], $hor, 'ico-' . $ico);
	}
	$add($ids['eventos']);
	$cont = $add($ids['artigos'], 0, '', 'Conteúdo');
	$add($ids['galeria'], $cont);
	$add($ids['aceitar']);
	$fale = $add($ids['contato'], 0, '', 'Contato');
	$add($ids['pedidos'], $fale);
	$add($ids['politica'], $fale);
	$menu = wp_get_nav_menu_object($menu_id);
	WP_CLI::log('Menu principal criado.');
}
$locais = get_theme_mod('nav_menu_locations', []);
$locais['primary'] = $menu->term_id;
set_theme_mod('nav_menu_locations', $locais);

WP_CLI::success('Páginas e menu prontos.');
