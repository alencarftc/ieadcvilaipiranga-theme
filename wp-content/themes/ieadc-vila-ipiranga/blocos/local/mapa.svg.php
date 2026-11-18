<?php
/**
 * Mapa ilustrado do protótipo (MAPA em prototipo/fonte/gen.py). Sem iframe do Google Maps (privacidade):
 * o botão do bloco leva ao Maps. ieadc_local_mapa() devolve o <div class="mapa"> com o nome no alfinete.
 */
defined('ABSPATH') || exit;

function ieadc_local_mapa(string $rotulo): string {
	$h = '<div class="mapa"><svg viewBox="0 0 640 440" aria-hidden="true"><rect width="640" height="440" fill="#DCEBF8"/>'
		. '<g fill="none" stroke="#fff" stroke-linecap="round"><path d="M-20 300 L660 180" stroke-width="22"/><path d="M180 -20 L300 460" stroke-width="16"/><path d="M-20 90 L660 40" stroke-width="12"/>'
		. '<path d="M470 -20 L520 460" stroke-width="12"/><path d="M-20 400 L660 360" stroke-width="10"/><path d="M60 -20 L120 460" stroke-width="8"/></g>'
		. '<rect x="330" y="225" width="96" height="70" rx="10" fill="#BFE3C6"/><text x="378" y="314" text-anchor="middle" font-family="Roboto,Arial" font-size="12" fill="#2C6B3A">Praça Nova República</text>'
		. '<text x="32" y="292" font-family="Roboto,Arial" font-size="12" fill="#45536B" transform="rotate(-10 32 292)">R. Bortolo Gusso</text>'
		. '<text x="192" y="36" font-family="Roboto,Arial" font-size="12" fill="#45536B" transform="rotate(76 192 36)">R. Prof. João Mazzarotto</text></svg>';
	$pin = trim($rotulo) !== '' ? '<b>' . esc_html($rotulo) . '</b>' : '';
	$url = function_exists('ieadc_mapa_embed_url') ? ieadc_mapa_embed_url() : '';
	// Mapa do Google (iframe): só carrega com um clique ou depois do "Aceitar" dos cookies (site.js); o Google pode gravar cookies ao mostrá-lo.
	$abrir = $url !== '' ? '<button type="button" class="btn mapa-abrir" data-mapa-url="' . esc_url($url) . '">Ver o mapa do Google' . ieadc_icone('pin') . '</button>'
		. '<p class="mapa-nota">Ao ver o mapa, o Google pode gravar cookies. <a href="' . esc_url(ieadc_url('politica-de-privacidade')) . '">Saiba mais</a></p>' : '';
	return $h . '<div class="pin">' . $pin . ieadc_icone('pin') . '</div>' . $abrir . '</div>';
}
