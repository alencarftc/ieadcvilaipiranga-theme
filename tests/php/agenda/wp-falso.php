<?php
/**
 * WordPress mínimo de mentira para testar a agenda sem banco nem Docker.
 * Só o que agenda.php usa. Os eventos avulsos ficam em $GLOBALS['ieadc_teste_eventos'].
 */
define('ABSPATH', __DIR__ . '/');
define('DAY_IN_SECONDS', 86400);

class WP_Post {
	public int $ID = 0;
	public string $post_type = 'evento';
	public string $post_status = 'publish';
	public string $post_title = '';
	public string $post_excerpt = '';
	public string $post_name = '';
	public array $meta = [];
	public function __construct(array $d = []) {
		foreach ($d as $k => $v) $this->$k = $v;
	}
}

$GLOBALS['ieadc_teste_eventos'] = [];
$GLOBALS['ieadc_teste_cache']   = [];
$GLOBALS['ieadc_teste_acoes']   = [];
$GLOBALS['ieadc_teste_consultas'] = 0;

function add_action($gancho, $fn, $prio = 10, $args = 1) { $GLOBALS['ieadc_teste_acoes'][$gancho][] = $fn; }
function do_action($gancho, ...$a) { foreach ($GLOBALS['ieadc_teste_acoes'][$gancho] ?? [] as $fn) $fn(...$a); }

function wp_cache_get($k, $g = '') { return $GLOBALS['ieadc_teste_cache'][$g][$k] ?? false; }
function wp_cache_set($k, $v, $g = '', $exp = 0) { $GLOBALS['ieadc_teste_cache'][$g][$k] = $v; return true; }
function wp_rand() { return random_int(0, PHP_INT_MAX); }

function ieadc_teste_post($id) {
	foreach ($GLOBALS['ieadc_teste_eventos'] as $p) if ($p->ID === (int) $id) return $p;
	return null;
}
function get_posts($q) {
	$GLOBALS['ieadc_teste_consultas']++;
	return array_values(array_filter($GLOBALS['ieadc_teste_eventos'], fn($p) => $p->post_type === $q['post_type'] && $p->post_status === $q['post_status']));
}
function get_post_meta($id, $k, $single) { return ieadc_teste_post($id)->meta[$k] ?? ''; }
function get_post_type($id) { return ieadc_teste_post($id)?->post_type ?? false; }
function get_permalink($p) { return 'http://localhost:8080/' . ($p instanceof WP_Post && $p->post_type === 'evento' ? 'eventos/evento/' : '') . $p->post_name . '/'; }
function home_url($c = '') { return 'http://localhost:8080' . $c; }
function get_page_by_path($c) { return new WP_Post(['post_type' => 'page', 'post_name' => $c]); }
