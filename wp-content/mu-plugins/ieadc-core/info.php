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
		'email'      => 'robsondouglass@terra.com.br',
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
		'google_avaliacao' => '',
		'latitude'   => '',
		'longitude'  => '',
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
		'sanitize_callback' => function ($v) {
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
		},
	]);
});

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
		'search_console' => 'Código de verificação (ou a meta tag inteira) do Google Search Console, método "Tag HTML". Vazio: nada é impresso. Não usa cookie.',
		'bing_verificacao' => 'Código do Bing Webmaster Tools (32 letras e números, ou a meta tag inteira). Vazio: nada é impresso.',
		'clarity_id' => 'Código do projeto no Microsoft Clarity (10 letras e números). Vazio: não é usado. Só carrega depois que a pessoa aceita o aviso de cookies.',
		'meta_pixel_id' => 'Número do Meta Pixel (só dígitos). Vazio: não é usado. Só carrega depois que a pessoa aceita o aviso de cookies e muda a política de privacidade.',
	];
	$v = ieadc_info();
	echo '<div class="wrap"><h1>IEADC Vila Ipiranga</h1>';
	ieadc_site_aberto_caixa();
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

/* ---------- Interruptor "Site aberto ao público" (fase 4) ---------- */
/**
 * Estado do site, em uma palavra: 'fechado' (visitantes veem "em preparação" e o Google não indexa), 'aberto' (no ar e liberado para o Google) ou
 * 'aberto-sem-google' (no ar, mas com o pedido para o Google não indexar ligado: é o estado da homologação). Lê as duas opções de verdade.
 */
function ieadc_site_estado(): string {
	if ((bool) get_option('ieadc_site_fechado')) {
		return 'fechado';
	}
	return (bool) get_option('blog_public') ? 'aberto' : 'aberto-sem-google';
}

/**
 * Abre ou fecha o site para o público: grava `ieadc_site_fechado` (aberto = 0, fechado = 1) e `blog_public` (aberto = 1, fechado = 0) juntos.
 * Só quem pode gerenciar opções (administrador) consegue; devolve false (e não muda nada) para os outros. Quem tem login continua vendo o site fechado.
 * As cargas de código (bin/implantar.sh, CARGA=codigo) não tocam nestas opções: o interruptor não é desfeito por uma atualização.
 */
function ieadc_site_definir_aberto(bool $aberto): bool {
	if (!current_user_can('manage_options')) {
		return false;
	}
	update_option('ieadc_site_fechado', $aberto ? 0 : 1);
	update_option('blog_public', $aberto ? 1 : 0);
	do_action('litespeed_purge_all'); // o cache de página não pode guardar a página "em preparação" nem o site aberto por engano
	return true;
}

add_action('admin_post_ieadc_site_aberto', function () {
	if (!current_user_can('manage_options')) {
		wp_die('Você não tem permissão para abrir ou fechar o site.', 'Sem permissão', ['response' => 403]);
	}
	check_admin_referer('ieadc_site_aberto');
	$aberto = !empty($_POST['aberto']);
	ieadc_site_definir_aberto($aberto);
	wp_safe_redirect(add_query_arg(['page' => 'ieadc-info', 'ieadc_site' => $aberto ? 'aberto' : 'fechado'], admin_url('options-general.php')));
	exit;
});

/** Caixa no alto da tela de Configurações: estado de hoje em palavras e o interruptor. Só para administradores. */
function ieadc_site_aberto_caixa(): void {
	if (!current_user_can('manage_options')) {
		return;
	}
	$estado = ieadc_site_estado();
	$hoje = [
		'fechado'           => 'Hoje: FECHADO. Visitantes veem a página "em preparação" e o Google não indexa. Quem tem login vê o site normalmente.',
		'aberto'            => 'Hoje: ABERTO ao público e liberado para o Google.',
		'aberto-sem-google' => 'Hoje: ABERTO ao público, mas o Google ainda não indexa (o pedido de não indexar está ligado). Marcar a caixa abaixo e salvar libera o Google.',
	][$estado];
	$feito = isset($_GET['ieadc_site']) ? sanitize_key((string) $_GET['ieadc_site']) : '';
	if ($feito === 'aberto' || $feito === 'fechado') {
		echo '<div class="notice notice-success is-dismissible"><p>Estado do site atualizado: ' . ($feito === 'aberto' ? 'aberto ao público.' : 'fechado.') . '</p></div>';
	}
	echo '<div class="card" style="max-width:none;margin:16px 0"><h2 style="margin-top:0">Site aberto ao público</h2>'
		. '<p id="ieadc-site-hoje" role="status"><strong>' . esc_html($hoje) . '</strong></p>'
		. '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">'
		. '<input type="hidden" name="action" value="ieadc_site_aberto">';
	wp_nonce_field('ieadc_site_aberto');
	echo '<p><label><input type="checkbox" name="aberto" value="1"' . checked($estado !== 'fechado', true, false) . '> Site aberto ao público</label></p>'
		. '<p class="description">Desmarcado = visitantes veem a página "em preparação" e o Google não indexa. Marcado = no ar e liberado para o Google. '
		. 'Quem tem login vê o site em qualquer dos dois estados (é assim que a equipe confere tudo antes de abrir). Uma atualização do código do site não muda este interruptor.</p>';
	submit_button('Salvar estado do site', 'secondary', 'submit', false);
	echo '</form></div>';
}
