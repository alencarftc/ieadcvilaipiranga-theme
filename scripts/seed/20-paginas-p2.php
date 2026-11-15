<?php
// P2: páginas da Igreja montadas com blocos ieadc/* (conteúdo em content/blocos/<pagina>.json).
// Troca o HTML de transição do protótipo pelos blocos; página já editada no painel fica como está
// (exceto com IEADC_ATUALIZAR=1).

$paginas = [
	'sobre-nos'   => 'sobre-nos',
	'lideranca'   => 'lideranca',
	'ministerios' => 'ministerios',
];
foreach (array_keys(ieadc_seed_minis()) as $slug) {
	$paginas['ministerios/' . $slug] = 'ministerios-' . $slug;
}
foreach ($paginas as $caminho => $arquivo) {
	ieadc_seed_pagina_blocos($caminho, '/content/blocos/' . $arquivo . '.json');
}
