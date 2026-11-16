<?php
/**
 * Bloco ieadc/leitura (B4). HTML esperado: docs/specs/referencias/leitura.html.
 * Texto corrido: parágrafos separados por linha em branco; linha com "### " vira título <h2> (a página já tem o h1 no topo;
 * antes era <h3> e pulava um nível). Também aceita o texto já em HTML (<p>, <h2>, <h3> e as marcas do texto rico).
 */
defined('ABSPATH') || exit;
$a = $attributes;
$texto = trim(str_replace("\r\n", "\n", (string) ($a['conteudo'] ?? '')));
if ($texto === '') {
	return;
}
if (preg_match('/<(p|h2|h3)\b/i', $texto)) {
	$partes = preg_split('/(<h[23]\b[^>]*>.*?<\/h[23]>|<p\b[^>]*>.*?<\/p>)/is', $texto, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	$blocos = [];
	foreach ($partes as $p) {
		if (preg_match('/^<h[23]\b[^>]*>(.*)<\/h[23]>$/is', trim($p), $m)) {
			$blocos[] = ['h2', $m[1]];
		} elseif (preg_match('/^<p\b[^>]*>(.*)<\/p>$/is', trim($p), $m)) {
			$blocos[] = ['p', $m[1]];
		} elseif (trim(wp_strip_all_tags($p)) !== '') {
			$blocos[] = ['p', trim($p)];
		}
	}
} else {
	$blocos = [];
	foreach (preg_split('/\n\s*\n/', $texto) as $par) {
		$par = trim($par);
		if ($par === '') {
			continue;
		}
		$blocos[] = str_starts_with($par, '### ') ? ['h2', trim(substr($par, 4))] : ['p', $par];
	}
}

echo ieadc_secao_abre($a, 'sec');
echo '<div class="wrap"><div class="leitura">';
foreach ($blocos as $k => [$tag, $html]) {
	// O primeiro parágrafo encosta no topo, como no protótipo.
	$estilo = ($k === 0 && $tag === 'p') ? ' style="margin-top:0"' : '';
	echo '<' . $tag . $estilo . '>' . ieadc_kses_rico($html) . '</' . $tag . '>';
}
echo '</div></div>' . ieadc_secao_fecha();
