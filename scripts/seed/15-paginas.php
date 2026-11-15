<?php
// Páginas da Estrutura do site (versão de transição: HTML do protótipo) e configurações de leitura.

$ids = [];
$ids['inicio']    = pagina('inicio', 'Início', 0, 'inicio');
$ids['sobre']     = pagina('sobre-nos', 'Sobre nós', 0, 'sobre-nos');
$ids['lideranca'] = pagina('lideranca', 'Liderança', 0, 'lideranca');
$ids['minis']     = pagina('ministerios', 'Ministérios', 0, 'ministerios');
$minis = ieadc_seed_minis();
foreach ($minis as $slug => [$t]) {
	$ids['m-' . $slug] = pagina($slug, $t, $ids['minis'], 'ministerios-' . $slug);
}
$ids['horarios'] = pagina('horarios-e-localizacao', 'Horários e localização', 0, 'horarios-e-localizacao');
$cultos = ieadc_seed_cultos();
foreach ($cultos as $slug => [$t]) {
	$ids['c-' . $slug] = pagina($slug, $t, 0, $slug);
}
$ids['eventos']   = pagina('eventos', 'Eventos', 0, 'eventos');
$ids['artigos']   = pagina('artigos', 'Artigos', 0, 'artigos');
$ids['galeria']   = pagina('galeria', 'Galeria de fotos e vídeos', 0, 'galeria');
$ids['aceitar']   = pagina('aceitar-jesus', 'Aceitar Jesus', 0, 'aceitar-jesus');
$ids['contato']   = pagina('contato', 'Fale conosco', 0, 'contato');
$ids['pedidos']   = pagina('pedidos-de-oracao', 'Pedidos de oração', 0, 'pedidos-de-oracao');
$ids['politica']  = pagina('politica-de-privacidade', 'Política de privacidade', 0, 'politica-de-privacidade');

// Leitura: página inicial fixa, artigos na página Artigos, política oficial do WordPress.
update_option('show_on_front', 'page');
update_option('page_on_front', $ids['inicio']);
update_option('page_for_posts', 0); // a página Artigos tem layout próprio

// Artigos de exemplo do protótipo (posts).
foreach (['alcancar-curitiba' => 'Alcançar Curitiba para Cristo', 'ate-que-ele-venha' => 'Até que ele venha', 'orai-uns-pelos-outros' => 'Orai uns pelos outros'] as $slug => $t) {
	pagina($slug, $t, 0, 'artigo-' . $slug, 'post');
}
update_option('wp_page_for_privacy_policy', $ids['politica']);

// Some com o "Olá, mundo!" e a "Página de exemplo" da instalação.
foreach (['ola-mundo', 'hello-world'] as $s) {
	$p = get_page_by_path($s, OBJECT, 'post');
	$p && wp_delete_post($p->ID, true);
}
foreach (['pagina-exemplo', 'sample-page'] as $s) {
	$p = get_page_by_path($s);
	$p && wp_delete_post($p->ID, true);
}

