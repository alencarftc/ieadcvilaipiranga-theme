<?php
/**
 * Bloco ieadc/departamentos (B3). HTML esperado: docs/specs/referencias/departamentos.html.
 * Dados: ieadc_departamentos() (M3). Líder sem foto = círculo com ícone e title="Foto em breve";
 * com foto = a foto recortada no mesmo círculo. Linha só de pendência ("[a definir]") vira parágrafo.
 */
defined('ABSPATH') || exit;
$a = $attributes;
$departamentos = function_exists('ieadc_departamentos') ? ieadc_departamentos() : [];

echo ieadc_secao_abre($a, 'sec gelo curva-top');
echo '<div class="wrap">' . ieadc_cab($a);
if (!empty($a['nota'])) {
	echo '<p class="note" style="margin:var(--s3) 0 var(--s4)">' . ieadc_pend($a['nota']) . '</p>';
}
if ($departamentos) {
	echo '<div class="grade g3">';
	foreach ($departamentos as $d) {
		$lideres    = array_filter($d['lideres'], fn($l) => !preg_match('/^\[[^\]]*\]$/u', $l['nome']));
		$pendencias = array_filter($d['lideres'], fn($l) => preg_match('/^\[[^\]]*\]$/u', $l['nome']));
		echo '<div class="dep"><h3 class="h4">' . esc_html($d['nome']) . '</h3>';
		if ($lideres) {
			echo '<ul class="pessoas">';
			foreach ($lideres as $l) {
				$foto = $l['foto'] ? ieadc_img((int) $l['foto'], 'thumbnail') : '';
				echo '<li>';
				echo $foto
					? '<span class="foto-lider" style="background:url(' . esc_url($foto) . ') center/cover no-repeat"></span>'
					: '<span class="foto-lider" title="Foto em breve">' . ieadc_icone('pessoa') . '</span>';
				echo '<span>' . ieadc_pend($l['nome']) . '</span></li>';
			}
			echo '</ul>';
		}
		foreach ($pendencias as $l) {
			echo '<p>' . ieadc_pend($l['nome']) . '</p>';
		}
		if ($d['complemento'] !== '') {
			echo '<p>' . ieadc_pend($d['complemento']) . '</p>';
		}
		echo '</div>';
	}
	echo '</div>';
}
echo '</div>' . ieadc_secao_fecha();
