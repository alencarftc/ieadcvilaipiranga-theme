<?php
/**
 * Dados estruturados (JSON-LD) da igreja: Church, WebSite, WebPage, BreadcrumbList, Event, BlogPosting e FAQPage.
 *
 * Um só grafo por página, sem tipo repetido:
 * - Com o Yoast ativo, o grafo é o dele (`wpseo_schema_graph`): a Organização e a Pessoa (autor) dele saem e entra a igreja
 *   (`Church`, mesmo @id `/#organization`, então as referências dele continuam valendo); WebSite, WebPage, Breadcrumb e
 *   Article dele são ajustados (migalha do tema, BlogPosting, foto do topo, título e descrição iguais aos da página).
 * - Sem o Yoast, `ieadc_schema_base()` monta os mesmos nós e o mesmo ajuste vale.
 * Só usa dados reais de Configurações > IEADC Vila Ipiranga e do conteúdo; nada de nota, avaliação ou número inventado
 * (nunca AggregateRating/Review: não temos dado real).
 */
defined('ABSPATH') || exit;

function ieadc_schema_id_igreja(): string {
	return home_url('/#organization');
}

/** Foto como ImageObject (medidas ajudam o Google a escolher o tamanho). */
function ieadc_schema_imagem(array $dados, string $id = ''): array {
	if (!$dados) {
		return [];
	}
	$img = ['@type' => 'ImageObject'] + ($id !== '' ? ['@id' => $id] : []) + ['url' => $dados['url'], 'contentUrl' => $dados['url']];
	if (!empty($dados['largura']) && !empty($dados['altura'])) {
		$img += ['width' => (int) $dados['largura'], 'height' => (int) $dados['altura']];
	}
	return $img;
}

/** Logo do tema (arquivo do próprio tema) com as medidas reais. */
function ieadc_schema_logo(): array {
	$arq = get_stylesheet_directory() . '/assets/img/logo_cor.webp';
	$med = is_file($arq) ? @getimagesize($arq) : false;
	return ieadc_schema_imagem([
		'url' => get_stylesheet_directory_uri() . '/assets/img/logo_cor.webp',
		'largura' => $med ? (int) $med[0] : 0,
		'altura' => $med ? (int) $med[1] : 0,
	], home_url('/#logo')) + ['caption' => ieadc_seo_nome()];
}

/** Dias e horários dos cultos fixos (um por linha de "Cultos no rodapé"): "Segunda · 20h · Culto de Oração" etc. */
function ieadc_seo_horarios(): array {
	$dias = ['segunda' => 'Monday', 'terça' => 'Tuesday', 'terca' => 'Tuesday', 'quarta' => 'Wednesday', 'quinta' => 'Thursday',
		'sexta' => 'Friday', 'sábado' => 'Saturday', 'sabado' => 'Saturday', 'domingo' => 'Sunday'];
	$saida = [];
	foreach (preg_split('/\R/', (string) ieadc_dado('cultos')) as $linha) {
		// "2º sábado · 19h30 · Ceia do Senhor" não começa por dia da semana: fica só no texto (description)
		if (!preg_match('/^\s*(segunda|terça|terca|quarta|quinta|sexta|s[áa]bado|domingo)(?!\p{L})(.*)$/iu', $linha, $m)
			|| !preg_match_all('/(\d{1,2})h(\d{2})?/u', $m[2], $horas, PREG_SET_ORDER)) {
			continue;
		}
		foreach ($horas as $h) { // "Domingo · 9h30 EBD · 19h culto" tem duas
			$saida[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $dias[mb_strtolower($m[1])] ?? 'Sunday',
				'opens' => sprintf('%02d:%02d', (int) $h[1], (int) ($h[2] ?? 0))]; // sem "closes": a duração do culto não é dado da igreja
		}
	}
	return $saida;
}

/** A igreja: Church (e Organization, para ser publicadora e autora). Só com o que existe em Configurações. */
function ieadc_schema_igreja(): array {
	$e = ieadc_seo_endereco();
	$nome = ieadc_seo_nome();
	$longo = ieadc_dado('nome_longo');
	$cultos = array_filter(array_map(fn($c) => str_replace(' · ', ', ', trim($c)), preg_split('/\R/', (string) ieadc_dado('cultos'))));
	$desc = ($longo ?: $nome) . ($e['bairro'] !== '' ? ', no bairro ' . $e['bairro'] : '') . (stripos($longo, $e['cidade']) === false ? ', em ' . $e['cidade'] : '') . '.'
		. ($cultos ? ' Cultos e encontros: ' . implode('; ', $cultos) . '.' : '');
	$logo = ieadc_schema_logo();
	$foto = ieadc_schema_imagem(ieadc_schema_foto_padrao());
	$no = [
		'@type'         => ['Church', 'Organization'],
		'@id'           => ieadc_schema_id_igreja(),
		'name'          => $nome,
		'alternateName' => $longo && $longo !== $nome ? $longo : '',
		'url'           => home_url('/'),
		'description'   => $desc,
		'slogan'        => ieadc_dado('frase_topo'),
		'logo'          => $logo,
		'image'         => $foto,
		'telephone'     => ieadc_dado('telefone'),
		'email'         => ieadc_dado('email'),
		'address'       => ieadc_schema_endereco(),
		'hasMap'        => ieadc_google_maps_url(),
		'sameAs'        => array_values(array_filter([ieadc_dado('instagram'), ieadc_dado('facebook'), ieadc_dado('youtube')])),
		'openingHoursSpecification' => ieadc_seo_horarios(),
	];
	$lat = ieadc_dado('latitude');
	$lng = ieadc_dado('longitude');
	if ($lat !== '' && $lng !== '') { // opcional: só entra se o Felipe preencher
		$no['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $lat, 'longitude' => (float) $lng];
	}
	// A sede (IEADC) é a organização da qual a congregação faz parte; o nome vem do nome completo ("Congregação da ...").
	$sede = (string) preg_replace('/^Congrega[çc][ãa]o\s+d[aoe]s?\s+/iu', '', (string) $longo);
	if (ieadc_dado('site_sede') !== '' && $sede !== '' && $sede !== $longo) {
		$no['parentOrganization'] = ['@type' => 'Organization', 'name' => $sede, 'alternateName' => 'IEADC', 'url' => ieadc_dado('site_sede')];
	}
	return array_filter($no, fn($v) => $v !== '' && $v !== []);
}

/** PostalAddress só com o que está no endereço de Configurações (sem CEP, que não temos). */
function ieadc_schema_endereco(): array {
	$e = ieadc_seo_endereco();
	return ['@type' => 'PostalAddress', 'streetAddress' => $e['rua'], 'addressLocality' => $e['cidade'], 'addressRegion' => $e['uf'], 'addressCountry' => 'BR'];
}

/** Foto usada como imagem da igreja: a do topo da página inicial. */
function ieadc_schema_foto_padrao(): array {
	$inicio = (int) get_option('page_on_front');
	$p = $inicio ? get_post($inicio) : null;
	return $p ? ieadc_seo_imagem_dados($p) : [];
}

/* ---------- Nós do grafo ---------- */

function ieadc_schema_website(array $no = []): array {
	$no = array_merge($no, [
		'@type' => 'WebSite', '@id' => home_url('/#website'), 'url' => home_url('/'), 'name' => ieadc_seo_nome(),
		'description' => ieadc_dado('frase_topo') ?: (string) get_bloginfo('description'),
		'publisher' => ['@id' => ieadc_schema_id_igreja()], 'inLanguage' => 'pt-BR',
	]);
	unset($no['potentialAction']); // o site não tem busca na tela: não prometer uma ao Google
	return $no;
}

/** Tipos que o Yoast usa para a página (WebPage e variações). */
function ieadc_schema_tipos_pagina(): array {
	return ['WebPage', 'CollectionPage', 'ItemPage', 'AboutPage', 'FAQPage', 'ProfilePage', 'ContactPage', 'SearchResultsPage'];
}

/** Tipo mais específico por página (slug => tipo). */
function ieadc_schema_tipo_da_pagina(?WP_Post $post): string {
	$mapa = ['contato' => 'ContactPage', 'sobre-nos' => 'AboutPage', 'eventos' => 'CollectionPage', 'artigos' => 'CollectionPage', 'galeria' => 'CollectionPage'];
	return $post && $post->post_type === 'page' ? ($mapa[$post->post_name] ?? '') : '';
}

/** Perguntas da página (bloco ieadc/perguntas) no formato do Google; as respostas são o texto que a pessoa lê. */
function ieadc_schema_faq(?WP_Post $post): array {
	$attrs = $post ? ieadc_seo_bloco($post, ['ieadc/perguntas']) : null;
	if ($attrs === null || !function_exists('ieadc_perguntas_itens')) {
		return [];
	}
	$saida = [];
	foreach (ieadc_perguntas_itens($attrs) as $i) {
		$saida[] = ['@type' => 'Question', 'name' => ieadc_seo_limpar($i['pergunta']),
			'acceptedAnswer' => ['@type' => 'Answer', 'text' => ieadc_seo_limpar($i['resposta'])]];
	}
	return $saida;
}

function ieadc_schema_pagina(array $no, ?WP_Post $post, bool $home): array {
	$tipos = (array) $no['@type'];
	if ($post) {
		$no['name'] = ieadc_seo_titulo($post) ?: ($no['name'] ?? '');
		if (($d = ieadc_seo_descricao($post)) !== '') {
			$no['description'] = $d;
		}
		$url = get_permalink($post);
		if (($img = ieadc_schema_imagem(ieadc_seo_imagem_dados($post), $url . '#primaryimage'))) {
			$no['primaryImageOfPage'] = $img;
			$no['thumbnailUrl'] = $img['url'];
		}
	}
	$no['inLanguage'] = 'pt-BR';
	if ($home || ($post && in_array($post->post_name, ['sobre-nos', 'horarios-e-localizacao', 'contato'], true))) {
		$no['about'] = ['@id' => ieadc_schema_id_igreja()];
	}
	if ($especifico = ieadc_schema_tipo_da_pagina($post)) {
		$tipos[] = $especifico;
	}
	if (($faq = ieadc_schema_faq($post))) {
		$tipos[] = 'FAQPage';
		$no['mainEntity'] = $faq;
	}
	$no['@type'] = count($tipos) > 1 ? array_values(array_unique($tipos)) : $tipos[0];
	if ($home) {
		unset($no['breadcrumb']); // a página inicial não tem migalha
	}
	return $no;
}

/** Migalha: a do tema (caminho do topo da página: "Horários › Culto de Ensino"), a do artigo ou a dos eventos. */
function ieadc_schema_migalha(?WP_Post $post): array {
	if (!$post) {
		return [];
	}
	$itens = [['name' => 'Início', 'item' => home_url('/')]];
	if ($post->post_type === 'post') {
		$topo = ieadc_seo_bloco($post, ['ieadc/artigo-topo']) ?? [];
		$itens[] = ['name' => ieadc_seo_limpar((string) ($topo['migalha'] ?? 'Artigos e conteúdo')), 'item' => ieadc_url((string) ($topo['migalhaLink'] ?? 'artigos'))];
	} elseif ($post->post_type === 'evento') {
		$itens[] = ['name' => 'Eventos', 'item' => ieadc_url('eventos')];
	} else {
		foreach ((array) (ieadc_seo_hero($post)['migalha'] ?? []) as $m) {
			$texto = ieadc_seo_limpar((string) ($m['texto'] ?? ''));
			$link = trim((string) ($m['link'] ?? ''));
			if ($texto !== '' && $link !== '') { // item sem link é a própria página: entra no fim
				$itens[] = ['name' => $texto, 'item' => ieadc_url($link)];
			}
		}
	}
	$itens[] = ['name' => ieadc_seo_limpar(get_the_title($post)), 'item' => get_permalink($post)];
	$lista = [];
	foreach (array_values($itens) as $k => $i) {
		$lista[] = ['@type' => 'ListItem', 'position' => $k + 1, 'name' => $i['name'], 'item' => $i['item']];
	}
	return $lista;
}

/** Artigo do blog: o `Article` do Yoast vira BlogPosting, com a igreja como autora e publicadora. */
function ieadc_schema_artigo(array $no, ?WP_Post $post): array {
	if (!$post) {
		return $no;
	}
	$igreja = ['@id' => ieadc_schema_id_igreja()];
	$no['@type'] = 'BlogPosting';
	$no['headline'] = ieadc_seo_cortar(ieadc_seo_limpar(get_the_title($post)), 110);
	$no['description'] = ieadc_seo_descricao($post);
	$no['author'] = $igreja;
	$no['publisher'] = $igreja;
	$no['inLanguage'] = 'pt-BR';
	$no['datePublished'] = get_the_date('c', $post);
	$no['dateModified'] = get_the_modified_date('c', $post);
	if (($img = ieadc_schema_imagem(ieadc_seo_imagem_dados($post)))) {
		$no['image'] = $img;
		$no['thumbnailUrl'] = $img['url'];
	}
	return $no;
}

/** "19h30" → "19:30" (primeiro e segundo horário do texto, ex.: "19h às 21h"). */
function ieadc_schema_horas(string $texto): array {
	preg_match_all('/(\d{1,2})h(\d{2})?/u', $texto, $m, PREG_SET_ORDER);
	return array_map(fn($h) => sprintf('%02d:%02d', min(23, (int) $h[1]), min(59, (int) ($h[2] ?? 0))), array_slice($m, 0, 2));
}

/** Data e hora com o fuso da igreja (-03:00); sem hora, só a data. */
function ieadc_schema_data_hora(string $data, string $hora): string {
	if ($hora === '') {
		return $data;
	}
	$d = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $data . ' ' . $hora, ieadc_agenda_fuso());
	return $d ? $d->format('Y-m-d\TH:i:sP') : $data;
}

/** Eventos cadastrados (tipo Eventos): na página do próprio evento e, os próximos, na página Eventos. */
function ieadc_schema_eventos(?WP_Post $post): array {
	if (!$post || !function_exists('ieadc_agenda_avulsos')) {
		return [];
	}
	$lista = ieadc_agenda_avulsos();
	if ($post->post_type === 'evento') {
		$lista = array_filter($lista, fn($e) => (int) $e['origem'] === (int) $post->ID);
	} elseif ($post->post_type === 'page' && $post->post_name === 'eventos') {
		$hoje = ieadc_hoje()->format('Y-m-d');
		$lista = array_filter($lista, fn($e) => $e['fim'] >= $hoje);
	} else {
		return [];
	}
	$e = ieadc_seo_endereco();
	$igreja = ['@id' => ieadc_schema_id_igreja()];
	$saida = [];
	foreach (array_slice(array_values($lista), 0, 30) as $ev) {
		$p = get_post((int) $ev['origem']);
		if (!$p) {
			continue;
		}
		$url = get_permalink($p);
		$horas = ieadc_schema_horas((string) $ev['hora']);
		$local = ieadc_seo_limpar((string) get_post_meta($p->ID, 'evento_local', true)); // "[local a confirmar]" não vai para o Google
		$fim = isset($horas[1]) ? ieadc_schema_data_hora($ev['fim'], $horas[1]) : ($ev['fim'] > $ev['ini'] ? $ev['fim'] : '');
		$foto = get_the_post_thumbnail_url($p, 'full') ?: (ieadc_seo_imagem_dados($p)['url'] ?? '');
		$saida[] = array_filter([
			'@type' => 'Event',
			'@id' => $url . '#evento',
			'name' => ieadc_seo_limpar((string) $ev['titulo']),
			'description' => ieadc_seo_descricao($p),
			'startDate' => ieadc_schema_data_hora($ev['ini'], $horas[0] ?? ''),
			'endDate' => $fim,
			'eventStatus' => 'https://schema.org/EventScheduled',
			'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
			'location' => $local !== '' ? ['@type' => 'Place', 'name' => $local, 'address' => $local]
				: ['@type' => 'Place', 'name' => ieadc_seo_nome(), 'address' => ieadc_schema_endereco()],
			'image' => $foto,
			'url' => $url,
			'organizer' => $igreja,
			'inLanguage' => 'pt-BR',
		], fn($v) => $v !== '' && $v !== []);
	}
	return $saida;
}

/** Tira as referências {"@id": …} que não apontam para nenhum nó do grafo (ex.: autor vazio) e repete @id. */
function ieadc_schema_limpar(array $grafo): array {
	$unicos = [];
	foreach ($grafo as $no) {
		$id = (string) ($no['@id'] ?? '');
		if ($id === '' || !isset($unicos[$id])) {
			$unicos[$id !== '' ? $id : count($unicos) . '-sem-id'] = $no;
		}
	}
	$grafo = array_values($unicos);
	$ids = [];
	foreach ($grafo as $no) {
		if (!empty($no['@id'])) {
			$ids[$no['@id']] = true;
		}
	}
	$limpa = function ($v) use (&$limpa, $ids) {
		if (!is_array($v)) {
			return $v;
		}
		$r = [];
		foreach ($v as $k => $item) {
			if (is_array($item) && array_keys($item) === ['@id']) {
				if (isset($ids[$item['@id']])) {
					$r[$k] = $item;
				}
				continue;
			}
			$r[$k] = $limpa($item);
		}
		return ($v === [] || array_keys($v) === range(0, count($v) - 1)) ? array_values($r) : $r;
	};
	return $limpa($grafo);
}

/** Ajusta o grafo (do Yoast ou o `ieadc_schema_base()`): igreja no lugar da Organização, migalha, BlogPosting, eventos. */
function ieadc_schema_ajustar(array $grafo): array {
	$post = is_singular() ? get_post() : null;
	$home = is_front_page();
	$saida = [ieadc_schema_igreja()];
	foreach ($grafo as $no) {
		if (!is_array($no)) {
			continue;
		}
		$tipos = (array) ($no['@type'] ?? []);
		$id = (string) ($no['@id'] ?? '');
		if (array_intersect($tipos, ['Organization', 'Person', 'Church']) || str_contains($id, '#/schema/') || ($id !== '' && str_ends_with($id, '#primaryimage'))) {
			continue; // a igreja (acima) substitui a Organização e a Pessoa do Yoast; a foto do topo vai dentro da página
		}
		if (in_array('WebSite', $tipos, true)) {
			$no = ieadc_schema_website($no);
		} elseif (in_array('BreadcrumbList', $tipos, true)) {
			$itens = $home ? [] : ieadc_schema_migalha($post);
			if (!$itens) {
				continue;
			}
			$no['itemListElement'] = $itens;
		} elseif (array_intersect($tipos, ['Article', 'BlogPosting', 'NewsArticle'])) {
			$no = ieadc_schema_artigo($no, $post);
		} elseif (array_intersect($tipos, ieadc_schema_tipos_pagina())) {
			$no = ieadc_schema_pagina($no, $post, $home);
		}
		$saida[] = $no;
	}
	return ieadc_schema_limpar(array_merge($saida, ieadc_schema_eventos($post)));
}

/** Grafo mínimo (sem Yoast), no mesmo formato do Yoast, para o mesmo ajuste valer. */
function ieadc_schema_base(): array {
	$post = is_singular() ? get_post() : null;
	$home = is_front_page();
	$url = $post ? get_permalink($post) : home_url('/');
	$pagina = ['@type' => 'WebPage', '@id' => $url, 'url' => $url, 'name' => $post ? get_the_title($post) : ieadc_seo_nome(),
		'isPartOf' => ['@id' => home_url('/#website')], 'inLanguage' => 'pt-BR'];
	$grafo = [];
	if ($post) {
		$pagina['datePublished'] = get_the_date('c', $post);
		$pagina['dateModified'] = get_the_modified_date('c', $post);
		if (!$home) {
			$pagina['breadcrumb'] = ['@id' => $url . '#breadcrumb'];
			$grafo[] = ['@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => []];
		}
		if ($post->post_type === 'post') {
			$grafo[] = ['@type' => 'Article', '@id' => $url . '#article', 'isPartOf' => ['@id' => $url], 'mainEntityOfPage' => ['@id' => $url]];
		}
	}
	return array_merge([$pagina, ieadc_schema_website()], $grafo);
}

/* Com Yoast: o grafo dele ajustado. Sem Yoast: o nosso, só na página inicial e nas páginas e artigos. */
add_filter('wpseo_schema_graph', fn($grafo) => ieadc_schema_ajustar((array) $grafo), 20);
add_action('wp_head', function () {
	if (ieadc_seo_yoast() || is_admin() || is_feed() || is_404() || (!is_front_page() && !is_singular())) {
		return;
	}
	$dados = ['@context' => 'https://schema.org', '@graph' => ieadc_schema_ajustar(ieadc_schema_base())];
	echo '<script type="application/ld+json">' . wp_json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . "</script>\n";
}, 3);
