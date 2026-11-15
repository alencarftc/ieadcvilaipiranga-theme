<?php
/**
 * Tipos de conteúdo do site.
 * - Eventos (públicos): data, horário e local em campos próprios.
 * - Mensagens (públicas): pregações e estudos em vídeo ou áudio.
 * - Pedidos de oração (privados): só a equipe de oração vê no painel; apagados após 90 dias.
 */
defined('ABSPATH') || exit;

add_action('init', function () {
	register_post_type('evento', [
		'labels'        => [
			'name' => 'Eventos', 'singular_name' => 'Evento', 'add_new_item' => 'Novo evento',
			'edit_item' => 'Editar evento', 'all_items' => 'Todos os eventos', 'menu_name' => 'Eventos',
		],
		'public'        => true,
		'has_archive'   => false, // a página "Eventos" lista os eventos
		'rewrite'       => ['slug' => 'eventos/evento', 'with_front' => false],
		'menu_icon'     => 'dashicons-calendar-alt',
		'menu_position' => 5,
		'show_in_rest'  => true,
		'supports'      => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
	]);

	foreach (['evento_data' => 'string', 'evento_data_fim' => 'string', 'evento_horario' => 'string', 'evento_local' => 'string'] as $chave => $tipo) {
		register_post_meta('evento', $chave, ['type' => $tipo, 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field']);
	}

	register_post_type('mensagem', [
		'labels'        => [
			'name' => 'Mensagens', 'singular_name' => 'Mensagem', 'add_new_item' => 'Nova mensagem',
			'edit_item' => 'Editar mensagem', 'all_items' => 'Todas as mensagens', 'menu_name' => 'Mensagens',
		],
		'public'        => true,
		'has_archive'   => 'mensagens',
		'rewrite'       => ['slug' => 'mensagens', 'with_front' => false],
		'menu_icon'     => 'dashicons-microphone',
		'menu_position' => 6,
		'show_in_rest'  => true,
		'supports'      => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'],
	]);
	register_post_meta('mensagem', 'mensagem_pregador', ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field']);
	register_post_meta('mensagem', 'mensagem_url', ['type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'esc_url_raw']);

	register_post_type('pedido_oracao', [
		'labels'              => [
			'name' => 'Pedidos de oração', 'singular_name' => 'Pedido de oração',
			'edit_item' => 'Ver pedido', 'all_items' => 'Pedidos recebidos', 'menu_name' => 'Pedidos de oração',
		],
		'public'              => false,
		'show_ui'             => true,
		'show_in_rest'        => false,
		'exclude_from_search' => true,
		'menu_icon'           => 'dashicons-heart',
		'menu_position'       => 7,
		'supports'            => ['title', 'editor'],
		'capability_type'     => 'pedido_oracao',
		'map_meta_cap'        => true,
		'capabilities'        => ['create_posts' => 'do_not_allow'], // só chegam pelo formulário
	]);
});

/* Quem pode ler pedidos: administradores e o papel "Equipe de oração". */
function ieadc_caps_pedidos(): array {
	return [
		'edit_pedido_oracaos', 'edit_others_pedido_oracaos', 'read_private_pedido_oracaos',
		'delete_pedido_oracaos', 'delete_others_pedido_oracaos', 'delete_private_pedido_oracaos',
		'edit_private_pedido_oracaos', 'delete_published_pedido_oracaos', 'edit_published_pedido_oracaos',
	];
}

function ieadc_instalar_papeis(): void {
	if (wp_installing() || !is_blog_installed()) {
		return;
	}
	if (get_option('ieadc_papeis_versao') === '1') {
		return;
	}
	$admin = get_role('administrator');
	foreach (ieadc_caps_pedidos() as $c) {
		$admin?->add_cap($c);
	}
	remove_role('equipe_oracao');
	add_role('equipe_oracao', 'Equipe de oração', array_fill_keys(array_merge(['read'], ieadc_caps_pedidos()), true));
	update_option('ieadc_papeis_versao', '1');
}
add_action('init', 'ieadc_instalar_papeis', 20);
