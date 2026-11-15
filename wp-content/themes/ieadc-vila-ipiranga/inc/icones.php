<?php
/**
 * Ícones de traço do protótipo (24×24). Mesmos nomes do dicionário I de prototipo/fonte/gen.py.
 * Uso: ieadc_icone('relogio'). Para ícone novo, peça ao M0 (integrador).
 */
defined('ABSPATH') || exit;

function ieadc_icones(): array {
	static $i = null;
	if ($i !== null) {
		return $i;
	}
	$i = [
		'oracao' => '<path d="M8.5 21v-5.5L5 10.8V5.5a1.3 1.3 0 0 1 2.6 0V10"/><path d="M15.5 21v-5.5l3.5-4.7V5.5a1.3 1.3 0 0 0-2.6 0V10"/><path d="M8.5 15.5 12 12l3.5 3.5"/><path d="M12 3v5"/>',
		'livro' => '<path d="M2 5.5c3-1.6 6.5-1.6 10 .8 3.5-2.4 7-2.4 10-.8v13.7c-3-1.6-6.5-1.6-10 .8-3.5-2.4-7-2.4-10-.8z"/><path d="M12 6.3v14"/>',
		'pessoas' => '<circle cx="9" cy="8" r="3.2"/><path d="M3 20.5c0-3.4 2.7-6 6-6s6 2.6 6 6"/><circle cx="17.2" cy="9" r="2.5"/><path d="M15.8 14.6c2.9.4 5.2 2.8 5.2 5.9"/>',
		'pessoa' => '<circle cx="12" cy="8.5" r="4"/><path d="M4.5 21c0-4.2 3.4-7.3 7.5-7.3s7.5 3.1 7.5 7.3"/>',
		'beca' => '<path d="m2 9 10-5 10 5-10 5z"/><path d="M6 11.2v4.6c3.3 2.4 8.7 2.4 12 0v-4.6"/><path d="M22 9v6"/>',
		'cafe' => '<path d="M4 9h13v5a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5z"/><path d="M17 10.5h1.2a2.5 2.5 0 0 1 0 5H17"/><path d="M8 3.5c0 1.2 1 1.2 1 2.4M12 3.5c0 1.2 1 1.2 1 2.4"/>',
		'igreja' => '<path d="M12 2v5M9.5 4.5h5"/><path d="M5 22V12l7-4.5 7 4.5v10z"/><path d="M10 22v-4.5a2 2 0 0 1 4 0V22"/>',
		'calice' => '<path d="M7 3h10c0 5.5-2.2 8.5-5 8.5S7 8.5 7 3z"/><path d="M12 11.5V19M8 21h8"/>',
		'chama' => '<path d="M12 2.5c2.2 3 5.5 5.3 5.5 9.8a5.5 5.5 0 0 1-11 0c0-2.2 1-3.8 2.2-4.9 0 2 1 3.2 2.3 3.2 0-3.3-.9-5.4 1-8.1z"/>',
		'megafone' => '<path d="M3 10.5v3a1 1 0 0 0 1 1h3l9 5.5V4L7 9.5H4a1 1 0 0 0-1 1z"/><path d="M19 9a4 4 0 0 1 0 6"/>',
		'degraus' => '<path d="M3 20h5v-4.5h4.5V11H17V6.5h4"/><path d="M17 3.5h4v3"/>',
		'casa' => '<path d="m3 11 9-7 9 7"/><path d="M5.5 9.5V20h13V9.5"/><path d="M10 20v-5h4v5"/>',
		'maos' => '<path d="M12 21s-7.5-4.6-7.5-10.3A4.2 4.2 0 0 1 12 8.2a4.2 4.2 0 0 1 7.5 2.5C19.5 16.4 12 21 12 21z"/>',
		'globo' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3.2 3.4 3.2 14.6 0 18M12 3c-3.2 3.4-3.2 14.6 0 18"/>',
		'nota' => '<path d="M9 18V5l11-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="17" cy="16" r="3"/>',
		'crianca' => '<circle cx="12" cy="5" r="2.6"/><path d="M12 8.5v6.5M7.5 11h9M9 21l3-6 3 6"/>',
		'cal' => '<rect x="3" y="5" width="18" height="16" rx="2.5"/><path d="M3 10h18M8 3v4M16 3v4"/>',
		'relogio' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/>',
		'pin' => '<path d="M12 22s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="10" r="2.6"/>',
		'fone' => '<path d="M5 3h3.6l1.9 4.8-2.4 1.5a11.5 11.5 0 0 0 5.6 5.6l1.5-2.4L20 14.4V18a2.4 2.4 0 0 1-2.6 2.4A16.6 16.6 0 0 1 2.6 5.6 2.4 2.4 0 0 1 5 3z"/>',
		'email' => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m3.5 7 8.5 6 8.5-6"/>',
		'seta' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'esq' => '<path d="m15 6-6 6 6 6"/>',
		'dir' => '<path d="m9 6 6 6-6 6"/>',
		'baixo' => '<path d="m6 9 6 6 6-6"/>',
		'onibus' => '<rect x="4" y="3" width="16" height="14.5" rx="3"/><path d="M4 11h16M7.5 21l1.2-3.5M16.5 21l-1.2-3.5"/><circle cx="8" cy="14.3" r=".9"/><circle cx="16" cy="14.3" r=".9"/>',
		'insta' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.4" cy="6.6" r=".9"/>',
		'yt' => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 5 3-5 3z"/>',
		'fb' => '<path d="M14 21v-7h3l.5-3.5H14V8.6c0-1 .4-1.6 1.7-1.6h1.9V3.8A22 22 0 0 0 15 3.6c-2.6 0-4.5 1.6-4.5 4.5v2.4h-3V14h3v7"/>',
		'escudo' => '<path d="M12 3 20 6v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="m9 12 2 2 4-4"/>',
		'play' => '<circle cx="12" cy="12" r="9"/><path d="m10 8.5 5.5 3.5-5.5 3.5z"/>',
		'fone-ouvido' => '<path d="M3.5 18v-5.5a8.5 8.5 0 0 1 17 0V18"/><rect x="3" y="14.5" width="4.5" height="6" rx="1.5"/><rect x="16.5" y="14.5" width="4.5" height="6" rx="1.5"/>',
		'texto' => '<path d="M6 3h9l4 4v14H6z"/><path d="M14.5 3v4.5H19M9 12h7M9 16h7"/>',
		'alvo' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.3"/>',
		'olho' => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
		'ok' => '<circle cx="12" cy="12" r="9"/><path d="m8 12.5 2.8 2.8L16.5 9.5"/>',
		'erro' => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v6M12 16.5v.1"/>',
	];
	// Apelidos usados no menu e no rodapé.
	foreach (['capelo' => 'beca', 'instagram' => 'insta', 'facebook' => 'fb', 'youtube' => 'yt'] as $apelido => $nome) {
		$i[$apelido] = $i[$nome];
	}
	return $i;
}

function ieadc_icone(string $nome, string $classe = 'ico'): string {
	$i = ieadc_icones();
	if (!isset($i[$nome])) {
		return '';
	}
	return '<svg class="' . esc_attr($classe) . '" viewBox="0 0 24 24" aria-hidden="true">' . $i[$nome] . '</svg>';
}
