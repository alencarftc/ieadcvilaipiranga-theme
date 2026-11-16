<?php
/**
 * M6: SEO, desempenho e segurança do tema.
 *
 * Funciona sozinho (sem plugin) e convive com o Yoast: se o Yoast estiver ativo, ele
 * imprime título, descrição, Open Graph e canonical (os filtros abaixo só entregam os
 * dados do site) e nós só acrescentamos o JSON-LD `Church`.
 */
defined('ABSPATH') || exit;

/** Primeiro bloco de topo (ieadc/hero ou ieadc/hero-home) do conteúdo do post (atributos), ou []. */
function ieadc_seo_hero(?WP_Post $post = null): array {
	static $cache = [];
	$post ??= get_post();
	if (!$post) {
		return [];
	}
	if (!isset($cache[$post->ID])) {
		$cache[$post->ID] = [];
		$procura = function (array $blocos) use (&$procura) {
			foreach ($blocos as $b) {
				if (in_array($b['blockName'] ?? '', ['ieadc/hero', 'ieadc/hero-home'], true)) {
					return (array) $b['attrs'];
				}
				if (!empty($b['innerBlocks']) && ($r = $procura($b['innerBlocks']))) {
					return $r;
				}
			}
			return [];
		};
		$cache[$post->ID] = $procura(parse_blocks($post->post_content));
	}
	return $cache[$post->ID];
}

/** Descrição da página: resumo manual, senão o `lead` do hero, senão o começo do texto. */
function ieadc_seo_descricao(): string {
	$post = is_singular() ? get_post() : null;
	if (!$post) {
		return is_front_page() ? (string) get_bloginfo('description') : '';
	}
	$txt = trim((string) $post->post_excerpt);
	if ($txt === '') {
		$txt = (string) (ieadc_seo_hero($post)['lead'] ?? '');
	}
	if ($txt === '') {
		$txt = (string) get_the_excerpt($post);
	}
	$txt = str_replace('{atualizacao}', (string) get_the_modified_date('', $post), $txt); // ver blocos/hero/render.php
	$txt = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags(strip_shortcodes($txt))));
	$txt = trim(str_replace(['[', ']'], '', $txt)); // [a confirmar] não vai para o Google
	return mb_strlen($txt) > 160 ? rtrim(mb_substr($txt, 0, 157)) . '…' : $txt;
}

/** URL da foto do topo (Open Graph e preload); cai na foto de destaque. */
function ieadc_seo_imagem(): string {
	$post = is_singular() ? get_post() : null;
	if (!$post) {
		return '';
	}
	$id = (int) (ieadc_seo_hero($post)['foto'] ?? 0);
	if (!$id) {
		$id = (int) get_post_thumbnail_id($post);
	}
	return $id && function_exists('ieadc_img') ? ieadc_img($id, 'full') : '';
}

function ieadc_seo_yoast(): bool {
	return defined('WPSEO_VERSION');
}

/* ---------- Título: "Página · IEADC Vila Ipiranga" ---------- */
add_filter('document_title_separator', fn() => '·');
add_filter('document_title_parts', function (array $p): array {
	$nome = ieadc_dado('nome') ?: get_bloginfo('name');
	if (is_front_page()) {
		return ['title' => $nome . ' · ' . (ieadc_dado('frase_topo') ?: get_bloginfo('description'))];
	}
	unset($p['tagline']);
	$p['site'] = $nome;
	return $p;
});
/* Com o Yoast ativo é ele que imprime título, descrição, canonical, Open Graph e Twitter; estes filtros só trocam o
   conteúdo para o do tema (sem duplicar nada). Título e descrição valem também para og: e twitter:. */
function ieadc_seo_titulo_yoast($t) {
	if (is_front_page()) {
		return (ieadc_dado('nome') ?: get_bloginfo('name')) . ' · ' . (ieadc_dado('frase_topo') ?: get_bloginfo('description'));
	}
	if (is_singular()) {
		return get_the_title() . ' · ' . (ieadc_dado('nome') ?: get_bloginfo('name'));
	}
	return $t;
}
add_filter('wpseo_title', 'ieadc_seo_titulo_yoast');
add_filter('wpseo_opengraph_title', 'ieadc_seo_titulo_yoast');
add_filter('wpseo_twitter_title', 'ieadc_seo_titulo_yoast');
add_filter('wpseo_metadesc', fn($d) => ieadc_seo_descricao() ?: $d);
add_filter('wpseo_opengraph_desc', fn($d) => ieadc_seo_descricao() ?: $d);
add_filter('wpseo_twitter_description', fn($d) => ieadc_seo_descricao() ?: $d);
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
	$img  = ieadc_seo_imagem();
	$url  = is_singular() ? get_permalink() : home_url('/');
	$tit  = wp_get_document_title();
	if ($desc !== '') {
		echo '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
	}
	echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
	$og = ['og:locale' => 'pt_BR', 'og:type' => is_singular('post') ? 'article' : 'website', 'og:site_name' => ieadc_dado('nome'),
		'og:title' => $tit, 'og:description' => $desc, 'og:url' => $url, 'og:image' => $img];
	foreach ($og as $k => $v) {
		if ($v !== '' && $v !== null) {
			echo '<meta property="' . esc_attr($k) . '" content="' . esc_attr($v) . '">' . "\n";
		}
	}
	echo '<meta name="twitter:card" content="' . ($img !== '' ? 'summary_large_image' : 'summary') . '">' . "\n";
}, 2);

/* ---------- Dados estruturados: Church ---------- */
/** Linhas "Segunda · 20h · Culto de Oração" do resumo de cultos viram openingHoursSpecification. */
function ieadc_seo_horarios(): array {
	$dias = ['segunda' => 'Monday', 'terça' => 'Tuesday', 'terca' => 'Tuesday', 'quarta' => 'Wednesday', 'quinta' => 'Thursday',
		'sexta' => 'Friday', 'sábado' => 'Saturday', 'sabado' => 'Saturday', 'domingo' => 'Sunday'];
	$saida = [];
	foreach (preg_split('/\R/', (string) ieadc_dado('cultos')) as $linha) {
		if (!preg_match('/^\s*(segunda|terça|terca|quarta|quinta|sexta|s[áa]bado|domingo)\s*·\s*(\d{1,2})h(\d{2})?\b/iu', $linha, $m)) {
			continue; // "2º sábado", "9h30 EBD · 19h culto" e afins ficam só no texto da página
		}
		$h = sprintf('%02d:%02d', (int) $m[2], (int) ($m[3] ?? 0));
		$saida[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $dias[mb_strtolower($m[1])] ?? 'Sunday', 'opens' => $h, 'closes' => sprintf('%02d:%02d', min(23, (int) $m[2] + 2), (int) ($m[3] ?? 0))];
	}
	return $saida;
}

function ieadc_seo_church(): array {
	$redes = array_values(array_filter([ieadc_dado('instagram'), ieadc_dado('facebook'), ieadc_dado('youtube')]));
	$dados = [
		'@context'  => 'https://schema.org',
		'@type'     => 'Church',
		'@id'       => home_url('/#igreja'),
		'name'      => ieadc_dado('nome_longo') ?: ieadc_dado('nome'),
		'alternateName' => ieadc_dado('nome'),
		'url'       => home_url('/'),
		'telephone' => ieadc_dado('telefone'),
		'email'     => ieadc_dado('email'),
		'address'   => ['@type' => 'PostalAddress', 'streetAddress' => 'Rua Marechal Althayr Roszanniy, 555', 'addressLocality' => 'Curitiba', 'addressRegion' => 'PR', 'addressCountry' => 'BR'],
		'sameAs'    => $redes,
		'openingHoursSpecification' => ieadc_seo_horarios(),
	];
	$logo = get_stylesheet_directory_uri() . '/assets/img/logo_cor.webp';
	$dados['logo'] = $logo;
	return array_filter($dados, fn($v) => $v !== '' && $v !== []);
}

add_action('wp_head', function () {
	if (!is_front_page()) {
		return;
	}
	echo '<script type="application/ld+json">' . wp_json_encode(ieadc_seo_church(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . "</script>\n";
}, 3);

/* ---------- Desempenho ---------- */
/* A foto do topo é o LCP: preload só dela (fundos não aceitam loading="lazy"). */
add_action('wp_head', function () {
	$img = is_singular() ? ieadc_seo_imagem() : '';
	if ($img !== '' && !empty(ieadc_seo_hero()['foto']) && (ieadc_seo_hero()['variante'] ?? 'pagina') !== 'sem-foto') {
		echo '<link rel="preload" as="image" href="' . esc_url($img) . '" fetchpriority="high">' . "\n";
	}
}, 2);

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
add_action('send_headers', function () {
	header('X-Content-Type-Options: nosniff');
	header('Referrer-Policy: strict-origin-when-cross-origin');
	header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
});
/* Não expõe a lista de usuários (e os logins dos administradores) para visitantes. */
add_filter('rest_endpoints', function (array $e): array {
	if (!is_user_logged_in()) {
		unset($e['/wp/v2/users'], $e['/wp/v2/users/(?P<id>[\d]+)']);
	}
	return $e;
});
add_filter('wp_sitemaps_add_provider', fn($p, $nome) => $nome === 'users' ? false : $p, 10, 2);
