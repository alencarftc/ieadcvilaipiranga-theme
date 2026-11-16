<?php
/**
 * UX e marketing (fase 4): botão flutuante de WhatsApp, "Adicionar ao calendário" nos eventos (arquivo .ics e Google Agenda),
 * compartilhar artigo (sem script de terceiros) e a página 404 acolhedora (404.php). Os links de "Como chegar" (Google Maps e Waze)
 * vêm dos atalhos {maps} e {waze} de ieadc_url() (helpers.php), montados a partir do endereço de Configurações > IEADC Vila Ipiranga.
 * Detalhes e desvios do protótipo: docs/specs/desvios-do-prototipo.md ("Melhorias da fase 4").
 */
defined('ABSPATH') || exit;

/* ---------- WhatsApp flutuante ---------- */
/**
 * Botão redondo no canto da tela, fora do <main>, com nome acessível. Usa o número do campo `whatsapp` de Configurações; vazio, o telefone do site
 * (confirmado como WhatsApp pelo Felipe). Sem número válido (celular), não aparece. Some perto do rodapé (assets/js/site.js) e fica acima do aviso de cookies.
 */
add_action('wp_footer', function () {
	if (is_customize_preview() || !function_exists('ieadc_whatsapp_url')) {
		return;
	}
	$url = ieadc_whatsapp_url();
	if ($url === '') {
		return;
	}
	$url .= '?text=' . rawurlencode('Olá! Vim pelo site da ' . ieadc_dado('nome') . '.');
	echo '<aside class="ieadc-whats ieadc-ui" aria-label="Contato rápido"><a href="' . esc_url($url) . '" target="_blank" rel="noopener" aria-label="Falar no WhatsApp (abre em nova aba)">'
		. '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.21 3.07.15.2 2.1 3.2 5.08 4.49.7.31 1.26.49 1.7.63.72.23 1.36.2 1.87.12.57-.08 1.76-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.12-.27-.2-.57-.35zM12.05 21.8h-.01a9.87 9.87 0 0 1-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 0 1-1.51-5.26c0-5.45 4.44-9.89 9.89-9.89 2.64 0 5.12 1.03 6.99 2.9a9.82 9.82 0 0 1 2.89 7c0 5.45-4.44 9.88-9.88 9.88zM20.46 3.49A11.8 11.8 0 0 0 12.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.95L.06 24l6.3-1.65a11.88 11.88 0 0 0 5.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.89 0-3.18-1.24-6.17-3.48-8.42z"/></svg>'
		. '</a></aside>' . "\n";
}, 5);

/* ---------- Adicionar ao calendário ---------- */
/**
 * Horário de um evento a partir do texto livre do campo (ex.: "19h30", "9h às 11h30", "19:30"; "[horário a confirmar]" não tem hora).
 * Devolve [h, m] do início e, se houver, do fim; ou null.
 */
function ieadc_evento_horas(string $texto): ?array {
	$texto = preg_replace('/\[[^\]]*\]/u', '', $texto);
	if (!preg_match_all('/(?<!\d)([01]?\d|2[0-3])\s*(?:h|:)\s*([0-5]\d)?(?!\d)/iu', (string) $texto, $m, PREG_SET_ORDER)) {
		return null;
	}
	$hm = fn(array $x): array => [(int) $x[1], (int) ($x[2] ?? 0)];
	return ['ini' => $hm($m[0]), 'fim' => isset($m[1]) ? $hm($m[1]) : null];
}

/** Dados de calendário de um evento: datas, hora e textos (já limpos). Null se o evento não tem data válida. */
function ieadc_evento_dados(WP_Post $p): ?array {
	if ($p->post_type !== 'evento' || $p->post_status !== 'publish') {
		return null;
	}
	$ini = ieadc_agenda_data((string) get_post_meta($p->ID, 'evento_data', true));
	if (!$ini) {
		return null;
	}
	$fim = ieadc_agenda_data((string) get_post_meta($p->ID, 'evento_data_fim', true));
	if (!$fim || $fim < $ini) {
		$fim = $ini;
	}
	$limpa = fn(string $t): string => trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/\[[^\]]*\]/u', '', wp_strip_all_tags($t))));
	$horario = trim((string) get_post_meta($p->ID, 'evento_horario', true));
	// Com mais de um dia, vira evento de dia inteiro (a hora não diz qual dia). Sem hora no texto, também.
	$horas = ($ini === $fim) ? ieadc_evento_horas($horario) : null;
	return [
		'titulo' => $limpa((string) $p->post_title) ?: 'Evento na IEADC Vila Ipiranga',
		'ini'    => $ini, 'fim' => $fim, 'horas' => $horas,
		'descricao' => $limpa((string) $p->post_excerpt),
		'horario'   => $limpa($horario),
		'local'     => $limpa((string) get_post_meta($p->ID, 'evento_local', true)),
		'url'       => (string) get_permalink($p),
	];
}

/** Início e fim no formato do calendário: ['ini' => '20261018T193000', 'fim' => '…', 'dia_inteiro' => false] (ou só a data, com o fim exclusivo, no dia inteiro). */
function ieadc_evento_quando(array $d): array {
	$fuso = new DateTimeZone('America/Sao_Paulo'); // -03:00 o ano todo (sem horário de verão)
	if ($d['horas']) {
		[$h, $m] = $d['horas']['ini'];
		$i = new DateTimeImmutable($d['ini'] . sprintf(' %02d:%02d:00', $h, $m), $fuso);
		if ($d['horas']['fim']) {
			[$h2, $m2] = $d['horas']['fim'];
			$f = new DateTimeImmutable($d['ini'] . sprintf(' %02d:%02d:00', $h2, $m2), $fuso);
			if ($f <= $i) {
				$f = $i->modify('+1 hour');
			}
		} else {
			$f = $i->modify('+1 hour'); // sem fim informado: 1 hora (o calendário exige um fim; não é dado da igreja)
		}
		return ['ini' => $i->format('Ymd\THis'), 'fim' => $f->format('Ymd\THis'), 'dia_inteiro' => false];
	}
	$i = new DateTimeImmutable($d['ini'], $fuso);
	$f = (new DateTimeImmutable($d['fim'], $fuso))->modify('+1 day');
	return ['ini' => $i->format('Ymd'), 'fim' => $f->format('Ymd'), 'dia_inteiro' => true];
}

/** Endereço do arquivo .ics de um evento (rota do WordPress, sem arquivo físico). */
function ieadc_evento_ics_url(int $id): string {
	return home_url('/evento-' . $id . '.ics');
}

/** Link "Google Agenda" (criar evento) com os mesmos dados do .ics. */
function ieadc_evento_google_url(array $d): string {
	$q = ieadc_evento_quando($d);
	$detalhes = trim(($d['descricao'] !== '' ? $d['descricao'] . "\n" : '') . ($d['horas'] ? '' : ($d['horario'] !== '' ? 'Horário: ' . $d['horario'] . "\n" : '')) . $d['url']);
	$args = ['action' => 'TEMPLATE', 'text' => $d['titulo'], 'dates' => $q['ini'] . '/' . $q['fim'], 'details' => $detalhes];
	if (!$q['dia_inteiro']) {
		$args['ctz'] = 'America/Sao_Paulo';
	}
	if ($d['local'] !== '') {
		$args['location'] = $d['local'];
	}
	return 'https://calendar.google.com/calendar/render?' . http_build_query($args, '', '&', PHP_QUERY_RFC3986);
}

/** Texto de um campo do .ics: escapa \ ; , e quebras de linha. */
function ieadc_ics_texto(string $t): string {
	return str_replace(["\\", ';', ',', "\r\n", "\n", "\r"], ["\\\\", '\;', '\,', '\n', '\n', '\n'], $t);
}

/** Dobra a linha em 75 bytes (RFC 5545; as continuações começam com um espaço), sem partir caractere UTF-8. */
function ieadc_ics_dobrar(string $linha): string {
	$partes = [];
	$limite = 75;
	while (strlen($linha) > $limite) {
		$corte = $limite;
		while ($corte > 0 && (ord($linha[$corte]) & 0xC0) === 0x80) {
			$corte--;
		}
		$partes[] = substr($linha, 0, $corte);
		$linha = substr($linha, $corte);
		$limite = 74;
	}
	$partes[] = $linha;
	return implode("\r\n ", $partes);
}

/** Conteúdo do arquivo .ics de um evento (uma linha por item, terminada em CRLF). */
function ieadc_evento_ics(WP_Post $p): string {
	$d = ieadc_evento_dados($p);
	if (!$d) {
		return '';
	}
	$q = ieadc_evento_quando($d);
	$host = (string) wp_parse_url(home_url(), PHP_URL_HOST);
	$desc = trim($d['descricao'] . ($d['horas'] || $d['horario'] === '' ? '' : ($d['descricao'] !== '' ? "\n" : '') . 'Horário: ' . $d['horario']));
	$l = [
		'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//IEADC Vila Ipiranga//Site//PT-BR', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
		'X-WR-TIMEZONE:America/Sao_Paulo',
		'BEGIN:VTIMEZONE', 'TZID:America/Sao_Paulo', 'BEGIN:STANDARD', 'DTSTART:19700101T000000', 'TZOFFSETFROM:-0300', 'TZOFFSETTO:-0300', 'TZNAME:-03', 'END:STANDARD', 'END:VTIMEZONE',
		'BEGIN:VEVENT',
		'UID:evento-' . $p->ID . '@' . $host,
		'DTSTAMP:' . gmdate('Ymd\THis\Z', (int) get_post_modified_time('U', true, $p)),
	];
	if ($q['dia_inteiro']) {
		$l[] = 'DTSTART;VALUE=DATE:' . $q['ini'];
		$l[] = 'DTEND;VALUE=DATE:' . $q['fim'];
	} else {
		$l[] = 'DTSTART;TZID=America/Sao_Paulo:' . $q['ini'];
		$l[] = 'DTEND;TZID=America/Sao_Paulo:' . $q['fim'];
	}
	$l[] = 'SUMMARY:' . ieadc_ics_texto($d['titulo']);
	if ($desc !== '') {
		$l[] = 'DESCRIPTION:' . ieadc_ics_texto($desc);
	}
	if ($d['local'] !== '') {
		$l[] = 'LOCATION:' . ieadc_ics_texto($d['local']);
	}
	$l[] = 'URL:' . $d['url'];
	$l[] = 'END:VEVENT';
	$l[] = 'END:VCALENDAR';
	return implode("\r\n", array_map('ieadc_ics_dobrar', $l)) . "\r\n";
}

/* /evento-<id>.ics: sem arquivo físico (mesmo método do /favicon.ico em identidade.php). Evento inexistente, sem data ou não publicado: 404. */
add_action('parse_request', function ($wp) {
	if (!preg_match('#^evento-(\d+)\.ics$#', (string) ($wp->request ?? ''), $m)) {
		return;
	}
	$p = get_post((int) $m[1]);
	$ics = $p ? ieadc_evento_ics($p) : '';
	if ($ics === '') {
		status_header(404);
		nocache_headers();
		header('Content-Type: text/plain; charset=utf-8');
		echo 'Evento não encontrado.';
		exit;
	}
	status_header(200);
	header('Content-Type: text/calendar; charset=utf-8');
	header('Content-Disposition: attachment; filename="evento-' . (int) $p->ID . '.ics"');
	header('X-Content-Type-Options: nosniff');
	header('Cache-Control: public, max-age=3600');
	echo $ics;
	exit;
});

/** Os links "Adicionar ao calendário" no fim da página de cada evento cadastrado. */
add_filter('the_content', function (string $html): string {
	if (!is_singular('evento') || !in_the_loop() || !is_main_query()) {
		return $html;
	}
	$d = ieadc_evento_dados(get_post());
	if (!$d) {
		return $html;
	}
	return $html . '<div class="ieadc-agenda-add ieadc-ui"><p class="ieadc-agenda-add__t"><b>Adicionar ao calendário</b></p><div class="btns">'
		. '<a class="btn" href="' . esc_url(ieadc_evento_ics_url((int) get_the_ID())) . '" download>Baixar arquivo (.ics)</a>'
		. '<a class="btn out" href="' . esc_url(ieadc_evento_google_url($d)) . '" target="_blank" rel="noopener" aria-label="Google Agenda (abre em nova aba)">Google Agenda</a>'
		. '</div></div>';
}, 20);

/* ---------- Compartilhar artigo ---------- */
/**
 * Compartilhar no fim do artigo: WhatsApp e Facebook (links simples, sem script de terceiros) e "Copiar link" (assets/js/site.js).
 * Os endereços dos links variam por artigo: o teste de markup não os compara (tests/visual/lib/normalizar.js).
 */
function ieadc_compartilhar_html(WP_Post $p): string {
	$url = (string) get_permalink($p);
	$titulo = wp_strip_all_tags((string) get_the_title($p));
	$wa = 'https://wa.me/?text=' . rawurlencode($titulo . ' ' . $url);
	$fb = 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url);
	return '<div class="compartilhar" role="group" aria-label="Compartilhar este artigo"><span class="compartilhar__t">Compartilhe:</span>'
		. '<a class="btn out" href="' . esc_url($wa) . '" target="_blank" rel="noopener" data-ieadc-rede="whatsapp" aria-label="Compartilhar no WhatsApp (abre em nova aba)">WhatsApp</a>'
		. '<a class="btn out" href="' . esc_url($fb) . '" target="_blank" rel="noopener" data-ieadc-rede="facebook" aria-label="Compartilhar no Facebook (abre em nova aba)">Facebook</a>'
		. '<button class="btn out" type="button" data-ieadc-copiar data-url="' . esc_url($url) . '">Copiar link</button>'
		. '<span class="compartilhar__ok" role="status" aria-live="polite"></span></div>';
}

/* ---------- Página 404 ---------- */
/* A página não encontrada não vai para o Google (a resposta já é 404; o cabeçalho e a meta reforçam). */
add_filter('wp_robots', function (array $r): array {
	if (is_404()) {
		$r['noindex'] = true;
		$r['follow'] = true;
	}
	return $r;
}, 20);
add_action('template_redirect', function () {
	if (is_404() && !headers_sent()) {
		header('X-Robots-Tag: noindex, follow');
	}
});
