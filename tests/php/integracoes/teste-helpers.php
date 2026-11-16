<?php
/**
 * Testes das integrações com plugins (mu-plugins/ieadc-core/integracoes.php), dentro do WordPress.
 * Rode com ./bin/testar.sh php. Parte do teste faz pedidos HTTP ao próprio site (serviço "wordpress" do Docker);
 * se o site não responder por esse caminho, essa parte é pulada e avisa.
 */
require dirname(__DIR__) . '/bootstrap.php';

/* ---------- LiteSpeed Cache x formulários ---------- */
echo "formulários não vão para o cache de página\n";
$motivos = [];
add_action('litespeed_control_set_nocache', function ($motivo) use (&$motivos) { $motivos[] = $motivo; });
do_shortcode('[ieadc_pedido_oracao]');
ieadc_igual(['formulario ieadc'], $motivos, '[ieadc_pedido_oracao] pede ao LiteSpeed para não cachear');
do_shortcode('[ieadc_contato]');
ieadc_igual(['formulario ieadc', 'formulario ieadc'], $motivos, '[ieadc_contato] também');
ieadc_ok(defined('DONOTCACHEPAGE') && DONOTCACHEPAGE === true, 'DONOTCACHEPAGE definida (outros plugins de cache respeitam)');

/* ---------- Expira à meia-noite ---------- */
echo "páginas com agenda expiram à meia-noite\n";
$sp = new DateTimeZone('America/Sao_Paulo');
ieadc_igual(1830, ieadc_segundos_ate_meia_noite(new DateTimeImmutable('2026-11-16 23:30:00', $sp)), '23h30: 30 min + respiro');
ieadc_igual(86430, ieadc_segundos_ate_meia_noite(new DateTimeImmutable('2026-11-16 00:00:00', $sp)), 'meia-noite em ponto: o dia inteiro');
ieadc_ok(ieadc_segundos_ate_meia_noite() > 0 && ieadc_segundos_ate_meia_noite() <= 86430, 'agora: entre 1 s e um dia');

/* ---------- Cadastrar/apagar evento limpa o cache ---------- */
echo "evento limpa o cache de página\n";
$purgas = 0;
add_action('litespeed_purge_all', function () use (&$purgas) { $purgas++; });
$GLOBALS['ieadc_cache_agenda_purgado'] = false;
$id = wp_insert_post(['post_type' => 'evento', 'post_status' => 'publish', 'post_title' => 'Teste de integração (apagar)', 'meta_input' => ['evento_data' => '2099-01-01']], true);
ieadc_ok(!is_wp_error($id) && $purgas === 1, 'publicar evento limpa o cache uma vez', [$purgas, is_wp_error($id) ? $id->get_error_message() : $id]);
$GLOBALS['ieadc_cache_agenda_purgado'] = false;
wp_update_post(['ID' => $id, 'post_status' => 'draft']);
ieadc_ok($purgas === 2, 'despublicar limpa de novo', $purgas);
$GLOBALS['ieadc_cache_agenda_purgado'] = false;
wp_delete_post($id, true);
ieadc_ok($purgas === 3, 'apagar limpa de novo', $purgas);
$GLOBALS['ieadc_cache_agenda_purgado'] = false;
$outro = wp_insert_post(['post_type' => 'post', 'post_status' => 'draft', 'post_title' => 'Rascunho de teste (apagar)']);
wp_delete_post($outro, true);
ieadc_igual(3, $purgas, 'salvar um artigo (outro tipo) não dispara a limpeza do evento');

/* ---------- Pedidos HTTP ao próprio site ---------- */
echo "cabeçalhos das páginas (HTTP)\n";
$base = getenv('IEADC_TESTE_URL') ?: 'http://wordpress';
$resp = wp_remote_get($base . '/', ['timeout' => 20, 'redirection' => 0]);
if (is_wp_error($resp)) {
	echo '  pulou: sem acesso HTTP ao site em ' . $base . ' (' . $resp->get_error_message() . ")\n";
} else {
	$cab = function (string $caminho) use ($base) {
		$r = wp_remote_get($base . $caminho, ['timeout' => 20, 'redirection' => 0]);
		return is_wp_error($r) ? ['erro' => $r->get_error_message()] : ['status' => wp_remote_retrieve_response_code($r), 'cache' => (string) wp_remote_retrieve_header($r, 'x-ieadc-cache'), 'controle' => (string) wp_remote_retrieve_header($r, 'cache-control')];
	};
	foreach (['/pedidos-de-oracao/', '/contato/'] as $c) {
		$h = $cab($c);
		ieadc_ok(($h['status'] ?? 0) === 200 && str_starts_with($h['cache'] ?? '', 'nao-cachear') && str_contains($h['controle'] ?? '', 'no-cache'), "$c: não cacheável, navegador não guarda", $h);
	}
	foreach (['/', '/eventos/'] as $c) {
		$h = $cab($c);
		ieadc_ok(($h['status'] ?? 0) === 200 && str_starts_with($h['cache'] ?? '', 'expira-meia-noite'), "$c: cache expira à meia-noite", $h);
	}
	$h = $cab('/sobre-nos/');
	ieadc_ok(($h['status'] ?? 0) === 200 && ($h['cache'] ?? '') === '', '/sobre-nos/: página comum, cache normal', $h);
}

ieadc_teste_fim();
