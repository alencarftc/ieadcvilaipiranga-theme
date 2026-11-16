<?php
// M6: configurações de SEO, segurança e permalinks, sem clicar no painel.
// Roda várias vezes sem problema. Opções de plugin só têm efeito quando o plugin está instalado
// (no ambiente de nuvem o wordpress.org é bloqueado e eles ficam guardados até a instalação).

$nome = 'IEADC Vila Ipiranga';

// Links limpos e descrição do site.
update_option('permalink_structure', '/%postname%/');
// O wp-cli não regrava o .htaccess sozinho ("requires special configuration"): sem as regras dos links limpos, toda
// página que não é a inicial dá 404. As funções do painel e o filtro got_rewrite fazem o WordPress gravar as regras.
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/misc.php';
add_filter('got_rewrite', '__return_true');
flush_rewrite_rules(true);
update_option('blogdescription', 'Uma família de fé no Capão Raso, em Curitiba.');
update_option('blog_public', getenv('IEADC_INDEXAR') === '0' ? 0 : 1); // IEADC_INDEXAR=0 esconde o site dos buscadores (servidor local)
update_option('default_ping_status', 'closed');
update_option('default_comment_status', 'closed');

// Yoast: título "Página · IEADC Vila Ipiranga", descrição pelo resumo; Organização = a igreja.
$titulos = (array) get_option('wpseo_titles', []);
$titulos = array_merge($titulos, [
	'separator'            => 'sc-dot',
	'title-page'           => '%%title%% %%sep%% %%sitename%%',
	'title-post'           => '%%title%% %%sep%% %%sitename%%',
	'title-evento'   => '%%title%% %%sep%% %%sitename%%',
	'metadesc-page'        => '%%excerpt%%',
	'metadesc-post'        => '%%excerpt%%',
	'company_name'         => 'Congregação da Igreja Evangélica Assembleia de Deus em Curitiba',
	'website_name'         => $nome,
	'company_or_person'    => 'company',
	'noindex-tax-post_tag' => true,
	'noindex-author-wpseo' => true,
	'disable-author'       => true,
	'disable-attachment'   => true,
]);
update_option('wpseo_titles', $titulos);
$redes = (array) get_option('wpseo_social', []);
foreach (['facebook_site' => 'facebook', 'instagram_url' => 'instagram', 'youtube_url' => 'youtube'] as $campo => $chave) {
	if (function_exists('ieadc_info') && ieadc_info($chave)) {
		$redes[$campo] = ieadc_info($chave);
	}
}
update_option('wpseo_social', $redes);
$wpseo = (array) get_option('wpseo', []);
$wpseo['enable_xml_sitemap'] = true;
$wpseo['enable_admin_bar_menu'] = false;
update_option('wpseo', $wpseo);

// Limit Login Attempts Reloaded: 4 tentativas, bloqueio de 20 min; depois de 3 bloqueios, 24 h.
update_option('limit_login_allowed_retries', 4);
update_option('limit_login_lockout_duration', 1200);
update_option('limit_login_allowed_lockouts', 3);
update_option('limit_login_long_duration', 86400);

// Two Factor (e-mail) para administradores. Só liga com IEADC_2FA=1, para não travar o login local sem e-mail configurado.
if (getenv('IEADC_2FA') === '1') {
	foreach (get_users(['role' => 'administrator', 'fields' => 'ID']) as $uid) {
		update_user_meta($uid, '_two_factor_enabled_providers', ['Two_Factor_Email']);
		update_user_meta($uid, '_two_factor_provider', 'Two_Factor_Email');
	}
	WP_CLI::log('Two Factor por e-mail ligado para os administradores.');
}
WP_CLI::log('Configurações de SEO e segurança aplicadas.');
