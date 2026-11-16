<?php
/**
 * Bloco ieadc/contato-lista (B4). HTML esperado: docs/specs/referencias/contato-lista.html.
 * O formulário é o shortcode [ieadc_contato] do ieadc-core; ao lado, a lista de contatos.
 */
defined('ABSPATH') || exit;
$a = $attributes;
$itens = array_filter((array) ($a['itens'] ?? []), fn($i) => trim((string) ($i['titulo'] ?? '')) !== '' || trim((string) ($i['texto'] ?? '')) !== '');

echo ieadc_secao_abre($a, 'sec');
echo '<div class="wrap" style="display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:55px;align-items:start" data-cols>';
echo do_shortcode('[ieadc_contato]');
echo '<aside class="lateral">';
if (!empty($a['eyebrow'])) {
	echo '<span class="eyebrow claro">' . ieadc_pend($a['eyebrow']) . '</span>';
}
if (!empty($a['titulo'])) {
	echo '<h2 class="h3" style="margin-top:8px">' . ieadc_pend($a['titulo']) . '</h2>';
}
if ($itens) {
	echo '<div class="contato-lista">';
	foreach ($itens as $i) {
		echo '<div>' . (!empty($i['icone']) ? ieadc_icone($i['icone']) : '') . '<p>';
		if (!empty($i['titulo'])) {
			echo '<b>' . ieadc_pend($i['titulo']) . '</b>';
		}
		if (!empty($i['texto'])) {
			// {maps} e {waze}: links montados a partir do endereço de Configurações (fase 4).
			$texto = str_replace(['{maps}', '{waze}'], [function_exists('ieadc_google_maps_url') ? ieadc_google_maps_url() : '', function_exists('ieadc_waze_url') ? ieadc_waze_url() : ''], (string) $i['texto']);
			echo '<span>' . ieadc_kses_rico($texto) . '</span>';
		}
		echo '</p></div>';
	}
	echo '</div>';
}
echo '</aside></div>' . ieadc_secao_fecha();
