<?php
/**
 * Testes do endurecimento do .htaccess (fase 4): sem listagem de pastas e sem acesso direto a arquivos sensíveis.
 * Rode com ./bin/testar.sh php. Regrava o .htaccess (flush_rewrite_rules(true), como o seed e os scripts de implantação),
 * cria arquivos e uma pasta de teste (apagados no fim) e pede cada endereço ao próprio site (servidor Apache do Docker).
 * Na hospedagem (LiteSpeed) vale a mesma regra: conferir com os endereços da lista depois do próximo deploy.
 */
require dirname(__DIR__) . '/bootstrap.php';

$base = rtrim(getenv('IEADC_TESTE_URL') ?: 'http://wordpress', '/');

function ieadc_s_status(string $caminho): array {
	global $base;
	$r = wp_remote_get($base . $caminho, ['timeout' => 30, 'redirection' => 0]);
	return is_wp_error($r) ? [0, ''] : [(int) wp_remote_retrieve_response_code($r), (string) wp_remote_retrieve_body($r)];
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';
add_filter('got_rewrite', '__return_true');
flush_rewrite_rules(true);

$ht = (string) @file_get_contents(ABSPATH . '.htaccess');
echo ".htaccess\n";
ieadc_ok(str_contains($ht, '# BEGIN WordPress') && str_contains($ht, 'RewriteRule'), 'o .htaccess tem o bloco do WordPress e as regras dos links limpos');
ieadc_ok(str_contains($ht, '<IfModule mod_autoindex.c>') && str_contains($ht, 'Options -Indexes'), 'Options -Indexes dentro de <IfModule>');
ieadc_ok(str_contains($ht, '<FilesMatch') && str_contains($ht, 'Require all denied') && str_contains($ht, 'Deny from all') && substr_count($ht, '<FilesMatch') === substr_count($ht, '</FilesMatch>'), 'FilesMatch com Require all denied (e Deny from all onde não há mod_authz_core), bem fechado');
foreach (['wp-config', 'readme', 'license', 'xmlrpc', '.env', 'log|sql|bak'] as $trecho) {
	ieadc_ok(str_contains($ht, $trecho), "o .htaccess cita $trecho");
}
ieadc_ok(str_contains($ht, 'mod_expires.c') && str_contains($ht, 'mod_deflate.c'), 'as regras de cache e compressão da fase 3 continuam');
ieadc_ok(str_contains($ht, '<IfModule mod_headers.c>') && str_contains($ht, 'Header set Cache-Control "public, max-age=31536000, immutable"') && str_contains($ht, 'Header set Cache-Control "public, max-age=31536000"'), 'cache de 1 ano por extensão (mod_headers): CSS, JS e fontes imutáveis, imagens 1 ano (fase 6)');
ieadc_ok(str_contains($ht, 'ExpiresByType image/webp "access plus 1 year"') && str_contains($ht, 'ExpiresByType font/woff2 "access plus 1 year"') && !str_contains($ht, 'image/webp "access plus 30 days"'), 'mod_expires: imagens e fontes por 1 ano (o Lighthouse marca 30 dias como cache curto)');
ieadc_ok(substr_count($ht, '<IfModule') === substr_count($ht, '</IfModule>') && substr_count($ht, '<FilesMatch') === substr_count($ht, '</FilesMatch>'), 'todo <IfModule> e <FilesMatch> bem fechado');
ieadc_ok(substr_count($ht, '# Fase 6') === 1, 'o bloco da fase 6 aparece uma vez só (gravação idempotente)');

$uploads = wp_upload_dir(null, false);
$pasta = $uploads['basedir'] . '/ieadc-teste-seguranca';
$criados = [];
wp_mkdir_p($pasta);
foreach (['dados.log' => 'x', 'copia.sql' => 'x', 'velho.bak' => 'x', 'ok.txt' => 'publico', 'imagem.png' => 'x'] as $nome => $conteudo) {
	file_put_contents("$pasta/$nome", $conteudo);
	$criados[] = "$pasta/$nome";
}
file_put_contents(ABSPATH . '.env', 'SEGREDO=x');
$criados[] = ABSPATH . '.env';

try {
	echo "arquivos sensíveis: 403\n";
	foreach (['/wp-config.php', '/readme.html', '/license.txt', '/xmlrpc.php', '/.env', '/wp-content/uploads/ieadc-teste-seguranca/dados.log',
		'/wp-content/uploads/ieadc-teste-seguranca/copia.sql', '/wp-content/uploads/ieadc-teste-seguranca/velho.bak'] as $c) {
		[$st] = ieadc_s_status($c);
		ieadc_ok($st === 403, "$c responde 403", $st);
	}
	echo "o que tem de continuar funcionando\n";
	foreach (['/' => 200, '/contato/' => 200, '/wp-login.php' => 200, '/wp-json/' => 200, '/favicon.ico' => 200, '/sitemap_index.xml' => 200, '/robots.txt' => 200, '/wp-content/uploads/ieadc-teste-seguranca/ok.txt' => 200] as $c => $esperado) {
		[$st] = ieadc_s_status($c);
		ieadc_ok($st === $esperado || ($c === '/sitemap_index.xml' && in_array($st, [200, 301, 404], true)), "$c responde $esperado", $st);
	}
	echo "cache longo dos arquivos estáticos (fase 6)\n";
	foreach (['/wp-content/uploads/ieadc-teste-seguranca/imagem.png', '/wp-content/themes/ieadc-vila-ipiranga/assets/fonts/Roboto-normal-latin.woff2', '/wp-content/themes/ieadc-vila-ipiranga/assets/css/ajustes.css', '/wp-content/themes/ieadc-vila-ipiranga/assets/js/site.js', '/wp-content/themes/ieadc-vila-ipiranga/assets/img/logo_cor-300.webp'] as $c) {
		$r = wp_remote_head($base . $c, ['timeout' => 30, 'redirection' => 0]);
		$cc = is_wp_error($r) ? '' : (string) wp_remote_retrieve_header($r, 'cache-control');
		ieadc_ok(preg_match('/max-age=(\d+)/', $cc, $mm) === 1 && (int) $mm[1] >= 31536000, "$c: Cache-Control de 1 ano", $cc);
	}
	echo "listagem de pastas\n";
	[$st, $corpo] = ieadc_s_status('/wp-content/uploads/ieadc-teste-seguranca/');
	ieadc_ok($st !== 200 && !str_contains($corpo, 'Index of') && !str_contains($corpo, 'ok.txt'), 'pasta sem index não lista o conteúdo', $st);
	[$st2, $corpo2] = ieadc_s_status('/wp-content/themes/ieadc-vila-ipiranga/assets/');
	ieadc_ok($st2 !== 200 || !str_contains($corpo2, 'Index of'), 'a pasta de assets do tema não lista o conteúdo', $st2);
} finally {
	foreach ($criados as $f) {
		@unlink($f);
	}
	@rmdir($pasta);
}

/* ---------- Categorias de artigo (SEO, fase 4) ---------- */
echo "categorias de artigo: descrição própria e fora do Google\n";
foreach (['mensagem', 'estudo', 'reflexao', 'noticia'] as $slug) {
	$t = get_term_by('slug', $slug, 'category');
	ieadc_ok($t && mb_strlen(trim((string) $t->description)) >= 70 && mb_strlen(trim((string) $t->description)) <= 160, "categoria $slug tem descrição de 70 a 160 caracteres", $t ? mb_strlen((string) $t->description) : 'sem categoria');
	[$st, $corpo] = ieadc_s_status('/category/' . $slug . '/');
	$desc = preg_match('/<meta name=["\']description["\'] content=["\']([^"\']*)["\']/', $corpo, $m) ? html_entity_decode($m[1]) : '';
	ieadc_ok($st === 200 && $t && $desc === trim(wp_strip_all_tags((string) $t->description)), "/category/$slug/: a meta description é a da categoria", [$st, $desc]);
	ieadc_ok(preg_match("/<meta name=.robots. content=.noindex, follow/", $corpo) === 1, "/category/$slug/: noindex, follow");
}
[$st, $corpo] = ieadc_s_status('/alcancar-curitiba/');
ieadc_ok($st === 200 && preg_match("/<meta name=.robots. content=.index, follow/", $corpo) === 1, 'o artigo continua indexável');
[$st, $map] = ieadc_s_status('/sitemap_index.xml');
ieadc_ok($st === 200 && !str_contains($map, 'category-sitemap') && str_contains($map, 'post-sitemap'), 'o mapa do site não lista as categorias, só artigos e páginas');

/* ---------- Site fechado: o mapa do site também (fase 4) ---------- */
echo "site fechado: mapas do site
";
$fechado_orig = get_option('ieadc_site_fechado', null);
$admin = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
$admin = (int) ($admin[0] ?? 0);
$expira = time() + 3600;
$token = $admin ? WP_Session_Tokens::get_instance($admin)->create($expira) : ''; // o login precisa de uma sessão válida (apagada no fim)
// O nome do cookie leva o hash do endereço do site; o servidor responde pelo endereço do pedido ($base), o wp-cli pelo do .env: vai o cookie dos dois.
$valor = $admin ? wp_generate_auth_cookie($admin, $expira, 'logged_in', $token) : '';
$cookie = $admin ? LOGGED_IN_COOKIE . '=' . $valor . '; wordpress_logged_in_' . md5($base) . '=' . $valor : '';
/** Pede o endereço como visitante (sem cookie) ou como administrador (com o cookie de login). */
$pedir = function (string $caminho, bool $logado) use ($base, $cookie): int {
	$args = ['timeout' => 30, 'redirection' => 0];
	if ($logado) {
		$args['headers'] = ['Cookie' => $cookie];
	}
	$r = wp_remote_get($base . $caminho, $args);
	return is_wp_error($r) ? 0 : (int) wp_remote_retrieve_response_code($r);
};
$mapas = ['/sitemap_index.xml', '/post-sitemap.xml', '/page-sitemap.xml', '/main-sitemap.xsl', '/wp-sitemap.xml'];
try {
	update_option('ieadc_site_fechado', 0);
	foreach ($mapas as $m) {
		$st = $pedir($m, false);
		ieadc_ok($st !== 503 && $st !== 0, "aberto: $m não responde 503 ao visitante", $st);
	}
	ieadc_ok($pedir('/sitemap_index.xml', false) === 200, 'aberto: /sitemap_index.xml responde 200 ao visitante');
	update_option('ieadc_site_fechado', 1);
	foreach ($mapas as $m) {
		ieadc_igual(503, $pedir($m, false), "fechado: $m responde 503 ao visitante");
	}
	ieadc_igual(503, $pedir('/robots.txt', false), 'fechado: /robots.txt continua 503');
	ieadc_igual(503, $pedir('/wp-json/', false), 'fechado: /wp-json/ continua 503');
	ieadc_ok($admin > 0 && $pedir('/sitemap_index.xml', true) === 200, 'fechado: quem tem login recebe 200 em /sitemap_index.xml');
	ieadc_ok($pedir('/post-sitemap.xml', true) === 200, 'fechado: quem tem login recebe 200 em /post-sitemap.xml');
	// O .ics de evento (rota própria) também não pode entregar título, data e local a visitante com o site fechado.
	ieadc_igual(503, $pedir('/evento-999999.ics', false), 'fechado: /evento-<id>.ics responde 503 ao visitante');
	ieadc_igual(404, $pedir('/evento-999999.ics', true), 'fechado: quem tem login recebe a resposta normal (404 para evento que não existe)');
	update_option('ieadc_site_fechado', 0);
	ieadc_igual(404, $pedir('/evento-999999.ics', false), 'aberto: evento inexistente continua 404 ao visitante');
} finally {
	$fechado_orig === null ? delete_option('ieadc_site_fechado') : update_option('ieadc_site_fechado', $fechado_orig);
	if ($admin && $token !== '') {
		WP_Session_Tokens::get_instance($admin)->destroy($token);
	}
}

ieadc_teste_fim();
