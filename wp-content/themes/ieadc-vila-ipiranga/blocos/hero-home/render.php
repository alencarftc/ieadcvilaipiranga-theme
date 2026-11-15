<?php
/**
 * Bloco ieadc/hero-home (B1). HTML esperado: docs/specs/referencias/hero-home.html.
 * Topo da página inicial: foto, "Bem-vindo à IEADC Vila Ipiranga", botões de acesso e frase cursiva.
 */
defined('ABSPATH') || exit;
$a = $attributes;

echo ieadc_secao_abre($a, 'hero');
$fundo = ieadc_fundo($a['foto'] ?? 0, (string) ($a['foco'] ?? ''));
echo '<div class="foto"' . ($fundo !== '' ? ' ' . $fundo : '') . '></div>';
echo '<div class="wrap"><div class="conteudo">';

$eyebrow = trim((string) ($a['eyebrow'] ?? ''));
if ($eyebrow !== '') {
	echo '<span class="eyebrow">' . ieadc_pend($eyebrow) . '</span>';
}
$sigla = trim((string) ($a['sigla'] ?? ''));
$nome  = trim((string) ($a['nome'] ?? ''));
if ($sigla !== '' || $nome !== '') {
	echo '<h1 class="h1 h1-home">';
	if ($sigla !== '') {
		echo '<span class="sigla">' . esc_html($sigla) . '</span>';
	}
	if ($nome !== '') {
		echo '<span class="nome">' . esc_html($nome) . '</span>';
	}
	echo '</h1>';
}
echo ieadc_swoosh('swoosh grosso');

$lead = trim((string) ($a['lead'] ?? ''));
if ($lead !== '') {
	echo '<p class="lead">' . ieadc_pend($lead) . '</p>';
}

$botoes = '';
foreach ((array) ($a['botoes'] ?? []) as $b) {
	$titulo = trim((string) ($b['titulo'] ?? ''));
	if ($titulo === '') {
		continue;
	}
	$estilo = ($b['estilo'] ?? '') === 'sec' ? 'sec' : 'prim';
	$icone  = ieadc_icone((string) ($b['icone'] ?? ''));
	$sub    = trim((string) ($b['sub'] ?? ''));
	$botoes .= '<a class="bh ' . $estilo . '" href="' . esc_url(ieadc_url((string) ($b['link'] ?? ''))) . '">';
	if ($icone !== '') {
		$botoes .= '<span class="bi">' . $icone . '</span>';
	}
	$botoes .= '<span class="bt"><b>' . esc_html($titulo) . '</b>';
	if ($sub !== '') {
		$botoes .= '<small>' . esc_html($sub) . '</small>';
	}
	$botoes .= '</span>' . ieadc_icone('seta') . '</a>';
}
$frase = trim((string) ($a['frase'] ?? ''));
if ($botoes !== '' || $frase !== '') {
	echo '<div class="acesso">';
	if ($botoes !== '') {
		echo '<div class="btns-hero">' . $botoes . '</div>';
	}
	if ($frase !== '') {
		echo '<div class="script script-home">' . esc_html($frase) . '</div>';
	}
	echo '</div>';
}
echo '</div></div>' . ieadc_secao_fecha();
