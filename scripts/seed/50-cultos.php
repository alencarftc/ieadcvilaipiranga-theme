<?php
// M3: campos do cartão de cada culto fixo (página Horários e localização) e títulos da agenda (regra).
// Fonte: prototipo/fonte/cultos.py (ícone, "curto", "Quando" dos fatos, regra) e gen.py (CORES; Vigília e consagração = "Uma vez por mês").
// Funções ieadc_seed_*: 40-ministerios.php.

$cultos = [
	'culto-de-oracao' => ['icone' => 'oracao', 'resumo' => 'Toda segunda-feira, às 20h, a igreja começa a semana em oração.', 'quando' => 'Segunda-feira, às 20h', 'cor' => 'c1', 'regra' => 'Culto de Oração'],
	'culto-de-ensino' => ['icone' => 'livro', 'resumo' => 'Toda quarta-feira, às 20h, a igreja se reúne para aprender a Palavra de Deus.', 'quando' => 'Quarta-feira, às 20h', 'cor' => 'c2', 'regra' => 'Culto de Ensino'],
	'culto-do-circulo-de-oracao' => ['icone' => 'pessoas', 'resumo' => 'Toda quinta-feira, às 20h, o culto é dirigido pelas irmãs do Círculo de Oração.', 'quando' => 'Quinta-feira, às 20h', 'cor' => 'c3', 'regra' => 'Culto dirigido pelo Círculo de Oração'],
	'culto-dominical' => ['icone' => 'igreja', 'resumo' => 'Todo domingo, às 19h. Cada domingo do mês tem um tema.', 'quando' => 'Domingo, às 19h', 'cor' => 'c4', 'regra' => 'Culto dominical'],
	'ceia-do-senhor' => ['icone' => 'calice', 'resumo' => 'Todo 2º sábado do mês, às 19h30, a igreja celebra a Ceia do Senhor.', 'quando' => '2º sábado do mês, às 19h30', 'cor' => 'c5', 'regra' => 'Ceia do Senhor'],
	'vigilia-e-consagracao' => ['icone' => 'chama', 'resumo' => 'Uma vez por mês, a igreja se prepara para a Ceia do Senhor em oração: vigília em um mês, consagração no outro.', 'quando' => 'Uma vez por mês', 'cor' => 'c6', 'regra' => "Vigília\nConsagração"],
];

foreach ($cultos as $slug => $c) {
	$p = get_page_by_path($slug);
	if (!$p) {
		WP_CLI::warning("Culto sem página: {$slug}");
		continue;
	}
	ieadc_seed_campos($p->ID, ['ieadc_icone' => $c['icone'], 'ieadc_resumo' => $c['resumo'], 'ieadc_quando' => $c['quando'], 'ieadc_cor' => $c['cor'], 'ieadc_regra' => $c['regra']], $slug);
}
