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
		echo '</td></tr>';
	}
	echo '</table>';
	submit_button();
	echo '</form></div>';
}
