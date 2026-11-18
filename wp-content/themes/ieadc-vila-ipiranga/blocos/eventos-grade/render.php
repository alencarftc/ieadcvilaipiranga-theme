<?php
/**
 * Bloco ieadc/eventos-grade (B2). HTML esperado: docs/specs/referencias/eventos-grade.html.
 * Cartões de ieadc_agenda_destaques($quantidade) (M2), iguais ao ev_cartao() do gerador.
 */
defined('ABSPATH') || exit;
$a = $attributes;
$n = max(0, (int) ($a['quantidade'] ?? 6));
$eventos = ($n && function_exists('ieadc_agenda_destaques')) ? (!empty($a['incluirAvulsos']) && function_exists('ieadc_agenda_destaques_com_avulsos') ? ieadc_agenda_destaques_com_avulsos($n) : ieadc_agenda_destaques($n)) : [];
$mais = (string) ($a['maisTexto'] ?? '');
$hoje = function_exists('ieadc_hoje') ? ieadc_hoje()->format('Y-m-d') : '';

$semana = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB']; // índice = format('w')
$meses  = ['', 'JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];
$chips  = [
	'c-ceia'    => ['sol', 'Ceia e preparação'],
	'c-vigilia' => ['sol', 'Ceia e preparação'],
	'c-jovens'  => ['verde', 'Mocidade'],
	'c-domingo' => ['', 'Culto dominical'],
];
$data = static fn(string $ymd) => DateTimeImmutable::createFromFormat('!Y-m-d', $ymd);

echo ieadc_secao_abre($a, 'sec');
echo '<div class="wrap">' . ieadc_cab($a);
if ($eventos) {
	echo '<div class="grade g3">';
	foreach ($eventos as $e) {
		$d = $data((string) $e['data']);
		if (!$d) {
			continue;
		}
		[$classe, $nome] = $chips[$e['categoria'] ?? ''] ?? ['', 'Evento'];
		echo '<a class="cartao evento" href="' . esc_url((string) ($e['link'] ?? '')) . '"><div class="data">';
		echo '<span class="dw">' . $semana[(int) $d->format('w')] . '</span><span class="dn">' . $d->format('j') . '</span><span class="mm">' . $meses[(int) $d->format('n')] . '</span>';
		$fim = !empty($e['data_fim']) ? $data((string) $e['data_fim']) : null;
		if ($fim) {
			echo '<span class="ate">até ' . mb_strtolower($semana[(int) $fim->format('w')]) . ' ' . $fim->format('j') . '</span>';
		}
		echo '</div><div class="corpo"><div class="chips"><span class="chip' . ($classe !== '' ? ' ' . $classe : '') . '">' . esc_html($nome) . '</span>';
		if ($e['data'] === $hoje) {
			echo '<span class="hoje-tag">Hoje</span>';
		}
		echo '</div><h3 class="h4">' . ieadc_pend((string) $e['titulo']) . '</h3>';
		if (trim((string) ($e['hora'] ?? '')) !== '') {
			echo '<span class="hora">' . ieadc_icone('relogio') . ieadc_pend((string) $e['hora']) . '</span>';
		}
		if (trim((string) ($e['descricao'] ?? '')) !== '') {
			echo '<p class="small">' . ieadc_pend((string) $e['descricao']) . '</p>';
		}
		if ($mais !== '') {
			echo '<span class="mais">' . esc_html($mais) . ieadc_icone('seta') . '</span>';
		}
		echo '</div></a>';
	}
	echo '</div>';
}
echo '</div>' . ieadc_secao_fecha();
