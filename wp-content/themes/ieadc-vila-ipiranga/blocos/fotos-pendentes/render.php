<?php
/**
 * Bloco ieadc/fotos-pendentes (B3). HTML esperado: docs/specs/referencias/fotos-pendentes.html.
 * Fica no lugar da galeria de um ministério enquanto não houver fotos autorizadas.
 */
defined('ABSPATH') || exit;
$a = $attributes;

echo ieadc_secao_abre($a, 'sec gelo curva-top');
echo '<div class="wrap">' . ieadc_cab($a);
echo '<div class="cartao sem-fotos">' . (!empty($a['icone']) ? ieadc_icone($a['icone']) : '');
if (!empty($a['aviso'])) {
	echo '<p class="txt">' . ieadc_kses_rico($a['aviso']) . '</p>';
}
echo '</div></div>' . ieadc_secao_fecha();
