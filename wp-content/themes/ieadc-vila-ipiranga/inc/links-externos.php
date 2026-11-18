<?php
/**
 * Links que levam a outro site (fase 7): todo link para fora do site abre em nova aba (target="_blank") com rel="noopener noreferrer",
 * em qualquer lugar da página (menu, rodapé, botões, textos). É feito na saída do HTML, num só ponto, para não depender de cada bloco.
 * Ficam de fora: endereços do próprio site (com ou sem www), mailto:, tel:, âncoras, caminhos relativos e links com `download`.
 */
defined('ABSPATH') || exit;

/** Acrescenta target e rel aos links externos do HTML. */
function ieadc_links_externos_html(string $html): string {
	if ($html === '' || stripos($html, '<a ') === false || !class_exists('WP_HTML_Tag_Processor')) {
		return $html;
	}
	$casa = strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
	$base = (string) preg_replace('/^www\./', '', $casa);
	$p = new WP_HTML_Tag_Processor($html);
	while ($p->next_tag('a')) {
		$href = $p->get_attribute('href');
		if (!is_string($href) || !preg_match('#^(?:https?:)?//([^/?\#:]+)#i', trim($href), $m)) {
			continue;
		}
		$host = strtolower($m[1]);
		if ($host === $casa || preg_replace('/^www\./', '', $host) === $base || $p->get_attribute('download') !== null) {
			continue;
		}
		if ($p->get_attribute('target') === null) {
			$p->set_attribute('target', '_blank');
		}
		$rel = preg_split('/\s+/', trim((string) $p->get_attribute('rel')), -1, PREG_SPLIT_NO_EMPTY);
		foreach (['noopener', 'noreferrer'] as $r) {
			if (!in_array($r, $rel, true)) {
				$rel[] = $r;
			}
		}
		$p->set_attribute('rel', implode(' ', $rel));
	}
	return $p->get_updated_html();
}

add_action('template_redirect', function () {
	if (is_admin() || wp_doing_ajax() || is_feed() || is_robots() || (defined('REST_REQUEST') && REST_REQUEST)) {
		return;
	}
	ob_start('ieadc_links_externos_html');
}, 0);
