<?php
/**
 * Bloco ieadc/corpo-indice (B4). HTML esperado: docs/specs/referencias/corpo-indice.html.
 * Texto das páginas de culto e de ministério: partes com título (texto, versículo + texto,
 * lista com ✓, "quando | o quê" ou próximas datas da agenda), índice "Nesta página" e quadro azul.
 */
defined('ABSPATH') || exit;
$a = $attributes;
$secoes = array_filter((array) ($a['secoes'] ?? []), fn($s) => trim((string) ($s['titulo'] ?? '')) !== '');

$versiculo = static function (string $v, string $ref): string {
	if (trim($v) === '') {
		return '';
	}
	return '<blockquote class="versiculo">' . ieadc_kses_rico($v) . (trim($ref) !== '' ? '<cite>' . ieadc_pend($ref) . '</cite>' : '') . '</blockquote>';
};

// Links dos itens "quando | o quê": caminho do site vira endereço da página; a classe lk é a do protótipo.
$links = static function (string $html): string {
	return (string) preg_replace_callback('/<a href="([^"]*)">/', fn($m) => '<a class="lk" href="' . esc_url(ieadc_url(html_entity_decode($m[1]))) . '">', $html);
};

// Próximas datas: títulos do atributo ou, se vazio, do campo "Títulos na agenda" (meta ieadc_regra) da página.
$datas = static function () use ($a): string {
	if (!function_exists('ieadc_agenda_proximas')) {
		return '';
	}
	$txt = trim((string) ($a['datasTitulos'] ?? ''));
	if ($txt === '' && get_the_ID()) {
		$txt = (string) get_post_meta(get_the_ID(), 'ieadc_regra', true);
	}
	$titulos = array_values(array_filter(array_map('trim', preg_split('/\R/', $txt))));
	if (!$titulos) {
		return '';
	}
	$sem = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
	$meses = ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
	$hoje = ieadc_hoje()->format('Y-m-d');
	$h = '';
	foreach (ieadc_agenda_proximas($titulos, max(1, (int) ($a['datasQuantidade'] ?? 4))) as $o) {
		$d = new DateTimeImmutable($o['data']);
		$h .= '<li><b>' . $sem[(int) $d->format('w')] . ', ' . (int) $d->format('j') . ' de ' . $meses[(int) $d->format('n')] . '</b>';
		$h .= '<span>' . ieadc_pend((string) $o['hora']) . ' · ' . esc_html($o['titulo']) . ($o['data'] === $hoje ? ' · hoje' : '') . '</span></li>';
	}
	return $h !== '' ? '<ul class="encontros">' . $h . '</ul>' : '';
};

echo ieadc_secao_abre($a, 'sec');
echo '<div class="wrap min-corpo"><article class="leitura min-texto">';
if (trim((string) ($a['aviso'] ?? '')) !== '') {
	echo '<span class="aviso">' . ieadc_pend($a['aviso']) . '</span>';
}
$indice = '';
foreach ($secoes as $s) {
	$ancora = sanitize_title((string) ($s['ancora'] ?? ''));
	$titulo = (string) $s['titulo'];
	$itens = array_values(array_filter(array_map(fn($i) => trim((string) $i), (array) ($s['itens'] ?? [])), 'strlen'));
	echo '<h2 class="h2"' . ($ancora !== '' ? ' id="' . esc_attr($ancora) . '"' : '') . '>' . ieadc_pend($titulo) . '</h2>' . ieadc_swoosh();
	switch ($s['tipo'] ?? 'texto') {
		case 'versiculo+texto':
			echo $versiculo((string) ($s['versiculo'] ?? ''), (string) ($s['referencia'] ?? ''));
			echo ieadc_paragrafos((string) ($s['texto'] ?? ''));
			break;
		case 'objetivos':
			if ($itens) {
				echo '<ul class="objetivos">';
				foreach ($itens as $i) {
					echo '<li>' . ieadc_icone('ok') . '<span>' . ieadc_kses_rico($i) . '</span></li>';
				}
				echo '</ul>';
			}
			break;
		case 'encontros':
			if ($itens) {
				echo '<ul class="encontros">';
				foreach ($itens as $i) {
					[$quando, $oque] = array_pad(array_map('trim', explode('|', $i, 2)), 2, '');
					echo '<li><b>' . ieadc_kses_rico($quando) . '</b>' . ($oque !== '' ? '<span>' . $links(ieadc_kses_rico($oque)) . '</span>' : '') . '</li>';
				}
				echo '</ul>';
			}
			break;
		case 'datas':
			echo $datas();
			break;
		default:
			echo ieadc_paragrafos((string) ($s['texto'] ?? ''));
	}
	if ($ancora !== '' && empty($s['foraDoIndice'])) {
		$indice .= '<a href="#' . esc_attr($ancora) . '">' . ieadc_pend($titulo) . '</a>';
	}
}
echo '</article><aside class="min-lado">';

foreach ((array) ($a['indiceExtras'] ?? []) as $x) {
	$anc = sanitize_title((string) ($x['ancora'] ?? ''));
	if ($anc !== '' && trim((string) ($x['titulo'] ?? '')) !== '') {
		$indice .= '<a href="#' . esc_attr($anc) . '">' . ieadc_pend($x['titulo']) . '</a>';
	}
}
if ($indice !== '') {
	echo '<div class="indice">' . (!empty($a['indiceTitulo']) ? '<span class="eyebrow">' . ieadc_pend($a['indiceTitulo']) . '</span>' : '') . $indice . '</div>';
}

// Quadro azul: "Quem lidera" (um nome por linha, "e equipe" no fim) ou um texto (ex.: endereço).
$lideres = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($a['lideres'] ?? ''))), 'strlen'));
$equipe = $lideres && mb_strtolower(end($lideres)) === 'e equipe';
if ($equipe) {
	array_pop($lideres);
}
$texto = trim((string) ($a['lateralTexto'] ?? ''));
$botoes = array_filter((array) ($a['botoes'] ?? []), fn($b) => trim((string) ($b['texto'] ?? '')) !== '');
if ($lideres || $texto !== '' || $botoes || !empty($a['lateralTitulo'])) {
	echo '<div class="lateral">';
	if (!empty($a['lateralTitulo'])) {
		echo '<span class="eyebrow claro">' . ieadc_pend($a['lateralTitulo']) . '</span>';
	}
	if ($lideres) {
		echo '<ul class="lideres-lado">';
		foreach ($lideres as $n) {
			echo '<li><span class="foto-lider" title="Foto em breve">' . ieadc_icone('pessoa') . '</span><span>' . ieadc_pend($n) . '</span></li>';
		}
		echo '</ul>';
	} elseif ($texto !== '') {
		echo '<p style="margin-top:8px;color:#fff;font:600 17px/1.45 var(--f-tit)">' . ieadc_kses_rico($texto) . '</p>';
	}
	if ($equipe) {
		echo '<p class="equipe">e equipe</p>';
	}
	if ($botoes) {
		echo '<div class="btns">';
		foreach ($botoes as $b) {
			echo ieadc_btn((string) $b['texto'], (string) ($b['link'] ?? ''), (string) ($b['classe'] ?? ''), (string) ($b['icone'] ?? 'seta'));
		}
		echo '</div>';
	}
	echo '</div>';
}
echo '</aside></div>' . ieadc_secao_fecha();
