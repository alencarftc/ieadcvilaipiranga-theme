<?php
/**
 * Bloco ieadc/boas-vindas (B3). HTML esperado: docs/specs/referencias/boas-vindas.html.
 */
defined('ABSPATH') || exit;
$a = $attributes;
$botoes  = array_filter((array) ($a['botoes'] ?? []), fn($b) => !empty($b['texto']));
$valores = array_filter((array) ($a['valores'] ?? []), fn($v) => !empty($v['texto']));

echo ieadc_secao_abre($a, 'sec glow');
echo '<div class="wrap boas"><div class="moldura"><div class="img" ' . ieadc_fundo((int) ($a['foto'] ?? 0), (string) ($a['foco'] ?? '')) . '></div>';
echo '<svg viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true"><path d="M50 1C99 1 99 99 50 99" fill="none" stroke="url(#gr-sol)" stroke-width="5" vector-effect="non-scaling-stroke"/></svg></div>';
echo '<div>';
if (!empty($a['eyebrow'])) {
	echo '<span class="eyebrow">' . ieadc_pend($a['eyebrow']) . '</span>';
}
echo '<h2 class="h2">' . ieadc_kses_rico((string) ($a['titulo'] ?? '')) . '</h2>' . ieadc_swoosh();
if (!empty($a['texto'])) {
	echo '<div class="txt" style="margin-top:21px">' . ieadc_paragrafos($a['texto']) . '</div>';
}
if (!empty($a['aviso'])) {
	echo '<span class="aviso">' . ieadc_pend($a['aviso']) . '</span>';
}
if ($botoes) {
	echo '<div class="btns">';
	foreach ($botoes as $b) {
		echo ieadc_btn($b['texto'], (string) ($b['link'] ?? ''), (string) ($b['estilo'] ?? ''));
	}
	echo '</div>';
}
echo '</div>';
if ($valores) {
	echo '<ul class="valores">';
	foreach ($valores as $v) {
		echo '<li>' . (!empty($v['icone']) ? ieadc_icone($v['icone']) : '') . ieadc_pend($v['texto']) . '</li>';
	}
	echo '</ul>';
}
echo '</div>' . ieadc_secao_fecha();
