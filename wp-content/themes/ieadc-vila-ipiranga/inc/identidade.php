<?php
/**
 * Identidade do site no navegador: favicon, ícone para a tela inicial do iPhone e webmanifest.
 * Sem isto o navegador pede /favicon.ico, o servidor responde 404 e o erro aparece no console (e derruba o Lighthouse).
 * Os PNG saem do emblema do logo (scripts/gerar-icones.py) e ficam em assets/img/icones/.
 */
defined('ABSPATH') || exit;

/** Endereço de um ícone do tema. */
function ieadc_icone_url(int $tamanho): string {
	return get_stylesheet_directory_uri() . '/assets/img/icones/icone-' . $tamanho . '.png';
}

/* Se a Comunicação definir um "Ícone do site" no painel (Aparência > Personalizar), o WordPress imprime os links dele e usamos só o manifesto. */
add_action('wp_head', function () {
	if (!has_site_icon()) {
		echo '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url(ieadc_icone_url(32)) . '">' . "\n";
		echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url(ieadc_icone_url(180)) . '">' . "\n";
	}
	echo '<link rel="manifest" href="' . esc_url(home_url('/site.webmanifest')) . '">' . "\n";
}, 3);

/** Conteúdo do webmanifest (nome vem de Configurações; cor igual ao theme-color das metatags). */
function ieadc_webmanifest(): array {
	$nome = (string) ieadc_info('nome');
	$raiz = wp_parse_url(home_url('/'), PHP_URL_PATH) ?: '/';
	return [
		'name'             => $nome,
		'short_name'       => $nome,
		'description'      => (string) ieadc_info('frase_topo'),
		'lang'             => 'pt-BR',
		'start_url'        => $raiz,
		'scope'            => $raiz,
		'display'          => 'standalone',
		'theme_color'      => '#071946',
		'background_color' => '#FFFFFF',
		'icons'            => [
			['src' => ieadc_icone_url(192), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
			['src' => ieadc_icone_url(512), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
		],
	];
}

/* /favicon.ico (o WordPress, sem ícone definido, redireciona para o dele) e /site.webmanifest, sem arquivo físico. */
add_action('parse_request', function ($wp) {
	$pedido = $wp->request ?? '';
	if ($pedido === 'favicon.ico') {
		$arq = get_stylesheet_directory() . '/assets/img/icones/icone-32.png';
		if (!is_file($arq)) {
			return;
		}
		status_header(200);
		header('Content-Type: image/png');
		header('X-Content-Type-Options: nosniff');
		header('Cache-Control: public, max-age=604800');
		header('Content-Length: ' . filesize($arq));
		readfile($arq);
		exit;
	}
	if ($pedido === 'site.webmanifest') {
		status_header(200);
		header('Content-Type: application/manifest+json; charset=utf-8');
		header('X-Content-Type-Options: nosniff');
		header('Cache-Control: public, max-age=86400');
		echo wp_json_encode(ieadc_webmanifest(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
		exit;
	}
});
