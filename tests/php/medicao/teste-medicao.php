<?php
/**
 * Testes da medição de acessos (fase 4), dentro do WordPress e com pedidos HTTP ao próprio site (serviço "wordpress" do Docker).
 * Rode com ./bin/testar.sh php. Confere:
 *  - validação de formato dos IDs (GTM, GA4, Clarity, Meta Pixel), dos códigos de verificação (Google e Bing) e do WhatsApp;
 *  - a saída do site SEM nenhum ID (nada impresso, CSP igual à da fase 3) e COM cada ID (aviso, JSON, script, meta tags, CSP, política de privacidade);
 *  - GTM e GA4 juntos: vale só o GTM;
 *  - interruptores "Site aberto ao público" e "Permitir que o Google indexe": combinações, regra de dependência, permissão e limpeza do cache.
 * As opções ieadc_info, ieadc_site_fechado e blog_public voltam ao que eram no fim (mesmo se o teste falhar).
 */
require dirname(__DIR__) . '/bootstrap.php';

$base = rtrim(getenv('IEADC_TESTE_URL') ?: 'http://wordpress', '/');

function ieadc_m_baixar(string $caminho): array {
	global $base;
	$r = wp_remote_get($base . $caminho, ['timeout' => 30, 'redirection' => 0, 'headers' => ['X-Ieadc-Hoje' => '2026-10-06']]);
	if (is_wp_error($r)) {
		return ['status' => 0, 'corpo' => '', 'csp' => ''];
	}
	$cab = array_change_key_case((array) wp_remote_retrieve_headers($r)->getAll(), CASE_LOWER);
	return ['status' => (int) wp_remote_retrieve_response_code($r), 'corpo' => (string) wp_remote_retrieve_body($r),
		'csp' => (string) ($cab['content-security-policy-report-only'] ?? ($cab['content-security-policy'] ?? ''))];
}

/** Diretiva da CSP (ex.: "script-src") como texto. */
function ieadc_m_diretiva(string $csp, string $nome): string {
	foreach (explode(';', $csp) as $d) {
		$d = trim($d);
		if (str_starts_with($d, $nome . ' ')) {
			return $d;
		}
	}
	return '';
}

$info_original  = get_option('ieadc_info', null);
$fechado_orig   = get_option('ieadc_site_fechado', null);
$publico_orig   = get_option('blog_public', null);
$usuario_orig   = get_current_user_id();
$tmp_usuarios   = [];

try {
	/* ---------- validação ---------- */
	echo "validação de formato\n";
	ieadc_igual('GTM-TESTE0000', ieadc_medicao_id_limpar('gtm', ' gtm-teste0000 '), 'GTM: aceita e põe em maiúsculas');
	ieadc_igual('', ieadc_medicao_id_limpar('gtm', 'GTM-'), 'GTM: "GTM-" sozinho não vale');
	ieadc_igual('', ieadc_medicao_id_limpar('gtm', 'GTM-AB<script>'), 'GTM: com tag não vale');
	ieadc_igual('', ieadc_medicao_id_limpar('gtm', 'G-TESTE00000'), 'GTM: um ID do GA4 não vale no campo do GTM');
	ieadc_igual('G-TESTE00000', ieadc_medicao_id_limpar('ga4', 'g-teste00000'), 'GA4: aceita G-XXXXXXXXXX');
	ieadc_igual('', ieadc_medicao_id_limpar('ga4', 'UA-12345-1'), 'GA4: o formato antigo UA- não vale');
	ieadc_igual('', ieadc_medicao_id_limpar('ga4', 'G-AB'), 'GA4: curto demais não vale');
	ieadc_igual('abcde12345', ieadc_medicao_id_limpar('clarity', 'abcde12345'), 'Clarity: 10 letras e números');
	ieadc_igual('', ieadc_medicao_id_limpar('clarity', 'abc'), 'Clarity: curto demais não vale');
	ieadc_igual('abcdef', ieadc_medicao_id_limpar('clarity', 'abcdef'), 'Clarity: 6 caracteres é o mínimo');
	ieadc_igual('', ieadc_medicao_id_limpar('clarity', 'abcde'), 'Clarity: 5 caracteres não vale');
	ieadc_igual('', ieadc_medicao_id_limpar('clarity', 'ABCDEF12'), 'Clarity: maiúsculas não valem (o ID do projeto é minúsculo)');
	ieadc_igual('1234567890123', ieadc_medicao_id_limpar('pixel', '1234567890123'), 'Meta Pixel: só dígitos');
	ieadc_igual('', ieadc_medicao_id_limpar('pixel', '12ab'), 'Meta Pixel: letras não valem');
	$g = 'AbCdEfGhIjKlMnOpQrStUvWxYz_-0123';
	ieadc_igual($g, ieadc_medicao_verificacao_limpar('google', $g), 'Search Console: aceita o código');
	ieadc_igual($g, ieadc_medicao_verificacao_limpar('google', '<meta name="google-site-verification" content="' . $g . '" />'), 'Search Console: extrai o código da meta tag inteira');
	ieadc_igual('', ieadc_medicao_verificacao_limpar('google', 'curto'), 'Search Console: código curto não vale');
	ieadc_igual($g, ieadc_medicao_verificacao_limpar('google', 'google-site-verification=' . $g), 'Search Console: aceita o texto do registro TXT e usa só o código depois do =');
	ieadc_igual('', ieadc_medicao_verificacao_limpar('google', 'google-site-verification='), 'Search Console: registro TXT sem código não vale');
	$b = 'A1B2C3D4E5F60718293A4B5C6D7E8F90';
	ieadc_igual($b, ieadc_medicao_verificacao_limpar('bing', strtolower($b)), 'Bing: 32 caracteres hexadecimais');
	ieadc_igual('', ieadc_medicao_verificacao_limpar('bing', 'xyz'), 'Bing: fora do formato não vale');
	ieadc_igual('5541988381994', ieadc_whatsapp_limpar('(41) 98838-1994'), 'WhatsApp: DDD e número viram 55 + dígitos');
	ieadc_igual('5541988381994', ieadc_whatsapp_limpar('+55 41 98838 1994'), 'WhatsApp: aceita o +55');
	ieadc_igual('', ieadc_whatsapp_limpar('12345'), 'WhatsApp: número curto não vale');
	ieadc_igual('', ieadc_whatsapp_limpar('+1 415 555 2671'), 'WhatsApp: número de outro país não vale');

	// A tela de Configurações grava só valores válidos.
	$salvo = ieadc_info_sanitizar(['ga4_id' => 'UA-1-1', 'gtm_id' => 'gtm-teste0000', 'clarity_id' => 'x', 'search_console' => 'curto', 'whatsapp' => 'abc']);
	ieadc_ok(($salvo['ga4_id'] ?? 'x') === '' && ($salvo['gtm_id'] ?? '') === 'GTM-TESTE0000' && ($salvo['clarity_id'] ?? 'x') === '' && ($salvo['search_console'] ?? 'x') === '' && ($salvo['whatsapp'] ?? 'x') === '',
		'a gravação em Configurações (ieadc_info_sanitizar) descarta o que está fora do formato e normaliza o resto', $salvo);

	/* ---------- dados públicos que vivem no código (informados pelo Felipe) ---------- */
	echo "valores padrão de Configurações
";
	update_option('ieadc_info', []);
	ieadc_igual('ieadcvilaipiranga@gmail.com', ieadc_info('email'), 'e-mail público padrão');
	ieadc_igual('https://share.google/U5EOVkegYKa2UcT7o', ieadc_info('google_avaliacao'), 'link de avaliação padrão');
	ieadc_igual('https://share.google/U5EOVkegYKa2UcT7o', ieadc_google_avaliacao_url(), 'o botão "Avaliar no Google" usa o link padrão');
	ieadc_igual(ieadc_google_avaliacao_url(), ieadc_google_avaliacao_limpar('https://share.google/U5EOVkegYKa2UcT7o'), 'o host share.google passa na validação do campo');
	ieadc_ok((float) ieadc_info('latitude') === -25.503215673764625 && (float) ieadc_info('longitude') === -49.28838685582037, 'latitude e longitude padrão');

	/* ---------- saída sem nenhum ID ---------- */
	echo "sem ID: nada é impresso\n";
	update_option('ieadc_info', []);
	$h = ieadc_m_baixar('/');
	$sem_csp = $h['csp'];
	ieadc_ok($h['status'] === 200, 'início responde 200', $h['status']);
	foreach (['ieadc-cookies', 'ieadc-medicao', 'medicao.js', 'google-site-verification', 'msvalidate.01', 'googletagmanager'] as $q) {
		ieadc_ok(!str_contains($h['corpo'], $q), "sem ID: o HTML não tem \"$q\"");
	}
	ieadc_ok(!preg_match('#googletagmanager|google-analytics|clarity\.ms|facebook\.|doubleclick#', $sem_csp) && str_contains($sem_csp, "frame-src 'none'") && str_contains($sem_csp, "script-src 'self' 'inline-speculation-rules';"),
		'sem ID: a CSP é a da fase 3 (nenhum endereço de terceiros)', $sem_csp);
	$pol = ieadc_m_baixar('/politica-de-privacidade/');
	ieadc_ok(!str_contains($pol['corpo'], 'Cookies de medição') && str_contains($pol['corpo'], 'O site não usa cookies de publicidade'), 'sem ID: a política de privacidade fica como estava');

	/* ---------- com Tag Manager ---------- */
	echo "com GTM\n";
	update_option('ieadc_info', ['gtm_id' => 'GTM-TESTE0000']);
	$h = ieadc_m_baixar('/');
	ieadc_ok(str_contains($h['corpo'], 'id="ieadc-cookies"') && str_contains($h['corpo'], 'data-ieadc-cookies="aceitar"') && str_contains($h['corpo'], 'data-ieadc-cookies="recusar"'), 'GTM: aviso com Aceitar e Recusar');
	ieadc_ok(str_contains($h['corpo'], '<script type="application/json" id="ieadc-medicao">{"gtm":"GTM-TESTE0000"}</script>'), 'GTM: o ID vai num bloco JSON (não executa script)', substr($h['corpo'], (int) strpos($h['corpo'], 'ieadc-medicao') - 40, 200));
	ieadc_ok(str_contains($h['corpo'], 'assets/js/medicao.js'), 'GTM: o JavaScript de medição é carregado');
	ieadc_ok(!preg_match('#<script[^>]+src="[^"]*googletagmanager#', $h['corpo']) && !str_contains($h['corpo'], 'gtm.js'), 'GTM: o HTML não carrega o gtm.js (só depois do "Aceitar")');
	ieadc_ok(str_contains(ieadc_m_diretiva($h['csp'], 'script-src'), 'https://www.googletagmanager.com') && str_contains(ieadc_m_diretiva($h['csp'], 'connect-src'), 'https://www.google-analytics.com')
		&& str_contains(ieadc_m_diretiva($h['csp'], 'connect-src'), 'https://analytics.google.com') && str_contains(ieadc_m_diretiva($h['csp'], 'frame-src'), 'googletagmanager.com'), 'GTM: a CSP libera googletagmanager.com e google-analytics.com', $h['csp']);
	ieadc_ok(!str_contains($h['csp'], 'clarity') && !str_contains($h['csp'], 'facebook'), 'GTM: a CSP não libera Clarity nem Facebook');
	$pol = ieadc_m_baixar('/politica-de-privacidade/');
	ieadc_ok(str_contains($pol['corpo'], 'Cookies de medição de acessos') && str_contains($pol['corpo'], 'data-ieadc-cookies-abrir') && str_contains($pol['corpo'], 'Google Tag Manager') && str_contains($pol['corpo'], 'O site não usa cookies de publicidade'),
		'GTM: a política ganha o parágrafo de cookies de medição e o botão "Mudar minha escolha"');
	$js = (string) file_get_contents(get_stylesheet_directory() . '/assets/js/medicao.js');
	ieadc_ok(str_contains($js, "gtag('consent', 'default', { ad_storage: 'denied', analytics_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied' })"), 'o JavaScript define o Consent Mode v2 como "negado" antes de tudo');
	ieadc_ok(strpos($js, "'consent', 'default'") < strpos($js, 'gtm.js?id='), 'o consent default vem antes da carga do gtm.js');

	/* ---------- GTM e GA4 juntos: vale só o GTM ---------- */
	update_option('ieadc_info', ['gtm_id' => 'GTM-TESTE0000', 'ga4_id' => 'G-TESTE00000']);
	$h = ieadc_m_baixar('/');
	ieadc_ok(str_contains($h['corpo'], '{"gtm":"GTM-TESTE0000"}') && !str_contains($h['corpo'], 'G-TESTE00000'), 'GTM e GA4 preenchidos: só o GTM é usado (a visita não é contada duas vezes)');

	/* ---------- só GA4 ---------- */
	echo "com GA4 (sem GTM)\n";
	update_option('ieadc_info', ['ga4_id' => 'G-TESTE00000']);
	$h = ieadc_m_baixar('/');
	ieadc_ok(str_contains($h['corpo'], '{"ga4":"G-TESTE00000"}') && str_contains($h['corpo'], 'id="ieadc-cookies"'), 'GA4: aviso e JSON com o ID');
	ieadc_ok(str_contains(ieadc_m_diretiva($h['csp'], 'script-src'), 'https://www.googletagmanager.com') && str_contains(ieadc_m_diretiva($h['csp'], 'connect-src'), 'google-analytics.com') && str_contains($h['csp'], "frame-src 'none'"),
		'GA4: a CSP libera o gtag.js e o envio, e segue sem quadros', $h['csp']);
	ieadc_ok(!str_contains($h['corpo'], 'gtag/js'), 'GA4: o gtag.js também só carrega depois do "Aceitar"');

	/* ---------- Clarity e Meta Pixel ---------- */
	echo "com Clarity e Meta Pixel\n";
	update_option('ieadc_info', ['clarity_id' => 'abcde12345']);
	$h = ieadc_m_baixar('/');
	ieadc_ok(str_contains(ieadc_m_diretiva($h['csp'], 'script-src'), 'https://www.clarity.ms') && !str_contains($h['csp'], 'googletagmanager'), 'Clarity: a CSP libera clarity.ms e só ele');
	update_option('ieadc_info', ['meta_pixel_id' => '1234567890123']);
	$h = ieadc_m_baixar('/');
	ieadc_ok(str_contains(ieadc_m_diretiva($h['csp'], 'script-src'), 'https://connect.facebook.net') && !str_contains($h['csp'], 'clarity') && str_contains($h['corpo'], '{"pixel":"1234567890123"}'), 'Meta Pixel: a CSP libera connect.facebook.net e só ele');
	$pol = ieadc_m_baixar('/politica-de-privacidade/');
	ieadc_ok(str_contains($pol['corpo'], 'Meta Pixel') && !str_contains($pol['corpo'], 'O site não usa cookies de publicidade'), 'Meta Pixel: a política deixa de dizer que o site não usa cookies de publicidade');

	/* ---------- verificação do Google e do Bing (sem cookie, sem aviso) ---------- */
	echo "verificação de propriedade\n";
	update_option('ieadc_info', ['search_console' => $g, 'bing_verificacao' => $b]);
	$h = ieadc_m_baixar('/');
	ieadc_ok(str_contains($h['corpo'], '<meta name="google-site-verification" content="' . $g . '">') && str_contains($h['corpo'], '<meta name="msvalidate.01" content="' . $b . '">'), 'as meta tags de verificação saem na página');
	ieadc_ok(!str_contains($h['corpo'], 'ieadc-cookies') && !str_contains($h['corpo'], 'medicao.js'), 'só verificação: sem aviso de cookies e sem script de medição');

	/* ---------- WhatsApp: campo ou telefone do site ---------- */
	echo "WhatsApp\n";
	update_option('ieadc_info', []);
	ieadc_igual('5541988381994', ieadc_whatsapp_numero(), 'campo vazio: usa o telefone do site (celular)');
	ieadc_igual('https://wa.me/5541988381994', ieadc_whatsapp_url(), 'link wa.me montado a partir do telefone');
	update_option('ieadc_info', ['whatsapp' => '(41) 99999-0000']);
	ieadc_igual('5541999990000', ieadc_whatsapp_numero(), 'campo preenchido vence o telefone do site');
	update_option('ieadc_info', ['telefone' => '+55 (41) 3333-4444']);
	ieadc_igual('', ieadc_whatsapp_numero(), 'telefone fixo não vira WhatsApp (o botão não aparece)');
	$h = ieadc_m_baixar('/');
	ieadc_ok(!str_contains($h['corpo'], 'ieadc-whats'), 'sem número de celular: o botão flutuante não aparece');
	update_option('ieadc_info', []);
	$h = ieadc_m_baixar('/');
	ieadc_ok(str_contains($h['corpo'], 'class="ieadc-whats ieadc-ui"') && str_contains($h['corpo'], 'aria-label="Falar no WhatsApp') && str_contains($h['corpo'], 'https://wa.me/5541988381994?text='),
		'com o telefone do site: botão flutuante com nome acessível e link wa.me');
	ieadc_ok(!preg_match('#<main[^>]*>.*ieadc-whats.*</main>#s', $h['corpo']), 'o botão flutuante fica fora do <main>');

	/* ---------- Como chegar: atalhos {maps} e {waze} ---------- */
	update_option('ieadc_info', ['endereco' => 'Rua Teste, 1 - Curitiba - PR']);
	ieadc_igual('https://waze.com/ul?q=Rua%20Teste%2C%201%20-%20Curitiba%20-%20PR&navigate=yes', ieadc_url('{waze}'), 'o atalho {waze} usa o endereço de Configurações');
	ieadc_ok(str_starts_with(ieadc_url('{maps}'), 'https://www.google.com/maps/search/?api=1&query=') && str_contains(ieadc_url('{maps}'), rawurlencode('Rua Teste, 1')), 'o atalho {maps} usa o endereço de Configurações', ieadc_url('{maps}'));

	/* ---------- interruptores do estado do site ---------- */
	echo "interruptores: aberto ao público e Google\n";
	require_once ABSPATH . 'wp-admin/includes/template.php'; // submit_button() (a caixa de estado é uma função do painel)
	$admin = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
	$admin = (int) ($admin[0] ?? 0);
	ieadc_ok($admin > 0, 'há um administrador para o teste');
	$purgas = 0;
	add_action('litespeed_purge_all', function () use (&$purgas) { $purgas++; });
	wp_set_current_user($admin);
	$antes = $purgas;
	ieadc_ok(ieadc_site_definir(true, true) === true && ieadc_site_estado() === 'no-ar' && (int) get_option('ieadc_site_fechado') === 0 && (int) get_option('blog_public') === 1, 'aberto + indexável = no ar');
	ieadc_ok($purgas === $antes + 1, 'salvar esvazia o cache do LiteSpeed (litespeed_purge_all)');
	ieadc_ok(ieadc_site_definir(true, false) === true && ieadc_site_estado() === 'aberto-sem-google' && (int) get_option('ieadc_site_fechado') === 0 && (int) get_option('blog_public') === 0, 'aberto + sem Google = aberto, fora do Google');
	ieadc_ok(ieadc_site_definir(false, true) === true && ieadc_site_estado() === 'fechado' && (int) get_option('ieadc_site_fechado') === 1 && (int) get_option('blog_public') === 0, 'fechado nunca é indexável (indexar marcado é ignorado)');
	ieadc_ok(ieadc_site_definir(false, false) === true && ieadc_site_estado() === 'fechado' && (int) get_option('blog_public') === 0, 'fechado + sem Google = fechado');
	ob_start();
	ieadc_site_estado_caixa();
	$caixa = (string) ob_get_clean();
	ieadc_ok(str_contains($caixa, 'Hoje: fechado') && str_contains($caixa, 'name="aberto"') && str_contains($caixa, 'name="indexavel"') && str_contains($caixa, '_wpnonce') && preg_match('/id="ieadc-indexavel"[^>]*disabled/', $caixa),
		'a caixa mostra "Hoje: fechado", os dois interruptores, o nonce e o do Google desabilitado quando fechado');
	ieadc_site_definir(true, false);
	ob_start();
	ieadc_site_estado_caixa();
	$caixa = (string) ob_get_clean();
	ieadc_ok(str_contains($caixa, 'Hoje: aberto, fora do Google') && !preg_match('/id="ieadc-indexavel"[^>]*disabled/', $caixa), 'a caixa mostra "Hoje: aberto, fora do Google" e libera o interruptor do Google');
	ieadc_site_definir(true, true);
	ob_start();
	ieadc_site_estado_caixa();
	$caixa = (string) ob_get_clean();
	ieadc_ok(str_contains($caixa, 'Hoje: no ar e indexável'), 'a caixa mostra "Hoje: no ar e indexável"');

	// Sem permissão: nada muda (visitante e assinante).
	$estado_antes = [get_option('ieadc_site_fechado'), get_option('blog_public')];
	wp_set_current_user(0);
	ieadc_ok(ieadc_site_definir(false, false) === false && [get_option('ieadc_site_fechado'), get_option('blog_public')] === $estado_antes, 'visitante sem login não consegue mudar o estado do site');
	$assinante = wp_insert_user(['user_login' => 'ieadc_teste_assinante_' . wp_generate_password(6, false), 'user_pass' => wp_generate_password(20), 'role' => 'subscriber']);
	if (!is_wp_error($assinante)) {
		$tmp_usuarios[] = $assinante;
		wp_set_current_user($assinante);
		ieadc_ok(ieadc_site_definir(false, false) === false && [get_option('ieadc_site_fechado'), get_option('blog_public')] === $estado_antes, 'assinante não consegue mudar o estado do site');
		ob_start();
		ieadc_site_estado_caixa();
		ieadc_ok(trim((string) ob_get_clean()) === '', 'a caixa de estado não aparece para quem não é administrador');
	}
	wp_set_current_user($admin);
} finally {
	foreach ($tmp_usuarios as $u) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user($u);
	}
	wp_set_current_user($usuario_orig);
	$info_original === null ? delete_option('ieadc_info') : update_option('ieadc_info', $info_original);
	$fechado_orig === null ? delete_option('ieadc_site_fechado') : update_option('ieadc_site_fechado', $fechado_orig);
	$publico_orig === null ? delete_option('blog_public') : update_option('blog_public', $publico_orig);
}

ieadc_teste_fim();
