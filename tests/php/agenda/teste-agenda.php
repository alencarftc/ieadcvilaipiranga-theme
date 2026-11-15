<?php
/**
 * Testes da agenda (M2). Rodam sem WordPress, com o wp-falso.php.
 * Uso, na raiz do repositório:  php tests/php/agenda/teste-agenda.php
 * Sai com código 1 se algum teste falhar.
 *
 * Fontes do esperado:
 * - dados/esperado-2026-2027.json: eventos_do_dia() do gerador do protótipo, dia a dia (gerar_esperado.py).
 * - docs/specs/referencias/eventos-grade.html e calendario.html: o HTML do protótipo.
 */
require __DIR__ . '/wp-falso.php';
require dirname(__DIR__, 3) . '/wp-content/mu-plugins/ieadc-core/agenda.php';

date_default_timezone_set('UTC'); // o resultado não pode depender do fuso do PHP
putenv('IEADC_HOJE=2026-10-06');

$RAIZ   = dirname(__DIR__, 3);
$falhas = 0;
$total  = 0;
function ok(bool $cond, string $nome, $detalhe = null): void {
	global $falhas, $total;
	$total++;
	if ($cond) {
		echo "  ok   $nome\n";
		return;
	}
	$falhas++;
	echo "  FALHOU $nome\n";
	if ($detalhe !== null) echo '         ' . str_replace("\n", "\n         ", print_r($detalhe, true)) . "\n";
}
function d(string $s): DateTimeImmutable { return new DateTimeImmutable($s, new DateTimeZone('America/Sao_Paulo')); }
function resumo(array $os): array { return array_map(fn($o) => $o['data'] . ' ' . $o['hora'] . ' ' . $o['titulo'], $os); }

/* O avulso que está no protótipo (o seed do M3 cadastra igual). */
function evento_confraternizacao(): WP_Post {
	return new WP_Post([
		'ID' => 101, 'post_title' => 'Confraternização dos jovens', 'post_name' => 'confraternizacao-dos-jovens',
		'post_excerpt' => 'Dias 17 e 18 de outubro. [Local e programação a confirmar]',
		'meta' => [
			'evento_data' => '2026-10-17', 'evento_data_fim' => '2026-10-18', 'evento_horario' => '[horário a confirmar]',
			'evento_categoria' => 'c-jovens', 'evento_link' => 'ministerios/mocidade',
		],
	]);
}
$GLOBALS['ieadc_teste_eventos'] = [evento_confraternizacao()];

echo "Agenda (hoje = " . ieadc_hoje()->format('d/m/Y') . ")\n";

/* 1. Igual ao gerador do protótipo, dia a dia, em 2026 e 2027. */
$esperado = json_decode(file_get_contents(__DIR__ . '/dados/esperado-2026-2027.json'), true);
$dif = [];
for ($x = d('2026-01-01'); $x <= d('2027-12-31'); $x = $x->modify('+1 day')) {
	$k   = $x->format('Y-m-d');
	$php = array_map(fn($o) => [$o['hora'], $o['titulo'], $o['categoria'], $o['especial']], ieadc_agenda_dia($x));
	if ($php !== ($esperado[$k] ?? [])) $dif[$k] = ['php' => $php, 'gen.py' => $esperado[$k] ?? []];
}
ok($dif === [], 'todos os dias de 2026 e 2027 iguais a eventos_do_dia() do gen.py', array_slice($dif, 0, 3, true));

/* 2. Destaques da Início. */
ok(resumo(ieadc_agenda_destaques(3)) === [
	'2026-10-10 8h Consagração',
	'2026-10-10 19h30 Ceia do Senhor',
	'2026-10-11 19h Culto dominical: Missões e Evangelismo',
], 'ieadc_agenda_destaques(3) = cartões da Início', resumo(ieadc_agenda_destaques(3)));

/* 3. Destaques da página Eventos = 6 cartões de eventos.html (data, "até", título, hora, descrição, link). */
$ref = file_get_contents($RAIZ . '/docs/specs/referencias/eventos-grade.html');
$ref = explode('<!-- ===== ', $ref)[1]; // só a ocorrência de eventos.html
preg_match_all('#<a class="cartao evento" href="([^"]*)"><div class="data"><span class="dw">([^<]*)</span><span class="dn">(\d+)</span><span class="mm">([^<]*)</span>(?:<span class="ate">([^<]*)</span>)?</div>.*?<h3 class="h4">([^<]*)</h3><span class="hora">.*?</svg>(.*?)</span><p class="small">(.*?)</p>#s', $ref, $m, PREG_SET_ORDER);
$dsem  = [1 => 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB', 'DOM'];
$meses = [1 => 'JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];
$cartoes_ref = array_map(fn($c) => [
	'link' => $c[1], 'dia' => "$c[2] $c[3] $c[4]", 'ate' => $c[5], 'titulo' => $c[6],
	'hora' => strip_tags($c[7]), 'descricao' => strip_tags($c[8]),
], $m);
$cartoes_php = array_map(function ($o) use ($dsem, $meses) {
	$x = d($o['data']);
	$f = $o['data_fim'] ? d($o['data_fim']) : null;
	return [
		'link' => parse_url($o['link'], PHP_URL_PATH), 'dia' => $dsem[(int) $x->format('N')] . ' ' . $x->format('j') . ' ' . $meses[(int) $x->format('n')],
		'ate' => $f ? 'até ' . mb_strtolower($dsem[(int) $f->format('N')]) . ' ' . $f->format('j') : '',
		'titulo' => $o['titulo'], 'hora' => $o['hora'], 'descricao' => $o['descricao'],
	];
}, ieadc_agenda_destaques(6));
ok(count($cartoes_ref) === 6 && $cartoes_php === $cartoes_ref, 'ieadc_agenda_destaques(6) = 6 cartões da página Eventos', ['php' => $cartoes_php, 'protótipo' => $cartoes_ref]);

/* 4. Meses pares e ímpares. */
$out = resumo(ieadc_agenda(d('2026-10-01'), d('2026-10-31')));
ok((bool) preg_grep('/^2026-10-10 8h Consagração$/', $out) && !preg_grep('/Vigília/', $out), 'outubro/2026 tem Consagração e não tem Vigília');
$nov = resumo(ieadc_agenda(d('2026-11-01'), d('2026-11-30')));
ok(array_values(preg_grep('/Vigília|Consagração/', $nov)) === ['2026-11-13 22h Vigília'], 'novembro/2026: Vigília na sexta 13/11 às 22h, sem Consagração', $nov);
ok(resumo(array_filter(ieadc_agenda_dia(d('2026-11-14')), fn($o) => $o['especial'])) === ['2026-11-14 19h30 Ceia do Senhor'], 'Ceia do Senhor em 14/11 às 19h30');

/* 5. 5º domingo. */
$dom = array_column(ieadc_agenda_dia(d('2026-11-29')), 'titulo');
ok(end($dom) === 'Culto dominical: Crianças', '29/11/2026 (5º domingo) é "Culto dominical: Crianças"', $dom);

/* 6. Próximas datas da Ceia. */
ok(array_column(ieadc_agenda_proximas(['Ceia do Senhor'], 4), 'data') === ['2026-10-10', '2026-11-14', '2026-12-12', '2027-01-09'],
	"ieadc_agenda_proximas(['Ceia do Senhor'], 4) = 10/10, 14/11, 12/12/2026 e 09/01/2027", ieadc_agenda_proximas(['Ceia do Senhor'], 4));
$cd = ieadc_agenda_proximas(['Culto dominical'], 4);
ok(resumo($cd) === ['2026-10-11 19h Culto dominical: Missões e Evangelismo', '2026-10-18 19h Culto dominical: Jovens e Adolescentes', '2026-10-25 19h Culto dominical: Mulheres', '2026-11-01 19h Culto dominical: Família'],
	"ieadc_agenda_proximas(['Culto dominical']) casa \"Culto dominical:\" no começo", resumo($cd));
ok(resumo(ieadc_agenda_proximas(['Vigília', 'Consagração'], 3)) === ['2026-10-10 8h Consagração', '2026-11-13 22h Vigília', '2026-12-12 8h Consagração'], 'próximas de Vigília e Consagração');

/* 7. Resumo dos meses = <small> do calendário do protótipo. */
preg_match_all('#<span class="tit">(\w+) de (\d{4})</span><small>([^<]*)</small>#u', file_get_contents($RAIZ . '/docs/specs/referencias/calendario.html'), $m, PREG_SET_ORDER);
$nomes = ['Outubro' => 10, 'Novembro' => 11, 'Dezembro' => 12];
foreach ($m as [, $mes, $ano, $small]) {
	$r = ieadc_agenda_resumo_mes((int) $ano, $nomes[$mes]);
	ok($r === $small, "ieadc_agenda_resumo_mes($ano, {$nomes[$mes]}) = \"$small\"", $r);
}
ok(count($m) === 3, 'calendário do protótipo tem os 3 meses');

/* 8. Fuso de São Paulo. */
putenv('IEADC_HOJE');
ok(ieadc_agenda_dia_de(new DateTimeImmutable('2026-10-07 02:30', new DateTimeZone('UTC')))->format('Y-m-d') === '2026-10-06', '23h30 de Brasília (02h30 UTC do dia seguinte) = dia 06');
ok(ieadc_agenda_dia_de(new DateTimeImmutable('2026-10-06 23:30', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d') === '2026-10-06', '23h30 em São Paulo = dia 06');
ok(ieadc_agenda_dia_de(new DateTimeImmutable('2026-10-07 00:10', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d') === '2026-10-07', '00h10 em São Paulo = dia 07');
$agora_sp = (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d');
ok(ieadc_hoje()->format('Y-m-d') === $agora_sp && ieadc_hoje()->getTimezone()->getName() === 'America/Sao_Paulo', 'sem IEADC_HOJE, ieadc_hoje() é o dia de hoje em São Paulo');
putenv('IEADC_HOJE=2026-13-45');
ok(ieadc_hoje()->format('Y-m-d') === $agora_sp, 'IEADC_HOJE inválida é ignorada');
putenv('IEADC_HOJE=2026-10-06');

/* 9. Eventos avulsos: regras do cadastro e cache. */
$GLOBALS['ieadc_teste_eventos'][] = new WP_Post(['ID' => 102, 'post_title' => 'Rascunho', 'post_status' => 'draft', 'meta' => ['evento_data' => '2026-10-20']]);
$GLOBALS['ieadc_teste_eventos'][] = new WP_Post(['ID' => 103, 'post_title' => 'Sem data', 'meta' => []]);
$GLOBALS['ieadc_teste_eventos'][] = new WP_Post(['ID' => 104, 'post_title' => 'Batismo', 'post_name' => 'batismo', 'post_excerpt' => 'Batismo nas águas.', 'meta' => ['evento_data' => '2026-10-21', 'evento_horario' => '20h']]);
do_action('save_post_evento', 104);
$dia = ieadc_agenda_dia(d('2026-10-21'));
ok($dia[0]['titulo'] === 'Batismo' && $dia[1]['titulo'] === 'Culto de Ensino', 'avulso entra antes dos cultos fixos do dia', resumo($dia));
ok($dia[0]['categoria'] === 'c-culto' && $dia[0]['especial'] === true && $dia[0]['origem'] === 104 && $dia[0]['data_fim'] === null, 'avulso: categoria padrão c-culto, especial, origem = ID, sem data_fim', $dia[0]);
ok($dia[0]['link'] === 'http://localhost:8080/eventos/evento/batismo/', 'avulso sem evento_link aponta para a página do evento', $dia[0]['link']);
ok(!preg_grep('/Rascunho|Sem data/', resumo(ieadc_agenda(d('2026-10-01'), d('2026-10-31')))), 'rascunho e evento sem data ficam fora');
$c17 = ieadc_agenda_dia(d('2026-10-17'))[0];
$c18 = ieadc_agenda_dia(d('2026-10-18'))[0];
ok($c17['data_fim'] === '2026-10-18' && $c18['data_fim'] === null && $c18['titulo'] === 'Confraternização dos jovens', 'avulso de vários dias aparece em todos os dias; data_fim só antes do último');

$antes = $GLOBALS['ieadc_teste_consultas'];
ieadc_agenda(d('2026-10-01'), d('2026-12-31'));
ok($GLOBALS['ieadc_teste_consultas'] === $antes, 'com cache, a agenda não consulta o banco de novo');
ieadc_teste_post(104)->meta['evento_data'] = '2026-10-22';
do_action('updated_post_meta', 1, 104, 'evento_data', '2026-10-22');
ok(ieadc_agenda_dia(d('2026-10-21'))[0]['titulo'] === 'Culto de Ensino' && ieadc_agenda_dia(d('2026-10-22'))[0]['titulo'] === 'Batismo', 'mudar um evento limpa o cache');

/* 10. Entradas fora do normal. */
ok(ieadc_agenda_resumo_mes(2026, 13) === '' && ieadc_agenda_resumo_mes(2026, 0) === '', 'resumo de mês inválido devolve vazio, sem exceção');
$GLOBALS['ieadc_teste_eventos'][] = new WP_Post(['ID' => 105, 'post_title' => 'Sem resumo', 'post_name' => 'sem-resumo', 'meta' => ['evento_data' => '2026-10-27']]);
do_action('save_post_evento', 105);
ok(ieadc_agenda_dia(d('2026-10-27'))[0]['descricao'] === '', 'evento sem resumo fica com descrição vazia (nada de [&hellip;] automático)');

echo "\n" . ($total - $falhas) . " de $total testes passaram.\n";
exit($falhas ? 1 : 0);
