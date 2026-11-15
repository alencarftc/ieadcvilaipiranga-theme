<?php
/**
 * Bloco ieadc/historia (B4). HTML esperado: docs/specs/referencias/historia.html.
 * Texto da história à esquerda; à direita a foto e o cartão com os dirigentes (do atual ao mais antigo).
 */
defined('ABSPATH') || exit;
$a = $attributes;
$dirigentes = array_values(array_filter(array_map(fn($i) => trim((string) $i), (array) ($a['dirigentes'] ?? [])), 'strlen'));
$foto = ieadc_img((int) ($a['foto'] ?? 0));

echo ieadc_secao_abre($a, 'sec gelo curva-top');
echo '<div class="wrap" style="display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:55px;align-items:start" data-cols>';
echo '<div class="leitura">';
if (!empty($a['eyebrow'])) {
	echo '<span class="eyebrow">' . ieadc_pend($a['eyebrow']) . '</span>';
}
if (!empty($a['titulo'])) {
	echo '<h2 class="h2" style="margin-top:8px">' . ieadc_kses_rico($a['titulo']) . '</h2>';
}
echo ieadc_swoosh() . ieadc_paragrafos((string) ($a['texto'] ?? '')) . '</div>';
echo '<div>';
if ($foto !== '') {
	$estilo = 'margin-top:0;aspect-ratio:4/3;background-image:url(' . esc_url($foto) . ')';
	if (trim((string) ($a['foco'] ?? '')) !== '') {
		$estilo .= ';background-position:' . trim($a['foco']);
	}
	echo '<div class="capa" style="' . esc_attr($estilo) . '"></div>';
}
if ($dirigentes) {
	echo '<div class="cartao" style="' . ($foto !== '' ? 'margin-top:34px;' : '') . 'padding:34px">';
	if (!empty($a['dirigentesTitulo'])) {
		echo '<span class="eyebrow">' . ieadc_pend($a['dirigentesTitulo']) . '</span>';
	}
	echo '<ul class="dirigentes" style="columns:1;margin-top:13px">';
	foreach ($dirigentes as $d) {
		echo '<li>' . ieadc_pend($d) . '</li>';
	}
	echo '</ul></div>';
}
echo '</div></div>' . ieadc_secao_fecha();
