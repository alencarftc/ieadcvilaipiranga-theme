<?php
/**
 * Menu principal e mapa do site no formato do protótipo.
 *
 * No painel (Aparência > Menus), um item ganha ícone com a classe CSS "ico-<nome>"
 * (ex.: ico-oracao). Os nomes estão em inc/icones.php.
 */
defined('ABSPATH') || exit;

/** Separa a classe ico-* das outras classes do item. */
function ieadc_item_icone(WP_Post $item): string {
	foreach ((array) $item->classes as $c) {
		if (str_starts_with((string) $c, 'ico-')) {
			return substr($c, 4);
		}
	}
	return '';
}

class IEADC_Walker_Nav extends Walker_Nav_Menu {
	/** Classe do submenu: nível 2 = "sub", nível 3 = "sub sub3"; Horários ganha "cultos". */
	private array $classe_sub = [];

	public function start_lvl(&$output, $depth = 0, $args = null) {
		$classe  = $depth === 0 ? 'sub' : 'sub sub3';
		$extra   = $this->classe_sub[$depth] ?? '';
		$output .= '<ul class="' . esc_attr(trim($classe . ' ' . $extra)) . '">';
	}

	public function end_lvl(&$output, $depth = 0, $args = null) {
		$output .= '</ul>';
	}

	public function start_el(&$output, $data_object, $depth = 0, $args = null, $current_object_id = 0) {
		$item       = $data_object;
		$classes    = (array) $item->classes;
		$tem_filhos = in_array('menu-item-has-children', $classes, true);
		$atual      = in_array('current-menu-item', $classes, true) || in_array('current_page_item', $classes, true);
		$ancestral  = in_array('current-menu-ancestor', $classes, true) || in_array('current-menu-parent', $classes, true);
		$icone      = ieadc_item_icone($item);

		// Submenu com ícones (ex.: cultos) fica mais largo, como no protótipo.
		if ($tem_filhos && in_array('sub-cultos', $classes, true)) {
			$this->classe_sub[$depth] = 'cultos';
		} else {
			unset($this->classe_sub[$depth]);
		}

		$li = [];
		if ($depth > 0 && $tem_filhos) {
			$li[] = 'tem-sub';
		}
		$output .= '<li' . ($li ? ' class="' . esc_attr(implode(' ', $li)) . '"' : '') . '>';

		$a = [];
		if ($depth === 0 && $tem_filhos) {
			$a[] = 'grp';
		}
		if ($atual || $ancestral) {
			$a[] = 'on';
		}

		$titulo  = apply_filters('the_title', $item->title, $item->ID);
		$antes   = $icone ? ieadc_icone($icone) : '';
		$depois  = $tem_filhos ? ieadc_icone('baixo') : '';
		$attrs   = ' href="' . esc_url($item->url ?: '#') . '"';
		$attrs  .= $a ? ' class="' . esc_attr(implode(' ', $a)) . '"' : '';
		$attrs  .= $atual ? ' aria-current="page"' : '';
		$attrs  .= $item->target ? ' target="' . esc_attr($item->target) . '" rel="noopener"' : '';

		$output .= '<a' . $attrs . '>' . $antes . esc_html($titulo) . $depois . '</a>';
	}

	public function end_el(&$output, $data_object, $depth = 0, $args = null) {
		$output .= '</li>';
	}
}

/** Itens do menu em árvore (para o mapa do site no rodapé). */
function ieadc_menu_arvore(string $local): array {
	$locais = get_nav_menu_locations();
	if (empty($locais[$local])) {
		return [];
	}
	$itens = wp_get_nav_menu_items($locais[$local]) ?: [];
	$por_pai = [];
	foreach ($itens as $i) {
		$por_pai[(int) $i->menu_item_parent][] = $i;
	}
	$montar = function (int $pai) use (&$montar, $por_pai): array {
		$saida = [];
		foreach ($por_pai[$pai] ?? [] as $i) {
			$saida[] = ['item' => $i, 'filhos' => $montar((int) $i->ID)];
		}
		return $saida;
	};
	return $montar(0);
}

/** Mapa do site: itens sem filhos juntos na 1ª coluna; cada grupo numa coluna. */
function ieadc_mapa_site(): string {
	$arvore = ieadc_menu_arvore('primary');
	if (!$arvore) {
		return '';
	}
	$link = fn(WP_Post $i) => '<a href="' . esc_url($i->url) . '">' . esc_html($i->title) . '</a>';
	$soltos = '';
	$grupos = '';
	foreach ($arvore as $no) {
		if (!$no['filhos']) {
			$soltos .= '<h4>' . $link($no['item']) . '</h4>';
			continue;
		}
		$grupos .= '<div><h4>' . $link($no['item']) . '</h4><ul>';
		foreach ($no['filhos'] as $f) {
			$grupos .= '<li>' . $link($f['item']);
			if ($f['filhos']) {
				$grupos .= '<ul class="sub-mapa">';
				foreach ($f['filhos'] as $n) {
					$grupos .= '<li>' . $link($n['item']) . '</li>';
				}
				$grupos .= '</ul>';
			}
			$grupos .= '</li>';
		}
		$grupos .= '</ul></div>';
	}
	return '<nav class="mapa-site" aria-label="' . esc_attr__('Mapa do site', 'ieadc') . '">'
		. ($soltos ? '<div class="soltos">' . $soltos . '</div>' : '')
		. $grupos . '</nav>';
}
