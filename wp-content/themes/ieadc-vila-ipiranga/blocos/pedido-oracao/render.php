<?php
/**
 * Bloco ieadc/pedido-oracao (B4). HTML esperado: docs/specs/referencias/pedido-oracao.html.
 * O formulário é o shortcode [ieadc_pedido_oracao] do ieadc-core; a lateral azul vem dos atributos.
 */
defined('ABSPATH') || exit;
$a = $attributes;

echo ieadc_secao_abre($a, 'sec glow');
echo '<div class="wrap" style="display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr);gap:55px;align-items:start" data-cols>';
echo do_shortcode('[ieadc_pedido_oracao]');
echo '<aside class="lateral">';
if (!empty($a['eyebrow'])) {
	echo '<span class="eyebrow claro">' . ieadc_pend($a['eyebrow']) . '</span>';
}
if (!empty($a['titulo'])) {
	echo '<h2 class="h3" style="margin-top:8px">' . ieadc_pend($a['titulo']) . '</h2>';
}
if (trim((string) ($a['texto'] ?? '')) !== '') {
	echo '<p style="margin-top:13px;color:#D6E2EE;font:400 17px/26px var(--f-txt)">' . ieadc_kses_rico($a['texto']) . '</p>';
}
if (trim((string) ($a['versiculo'] ?? '')) !== '') {
	echo '<blockquote class="versiculo" style="background:rgba(255,255,255,.1);color:#fff;margin-top:34px">' . ieadc_kses_rico($a['versiculo']);
	if (!empty($a['referencia'])) {
		echo '<cite style="color:var(--amarelo)">' . ieadc_pend($a['referencia']) . '</cite>';
	}
	echo '</blockquote>';
}
echo '</aside></div>' . ieadc_secao_fecha();
