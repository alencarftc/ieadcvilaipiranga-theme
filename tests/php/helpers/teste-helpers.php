<?php
/** Testes das funções comuns de renderização (inc/helpers.php). */
require dirname(__DIR__) . '/bootstrap.php';

echo "ieadc_pend (só escapa: nenhuma marcação amarela desde a fase 5B)\n";
ieadc_igual('Olá [a confirmar] fim', ieadc_pend('Olá [a confirmar] fim'), 'colchetes saem como texto, sem <mark>');
ieadc_igual('a &lt;b&gt; &amp; c', ieadc_pend('a <b> & c'), 'escapa HTML');
ieadc_igual('[um] e [dois]', ieadc_pend('[um] e [dois]'), 'vários trechos entre colchetes, sem marca');
ieadc_igual('sem marca', ieadc_pend('sem marca'), 'texto sem colchetes');

echo "ieadc_kses_rico\n";
ieadc_igual('<b>a</b> <i>b</i><br>', ieadc_kses_rico('<b>a</b> <i>b</i><br>'), 'mantém b, i e br');
ieadc_igual('alert(1)x', ieadc_kses_rico('<script>alert(1)</script>x'), 'tira a tag script');
ieadc_igual('<a href="/contato/">c</a>', ieadc_kses_rico('<a href="/contato/" onclick="x()">c</a>'), 'a só com href');
ieadc_igual('<span class="azul">m</span>', ieadc_kses_rico('<span class="azul" style="color:red">m</span>'), 'span.azul sem style');
ieadc_igual('<span>m</span>', ieadc_kses_rico('<span class="outra">m</span>'), 'span com outra classe perde a classe');
ieadc_igual('a [b]', ieadc_kses_rico('a [b]'), 'texto rico não marca [trecho]');
ieadc_igual('b', ieadc_kses_rico('<mark class="pend">b</mark>'), 'texto rico não aceita mark.pend (a tag some)');
ieadc_igual('<small>p</small>', ieadc_kses_rico('<small>p</small>'), 'small');

echo "ieadc_paragrafos\n";
ieadc_igual('<p>um</p><p>dois</p>', ieadc_paragrafos("um\n\ndois"), 'linha em branco separa <p>');
ieadc_igual('<p class="lead">a</p>', ieadc_paragrafos('a', 'lead'), 'classe do <p>');
ieadc_igual('', ieadc_paragrafos("  \n "), 'vazio não gera tag');
ieadc_igual('<p>a<br>b</p>', ieadc_paragrafos('a<br>b'), 'aceita rico');

echo "ieadc_img / ieadc_fundo\n";
ieadc_igual('', ieadc_img(0), 'id 0 sem URL');
ieadc_igual('', ieadc_img(999999999), 'id inexistente sem URL');
ieadc_igual('', ieadc_fundo(0), 'fundo vazio');
ieadc_igual('style="padding-top:0"', ieadc_fundo(0, '', 'padding-top:0;'), 'só o extra');
$anexo = get_posts(['post_type' => 'attachment', 'posts_per_page' => 1, 'fields' => 'ids']);
if ($anexo) {
	$url = ieadc_img($anexo[0]);
	ieadc_ok($url !== '' && str_starts_with($url, 'http'), 'ieadc_img devolve URL de mídia real');
	ieadc_igual('style="background-image:url(' . esc_url($url) . ');background-position:center 30%"', ieadc_fundo($anexo[0], 'center 30%'), 'fundo com foco');
	ieadc_igual('style="background-image:url(' . esc_url($url) . ')"', ieadc_fundo($anexo[0]), 'fundo sem foco');
} else {
	echo "  (sem mídia no site: testes de fundo com foto pulados)\n";
}

echo "ieadc_swoosh / ieadc_btn / ieadc_url\n";
ieadc_igual('<svg class="swoosh" viewBox="0 0 260 16" preserveAspectRatio="none" aria-hidden="true"><path d="M3 12C70 3 170 2 257 8"/></svg>', ieadc_swoosh(), 'swoosh padrão');
ieadc_ok(str_contains(ieadc_swoosh('swoosh grosso'), 'class="swoosh grosso"'), 'swoosh com classe');
$b = ieadc_btn('Ver tudo', '/eventos/');
ieadc_ok(str_starts_with($b, '<a class="btn " href="') && str_contains($b, '>Ver tudo<svg class="ico"') && str_ends_with($b, '</svg></a>'), 'botão com seta', $b);
ieadc_ok(!str_contains(ieadc_btn('X', '#a', 'sec', ''), '<svg'), 'botão sem ícone');
ieadc_igual('', ieadc_btn('', '/x/'), 'botão sem texto some');
ieadc_igual('#ancora', ieadc_url('#ancora'), 'âncora fica');
ieadc_igual('mailto:a@b.c', ieadc_url('mailto:a@b.c'), 'mailto fica');
ieadc_igual('https://x.org/a', ieadc_url('https://x.org/a'), 'URL completa fica');
ieadc_igual(home_url('/'), ieadc_url('inicio'), 'inicio vira a home');
ieadc_ok(str_contains(ieadc_url('contato.html'), '/contato/'), 'x.html vira /x/');

echo "ieadc_cab\n";
$esperado = '<div class="cab"><div><span class="eyebrow">Eyebrow</span><h2 class="h2">Título</h2>' . ieadc_swoosh() . '<p class="txt">Texto</p></div>'
	. ieadc_btn('Botão', '/eventos/') . '</div>';
ieadc_igual($esperado, ieadc_cab(['eyebrow' => 'Eyebrow', 'titulo' => 'Título', 'texto' => 'Texto', 'botaoTexto' => 'Botão', 'botaoLink' => '/eventos/']), 'cabeçalho completo');
ieadc_igual('<div class="cab"><div><h2 class="h2">T</h2>' . ieadc_swoosh() . '</div></div>', ieadc_cab(['titulo' => 'T']), 'sem eyebrow, texto nem botão');

echo "ieadc_secao_abre / fecha\n";
ieadc_igual('<section class="sec">', ieadc_secao_abre([], 'sec'), 'classe padrão');
ieadc_igual('<section class="sec gelo curva-top" id="a" style="padding-top:0">', ieadc_secao_abre(['secao' => 'sec gelo curva-top', 'ancora' => 'a', 'secaoEstilo' => 'padding-top:0'], 'sec'), 'atributos comuns');
ieadc_igual('</section>', ieadc_secao_fecha(), 'fecha');

echo "registro de blocos\n";
ieadc_ok(is_array(ieadc_blocos_pastas()), 'ieadc_blocos_pastas');
ieadc_ok(wp_script_is('ieadc-blocos-editor', 'registered'), 'script do editor registrado');

echo "ieadc_site_fechado_bloqueia (site em preparação)\n";
$fechado_antes = get_option('ieadc_site_fechado', null);
$usuario_antes = get_current_user_id();
// Se o teste for interrompido no meio, o site local não pode ficar fechado: restaura de qualquer jeito.
$fechado_restaurar = function () use ($fechado_antes) {
	$fechado_antes === null ? delete_option('ieadc_site_fechado') : update_option('ieadc_site_fechado', $fechado_antes);
};
register_shutdown_function($fechado_restaurar);
wp_set_current_user(0);
delete_option('ieadc_site_fechado');
ieadc_igual(false, ieadc_site_fechado_bloqueia(), 'sem a opção o site é aberto');
update_option('ieadc_site_fechado', 1);
ieadc_igual(true, ieadc_site_fechado_bloqueia(), 'opção ligada: visitante bloqueado');
$admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
if ($admins) {
	wp_set_current_user((int) $admins[0]);
	ieadc_igual(false, ieadc_site_fechado_bloqueia(), 'opção ligada: quem tem login passa');
	wp_set_current_user(0);
}
update_option('ieadc_site_fechado', 0);
ieadc_igual(false, ieadc_site_fechado_bloqueia(), 'opção 0: aberto');
$fechado_restaurar();
wp_set_current_user($usuario_antes);

echo "ieadc_site_fechado_html (página 'em preparação')
";
$fechada = ieadc_site_fechado_html();
ieadc_ok(str_contains($fechada, '<img class="logo"') && str_contains($fechada, '/assets/img/logo_negativo.webp'), 'tem o logo colorido (versão para fundo escuro) acima do título');
ieadc_ok(strpos($fechada, '<img class="logo"') < strpos($fechada, '<h1>'), 'o logo vem antes do título');
ieadc_ok(str_contains($fechada, 'alt="Assembleia de Deus em Curitiba"'), 'o logo tem texto alternativo (o texto da imagem não aparece no título)');
ieadc_ok(str_contains($fechada, 'noindex'), 'página fechada não é indexada');
ieadc_ok(is_file(get_theme_root() . '/ieadc-vila-ipiranga/assets/img/logo_negativo.webp'), 'o arquivo do logo existe no tema');

echo "desempenho (fase 6): fontes separadas em reais e de reserva\n";
$amostra = "@font-face {\n  font-family: 'Roboto';\n  src: url(../fonts/Roboto-normal-latin.woff2) format('woff2');\n}\n/* comentário */\n@font-face {\n  font-family: 'Roboto Fallback';\n  src: local('Arial'), local('Helvetica');\n  size-adjust: 98.7%;\n}\n";
[$reais, $reserva] = ieadc_fontes_dividir($amostra);
ieadc_ok(str_contains($reais, 'Roboto-normal-latin.woff2') && !str_contains($reais, 'Fallback'), 'a @font-face com url() vai para as fontes reais');
ieadc_ok(str_contains($reserva, "'Roboto Fallback'") && str_contains($reserva, 'size-adjust') && !str_contains($reserva, 'woff2'), 'a @font-face com local() fica no CSS do tema');
[$reais_reais, $reserva_real] = ieadc_fontes_dividir((string) file_get_contents(get_theme_root() . '/ieadc-vila-ipiranga/assets/css/fontes.css'));
ieadc_ok(substr_count($reais_reais, '@font-face') === 9 && substr_count($reserva_real, '@font-face') === 5, 'fontes.css: 9 fontes reais (Roboto, Roboto Condensed e Dancing Script) e 5 de reserva', [substr_count($reais_reais, '@font-face'), substr_count($reserva_real, '@font-face')]);

echo "desempenho (fase 6): CSS do tema e das fontes em uploads/ieadc-cache\n";
$arqs = ieadc_css_arquivos();
$up = wp_upload_dir(null, false);
$caminho = fn(string $url): string => $up['basedir'] . substr($url, strlen($up['baseurl']));
ieadc_ok($arqs['tema'] !== '' && $arqs['fontes'] !== '' && is_file($caminho($arqs['tema'])) && is_file($caminho($arqs['fontes'])), 'os dois arquivos existem', $arqs);
ieadc_igual($arqs['tema'], ieadc_css_pacote(), 'ieadc_css_pacote() devolve o CSS do tema');
$css_tema = (string) file_get_contents($caminho($arqs['tema']));
$css_fontes = (string) file_get_contents($caminho($arqs['fontes']));
ieadc_ok(str_contains($css_tema, 'Roboto Fallback') && !str_contains($css_tema, '.woff2') && str_contains($css_tema, '.ieadc-ui'), 'CSS do tema: fontes de reserva e protótipo, sem arquivo de fonte');
ieadc_ok(substr_count($css_fontes, '.woff2') === 9 && !str_contains($css_fontes, 'Fallback') && str_contains($css_fontes, '/assets/fonts/Roboto-normal-latin.woff2') && !str_contains($css_fontes, '../fonts/'), 'CSS das fontes: as 9 @font-face com o endereço completo do tema', substr_count($css_fontes, '.woff2'));
ieadc_ok(strlen($css_fontes) < 6000 && !str_contains($css_fontes, "\n  "), 'CSS das fontes minificado (pequeno)', strlen($css_fontes));
ieadc_ok(str_contains(ieadc_fontes_tag(), 'media="print"') && str_contains(ieadc_fontes_tag(), 'data-ieadc-fontes') && str_contains(ieadc_fontes_tag(), '<noscript>'), 'o <link> das fontes sai com media=print, data-ieadc-fontes e <noscript>');
$site_js = (string) file_get_contents(get_theme_root() . '/ieadc-vila-ipiranga/assets/js/site.js');
ieadc_ok(str_contains($site_js, "link[data-ieadc-fontes]") && str_contains($site_js, "fl.media = 'all'") && str_contains($site_js, "addEventListener('load'"), 'site.js liga as fontes (media=all) depois do carregamento da página');

echo "desempenho (fase 6): foto do topo sob medida para telas estreitas\n";
$anexo = function (string $origem): int {
	$ids = get_posts(['post_type' => 'attachment', 'post_status' => 'any', 'meta_key' => '_ieadc_origem', 'meta_value' => $origem, 'fields' => 'ids', 'posts_per_page' => 1]);
	return (int) ($ids[0] ?? 0);
};
$id_capa = $anexo('f_art_tema.webp'); // 1066x1600, retrato
$id_topo = $anexo('f_hero.webp');     // 1600x1067
ieadc_ok($id_capa > 0 && $id_topo > 0, 'as fotos do seed existem na biblioteca de mídia', [$id_capa, $id_topo]);
if ($id_capa > 0 && $id_topo > 0) {
	$capa = ieadc_foto_estreita($id_capa, true);
	ieadc_ok($capa !== null && $capa['largura'] === 640 && $capa['altura'] === 400, 'capa de artigo: 640x400 (16:10 central, o que o .capa mostra)', $capa);
	ieadc_ok($capa !== null && preg_match('#/ieadc-cache/topo-' . $id_capa . '-capa-640-[0-9a-f]{10}\.webp$#', $capa['url']) === 1, 'o nome leva o id, o tipo, a largura e a marca da original', $capa);
	$arq_capa = $capa ? $caminho($capa['url']) : '';
	$info = $arq_capa !== '' ? getimagesize($arq_capa) : false;
	ieadc_ok($info && $info['mime'] === 'image/webp' && filesize($arq_capa) < 60000, 'é WebP e pesa menos de 60 KB (a de 768 px do WordPress tem ~150 KB)', $arq_capa !== '' && is_file($arq_capa) ? filesize($arq_capa) : 'sem arquivo');
	$topo = ieadc_foto_estreita($id_topo, false);
	ieadc_ok($topo !== null && $topo['largura'] === 640 && abs($topo['altura'] - 427) <= 1, 'topo de página: 640 px de largura, proporção mantida (sem recorte)', $topo);
	ieadc_ok($topo !== null && filesize($caminho($topo['url'])) < 50000, 'topo de página: menos de 50 KB (a de 768 px do WordPress tem ~65 KB)', $topo ? filesize($caminho($topo['url'])) : 'sem arquivo');
	$mtime = $arq_capa !== '' ? filemtime($arq_capa) : 0;
	clearstatcache();
	ieadc_ok($capa !== null && ieadc_foto_estreita($id_capa, true) === $capa && filemtime($arq_capa) === $mtime, 'segunda chamada devolve o mesmo arquivo, sem gerar de novo');
}
ieadc_ok(ieadc_foto_estreita(999999999, true) === null, 'anexo que não existe: null (o tema segue com a foto de 768 px)');
ieadc_ok(ieadc_foto_estreita(0, false) === null, 'id 0: null');

ieadc_teste_fim();
