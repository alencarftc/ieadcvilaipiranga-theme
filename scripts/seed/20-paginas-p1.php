<?php
// P1: páginas prioritárias montadas com blocos ieadc/* (conteúdo em content/blocos/<pagina>.json).
// Troca o HTML de transição do protótipo pelos blocos; página já editada no painel fica como está
// (exceto com IEADC_ATUALIZAR=1).

foreach ([
	'inicio'                 => 'inicio',
	'horarios-e-localizacao' => 'horarios-e-localizacao',
	'contato'                => 'contato',
	'pedidos-de-oracao'      => 'pedidos-de-oracao',
] as $caminho => $arquivo) {
	ieadc_seed_pagina_blocos($caminho, '/content/blocos/' . $arquivo . '.json');
}
