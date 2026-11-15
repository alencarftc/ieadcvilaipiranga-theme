<?php
/**
 * Bloco ieadc/leitura (B4). HTML esperado: docs/specs/referencias/leitura.html.
 * Texto corrido: parágrafos separados por linha em branco; linha com "### " vira <h3>.
 * Também aceita o texto já em HTML (<p>, <h3> e as marcas do texto rico).
 */
defined('ABSPATH') || exit;
$a = $attributes;
$texto = trim(str_replace("\r\n", "\n", (string) ($a['conteudo'] ?? '')));
if ($texto === '') {
	return;
}
if (preg_match('/<(p|h3)\b/i', $texto)) {
	$partes = preg_split('/(<h3\b[^>]*>.*?<\/h3>|<p\b[^>]*>.*?<\/p>)/is', $texto, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
	$blocos = [];
	foreach ($partes as $p) {
		if (preg_match('/^<h3\b[^>]*>(.*)<\/h3>$/is', trim($p), $m)) {
			$blocos[] = ['h3', $m[1]];
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
		$blocos[] = str_starts_with($par, '### ') ? ['h3', trim(substr($par, 4))] : ['p', $par];
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
