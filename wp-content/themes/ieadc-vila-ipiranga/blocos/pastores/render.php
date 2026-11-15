<?php
/**
 * Bloco ieadc/pastores (B3). HTML esperado: docs/specs/referencias/pastores.html.
 * Sem foto, o avatar mostra as iniciais do nome (sem o título: Ev., Pb., Pr.…).
 */
defined('ABSPATH') || exit;
$a = $attributes;
$pessoas = array_filter((array) ($a['pessoas'] ?? []), fn($p) => !empty($p['nome']));

$iniciais = static function (string $nome): string {
	$partes = array_values(array_filter(preg_split('/\s+/u', trim($nome)), fn($w) => $w !== '' && !str_ends_with($w, '.')));
	if (!$partes) {
		return '';
	}
	$ini = mb_substr($partes[0], 0, 1);
	if (count($partes) > 1) {
		$ini .= mb_substr(end($partes), 0, 1);
	}
	return mb_strtoupper($ini);
};

echo ieadc_secao_abre($a, 'sec');
echo '<div class="wrap">' . ieadc_cab($a);
if ($pessoas) {
	echo '<div class="grade g2">';
	foreach ($pessoas as $p) {
		$foto = ieadc_img((int) ($p['foto'] ?? 0));
		echo '<div class="lider">';
		echo $foto
			? '<span class="avatar" role="img" aria-label="' . esc_attr($p['nome']) . '" style="background:url(' . esc_url($foto) . ') center/cover no-repeat"></span>'
			: '<span class="avatar">' . esc_html($iniciais($p['nome'])) . '</span>';
		echo '<div>';
		if (!empty($p['cargo'])) {
			echo '<span class="eyebrow">' . ieadc_pend($p['cargo']) . '</span>';
		}
		echo '<h3 class="h3" style="margin-top:8px">' . ieadc_pend($p['nome']) . '</h3>';
		if (!empty($p['nota'])) {
			echo '<p class="note" style="margin-top:8px">' . ieadc_pend($p['nota']) . '</p>';
		}
		echo '</div></div>';
	}
	echo '</div>';
}
echo '</div>' . ieadc_secao_fecha();
