<?php
/**
 * Aviso de cookies e medição de acessos (fase 4, LGPD). Os campos (ID do Tag Manager, do Analytics 4, do Clarity e do Meta Pixel) ficam em
 * Configurações > IEADC Vila Ipiranga e são VAZIOS por padrão: sem ID, este arquivo não imprime nada (nem o aviso, nem o JSON, nem o script).
 * Os IDs, a validação e a política de conteúdo (CSP) estão em wp-content/mu-plugins/ieadc-core/medicao.php; o comportamento (consentimento,
 * carga dos serviços e eventos) está em assets/js/medicao.js; passo a passo para quem cuida do Tag Manager: docs/operacao/rastreamento.md.
 */
defined('ABSPATH') || exit;

/** Nome de cada serviço, como aparece no aviso e na política de privacidade. */
function ieadc_medicao_nomes(): array {
	return [
		'gtm'     => 'Google Analytics (pelo Google Tag Manager)',
		'ga4'     => 'Google Analytics',
		'clarity' => 'Microsoft Clarity',
		'pixel'   => 'Meta Pixel (Facebook e Instagram)',
	];
}

/** "A, B e C". */
function ieadc_medicao_lista_nomes(array $servicos): string {
	$nomes = array_values(array_intersect_key(ieadc_medicao_nomes(), $servicos));
	if (count($nomes) <= 1) {
		return (string) ($nomes[0] ?? '');
	}
	$ultimo = array_pop($nomes);
	return implode(', ', $nomes) . ' e ' . $ultimo;
}

/** O JavaScript só é carregado quando há serviço ligado. Fica no <head> com defer: não bloqueia a página. */
add_action('wp_enqueue_scripts', function () {
	if (!function_exists('ieadc_medicao_ativa') || !ieadc_medicao_ativa()) {
		return;
	}
	$arq = get_stylesheet_directory() . '/assets/js/medicao.js';
	wp_enqueue_script('ieadc-medicao', get_stylesheet_directory_uri() . '/assets/js/medicao.js', [], IEADC_TEMA_VERSAO . '.' . (is_file($arq) ? filemtime($arq) : 0), ['in_footer' => false, 'strategy' => 'defer']);
}, 20);

/**
 * Aviso de cookies (oculto até o JavaScript decidir mostrá-lo) e os IDs em JSON (um bloco de dados, que a CSP não executa).
 * Fica no fim da página (um landmark logo depois do link "Pular para o conteúdo" faz o axe acusar `region` nesse link) e, quando aparece, o JavaScript leva
 * o foco até ele (caixa de diálogo não modal): quem usa teclado ou leitor de tela o encontra sem percorrer a página, e pode sair dele com o Tab.
 */
add_action('wp_footer', function () {
	if (is_customize_preview() || !function_exists('ieadc_medicao_servicos')) {
		return;
	}
	$s = ieadc_medicao_servicos();
	if (!$s) {
		return;
	}
	$politica = get_privacy_policy_url() ?: ieadc_link('politica-de-privacidade');
	$lista = ieadc_medicao_lista_nomes($s);
	echo '<script type="application/json" id="ieadc-medicao">' . wp_json_encode($s, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>' . "\n";
	?>
	<div id="ieadc-cookies" class="ieadc-cookies ieadc-ui" role="dialog" aria-modal="false" aria-labelledby="ieadc-cookies-t" tabindex="-1" hidden>
		<div class="wrap ieadc-cookies__in">
			<div class="ieadc-cookies__txt">
				<p id="ieadc-cookies-t" class="ieadc-cookies__t">Podemos medir os acessos ao site?</p>
				<p>Usamos <?php echo esc_html($lista); ?> para saber quais páginas ajudam mais as pessoas e melhorar o site. Isso usa cookies e só começa se você aceitar; se recusar, nada é carregado e o site funciona do mesmo jeito. Você pode mudar de ideia quando quiser. Leia a <a href="<?php echo esc_url($politica); ?>">política de privacidade</a>.</p>
			</div>
			<div class="ieadc-cookies__bt">
				<button type="button" class="ieadc-cookies__b" data-ieadc-cookies="aceitar">Aceitar</button>
				<button type="button" class="ieadc-cookies__b" data-ieadc-cookies="recusar">Recusar</button>
			</div>
		</div>
	</div>
	<?php
}, 20);

/**
 * Política de privacidade: parágrafo sobre cookies de medição, SÓ quando há serviço ligado (sem ID, a política fica como estava).
 * Entra no fim do texto da página; se o Meta Pixel estiver ligado, sai a frase "O site não usa cookies de publicidade".
 */
add_filter('render_block_ieadc/leitura', function (string $html, array $bloco): string {
	if (!function_exists('ieadc_medicao_servicos') || !is_page('politica-de-privacidade')) {
		return $html;
	}
	$s = ieadc_medicao_servicos();
	if (!$s) {
		return $html;
	}
	if (isset($s['pixel'])) {
		$html = (string) preg_replace('/\s*O site não usa cookies de publicidade\./u', '', $html);
	}
	$txt = 'Este site usa ' . ieadc_medicao_lista_nomes($s) . ' para saber quais páginas são mais visitadas e quais botões são mais usados (por exemplo, telefone, WhatsApp e "Como chegar"). '
		. 'Esses serviços usam cookies e identificadores próprios, e só são ativados depois que você clica em "Aceitar" no aviso de cookies; se você recusar, nada disso é carregado. '
		. 'Cada serviço recebe os dados que coleta por padrão (páginas visitadas, tipo de aparelho e localização aproximada); os eventos que o site envia informam só o tipo da ação, nunca o que você escreve nos formulários.';
	if (isset($s['clarity'])) {
		$txt .= ' O Microsoft Clarity também registra, de forma anônima, cliques e rolagem para mostrar como a tela é usada.';
	}
	if (isset($s['pixel'])) {
		$txt .= ' O Meta Pixel pode ligar a visita ao seu perfil no Facebook ou no Instagram, se você estiver conectado, para medir anúncios.';
	}
	$extra = '<h2>Cookies de medição de acessos.</h2><p>' . esc_html($txt)
		. ' A sua escolha fica salva neste navegador e pode ser mudada a qualquer momento: <button type="button" class="ieadc-linkbtn" data-ieadc-cookies-abrir>Mudar minha escolha de cookies</button>.</p>';
	$fim = '</div></div></section>';
	$pos = strrpos($html, $fim);
	return $pos === false ? $html : substr_replace($html, $extra, $pos, 0);
}, 10, 2);
