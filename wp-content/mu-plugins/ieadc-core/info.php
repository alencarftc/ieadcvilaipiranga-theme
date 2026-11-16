<?php
/**
 * Dados da igreja usados no cabeçalho, rodapé e páginas.
 *
 * A fonte da verdade é a aba "Informações gerais" da documentação da Comunicação.
 * Mudou lá, muda aqui (ou no painel, em Configurações > IEADC Vila Ipiranga).
 */
defined('ABSPATH') || exit;

function ieadc_info_padrao(): array {
	return [
		'nome'       => 'IEADC Vila Ipiranga',
		'frase_topo' => 'Uma família de fé no Capão Raso, em Curitiba.',
		'nome_longo' => 'Congregação da Igreja Evangélica Assembleia de Deus em Curitiba',
		'endereco'   => 'Rua Marechal Althayr Roszanniy, 555 - Capão Raso, Curitiba - PR',
		'telefone'   => '+55 (41) 98838-1994',
		'email'      => 'ieadcvilaipiranga@gmail.com', // e-mail público da igreja (o do pastor fica só na lista de contatos e na política de privacidade)
		'instagram'  => 'https://www.instagram.com/ieadc.vilaipiranga/',
		'facebook'   => 'https://www.facebook.com/ieadcvipagina',
		'youtube'    => 'https://www.youtube.com/channel/UC7la6gW5oIvo83_DQhBp9sQ',
		'site_sede'  => 'https://assembleiadedeus.org.br/',
		'sede'       => 'Igreja Sede: Pr. Wagner Tadeu dos Santos Gaby, presidente, e Pr. Aristides Vieira, vice-presidente.',
		'tema_ano'   => '2026 · ALCANÇAR CURITIBA PARA CRISTO. NOSSA MISSÃO, NOSSA PAIXÃO.',
		// Resumo do rodapé. A agenda completa é calculada pelas regras da igreja (em construção).
		'cultos'     => "Segunda · 20h · Culto de Oração\nQuarta · 20h · Culto de Ensino\nQuinta · 20h · Círculo de Oração\nSexta · 19h30 · Ensinai\nDomingo · 9h30 EBD · 19h culto\n2º sábado · 19h30 · Ceia do Senhor",
		// Destinatário dos pedidos de oração e do formulário de contato.
		'email_pedidos' => '',
		'email_contato' => '',
		// Link para avaliar a igreja no Google (vazio = o botão leva à igreja no Google Maps). Coordenadas: opcionais, ajudam o Google.
		// Dados públicos informados pelo Felipe (16/11/2026). Ficam no código porque a carga completa substitui o banco; pode trocar em Configurações.
		'google_avaliacao' => 'https://share.google/U5EOVkegYKa2UcT7o',
		'latitude'   => '-25.503215673764625',
		'longitude'  => '-49.28838685582037',
		// Fase 4: WhatsApp e medição. Todos vazios por padrão = o recurso fica desligado e invisível (ver medicao.php).
		'whatsapp'         => '',
		'gtm_id'           => '',
		'ga4_id'           => '',
		'search_console'   => '',
		'bing_verificacao' => '',
		'clarity_id'       => '',
		'meta_pixel_id'    => '',
	];
}

function ieadc_info(?string $chave = null) {
	$dados = wp_parse_args((array) get_option('ieadc_info', []), ieadc_info_padrao());
	$dados = apply_filters('ieadc_info', $dados);
	if ($chave === null) {
		return $dados;
	}
	return $dados[$chave] ?? '';
}

/* Tela simples em Configurações > IEADC Vila Ipiranga. */
add_action('admin_menu', function () {
	add_options_page('IEADC Vila Ipiranga', 'IEADC Vila Ipiranga', 'manage_options', 'ieadc-info', 'ieadc_info_tela');
});

add_action('admin_init', function () {
	register_setting('ieadc_info', 'ieadc_info', [
		'type'              => 'array',
		'sanitize_callback' => 'ieadc_info_sanitizar',
	]);
});

/** Limpa e valida os campos de Configurações antes de gravar (formato dos IDs, links, e-mails, coordenadas). Campo fora do formato vira vazio. */
function ieadc_info_sanitizar($v): array {
	$v = is_array($v) ? $v : [];
	$limpo = [];
	foreach (ieadc_info_padrao() as $k => $_) {
		if (!isset($v[$k])) {
			continue;
		}
		$limpo[$k] = match (true) {
			$k === 'google_avaliacao' => ieadc_google_avaliacao_limpar((string) $v[$k]),
			in_array($k, ['latitude', 'longitude'], true) => ieadc_coordenada_limpar((string) $v[$k], $k === 'latitude' ? 90 : 180),
			$k === 'whatsapp' => ieadc_whatsapp_limpar((string) $v[$k]),
			$k === 'gtm_id' => ieadc_medicao_id_limpar('gtm', (string) $v[$k]),
			$k === 'ga4_id' => ieadc_medicao_id_limpar('ga4', (string) $v[$k]),
			$k === 'clarity_id' => ieadc_medicao_id_limpar('clarity', (string) $v[$k]),
			$k === 'meta_pixel_id' => ieadc_medicao_id_limpar('pixel', (string) $v[$k]),
			$k === 'search_console' => ieadc_medicao_verificacao_limpar('google', (string) $v[$k]),
			$k === 'bing_verificacao' => ieadc_medicao_verificacao_limpar('bing', (string) $v[$k]),
			in_array($k, ['instagram', 'facebook', 'youtube', 'site_sede'], true) => esc_url_raw($v[$k]),
			str_starts_with($k, 'email') => sanitize_email($v[$k]),
			$k === 'cultos' => sanitize_textarea_field($v[$k]),
			default => sanitize_text_field($v[$k]),
		};
	}
	return $limpo;
}

function ieadc_info_tela(): void {
	$rotulos = [
		'nome' => 'Nome', 'frase_topo' => 'Frase no topo do site', 'nome_longo' => 'Nome completo', 'endereco' => 'Endereço', 'telefone' => 'Telefone',
		'email' => 'E-mail público', 'instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube',
		'site_sede' => 'Site da IEADC', 'sede' => 'Linha da Igreja Sede (rodapé)', 'tema_ano' => 'Tema do ano (rodapé)',
		'cultos' => 'Cultos no rodapé (um por linha)', 'email_pedidos' => 'E-mail que recebe os pedidos de oração',
		'email_contato' => 'E-mail que recebe o Fale conosco',
		'google_avaliacao' => 'Link para avaliar no Google', 'latitude' => 'Latitude (opcional)', 'longitude' => 'Longitude (opcional)',
		'whatsapp' => 'WhatsApp da igreja (com DDD)', 'gtm_id' => 'ID do Google Tag Manager', 'ga4_id' => 'ID de medição do Google Analytics 4',
		'search_console' => 'Código do Google Search Console', 'bing_verificacao' => 'Código do Bing Webmaster Tools',
		'clarity_id' => 'ID do Microsoft Clarity (opcional)', 'meta_pixel_id' => 'ID do Meta Pixel (opcional)',
	];
	$ajuda = [
		'google_avaliacao' => 'Cole o link de avaliação do Perfil da Empresa no Google (ou search.google.com/local/writereview?placeid=…; vale também só o código do lugar, que começa com ChIJ). Vazio: o botão "Avaliar no Google" abre a igreja no Google Maps.',
		'latitude' => 'Opcional. Ajuda o Google a achar a igreja no mapa (exemplo: -00.0000). Deixe vazio se não souber.',
		'longitude' => 'Opcional (exemplo: -00.0000). Só vale se a latitude também estiver preenchida.',
		'whatsapp' => 'Número do WhatsApp com DDD (exemplo: 41 9XXXX-XXXX). Vazio: o botão flutuante e os links usam o telefone do site (confirmado como WhatsApp, desde que seja celular). Preencha só para usar outro número.',
		'gtm_id' => 'Formato GTM-XXXXXXX. Vazio: o Tag Manager não é usado. Preenchido, o site mostra o aviso de cookies e só carrega o GTM depois que a pessoa aceita. Se este campo e o do Google Analytics estiverem preenchidos, vale só o GTM (configure o Analytics dentro dele). Passo a passo: docs/operacao/rastreamento.md.',
		'ga4_id' => 'Formato G-XXXXXXXXXX (Google Analytics > Administrador > Fluxos de dados). Vazio: sem medição. Use só se NÃO usar o Tag Manager. Preenchido, o site mostra o aviso de cookies e só carrega o Analytics depois que a pessoa aceita.',
		'search_console' => 'Só se verificar o site pelo método "Tag HTML" (a verificação por registro DNS/TXT não precisa deste campo). Aceita o código, o texto google-site-verification=CODIGO ou a meta tag inteira; a meta tag usa só o código. Vazio: nada é impresso. Não usa cookie.',
		'bing_verificacao' => 'Opcional. O Bing Webmaster Tools pode importar o site do Search Console (nada a fazer aqui). Só preencha se verificar por "Tag HTML" (32 letras e números, ou a meta tag inteira).',
		'clarity_id' => 'Código do projeto no Microsoft Clarity (de 6 a 12 letras minúsculas e números). Vazio: não é usado. Só carrega depois que a pessoa aceita o aviso de cookies. Alternativa: o Clarity também pode entrar como tag dentro do Tag Manager (herda o consentimento); use um ou outro, não os dois.',
		'meta_pixel_id' => 'Número do Meta Pixel (só dígitos). Vazio: não é usado. Só carrega depois que a pessoa aceita o aviso de cookies e muda a política de privacidade.',
	];
	$v = ieadc_info();
	echo '<div class="wrap"><h1>IEADC Vila Ipiranga</h1>';
	ieadc_site_estado_caixa();
	echo '<p>Dados usados no cabeçalho, no rodapé e nos formulários. A fonte é a aba Informações gerais da documentação da Comunicação.</p><form method="post" action="options.php">';
	settings_fields('ieadc_info');
	echo '<table class="form-table">';
	foreach ($rotulos as $k => $r) {
		$nome = 'ieadc_info[' . $k . ']';
		echo '<tr><th><label for="ieadc-' . esc_attr($k) . '">' . esc_html($r) . '</label></th><td>';
		if ($k === 'cultos') {
			echo '<textarea class="large-text" rows="7" id="ieadc-' . esc_attr($k) . '" name="' . esc_attr($nome) . '">' . esc_textarea($v[$k]) . '</textarea>';
		} else {
			echo '<input class="regular-text" id="ieadc-' . esc_attr($k) . '" name="' . esc_attr($nome) . '" value="' . esc_attr($v[$k]) . '">';
		}
		if (isset($ajuda[$k])) {
			echo '<p class="description">' . esc_html($ajuda[$k]) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '</table>';
	submit_button();
	echo '</form></div>';
}

/** Link de avaliação: aceita o endereço completo (https) ou só o código do lugar (ChIJ…), que vira o link de avaliação. */
function ieadc_google_avaliacao_limpar(string $valor): string {
	$valor = trim($valor);
	if (preg_match('/^ChIJ[\w-]{10,}$/', $valor)) {
		return 'https://search.google.com/local/writereview?placeid=' . $valor;
	}
	// Só endereços do Google (o campo é de texto livre): perfil/avaliação, Maps e encurtadores oficiais; nada de // nem de outros domínios.
	$url = esc_url_raw($valor, ['https']);
	$host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
	$permitidos = ['google.com', 'g.page', 'goo.gl', 'maps.app.goo.gl', 'g.co', 'share.google'];
	foreach ($permitidos as $d) {
		if ($host === $d || str_ends_with($host, '.' . $d)) {
			return $url;
		}
	}
	return '';
}

/** Coordenada decimal dentro do limite (90 ou 180), ou vazio. */
function ieadc_coordenada_limpar(string $valor, int $limite): string {
	$valor = str_replace(',', '.', trim($valor));
	return (is_numeric($valor) && abs((float) $valor) <= $limite) ? (string) (float) $valor : '';
}

/** A igreja no Google Maps (nome e endereço): link do botão de avaliação quando não há link próprio, e `hasMap` dos dados estruturados. */
function ieadc_google_maps_url(): string {
	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(ieadc_info('nome') . ', ' . ieadc_info('endereco'));
}

/** A igreja no Waze (rota até o endereço de Configurações). */
function ieadc_waze_url(): string {
	return 'https://waze.com/ul?q=' . rawurlencode((string) ieadc_info('endereco')) . '&navigate=yes';
}

/** Link do botão "Avaliar no Google": o de Configurações > IEADC Vila Ipiranga; sem ele, a igreja no Google Maps. */
function ieadc_google_avaliacao_url(): string {
	$link = trim((string) ieadc_info('google_avaliacao'));
	return $link !== '' ? $link : ieadc_google_maps_url();
}

/**
 * WhatsApp: só os números, com o código do Brasil (55). Aceita "41 9XXXX-XXXX", "(41) 9XXXX-XXXX", "+55 41 9XXXX-XXXX". Fora do formato,
 * vazio (o botão não aparece). 10 ou 11 dígitos = DDD + número; 12 ou 13 começando em 55 = já com o país.
 */
function ieadc_whatsapp_limpar(string $valor): string {
	$n = preg_replace('/\D+/', '', $valor);
	if (strlen($n) === 10 || strlen($n) === 11) {
		$n = '55' . $n;
	}
	return preg_match('/^55[1-9][0-9][2-9][0-9]{7,8}$/', $n) ? $n : '';
}

/**
 * Número do WhatsApp (só dígitos, com 55): o campo `whatsapp` de Configurações; vazio, o telefone do site, que o Felipe confirmou que é WhatsApp
 * (só vale se for celular: 9 depois do DDD; um telefone fixo não recebe WhatsApp). Nada é gravado fora de Configurações.
 */
function ieadc_whatsapp_numero(): string {
	$n = ieadc_whatsapp_limpar((string) ieadc_info('whatsapp'));
	if ($n !== '') {
		return $n;
	}
	$tel = ieadc_whatsapp_limpar((string) ieadc_info('telefone'));
	return preg_match('/^55[1-9][0-9]9[0-9]{8}$/', $tel) ? $tel : '';
}

/** Link wa.me do WhatsApp da igreja, ou '' se não houver número. */
function ieadc_whatsapp_url(): string {
	$n = ieadc_whatsapp_numero();
	return $n !== '' ? 'https://wa.me/' . $n : '';
}

/* ---------- Interruptores "Site aberto ao público" e "Permitir que o Google indexe" (fase 4) ---------- */
/**
 * Estado do site, em uma palavra, lendo as duas opções de verdade:
 *  'fechado'           visitantes veem "em preparação" (ieadc_site_fechado = 1); o Google nunca indexa um site fechado;
 *  'aberto-sem-google' no ar para quem tem o link, mas com o pedido de não indexar ligado (blog_public = 0): é o estado da homologação e serve para testar com
 *                      ferramentas externas (PageSpeed, Teste de Resultados Rich, prévia do WhatsApp e do Facebook);
 *  'no-ar'             no ar e indexável (lançado).
 */
function ieadc_site_estado(): string {
	if ((bool) get_option('ieadc_site_fechado')) {
		return 'fechado';
	}
	return (bool) get_option('blog_public') ? 'no-ar' : 'aberto-sem-google';
}

/**
 * Define o estado do site: grava `ieadc_site_fechado` (aberto = 0, fechado = 1) e `blog_public` (indexável = 1). Regra: fechado nunca é indexável
 * (com $aberto falso, $indexavel vira falso). Só quem pode gerenciar opções (administrador) muda; os outros recebem false e nada muda.
 * Depois esvazia o cache de página do LiteSpeed (se existir): páginas e robots.txt em cache mostrariam o estado antigo.
 * As cargas de código (bin/implantar.sh, CARGA=codigo) não tocam nestas opções: uma atualização não desfaz o interruptor.
 */
function ieadc_site_definir(bool $aberto, bool $indexavel): bool {
	if (!current_user_can('manage_options')) {
		return false;
	}
	$indexavel = $aberto && $indexavel;
	update_option('ieadc_site_fechado', $aberto ? 0 : 1);
	update_option('blog_public', $indexavel ? 1 : 0);
	do_action('litespeed_purge_all'); // sem o plugin, ninguém escuta e nada acontece
	return true;
}

add_action('admin_post_ieadc_site_estado', function () {
	if (!current_user_can('manage_options')) {
		wp_die('Você não tem permissão para mudar o estado do site.', 'Sem permissão', ['response' => 403]);
	}
	check_admin_referer('ieadc_site_estado');
	ieadc_site_definir(!empty($_POST['aberto']), !empty($_POST['indexavel']));
	wp_safe_redirect(add_query_arg(['page' => 'ieadc-info', 'ieadc_site' => ieadc_site_estado()], admin_url('options-general.php')));
	exit;
});

/** Caixa no alto da tela de Configurações: estado de hoje em palavras e os dois interruptores. Só para administradores. */
function ieadc_site_estado_caixa(): void {
	if (!current_user_can('manage_options')) {
		return;
	}
	$estado = ieadc_site_estado();
	$hoje = [
		'fechado'           => 'Hoje: fechado. Visitantes veem a página "em preparação" e o Google não indexa; quem tem login vê o site normalmente.',
		'aberto-sem-google' => 'Hoje: aberto, fora do Google. O site está no ar para quem tem o link, mas o Google ainda não indexa (bom para testar com ferramentas externas).',
		'no-ar'             => 'Hoje: no ar e indexável. O site está aberto ao público e liberado para o Google.',
	];
	$feito = isset($_GET['ieadc_site']) ? sanitize_key((string) $_GET['ieadc_site']) : '';
	if (isset($hoje[$feito])) {
		echo '<div class="notice notice-success is-dismissible"><p>Estado do site atualizado. ' . esc_html($hoje[$feito]) . '</p></div>';
	}
	echo '<div class="card" style="max-width:none;margin:16px 0"><h2 style="margin-top:0">Estado do site</h2>'
		. '<p id="ieadc-site-hoje" role="status"><strong>' . esc_html($hoje[$estado]) . '</strong></p>'
		. '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">'
		. '<input type="hidden" name="action" value="ieadc_site_estado">';
	wp_nonce_field('ieadc_site_estado');
	echo '<p><label><input type="checkbox" id="ieadc-aberto" name="aberto" value="1"' . checked($estado !== 'fechado', true, false) . '> <strong>Site aberto ao público</strong></label></p>'
		. '<p class="description">Desmarcado: visitantes veem a página "em preparação" (quem tem login vê o site) e o Google não indexa. Marcado: o site está no ar para quem tiver o link.</p>'
		. '<p><label><input type="checkbox" id="ieadc-indexavel" name="indexavel" value="1"' . checked($estado === 'no-ar', true, false) . disabled($estado === 'fechado', true, false) . '> <strong>Permitir que o Google indexe o site</strong></label></p>'
		. '<p class="description">Só vale com o site aberto (fechado nunca é indexado). Aberto e sem esta marca: o site fica no ar, mas "fora do Google" (para testar com PageSpeed, Teste de Resultados Rich e a prévia do WhatsApp e do Facebook). Aberto e com esta marca: lançado.</p>';
	submit_button('Salvar estado do site', 'secondary', 'submit', false);
	echo '</form>'
		. '<script>(function(){var a=document.getElementById("ieadc-aberto"),i=document.getElementById("ieadc-indexavel");if(!a||!i)return;'
		. 'function s(){if(!a.checked){i.checked=false;i.disabled=true;}else{i.disabled=false;}}a.addEventListener("change",s);})();</script>'
		. '</div>';
}
