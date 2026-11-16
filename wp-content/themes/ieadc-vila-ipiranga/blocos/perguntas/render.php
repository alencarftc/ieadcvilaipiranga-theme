<?php
/**
 * Bloco ieadc/perguntas (fase 2). HTML esperado: docs/specs/referencias/perguntas.html.
 * Perguntas e respostas visíveis (cartões). Os dados estruturados (FAQPage, inc/seo-schema.php) usam a mesma lista
 * (ieadc_perguntas_itens): o que o Google lê é o que a pessoa vê. Sem itens no bloco, vale a lista padrão.
 */
defined('ABSPATH') || exit;
$a = $attributes;
$itens = ieadc_perguntas_itens($a);
if (!$itens) {
	return;
}
echo ieadc_secao_abre($a, 'sec gelo');
echo '<div class="wrap">' . ieadc_cab($a) . '<div class="faq">';
foreach ($itens as $i) {
	echo '<div class="faq-item"><h3 class="h4">' . esc_html($i['pergunta']) . '</h3><p>' . ieadc_kses_rico($i['resposta']) . '</p></div>';
}
echo '</div></div>' . ieadc_secao_fecha();
