<?php
/**
 * Formulários do site, sem plugin de terceiros.
 *
 * [ieadc_pedido_oracao]  guarda o pedido como "Pedido de oração" privado, avisa por e-mail
 *                        e apaga sozinho depois de 90 dias (política de privacidade).
 * [ieadc_contato]        envia a mensagem por e-mail e não guarda nada no site.
 *
 * Proteções: nonce, campo-isca contra robôs e limite de envios por endereço.
 */
defined('ABSPATH') || exit;

const IEADC_PEDIDO_DIAS = 90;

function ieadc_form_icone(string $tipo): string {
	$p = [
		'enviar' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'ok'     => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.8 2.8L16.5 9.5"/>',
		'erro'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v6M12 16.5v.1"/>',
	];
	return '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true">' . $p[$tipo] . '</svg>';
}

function ieadc_form_status(string $form): string {
	$s = isset($_GET['ieadc_' . $form]) ? sanitize_key($_GET['ieadc_' . $form]) : '';
	return in_array($s, ['ok', 'erro'], true) ? $s : '';
}

function ieadc_form_msgs(string $form, string $ok): string {
	$s = ieadc_form_status($form);
	if ($s === 'ok') {
		return '<div class="msg" role="status">' . ieadc_form_icone('ok') . '<span>' . esc_html($ok) . '</span></div>';
	}
	if ($s === 'erro') {
		return '<div class="msg erro" role="alert">' . ieadc_form_icone('erro') . '<span><b>Não foi possível enviar.</b> Confira os campos e tente de novo.</span></div>';
	}
	return '';
}

function ieadc_form_abre(string $acao, string $id): string {
	return '<form class="form ieadc-ui" id="' . esc_attr($id) . '" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">'
		. '<input type="hidden" name="action" value="' . esc_attr($acao) . '">'
		. '<input type="hidden" name="volta" value="' . esc_url(get_permalink()) . '">'
		. wp_nonce_field($acao, '_ieadc', false, false)
		// Campo-isca: pessoas não veem; robôs preenchem.
		. '<div aria-hidden="true" style="position:absolute;left:-9999px"><label>Não preencha<input name="site_url" tabindex="-1" autocomplete="off"></label></div>';
}

add_shortcode('ieadc_pedido_oracao', function () {
	$politica = get_privacy_policy_url() ?: home_url('/politica-de-privacidade/');
	$h  = ieadc_form_abre('ieadc_pedido', 'pedido');
	$h .= '<div class="campo"><label for="p-nome">Seu nome (pode ser só o primeiro nome)</label><input id="p-nome" name="nome" required maxlength="80" autocomplete="given-name"></div>';
	$h .= '<div class="campo"><label for="p-pedido">Seu pedido</label><textarea id="p-pedido" name="pedido" required maxlength="3000"></textarea></div>';
	$h .= '<p class="note">Evite citar nomes ou detalhes de outras pessoas sem que elas saibam.</p>';
	$h .= '<fieldset class="campo"><legend>Quer que alguém da igreja entre em contato com você?</legend><div class="opcoes"><label><input type="radio" name="quer_contato" value="sim">Sim</label><label><input type="radio" name="quer_contato" value="nao" checked>Não</label></div></fieldset>';
	$h .= '<div class="campo"><label for="p-fone">Telefone ou e-mail (só se quiser contato)</label><input id="p-fone" name="contato" maxlength="120"></div>';
	$h .= '<label class="aceite" for="p-ok"><input type="checkbox" id="p-ok" name="aceite" value="1" required><span>Autorizo a IEADC Vila Ipiranga a receber este pedido para oração, conforme a <a href="' . esc_url($politica) . '" style="color:var(--royal);text-decoration:underline">política de privacidade</a>.</span></label>';
	$h .= '<div><button class="btn" type="submit">Enviar pedido' . ieadc_form_icone('enviar') . '</button></div>';
	$h .= ieadc_form_msgs('pedido', 'Recebemos o seu pedido. Estaremos orando por você.');
	return $h . '</form>';
});

add_shortcode('ieadc_contato', function () {
	$h  = ieadc_form_abre('ieadc_contato', 'contato');
	$h .= '<div><span class="eyebrow">Formulário</span><h2 class="h2" style="margin-top:8px">Mande uma mensagem</h2><svg class="swoosh" viewBox="0 0 260 16" preserveAspectRatio="none" aria-hidden="true"><path d="M3 12C70 3 170 2 257 8"/></svg></div>';
	$h .= '<div class="campo"><label for="c-nome">Nome</label><input id="c-nome" name="nome" required maxlength="80" autocomplete="name"></div>';
	$h .= '<div class="campo"><label for="c-contato">E-mail ou telefone</label><input id="c-contato" name="contato" required maxlength="120"></div>';
	$h .= '<div class="campo"><label for="c-msg">Mensagem</label><textarea id="c-msg" name="mensagem" required maxlength="3000"></textarea></div>';
	$h .= '<div><button class="btn" type="submit">Enviar mensagem' . ieadc_form_icone('enviar') . '</button></div>';
	$h .= ieadc_form_msgs('contato', 'Mensagem enviada. Obrigado pelo contato! Vamos responder assim que possível.');
	return $h . '</form>';
});

/** Valida nonce, isca e limite (5 envios por hora por endereço). Volta com erro se falhar. */
function ieadc_form_checa(string $acao, string $form): string {
	$volta = wp_validate_redirect(esc_url_raw(wp_unslash($_POST['volta'] ?? '')), home_url('/'));
	$falha = fn() => wp_safe_redirect(add_query_arg('ieadc_' . $form, 'erro', $volta) . '#' . $form) && exit;

	if (!wp_verify_nonce($_POST['_ieadc'] ?? '', $acao) || !empty($_POST['site_url'])) {
		$falha();
	}
	$chave = 'ieadc_lim_' . md5(($_SERVER['REMOTE_ADDR'] ?? '') . wp_salt());
	$n = (int) get_transient($chave);
	if ($n >= 5) {
		$falha();
	}
	set_transient($chave, $n + 1, HOUR_IN_SECONDS);
	return $volta;
}

function ieadc_campo(string $nome, bool $longo = false): string {
	$v = wp_unslash($_POST[$nome] ?? '');
	return trim($longo ? sanitize_textarea_field($v) : sanitize_text_field($v));
}

add_action('admin_post_nopriv_ieadc_pedido', 'ieadc_recebe_pedido');
add_action('admin_post_ieadc_pedido', 'ieadc_recebe_pedido');
function ieadc_recebe_pedido(): void {
	$volta  = ieadc_form_checa('ieadc_pedido', 'pedido');
	$nome   = ieadc_campo('nome');
	$pedido = ieadc_campo('pedido', true);
	$quer   = ieadc_campo('quer_contato') === 'sim';
	$cont   = ieadc_campo('contato');
	if ($nome === '' || $pedido === '' || empty($_POST['aceite'])) {
		wp_safe_redirect(add_query_arg('ieadc_pedido', 'erro', $volta) . '#pedido');
		exit;
	}
	$id = wp_insert_post([
		'post_type'    => 'pedido_oracao',
		'post_status'  => 'private',
		'post_title'   => sprintf('%s · %s', $nome, wp_date('d/m/Y H:i')),
		'post_content' => $pedido,
		'meta_input'   => ['quer_contato' => $quer ? 'sim' : 'não', 'contato' => $cont],
	], true);

	$para = ieadc_info('email_pedidos') ?: get_option('admin_email');
	$corpo = "Novo pedido de oração pelo site.\n\nNome: {$nome}\nQuer contato: " . ($quer ? 'sim' : 'não')
		. ($cont ? "\nContato: {$cont}" : '') . "\n\nPedido:\n{$pedido}\n\n"
		. 'Este pedido fica no painel (Pedidos de oração) e é apagado sozinho em ' . IEADC_PEDIDO_DIAS . ' dias.';
	wp_mail($para, 'Pedido de oração pelo site', $corpo);

	wp_safe_redirect(add_query_arg('ieadc_pedido', is_wp_error($id) ? 'erro' : 'ok', $volta) . '#pedido');
	exit;
}

add_action('admin_post_nopriv_ieadc_contato', 'ieadc_recebe_contato');
add_action('admin_post_ieadc_contato', 'ieadc_recebe_contato');
function ieadc_recebe_contato(): void {
	$volta = ieadc_form_checa('ieadc_contato', 'contato');
	$nome  = ieadc_campo('nome');
	$cont  = ieadc_campo('contato');
	$msg   = ieadc_campo('mensagem', true);
	if ($nome === '' || $cont === '' || $msg === '') {
		wp_safe_redirect(add_query_arg('ieadc_contato', 'erro', $volta) . '#contato');
		exit;
	}
	$para = ieadc_info('email_contato') ?: get_option('admin_email');
	$cab  = is_email($cont) ? ['Reply-To: ' . $nome . ' <' . $cont . '>'] : [];
	$ok   = wp_mail($para, 'Fale conosco pelo site: ' . $nome, "Nome: {$nome}\nContato: {$cont}\n\n{$msg}", $cab);
	wp_safe_redirect(add_query_arg('ieadc_contato', $ok ? 'ok' : 'erro', $volta) . '#contato');
	exit;
}

/* Limpeza diária: pedidos com mais de 90 dias são apagados de vez. */
add_action('init', function () {
	if (wp_installing() || !is_blog_installed()) {
		return;
	}
	if (!wp_next_scheduled('ieadc_limpa_pedidos')) {
		wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'ieadc_limpa_pedidos');
	}
});
add_action('ieadc_limpa_pedidos', function () {
	$velhos = get_posts([
		'post_type'      => 'pedido_oracao',
		'post_status'    => 'any',
		'fields'         => 'ids',
		'posts_per_page' => 200,
		'date_query'     => [['before' => IEADC_PEDIDO_DIAS . ' days ago']],
	]);
	foreach ($velhos as $id) {
		wp_delete_post($id, true);
	}
});

/* No painel, mostra contato e escolha da pessoa ao abrir o pedido. */
add_action('add_meta_boxes_pedido_oracao', function () {
	add_meta_box('ieadc-pedido-contato', 'Contato', function (WP_Post $p) {
		echo '<p><b>Quer contato:</b> ' . esc_html(get_post_meta($p->ID, 'quer_contato', true) ?: 'não') . '</p>';
		$c = get_post_meta($p->ID, 'contato', true);
		echo $c ? '<p><b>Telefone ou e-mail:</b> ' . esc_html($c) . '</p>' : '';
		echo '<p class="description">Apagado automaticamente ' . (int) IEADC_PEDIDO_DIAS . ' dias depois do envio.</p>';
	}, 'pedido_oracao', 'side');
});
