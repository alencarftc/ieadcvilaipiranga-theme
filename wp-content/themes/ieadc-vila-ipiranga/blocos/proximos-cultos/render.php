<?php
/**
 * Bloco ieadc/proximos-cultos (fase 7). Cartões iguais aos de ieadc/eventos-grade, um por culto ou encontro que se repete, com a PRÓXIMA data
 * (ieadc_agenda_proximas): os cultos de oração, ensino e do Círculo (e Ensinai e EBD) também aparecem em Eventos, sempre atualizados.
 */
defined('ABSPATH') || exit;
$a = $attributes;
$nomes = array_values(array_filter(array_map(fn($i) => trim((string) ($i['texto'] ?? '')), (array) ($a['titulos'] ?? [])), 'strlen'));
$mais = (string) ($a['maisTexto'] ?? '');
$hoje = function_exists('ieadc_hoje') ? ieadc_hoje()->format('Y-m-d') : '';
$eventos = [];
if (function_exists('ieadc_agenda_proximas')) {
	foreach ($nomes as $nome) {
		$o = ieadc_agenda_proximas([$nome], 1);
		if ($o) {
			$eventos[] = $o[0];
		}
	}
	usort($eventos, fn($x, $y) => strcmp((string) $x['data'], (string) $y['data']));
}
if (!$eventos) {
	return;
}
$semana = ['DOM', 'SEG', 'TER', 'QUA', 'QUI', 'SEX', 'SÁB'];
$meses  = ['', 'JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];
$chips  = ['c-culto' => 'Culto', 'c-ensino' => 'Ensino', 'c-domingo' => 'Domingo'];

echo ieadc_secao_abre($a, 'sec gelo');
echo '<div class="wrap">' . ieadc_cab($a) . '<div class="grade g3">';
foreach ($eventos as $e) {
	$d = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $e['data']);
	if (!$d) {
		continue;
	}
	echo '<a class="cartao evento" href="' . esc_url((string) ($e['link'] ?? '')) . '"><div class="data">';
	echo '<span class="dw">' . $semana[(int) $d->format('w')] . '</span><span class="dn">' . $d->format('j') . '</span><span class="mm">' . $meses[(int) $d->format('n')] . '</span>';
	echo '</div><div class="corpo"><div class="chips"><span class="chip">' . esc_html($chips[$e['categoria'] ?? ''] ?? 'Toda semana') . '</span>';
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
echo '</div></div>' . ieadc_secao_fecha();
