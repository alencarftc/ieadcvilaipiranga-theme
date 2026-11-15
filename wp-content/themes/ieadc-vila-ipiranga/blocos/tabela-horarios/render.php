<?php
/**
 * Bloco ieadc/tabela-horarios (B2). HTML esperado: docs/specs/referencias/tabela-horarios.html.
 * Cada coluna: cabeçalho de seção, tabela (uma linha por "Dia | Evento | Horário") e nota opcional.
 */
defined('ABSPATH') || exit;
$a = $attributes;
$colunas = array_filter((array) ($a['colunas'] ?? []), fn($c) => trim((string) ($c['linhas'] ?? '')) !== '' || trim((string) ($c['titulo'] ?? '')) !== '');

$celulas = static function (string $linha): array {
	return array_map('trim', explode('|', $linha));
};

echo ieadc_secao_abre($a, 'sec');
echo '<div class="wrap grade g2" style="gap:55px;align-items:start">';
foreach ($colunas as $c) {
	echo '<div>' . ieadc_cab(['eyebrow' => $c['eyebrow'] ?? '', 'titulo' => $c['titulo'] ?? '']);
	$linhas = array_filter(array_map('trim', preg_split('/\r?\n/', (string) ($c['linhas'] ?? ''))), 'strlen');
	if ($linhas) {
		echo '<div class="tabela"><table>';
		$cab = trim((string) ($c['cabecalhos'] ?? ''));
		if ($cab !== '') {
			echo '<tr>';
			foreach ($celulas($cab) as $th) {
				echo '<th>' . ieadc_pend($th) . '</th>';
			}
			echo '</tr>';
		}
		foreach ($linhas as $l) {
			echo '<tr>';
			foreach ($celulas($l) as $td) {
				echo '<td>' . ieadc_pend($td) . '</td>';
			}
			echo '</tr>';
		}
		echo '</table></div>';
	}
	if (trim((string) ($c['nota'] ?? '')) !== '') {
		echo '<p class="note" style="margin-top:21px">' . ieadc_kses_rico((string) $c['nota']) . '</p>';
	}
	echo '</div>';
}
echo '</div>' . ieadc_secao_fecha();
