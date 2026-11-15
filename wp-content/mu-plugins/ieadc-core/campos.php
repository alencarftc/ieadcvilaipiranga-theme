<?php
/**
 * Campos do conteúdo que muda, com caixas no painel (sem plugin de campos).
 * - Evento: caixa "Quando e onde" (data, data final, horário, local, categoria, link).
 * - Ministério (página filha de "ministerios"): caixa "Cartão do ministério".
 * - Culto fixo (as páginas de ieadc_paginas_culto()): caixa "Cartão do culto".
 * - Artigo (post): caixa "Artigo" (versículo de abertura, referência e selo "Exemplo").
 * Todos os campos aparecem na REST API.
 */
defined('ABSPATH') || exit;

/** Cores dos cartões (classe CSS do protótipo). */
function ieadc_cores_cartao(): array {
	return ['c1' => 'Cor 1', 'c2' => 'Cor 2', 'c3' => 'Cor 3', 'c4' => 'Cor 4', 'c5' => 'Cor 5', 'c6' => 'Cor 6', 'c7' => 'Cor 7'];
}

/** Slugs das páginas de culto fixo (filtrável). */
function ieadc_paginas_culto(): array {
	return apply_filters('ieadc_paginas_culto', [
		'culto-de-oracao', 'culto-de-ensino', 'culto-do-circulo-de-oracao',
		'culto-dominical', 'ceia-do-senhor', 'vigilia-e-consagracao',
	]);
}

function ieadc_eh_ministerio(WP_Post $p): bool {
	return $p->post_type === 'page' && $p->post_parent && get_post_field('post_name', $p->post_parent) === 'ministerios';
}

function ieadc_eh_culto(WP_Post $p): bool {
	return $p->post_type === 'page' && in_array($p->post_name, ieadc_paginas_culto(), true);
}

/** Ícones disponíveis no tema (inc/icones.php), para a lista do painel. */
function ieadc_icones_disponiveis(): array {
	$nomes = function_exists('ieadc_icones') ? array_keys((array) ieadc_icones()) : [];
	return $nomes ?: ['oracao', 'livro', 'beca', 'casa', 'globo', 'pessoas', 'crianca', 'igreja', 'calice', 'chama'];
}

/* ---------- Registro dos campos ---------- */

add_action('init', function () {
	$texto = ['type' => 'string', 'single' => true, 'show_in_rest' => true];
	register_post_meta('page', 'ieadc_icone', $texto + ['sanitize_callback' => 'sanitize_key']);
	register_post_meta('page', 'ieadc_resumo', $texto + ['sanitize_callback' => 'sanitize_text_field']);
	register_post_meta('page', 'ieadc_quando', $texto + ['sanitize_callback' => 'sanitize_text_field']);
	register_post_meta('page', 'ieadc_cor', $texto + ['sanitize_callback' => fn($v) => array_key_exists($v, ieadc_cores_cartao()) ? $v : '']);
	register_post_meta('page', 'ieadc_regra', $texto + ['sanitize_callback' => 'sanitize_textarea_field']);

	register_post_meta('post', 'ieadc_versiculo', $texto + ['sanitize_callback' => 'ieadc_kses_versiculo']);
	register_post_meta('post', 'ieadc_referencia', $texto + ['sanitize_callback' => 'sanitize_text_field']);
	register_post_meta('post', 'ieadc_exemplo', [
		'type' => 'boolean', 'single' => true, 'default' => false, 'show_in_rest' => true,
		'sanitize_callback' => 'rest_sanitize_boolean',
	]);
});

/** Versículo: texto simples com <b>, <i> e <br> (o bloco artigo-corpo mostra com ieadc_kses_rico). */
function ieadc_kses_versiculo($v): string {
	return trim(wp_kses((string) $v, ['b' => [], 'strong' => [], 'i' => [], 'em' => [], 'br' => []]));
}

/* ---------- Caixas do painel ---------- */

add_action('add_meta_boxes', function ($tipo, $p) {
	if ($tipo === 'evento') {
		add_meta_box('ieadc-evento', 'Quando e onde', 'ieadc_caixa_evento', 'evento', 'side', 'high');
	} elseif ($tipo === 'post') {
		add_meta_box('ieadc-artigo', 'Artigo', 'ieadc_caixa_artigo', 'post', 'side', 'high');
	} elseif ($tipo === 'page' && $p instanceof WP_Post) {
		if (ieadc_eh_ministerio($p)) {
			add_meta_box('ieadc-cartao', 'Cartão do ministério', 'ieadc_caixa_cartao', 'page', 'side', 'high', ['culto' => false]);
		} elseif (ieadc_eh_culto($p)) {
			add_meta_box('ieadc-cartao', 'Cartão do culto', 'ieadc_caixa_cartao', 'page', 'side', 'high', ['culto' => true]);
		}
	}
}, 10, 2);

/** Campo de uma linha, área de texto ou lista suspensa. */
function ieadc_painel_campo(string $nome, string $rotulo, string $valor, array $opts = []): void {
	$id = 'ieadc-' . str_replace('_', '-', $nome);
	echo '<p style="margin:0 0 12px"><label for="' . esc_attr($id) . '"><strong>' . esc_html($rotulo) . '</strong></label><br>';
	if (isset($opts['opcoes'])) {
		echo '<select id="' . esc_attr($id) . '" name="' . esc_attr($nome) . '" style="width:100%">';
		foreach ($opts['opcoes'] as $v => $r) {
			echo '<option value="' . esc_attr($v) . '"' . selected($valor, (string) $v, false) . '>' . esc_html($r) . '</option>';
		}
		echo '</select>';
	} elseif (!empty($opts['linhas'])) {
		echo '<textarea id="' . esc_attr($id) . '" name="' . esc_attr($nome) . '" rows="' . (int) $opts['linhas'] . '" style="width:100%">' . esc_textarea($valor) . '</textarea>';
	} else {
		echo '<input id="' . esc_attr($id) . '" name="' . esc_attr($nome) . '" type="' . esc_attr($opts['tipo'] ?? 'text') . '" value="' . esc_attr($valor) . '" style="width:100%"'
			. (isset($opts['dica']) ? ' placeholder="' . esc_attr($opts['dica']) . '"' : '') . '>';
	}
	if (isset($opts['ajuda'])) {
		echo '<span class="description">' . esc_html($opts['ajuda']) . '</span>';
	}
	echo '</p>';
}

function ieadc_caixa_evento(WP_Post $p): void {
	wp_nonce_field('ieadc_campos_salvar', 'ieadc_campos_nonce');
	$m = fn($k) => (string) get_post_meta($p->ID, $k, true);
	ieadc_painel_campo('evento_data', 'Data', $m('evento_data'), ['tipo' => 'date']);
	ieadc_painel_campo('evento_data_fim', 'Até (opcional)', $m('evento_data_fim'), ['tipo' => 'date', 'ajuda' => 'Para eventos de mais de um dia.']);
	ieadc_painel_campo('evento_horario', 'Horário', $m('evento_horario'), ['dica' => '19h30']);
	ieadc_painel_campo('evento_local', 'Local', $m('evento_local'), ['dica' => 'No templo da igreja']);
	ieadc_painel_campo('evento_categoria', 'Categoria (cor na agenda)', $m('evento_categoria') ?: 'c-culto', ['opcoes' => ieadc_evento_categorias()]);
	ieadc_painel_campo('evento_link', 'Link (opcional)', $m('evento_link'), ['dica' => 'ministerios/mocidade', 'ajuda' => 'Página do site (ex.: ministerios/mocidade) ou endereço completo. Vazio = página do evento.']);
}

function ieadc_caixa_artigo(WP_Post $p): void {
	wp_nonce_field('ieadc_campos_salvar', 'ieadc_campos_nonce');
	$m = fn($k) => (string) get_post_meta($p->ID, $k, true);
	ieadc_painel_campo('ieadc_versiculo', 'Versículo de abertura', $m('ieadc_versiculo'), ['linhas' => 4, 'ajuda' => 'Aparece em destaque antes do texto. Vazio = sem versículo.']);
	ieadc_painel_campo('ieadc_referencia', 'Referência', $m('ieadc_referencia'), ['dica' => 'Tiago 5.16 (ARA)']);
	echo '<input type="hidden" name="ieadc_exemplo_presente" value="1">';
	echo '<label><input type="checkbox" name="ieadc_exemplo" value="1"' . checked((bool) get_post_meta($p->ID, 'ieadc_exemplo', true), true, false) . '> Mostrar o selo "Exemplo"</label>';
}

function ieadc_caixa_cartao(WP_Post $p, array $caixa): void {
	wp_nonce_field('ieadc_campos_salvar', 'ieadc_campos_nonce');
	$culto = !empty($caixa['args']['culto']);
	$m = fn($k) => (string) get_post_meta($p->ID, $k, true);
	$icones = ['' => '(nenhum)'] + array_combine(ieadc_icones_disponiveis(), ieadc_icones_disponiveis());
	ieadc_painel_campo('ieadc_icone', 'Ícone', $m('ieadc_icone'), ['opcoes' => $icones]);
	ieadc_painel_campo('ieadc_resumo', 'Resumo', $m('ieadc_resumo'), ['linhas' => 3, 'ajuda' => $culto ? 'Aparece no cartão do culto.' : 'Uma frase. Aparece no cartão do ministério.']);
	ieadc_painel_campo('ieadc_quando', 'Quando', $m('ieadc_quando'), ['dica' => 'Quinta-feira, às 20h']);
	ieadc_painel_campo('ieadc_cor', 'Cor do cartão', $m('ieadc_cor'), ['opcoes' => ['' => '(padrão)'] + ieadc_cores_cartao()]);
	if ($culto) {
		ieadc_painel_campo('ieadc_regra', 'Títulos na agenda', $m('ieadc_regra'), ['linhas' => 3, 'ajuda' => 'Um por linha, como aparecem na agenda (ex.: Vigília). Serve para listar as próximas datas.']);
	}
	echo '<p class="description" style="margin:0">A foto do cartão é a imagem destacada da página.</p>';
}

/* ---------- Gravação ---------- */

add_action('save_post', function ($id, $p) {
	if (!isset($_POST['ieadc_campos_nonce']) || !wp_verify_nonce(sanitize_key($_POST['ieadc_campos_nonce']), 'ieadc_campos_salvar')) {
		return;
	}
	if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($id) || !current_user_can('edit_post', $id)) {
		return;
	}
	$chaves = [];
	if ($p->post_type === 'evento') {
		$chaves = ['evento_data', 'evento_data_fim', 'evento_horario', 'evento_local', 'evento_categoria', 'evento_link'];
	} elseif ($p->post_type === 'page' && (ieadc_eh_ministerio($p) || ieadc_eh_culto($p))) {
		$chaves = ['ieadc_icone', 'ieadc_resumo', 'ieadc_quando', 'ieadc_cor'];
		if (ieadc_eh_culto($p)) {
			$chaves[] = 'ieadc_regra';
		}
	} elseif ($p->post_type === 'post' && isset($_POST['ieadc_exemplo_presente'])) {
		update_post_meta($id, 'ieadc_exemplo', !empty($_POST['ieadc_exemplo']));
		foreach (['ieadc_versiculo', 'ieadc_referencia'] as $k) {
			if (isset($_POST[$k])) {
				$v = trim(wp_unslash($_POST[$k]));
				$v === '' ? delete_post_meta($id, $k) : update_post_meta($id, $k, $v);
			}
		}
	}
	foreach ($chaves as $k) {
		if (!isset($_POST[$k])) {
			continue;
		}
		$v = wp_unslash($_POST[$k]);
		$v = in_array($k, ['ieadc_resumo', 'ieadc_regra'], true) ? sanitize_textarea_field($v) : sanitize_text_field($v);
		if ($k === 'ieadc_resumo') {
			$v = trim(preg_replace('/\s+/', ' ', $v));
		}
		// O sanitize_callback do register_post_meta faz a validação final (data, categoria, cor, URL).
		update_post_meta($id, $k, $v);
	}
}, 10, 2);
