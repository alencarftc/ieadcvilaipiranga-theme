<?php
/**
 * Bloco ieadc/trocador (B1). HTML esperado: docs/specs/referencias/trocador.html (14 ocorrências).
 * Lista as páginas do grupo (cultos ou ministérios); a atual ganha class="on" aria-current="page".
 * Ícone de cada item = campo ieadc_icone da página.
 */
defined('ABSPATH') || exit;
require_once __DIR__ . '/grupo.php';
$a = $attributes;
$grupo = ($a['grupo'] ?? '') === 'ministerios' ? 'ministerios' : 'cultos';
$paginas = ieadc_b1_grupo($grupo);
if (!$paginas) {
	return;
}
$rotulo = trim((string) ($a['rotulo'] ?? ''));
$rotulo = $rotulo !== '' ? $rotulo : ($grupo === 'ministerios' ? 'Ministérios' : 'Cultos');
$atual = ieadc_b1_atual();

echo '<nav class="trocar" aria-label="' . esc_attr($grupo === 'ministerios' ? 'Escolha um ministério' : 'Escolha um culto') . '">';
echo '<div class="wrap"><span class="rot">' . esc_html($rotulo) . '</span><div class="trilha">';
foreach ($paginas as $p) {
	echo '<a href="' . esc_url(get_permalink($p)) . '"' . ($p->ID === $atual ? ' class="on" aria-current="page"' : '') . '>';
	echo ieadc_icone((string) get_post_meta($p->ID, 'ieadc_icone', true));
	echo '<span>' . esc_html(get_the_title($p)) . '</span></a>';
}
echo '</div></div></nav>';
