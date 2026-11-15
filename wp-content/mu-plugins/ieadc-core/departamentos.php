<?php
/**
 * Departamentos da igreja (página Liderança).
 * Tipo não público: só aparece no painel (Departamentos), para quem edita páginas.
 * Campos: lideres (um por linha), complemento ("e equipe"), ordem e fotos_lideres (IDs na ordem dos líderes; 0 = sem foto).
 * Leitura: ieadc_departamentos().
 */
defined('ABSPATH') || exit;

add_action('init', function () {
	register_post_type('departamento', [
		'labels'          => [
			'name' => 'Departamentos', 'singular_name' => 'Departamento', 'add_new' => 'Adicionar novo',
			'add_new_item' => 'Novo departamento', 'edit_item' => 'Editar departamento',
			'all_items' => 'Todos os departamentos', 'menu_name' => 'Departamentos',
			'search_items' => 'Buscar departamentos', 'not_found' => 'Nenhum departamento encontrado.',
		],
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'show_in_rest'    => true,  // campos na REST API (o editor de blocos fica desligado abaixo)
		'publicly_queryable' => false,
		'exclude_from_search' => true,
		'menu_icon'       => 'dashicons-groups',
		'menu_position'   => 8,
		'supports'        => ['title', 'custom-fields'],
		'capability_type' => 'page', // quem edita páginas (editor, administrador)
		'map_meta_cap'    => true,
	]);

	$meta = [
		'lideres'     => ['type' => 'string', 'sanitize_callback' => 'sanitize_textarea_field'],
		'complemento' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
		'ordem'       => ['type' => 'integer', 'sanitize_callback' => 'absint', 'default' => 0],
	];
	foreach ($meta as $chave => $args) {
		register_post_meta('departamento', $chave, $args + ['single' => true, 'show_in_rest' => true]);
	}
	register_post_meta('departamento', 'fotos_lideres', [
		'type'              => 'array',
		'single'            => true,
		'default'           => [],
		'sanitize_callback' => 'ieadc_sanitizar_ids',
		'show_in_rest'      => ['schema' => ['type' => 'array', 'items' => ['type' => 'integer']]],
	]);
});

/** Lista de IDs inteiros (aceita array ou texto separado por vírgula). */
function ieadc_sanitizar_ids($v): array {
	if (is_string($v)) {
		$v = explode(',', $v);
	}
	return array_values(array_map('absint', (array) $v));
}

/** Nomes dos líderes (um por linha, sem linhas vazias). */
function ieadc_linhas(string $txt): array {
	return array_values(array_filter(array_map('trim', preg_split('/\R/', $txt)), 'strlen'));
}

/**
 * Departamentos publicados, na ordem do campo "ordem".
 * @return array<int, array{nome: string, lideres: array<int, array{nome: string, foto: int}>, complemento: string}>
 */
function ieadc_departamentos(): array {
	$posts = get_posts([
		'post_type'      => 'departamento',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC'],
		'no_found_rows'  => true,
	]);
	$lista = [];
	foreach ($posts as $p) {
		$fotos   = ieadc_sanitizar_ids(get_post_meta($p->ID, 'fotos_lideres', true) ?: []);
		$lideres = [];
		foreach (ieadc_linhas((string) get_post_meta($p->ID, 'lideres', true)) as $i => $nome) {
			$foto = $fotos[$i] ?? 0;
			$lideres[] = ['nome' => $nome, 'foto' => ($foto && wp_attachment_is_image($foto)) ? $foto : 0];
		}
		$lista[] = [
			'nome'        => get_the_title($p),
			'lideres'     => $lideres,
			'complemento' => (string) get_post_meta($p->ID, 'complemento', true),
			'ordem'       => (int) get_post_meta($p->ID, 'ordem', true),
		];
	}
	usort($lista, fn($a, $b) => $a['ordem'] <=> $b['ordem']);
	return array_map(fn($d) => array_diff_key($d, ['ordem' => 1]), $lista);
}

/* "ordem" é espelhada em menu_order, para as listas (painel, wp-cli, REST) saírem na ordem. */
add_action('updated_post_meta', 'ieadc_dep_espelhar_ordem', 10, 4);
add_action('added_post_meta', 'ieadc_dep_espelhar_ordem', 10, 4);
function ieadc_dep_espelhar_ordem($meta_id, $post_id, $chave, $valor): void {
	if ($chave !== 'ordem' || get_post_type($post_id) !== 'departamento') {
		return;
	}
	if ((int) get_post_field('menu_order', $post_id) !== (int) $valor) {
		global $wpdb;
		$wpdb->update($wpdb->posts, ['menu_order' => (int) $valor], ['ID' => $post_id]);
		clean_post_cache($post_id);
	}
}

/* Sem ordem pedida, departamentos saem por "ordem" (lista do painel e wp post list). */
add_action('pre_get_posts', function (WP_Query $q) {
	if ($q->get('post_type') === 'departamento' && !$q->get('orderby')) {
		$q->set('orderby', ['menu_order' => 'ASC', 'title' => 'ASC']);
		$q->set('order', 'ASC');
	}
});

/* Departamento é um cadastro simples: editor clássico, sem blocos. */
add_filter('use_block_editor_for_post_type', fn($usar, $tipo) => $tipo === 'departamento' ? false : $usar, 10, 2);

/* ---------- Painel ---------- */

add_filter('manage_departamento_posts_columns', function ($cols) {
	return ['cb' => $cols['cb'], 'title' => 'Departamento', 'lideres' => 'Líderes', 'ordem' => 'Ordem'];
});
add_action('manage_departamento_posts_custom_column', function ($col, $id) {
	if ($col === 'lideres') {
		echo esc_html(implode(', ', ieadc_linhas((string) get_post_meta($id, 'lideres', true))));
		$c = get_post_meta($id, 'complemento', true);
		echo $c ? ' ' . esc_html($c) : '';
	} elseif ($col === 'ordem') {
		echo (int) get_post_meta($id, 'ordem', true);
	}
}, 10, 2);
add_filter('manage_edit-departamento_sortable_columns', fn($c) => $c + ['ordem' => 'menu_order']);

add_action('add_meta_boxes_departamento', function () {
	add_meta_box('ieadc-departamento', 'Líderes do departamento', 'ieadc_dep_caixa', 'departamento', 'normal', 'high');
});

function ieadc_dep_caixa(WP_Post $p): void {
	wp_nonce_field('ieadc_dep_salvar', 'ieadc_dep_nonce');
	wp_enqueue_media();
	$lideres = ieadc_linhas((string) get_post_meta($p->ID, 'lideres', true));
	$fotos   = ieadc_sanitizar_ids(get_post_meta($p->ID, 'fotos_lideres', true) ?: []);
	$ordem   = (int) get_post_meta($p->ID, 'ordem', true);
	?>
	<p><label for="ieadc-lideres"><strong>Líderes</strong> (um por linha)</label><br>
		<textarea id="ieadc-lideres" name="ieadc_lideres" rows="5" class="large-text"><?php echo esc_textarea(implode("\n", $lideres)); ?></textarea></p>
	<p><label for="ieadc-complemento"><strong>Complemento</strong></label><br>
		<input id="ieadc-complemento" name="ieadc_complemento" type="text" class="regular-text" placeholder="e equipe" value="<?php echo esc_attr(get_post_meta($p->ID, 'complemento', true)); ?>"></p>
	<p><label for="ieadc-ordem"><strong>Ordem na página Liderança</strong></label><br>
		<input id="ieadc-ordem" name="ieadc_ordem" type="number" min="0" step="1" class="small-text" value="<?php echo esc_attr($ordem); ?>"></p>
	<?php if ($lideres) : ?>
		<p><strong>Fotos dos líderes</strong><br><span class="description">Sem foto, o site mostra um círculo com ícone. Mudou a lista de líderes? Salve antes de trocar as fotos.</span></p>
		<table class="widefat striped" style="max-width:640px">
			<?php foreach ($lideres as $i => $nome) : $foto = $fotos[$i] ?? 0; ?>
				<tr class="ieadc-foto-linha">
					<td style="width:72px"><span class="ieadc-foto-previa"><?php echo $foto ? wp_get_attachment_image($foto, [56, 56], false, ['style' => 'border-radius:50%;width:56px;height:56px;object-fit:cover']) : ''; ?></span></td>
					<td><?php echo esc_html($nome); ?></td>
					<td style="text-align:right">
						<input type="hidden" name="ieadc_fotos[<?php echo (int) $i; ?>]" value="<?php echo (int) $foto; ?>">
						<button type="button" class="button ieadc-foto-escolher">Escolher foto</button>
						<button type="button" class="button-link ieadc-foto-tirar"<?php echo $foto ? '' : ' hidden'; ?>>Tirar foto</button>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
		<script>
		document.querySelectorAll('.ieadc-foto-linha').forEach(function (tr) {
			var campo = tr.querySelector('input'), previa = tr.querySelector('.ieadc-foto-previa'), tirar = tr.querySelector('.ieadc-foto-tirar');
			tr.querySelector('.ieadc-foto-escolher').addEventListener('click', function () {
				var f = wp.media({title: 'Foto do líder', library: {type: 'image'}, multiple: false, button: {text: 'Usar esta foto'}});
				f.on('select', function () {
					var a = f.state().get('selection').first().toJSON(), u = (a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url);
					campo.value = a.id;
					previa.innerHTML = '<img src="' + encodeURI(u) + '" alt="" style="border-radius:50%;width:56px;height:56px;object-fit:cover">';
					tirar.hidden = false;
				});
				f.open();
			});
			tirar.addEventListener('click', function () { campo.value = 0; previa.innerHTML = ''; tirar.hidden = true; });
		});
		</script>
	<?php endif;
}

add_action('save_post_departamento', function ($id) {
	if (!isset($_POST['ieadc_dep_nonce']) || !wp_verify_nonce(sanitize_key($_POST['ieadc_dep_nonce']), 'ieadc_dep_salvar')) {
		return;
	}
	if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_revision($id) || !current_user_can('edit_post', $id)) {
		return;
	}
	$lideres = ieadc_linhas(sanitize_textarea_field(wp_unslash($_POST['ieadc_lideres'] ?? '')));
	update_post_meta($id, 'lideres', implode("\n", $lideres));
	update_post_meta($id, 'complemento', sanitize_text_field(wp_unslash($_POST['ieadc_complemento'] ?? '')));
	update_post_meta($id, 'ordem', absint($_POST['ieadc_ordem'] ?? 0));
	if (isset($_POST['ieadc_fotos']) && is_array($_POST['ieadc_fotos'])) {
		$fotos = array_slice(ieadc_sanitizar_ids(wp_unslash($_POST['ieadc_fotos'])), 0, count($lideres));
		update_post_meta($id, 'fotos_lideres', $fotos);
	}
});
