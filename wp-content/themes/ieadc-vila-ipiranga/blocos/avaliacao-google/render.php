<?php
/**
 * Bloco ieadc/avaliacao-google (fase 2). HTML esperado: docs/specs/referencias/avaliacao-google.html.
 * Convite para avaliar a igreja no Google: 5 estrelas decorativas, título, texto curto e botão que abre em nova aba.
 * O link é o de Configurações > IEADC Vila Ipiranga ("Link para avaliar no Google"); vazio = a igreja no Google Maps.
 * Não mostra nota nem número de avaliações (não há dado real).
 */
defined('ABSPATH') || exit;
$a = $attributes;
$estrela = '<svg class="estrela" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.6l2.9 6.1 6.7.9-4.9 4.7 1.2 6.7L12 17.7l-5.9 3.3 1.2-6.7L2.4 9.6l6.7-.9z"/></svg>';

echo ieadc_secao_abre($a, 'sec');
echo '<div class="wrap"><div class="avalie">';
echo '<div class="estrelas" aria-hidden="true">' . str_repeat($estrela, 5) . '</div>';
if (trim((string) ($a['eyebrow'] ?? '')) !== '') {
	echo '<span class="eyebrow">' . ieadc_kses_rico((string) $a['eyebrow']) . '</span>';
}
if (trim((string) ($a['titulo'] ?? '')) !== '') {
	echo '<h2 class="h2">' . ieadc_kses_rico((string) $a['titulo']) . '</h2>';
}
echo ieadc_swoosh();
if (trim((string) ($a['texto'] ?? '')) !== '') {
	echo '<p class="txt">' . ieadc_kses_rico((string) $a['texto']) . '</p>';
}
$botao = trim((string) ($a['botaoTexto'] ?? ''));
if ($botao !== '') {
	$ico = function_exists('ieadc_icone') ? ieadc_icone('seta') : '';
	echo '<div class="btns"><a class="btn" href="' . esc_url(ieadc_google_avaliacao_url()) . '" target="_blank" rel="noopener" aria-label="'
		. esc_attr($botao . ' (abre em nova aba)') . '">' . esc_html($botao) . $ico . '</a></div>';
}
echo '</div></div>' . ieadc_secao_fecha();
