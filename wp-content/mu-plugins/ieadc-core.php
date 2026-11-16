<?php
/**
 * Plugin Name: IEADC Vila Ipiranga · Núcleo
 * Description: Dados da igreja, tipos de conteúdo (Eventos, Mensagens, Pedidos de oração) e formulários do site. Fica em mu-plugins para não ser desativado por engano.
 * Version:     0.1.0
 * Author:      Depto. de Comunicação IEADC Vila Ipiranga
 * License:     GPL-2.0-or-later
 */
defined('ABSPATH') || exit;

define('IEADC_CORE_DIR', __DIR__ . '/ieadc-core');

require IEADC_CORE_DIR . '/info.php';
require IEADC_CORE_DIR . '/tipos.php';
require IEADC_CORE_DIR . '/pedidos.php';
require IEADC_CORE_DIR . '/agenda.php';
require IEADC_CORE_DIR . '/fechado.php';
require IEADC_CORE_DIR . '/integracoes.php';
require IEADC_CORE_DIR . '/seguranca.php';

/* Só no ambiente local (Docker): os e-mails vão para o Mailpit, em http://localhost:8025. */
if (wp_get_environment_type() === 'local') {
	add_action('phpmailer_init', function ($m) {
		$m->isSMTP();
		$m->Host     = 'mailpit';
		$m->Port     = 1025;
		$m->SMTPAuth = false;
	});
	// "wordpress@localhost" é recusado como remetente; no Docker usa um endereço válido.
	add_filter('wp_mail_from', fn() => 'site@ieadc.local');
}
