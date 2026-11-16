<?php
/**
 * Testes de SEO (fase 2), dentro do WordPress e com pedidos HTTP ao próprio site (serviço "wordpress" do Docker).
 * Rode com ./bin/testar.sh php. Baixa todas as páginas e artigos publicados (mais um evento de teste, apagado no fim) e confere:
 *  - dados estruturados (JSON-LD): JSON válido, @context e @type, campos da igreja, um só Church, WebSite e WebPage por página,
 *    BreadcrumbList nas páginas internas, BlogPosting, Event, FAQPage visível, nenhuma referência solta e nenhuma avaliação inventada;
 *  - metatags: título (até 60), descrição (70 a 160), canonical, lang, Open Graph, Twitter, theme-color, sitemap, robots;
 *  - HTML: um h1, títulos sem salto, imagens com alt, largura e altura;
 *  - robots.txt, sitemap e llms.txt.
 */
require dirname(__DIR__) . '/bootstrap.php';

$base = rtrim(getenv('IEADC_TESTE_URL') ?: 'http://wordpress', '/');
$nome = (string) ieadc_info('nome');

/** Baixa uma página como o site entrega (com a data fixa dos testes). */
function ieadc_t_baixar(string $url): array {
	$r = wp_remote_get($url, ['timeout' => 30, 'redirection' => 0, 'headers' => ['X-Ieadc-Hoje' => '2026-10-06']]);
	if (is_wp_error($r)) {
		return ['status' => 0, 'corpo' => '', 'tipo' => '', 'erro' => $r->get_error_message()];
	}
	return ['status' => (int) wp_remote_retrieve_response_code($r), 'corpo' => (string) wp_remote_retrieve_body($r),
		'tipo' => (string) wp_remote_retrieve_header($r, 'content-type'), 'erro' => ''];
}

/** Monta DOM, XPath e os dados estruturados de uma página. */
function ieadc_t_analisar(string $html): array {
	$dom = new DOMDocument();
	libxml_use_internal_errors(true);
	$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
	libxml_clear_errors();
	$xp = new DOMXPath($dom);
	$nos = [];
	$erros = [];
	$texto_ld = '';
	foreach ($xp->query('//script[@type="application/ld+json"]') as $s) {
		$texto_ld .= $s->textContent;
		$d = json_decode($s->textContent, true);
		if (!is_array($d)) {
			$erros[] = 'JSON-LD inválido: ' . json_last_error_msg();
			continue;
		}
		if (!in_array($d['@context'] ?? '', ['https://schema.org', 'https://schema.org/'], true)) {
			$erros[] = '@context diferente de https://schema.org';
		}
		foreach (isset($d['@graph']) ? $d['@graph'] : [$d] as $no) {
			$nos[] = $no;
		}
	}
	return ['dom' => $dom, 'xp' => $xp, 'nos' => $nos, 'erros_ld' => $erros, 'texto_ld' => $texto_ld, 'html' => $html];
}

function ieadc_t_tipos(array $no): array {
	return array_map('strval', (array) ($no['@type'] ?? []));
}

/** Nós do grafo que têm o tipo. */
function ieadc_t_nos(array $p, string $tipo): array {
	return array_values(array_filter($p['nos'], fn($n) => in_array($tipo, ieadc_t_tipos($n), true)));
}

/** Valor da metatag (name= ou property=). */
function ieadc_t_meta(array $p, string $chave): string {
	foreach (['name', 'property'] as $attr) {
		$r = $p['xp']->query('//meta[@' . $attr . '="' . $chave . '"]/@content');
		if ($r->length) {
			return trim($r->item(0)->nodeValue);
		}
	}
	return '';
}

function ieadc_t_titulo(array $p): string {
	return trim($p['xp']->query('//title')->item(0)->textContent ?? '');
}

/** Texto de cada parágrafo da página (como a pessoa lê: espaços normalizados, sem tags). */
function ieadc_t_paragrafos(array $p): array {
	$r = [];
	foreach ($p['xp']->query('//body//p') as $n) {
		$r[] = trim(preg_replace('/\s+/u', ' ', $n->textContent));
	}
	return $r;
}

/** Todas as referências {"@id": …} (objeto só com @id) de um valor. */
function ieadc_t_refs($v, array &$refs): void {
	if (!is_array($v)) {
		return;
	}
	if (array_keys($v) === ['@id']) {
		$refs[] = $v['@id'];
		return;
	}
	foreach ($v as $x) {
		ieadc_t_refs($x, $refs);
	}
}

/** Roda a verificação em todas as páginas e junta as falhas numa só linha de resultado. */
function ieadc_t_todas(array $paginas, string $nome, callable $fn): void {
	$falhas = [];
	foreach ($paginas as $caminho => $p) {
		$r = isset($p['xp']) ? $fn($caminho, $p) : 'não respondeu 200 (' . ($p['resp']['status'] ?? '?') . ')';
		if ($r !== true) {
			$falhas[] = $caminho . ': ' . $r;
		}
	}
	ieadc_ok(!$falhas, $nome . ' (' . count($paginas) . ' páginas)', implode("\n", array_slice($falhas, 0, 8)));
}

/* ---------- Pedidos ---------- */
echo "site responde\n";
$inicio_resp = ieadc_t_baixar($base . '/');
ieadc_ok($inicio_resp['status'] === 200, 'a página inicial responde 200 em ' . $base, $inicio_resp['erro'] ?: $inicio_resp['status']);
if ($inicio_resp['status'] !== 200) {
	ieadc_teste_fim();
}

/* Evento de teste (apagado no fim, mesmo que algo falhe). */
$evento_id = wp_insert_post(['post_type' => 'evento', 'post_status' => 'publish', 'post_title' => 'Culto de teste de SEO (apagar)', 'post_excerpt' => '',
	'meta_input' => ['evento_data' => '2026-12-05', 'evento_data_fim' => '2026-12-06', 'evento_horario' => '19h30 às 21h', 'evento_local' => '[local a confirmar]']], true);
register_shutdown_function(function () use ($evento_id) {
	if ($evento_id && !is_wp_error($evento_id)) {
		wp_delete_post($evento_id, true);
	}
});
ieadc_ok(!is_wp_error($evento_id), 'evento de teste criado');

$posts = get_posts(['post_type' => ['page', 'post', 'evento'], 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'ID', 'order' => 'ASC']);
$paginas = [];
foreach ($posts as $post) {
	$caminho = (string) wp_parse_url(get_permalink($post), PHP_URL_PATH);
	$r = ieadc_t_baixar($base . $caminho);
	$paginas[$caminho] = ['post' => $post, 'resp' => $r] + ($r['status'] === 200 ? ieadc_t_analisar($r['corpo']) : []);
}
$home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH) ?: '/';
$total = count($paginas);
echo "  $total páginas, artigos e eventos baixados\n";
ieadc_ok($total >= 29, 'páginas, artigos e evento de teste publicados (29 ou mais)', $total);

/* ---------- Metatags ---------- */
echo "metatags\n";
$titulos = [];
$descricoes = [];
ieadc_t_todas($paginas, 'responde 200', fn($c, $p) => $p['resp']['status'] === 200 ?: 'status ' . $p['resp']['status']);
ieadc_t_todas($paginas, '<html lang="pt-BR">', function ($c, $p) {
	$l = $p['xp']->query('/html/@lang')->item(0);
	return ($l && $l->nodeValue === 'pt-BR') ?: 'lang = ' . ($l ? $l->nodeValue : '(vazio)');
});
ieadc_t_todas($paginas, 'título com até 60 caracteres', function ($c, $p) use (&$titulos) {
	$t = ieadc_t_titulo($p);
	$titulos[$t][] = $c;
	return ($t !== '' && mb_strlen($t) <= 60) ?: mb_strlen($t) . ' caracteres: ' . $t;
});
$repetidos = array_filter($titulos, fn($l) => count($l) > 1);
ieadc_ok(!$repetidos, 'títulos únicos', $repetidos);
ieadc_t_todas($paginas, 'descrição de 70 a 160 caracteres, sem "[…]"', function ($c, $p) use (&$descricoes) {
	$d = ieadc_t_meta($p, 'description');
	$descricoes[$d][] = $c;
	return (mb_strlen($d) >= 70 && mb_strlen($d) <= 160 && !str_contains($d, '[')) ?: mb_strlen($d) . ' caracteres: ' . $d;
});
$repetidas = array_filter($descricoes, fn($l) => count($l) > 1);
ieadc_ok(!$repetidas, 'descrições únicas', $repetidas);
ieadc_t_todas($paginas, 'canonical aponta para a própria página', function ($c, $p) {
	$k = $p['xp']->query('//link[@rel="canonical"]/@href');
	return ($k->length === 1 && wp_parse_url($k->item(0)->nodeValue, PHP_URL_PATH) === $c) ?: 'canonical: ' . ($k->length ? $k->item(0)->nodeValue : 'ausente') . ' (' . $k->length . ')';
});
ieadc_t_todas($paginas, 'Open Graph completo (locale, type, title, description, url, site_name, image com medidas)', function ($c, $p) use ($nome) {
	$tipo_esperado = $p['post']->post_type === 'post' ? 'article' : 'website';
	$er = [];
	foreach ([['og:locale', 'pt_BR'], ['og:type', $tipo_esperado], ['og:site_name', $nome], ['og:title', ieadc_t_titulo($p)], ['og:description', ieadc_t_meta($p, 'description')]] as [$k, $v]) {
		if (ieadc_t_meta($p, $k) !== $v) {
			$er[] = $k . ' = ' . ieadc_t_meta($p, $k);
		}
	}
	if (wp_parse_url(ieadc_t_meta($p, 'og:url'), PHP_URL_PATH) !== $c) {
		$er[] = 'og:url = ' . ieadc_t_meta($p, 'og:url');
	}
	if (!preg_match('#^https?://.+\.(webp|jpe?g|png)$#i', ieadc_t_meta($p, 'og:image'))) {
		$er[] = 'og:image = ' . ieadc_t_meta($p, 'og:image');
	}
	foreach (['og:image:width', 'og:image:height'] as $k) {
		if ((int) ieadc_t_meta($p, $k) <= 0) {
			$er[] = $k . ' ausente';
		}
	}
	return $er ? implode('; ', $er) : true;
});
ieadc_t_todas($paginas, 'Twitter Card summary_large_image, theme-color, rel=sitemap', function ($c, $p) {
	$er = [];
	if (ieadc_t_meta($p, 'twitter:card') !== 'summary_large_image') {
		$er[] = 'twitter:card = ' . ieadc_t_meta($p, 'twitter:card');
	}
	if (!preg_match('/^#[0-9a-f]{6}$/i', ieadc_t_meta($p, 'theme-color'))) {
		$er[] = 'theme-color ausente';
	}
	if (!$p['xp']->query('//link[@rel="sitemap"]/@href')->length) {
		$er[] = 'link rel=sitemap ausente';
	}
	return $er ? implode('; ', $er) : true;
});
ieadc_t_todas($paginas, 'robots: indexável com max-image-preview:large (uma só tag)', function ($c, $p) {
	$m = $p['xp']->query('//meta[@name="robots"]/@content');
	$v = $m->length ? $m->item(0)->nodeValue : '';
	return ($m->length === 1 && !str_contains($v, 'noindex') && str_contains($v, 'max-image-preview:large')) ?: 'robots: ' . $v . ' (' . $m->length . ' tag(s))';
});
$busca = ieadc_t_analisar(ieadc_t_baixar($base . '/?s=teste')['corpo']);
ieadc_ok(str_contains(ieadc_t_meta($busca, 'robots'), 'noindex'), 'busca interna com noindex', ieadc_t_meta($busca, 'robots'));

/* ---------- HTML: títulos e imagens ---------- */
echo "títulos e imagens\n";
ieadc_t_todas($paginas, 'um único h1', function ($c, $p) {
	$n = $p['xp']->query('//body//h1')->length;
	return $n === 1 ?: "$n h1";
});
ieadc_t_todas($paginas, 'hierarquia de títulos sem salto', function ($c, $p) {
	$ant = 0;
	foreach ($p['xp']->query('//body//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]') as $h) {
		$n = (int) substr($h->nodeName, 1);
		if ($ant && $n > $ant + 1) {
			return 'h' . $ant . ' → h' . $n . ' em "' . mb_substr(trim($h->textContent), 0, 40) . '"';
		}
		$ant = $n;
	}
	return true;
});
ieadc_t_todas($paginas, 'toda <img> tem alt, width e height; as do rodapé são lazy', function ($c, $p) {
	$er = [];
	foreach ($p['xp']->query('//img') as $img) {
		$src = basename((string) $img->getAttribute('src'));
		foreach (['alt', 'width', 'height'] as $a) {
			if (!$img->hasAttribute($a) || ($a !== 'alt' && (int) $img->getAttribute($a) <= 0)) {
				$er[] = "$src sem $a";
			}
		}
		if ($img->hasAttribute('alt') && $img->getAttribute('alt') === '' && $img->getAttribute('aria-hidden') !== 'true' && $img->getAttribute('role') !== 'presentation') {
			$er[] = "$src com alt vazio mas não decorativa";
		}
		if ($p['xp']->query('ancestor::footer', $img)->length > 0 && $img->getAttribute('loading') !== 'lazy') {
			$er[] = "$src no rodapé sem loading=lazy";
		}
	}
	return $er ? implode('; ', $er) : true;
});
$ini = $paginas[$home_path];
$pre = $ini['xp']->query('//link[@rel="preload"][@as="image"]');
ieadc_ok($pre->length === 1 && $pre->item(0)->getAttribute('fetchpriority') === 'high', 'foto do topo da página inicial pré-carregada com fetchpriority=high');

/* ---------- Dados estruturados ---------- */
echo "dados estruturados (JSON-LD)\n";
$home_url = $base . $home_path;
$igreja_id = $home_url . '#organization';
ieadc_t_todas($paginas, 'JSON-LD presente, JSON válido, @context schema.org', fn($c, $p) => ($p['nos'] && !$p['erros_ld']) ? true : (implode('; ', $p['erros_ld']) ?: 'sem JSON-LD'));
ieadc_t_todas($paginas, 'todo nó tem @type e nenhum @id se repete', function ($c, $p) {
	$ids = [];
	foreach ($p['nos'] as $n) {
		if (empty($n['@type'])) {
			return 'nó sem @type';
		}
		if (!empty($n['@id'])) {
			if (isset($ids[$n['@id']])) {
				return '@id repetido: ' . $n['@id'];
			}
			$ids[$n['@id']] = 1;
		}
	}
	return true;
});
ieadc_t_todas($paginas, 'um só Church, uma só Organization, um só WebSite, um só WebPage, nenhuma Person', function ($c, $p) {
	$er = [];
	foreach (['Church' => 1, 'Organization' => 1, 'WebSite' => 1, 'WebPage' => 1, 'Person' => 0] as $tipo => $esperado) {
		$n = count(ieadc_t_nos($p, $tipo));
		if ($n !== $esperado) {
			$er[] = "$tipo: $n";
		}
	}
	return $er ? implode(', ', $er) : true;
});
ieadc_t_todas($paginas, 'toda referência {"@id"} aponta para um nó do grafo', function ($c, $p) {
	$ids = array_filter(array_column($p['nos'], '@id'));
	$refs = [];
	ieadc_t_refs($p['nos'], $refs);
	$soltas = array_diff($refs, $ids);
	return !$soltas ?: 'sem nó: ' . implode(', ', $soltas);
});
ieadc_t_todas($paginas, 'sem AggregateRating nem Review (não há avaliação real)', fn($c, $p) => !preg_match('/aggregateRating|"Review"|"review"|ratingValue|reviewCount/i', $p['texto_ld']) ?: 'tem marcação de avaliação');
ieadc_t_todas($paginas, 'WebPage com name igual ao título, url e inLanguage pt-BR', function ($c, $p) {
	$w = ieadc_t_nos($p, 'WebPage')[0] ?? [];
	return (($w['name'] ?? '') === ieadc_t_titulo($p) && wp_parse_url($w['url'] ?? '', PHP_URL_PATH) === $c && ($w['inLanguage'] ?? '') === 'pt-BR')
		?: 'WebPage: ' . json_encode([$w['name'] ?? null, $w['url'] ?? null, $w['inLanguage'] ?? null], JSON_UNESCAPED_UNICODE);
});
ieadc_t_todas($paginas, 'WebSite com publisher = igreja e sem SearchAction (não há busca na tela)', function ($c, $p) use ($igreja_id, $nome, $home_url) {
	$w = ieadc_t_nos($p, 'WebSite')[0] ?? [];
	return (($w['@id'] ?? '') === $home_url . '#website' && ($w['publisher']['@id'] ?? '') === $igreja_id && ($w['name'] ?? '') === $nome && !isset($w['potentialAction']))
		?: 'WebSite: ' . json_encode($w, JSON_UNESCAPED_UNICODE);
});

/* A igreja (Church) */
$ig = ieadc_t_nos($ini, 'Church')[0] ?? [];
ieadc_ok(($ig['@id'] ?? '') === $igreja_id, 'Church com @id /#organization (o mesmo da Organização do Yoast)', $ig['@id'] ?? null);
ieadc_igual(['Church', 'Organization'], ieadc_t_tipos($ig), 'Church também é Organization');
ieadc_igual($nome, $ig['name'] ?? '', 'Church: name = nome em Configurações');
ieadc_igual(ieadc_info('nome_longo'), $ig['alternateName'] ?? '', 'Church: alternateName = nome completo');
ieadc_igual($home_url, $ig['url'] ?? '', 'Church: url = página inicial');
ieadc_ok(str_contains($ig['logo']['url'] ?? '', '/logo_cor.webp') && ($ig['logo']['width'] ?? 0) >= 112 && ($ig['logo']['height'] ?? 0) >= 112, 'Church: logo com medidas (mínimo 112 px do Google)', $ig['logo'] ?? null);
ieadc_ok((bool) preg_match('#^https?://.+\.(webp|jpe?g|png)$#', $ig['image']['url'] ?? '') && ($ig['image']['width'] ?? 0) > 0, 'Church: image = foto do topo da página inicial, com medidas', $ig['image'] ?? null);
$e = ieadc_seo_endereco();
ieadc_igual(['@type' => 'PostalAddress', 'streetAddress' => $e['rua'], 'addressLocality' => 'Curitiba', 'addressRegion' => 'PR', 'addressCountry' => 'BR'], $ig['address'] ?? null, 'Church: PostalAddress só com rua, cidade, estado e país (sem CEP)');
ieadc_ok(str_contains($ig['address']['streetAddress'] ?? '', 'Marechal Althayr Roszanniy, 555'), 'Church: rua e número do endereço da igreja');
ieadc_igual(ieadc_info('telefone'), $ig['telephone'] ?? '', 'Church: telephone');
ieadc_igual(ieadc_info('email'), $ig['email'] ?? '', 'Church: email (o público em Configurações)');
ieadc_igual(array_values(array_filter([ieadc_info('instagram'), ieadc_info('facebook'), ieadc_info('youtube')])), $ig['sameAs'] ?? [], 'Church: sameAs = Instagram, Facebook e YouTube');
ieadc_ok(($ig['parentOrganization']['url'] ?? '') === ieadc_info('site_sede'), 'Church: parentOrganization = igreja sede (site da IEADC)', $ig['parentOrganization'] ?? null);
ieadc_ok(str_starts_with($ig['hasMap'] ?? '', 'https://www.google.com/maps/search/?api=1&query='), 'Church: hasMap = igreja no Google Maps');
ieadc_ok(!isset($ig['geo']) || (ieadc_info('latitude') !== '' && ieadc_info('longitude') !== ''), 'Church: sem coordenadas inventadas (geo só se Configurações tiver latitude e longitude)');
$horas = $ig['openingHoursSpecification'] ?? [];
ieadc_igual(6, count($horas), 'Church: 6 horários (seg, qua, qui, sex e dois no domingo); "2º sábado" fica só na descrição');
ieadc_ok(!array_filter($horas, fn($h) => !in_array($h['dayOfWeek'] ?? '', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'], true) || !preg_match('/^\d{2}:\d{2}$/', $h['opens'] ?? '') || isset($h['closes']) || ($h['@type'] ?? '') !== 'OpeningHoursSpecification'), 'Church: horários com dia e hora de início, sem "closes" inventado', $horas);
ieadc_ok(!array_filter($horas, fn($h) => ($h['dayOfWeek'] ?? '') === 'Saturday'), 'Church: nenhum horário no sábado (a Ceia é no 2º sábado, só em description)');
ieadc_ok(str_contains($ig['description'] ?? '', '2º sábado, 19h30, Ceia do Senhor'), 'Church: description cita a Ceia no 2º sábado', $ig['description'] ?? null);
ieadc_ok(!str_contains(json_encode($ig, JSON_UNESCAPED_UNICODE), 'a confirmar'), 'Church: nada de "[a confirmar]"');

/* Página inicial e páginas internas */
$w_ini = ieadc_t_nos($ini, 'WebPage')[0] ?? [];
ieadc_igual($igreja_id, $w_ini['about']['@id'] ?? '', 'página inicial: WebPage.about = a igreja');
ieadc_ok(!ieadc_t_nos($ini, 'BreadcrumbList') && !isset($w_ini['breadcrumb']), 'página inicial: sem BreadcrumbList');
ieadc_ok((bool) preg_match('#^https?://.+\.(webp|jpe?g|png)$#', $w_ini['primaryImageOfPage']['url'] ?? '') && ($w_ini['primaryImageOfPage']['width'] ?? 0) > 0, 'página inicial: primaryImageOfPage com a foto do topo');
ieadc_t_todas(array_diff_key($paginas, [$home_path => 1]), 'BreadcrumbList em página interna: Início primeiro, posições 1..n, a própria página por último', function ($c, $p) use ($home_url, $base) {
	$b = ieadc_t_nos($p, 'BreadcrumbList');
	if (count($b) !== 1) {
		return count($b) . ' BreadcrumbList';
	}
	$itens = $b[0]['itemListElement'] ?? [];
	if (count($itens) < 2 || ($itens[0]['name'] ?? '') !== 'Início' || ($itens[0]['item'] ?? '') !== $home_url) {
		return 'começo: ' . json_encode($itens[0] ?? null, JSON_UNESCAPED_UNICODE);
	}
	foreach ($itens as $k => $i) {
		if (($i['@type'] ?? '') !== 'ListItem' || ($i['position'] ?? 0) !== $k + 1 || ($i['name'] ?? '') === '' || !str_starts_with($i['item'] ?? '', $base)) {
			return 'item ' . ($k + 1) . ': ' . json_encode($i, JSON_UNESCAPED_UNICODE);
		}
	}
	$w = ieadc_t_nos($p, 'WebPage')[0] ?? [];
	$ultimo = end($itens);
	return (wp_parse_url($ultimo['item'], PHP_URL_PATH) === $c && ($w['breadcrumb']['@id'] ?? '') === ($b[0]['@id'] ?? 'x')) ?: 'fim: ' . $ultimo['item'];
});
$culto = $paginas['/culto-de-ensino/'] ?? null;
ieadc_igual(['Início', 'Horários', 'Culto de Ensino'], $culto ? array_column(ieadc_t_nos($culto, 'BreadcrumbList')[0]['itemListElement'] ?? [], 'name') : null, 'migalha do tema: Início > Horários > Culto de Ensino');
$art = $paginas['/alcancar-curitiba/'] ?? null;
ieadc_igual(['Início', 'Artigos e conteúdo', 'Alcançar Curitiba para Cristo'], $art ? array_column(ieadc_t_nos($art, 'BreadcrumbList')[0]['itemListElement'] ?? [], 'name') : null, 'migalha do artigo: Início > Artigos e conteúdo > título');

/* Artigos: BlogPosting */
$blog = array_filter($paginas, fn($p) => $p['post']->post_type === 'post');
ieadc_igual(3, count($blog), 'três artigos publicados');
ieadc_t_todas($blog, 'BlogPosting: headline, datas, imagem, autora e publicadora = a igreja, sem Article', function ($c, $p) use ($igreja_id) {
	$b = ieadc_t_nos($p, 'BlogPosting');
	if (count($b) !== 1 || ieadc_t_nos($p, 'Article')) {
		return count($b) . ' BlogPosting / Article presente';
	}
	$b = $b[0];
	$er = [];
	if (($b['headline'] ?? '') === '' || mb_strlen($b['headline']) > 110) {
		$er[] = 'headline';
	}
	foreach (['datePublished', 'dateModified'] as $k) {
		if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(Z|[+-]\d{2}:\d{2})$/', $b[$k] ?? '')) {
			$er[] = $k;
		}
	}
	if (!preg_match('#^https?://.+\.(webp|jpe?g|png)$#', $b['image']['url'] ?? '')) {
		$er[] = 'image';
	}
	foreach (['author', 'publisher'] as $k) {
		if (($b[$k]['@id'] ?? '') !== $igreja_id) {
			$er[] = $k;
		}
	}
	if (($b['mainEntityOfPage']['@id'] ?? '') === '' || ($b['description'] ?? '') === '') {
		$er[] = 'mainEntityOfPage/description';
	}
	return $er ? implode(', ', $er) : true;
});
$nao_artigos = array_filter($paginas, fn($p) => $p['post']->post_type !== 'post');
ieadc_t_todas($nao_artigos, 'só os artigos têm BlogPosting', fn($c, $p) => (!ieadc_t_nos($p, 'BlogPosting') && !ieadc_t_nos($p, 'Article')) ?: 'BlogPosting fora de artigo');

/* FAQPage visível */
$hor = $paginas['/horarios-e-localizacao/'] ?? null;
$faq = $hor ? (ieadc_t_nos($hor, 'WebPage')[0] ?? []) : [];
ieadc_ok(in_array('FAQPage', ieadc_t_tipos($faq), true) && count($faq['mainEntity'] ?? []) >= 5, 'Horários e localização: WebPage também é FAQPage com 5 ou mais perguntas', ieadc_t_tipos($faq));
$paragrafos = $hor ? ieadc_t_paragrafos($hor) : [];
$perguntas_h3 = [];
if ($hor) {
	foreach ($hor['xp']->query('//body//h3') as $h) {
		$perguntas_h3[] = trim($h->textContent);
	}
}
$invisiveis = [];
foreach ($faq['mainEntity'] ?? [] as $q) {
	if (($q['@type'] ?? '') !== 'Question' || ($q['acceptedAnswer']['@type'] ?? '') !== 'Answer') {
		$invisiveis[] = 'estrutura: ' . json_encode($q, JSON_UNESCAPED_UNICODE);
		continue;
	}
	if (!in_array($q['name'], $perguntas_h3, true)) {
		$invisiveis[] = 'pergunta fora dos títulos da página: ' . $q['name'];
	}
	if (!in_array($q['acceptedAnswer']['text'], $paragrafos, true)) {
		$invisiveis[] = 'resposta não está visível na página: ' . $q['acceptedAnswer']['text'];
	}
}
ieadc_ok(!$invisiveis, 'FAQPage: cada pergunta e cada resposta do JSON-LD está escrita na página (visível)', $invisiveis);
$faq_resp = implode(' ', array_column(array_column($faq['mainEntity'] ?? [], 'acceptedAnswer'), 'text'));
ieadc_ok(str_contains($faq_resp, ieadc_info('endereco')) && str_contains($faq_resp, ieadc_info('telefone')) && str_contains($faq_resp, 'Ceia do Senhor é celebrada todo 2º sábado do mês, às 19h30'), 'FAQPage: respostas com o endereço, o telefone e a Ceia de Configurações (sem dado inventado)');
$outras = array_diff_key($paginas, ['/horarios-e-localizacao/' => 1]);
ieadc_t_todas($outras, 'só Horários e localização é FAQPage', fn($c, $p) => !in_array('FAQPage', ieadc_t_tipos(ieadc_t_nos($p, 'WebPage')[0] ?? []), true) ?: 'FAQPage fora de lugar');

/* Eventos */
echo "eventos\n";
$ev_path = is_wp_error($evento_id) ? '' : (string) wp_parse_url(get_permalink($evento_id), PHP_URL_PATH);
$ev = $paginas[$ev_path] ?? null;
$no_ev = $ev ? (ieadc_t_nos($ev, 'Event')[0] ?? []) : [];
ieadc_ok($ev && count(ieadc_t_nos($ev, 'Event')) === 1, 'página do evento: um Event', $ev_path);
ieadc_igual('Culto de teste de SEO (apagar)', $no_ev['name'] ?? '', 'Event: name');
ieadc_igual('2026-12-05T19:30:00-03:00', $no_ev['startDate'] ?? '', 'Event: startDate com fuso -03:00');
ieadc_igual('2026-12-06T21:00:00-03:00', $no_ev['endDate'] ?? '', 'Event: endDate com fuso -03:00 (dia final e segundo horário)');
ieadc_igual('https://schema.org/EventScheduled', $no_ev['eventStatus'] ?? '', 'Event: eventStatus');
ieadc_igual('https://schema.org/OfflineEventAttendanceMode', $no_ev['eventAttendanceMode'] ?? '', 'Event: presencial');
ieadc_ok(($no_ev['location']['@type'] ?? '') === 'Place' && ($no_ev['location']['name'] ?? '') === $nome && ($no_ev['location']['address']['addressLocality'] ?? '') === 'Curitiba', 'Event: location = a igreja (Place com o endereço), já que o evento não tem local próprio', $no_ev['location'] ?? null);
ieadc_igual($igreja_id, $no_ev['organizer']['@id'] ?? '', 'Event: organizer = a igreja');
ieadc_ok(!str_contains(json_encode($no_ev, JSON_UNESCAPED_UNICODE), '['), 'Event: "[local a confirmar]" do cadastro não vai para o Google (o local cai na igreja)');
ieadc_ok(wp_parse_url($no_ev['url'] ?? '', PHP_URL_PATH) === $ev_path && preg_match('#^https?://#', $no_ev['image'] ?? ''), 'Event: url da página e imagem');
ieadc_ok(str_contains($no_ev['description'] ?? '', 'das 19h30 às 21h') && !str_contains($no_ev['description'] ?? '', 'às 19h30 às'), 'Event: descrição montada com data e horário', $no_ev['description'] ?? null);
ieadc_igual(['Início', 'Eventos', 'Culto de teste de SEO (apagar)'], $ev ? array_column(ieadc_t_nos($ev, 'BreadcrumbList')[0]['itemListElement'] ?? [], 'name') : null, 'página do evento: migalha Início > Eventos > título');
$lista = $paginas['/eventos/'] ?? null;
$nomes_lista = $lista ? array_column(ieadc_t_nos($lista, 'Event'), 'name') : [];
ieadc_ok(in_array('Culto de teste de SEO (apagar)', $nomes_lista, true), 'página Eventos: lista os próximos eventos como Event', $nomes_lista);
ieadc_ok($lista && in_array('CollectionPage', ieadc_t_tipos(ieadc_t_nos($lista, 'WebPage')[0] ?? []), true), 'página Eventos: WebPage também é CollectionPage');
$sem_evento = array_filter($paginas, fn($p, $c) => $p['post']->post_type !== 'evento' && $c !== '/eventos/', ARRAY_FILTER_USE_BOTH); // o testar.sh também cria o evento do protótipo
ieadc_t_todas($sem_evento, 'Event só nas páginas de evento e na página Eventos', fn($c, $p) => !ieadc_t_nos($p, 'Event') ?: 'Event fora de lugar');

/* ---------- Sem Yoast: o mesmo grafo pelo fallback do tema ---------- */
echo "grafo sem Yoast (fallback do tema)\n";
foreach ([['inicio', 'page', 1], ['alcancar-curitiba', 'post', 3]] as [$slug, $tipo, $nitens]) {
	$post = get_page_by_path($slug, OBJECT, $tipo);
	$GLOBALS['wp_query'] = new WP_Query($tipo === 'page' ? ['page_id' => $post->ID] : ['p' => $post->ID]);
	$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
	$GLOBALS['post'] = $post;
	setup_postdata($post);
	$g = ieadc_schema_ajustar(ieadc_schema_base());
	$p = ['nos' => $g];
	$ok = count(ieadc_t_nos($p, 'Church')) === 1 && count(ieadc_t_nos($p, 'WebSite')) === 1 && count(ieadc_t_nos($p, 'WebPage')) === 1;
	ieadc_ok($ok, "$slug: um Church, um WebSite e um WebPage", array_map('ieadc_t_tipos', $g));
	ieadc_ok(json_decode(wp_json_encode(['@context' => 'https://schema.org', '@graph' => $g]), true) !== null, "$slug: JSON válido");
	if ($tipo === 'post') {
		$b = ieadc_t_nos($p, 'BlogPosting')[0] ?? [];
		ieadc_ok(($b['author']['@id'] ?? '') === home_url('/#organization') && ($b['headline'] ?? '') !== '' && !empty($b['datePublished']), 'artigo sem Yoast: BlogPosting com autora = igreja');
		ieadc_igual($nitens, count(ieadc_t_nos($p, 'BreadcrumbList')[0]['itemListElement'] ?? []), 'artigo sem Yoast: migalha com 3 itens');
	} else {
		ieadc_ok(!ieadc_t_nos($p, 'BreadcrumbList'), 'página inicial sem Yoast: sem BreadcrumbList');
	}
}
wp_reset_query();

/* ---------- Avalie no Google (bloco e campo de Configurações) ---------- */
echo "avalie a igreja no Google
";
ieadc_igual('https://search.google.com/local/writereview?placeid=ChIJN1t_tDeuEmsRUsoyG83frY4', ieadc_google_avaliacao_limpar('ChIJN1t_tDeuEmsRUsoyG83frY4'), 'campo aceita só o código do lugar (ChIJ…)');
ieadc_igual('https://g.page/r/abc123/review', ieadc_google_avaliacao_limpar(' https://g.page/r/abc123/review '), 'campo aceita o link do Perfil da Empresa');
ieadc_igual('https://search.google.com/local/writereview?placeid=ChIJxyz', ieadc_google_avaliacao_limpar('https://search.google.com/local/writereview?placeid=ChIJxyz'), 'campo aceita o link writereview?placeid=');
ieadc_igual('', ieadc_google_avaliacao_limpar('https://evil.example/x'), 'campo recusa domínio que não é do Google');
ieadc_igual('', ieadc_google_avaliacao_limpar('//evil.example/x'), 'campo recusa endereço sem protocolo');
ieadc_igual('', ieadc_google_avaliacao_limpar('https://google.com.evil.example/x'), 'campo recusa domínio que só começa com google.com');
ieadc_igual('https://maps.app.goo.gl/abc', ieadc_google_avaliacao_limpar('https://maps.app.goo.gl/abc'), 'campo aceita o encurtador do Maps');
ieadc_igual('', ieadc_google_avaliacao_limpar('javascript:alert(1)'), 'campo recusa javascript:');
ieadc_igual('', ieadc_google_avaliacao_limpar('http://exemplo.com/sem-https'), 'campo só aceita https');
ieadc_igual('-25.4999', ieadc_coordenada_limpar('-25,4999', 90), 'latitude aceita vírgula');
ieadc_igual(['', ''], [ieadc_coordenada_limpar('95', 90), ieadc_coordenada_limpar('abc', 180)], 'coordenada fora do limite ou não numérica some');
$mapa = ieadc_google_maps_url();
ieadc_ok(str_starts_with($mapa, 'https://www.google.com/maps/search/?api=1&query=') && str_contains($mapa, rawurlencode(ieadc_info('nome') . ', ' . ieadc_info('endereco'))), 'sem link próprio, o botão leva à igreja no Google Maps (nome e endereço codificados)', $mapa);
$html = do_blocks('<!-- wp:ieadc/avaliacao-google /-->');
ieadc_ok(str_contains($html, 'href="' . esc_url($mapa) . '"') && str_contains($html, 'target="_blank"') && str_contains($html, 'rel="noopener"'), 'bloco: botão abre em nova aba com rel="noopener" (sem campo preenchido, vai ao Maps)');
ieadc_ok(substr_count($html, 'class="estrela"') === 5 && str_contains($html, '<div class="estrelas" aria-hidden="true">') && str_contains($html, '>Avaliar no Google<'), 'bloco: 5 estrelas decorativas e botão com texto visível "Avaliar no Google"');
ieadc_ok(!preg_match('/\d+([,.]\d+)?\s*(estrelas|avalia)|nota\s+\d/iu', wp_strip_all_tags($html)), 'bloco: sem nota nem número de avaliações no texto', wp_strip_all_tags($html));
$troca = fn($d) => array_merge($d, ['google_avaliacao' => 'https://g.page/r/abc123/review']);
add_filter('ieadc_info', $troca);
$html = do_blocks('<!-- wp:ieadc/avaliacao-google /-->');
remove_filter('ieadc_info', $troca);
ieadc_ok(str_contains($html, 'href="https://g.page/r/abc123/review"') && str_contains($html, 'aria-label="Avaliar no Google (abre em nova aba)"'), 'bloco: com o campo preenchido o botão usa o link de avaliação');

/* ---------- robots.txt, sitemap e llms.txt ---------- */
echo "robots.txt, sitemap e llms.txt\n";
$rob = ieadc_t_baixar($base . '/robots.txt');
ieadc_ok($rob['status'] === 200 && preg_match('/^Sitemap:\s*https?:\/\/\S+sitemap\S*\.xml/mi', $rob['corpo']), 'robots.txt responde e aponta o Sitemap', $rob['corpo']);
ieadc_ok(!preg_match('/^Disallow:\s*\/(wp-content|wp-includes)|^Disallow:.*\.(css|js)\b/mi', $rob['corpo']), 'robots.txt não bloqueia CSS, JS nem wp-content');
$sm = ieadc_t_baixar($base . '/sitemap_index.xml');
ieadc_ok($sm['status'] === 200 && str_contains($sm['corpo'], 'page-sitemap.xml') && str_contains($sm['corpo'], 'post-sitemap.xml'), 'sitemap_index.xml responde com páginas e artigos', $sm['status']);
$pg = ieadc_t_baixar($base . '/page-sitemap.xml');
$faltam = [];
foreach ($paginas as $c => $p) {
	if ($p['post']->post_type === 'page' && !str_contains($pg['corpo'], $c)) {
		$faltam[] = $c;
	}
}
ieadc_ok(!$faltam, 'todas as páginas estão no page-sitemap.xml', $faltam);
$llms = ieadc_t_baixar($base . '/llms.txt');
ieadc_ok($llms['status'] === 200 && str_starts_with($llms['tipo'], 'text/plain') && str_starts_with($llms['corpo'], '# ' . $nome), 'llms.txt responde 200, text/plain, começando pelo nome da igreja', [$llms['status'], $llms['tipo']]);
$ok = true;
foreach ([ieadc_info('endereco'), ieadc_info('telefone'), ieadc_info('email'), ieadc_info('instagram'), 'Segunda · 20h · Culto de Oração', '2º sábado · 19h30 · Ceia do Senhor'] as $dado) {
	$ok = $ok && str_contains($llms['corpo'], $dado);
}
ieadc_ok($ok, 'llms.txt: endereço, telefone, e-mail, redes e cultos iguais aos de Configurações');
ieadc_ok(!preg_match('/a confirmar|a definir|aggregateRating|nota |avalia[çc][õo]es/i', $llms['corpo']) && strlen($llms['corpo']) < 6000, 'llms.txt: curto, sem pendência e sem nota ou número de avaliações', strlen($llms['corpo']));

ieadc_teste_fim();
