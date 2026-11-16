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
	];
	$ajuda = [
		'google_avaliacao' => 'Cole o link de avaliação do Perfil da Empresa no Google (ou search.google.com/local/writereview?placeid=…; vale também só o código do lugar, que começa com ChIJ). Vazio: o botão "Avaliar no Google" abre a igreja no Google Maps.',
		'latitude' => 'Opcional. Ajuda o Google a achar a igreja no mapa (exemplo: -25.4999). Deixe vazio se não souber.',
		'longitude' => 'Opcional (exemplo: -49.2999). Só vale se a latitude também estiver preenchida.',
	];
	$v = ieadc_info();
	echo '<div class="wrap"><h1>IEADC Vila Ipiranga</h1><p>Dados usados no cabeçalho, no rodapé e nos formulários. A fonte é a aba Informações gerais da documentação da Comunicação.</p><form method="post" action="options.php">';
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
	return esc_url_raw($valor, ['https']);
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

/** Link do botão "Avaliar no Google": o de Configurações > IEADC Vila Ipiranga; sem ele, a igreja no Google Maps. */
function ieadc_google_avaliacao_url(): string {
	$link = trim((string) ieadc_info('google_avaliacao'));
	return $link !== '' ? $link : ieadc_google_maps_url();
}
