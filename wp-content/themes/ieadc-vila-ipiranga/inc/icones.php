<?php
/**
 * Ícones de traço do protótipo (24×24, stroke). Use ieadc_icone('nome').
 */
defined('ABSPATH') || exit;

function ieadc_icones(): array {
	return [
		'seta'      => '<path d="m6 9 6 6 6-6"/>',
		'oracao'    => '<path d="M8.5 21v-5.5L5 10.8V5.5a1.3 1.3 0 0 1 2.6 0V10"/><path d="M15.5 21v-5.5l3.5-4.7V5.5a1.3 1.3 0 0 0-2.6 0V10"/><path d="M8.5 15.5 12 12l3.5 3.5"/><path d="M12 3v5"/>',
		'livro'     => '<path d="M2 5.5c3-1.6 6.5-1.6 10 .8 3.5-2.4 7-2.4 10-.8v13.7c-3-1.6-6.5-1.6-10 .8-3.5-2.4-7-2.4-10-.8z"/><path d="M12 6.3v14"/>',
		'capelo'    => '<path d="m2 9 10-5 10 5-10 5z"/><path d="M6 11.2v4.6c3.3 2.4 8.7 2.4 12 0v-4.6"/><path d="M22 9v6"/>',
		'casa'      => '<path d="m3 11 9-7 9 7"/><path d="M5.5 9.5V20h13V9.5"/><path d="M10 20v-5h4v5"/>',
		'globo'     => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3.2 3.4 3.2 14.6 0 18M12 3c-3.2 3.4-3.2 14.6 0 18"/>',
		'pessoas'   => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20.5c0-3.4 2.7-6 6-6s6 2.6 6 6"/><circle cx="17.2" cy="9" r="2.5"/><path d="M15.8 14.6c2.9.4 5.2 2.8 5.2 5.9"/>',
		'crianca'   => '<circle cx="12" cy="5" r="2.6"/><path d="M12 8.5v6.5M7.5 11h9M9 21l3-6 3 6"/>',
		'igreja'    => '<path d="M12 2v5M9.5 4.5h5"/><path d="M5 22V12l7-4.5 7 4.5v10z"/><path d="M10 22v-4.5a2 2 0 0 1 4 0V22"/>',
		'calice'    => '<path d="M7 3h10c0 5.5-2.2 8.5-5 8.5S7 8.5 7 3z"/><path d="M12 11.5V19M8 21h8"/>',
		'chama'     => '<path d="M12 2.5c2.2 3 5.5 5.3 5.5 9.8a5.5 5.5 0 0 1-11 0c0-2.2 1-3.8 2.2-4.9 0 2 1 3.2 2.3 3.2 0-3.3-.9-5.4 1-8.1z"/>',
		'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.4" cy="6.6" r=".9"/>',
		'facebook'  => '<path d="M14 21v-7h3l.5-3.5H14V8.6c0-1 .4-1.6 1.7-1.6h1.9V3.8A22 22 0 0 0 15 3.6c-2.6 0-4.5 1.6-4.5 4.5v2.4h-3V14h3v7"/>',
		'youtube'   => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 5 3-5 3z"/>',
	];
}

function ieadc_icone(string $nome): string {
	$i = ieadc_icones();
	if (!isset($i[$nome])) {
		return '';
	}
	return '<svg class="ico" viewBox="0 0 24 24" aria-hidden="true">' . $i[$nome] . '</svg>';
}
