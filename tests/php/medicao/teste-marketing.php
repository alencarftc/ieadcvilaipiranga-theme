<?php
/**
 * Testes de UX e marketing (fase 4): "Adicionar ao calendário" (.ics e Google Agenda), compartilhar artigo e página 404.
 * Rode com ./bin/testar.sh php. Cria eventos de teste (apagados no fim, mesmo se o teste falhar) e baixa páginas do próprio site.
 */
require dirname(__DIR__) . '/bootstrap.php';

$base = rtrim(getenv('IEADC_TESTE_URL') ?: 'http://wordpress', '/');

function ieadc_k_baixar(string $caminho): array {
	global $base;
	$r = wp_remote_get($base . $caminho, ['timeout' => 30, 'redirection' => 0, 'headers' => ['X-Ieadc-Hoje' => '2026-10-06']]);
	if (is_wp_error($r)) {
		return ['status' => 0, 'corpo' => '', 'cab' => []];
	}
	return ['status' => (int) wp_remote_retrieve_response_code($r), 'corpo' => (string) wp_remote_retrieve_body($r),
		'cab' => array_change_key_case((array) wp_remote_retrieve_headers($r)->getAll(), CASE_LOWER)];
}

$ids = [];
try {
	echo "horário do evento (texto livre)\n";
	ieadc_igual(['ini' => [19, 30], 'fim' => null], ieadc_evento_horas('19h30'), '19h30');
	ieadc_igual(['ini' => [9, 0], 'fim' => [11, 30]], ieadc_evento_horas('9h às 11h30'), '9h às 11h30');
	ieadc_igual(['ini' => [20, 0], 'fim' => null], ieadc_evento_horas('20:00'), '20:00');
	ieadc_igual(null, ieadc_evento_horas('[horário a confirmar]'), '"[horário a confirmar]" não tem hora');
	ieadc_igual(null, ieadc_evento_horas(''), 'texto vazio');

	$criar = function (array $meta, string $titulo, string $resumo = '') use (&$ids): int {
		$id = wp_insert_post(['post_type' => 'evento', 'post_status' => 'publish', 'post_title' => $titulo, 'post_excerpt' => $resumo, 'meta_input' => $meta + ['_ieadc_fixture_teste' => '1']], true);
		if (is_wp_error($id)) {
			fwrite(STDERR, $id->get_error_message() . "\n");
			exit(2);
		}
		$ids[] = $id;
		return (int) $id;
	};

	echo "arquivo .ics e Google Agenda\n";
	$a = $criar(['evento_data' => '2027-03-14', 'evento_horario' => '19h30', 'evento_local' => 'Salão, térreo; entrada principal', 'evento_categoria' => 'c-culto'], 'Culto especial de teste (apagar)', 'Resumo do evento, com vírgula; e ponto e vírgula. [Programação a confirmar]');
	$ics = ieadc_evento_ics(get_post($a));
	ieadc_ok(str_starts_with($ics, "BEGIN:VCALENDAR\r\n") && str_ends_with($ics, "END:VCALENDAR\r\n"), 'o .ics começa e termina como o formato manda, com CRLF');
	ieadc_ok(str_contains($ics, "DTSTART;TZID=America/Sao_Paulo:20270314T193000\r\n") && str_contains($ics, "DTEND;TZID=America/Sao_Paulo:20270314T203000\r\n"), 'evento com hora: início às 19h30 no fuso de São Paulo e fim 1 hora depois');
	ieadc_ok(str_contains($ics, 'TZOFFSETTO:-0300') && str_contains($ics, 'TZID:America/Sao_Paulo'), 'o fuso -03:00 vai declarado (VTIMEZONE)');
	ieadc_ok(str_contains($ics, "UID:evento-$a@") && str_contains($ics, 'SUMMARY:Culto especial de teste (apagar)') && str_contains($ics, 'URL:'), 'UID, título e endereço do evento');
	ieadc_ok(str_contains($ics, 'LOCATION:Salão\, térreo\; entrada principal') && str_contains($ics, 'DESCRIPTION:Resumo do evento\, com vírgula\; e ponto e vírgula.') && !str_contains($ics, 'Programação a confirmar'), 'vírgula e ponto e vírgula escapados; o trecho [a confirmar] não vai para o calendário');
	$longas = array_filter(explode("\r\n", $ics), fn($l) => strlen($l) > 75);
	ieadc_ok(!$longas, 'nenhuma linha do .ics passa de 75 bytes', $longas);
	$d = ieadc_evento_dados(get_post($a));
	$g = ieadc_evento_google_url($d);
	ieadc_ok(str_starts_with($g, 'https://calendar.google.com/calendar/render?action=TEMPLATE') && str_contains($g, 'dates=20270314T193000%2F20270314T203000') && str_contains($g, 'ctz=America%2FSao_Paulo') && str_contains($g, 'location='), 'link do Google Agenda com datas, fuso e local', $g);

	$b = $criar(['evento_data' => '2027-04-02', 'evento_data_fim' => '2027-04-04', 'evento_horario' => '[horário a confirmar]'], 'Retiro de teste (apagar)');
	$ics2 = ieadc_evento_ics(get_post($b));
	ieadc_ok(str_contains($ics2, "DTSTART;VALUE=DATE:20270402\r\n") && str_contains($ics2, "DTEND;VALUE=DATE:20270405\r\n") && !str_contains($ics2, 'LOCATION'), 'vários dias sem hora: dia inteiro, fim exclusivo no dia seguinte, sem local inventado');
	ieadc_ok(str_contains(ieadc_evento_google_url(ieadc_evento_dados(get_post($b))), 'dates=20270402%2F20270405'), 'Google Agenda: dia inteiro');
	$c = $criar(['evento_data' => '2027-05-10', 'evento_horario' => '9h às 11h30'], 'Manhã de teste (apagar)');
	$ics3 = ieadc_evento_ics(get_post($c));
	ieadc_ok(str_contains($ics3, "DTSTART;TZID=America/Sao_Paulo:20270510T090000\r\n") && str_contains($ics3, "DTEND;TZID=America/Sao_Paulo:20270510T113000\r\n"), 'horário com início e fim: usa os dois');
	$sem = wp_insert_post(['post_type' => 'evento', 'post_status' => 'publish', 'post_title' => 'Sem data (apagar)', 'meta_input' => ['_ieadc_fixture_teste' => '1']]);
	$ids[] = $sem;
	ieadc_igual('', ieadc_evento_ics(get_post($sem)), 'evento sem data não gera .ics');

	echo "rota /evento-<id>.ics e página do evento\n";
	$r = ieadc_k_baixar("/evento-$a.ics");
	ieadc_ok($r['status'] === 200 && str_contains((string) ($r['cab']['content-type'] ?? ''), 'text/calendar') && str_contains((string) ($r['cab']['content-disposition'] ?? ''), "evento-$a.ics") && str_contains($r['corpo'], 'BEGIN:VEVENT'), 'GET /evento-<id>.ics: 200, text/calendar, anexo e VEVENT', [$r['status'], $r['cab']['content-type'] ?? null]);
	$r404 = ieadc_k_baixar('/evento-99999999.ics');
	ieadc_ok($r404['status'] === 404, 'evento inexistente: 404', $r404['status']);
	$r404b = ieadc_k_baixar("/evento-$sem.ics");
	ieadc_ok($r404b['status'] === 404, 'evento sem data: 404', $r404b['status']);
	$pagina = ieadc_k_baixar((string) wp_parse_url(get_permalink($a), PHP_URL_PATH));
	ieadc_ok($pagina['status'] === 200 && str_contains($pagina['corpo'], 'Adicionar ao calendário') && str_contains($pagina['corpo'], "/evento-$a.ics") && str_contains($pagina['corpo'], 'calendar.google.com/calendar/render'),
		'a página do evento tem "Adicionar ao calendário" com o .ics e o Google Agenda', $pagina['status']);

	echo "compartilhar artigo\n";
	$artigo = get_posts(['post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 1, 'name' => 'alcancar-curitiba']);
	if ($artigo) {
		$p = ieadc_k_baixar((string) wp_parse_url(get_permalink($artigo[0]), PHP_URL_PATH));
		ieadc_ok(str_contains($p['corpo'], 'class="compartilhar"') && str_contains($p['corpo'], 'data-ieadc-rede="whatsapp"') && str_contains($p['corpo'], 'data-ieadc-rede="facebook"') && str_contains($p['corpo'], 'data-ieadc-copiar')
			&& str_contains($p['corpo'], 'https://wa.me/?text=') && str_contains($p['corpo'], 'facebook.com/sharer/sharer.php?u='), 'o artigo termina com WhatsApp, Facebook e Copiar link');
		ieadc_ok(!preg_match('#<script[^>]+src="https?://(?!(?:www\.)?(?:localhost|wordpress))#', $p['corpo']), 'compartilhar não carrega script de terceiros');
		$outra = ieadc_k_baixar('/contato/');
		ieadc_ok(!str_contains($outra['corpo'], 'class="compartilhar"'), 'páginas que não são artigos não têm o compartilhar');
	} else {
		ieadc_ok(false, 'artigo alcancar-curitiba não encontrado');
	}

	echo "página 404\n";
	$nf = ieadc_k_baixar('/pagina-que-nao-existe-' . wp_generate_password(6, false) . '/');
	ieadc_ok($nf['status'] === 404, 'endereço inexistente responde 404', $nf['status']);
	ieadc_ok(str_contains($nf['corpo'], 'Não achamos esta página') && str_contains($nf['corpo'], 'horarios-e-localizacao') && str_contains($nf['corpo'], 'pedidos-de-oracao') && str_contains($nf['corpo'], 'contato') && substr_count($nf['corpo'], 'class="btn out"') >= 6,
		'a 404 acolhe e tem links para as páginas principais');
	ieadc_ok(preg_match('/<meta name=.robots. content=.noindex/', $nf['corpo']) === 1 && str_contains((string) ($nf['cab']['x-robots-tag'] ?? ''), 'noindex'), 'a 404 tem noindex (meta e cabeçalho)', $nf['cab']['x-robots-tag'] ?? null);
} finally {
	foreach ($ids as $id) {
		wp_delete_post((int) $id, true);
	}
}

ieadc_teste_fim();
