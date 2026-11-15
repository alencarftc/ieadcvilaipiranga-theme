<?php
/**
 * Bloco ieadc/carrossel (B4). HTML esperado: docs/specs/referencias/carrossel.html.
 * Fotos em carrossel; setas no cabeçalho e pontos embaixo (o movimento é do assets/js/site.js).
 */
defined('ABSPATH') || exit;
$a = $attributes;
$fotos = [];
foreach ((array) ($a['fotos'] ?? []) as $id) {
	$url = ieadc_img((int) $id);
	if ($url !== '') {
		$fotos[] = $url;
	}
}
if (!$fotos) {
	return;
}
$total = count($fotos);
$legenda = trim((string) ($a['legenda'] ?? ''));

$ctrl = '<div class="car-ctrl"><button type="button" class="car-ant" aria-label="Foto anterior">' . ieadc_icone('esq') . '</button>'
	. '<button type="button" class="car-prox" aria-label="Próxima foto">' . ieadc_icone('dir') . '</button></div>';
// Cabeçalho comum com as setas no lugar do botão (cab(..., direita=ctrl) do gerador).
$cab = ieadc_cab(array_diff_key($a, ['botaoTexto' => 1]));
$cab = substr($cab, 0, -strlen('</div>')) . $ctrl . '</div>';

echo ieadc_secao_abre($a, 'sec gelo curva-top');
echo '<div class="wrap">' . $cab . '<div class="carrossel" data-carrossel><div class="pista">';
foreach ($fotos as $n => $url) {
	$num = $n + 1;
	echo '<figure class="slide" style="' . esc_attr('background-image:url(' . esc_url($url) . ')') . '">';
	echo '<figcaption>' . ($legenda !== '' ? esc_html($legenda) . ' · ' : '') . 'foto ' . $num . ' de ' . $total . '</figcaption></figure>';
}
echo '</div><div class="pontos">';
for ($n = 1; $n <= $total; $n++) {
	echo '<button type="button" aria-label="Foto ' . $n . '"' . ($n === 1 ? ' class="on"' : '') . '></button>';
}
echo '</div></div></div>' . ieadc_secao_fecha();
