<?php
/**
 * Integrações com os plugins que ficam instalados no site (docs/operacao/plugins.md).
 *
 * Tudo aqui é inofensivo quando o plugin não está instalado: só dispara ganchos (do_action) que ninguém escuta.
 *
 * - LiteSpeed Cache (e qualquer cache de página que respeite DONOTCACHEPAGE):
 *     1. páginas com formulário (pedido de oração, contato) não vão para o cache, porque o nonce do formulário vence
 *        e o envio falharia ("link expirado");
 *     2. páginas com agenda (calendário, próximos eventos, próximas datas) expiram à meia-noite de São Paulo, para
 *        mostrar sempre o mês e os dias de hoje;
 *     3. cadastrar, editar, apagar ou despublicar um evento limpa o cache de página (a agenda muda na hora).
 * - Yoast SEO: título, descrição, Open Graph e imagem do tema chegam ao Yoast pelos filtros de wp-content/themes/
 *   ieadc-vila-ipiranga/inc/seo.php (sem duplicar tags).
 * - UpdraftPlus (agendamento de backup): scripts/seed/90-configuracoes.php.
 *
 * Para depurar, as respostas trazem o cabeçalho X-Ieadc-Cache com a decisão tomada nesta página.
 */
defined('ABSPATH') || exit;

/** Blocos e shortcodes que renderizam formulário com nonce. */
function ieadc_integracoes_formularios(): array {
	return [
		'shortcodes' => ['ieadc_pedido_oracao', 'ieadc_contato'],
		'blocos'     => ['ieadc/pedido-oracao', 'ieadc/contato-lista'],
	];
}

/** Blocos cujo conteúdo depende do dia de hoje (agenda). */
function ieadc_integracoes_blocos_agenda(): array {
	return ['ieadc/calendario', 'ieadc/eventos-grade', 'ieadc/corpo-indice'];
}

/**
 * Marca a resposta atual como não cacheável: constante DONOTCACHEPAGE (WP Super Cache, W3TC, WP Rocket, LiteSpeed...)
 * e o gancho oficial do LiteSpeed Cache. Pode ser chamada no meio da renderização.
 */
function ieadc_nao_cachear(string $motivo = 'formulario ieadc'): void {
	if (!defined('DONOTCACHEPAGE')) {
		define('DONOTCACHEPAGE', true);
	}
	do_action('litespeed_control_set_nocache', $motivo);
	if (!headers_sent()) {
		header('X-Ieadc-Cache: nao-cachear (' . $motivo . ')');
	}
}

/** Segundos até a próxima meia-noite em São Paulo (mais um respiro, para a página nova já ser a do dia seguinte). */
function ieadc_segundos_ate_meia_noite(?DateTimeImmutable $agora = null): int {
	$agora ??= new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
	$amanha = $agora->modify('tomorrow'); // 00:00 do dia seguinte
	return max(60, $amanha->getTimestamp() - $agora->getTimestamp() + 30);
}

/** O conteúdo do post tem algum destes blocos ou shortcodes? */
function ieadc_integracoes_tem(WP_Post $post, array $blocos, array $shortcodes = []): bool {
	foreach ($blocos as $b) {
		if (has_block($b, $post)) {
			return true;
		}
	}
	foreach ($shortcodes as $s) {
		if (has_shortcode($post->post_content, $s)) {
			return true;
		}
	}
	return false;
}

add_action('template_redirect', function () {
	if (!is_singular() || is_preview()) {
		return;
	}
	$post = get_queried_object();
	if (!$post instanceof WP_Post) {
		return;
	}
	$f = ieadc_integracoes_formularios();
	if (ieadc_integracoes_tem($post, $f['blocos'], $f['shortcodes'])) {
		// Navegador e cache intermediário também não guardam: o nonce do formulário vale poucas horas.
		nocache_headers();
		ieadc_nao_cachear('formulario ieadc');
		return;
	}
	if (ieadc_integracoes_tem($post, ieadc_integracoes_blocos_agenda())) {
		$ttl = ieadc_segundos_ate_meia_noite();
		do_action('litespeed_control_set_ttl', $ttl, 'agenda do dia');
		if (!headers_sent()) {
			header('X-Ieadc-Cache: expira-meia-noite (' . $ttl . 's)');
		}
	}
}, 5);

/* A agenda muda quando um evento é criado, editado, despublicado ou apagado: limpa o cache de página (uma vez por pedido). */
function ieadc_purgar_cache_agenda(): void {
	if (!empty($GLOBALS['ieadc_cache_agenda_purgado'])) {
		return; // $GLOBALS['ieadc_cache_agenda_purgado'] = false reinicia (testes)
	}
	$GLOBALS['ieadc_cache_agenda_purgado'] = true;
	do_action('litespeed_purge_all');
}
add_action('save_post_evento', static function ($id) {
	if (!wp_is_post_autosave($id) && !wp_is_post_revision($id)) {
		ieadc_purgar_cache_agenda();
	}
});
add_action('transition_post_status', static function ($novo, $antigo, $post) {
	if ($post instanceof WP_Post && $post->post_type === 'evento' && $novo !== $antigo) {
		ieadc_purgar_cache_agenda();
	}
}, 10, 3);
foreach (['before_delete_post', 'trashed_post', 'untrashed_post'] as $gancho) {
	add_action($gancho, static function ($id) {
		if (get_post_type($id) === 'evento') {
			ieadc_purgar_cache_agenda();
		}
	});
}
