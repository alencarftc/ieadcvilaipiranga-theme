<?php
/**
 * Bloco ieadc/chamadas (B3). HTML esperado: docs/specs/referencias/chamadas.html.
 * Chamada branca leva botão "branco"; chamada azul leva botão "sol".
 */
defined('ABSPATH') || exit;
$a = $attributes;

echo ieadc_secao_abre($a, ''); // secao vazia = sem classe (Aceitar Jesus)
echo '<div class="wrap duplas">';
foreach ((array) ($a['chamadas'] ?? []) as $c) {
	$azul = ($c['estilo'] ?? '') === 'azul';
	echo '<div class="chamada' . ($azul ? ' azul' : '') . '" ' . ieadc_fundo((int) ($c['foto'] ?? 0)) . '>';
	if (!empty($c['eyebrow'])) {
		echo '<span class="eyebrow claro">' . ieadc_pend($c['eyebrow']) . '</span>';
	}
	echo '<h2 class="h2">' . ieadc_pend((string) ($c['titulo'] ?? '')) . '</h2><div class="fio"></div>';
	if (!empty($c['texto'])) {
		echo '<p class="txt">' . ieadc_pend($c['texto']) . '</p>';
	}
	if (!empty($c['botaoTexto'])) {
		echo '<div class="btns">' . ieadc_btn($c['botaoTexto'], (string) ($c['botaoLink'] ?? ''), $azul ? 'sol' : 'branco') . '</div>';
	}
	echo '</div>';
}
echo '</div>' . ieadc_secao_fecha();
