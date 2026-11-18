<?php
/**
 * Cabeçalhos de segurança do site (fase 3). Valem só para o site público (hook send_headers):
 * o painel (/wp-admin), o login, o editor de blocos e a visualização do Personalizar não recebem CSP.
 *
 * - X-Content-Type-Options, Referrer-Policy, Permissions-Policy: sempre.
 * - X-Frame-Options SAMEORIGIN: o site só pode ser exibido em quadro do próprio site (o painel já manda o dele).
 * - Cross-Origin-Opener-Policy same-origin-allow-popups: isola a janela do site sem quebrar links que abrem em nova aba (ex.: avaliar no Google).
 * - Strict-Transport-Security: só quando a página chegou por https (no endereço local, http, não vai); sem `preload` por ora.
 * - Content-Security-Policy: política em ieadc_csp_politica(). Modo na opção `ieadc_csp_modo`:
 *     'relatorio' (padrão): envia Content-Security-Policy-Report-Only (o navegador só registra o que bloquearia, no console; nada quebra);
 *     'ativo': envia Content-Security-Policy (bloqueia de verdade);  'desligado': não envia.
 *   Mudar: `wp option update ieadc_csp_modo ativo`. Só passe para 'ativo' depois de abrir as páginas na homologação com o console aberto
 *   e ver zero avisos de "Content Security Policy" (o LiteSpeed Cache e outros plugins podem acrescentar scripts próprios). docs/operacao/plugins.md.
 */
defined('ABSPATH') || exit;

/**
 * Política de conteúdo do site público. O site usa só arquivos do próprio endereço (CSS, JS, fontes, fotos), imagens de qualquer https,
 * `data:` nas imagens (um SVG do CSS) e nenhum quadro (iframe), YouTube ou mapa embutido: os mapas e vídeos são links.
 * style-src precisa de 'unsafe-inline' (as fotos de fundo vão no atributo style de cada seção e há um <style> no head para a foto do topo);
 * script-src não precisa: o JavaScript do tema é um arquivo; 'inline-speculation-rules' libera o <script type="speculationrules"> do WordPress.
 */
function ieadc_csp_politica(bool $aplicar): string {
	$d = [
		"default-src 'self'",
		"script-src 'self' 'inline-speculation-rules'",
		"style-src 'self' 'unsafe-inline'",
		"img-src 'self' data: https:",
		"font-src 'self' data:",
		"connect-src 'self'",
		"media-src 'self'",
		"frame-src https://maps.google.com https://www.google.com",
		"object-src 'none'",
		"base-uri 'self'",
		"form-action 'self'",
	];
	if ($aplicar) { // frame-ancestors e upgrade-insecure-requests não valem no modo só-relatório
		$d[] = "frame-ancestors 'self'";
		if (is_ssl()) {
			$d[] = 'upgrade-insecure-requests';
		}
	}
	return implode('; ', apply_filters('ieadc_csp_diretivas', $d, $aplicar));
}

add_action('send_headers', function () {
	header('X-Content-Type-Options: nosniff');
	header('Referrer-Policy: strict-origin-when-cross-origin');
	header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
	header('X-Frame-Options: SAMEORIGIN');
	header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
	if (is_ssl()) {
		header('Strict-Transport-Security: max-age=2592000'); // 30 dias, sem includeSubDomains nem preload: fácil de desfazer; aumentar depois de um tempo estável
	}

	// CSP: nunca no painel, no login, nas telas de edição nem para quem está logado (a barra de administração usa script e estilo inline).
	if (is_admin() || is_user_logged_in() || is_customize_preview() || (isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php')) {
		return;
	}
	$modo = (string) get_option('ieadc_csp_modo', 'relatorio');
	if ($modo === 'ativo') {
		header('Content-Security-Policy: ' . ieadc_csp_politica(true));
	} elseif ($modo !== 'desligado') {
		header('Content-Security-Policy-Report-Only: ' . ieadc_csp_politica(false));
	}
});
