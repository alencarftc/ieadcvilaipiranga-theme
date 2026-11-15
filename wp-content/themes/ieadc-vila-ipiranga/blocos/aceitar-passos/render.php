<?php
/**
 * Bloco ieadc/aceitar-passos (B4). HTML esperado: docs/specs/referencias/aceitar-passos.html.
 * Ordem obrigatória (decisão de 09/10/2026): introdução, 4 passos com versículo, o que muda,
 * e o botão "Quero aceitar Jesus" que abre a carta do pastor (<details>).
 */
defined('ABSPATH') || exit;
$a = $attributes;
$passos = array_values(array_filter((array) ($a['passos'] ?? []), fn($p) => trim((string) ($p['titulo'] ?? '')) !== ''));
$muda = array_values(array_filter(array_map(fn($i) => trim((string) $i), (array) ($a['muda'] ?? [])), 'strlen'));
$t = static fn($k) => trim((string) ($a[$k] ?? ''));
$versiculo = static function (string $v, string $ref): string {
	if (trim($v) === '') {
		return '';
	}
	return '<blockquote class="versiculo">' . ieadc_kses_rico($v) . (trim($ref) !== '' ? '<cite>' . ieadc_pend($ref) . '</cite>' : '') . '</blockquote>';
};

echo ieadc_secao_abre($a, 'sec glow');
echo '<div class="wrap"><div class="aj-intro">' . ieadc_cab(['eyebrow' => $a['eyebrow'] ?? '', 'titulo' => $a['titulo'] ?? '']);
if ($t('texto') !== '') {
	echo '<p class="txt">' . ieadc_kses_rico($t('texto')) . '</p>';
}
echo '</div>';

if ($passos) {
	echo '<ol class="aj-passos">';
	foreach ($passos as $n => $p) {
		echo '<li><span class="num">' . ($n + 1) . '</span><div><h3 class="h4">' . ieadc_pend((string) $p['titulo']) . '</h3>';
		if (trim((string) ($p['texto'] ?? '')) !== '') {
			echo '<p>' . ieadc_kses_rico($p['texto']) . '</p>';
		}
		echo $versiculo((string) ($p['versiculo'] ?? ''), (string) ($p['referencia'] ?? '')) . '</div></li>';
	}
	echo '</ol>';
}

if ($muda || $t('mudaTitulo') !== '') {
	echo '<div class="aj-muda">';
	if ($t('mudaTitulo') !== '') {
		echo '<h2 class="h3">' . ieadc_pend($t('mudaTitulo')) . '</h2>';
	}
	if ($muda) {
		echo '<ul class="objetivos">';
		foreach ($muda as $m) {
			echo '<li>' . ieadc_icone('ok') . '<span>' . ieadc_pend($m) . '</span></li>';
		}
		echo '</ul>';
	}
	echo $versiculo($t('mudaVersiculo'), $t('mudaReferencia')) . '</div>';
}

if ($t('botaoTexto') !== '') {
	echo '<details class="aj-decisao"><summary><span class="aj-pergunta">';
	if ($t('decisaoEyebrow') !== '') {
		echo '<span class="eyebrow claro">' . ieadc_pend($t('decisaoEyebrow')) . '</span>';
	}
	if ($t('decisaoTitulo') !== '') {
		echo '<span class="h3">' . ieadc_pend($t('decisaoTitulo')) . '</span>';
	}
	if ($t('decisaoTexto') !== '') {
		echo '<span class="aj-sub">' . ieadc_kses_rico($t('decisaoTexto')) . '</span>';
	}
	echo '</span><span class="btn sol aj-btn">' . ieadc_pend($t('botaoTexto')) . ieadc_icone('seta') . '</span></summary>';
	echo '<div class="carta">';
	if ($t('cartaTitulo') !== '') {
		echo '<span class="eyebrow">' . ieadc_pend($t('cartaTitulo')) . '</span>';
	}
	echo ieadc_paragrafos($t('carta'));
	if ($t('assinatura') !== '') {
		echo '<div class="ass">' . ieadc_pend($t('assinatura')) . '</div>';
	}
	echo '</div></details>';
}
echo '</div>' . ieadc_secao_fecha();
