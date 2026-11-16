<?php
// Fase 2 (SEO): resumos das páginas, que viram a meta description (inc/seo.php). Só onde o texto do topo não serve
// (curto demais ou "Última atualização"); as demais páginas usam o texto do próprio topo. Resumo escrito no painel
// (campo "Resumo" da página) não é sobrescrito, a não ser com IEADC_ATUALIZAR=1. Textos só com o que o site já diz.

$resumos = [
	'inicio'                  => 'IEADC Vila Ipiranga, igreja evangélica Assembleia de Deus no Capão Raso, em Curitiba. Veja os horários dos cultos, os ministérios e como chegar.',
	'politica-de-privacidade' => 'Saiba como o site da IEADC Vila Ipiranga cuida dos dados de quem navega, envia um pedido de oração ou escreve para a igreja.',
	'lideranca'               => 'Conheça quem serve à frente da IEADC Vila Ipiranga: o pastoreio e os departamentos da igreja, no Capão Raso, em Curitiba.',
	'culto-de-oracao'         => 'Toda segunda-feira, às 20h, a igreja começa a semana em oração. Participe do Culto de Oração na IEADC Vila Ipiranga, em Curitiba.',
	'culto-dominical'         => 'Todo domingo, às 19h, a IEADC Vila Ipiranga reúne a igreja no culto dominical. Cada domingo do mês tem um tema. Venha nos visitar.',
	'ceia-do-senhor'          => 'Todo 2º sábado do mês, às 19h30, a igreja celebra a Ceia do Senhor. Venha celebrar conosco na IEADC Vila Ipiranga, em Curitiba.',
	'galeria'                 => 'Fotos e vídeos de cultos, eventos e momentos de comunhão da IEADC Vila Ipiranga, igreja no Capão Raso, em Curitiba.',
];
$forcar = getenv('IEADC_ATUALIZAR') === '1';
foreach ($resumos as $slug => $texto) {
	$p = get_page_by_path($slug);
	if (!$p || (!$forcar && trim($p->post_excerpt) !== '') || $p->post_excerpt === $texto) {
		continue;
	}
	wp_update_post(wp_slash(['ID' => $p->ID, 'post_excerpt' => $texto]));
	WP_CLI::log("Resumo (meta description): /{$slug}/");
}

// O Yoast só publica o llms.txt se ligarmos o recurso dele (grava um arquivo físico); o do tema vem do WordPress (inc/seo.php).
$wpseo = (array) get_option('wpseo', []);
if (!empty($wpseo['enable_llms_txt'])) {
	$wpseo['enable_llms_txt'] = false;
	update_option('wpseo', $wpseo);
}
