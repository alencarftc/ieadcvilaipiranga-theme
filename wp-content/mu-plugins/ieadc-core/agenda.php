<?php
/**
 * Agenda da igreja (M2).
 *
 * Calcula os cultos e eventos pelas regras da igreja (aba Informações gerais; decisões
 * de 06/10 e 08/10/2026), as mesmas do gerador do protótipo (prototipo/fonte/gen.py:
 * eventos_do_dia, segundo_sabado, destaques, proximas, mes_html), e soma os eventos
 * avulsos cadastrados no tipo "evento". Nada de cadastrar culto fixo toda semana.
 *
 * Ocorrência:
 *   ['data' => 'Y-m-d', 'hora' => '20h', 'titulo' => …, 'categoria' => 'c-…', 'especial' => bool,
 *    'descricao' => …, 'link' => URL, 'origem' => 'regra'|ID do evento, 'data_fim' => 'Y-m-d'|null]
 *
 * Data para testes: cabeçalho X-Ieadc-Hoje (só no ambiente local) ou a constante/variável IEADC_HOJE (ver ieadc_hoje()).
 */
defined('ABSPATH') || exit;

const IEADC_AGENDA_FUSO  = 'America/Sao_Paulo';
const IEADC_AGENDA_GRUPO = 'ieadc_agenda';

/* ---------- Datas ---------- */

function ieadc_agenda_fuso(): DateTimeZone {
	static $fuso = null;
	return $fuso ??= new DateTimeZone(IEADC_AGENDA_FUSO);
}

/** Data (meia-noite, fuso de São Paulo) do instante $agora. Separada de ieadc_hoje() para poder ser testada. */
function ieadc_agenda_dia_de(DateTimeInterface $agora): DateTimeImmutable {
	$local = DateTimeImmutable::createFromInterface($agora)->setTimezone(ieadc_agenda_fuso());
	return new DateTimeImmutable($local->format('Y-m-d'), ieadc_agenda_fuso());
}

/**
 * Hoje no fuso de São Paulo. A data real vale em uso normal. Para os testes, a data pode ser fixada de duas formas:
 * a constante (ou variável de ambiente) IEADC_HOJE=Y-m-d, que vale para o site todo, e o cabeçalho HTTP
 * "X-Ieadc-Hoje: Y-m-d", que vale só para aquele pedido e só no ambiente local (wp_get_environment_type() === 'local');
 * em homologação e produção o cabeçalho é ignorado.
 */
function ieadc_hoje(): DateTimeImmutable {
	$candidatas = [];
	if (isset($_SERVER['HTTP_X_IEADC_HOJE']) && is_string($_SERVER['HTTP_X_IEADC_HOJE'])
		&& function_exists('wp_get_environment_type') && wp_get_environment_type() === 'local') {
		$candidatas[] = trim($_SERVER['HTTP_X_IEADC_HOJE']);
	}
	$candidatas[] = defined('IEADC_HOJE') ? (string) IEADC_HOJE : (string) getenv('IEADC_HOJE');
	foreach ($candidatas as $fixa) {
		if ($fixa === '') continue;
		$d = DateTimeImmutable::createFromFormat('!Y-m-d', $fixa, ieadc_agenda_fuso());
		if ($d && $d->format('Y-m-d') === $fixa) {
			return $d;
		}
	}
	return ieadc_agenda_dia_de(new DateTimeImmutable('now', new DateTimeZone('UTC')));
}

/** Normaliza qualquer data para meia-noite no fuso de São Paulo, mantendo o dia do calendário. */
function ieadc_agenda_norm(DateTimeInterface $d): DateTimeImmutable {
	return new DateTimeImmutable($d->format('Y-m-d'), ieadc_agenda_fuso());
}

/** 2º sábado do mês = primeiro sábado + 7 dias (dia da Ceia do Senhor). */
function ieadc_agenda_segundo_sabado(int $ano, int $mes): DateTimeImmutable {
	$mes = min(12, max(1, $mes));
	$d = new DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mes), ieadc_agenda_fuso());
	$ate_sabado = (6 - (int) $d->format('w') + 7) % 7; // w: 0 = domingo … 6 = sábado
	return $d->modify('+' . ($ate_sabado + 7) . ' days');
}

/* ---------- Textos e links ---------- */

/** Tema do culto dominical pela ordem do domingo no mês. */
function ieadc_agenda_temas(): array {
	return ['Família', 'Missões e Evangelismo', 'Jovens e Adolescentes', 'Mulheres', 'Crianças'];
}

function ieadc_agenda_descricoes(): array {
	return [
		'Vigília'         => 'Na sexta-feira que antecede a ceia. Nos meses ímpares.',
		'Consagração'     => 'No sábado da ceia, pela manhã. Nos meses pares.',
		'Ceia do Senhor'  => 'Todo 2º sábado do mês.',
		'Culto dominical' => 'Cada domingo do mês tem um tema no culto dominical.',
	];
}

/** Página de cada regra (caminho da página no WordPress). */
function ieadc_agenda_paginas(): array {
	return [
		'Culto de Oração'                         => 'culto-de-oracao',
		'Culto de Ensino'                         => 'culto-de-ensino',
		'Culto dirigido pelo Círculo de Oração'   => 'culto-do-circulo-de-oracao',
		'Ensinai (Curso de Teologia Ministerial)' => 'ministerios/ensinai',
		'Vigília'                                 => 'vigilia-e-consagracao',
		'Consagração'                             => 'vigilia-e-consagracao',
		'Ceia do Senhor'                          => 'ceia-do-senhor',
		'Café comunitário'                        => 'culto-dominical',
		'EBD (Escola Bíblica Dominical)'          => 'ministerios/escola-biblica-dominical',
		'Culto dominical'                         => 'culto-dominical',
	];
}

/** URL de uma página pelo caminho. Usa ieadc_link() do tema quando existe. */
function ieadc_agenda_url(string $caminho): string {
	$caminho = trim($caminho, '/');
	if (function_exists('ieadc_link')) {
		return ieadc_link($caminho);
	}
	$p = get_page_by_path($caminho);
	return $p ? get_permalink($p) : home_url('/' . $caminho . '/');
}

/* ---------- Regras fixas ---------- */

/** Ocorrências das regras da igreja num dia, na ordem da tabela da spec. */
function ieadc_agenda_regras_dia(DateTimeImmutable $d): array {
	$d    = ieadc_agenda_norm($d);
	$dia  = $d->format('Y-m-d');
	$wd   = (int) $d->format('N'); // 1 = segunda … 7 = domingo
	$mes  = (int) $d->format('n');
	$ceia = ieadc_agenda_segundo_sabado((int) $d->format('Y'), $mes);
	$r    = [];

	if ($wd === 1) $r[] = ['20h', 'Culto de Oração', 'c-culto', false];
	if ($wd === 3) $r[] = ['20h', 'Culto de Ensino', 'c-ensino', false];
	if ($wd === 4) $r[] = ['20h', 'Culto dirigido pelo Círculo de Oração', 'c-culto', false];
	if ($wd === 5) $r[] = ['19h30', 'Ensinai (Curso de Teologia Ministerial)', 'c-ensino', false];
	if ($mes % 2 === 1 && $dia === $ceia->modify('-1 day')->format('Y-m-d')) {
		$r[] = ['22h', 'Vigília', 'c-vigilia', true];
	}
	if ($dia === $ceia->format('Y-m-d')) {
		if ($mes % 2 === 0) $r[] = ['8h', 'Consagração', 'c-vigilia', true];
		$r[] = ['19h30', 'Ceia do Senhor', 'c-ceia', true];
	}
	if ($wd === 7) {
		$tema = ieadc_agenda_temas()[intdiv((int) $d->format('j') - 1, 7)];
		$r[]  = ['9h', 'Café comunitário', 'c-domingo', false];
		$r[]  = ['9h30', 'EBD (Escola Bíblica Dominical)', 'c-ensino', false];
		$r[]  = ['19h', 'Culto dominical: ' . $tema, 'c-domingo', true];
	}

	$desc    = ieadc_agenda_descricoes();
	$paginas = ieadc_agenda_paginas();
	return array_map(static function ($x) use ($dia, $desc, $paginas) {
		[$hora, $titulo, $cat, $esp] = $x;
		$base = explode(':', $titulo, 2)[0]; // "Culto dominical: Família" → "Culto dominical"
		return [
			'data'      => $dia,
			'hora'      => $hora,
			'titulo'    => $titulo,
			'categoria' => $cat,
			'especial'  => $esp,
			'descricao' => $esp ? ($desc[$base] ?? '') : '',
			'link'      => ieadc_agenda_url($paginas[$base] ?? 'eventos'),
			'origem'    => 'regra',
			'data_fim'  => null,
		];
	}, $r);
}

/* ---------- Eventos avulsos (tipo "evento") ---------- */

/** Geração do cache: muda sempre que um evento é salvo, apagado ou muda de status. */
function ieadc_agenda_geracao(): string {
	$g = wp_cache_get('geracao', IEADC_AGENDA_GRUPO);
	if ($g === false) {
		$g = (string) microtime(true);
		wp_cache_set('geracao', $g, IEADC_AGENDA_GRUPO);
	}
	return (string) $g;
}

function ieadc_agenda_limpar_cache(): void {
	wp_cache_set('geracao', (string) microtime(true) . '-' . wp_rand(), IEADC_AGENDA_GRUPO);
}

add_action('save_post_evento', 'ieadc_agenda_limpar_cache');
add_action('before_delete_post', static function ($id) {
	if (get_post_type($id) === 'evento') ieadc_agenda_limpar_cache();
});
add_action('transition_post_status', static function ($novo, $antigo, $post) {
	if ($post instanceof WP_Post && $post->post_type === 'evento') ieadc_agenda_limpar_cache();
}, 10, 3);
foreach (['added_post_meta', 'updated_post_meta', 'deleted_post_meta'] as $gancho) {
	add_action($gancho, static function ($mid, $id) {
		if (get_post_type($id) === 'evento') ieadc_agenda_limpar_cache();
	}, 10, 2);
}

/** Valida uma data Y-m-d; devolve null se vazia ou inválida. */
function ieadc_agenda_data(string $s): ?string {
	$s = trim($s);
	$d = DateTimeImmutable::createFromFormat('!Y-m-d', $s, ieadc_agenda_fuso());
	return ($d && $d->format('Y-m-d') === $s) ? $s : null;
}

/** Link de um evento avulso: evento_link (URL, /caminho ou caminho de página) ou a página do próprio evento. */
function ieadc_agenda_link_evento(WP_Post $p): string {
	$l = trim((string) get_post_meta($p->ID, 'evento_link', true));
	if ($l === '') return (string) get_permalink($p);
	if (preg_match('#^https?://#i', $l)) return $l;
	if ($l[0] === '/') return home_url($l);
	return ieadc_agenda_url($l);
}

/** Eventos avulsos publicados, em ordem de data (e de ID no mesmo dia). */
function ieadc_agenda_avulsos(): array {
	$chave = 'avulsos-' . ieadc_agenda_geracao();
	$lista = wp_cache_get($chave, IEADC_AGENDA_GRUPO);
	if (is_array($lista)) return $lista;

	$lista = [];
	$posts = get_posts([
		'post_type'        => 'evento',
		'post_status'      => 'publish',
		'numberposts'      => -1,
		'orderby'          => 'ID',
		'order'            => 'ASC',
		'suppress_filters' => false,
	]);
	foreach ($posts as $p) {
		$ini = ieadc_agenda_data((string) get_post_meta($p->ID, 'evento_data', true));
		if (!$ini) continue;
		$fim = ieadc_agenda_data((string) get_post_meta($p->ID, 'evento_data_fim', true));
		if (!$fim || $fim < $ini) $fim = $ini;
		$cat = trim((string) get_post_meta($p->ID, 'evento_categoria', true));
		$lista[] = [
			'ini'       => $ini,
			'fim'       => $fim,
			'hora'      => trim((string) get_post_meta($p->ID, 'evento_horario', true)),
			'titulo'    => (string) $p->post_title, // cru: quem exibe escapa (get_the_title aplicaria wptexturize)
			'categoria' => preg_match('/^c-[a-z0-9-]+$/', $cat) ? $cat : 'c-culto',
			'descricao' => trim((string) $p->post_excerpt), // cru: o resumo automático do WP termina em [&hellip;], que viraria pendência
			'link'      => ieadc_agenda_link_evento($p),
			'origem'    => (int) $p->ID,
		];
	}
	usort($lista, static fn($a, $b) => [$a['ini'], $a['origem']] <=> [$b['ini'], $b['origem']]);
	wp_cache_set($chave, $lista, IEADC_AGENDA_GRUPO, DAY_IN_SECONDS);
	return $lista;
}

/* ---------- API pública (contrato do plano, seção 5.1) ---------- */

/** Ocorrências de um dia: eventos avulsos primeiro, depois as regras fixas. */
function ieadc_agenda_dia(DateTimeImmutable $d): array {
	$d     = ieadc_agenda_norm($d);
	$dia   = $d->format('Y-m-d');
	$chave = 'dia-' . $dia . '-' . ieadc_agenda_geracao();
	$cache = wp_cache_get($chave, IEADC_AGENDA_GRUPO);
	if (is_array($cache)) return $cache;

	$out = [];
	foreach (ieadc_agenda_avulsos() as $e) {
		if ($e['ini'] <= $dia && $dia <= $e['fim']) {
			$out[] = [
				'data'      => $dia,
				'hora'      => $e['hora'],
				'titulo'    => $e['titulo'],
				'categoria' => $e['categoria'],
				'especial'  => true,
				'descricao' => $e['descricao'],
				'link'      => $e['link'],
				'origem'    => $e['origem'],
				'data_fim'  => $e['fim'] > $dia ? $e['fim'] : null,
			];
		}
	}
	$out = array_merge($out, ieadc_agenda_regras_dia($d));
	wp_cache_set($chave, $out, IEADC_AGENDA_GRUPO, DAY_IN_SECONDS);
	return $out;
}

/** Ocorrências de $de até $ate (inclusive), em ordem de dia. */
function ieadc_agenda(DateTimeImmutable $de, DateTimeImmutable $ate): array {
	$out = [];
	$fim = ieadc_agenda_norm($ate)->format('Y-m-d');
	for ($d = ieadc_agenda_norm($de); $d->format('Y-m-d') <= $fim; $d = $d->modify('+1 day')) {
		array_push($out, ...ieadc_agenda_dia($d));
	}
	return $out;
}

/**
 * Próximos destaques: anda dia a dia a partir de $desde (padrão hoje) e pega as
 * ocorrências especiais sem repetir título até juntar $n (= destaques() do gerador).
 */
function ieadc_agenda_destaques(int $n, ?DateTimeImmutable $desde = null): array {
	$out    = [];
	$vistos = [];
	$d      = ieadc_agenda_norm($desde ?? ieadc_hoje());
	for ($i = 0; count($out) < $n && $i < 800; $i++, $d = $d->modify('+1 day')) {
		foreach (ieadc_agenda_dia($d) as $o) {
			if ($o['especial'] && !isset($vistos[$o['titulo']])) {
				$vistos[$o['titulo']] = true;
				$out[] = $o;
			}
		}
	}
	return array_slice($out, 0, max(0, $n));
}

/**
 * Destaques da agenda (como ieadc_agenda_destaques) MAIS todos os eventos avulsos cadastrados dos próximos $dias dias: nenhum evento cadastrado fica de fora
 * por causa do limite $n (fase 7). Em ordem de data; um evento de vários dias entra uma vez.
 */
function ieadc_agenda_destaques_com_avulsos(int $n, int $dias = 45): array {
	$lista  = ieadc_agenda_destaques($n);
	$vistos = [];
	foreach ($lista as $o) {
		$vistos[($o['origem'] ?? 'regra') . '|' . $o['titulo']] = true;
	}
	$d = ieadc_agenda_norm(ieadc_hoje());
	for ($i = 0; $i < $dias; $i++, $d = $d->modify('+1 day')) {
		foreach (ieadc_agenda_dia($d) as $o) {
			if (($o['origem'] ?? 'regra') === 'regra' || empty($o['especial'])) {
				continue;
			}
			$k = $o['origem'] . '|' . $o['titulo'];
			if (!isset($vistos[$k])) {
				$vistos[$k] = true;
				$lista[] = $o;
			}
		}
	}
	usort($lista, fn($x, $y) => strcmp((string) $x['data'], (string) $y['data']));
	return $lista;
}

/**
 * Próximas datas (a partir de hoje) das ocorrências cujo título é igual a um dos
 * $titulos ou começa com "título:" (= proximas() do gerador). Procura até 400 dias.
 */
function ieadc_agenda_proximas(array $titulos, int $n = 4): array {
	$out  = [];
	$hoje = ieadc_hoje();
	$d    = $hoje;
	$lim  = $hoje->modify('+400 days')->format('Y-m-d');
	while (count($out) < $n && $d->format('Y-m-d') < $lim) {
		foreach (ieadc_agenda_dia($d) as $o) {
			foreach ($titulos as $t) {
				if ($o['titulo'] === $t || str_starts_with($o['titulo'], $t . ':')) {
					$out[] = $o;
					break;
				}
			}
		}
		$d = $d->modify('+1 day');
	}
	return array_slice($out, 0, max(0, $n));
}

/** Resumo do mês (o <small> do calendário): especiais sem "Culto dominical", sem repetir, separados por " · ". */
function ieadc_agenda_resumo_mes(int $ano, int $mes): string {
	if ($mes < 1 || $mes > 12) return '';
	$ini = new DateTimeImmutable(sprintf('%04d-%02d-01', $ano, $mes), ieadc_agenda_fuso());
	$tit = [];
	foreach (ieadc_agenda($ini, $ini->modify('last day of this month')) as $o) {
		if ($o['especial'] && !str_starts_with($o['titulo'], 'Culto dominical')) {
			$tit[$o['titulo']] = true;
		}
	}
	return implode(' · ', array_keys($tit));
}
