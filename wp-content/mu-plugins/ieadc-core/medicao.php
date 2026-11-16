<?php
/**
 * Medição de acessos e verificação de propriedade (fase 4). Tudo vem de campos em Configurações > IEADC Vila Ipiranga,
 * VAZIOS por padrão: sem ID, nada é impresso, nenhum script é carregado e a política de conteúdo (CSP) não muda.
 *
 * Serviços (um campo cada; o ID é validado aqui e de novo na hora de usar):
 *   gtm_id          Google Tag Manager, GTM-XXXXXXX. Se estiver preenchido, vale só ele (o GA4 é configurado dentro do GTM; os dois juntos contariam a visita duas vezes).
 *   ga4_id          Google Analytics 4 pelo gtag.js, G-XXXXXXXXXX (usado só quando não há gtm_id).
 *   clarity_id      Microsoft Clarity (mapas de calor e gravações anônimas).
 *   meta_pixel_id   Meta Pixel (Facebook e Instagram).
 *   search_console  código de verificação do Google Search Console (vira <meta name="google-site-verification">).
 *   bing_verificacao  código do Bing Webmaster Tools (vira <meta name="msvalidate.01">).
 * Os quatro primeiros só carregam DEPOIS que a pessoa aceita o aviso de cookies (tema, inc/medicao.php e assets/js/medicao.js).
 * A verificação do Google e do Bing não usa cookie nem script: a meta tag é impressa sempre que o campo existe.
 */
defined('ABSPATH') || exit;

/** Valida um ID de serviço. Devolve o ID limpo ou '' se o formato não for o do serviço. */
function ieadc_medicao_id_limpar(string $servico, string $valor): string {
	$valor = trim($valor);
	$padroes = [
		'gtm'     => '/^GTM-[A-Z0-9]{4,12}$/',
		'ga4'     => '/^G-[A-Z0-9]{6,12}$/',
		'clarity' => '/^[a-z0-9]{6,12}$/',
		'pixel'   => '/^[0-9]{10,20}$/',
	];
	if (!isset($padroes[$servico])) {
		return '';
	}
	$valor = in_array($servico, ['gtm', 'ga4'], true) ? strtoupper($valor) : $valor;
	return preg_match($padroes[$servico], $valor) ? $valor : '';
}

/**
 * Código de verificação: aceita só o código, o texto do registro TXT (google-site-verification=CODIGO) ou a meta tag inteira copiada do Google/Bing (extrai o content="...").
 * Google: letras, números, _ e -, de 20 a 100 caracteres. Bing: 32 caracteres hexadecimais.
 */
function ieadc_medicao_verificacao_limpar(string $tipo, string $valor): string {
	$valor = trim($valor);
	if (preg_match('/content\s*=\s*["\']([^"\']+)["\']/i', $valor, $m)) {
		$valor = trim($m[1]);
	}
	// Registro TXT do DNS do Google: "google-site-verification=CODIGO". Para a <meta> vale só o código depois do "=".
	if ($tipo !== 'bing' && stripos($valor, 'google-site-verification=') === 0) {
		$valor = trim(substr($valor, strlen('google-site-verification=')), " \t\"'");
	}
	if ($tipo === 'bing') {
		return preg_match('/^[A-Fa-f0-9]{32}$/', $valor) ? strtoupper($valor) : '';
	}
	return preg_match('/^[A-Za-z0-9_-]{20,100}$/', $valor) ? $valor : '';
}

/**
 * Serviços de medição que precisam de consentimento e estão ligados: ['gtm' => 'GTM-…'] ou ['ga4' => 'G-…'] (nunca os dois),
 * mais 'clarity' e 'pixel' quando houver. Vazio = o site não mede nada e não mostra aviso de cookies.
 */
function ieadc_medicao_servicos(): array {
	$s = [];
	$gtm = ieadc_medicao_id_limpar('gtm', (string) ieadc_info('gtm_id'));
	$ga4 = ieadc_medicao_id_limpar('ga4', (string) ieadc_info('ga4_id'));
	if ($gtm !== '') {
		$s['gtm'] = $gtm;
	} elseif ($ga4 !== '') {
		$s['ga4'] = $ga4;
	}
	$clarity = ieadc_medicao_id_limpar('clarity', (string) ieadc_info('clarity_id'));
	if ($clarity !== '') {
		$s['clarity'] = $clarity;
	}
	$pixel = ieadc_medicao_id_limpar('pixel', (string) ieadc_info('meta_pixel_id'));
	if ($pixel !== '') {
		$s['pixel'] = $pixel;
	}
	return $s;
}

/** Algum serviço de medição está ligado (e portanto o aviso de cookies existe)? */
function ieadc_medicao_ativa(): bool {
	return ieadc_medicao_servicos() !== [];
}

/* Meta tags de verificação (Google Search Console e Bing Webmaster): sem cookie nem script, impressas sempre que o campo existe. */
add_action('wp_head', function () {
	$g = ieadc_medicao_verificacao_limpar('google', (string) ieadc_info('search_console'));
	if ($g !== '') {
		echo '<meta name="google-site-verification" content="' . esc_attr($g) . '">' . "\n";
	}
	$b = ieadc_medicao_verificacao_limpar('bing', (string) ieadc_info('bing_verificacao'));
	if ($b !== '') {
		echo '<meta name="msvalidate.01" content="' . esc_attr($b) . '">' . "\n";
	}
}, 2);

/**
 * Política de conteúdo (CSP): libera os endereços de cada serviço SOMENTE se o ID existir. Sem ID, a política fica igual à da fase 3.
 * Entra no filtro `ieadc_csp_diretivas` de seguranca.php. Quem acrescentar outras tags dentro do GTM precisa liberar os endereços delas
 * com o mesmo filtro (ver docs/operacao/rastreamento.md).
 */
add_filter('ieadc_csp_diretivas', function (array $d): array {
	$s = ieadc_medicao_servicos();
	if (!$s) {
		return $d;
	}
	$extra = ['script-src' => [], 'connect-src' => [], 'frame-src' => []];
	if (isset($s['gtm']) || isset($s['ga4'])) {
		$extra['script-src'][]  = 'https://www.googletagmanager.com';
		$extra['connect-src'] = array_merge($extra['connect-src'], [
			'https://www.googletagmanager.com', 'https://www.google-analytics.com', 'https://analytics.google.com',
			'https://*.google-analytics.com', 'https://*.analytics.google.com', 'https://stats.g.doubleclick.net',
		]);
	}
	if (isset($s['gtm'])) {
		$extra['frame-src'][] = 'https://www.googletagmanager.com'; // modo de visualização (Preview) do GTM
	}
	if (isset($s['clarity'])) {
		$extra['script-src'] = array_merge($extra['script-src'], ['https://www.clarity.ms', 'https://scripts.clarity.ms']);
		$extra['connect-src'] = array_merge($extra['connect-src'], ['https://*.clarity.ms', 'https://c.clarity.ms', 'https://c.bing.com']);
	}
	if (isset($s['pixel'])) {
		$extra['script-src'][]  = 'https://connect.facebook.net';
		$extra['connect-src'] = array_merge($extra['connect-src'], ['https://www.facebook.com', 'https://connect.facebook.net']);
	}
	foreach ($d as $i => $linha) {
		foreach ($extra as $nome => $fontes) {
			if (!$fontes || !str_starts_with($linha, $nome . ' ')) {
				continue;
			}
			$base = $nome === 'frame-src' && $linha === "frame-src 'none'" ? '' : substr($linha, strlen($nome) + 1);
			$d[$i] = $nome . ' ' . trim($base . ' ' . implode(' ', array_unique($fontes)));
		}
	}
	return $d;
});
