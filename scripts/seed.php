<?php
/**
 * Seed do site (rodado por bin/setup.sh com wp eval-file).
 * Só orquestra: carrega scripts/seed/_funcoes.php e roda scripts/seed/NN-*.php em ordem.
 * Cada arquivo pode rodar várias vezes e não sobrescreve conteúdo editado no painel
 * (exceto com IEADC_ATUALIZAR=1).
 */

// Conteúdo com HTML do protótipo (svg, style): o seed roda sem usuário, então desliga o filtro de HTML.
kses_remove_filters();

$pasta = __DIR__ . '/seed';
require $pasta . '/_funcoes.php';
foreach (glob($pasta . '/[0-9][0-9]-*.php') as $arquivo) {
	WP_CLI::log('Seed: ' . basename($arquivo));
	require $arquivo;
}

WP_CLI::success('Seed concluído.');
