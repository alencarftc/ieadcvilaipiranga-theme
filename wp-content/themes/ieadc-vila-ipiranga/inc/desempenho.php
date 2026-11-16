<?php
/**
 * Desempenho do front (fase 3): foto do topo (LCP) em tamanho certo, CSS em um arquivo, folhas sem uso fora e cache de arquivos estáticos.
 * Nada aqui muda o HTML dos blocos (o teste de markup segue igual ao do protótipo): as trocas vêm por <link>/<style> no <head>.
 */
defined('ABSPATH') || exit;

/** Largura (px) até a qual o celular recebe a foto reduzida do topo (768 px de largura é o "medium_large" do WordPress). */
const IEADC_LARGURA_CELULAR = 768;

/**
 * Foto do topo (a que o Lighthouse mede como LCP; nos artigos, a capa): no celular (até 768 px) vai a versão de 768 px (~65 KB em vez de ~185 KB);
 * no resto, a original. O HTML do bloco aponta para a original (style inline): um <style> no cabeçalho troca o fundo no celular (o seletor casa o
 * nome do arquivo no atributo style). O <link rel="preload"> só vale para o computador (`media`): o motivo está no corpo da função.
 */
add_action('wp_head', function () {
	if (!is_singular()) {
		return;
	}
	$hero = ieadc_seo_hero();
	$id   = (int) ($hero['foto'] ?? 0);
	$artigo = false;
	if ($hero && ($hero['variante'] ?? 'pagina') === 'sem-foto') {
		return;
	}
	if (!$id && ieadc_seo_bloco(null, ['ieadc/artigo-topo']) !== null) { // artigo: a capa (imagem destacada) é a maior imagem do topo
		$id = (int) get_post_thumbnail_id();
		$artigo = true;
	}
	if (!$id) {
		return;
	}
	$cheia = wp_get_attachment_image_src($id, 'full');
	$media = wp_get_attachment_image_src($id, 'medium_large');
	if (!$cheia) {
		return;
	}
	$pequena = $media && $media[0] !== $cheia[0] && $media[1] <= IEADC_LARGURA_CELULAR + 64 && $media[1] >= 640;
	if (!$pequena) {
		if (!$artigo) {
			echo '<link rel="preload" as="image" href="' . esc_url($cheia[0]) . '" fetchpriority="high">' . "
";
		}
		return;
	}
	$l = IEADC_LARGURA_CELULAR;
	// Sem preload no celular: medido no Lighthouse (simulação de 4G lenta), o preload com prioridade alta da versão de 768 px disputa a banda com o CSS
	// e piora o LCP (início 97 para 98, horários 96 para 98 e artigo 94 para 98 ao tirá-lo). A foto é descoberta logo no começo do corpo e o <style> abaixo
	// já aponta a versão certa. No computador o preload da original (que o computador mede em 100) fica; no artigo, a capa fica mais abaixo: sem preload.
	if (!$artigo) {
		echo '<link rel="preload" as="image" href="' . esc_url($cheia[0]) . '" media="(min-width:' . ($l + 1) . 'px)" fetchpriority="high">' . "
";
	}
	$arq = basename((string) wp_parse_url($cheia[0], PHP_URL_PATH));
	echo '<style id="ieadc-topo-celular">@media (max-width:' . $l . 'px){.ieadc-pagina>section:first-child [style*="' . esc_attr($arq) . '"]{background-image:url(' . esc_url($media[0]) . ')!important}}</style>' . "
";
}, 2);

/**
 * Páginas e artigos do site usam o modelo "larga" (cabeçalho, rodapé e blocos do protótipo): o CSS e o JavaScript do Blocksy, o
 * CSS do editor de blocos e os estilos globais do WordPress não são usados nelas (o Lighthouse mostrava 16 KB de CSS sem uso e 740 ms de
 * bloqueio de renderização). Nas outras telas (busca, 404, arquivos) tudo continua carregando. Confirmado com ./bin/testar.sh visual (0,000%).
 */
/** A página atual usa o modelo do protótipo (todas as 28 páginas e os artigos)? */
function ieadc_pagina_do_prototipo(): bool {
	return is_singular() && !is_customize_preview() && is_page_template('page-templates/larga.php');
}

/* Estilos globais, do editor de blocos e "clássicos" do WordPress (WP 6.9+ os joga no <head> por um buffer de saída, com placeholders): fora. */
add_action('wp', function () {
	if (!ieadc_pagina_do_prototipo()) {
		return;
	}
	remove_action('wp_enqueue_scripts', 'wp_enqueue_global_styles');
	remove_action('wp_footer', 'wp_enqueue_global_styles', 1);
	remove_action('wp_enqueue_scripts', 'wp_enqueue_stored_styles');
	remove_action('wp_footer', 'wp_enqueue_stored_styles', 1);
	remove_action('wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles');
	remove_action('wp_template_enhancement_output_buffer_started', 'wp_hoist_late_printed_styles');
});

/* Folhas e script do Blocksy e do bloco do core que sobram (a fila é montada depois de wp_enqueue_scripts; por isso também na impressão). */
$ieadc_sem_blocksy = function () {
	if (!ieadc_pagina_do_prototipo()) {
		return;
	}
	foreach (['ct-main-styles-inline', 'ct-page-title-styles', 'wp-block-library', 'wp-block-library-theme', 'global-styles', 'classic-theme-styles', 'wp-img-auto-sizes-contain', 'wp-block-styles-placeholder', 'wp-global-styles-placeholder'] as $h) {
		wp_dequeue_style($h);
	}
	wp_dequeue_script('ct-scripts');
};
add_action('wp_enqueue_scripts', $ieadc_sem_blocksy, 100);
add_action('wp_print_styles', $ieadc_sem_blocksy, 100);
add_action('wp_print_scripts', $ieadc_sem_blocksy, 100);

/* O CSS dinâmico do Blocksy (14 KB, configuração do cabeçalho dele, que não usamos) é impresso direto no <head>, fora das filas de estilos:
   corta-se o trecho na saída do wp_head, só nas páginas do protótipo. */
add_action('wp_head', function () {
	if (ieadc_pagina_do_prototipo()) {
		ob_start(function (string $html): string {
			return (string) preg_replace([
				'#<style id="ct-main-styles-inline-css">.*?</style>\s*#s',
				"#<noscript><link rel='stylesheet' href='[^']*/blocksy/static/bundle/no-scripts\\.min\\.css'[^>]*></noscript>\\s*#",
			], '', $html);
		});
		add_action('wp_head', function () {
			if (ob_get_level() > 0) {
				ob_end_flush();
			}
		}, 9999);
	}
}, 0);

/* ---------- CSS do tema em um arquivo só, minificado ---------- */
/**
 * Minifica CSS sem mexer em strings nem em url(...): tira comentários e espaços que não fazem diferença. Não reordena nem junta regras.
 */
function ieadc_css_minificar(string $css): string {
	$original = $css;
	$css = preg_replace('#/\*.*?\*/#s', '', $css);
	$guardados = [];
	// strings "..." e '...' e url(...) ficam intactas (a regex vem de um nowdoc: sem escapes de PHP)
	$padrao = <<<'REGEX'
#url\(\s*(?:"[^"\\]*(?:\\.[^"\\]*)*"|'[^'\\]*(?:\\.[^'\\]*)*'|[^)"']*)\s*\)|"[^"\\]*(?:\\.[^"\\]*)*"|'[^'\\]*(?:\\.[^'\\]*)*'#
REGEX;
	$css = preg_replace_callback(
		$padrao,
		function (array $m) use (&$guardados): string {
			$guardados[] = $m[0];
			return "\x01" . (count($guardados) - 1) . "\x02";
		},
		(string) $css
	);
	$css = preg_replace('#\s+#', ' ', (string) $css);
	$css = preg_replace('#\s*([{};,>])\s*#', '$1', (string) $css);
	$css = preg_replace('#:\s+#', ':', (string) $css);
	$css = str_replace(';}', '}', trim((string) $css));
	$css = preg_replace_callback("#\x01(\d+)\x02#", fn(array $m): string => $guardados[(int) $m[1]], $css);
	// Qualquer erro de regex (preg_* devolve null): fica o CSS original, só sem comentários extras. Nunca devolve vazio.
	return is_string($css) && $css !== '' ? $css : $original;
}

/**
 * Endereço do CSS do tema (fontes + protótipo + ajustes) já minificado e em um arquivo só (1 pedido em vez de 3), ou '' se não der para gravar.
 * O arquivo nasce na primeira visita em uploads/ieadc-cache/tema-<hash>.css; o hash muda quando qualquer um dos três muda (tamanho e data),
 * então o navegador baixa o novo e o cache longo é seguro. Os arquivos originais (inclusive o prototipo.css congelado) não são alterados.
 * As fontes (url(../fonts/...)) ganham o endereço completo do tema.
 */
function ieadc_css_pacote(): string {
	static $url = null;
	if ($url !== null) {
		return $url;
	}
	$url = '';
	$dir = get_stylesheet_directory() . '/assets/css/';
	$arquivos = ['fontes.css', 'prototipo.css', 'ajustes.css'];
	$marca = '';
	foreach ($arquivos as $a) {
		if (!is_file($dir . $a)) {
			return $url;
		}
		$marca .= $a . filesize($dir . $a) . '.' . filemtime($dir . $a) . '|';
	}
	$hash = substr(md5($marca . get_stylesheet_directory_uri()), 0, 12);
	$up = wp_upload_dir(null, false);
	if (!empty($up['error'])) {
		return $url;
	}
	$pasta = $up['basedir'] . '/ieadc-cache';
	$arq = "$pasta/tema-$hash.css";
	if (!is_file($arq)) {
		if (!wp_mkdir_p($pasta)) {
			return $url;
		}
		$fontes = get_stylesheet_directory_uri() . '/assets/fonts/';
		$css = '';
		foreach ($arquivos as $a) {
			$css .= str_replace('url(../fonts/', 'url(' . $fontes, (string) file_get_contents($dir . $a)) . "\n";
		}
		$css = ieadc_css_minificar($css);
		$tmp = $arq . '.' . uniqid('', true) . '.tmp';
		if (@file_put_contents($tmp, $css) === false || !@rename($tmp, $arq)) {
			@unlink($tmp);
			return $url;
		}
		foreach ((array) glob($pasta . '/tema-*.css') as $velho) { // versões antigas
			if ($velho !== $arq && @filemtime($velho) < time() - 7 * DAY_IN_SECONDS) { // o HTML em cache pode apontar para a versão anterior
				@unlink($velho);
			}
		}
	}
	$url = $up['baseurl'] . '/ieadc-cache/tema-' . $hash . '.css';
	return $url;
}

/* ---------- Compressão e cache longo dos arquivos estáticos (.htaccess) ---------- */
/**
 * O WordPress grava as regras dos links limpos no .htaccess (seed, migrar-remoto.sh ou Configurações > Links > Salvar). Este filtro acrescenta ao mesmo
 * bloco a compressão e o cache de 1 ano para CSS, JS, fontes e imagens (nomes de arquivo ou ?ver= mudam quando o conteúdo muda; o CSS do tema vem com hash
 * no nome). Cada parte só vale se o módulo existir (Apache e LiteSpeed leem este arquivo). O HTML não entra: quem o guarda é o plugin de cache.
 */
add_filter('mod_rewrite_rules', function (string $regras): string {
	$bloco = <<<'HTACCESS'
# Fase 3 (desempenho): compressão e cache longo de arquivos estáticos
<IfModule mod_deflate.c>
AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript application/json application/manifest+json application/xml image/svg+xml
</IfModule>
<IfModule mod_expires.c>
ExpiresActive On
ExpiresByType text/css "access plus 1 year"
ExpiresByType text/javascript "access plus 1 year"
ExpiresByType application/javascript "access plus 1 year"
ExpiresByType font/woff2 "access plus 1 year"
ExpiresByType image/webp "access plus 30 days"
ExpiresByType image/png "access plus 30 days"
ExpiresByType image/jpeg "access plus 30 days"
ExpiresByType image/gif "access plus 30 days"
ExpiresByType image/svg+xml "access plus 30 days"
ExpiresByType image/x-icon "access plus 30 days"
ExpiresByType image/vnd.microsoft.icon "access plus 30 days"
</IfModule>

HTACCESS;
	return $bloco . ieadc_htaccess_seguranca() . $regras;
});

/**
 * Endurecimento do .htaccess (fase 4), no mesmo bloco do WordPress e com o mesmo mecanismo da fase 3 (vale depois do próximo flush_rewrite_rules(true):
 * o seed e o migrar-remoto.sh/atualizar-remoto.sh o fazem). Tudo dentro de <IfModule>/<FilesMatch>: onde o módulo não existe a regra é ignorada, sem erro 500.
 *  - sem listagem de pastas (uma pasta sem index não mostra o que tem dentro);
 *  - acesso direto negado a: .env e outros arquivos de ponto (.git, .htpasswd, .DS_Store), wp-config.php (e o de exemplo), readme.html, license.txt,
 *    xmlrpc.php (o site não usa XML-RPC; é alvo comum de ataque de força bruta) e arquivos de log, dump e cópia (.log, .sql, .bak, .old, .orig, .swp, .dist).
 * Atenção: bloquear o xmlrpc.php desliga aplicativos que o usam (app do WordPress no celular com XML-RPC, Jetpack). docs/operacao/plugins.md.
 */
function ieadc_htaccess_seguranca(): string {
	return <<<'HTACCESS'
# Fase 4 (segurança): sem listagem de pastas e sem acesso direto a arquivos sensíveis
<IfModule mod_autoindex.c>
Options -Indexes
</IfModule>
<FilesMatch "(?i)(^\.env.*|^\.git.*|^\.htpasswd$|^\.ds_store$|^wp-config(-sample)?\.php$|^readme\.html$|^license\.txt$|^xmlrpc\.php$|\.(log|sql|bak|old|orig|swp|dist)$)">
<IfModule mod_authz_core.c>
Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
Order allow,deny
Deny from all
</IfModule>
</FilesMatch>

HTACCESS;
}
