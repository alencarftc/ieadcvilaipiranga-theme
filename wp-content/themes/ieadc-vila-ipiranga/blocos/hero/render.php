<?php
/**
 * Bloco ieadc/hero (B1). HTML esperado: docs/specs/referencias/hero.html (24 ocorrências).
 * Variantes: "pagina" (foto de fundo) e "sem-foto"; a migalha (cultos e ministérios) aparece se preenchida.
 */
defined('ABSPATH') || exit;
$a = $attributes;
$variante = ($a['variante'] ?? 'pagina') === 'sem-foto' ? 'sem-foto' : 'pagina';

echo ieadc_secao_abre($a, 'hero ' . $variante);
if ($variante === 'pagina') {
	$fundo = ieadc_fundo($a['foto'] ?? 0, (string) ($a['foco'] ?? ''));
	echo '<div class="foto"' . ($fundo !== '' ? ' ' . $fundo : '') . '></div>';
}
echo '<div class="wrap"><div class="conteudo">';

// Migalha: "<a>Horários</a> › Culto de Ensino". Item sem link sai como texto.
$migalha = [];
foreach ((array) ($a['migalha'] ?? []) as $m) {
	$texto = trim((string) ($m['texto'] ?? ''));
	if ($texto === '') {
		continue;
	}
	$link = trim((string) ($m['link'] ?? ''));
	$migalha[] = $link !== '' ? '<a href="' . esc_url(ieadc_url($link)) . '">' . esc_html($texto) . '</a>' : esc_html($texto);
}
if ($migalha) {
	echo '<div class="migalha">' . implode(ieadc_migalha_sep(), $migalha) . '</div>';
}

$eyebrow = trim((string) ($a['eyebrow'] ?? ''));
if ($eyebrow !== '') {
	echo '<span class="eyebrow">' . ieadc_pend($eyebrow) . '</span>';
}
$titulo = trim((string) ($a['titulo'] ?? ''));
if ($titulo !== '') {
	echo '<h1 class="h1">' . ieadc_kses_rico($titulo) . '</h1>';
}
echo ieadc_swoosh();
// {atualizacao} = data da última alteração desta página (ex.: "Última atualização" da política de privacidade).
$lead = str_replace('{atualizacao}', esc_html((string) get_the_modified_date('', (int) get_the_ID())), (string) ($a['lead'] ?? ''));
echo ieadc_paragrafos($lead, 'lead');

$extra = trim((string) ($a['extra'] ?? ''));
if ($extra !== '') {
	echo ieadc_kses_rico($extra);
}

$botoes = '';
foreach ((array) ($a['botoes'] ?? []) as $b) {
	$estilo = (string) ($b['estilo'] ?? '');
	$estilo = in_array($estilo, ['out', 'branco', 'sol'], true) ? $estilo : '';
	$botoes .= ieadc_btn((string) ($b['texto'] ?? ''), (string) ($b['link'] ?? ''), $estilo, (string) ($b['icone'] ?? 'seta'));
}
if ($botoes !== '') {
	echo '<div class="btns">' . $botoes . '</div>';
}

$frase = trim((string) ($a['frase'] ?? ''));
if ($frase !== '') {
	echo '<div class="script script-home">' . esc_html($frase) . '</div>';
}
echo '</div></div>' . ieadc_secao_fecha();
