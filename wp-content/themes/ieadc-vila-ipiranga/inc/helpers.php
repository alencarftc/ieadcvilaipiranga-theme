<?php
/**
 * Funções comuns de renderização dos blocos ieadc/* (spec M1).
 * Geram o mesmo HTML do gerador do protótipo (prototipo/fonte/gen.py).
 */
defined('ABSPATH') || exit;

/** O traço amarelo→vermelho sob os títulos (SWOOSH do gen.py). */
function ieadc_swoosh(string $classe = 'swoosh'): string {
	return '<svg class="' . esc_attr($classe) . '" viewBox="0 0 260 16" preserveAspectRatio="none" aria-hidden="true"><path d="M3 12C70 3 170 2 257 8"/></svg>';
}

/**
 * Escapa o texto. Até a fase 5B este nome marcava os trechos [a confirmar] em amarelo (<mark class="pend">); a liderança
 * respondeu tudo e mandou tratar o conteúdo como definitivo, então hoje nenhuma página produz marcação amarela e esta
 * função só escapa. O nome continua porque os blocos ainda a chamam (e o que for digitado entre colchetes sai como texto).
 */
function ieadc_pend(string $texto): string {
	return esc_html($texto);
}

/**
 * Texto "rico": só b, strong, i, em, br, small, a[href] e span.azul. Qualquer outra tag ou classe some.
 */
function ieadc_kses_rico(string $html): string {
	$permitidas = [
		'b' => [], 'strong' => [], 'i' => [], 'em' => [], 'br' => [], 'small' => [],
		'a' => ['href' => true],
		'span' => ['class' => true],
	];
	$html = wp_kses($html, $permitidas);
	// span só com a classe permitida (span.azul); outra classe some.
	return (string) preg_replace_callback('/<span\b([^>]*)>/i', function ($m) {
		return preg_match('/\bclass\s*=\s*(["\'])\s*azul\s*\1/i', $m[1]) ? '<span class="azul">' : '<span>';
	}, $html);
}

/** Separa o texto em <p> por linha em branco; cada parágrafo aceita o texto rico. */
function ieadc_paragrafos(string $texto, string $classe = ''): string {
	$texto = trim(str_replace("\r\n", "\n", $texto));
	if ($texto === '') {
		return '';
	}
	$abre = $classe !== '' ? '<p class="' . esc_attr($classe) . '">' : '<p>';
	$saida = '';
	foreach (preg_split('/\n\s*\n/', $texto) as $par) {
		$par = trim($par);
		if ($par !== '') {
			$saida .= $abre . ieadc_kses_rico($par) . '</p>';
		}
	}
	return $saida;
}

/** URL da mídia (vazio se o ID não for uma imagem existente). */
function ieadc_img($id, string $tamanho = 'full'): string {
	$id = (int) $id;
	if ($id <= 0) {
		return '';
	}
	$url = wp_get_attachment_image_url($id, $tamanho);
	return $url ? (string) $url : '';
}

/**
 * Atributo style completo de uma foto de fundo, igual ao protótipo:
 * style="background-image:url(…);background-position:<foco>;<extra>". Vazio se não houver nada.
 */
function ieadc_fundo($id, string $foco = '', string $extra = '', string $tamanho = 'full'): string {
	$partes = [];
	$url = ieadc_img($id, $tamanho);
	if ($url !== '') {
		$partes[] = 'background-image:url(' . esc_url($url) . ')';
		if (trim($foco) !== '') {
			$partes[] = 'background-position:' . trim($foco);
		}
	}
	if (trim($extra) !== '') {
		$partes[] = trim(trim($extra), ';');
	}
	return $partes ? 'style="' . esc_attr(implode(';', $partes)) . '"' : '';
}

/** Botão igual ao btn() do gerador: <a class="btn <classe>" href="…">texto<svg class="ico">…</svg></a>. */
function ieadc_btn(string $texto, string $link, string $classe = '', string $icone = 'seta'): string {
	if (trim($texto) === '') {
		return '';
	}
	$href = ieadc_url($link);
	if ($href === '' && str_starts_with(trim($link), '{')) { // atalho sem destino (ex.: {uber} sem coordenadas): o botão não aparece
		return '';
	}
	$ico = ($icone !== '' && function_exists('ieadc_icone')) ? ieadc_icone($icone) : '';
	return '<a class="btn ' . esc_attr($classe) . '" href="' . esc_url($href) . '">' . ieadc_kses_rico($texto) . $ico . '</a>';
}

/**
 * Link de um campo "link": URL completa, âncora e mailto/tel ficam como estão;
 * caminho do site ("contato", "/ministerios/familia/") vira o endereço da página.
 */
function ieadc_url(string $link): string {
	$link = trim($link);
	// Atalhos montados a partir do endereço de Configurações > IEADC Vila Ipiranga (fase 4): {maps} = Google Maps, {waze} = Waze.
	if ($link === '{maps}' && function_exists('ieadc_google_maps_url')) {
		return ieadc_google_maps_url();
	}
	if ($link === '{waze}' && function_exists('ieadc_waze_url')) {
		return ieadc_waze_url();
	}
	if ($link === '{uber}' && function_exists('ieadc_uber_url')) {
		return ieadc_uber_url();
	}
	if ($link === '' || preg_match('#^([a-z][a-z0-9+.-]*:|//|\#|\?)#i', $link)) {
		return $link;
	}
	$caminho = trim(preg_replace('/\.html$/', '', $link), '/');
	$ancora = '';
	if (str_contains($caminho, '#')) {
		[$caminho, $ancora] = explode('#', $caminho, 2);
		$caminho = trim($caminho, '/');
		$ancora = '#' . $ancora;
	}
	if ($caminho === '' || $caminho === 'inicio') {
		return home_url('/') . $ancora;
	}
	return (function_exists('ieadc_link') ? ieadc_link($caminho) : home_url('/' . $caminho . '/')) . $ancora;
}

/**
 * Cabeçalho de seção (cab() do gerador):
 * <div class="cab"><div><span class="eyebrow">…</span><h2 class="h2">…</h2>SWOOSH<p class="txt">…</p></div>botão</div>
 * Chaves: eyebrow, titulo, texto, botaoTexto, botaoLink, botaoClasse, botaoIcone; lead=true usa <p class="lead">.
 */
function ieadc_cab(array $a): string {
	$eyebrow = (string) ($a['eyebrow'] ?? '');
	$titulo = (string) ($a['titulo'] ?? '');
	$texto = (string) ($a['texto'] ?? '');
	$h = '<div class="cab"><div>';
	if ($eyebrow !== '') {
		$h .= '<span class="eyebrow">' . ieadc_kses_rico($eyebrow) . '</span>';
	}
	if ($titulo !== '') {
		$h .= '<h2 class="h2">' . ieadc_kses_rico($titulo) . '</h2>';
	}
	$h .= ieadc_swoosh();
	if (trim($texto) !== '') {
		$h .= '<p class="' . (!empty($a['lead']) ? 'lead' : 'txt') . '">' . ieadc_kses_rico($texto) . '</p>';
	}
	$h .= '</div>';
	if (trim((string) ($a['botaoTexto'] ?? '')) !== '') {
		$h .= ieadc_btn((string) $a['botaoTexto'], (string) ($a['botaoLink'] ?? ''), (string) ($a['botaoClasse'] ?? ''), (string) ($a['botaoIcone'] ?? 'seta'));
	}
	return $h . '</div>';
}

/** <section class id style> com os atributos comuns secao, ancora e secaoEstilo. */
function ieadc_secao_abre(array $a, string $padrao = 'sec'): string {
	$classe = trim((string) ($a['secao'] ?? ''));
	$classe = $classe !== '' ? $classe : $padrao;
	$h = '<section';
	if ($classe !== '') {
		$h .= ' class="' . esc_attr($classe) . '"';
	}
	$ancora = trim((string) ($a['ancora'] ?? ''));
	if ($ancora !== '') {
		$h .= ' id="' . esc_attr($ancora) . '"';
	}
	$estilo = trim((string) ($a['secaoEstilo'] ?? ''));
	if ($estilo !== '') {
		$h .= ' style="' . esc_attr($estilo) . '"';
	}
	return $h . '>';
}

function ieadc_secao_fecha(): string {
	return '</section>';
}

/**
 * Perguntas e respostas do bloco ieadc/perguntas (e do FAQPage dos dados estruturados): as do bloco, se houver,
 * senão as padrão, montadas com os dados da igreja (Configurações > IEADC Vila Ipiranga) e com o que as páginas já dizem.
 * Devolve [['pergunta' => texto, 'resposta' => HTML do texto rico], …].
 */
function ieadc_perguntas_itens(array $attrs = []): array {
	$itens = [];
	foreach ((array) ($attrs['itens'] ?? []) as $i) {
		$p = trim((string) ($i['pergunta'] ?? ''));
		$r = trim((string) ($i['resposta'] ?? ''));
		if ($p !== '' && $r !== '') {
			$itens[] = ['pergunta' => $p, 'resposta' => $r];
		}
	}
	if ($itens) {
		return $itens;
	}
	$nome = ieadc_dado('nome');
	$endereco = ieadc_dado('endereco');
	if ($endereco !== '') {
		$itens[] = ['pergunta' => 'Onde fica a ' . $nome . '?',
			'resposta' => 'A igreja fica na ' . esc_html($endereco) . '. <a href="' . esc_url(ieadc_google_maps_url()) . '">Veja no Google Maps</a>.'];
	}
	$cultos = array_filter(array_map('trim', preg_split('/\R/', (string) ieadc_dado('cultos'))));
	if ($cultos) {
		$itens[] = ['pergunta' => 'Quais são os horários dos cultos?',
			'resposta' => 'Os cultos e encontros da semana são: ' . esc_html(implode('; ', array_map(fn($c) => str_replace(' · ', ', ', $c), $cultos))) . '.'];
	}
	$itens[] = ['pergunta' => 'Preciso avisar antes de visitar?',
		'resposta' => 'Não. Você é bem-vindo em qualquer culto. Não precisa avisar antes: é só chegar.'];
	foreach ($cultos as $c) { // linha "2º sábado · 19h30 · Ceia do Senhor"
		if (preg_match('/^(\d+º\s+\p{L}+)\s*·\s*(\d{1,2}h(?:\d{2})?)\s*·\s*Ceia/iu', $c, $m)) {
			$itens[] = ['pergunta' => 'Quando acontece a Ceia do Senhor?',
				'resposta' => 'A Ceia do Senhor é celebrada todo ' . esc_html($m[1]) . ' do mês, às ' . esc_html($m[2]) . '.'];
			break;
		}
	}
	$contato = [];
	if (ieadc_dado('telefone') !== '') {
		$contato[] = 'pelo telefone <a href="tel:' . esc_attr(preg_replace('/[^0-9+]/', '', ieadc_dado('telefone'))) . '">' . esc_html(ieadc_dado('telefone')) . '</a>';
	}
	if (ieadc_dado('email') !== '') {
		$contato[] = 'pelo e-mail <a href="mailto:' . esc_attr(antispambot(ieadc_dado('email'))) . '">' . esc_html(antispambot(ieadc_dado('email'))) . '</a>';
	}
	$itens[] = ['pergunta' => 'Como falar com a igreja?',
		'resposta' => ($contato ? 'Fale conosco ' . implode(' ou ', $contato) . '. ' : '') . 'Você também pode enviar uma mensagem pela página <a href="' . esc_url(ieadc_link('contato')) . '">Fale conosco</a>.'];
	$itens[] = ['pergunta' => 'Como enviar um pedido de oração?',
		'resposta' => 'Pela página <a href="' . esc_url(ieadc_link('pedidos-de-oracao')) . '">Pedidos de oração</a>. O pedido chega apenas ao Círculo de Oração e nunca é publicado.'];
	return $itens;
}

/** Divisor da migalha (fase 7): ícone de seta, fora da leitura de tela (o texto da migalha segue em ordem). */
function ieadc_migalha_sep(): string {
	return '<span class="sep" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m9 6 6 6-6 6"/></svg></span>';
}
