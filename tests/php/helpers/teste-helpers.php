<?php
/** Testes das funções comuns de renderização (inc/helpers.php). */
require dirname(__DIR__) . '/bootstrap.php';

echo "ieadc_pend\n";
ieadc_igual('Olá <mark class="pend">[a confirmar]</mark> fim', ieadc_pend('Olá [a confirmar] fim'), 'marca [trecho]');
ieadc_igual('a &lt;b&gt; &amp; c', ieadc_pend('a <b> & c'), 'escapa HTML');
ieadc_igual('<mark class="pend">[um]</mark> e <mark class="pend">[dois]</mark>', ieadc_pend('[um] e [dois]'), 'vários trechos');
ieadc_igual('sem marca', ieadc_pend('sem marca'), 'texto sem colchetes');

echo "ieadc_kses_rico\n";
ieadc_igual('<b>a</b> <i>b</i><br>', ieadc_kses_rico('<b>a</b> <i>b</i><br>'), 'mantém b, i e br');
ieadc_igual('alert(1)x', ieadc_kses_rico('<script>alert(1)</script>x'), 'tira a tag script');
ieadc_igual('<a href="/contato/">c</a>', ieadc_kses_rico('<a href="/contato/" onclick="x()">c</a>'), 'a só com href');
ieadc_igual('<span class="azul">m</span>', ieadc_kses_rico('<span class="azul" style="color:red">m</span>'), 'span.azul sem style');
ieadc_igual('<span>m</span>', ieadc_kses_rico('<span class="outra">m</span>'), 'span com outra classe perde a classe');
ieadc_igual('a <mark class="pend">[b]</mark>', ieadc_kses_rico('a [b]'), 'marca [trecho] no texto rico');
ieadc_igual('<mark class="pend">[b]</mark>', ieadc_kses_rico('<mark class="pend">[b]</mark>'), 'não duplica mark existente');
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
ieadc_ok(str_contains($fechada, '<img class="logo"') && str_contains($fechada, '/assets/img/logo-branca.png'), 'tem o logo branco acima do título');
ieadc_ok(strpos($fechada, '<img class="logo"') < strpos($fechada, '<h1>'), 'o logo vem antes do título');
ieadc_ok(str_contains($fechada, 'alt=""'), 'logo decorativo (alt vazio; o nome já está no título)');
ieadc_ok(str_contains($fechada, 'noindex'), 'página fechada não é indexada');
ieadc_ok(is_file(get_theme_root() . '/ieadc-vila-ipiranga/assets/img/logo-branca.png'), 'o arquivo do logo existe no tema');

ieadc_teste_fim();
