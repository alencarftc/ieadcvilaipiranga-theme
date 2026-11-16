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

ieadc_teste_fim();
