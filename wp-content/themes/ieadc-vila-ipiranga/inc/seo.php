<?php
/**
 * M6 e fase 2: SEO, desempenho e segurança do tema.
 *
 * Funciona sozinho (sem plugin) e convive com o Yoast: se o Yoast estiver ativo, ele imprime título, descrição,
 * Open Graph, Twitter e canonical (os filtros abaixo só entregam os dados do site) e os dados estruturados entram no
 * grafo dele (inc/seo-schema.php); sem o Yoast, imprimimos as mesmas metatags e o mesmo grafo.
 * Regras: título até 60 caracteres, descrição de 70 a 160, uma foto de compartilhamento em toda página, nada de dado inventado.
 */
defined('ABSPATH') || exit;

const IEADC_SEO_TITULO_MAX = 60;
const IEADC_SEO_DESC_MIN   = 70;
const IEADC_SEO_DESC_MAX   = 160;

/** Primeiro bloco com um dos nomes dados, no conteúdo do post: os atributos (pode ser []), ou null se não houver. */
function ieadc_seo_bloco(?WP_Post $post, array $nomes): ?array {
	static $cache = [];
	$post ??= get_post();
	if (!$post) {
		return null;
	}
	$chave = $post->ID . '|' . implode(',', $nomes);
	if (!array_key_exists($chave, $cache)) {
		$procura = function (array $blocos) use (&$procura, $nomes) {
			foreach ($blocos as $b) {
				if (in_array($b['blockName'] ?? '', $nomes, true)) {
					return (array) $b['attrs'];
				}
				if (!empty($b['innerBlocks']) && ($r = $procura($b['innerBlocks'])) !== null) {
					return $r;
				}
			}
			return null;
		};
		$cache[$chave] = $procura(parse_blocks($post->post_content));
	}
	return $cache[$chave];
}

/** Primeiro bloco de topo (ieadc/hero ou ieadc/hero-home) do conteúdo do post (atributos), ou []. */
function ieadc_seo_hero(?WP_Post $post = null): array {
	return ieadc_seo_bloco($post, ['ieadc/hero', 'ieadc/hero-home']) ?? [];
}

/** Endereço de Configurações separado em partes: "Rua X, 555 - Capão Raso, Curitiba - PR". */
function ieadc_seo_endereco(): array {
	$e = trim((string) ieadc_dado('endereco'));
	$p = ['rua' => $e, 'bairro' => '', 'cidade' => 'Curitiba', 'uf' => 'PR'];
	if (preg_match('/^(.+?)\s+-\s+(.+?),\s*(.+?)\s+-\s+([A-Za-z]{2})$/u', $e, $m)) {
		$p = ['rua' => $m[1], 'bairro' => $m[2], 'cidade' => $m[3], 'uf' => strtoupper($m[4])];
	}
	return $p;
}

/** Texto limpo para título e descrição: sem tags, entidades nem pendências ("[a confirmar]" não vai para o Google). */
function ieadc_seo_limpar(string $t): string {
	$t = html_entity_decode(wp_strip_all_tags(strip_shortcodes($t)), ENT_QUOTES, 'UTF-8');
	$t = (string) preg_replace('/\s*\[[^\]]*\]/u', '', $t);
	return trim((string) preg_replace('/\s+/u', ' ', $t));
}

/** Corta no limite sem partir palavra e fecha com reticências. */
function ieadc_seo_cortar(string $t, int $max): string {
	if (mb_strlen($t) <= $max) {
		return $t;
	}
	$c = mb_substr($t, 0, $max - 1);
	if (!preg_match('/\s/u', mb_substr($t, $max - 1, 1))) {
		$c = (string) preg_replace('/\s+\S*$/u', '', $c) ?: $c;
	}
	return rtrim($c, " ,;:.-–·") . '…';
}

function ieadc_seo_nome(): string {
	return ieadc_dado('nome') ?: (string) get_bloginfo('name');
}

/** Título da página inicial: o nome e o que a igreja é, para quem procura "igreja em Curitiba". */
function ieadc_seo_titulo_home(): string {
	return ieadc_seo_nome() . ' · Igreja Assembleia de Deus em Curitiba';
}

/** Título único da página: "Página · IEADC Vila Ipiranga", com até 60 caracteres (se passar, sem o nome da igreja). */
function ieadc_seo_titulo(?WP_Post $post = null): string {
	$post ??= is_singular() ? get_post() : null;
	if (is_front_page() && (!$post || (int) $post->ID === (int) get_option('page_on_front'))) {
		return ieadc_seo_titulo_home();
	}
	if (!$post) {
		return '';
	}
	$t = ieadc_seo_limpar(get_the_title($post));
	$completo = $t . ' · ' . ieadc_seo_nome();
	return mb_strlen($completo) <= IEADC_SEO_TITULO_MAX ? $completo : ieadc_seo_cortar($t, IEADC_SEO_TITULO_MAX);
}

/** Descrição do evento a partir dos campos dele (data, horário e local), quando não há resumo. */
function ieadc_seo_descricao_evento(WP_Post $p): string {
	$ini = function_exists('ieadc_agenda_data') ? ieadc_agenda_data((string) get_post_meta($p->ID, 'evento_data', true)) : null;
	$partes = [];
	if ($ini) {
		$d = DateTimeImmutable::createFromFormat('!Y-m-d', $ini, ieadc_agenda_fuso());
		$partes[] = wp_date('j \d\e F \d\e Y', $d->getTimestamp(), ieadc_agenda_fuso());
	}
	$hora = ieadc_seo_limpar((string) get_post_meta($p->ID, 'evento_horario', true)); // sem "[a confirmar]"
	if ($hora !== '') { // "19h30" vira "às 19h30"; "19h30 às 21h" vira "das 19h30 às 21h"
		$partes[] = (preg_match_all('/\d{1,2}h/u', $hora) > 1 ? 'das ' : 'às ') . $hora;
	}
	$local = ieadc_seo_limpar((string) get_post_meta($p->ID, 'evento_local', true));
	$txt = ieadc_seo_limpar(get_the_title($p)) . ($partes ? ': ' . implode(', ', $partes) : '') . ($local !== '' ? ' · ' . $local : '');
	return $txt . '. Evento da ' . ieadc_seo_nome() . '.';
}

/** Descrição da página (70 a 160 caracteres): resumo manual, senão o `lead` do topo, senão o começo do texto. */
function ieadc_seo_descricao(?WP_Post $post = null): string {
	$post ??= is_singular() ? get_post() : null;
	if (!$post) {
		return is_front_page() ? ieadc_seo_limpar((string) get_bloginfo('description')) : '';
	}
	$txt = trim((string) $post->post_excerpt);
	if ($txt === '') {
		$txt = (string) (ieadc_seo_hero($post)['lead'] ?? '');
	}
	if ($txt === '' && $post->post_type === 'evento') {
		$txt = ieadc_seo_descricao_evento($post);
	}
	if ($txt === '') {
		$txt = (string) get_the_excerpt($post);
	}
	$txt = str_replace('{atualizacao}', (string) get_the_modified_date('', $post), $txt); // ver blocos/hero/render.php
	$txt = ieadc_seo_limpar($txt);
	if ($txt !== '' && mb_strlen($txt) < IEADC_SEO_DESC_MIN) { // curta demais: acrescenta quem somos e onde fica
		$e = ieadc_seo_endereco();
		$quem = $post->post_type === 'post' ? 'Artigo da ' . ieadc_seo_nome() . ', igreja' : ieadc_seo_nome() . ', igreja';
		$txt = rtrim($txt, " ") . (preg_match('/[.!?…]$/u', $txt) ? ' ' : '. ') . $quem . ($e['bairro'] !== '' ? ' no ' . $e['bairro'] . ',' : '') . ' em ' . $e['cidade'] . '.';
	}
	return ieadc_seo_cortar($txt, IEADC_SEO_DESC_MAX);
}

/** ID da foto de compartilhamento: a do topo da página, a foto de destaque ou a do topo da página inicial. */
function ieadc_seo_imagem_id(?WP_Post $post = null): int {
	$post ??= is_singular() ? get_post() : null;
	if (!$post) {
		return 0;
	}
	$id = (int) (ieadc_seo_hero($post)['foto'] ?? 0);
	if (!$id) {
		$id = (int) get_post_thumbnail_id($post);
	}
	if (!$id && ($inicio = (int) get_option('page_on_front')) && $inicio !== (int) $post->ID && ($pi = get_post($inicio))) {
		$id = (int) (ieadc_seo_hero($pi)['foto'] ?? 0);
	}
	return $id;
}

/** Foto de compartilhamento com as medidas: ['url' => , 'largura' => , 'altura' => ] ou []. */
function ieadc_seo_imagem_dados(?WP_Post $post = null): array {
	$id = ieadc_seo_imagem_id($post);
	$img = $id ? wp_get_attachment_image_src($id, 'full') : false;
	return $img ? ['url' => (string) $img[0], 'largura' => (int) $img[1], 'altura' => (int) $img[2]] : [];
}

/** URL da foto do topo (Open Graph e preload). */
function ieadc_seo_imagem(): string {
	return ieadc_seo_imagem_dados()['url'] ?? '';
}

function ieadc_seo_yoast(): bool {
	return defined('WPSEO_VERSION');
}

/* Páginas aceitam resumo (campo "Resumo" do editor): é a descrição que o Google mostra. */
add_action('init', fn() => add_post_type_support('page', 'excerpt'));

/* ---------- Título: "Página · IEADC Vila Ipiranga" ---------- */
add_filter('document_title_separator', fn() => '·');
add_filter('document_title_parts', function (array $p): array {
	if (is_front_page() || is_singular()) {
		return ['title' => ieadc_seo_titulo() ?: ($p['title'] ?? '')];
	}
	unset($p['tagline']);
	$p['site'] = ieadc_seo_nome();
	return $p;
});
/* Com o Yoast ativo é ele que imprime título, descrição, canonical, Open Graph e Twitter; estes filtros só trocam o
   conteúdo para o do tema (sem duplicar nada). Título e descrição valem também para og: e twitter:. */
function ieadc_seo_titulo_yoast($t) {
	return (is_front_page() || is_singular()) ? (ieadc_seo_titulo() ?: $t) : $t;
}
add_filter('wpseo_title', 'ieadc_seo_titulo_yoast');
add_filter('wpseo_opengraph_title', 'ieadc_seo_titulo_yoast');
add_filter('wpseo_twitter_title', 'ieadc_seo_titulo_yoast');
add_filter('wpseo_metadesc', fn($d) => ieadc_seo_descricao() ?: $d);
add_filter('wpseo_opengraph_desc', fn($d) => ieadc_seo_descricao() ?: $d);
add_filter('wpseo_twitter_description', fn($d) => ieadc_seo_descricao() ?: $d);
/* Tipo Open Graph: "article" só nos artigos; páginas e eventos são "website" (o Yoast põe "article" em toda página interna). */
add_filter('wpseo_opengraph_type', fn($t) => is_singular('post') ? 'article' : 'website');
/* Foto do topo como imagem de compartilhamento, só quando ninguém escolheu outra (foto de destaque ou campo do Yoast). */
add_filter('wpseo_add_opengraph_additional_images', function ($imagens) {
	$img = ieadc_seo_imagem();
	if ($img !== '' && is_object($imagens) && method_exists($imagens, 'get_images') && !$imagens->get_images()) {
		$imagens->add_image_by_url($img);
	}
	return $imagens;
});

/* ---------- Metatags próprias (só sem Yoast) ---------- */
/* Busca e anexos fora do índice: pelo filtro do WordPress (uma só tag robots); com Yoast, quem decide é ele. */
add_filter('wp_robots', function (array $r): array {
	if (!ieadc_seo_yoast() && (is_search() || is_attachment())) {
		unset($r['index']);
		$r['noindex'] = true;
		$r['follow']  = true;
	}
	return $r;
});
/* Sem Yoast imprimimos o canonical abaixo; o do WordPress sairia duplicado. */
add_action('init', function () {
	if (!ieadc_seo_yoast()) {
		remove_action('wp_head', 'rel_canonical');
	}
});
add_action('wp_head', function () {
	if (is_admin() || is_404() || is_feed() || ieadc_seo_yoast()) {
		return;
	}
	$desc = ieadc_seo_descricao();
	$img  = ieadc_seo_imagem_dados();
	$url  = is_singular() ? get_permalink() : home_url('/');
	$tit  = wp_get_document_title();
	if ($desc !== '') {
		echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
	}
	echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
	$og = ['og:locale' => 'pt_BR', 'og:type' => is_singular('post') ? 'article' : 'website', 'og:site_name' => ieadc_seo_nome(),
		'og:title' => $tit, 'og:description' => $desc, 'og:url' => $url, 'og:image' => $img['url'] ?? '',
		'og:image:width' => $img['largura'] ?? '', 'og:image:height' => $img['altura'] ?? ''];
	foreach ($og as $k => $v) {
		if ($v !== '' && $v !== null && $v !== 0) {
			echo '<meta property="' . esc_attr($k) . '" content="' . esc_attr($v) . '">' . "\n";
		}
	}
	echo '<meta name="twitter:card" content="' . ($img ? 'summary_large_image' : 'summary') . '">' . "\n";
}, 2);

/* Com ou sem Yoast: cor da barra do navegador no celular (o marinho da marca) e o mapa do site para quem lê o HTML. */
add_action('wp_head', function () {
	if (is_admin() || is_feed()) {
		return;
	}
	echo '<meta name="theme-color" content="#071946">' . "\n";
	echo '<link rel="sitemap" type="application/xml" title="Mapa do site" href="' . esc_url(home_url(ieadc_seo_yoast() ? '/sitemap_index.xml' : '/wp-sitemap.xml')) . '">' . "\n";
}, 2);

/* ---------- llms.txt: resumo curto e factual da igreja para leitores automáticos (IA) ---------- */
function ieadc_seo_llms(): string {
	$nome = ieadc_seo_nome();
	$e = ieadc_seo_endereco();
	$l = ['# ' . $nome, ''];
	$longo = ieadc_dado('nome_longo') ?: $nome;
	$l[] = '> ' . $longo . ($e['bairro'] !== '' ? ', no bairro ' . $e['bairro'] : '') . (stripos($longo, $e['cidade']) === false ? ', em ' . $e['cidade'] : '') . '.';
	$l[] = '';
	$l[] = '## Endereço e contato';
	$l[] = '- Endereço: ' . ieadc_dado('endereco');
	foreach (['telefone' => 'Telefone', 'email' => 'E-mail'] as $k => $r) {
		if (ieadc_dado($k) !== '') {
			$l[] = '- ' . $r . ': ' . ieadc_dado($k);
		}
	}
	$l[] = '- Site: ' . home_url('/');
	foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube', 'site_sede' => 'Site da IEADC (igreja sede)'] as $k => $r) {
		if (ieadc_dado($k) !== '') {
			$l[] = '- ' . $r . ': ' . ieadc_dado($k);
		}
	}
	$cultos = array_filter(array_map('trim', preg_split('/\R/', (string) ieadc_dado('cultos'))));
	if ($cultos) {
		$l[] = '';
		$l[] = '## Cultos e encontros';
		foreach ($cultos as $c) {
			$l[] = '- ' . $c;
		}
	}
	$l[] = '';
	$l[] = '## Páginas principais';
	foreach (['horarios-e-localizacao', 'contato', 'pedidos-de-oracao', 'ministerios', 'eventos', 'artigos', 'sobre-nos', 'lideranca', 'aceitar-jesus', 'galeria'] as $slug) {
		$p = get_page_by_path($slug);
		if ($p && $p->post_status === 'publish') {
			$l[] = '- [' . ieadc_seo_limpar(get_the_title($p)) . '](' . get_permalink($p) . '): ' . ieadc_seo_descricao($p);
		}
	}
	$l[] = '- [Mapa do site](' . home_url(ieadc_seo_yoast() ? '/sitemap_index.xml' : '/wp-sitemap.xml') . ')';
	return implode("\n", $l) . "\n";
}
add_action('parse_request', function ($wp) {
	if (($wp->request ?? '') !== 'llms.txt') {
		return;
	}
	status_header(200);
	header('Content-Type: text/plain; charset=utf-8');
	header('X-Content-Type-Options: nosniff');
	header('Cache-Control: public, max-age=3600');
	echo ieadc_seo_llms(); // phpcs:ignore WordPress.Security.EscapeOutput -- texto puro, sem HTML
	exit;
});

/* ---------- Dados estruturados (JSON-LD): ver inc/seo-schema.php ---------- */
require_once __DIR__ . '/seo-schema.php';

/* ---------- Desempenho ---------- */
/* A foto do topo (LCP): preload com prioridade alta e versão menor no celular, em inc/desempenho.php. */

/* Sem emojis, embeds e links extras do WordPress no cabeçalho. */
add_action('init', function () {
	remove_action('wp_head', 'print_emoji_detection_script', 7);
	remove_action('wp_print_styles', 'print_emoji_styles');
	remove_action('wp_head', 'wp_generator');
	remove_action('wp_head', 'wlwmanifest_link');
	remove_action('wp_head', 'rsd_link');
	remove_action('wp_head', 'wp_shortlink_wp_head');
	remove_action('wp_head', 'wp_oembed_add_discovery_links');
});

/* CSS/JS do Blocksy e do core: só desligar depois de medir com o Coverage do Chrome e passar ./bin/testar.sh visual
   (global-styles e block-library carregam variáveis e layout que os blocos podem usar). Ver docs/operacao/plugins.md. */

/* ---------- Segurança ---------- */
add_filter('xmlrpc_enabled', '__return_false');
add_filter('wp_headers', function (array $h): array {
	unset($h['X-Pingback']);
	return $h;
});
/* Cabeçalhos de segurança (nosniff, Referrer-Policy, Permissions-Policy, X-Frame-Options, COOP, HSTS e CSP): mu-plugins/ieadc-core/seguranca.php. */
/* Não expõe a lista de usuários (e os logins dos administradores) para visitantes. */
add_filter('rest_endpoints', function (array $e): array {
	if (!is_user_logged_in()) {
		unset($e['/wp/v2/users'], $e['/wp/v2/users/(?P<id>[\d]+)']);
	}
	return $e;
});
add_filter('wp_sitemaps_add_provider', fn($p, $nome) => $nome === 'users' ? false : $p, 10, 2);
