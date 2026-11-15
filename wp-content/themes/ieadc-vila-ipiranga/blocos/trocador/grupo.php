<?php
/**
 * Páginas de um grupo de navegação (B1), usadas pelos blocos ieadc/trocador e ieadc/vizinhos.
 *  - "ministerios": páginas filhas de "ministerios", por ordem da página (menu_order) e ID;
 *  - "cultos": páginas com o campo ieadc_regra (M3), na ordem em que aparecem no menu principal
 *    (as que não estão no menu vão para o fim, por ordem da página e ID).
 */
defined('ABSPATH') || exit;

if (!function_exists('ieadc_b1_grupo')) {
	/** @return WP_Post[] */
	function ieadc_b1_grupo(string $grupo): array {
		if ($grupo === 'ministerios') {
			$pai = get_page_by_path('ministerios');
			return $pai ? get_pages([
				'parent'      => $pai->ID,
				'sort_column' => 'menu_order,ID',
				'post_status' => 'publish',
			]) : [];
		}

		$cultos = get_pages([
			'meta_key'    => 'ieadc_regra',
			'sort_column' => 'menu_order,ID',
			'post_status' => 'publish',
		]);
		$cultos = array_values(array_filter($cultos, fn($p) => trim((string) get_post_meta($p->ID, 'ieadc_regra', true)) !== ''));
		if (count($cultos) < 2) {
			return $cultos;
		}

		$ordem = [];
		$locais = get_nav_menu_locations();
		$menu = $locais['primary'] ?? 0;
		if (!$menu) {
			$menus = wp_get_nav_menus();
			$menu = $menus ? $menus[0]->term_id : 0;
		}
		foreach ($menu ? (wp_get_nav_menu_items($menu) ?: []) : [] as $item) {
			if ($item->type === 'post_type' && $item->object === 'page' && !isset($ordem[(int) $item->object_id])) {
				$ordem[(int) $item->object_id] = count($ordem);
			}
		}
		$pos = array_flip(array_map(fn($p) => $p->ID, $cultos));
		usort($cultos, function ($x, $y) use ($ordem, $pos) {
			$ox = $ordem[$x->ID] ?? PHP_INT_MAX;
			$oy = $ordem[$y->ID] ?? PHP_INT_MAX;
			return $ox <=> $oy ?: $pos[$x->ID] <=> $pos[$y->ID];
		});
		return $cultos;
	}

	/** ID da página que está sendo exibida (0 na prévia do editor). */
	function ieadc_b1_atual(): int {
		$id = is_singular() ? (int) get_queried_object_id() : 0;
		return $id ?: (int) get_the_ID();
	}
}
