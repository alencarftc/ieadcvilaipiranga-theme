<?php
/**
 * Bloco ieadc/artigos-categorias (B3). HTML esperado: docs/specs/referencias/artigos-categorias.html.
 * Cartão comum: etiqueta com ícone, título e texto. Cartão tracejado (aviso): etiqueta como sobretítulo e texto.
 */
defined('ABSPATH') || exit;
$a = $attributes;
$cartoes = (array) ($a['cartoes'] ?? []);

echo ieadc_secao_abre($a, 'sec gelo curva-top');
echo '<div class="wrap">' . ieadc_cab($a);
if ($cartoes) {
	echo '<div class="grade g3">';
	foreach ($cartoes as $c) {
		$tracejado = !empty($c['tracejado']);
		echo '<div class="cartao" style="padding:34px' . ($tracejado ? ';border-style:dashed' : '') . '">';
		if (!empty($c['chip'])) {
			echo $tracejado
				? '<span class="eyebrow">' . ieadc_pend($c['chip']) . '</span>'
				: '<span class="chip">' . (!empty($c['icone']) ? ieadc_icone($c['icone']) : '') . ieadc_pend($c['chip']) . '</span>';
		}
		if (!empty($c['titulo'])) {
			echo '<h3 class="h3" style="margin-top:13px">' . ieadc_pend($c['titulo']) . '</h3>';
		}
		if (!empty($c['texto'])) {
			echo '<p class="txt" style="margin-top:' . (!empty($c['titulo']) ? '8px' : '13px') . '">' . ieadc_pend($c['texto']) . '</p>';
		}
		echo '</div>';
	}
	echo '</div>';
}
echo '</div>' . ieadc_secao_fecha();
