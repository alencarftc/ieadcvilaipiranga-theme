<?php
/**
 * Bloco ieadc/vizinhos (B1). HTML esperado: docs/specs/referencias/vizinhos.html (13 ocorrências).
 * Anterior e próximo do mesmo grupo (em círculo) e, no meio, o link para todos.
 */
defined('ABSPATH') || exit;
require_once dirname(__DIR__) . '/trocador/grupo.php';
$a = $attributes;
$min = ($a['grupo'] ?? '') === 'ministerios';
$paginas = ieadc_b1_grupo($min ? 'ministerios' : 'cultos');
$n = count($paginas);
if ($n < 2) {
	return;
}
$i = array_search(ieadc_b1_atual(), array_map(fn($p) => $p->ID, $paginas), true);
if ($i === false) {
	// Fora do grupo: no site não mostra nada; na prévia do editor mostra a partir do primeiro.
	if (!(defined('REST_REQUEST') && REST_REQUEST)) {
		return;
	}
	$i = 0;
}
$ant = $paginas[($i - 1 + $n) % $n];
$prox = $paginas[($i + 1) % $n];

$val = static function (string $chave, string $culto, string $ministerio) use ($a, $min): string {
	$v = trim((string) ($a[$chave] ?? ''));
	return $v !== '' ? $v : ($min ? $ministerio : $culto);
};
$rotAnt = $val('rotuloAnterior', 'Culto anterior', 'Ministério anterior');
$rotProx = $val('rotuloProximo', 'Próximo culto', 'Próximo ministério');
$todosTexto = $val('todosTexto', 'Todos os horários', 'Todos os ministérios');
$todosLink = $val('todosLink', 'horarios-e-localizacao', 'ministerios');
$todosIcone = $val('todosIcone', 'relogio', 'pessoas');

echo ieadc_secao_abre($a, '');
echo '<div class="wrap"><div class="vizinhos">';
echo '<a href="' . esc_url(get_permalink($ant)) . '"><span class="ic">' . ieadc_icone('esq') . '</span>';
echo '<span><small>' . esc_html($rotAnt) . '</small><b>' . esc_html(get_the_title($ant)) . '</b></span></a>';
echo '<a href="' . esc_url(ieadc_url($todosLink)) . '" class="todos">' . ieadc_icone($todosIcone) . '<span>' . esc_html($todosTexto) . '</span></a>';
echo '<a href="' . esc_url(get_permalink($prox)) . '" class="dir"><span><small>' . esc_html($rotProx) . '</small><b>' . esc_html(get_the_title($prox)) . '</b></span>';
echo '<span class="ic">' . ieadc_icone('dir') . '</span></a>';
echo '</div></div>' . ieadc_secao_fecha();
