<?php
/**
 * Bloco ieadc/local (B2). HTML esperado: docs/specs/referencias/local.html.
 * Endereço (linha vazia = endereço de Configurações > IEADC Vila Ipiranga), linhas de ônibus,
 * botões e o mapa ilustrado (mapa.svg.php).
 */
defined('ABSPATH') || exit;
$a = $attributes;
require_once __DIR__ . '/mapa.svg.php';

$endereco_padrao = static function (): string {
	return function_exists('ieadc_info') ? (string) ieadc_info('endereco') : '';
};

$linhas = (array) ($a['linhasEndereco'] ?? []);
if (!$linhas) {
	$linhas = [['icone' => 'pin', 'texto' => '']];
}

echo ieadc_secao_abre($a, 'sec');
echo '<div class="wrap local"><div>';
if (trim((string) ($a['eyebrow'] ?? '')) !== '') {
	echo '<span class="eyebrow">' . ieadc_kses_rico((string) $a['eyebrow']) . '</span>';
}
if (trim((string) ($a['titulo'] ?? '')) !== '') {
	echo '<h2 class="h2">' . ieadc_kses_rico((string) $a['titulo']) . '</h2>';
}
echo ieadc_swoosh();

$html_linhas = '';
foreach ($linhas as $l) {
	$texto = trim((string) ($l['texto'] ?? ''));
	$texto = $texto !== '' ? ieadc_kses_rico($texto) : esc_html($endereco_padrao());
	if ($texto === '') {
		continue;
	}
	$html_linhas .= '<div>' . ieadc_icone((string) ($l['icone'] ?? 'pin')) . '<span>' . $texto . '</span></div>';
}
if ($html_linhas !== '') {
	echo '<div class="endereco">' . $html_linhas . '</div>';
}

$onibus = array_filter(array_map(fn($o) => trim((string) ($o['texto'] ?? '')), (array) ($a['onibus'] ?? [])), 'strlen');
if ($onibus) {
	echo '<div class="linhas">';
	foreach ($onibus as $o) {
		echo '<span>' . ieadc_icone('onibus') . ieadc_pend($o) . '</span>';
	}
	echo '</div>';
}

$botoes = '';
foreach ((array) ($a['botoes'] ?? []) as $b) {
	$botoes .= ieadc_btn((string) ($b['texto'] ?? ''), (string) ($b['link'] ?? ''), '', (string) (($b['icone'] ?? '') !== '' ? $b['icone'] : 'pin'));
}
if ($botoes !== '') {
	echo '<div class="btns">' . $botoes . '</div>';
}
echo '</div>' . ieadc_local_mapa((string) ($a['rotuloPin'] ?? '')) . '</div>' . ieadc_secao_fecha();
