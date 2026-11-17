<?php
// Menu principal (estrutura aprovada em 06/10 e 09/10/2026).

$minis  = ieadc_seed_minis();
$cultos = ieadc_seed_cultos();
$id = fn(string $caminho): int => (int) (get_page_by_path($caminho)?->ID ?? 0);
$ids = [
	'inicio' => $id('inicio'), 'sobre' => $id('sobre-nos'), 'lideranca' => $id('lideranca'), 'minis' => $id('ministerios'),
	'horarios' => $id('horarios-e-localizacao'), 'eventos' => $id('eventos'), 'artigos' => $id('artigos'), 'galeria' => $id('galeria'),
	'aceitar' => $id('aceitar-jesus'), 'contato' => $id('contato'), 'pedidos' => $id('pedidos-de-oracao'), 'politica' => $id('politica-de-privacidade'),
];
foreach ($minis as $slug => $_) {
	$ids['m-' . $slug] = $id('ministerios/' . $slug);
}
foreach ($cultos as $slug => $_) {
	$ids['c-' . $slug] = $id($slug);
}

$nome_menu = 'Menu principal';
$menu = wp_get_nav_menu_object($nome_menu);
if (!$menu) {
	$menu_id = wp_create_nav_menu($nome_menu);
	$add = function (int $page_id, int $pai = 0, string $classes = '', string $titulo = '') use ($menu_id): int {
		return wp_update_nav_menu_item($menu_id, 0, [
			'menu-item-object-id' => $page_id,
			'menu-item-object'    => 'page',
			'menu-item-type'      => 'post_type',
			'menu-item-parent-id' => $pai,
			'menu-item-classes'   => $classes,
			'menu-item-title'     => $titulo,
			'menu-item-status'    => 'publish',
		]);
	};
	$add($ids['inicio']);
	$igreja = $add($ids['sobre'], 0, '', 'A Igreja');
	$add($ids['sobre'], $igreja, '', 'Sobre nós');
	$add($ids['lideranca'], $igreja);
	$mi = $add($ids['minis'], $igreja);
	foreach ($minis as $slug => [$t, $ico]) {
		$add($ids['m-' . $slug], $mi, 'ico-' . $ico);
	}
	$hor = $add($ids['horarios'], 0, 'sub-cultos', 'Horários');
	foreach ($cultos as $slug => [$t, $ico]) {
		$add($ids['c-' . $slug], $hor, 'ico-' . $ico);
	}
	$add($ids['eventos']);
	$cont = $add($ids['artigos'], 0, '', 'Conteúdo');
	$add($ids['galeria'], $cont);
	$add($ids['aceitar']);
	$fale = $add($ids['contato'], 0, '', 'Contato');
	$add($ids['pedidos'], $fale);
	$add($ids['politica'], $fale);
	$menu = wp_get_nav_menu_object($menu_id);
	WP_CLI::log('Menu principal criado.');
}

// Fase 5B: "Sobre nós" também como primeiro item do submenu "A Igreja" (antes só havia o título "A Igreja", que leva à mesma página,
// e a página parecia faltar no menu e no mapa do rodapé). Menu criado antes disso: acrescenta o item na posição certa, sem apagar
// o que foi mudado no painel.
if ($ids['sobre']) {
	$itens_menu = wp_get_nav_menu_items($menu->term_id) ?: [];
	$pai_igreja = null;
	$tem_filho = false;
	foreach ($itens_menu as $it) {
		if ((int) $it->object_id !== $ids['sobre']) {
			continue;
		}
		if ((int) $it->menu_item_parent === 0) {
			$pai_igreja = $it;
		} else {
			$tem_filho = true;
		}
	}
	if ($pai_igreja && !$tem_filho) {
		$posicao = PHP_INT_MAX;
		foreach ($itens_menu as $it) {
			if ((int) $it->menu_item_parent === (int) $pai_igreja->ID) {
				$posicao = min($posicao, (int) $it->menu_order);
			}
		}
		$posicao = $posicao === PHP_INT_MAX ? (int) $pai_igreja->menu_order + 1 : $posicao;
		foreach ($itens_menu as $it) {
			if ((int) $it->menu_order >= $posicao) {
				wp_update_post(['ID' => (int) $it->ID, 'menu_order' => (int) $it->menu_order + 1]);
			}
		}
		wp_update_nav_menu_item($menu->term_id, 0, [
			'menu-item-object-id' => $ids['sobre'],
			'menu-item-object'    => 'page',
			'menu-item-type'      => 'post_type',
			'menu-item-parent-id' => (int) $pai_igreja->ID,
			'menu-item-position'  => $posicao,
			'menu-item-title'     => 'Sobre nós',
			'menu-item-status'    => 'publish',
		]);
		WP_CLI::log('Menu: "Sobre nós" acrescentado em A Igreja.');
	}
}

$locais = get_theme_mod('nav_menu_locations', []);
$locais['primary'] = $menu->term_id;
set_theme_mod('nav_menu_locations', $locais);

