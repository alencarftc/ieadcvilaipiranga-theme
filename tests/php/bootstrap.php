<?php
/**
 * Base dos testes PHP que precisam do WordPress de verdade (spec M1).
 *
 * Uso num teste:  require dirname(__DIR__) . '/bootstrap.php';  ...  ieadc_teste_fim();
 * Rode com ./bin/testar.sh php (dentro do contêiner WordPress) ou, num WordPress já instalado,
 * com `php tests/php/helpers/teste-helpers.php` (WP_LOAD=/caminho/wp-load.php se não estiver em /var/www/html).
 *
 * Os testes da agenda (M2) não usam este arquivo: rodam sem WordPress, com o wp-falso.php.
 */
if (PHP_SAPI !== 'cli') {
	exit('Só pela linha de comando.');
}
if (!defined('ABSPATH')) {
	$ieadc_wp = getenv('WP_LOAD') ?: '/var/www/html/wp-load.php';
	if (!is_file($ieadc_wp)) {
		fwrite(STDERR, "wp-load.php não encontrado ($ieadc_wp). Rode ./bin/testar.sh php ou defina WP_LOAD.\n");
		exit(2);
	}
	require $ieadc_wp;
}
if (!function_exists('ieadc_swoosh')) {
	fwrite(STDERR, "O tema ieadc-vila-ipiranga não está ativo (inc/helpers.php não carregou).\n");
	exit(2);
}

$GLOBALS['ieadc_teste'] = ['total' => 0, 'falhas' => 0];

/** Confere uma condição e imprime o resultado. */
function ieadc_ok(bool $cond, string $nome, $detalhe = null): void {
	$GLOBALS['ieadc_teste']['total']++;
	if ($cond) {
		echo "  ok   $nome\n";
		return;
	}
	$GLOBALS['ieadc_teste']['falhas']++;
	echo "  FALHOU $nome\n";
	if ($detalhe !== null) {
		echo '         ' . str_replace("\n", "\n         ", is_string($detalhe) ? $detalhe : var_export($detalhe, true)) . "\n";
	}
}

/** Compara texto (esperado, obtido). */
function ieadc_igual($esperado, $obtido, string $nome): void {
	ieadc_ok($esperado === $obtido, $nome, "esperado: " . var_export($esperado, true) . "\n obtido: " . var_export($obtido, true));
}

/** Imprime o total e sai com código 1 se algo falhou. */
function ieadc_teste_fim(): void {
	$t = $GLOBALS['ieadc_teste'];
	echo "\n{$t['total']} verificações, {$t['falhas']} falha(s).\n";
	exit($t['falhas'] ? 1 : 0);
}
