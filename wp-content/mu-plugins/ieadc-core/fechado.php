<?php
/**
 * Site fechado ("em preparação").
 *
 * Enquanto a opção ieadc_site_fechado está ligada, visitantes sem login recebem uma página simples com status 503
 * (e sem indexação); quem tem login vê o site e o painel normalmente, para conferir as páginas.
 * O bin/migrar.ps1 liga a opção no ensaio de migração e desliga no lançamento (-Lancar). Sem a opção, o site é aberto.
 *
 * Não confundir com o modo manutenção do WordPress (arquivo .maintenance): ele expira sozinho depois de 10 minutos
 * e bloqueia também o login, então não serve para guardar um site no domínio por dias.
 */
defined('ABSPATH') || exit;

/** O site está fechado para quem está olhando agora? (opção ligada e sem login) */
function ieadc_site_fechado_bloqueia(): bool {
	return (bool) get_option('ieadc_site_fechado') && !is_user_logged_in();
}

/** A página que os visitantes veem enquanto o site está fechado: logo colorido com texto (versão para fundo escuro) acima do nome da igreja. */
function ieadc_site_fechado_html(): string {
	$nome = esc_html((string) ieadc_info('nome'));
	$logo = esc_url(get_stylesheet_directory_uri() . '/assets/img/logo_negativo.webp');
	return '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8">'
		. '<meta name="viewport" content="width=device-width,initial-scale=1">'
		. '<meta name="robots" content="noindex,nofollow">'
		. '<title>' . $nome . ' · em preparação</title>'
		. '<style>html,body{margin:0;height:100%}body{display:grid;place-items:center;background:#071946;color:#fff;font-family:Roboto,system-ui,sans-serif;text-align:center;padding:24px;box-sizing:border-box}'
		. '.logo{display:block;width:300px;max-width:78vw;height:auto;margin:0 auto 28px}@media(max-width:600px){.logo{width:200px;margin-bottom:22px}}'
		. 'h1{font-size:clamp(28px,6vw,44px);margin:0 0 12px}p{margin:0;font-size:18px;opacity:.85}</style></head>'
		. '<body><main><img class="logo" src="' . $logo . '" alt="Assembleia de Deus em Curitiba" width="300" height="90">'
		. '<h1>' . $nome . '</h1><p>Nosso site novo está sendo preparado. Volte em breve.</p></main></body></html>';
}

add_action('template_redirect', function () {
	if (!ieadc_site_fechado_bloqueia()) {
		return;
	}
	nocache_headers();
	status_header(503);
	header('Retry-After: 3600');
	header('X-Robots-Tag: noindex, nofollow');
	header('Content-Type: text/html; charset=utf-8');
	echo ieadc_site_fechado_html();
	exit;
}, 0);

/* A API REST mostraria o mesmo conteúdo: também fechada para visitantes. */
add_filter('rest_authentication_errors', function ($erro) {
	if ($erro === null && ieadc_site_fechado_bloqueia()) {
		return new WP_Error('ieadc_site_fechado', 'Site em preparação.', ['status' => 503]);
	}
	return $erro;
});
