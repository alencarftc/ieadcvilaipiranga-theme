<?php
/**
 * Desempenho do front (fase 3): foto do topo (LCP) em tamanho certo, CSS em um arquivo, folhas sem uso fora e cache de arquivos estáticos.
 * Nada aqui muda o HTML dos blocos (o teste de markup segue igual ao do protótipo): as trocas vêm por <link>/<style> no <head>.
 */
defined('ABSPATH') || exit;

/** Largura (px) até a qual o celular recebe a foto reduzida do topo (768 px de largura é o "medium_large" do WordPress). */
const IEADC_LARGURA_CELULAR = 768;
/** Telas estreitas (celular em pé, até esta largura em px): foto ainda menor, feita sob medida (ieadc_foto_estreita()). */
const IEADC_LARGURA_ESTREITA = 520;
/** Largura em px e qualidade do WebP da foto das telas estreitas (640 px cobrem 412 px de tela a 1,55x; com a qualidade 76 a foto cai de ~65 KB para ~32 KB). */
const IEADC_FOTO_ESTREITA_PX = 640;
const IEADC_FOTO_ESTREITA_QUALIDADE = 76;

/**
 * A foto do topo da página atual (a que o Lighthouse mede como LCP): ['id' => anexo, 'artigo' => bool] ou null.
 * Páginas: a foto do primeiro bloco de topo (ieadc/hero ou ieadc/hero-home, menos a variante "sem foto"); artigos: a capa (imagem destacada).
 */
function ieadc_foto_do_topo(): ?array {
	if (!is_singular()) {
		return null;
	}
	$hero = ieadc_seo_hero();
	if ($hero && ($hero['variante'] ?? 'pagina') === 'sem-foto') {
		return null;
	}
	$id = (int) ($hero['foto'] ?? 0);
	$artigo = false;
	if (!$id && ieadc_seo_bloco(null, ['ieadc/artigo-topo']) !== null) { // artigo: a capa (imagem destacada) é a maior imagem do topo
		$id = (int) get_post_thumbnail_id();
		$artigo = true;
	}
	return $id > 0 ? ['id' => $id, 'artigo' => $artigo] : null;
}

/**
 * Versão da foto do topo para telas estreitas (até IEADC_LARGURA_ESTREITA px): WebP de 640 px, qualidade 76. Na capa dos artigos (`.capa`, sempre
 * 16:10 e centralizada, `background: center/cover`) já vem recortada em 640x400, só a parte que aparece (de 150 KB para ~32 KB no artigo "alcançar
 * Curitiba"); no topo das páginas só reduz (a janela visível depende da largura da tela e do foco, então não recorta). O arquivo nasce na primeira visita
 * em uploads/ieadc-cache/ (como o CSS do tema; o nome leva a data e o tamanho da original, então foto trocada gera outro) e o WordPress não precisa
 * regerar miniaturas. Devolve ['url', 'largura', 'altura'] ou null (sem permissão de gravação, editor de imagem sem WebP...): aí a foto de 768 px vale.
 */
function ieadc_foto_estreita(int $id, bool $capa): ?array {
	static $feitas = [];
	$chave = $id . ($capa ? 'c' : 't');
	if (array_key_exists($chave, $feitas)) {
		return $feitas[$chave];
	}
	$feitas[$chave] = null;
	$orig = get_attached_file($id);
	if (!$orig || !is_file($orig)) {
		return null;
	}
	$up = wp_upload_dir(null, false);
	if (!empty($up['error'])) {
		return null;
	}
	$larg = IEADC_FOTO_ESTREITA_PX;
	$alt = $capa ? (int) round($larg * 10 / 16) : 0;
	$marca = substr(md5(filemtime($orig) . '|' . filesize($orig) . '|' . IEADC_FOTO_ESTREITA_QUALIDADE . '|' . ($capa ? 'c' : 't')), 0, 10);
	$pasta = $up['basedir'] . '/ieadc-cache';
	$nome = "topo-$id-" . ($capa ? 'capa' : 'foto') . "-$larg-$marca.webp";
	$destino = "$pasta/$nome";
	if (!is_file($destino)) {
		if (!wp_mkdir_p($pasta)) {
			return null;
		}
		if (!wp_image_editor_supports(['mime_type' => 'image/webp'])) { // sem WebP no editor o WordPress grava outra extensão e deixaria arquivo órfão a cada visita
			return null;
		}
		$ed = wp_get_image_editor($orig);
		if (is_wp_error($ed)) {
			return null;
		}
		$tam = $ed->get_size();
		if (($tam['width'] ?? 0) <= $larg + 64) { // a original já é pequena: nada a ganhar
			return null;
		}
		$altura = $capa ? $alt : (int) round($larg * ($tam['height'] ?? 0) / max(1, (int) $tam['width']));
		if (is_wp_error($ed->resize($larg, $capa ? $alt : null, $capa))) {
			return null;
		}
		$ed->set_quality(IEADC_FOTO_ESTREITA_QUALIDADE);
		$tmp = "$pasta/topo-" . uniqid('', true) . '.tmp.webp';
		$salvo = $ed->save($tmp, 'image/webp');
		$gravado = is_wp_error($salvo) ? $tmp : (string) ($salvo['path'] ?? $tmp);
		if (is_wp_error($salvo) || substr($gravado, -5) !== '.webp' || !@rename($gravado, $destino)) {
			@unlink($tmp);
			if ($gravado !== $tmp) {
				@unlink($gravado);
			}
			return null;
		}
	}
	if (!isset($altura)) { // arquivo que já existia: a altura sai da proporção da original (o WordPress já guarda as medidas, sem abrir o arquivo)
		$meta = wp_get_attachment_metadata($id);
		$altura = $capa ? $alt : (int) round($larg * (int) ($meta['height'] ?? 0) / max(1, (int) ($meta['width'] ?? 0)));
	}
	return $feitas[$chave] = ['url' => $up['baseurl'] . '/ieadc-cache/' . $nome, 'largura' => $larg, 'altura' => $altura];
}

/**
 * Foto do topo (a que o Lighthouse mede como LCP; nos artigos, a capa): até 520 px de tela vai a versão sob medida de 640 px (ieadc_foto_estreita(),
 * ~32 KB); até 768 px, a de 768 px do WordPress (~65 KB em vez de ~185 KB); no resto, a original. O HTML do bloco aponta para a original (style inline):
 * um <style> no cabeçalho troca o fundo (o seletor casa o nome do arquivo no atributo style). Os <link rel="preload"> usam `media` para nunca baixar
 * duas fotos: o motivo de cada um está no corpo da função.
 */
add_action('wp_head', function () {
	$topo = ieadc_foto_do_topo();
	if (!$topo) {
		return;
	}
	$id = $topo['id'];
	$artigo = $topo['artigo'];
	$cheia = wp_get_attachment_image_src($id, 'full');
	$media = wp_get_attachment_image_src($id, 'medium_large');
	if (!$cheia) {
		return;
	}
	$pequena = $media && $media[0] !== $cheia[0] && $media[1] <= IEADC_LARGURA_CELULAR + 64 && $media[1] >= 640;
	$estreita = ieadc_foto_estreita($id, $artigo);
	$l = IEADC_LARGURA_CELULAR;
	if (!$pequena && !$estreita) {
		if (!$artigo) {
			echo '<link rel="preload" as="image" href="' . esc_url($cheia[0]) . '" fetchpriority="high">' . "
";
		}
		return;
	}
	// No computador (a partir de 769 px) o preload da original (que o computador mede em 100) fica; no artigo, a capa fica mais abaixo: sem preload.
	if (!$artigo) {
		echo '<link rel="preload" as="image" href="' . esc_url($cheia[0]) . '" media="(min-width:' . ($l + 1) . 'px)" fetchpriority="high">' . "
";
	}
	$arq = basename((string) wp_parse_url($cheia[0], PHP_URL_PATH));
	$alvo = '.ieadc-pagina>section:first-child [style*="' . esc_attr($arq) . '"]';
	$css = '';
	if ($pequena) {
		$css .= '@media (max-width:' . $l . 'px){' . $alvo . '{background-image:url(' . esc_url($media[0]) . ')!important}}';
	}
	if ($estreita) {
		$css .= '@media (max-width:' . IEADC_LARGURA_ESTREITA . 'px){' . $alvo . '{background-image:url(' . esc_url($estreita['url']) . ')!important}}';
	}
	echo '<style id="ieadc-topo-celular">' . $css . '</style>' . "
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
 * Divide o fontes.css em @font-face "reais" (têm url(...): baixam um arquivo de fonte) e "de reserva" (src: local(...): usam a fonte do aparelho, sem rede).
 * As de reserva ficam no CSS do tema (valem já no primeiro desenho); as reais vão para outro arquivo, carregado só depois da página (ver ieadc_fontes_tag()).
 * @return array{0:string,1:string} [reais, reserva]
 */
function ieadc_fontes_dividir(string $css): array {
	preg_match_all('#@font-face\s*\{[^}]*\}#s', $css, $m);
	$reais = '';
	$reserva = '';
	foreach ($m[0] as $bloco) {
		if (str_contains($bloco, 'url(')) {
			$reais .= $bloco . "\n";
		} else {
			$reserva .= $bloco . "\n";
		}
	}
	return [$reais, $reserva];
}

/**
 * Endereços dos CSS do tema já minificados, em uploads/ieadc-cache (criados na primeira visita; o hash muda quando qualquer um dos três arquivos de origem
 * muda, então o navegador baixa o novo e o cache longo é seguro):
 *  - 'tema': fontes de reserva + protótipo + ajustes em um arquivo só (1 pedido em vez de 3), bloqueia a renderização como sempre;
 *  - 'fontes': só as fontes reais (Roboto, Roboto Condensed, Dancing Script), carregado depois da página (fase 6).
 * Com o filtro `ieadc_fontes_adiar` em false (ou sem permissão para gravar), 'fontes' vem vazio e as fontes reais voltam para o CSS do tema (com preload, como na
 * fase 4); sem permissão para gravar, os dois vêm vazios e o tema usa os três arquivos originais. Os originais (inclusive o prototipo.css congelado) não são alterados.
 * As fontes (url(../fonts/...)) ganham o endereço completo do tema.
 * @param bool|null $adiar Adiar as fontes reais (padrão: o filtro `ieadc_fontes_adiar`, que começa em true).
 * @return array{tema:string,fontes:string}
 */
function ieadc_css_arquivos(?bool $adiar = null): array {
	static $feitos = [];
	$adiar ??= (bool) apply_filters('ieadc_fontes_adiar', true);
	$chave = (int) $adiar;
	if (isset($feitos[$chave])) {
		return $feitos[$chave];
	}
	$feitos[$chave] = ['tema' => '', 'fontes' => ''];
	$dir = get_stylesheet_directory() . '/assets/css/';
	$arquivos = ['fontes.css', 'prototipo.css', 'ajustes.css'];
	$marca = '';
	foreach ($arquivos as $a) {
		if (!is_file($dir . $a)) {
			return $feitos[$chave];
		}
		$marca .= $a . filesize($dir . $a) . '.' . filemtime($dir . $a) . '|';
	}
	$hash = substr(md5($marca . get_stylesheet_directory_uri() . '|fontes-' . ($adiar ? 'separadas' : 'juntas')), 0, 12);
	$up = wp_upload_dir(null, false);
	if (!empty($up['error'])) {
		return $feitos[$chave];
	}
	$pasta = $up['basedir'] . '/ieadc-cache';
	$arq_tema = "$pasta/tema-$hash.css";
	$arq_fontes = "$pasta/fontes-$hash.css";
	if (!is_file($arq_tema) || ($adiar && !is_file($arq_fontes))) {
		if (!wp_mkdir_p($pasta)) {
			return $feitos[$chave];
		}
		$fontes_url = get_stylesheet_directory_uri() . '/assets/fonts/';
		$ler = fn(string $a): string => str_replace('url(../fonts/', 'url(' . $fontes_url, (string) file_get_contents($dir . $a));
		$gravar = function (string $destino, string $css): bool {
			$tmp = $destino . '.' . uniqid('', true) . '.tmp';
			if (@file_put_contents($tmp, ieadc_css_minificar($css)) === false || !@rename($tmp, $destino)) {
				@unlink($tmp);
				return false;
			}
			return true;
		};
		if ($adiar) {
			[$reais, $reserva] = ieadc_fontes_dividir($ler('fontes.css'));
			if ($reais === '' || !$gravar($arq_fontes, $reais) || !$gravar($arq_tema, $reserva . "\n" . $ler('prototipo.css') . "\n" . $ler('ajustes.css'))) {
				return $feitos[$chave];
			}
		} elseif (!$gravar($arq_tema, $ler('fontes.css') . "\n" . $ler('prototipo.css') . "\n" . $ler('ajustes.css'))) {
			return $feitos[$chave];
		}
		foreach (array_merge((array) glob($pasta . '/tema-*.css'), (array) glob($pasta . '/fontes-*.css')) as $velho) { // versões antigas (sem GLOB_BRACE: a imagem Alpine do wp-cli não o tem)
			if ($velho !== $arq_tema && $velho !== $arq_fontes && @filemtime($velho) < time() - 7 * DAY_IN_SECONDS) { // o HTML em cache pode apontar para a versão anterior
				@unlink($velho);
			}
		}
	}
	$feitos[$chave] = ['tema' => $up['baseurl'] . '/ieadc-cache/tema-' . $hash . '.css', 'fontes' => $adiar ? $up['baseurl'] . '/ieadc-cache/fontes-' . $hash . '.css' : ''];
	return $feitos[$chave];
}

/** Endereço do CSS do tema em um arquivo só (ver ieadc_css_arquivos()), ou '' se não der para gravar. */
function ieadc_css_pacote(): string {
	return ieadc_css_arquivos()['tema'];
}

/**
 * Fontes reais carregadas DEPOIS da página (fase 6). Antes, o Roboto, o Roboto Condensed e o Dancing Script (93 a 139 KB) eram pedidos junto com o CSS e a foto do
 * topo e, na rede lenta simulada pelo Lighthouse, disputavam a banda com eles: o LCP do celular ficava em 2,2 s (sem as fontes, o mesmo teste dá 1,6 s) e o FCP
 * ganhava mais 0,3 a 0,5 s nas páginas com a frase em script ou itálico (essas fontes eram pedidas só quando o CSS chegava, com prioridade alta).
 * Agora o <link> das fontes sai com media="print" (o navegador o baixa com prioridade mínima e não aplica as @font-face) e assets/js/site.js troca para "all"
 * quando a página termina de carregar. O primeiro desenho usa as fontes de reserva, que têm as medidas das reais (CLS 0,000), e a troca é só de forma das letras.
 * Quem já abriu o site neste navegador (marca no localStorage) as recebe logo: estão no cache. Sem JavaScript, o <noscript> carrega as fontes normalmente.
 */
function ieadc_fontes_tag(): string {
	$url = ieadc_css_arquivos()['fontes'];
	if ($url === '') {
		return '';
	}
	return '<link rel="stylesheet" id="ieadc-fontes-css" href="' . esc_url($url) . '" media="print" data-ieadc-fontes>' . "\n"
		. '<noscript><link rel="stylesheet" href="' . esc_url($url) . '"></noscript>' . "\n";
}

add_action('wp_head', function () {
	echo ieadc_fontes_tag();
}, 9);

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
ExpiresByType application/font-woff2 "access plus 1 year"
ExpiresByType application/x-font-woff2 "access plus 1 year"
ExpiresByType font/woff "access plus 1 year"
ExpiresByType image/webp "access plus 30 days"
ExpiresByType image/avif "access plus 30 days"
ExpiresByType image/png "access plus 30 days"
ExpiresByType image/jpeg "access plus 30 days"
ExpiresByType image/gif "access plus 30 days"
ExpiresByType image/svg+xml "access plus 30 days"
ExpiresByType image/x-icon "access plus 30 days"
ExpiresByType image/vnd.microsoft.icon "access plus 30 days"
</IfModule>
# Fase 6: o mesmo por extensão (não depende do tipo MIME que o servidor atribui; a hospedagem LiteSpeed media 5 a 6 arquivos sem cache longo com só o mod_expires)
<IfModule mod_headers.c>
<FilesMatch "(?i)\.(css|js|woff2?)$">
Header set Cache-Control "public, max-age=31536000, immutable"
</FilesMatch>
# Fotos de uploads e imagens do tema ficam em 30 dias: uma foto trocada mantendo o nome (carga completa) não pode ficar presa por 1 ano no navegador.
# Só os arquivos gerados com hash no nome (topo-*, tema-*, fontes-*) mudam de nome quando mudam de conteúdo e podem durar 1 ano.
<FilesMatch "^(topo|tema|fontes)-.*\.(webp|css)$">
Header set Cache-Control "public, max-age=31536000, immutable"
</FilesMatch>
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
