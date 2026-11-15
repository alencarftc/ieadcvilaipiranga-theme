<?php
// M3: campos do cartão de cada ministério (páginas filhas de "ministerios") e foto do cartão (imagem destacada).
// Fonte: prototipo/fonte/minis.py (ícone, 1ª frase do "curto", "Quando" dos fatos) e gen.py (CORES, foto do cartão).
// Não sobrescreve o que foi editado no painel (exceto com IEADC_ATUALIZAR=1).

/** Grava o campo só se ele ainda não existe (ou com IEADC_ATUALIZAR=1). Devolve true se gravou. */
function ieadc_seed_meta(int $id, string $chave, $valor): bool {
	if (metadata_exists('post', $id, $chave) && getenv('IEADC_ATUALIZAR') !== '1') {
		return false;
	}
	update_post_meta($id, $chave, $valor);
	return true;
}

/** Define a imagem destacada pelo nome do arquivo em content/media, se a página ainda não tem uma. */
function ieadc_seed_capa(int $id, string $arquivo): void {
	static $midia = null;
	$midia ??= midias_ids();
	if (has_post_thumbnail($id) && getenv('IEADC_ATUALIZAR') !== '1') {
		return;
	}
	if (empty($midia[$arquivo])) {
		WP_CLI::warning("Foto não encontrada na mídia: {$arquivo}");
		return;
	}
	set_post_thumbnail($id, $midia[$arquivo]);
}

/** Grava vários campos de uma página e diz no log quantos foram gravados. */
function ieadc_seed_campos(int $id, array $campos, string $rotulo): void {
	$n = 0;
	foreach ($campos as $k => $v) {
		$n += (int) ieadc_seed_meta($id, $k, $v);
	}
	if ($n) {
		WP_CLI::log("Campos ({$n}): {$rotulo}");
	}
}

$ministerios = [
	'circulo-de-oracao' => ['icone' => 'oracao', 'resumo' => 'Reúne as irmãs da igreja para orar pela congregação, pelas famílias e pelos pedidos recebidos.', 'quando' => 'Quinta-feira, às 20h', 'cor' => 'c1', 'foto' => 'f_min_circulo.webp'],
	'escola-biblica-dominical' => ['icone' => 'livro', 'resumo' => 'Estuda a Bíblia em classes, todos os domingos às 9h30.', 'quando' => 'Domingo, às 9h30', 'cor' => 'c2', 'foto' => 'f_min_ebd.webp'],
	'ensinai' => ['icone' => 'beca', 'resumo' => 'Curso de Teologia Ministerial para quem deseja se aprofundar no estudo da Bíblia e se preparar para servir.', 'quando' => 'Sexta-feira, às 19h30', 'cor' => 'c3', 'foto' => 'f_min_ensinai.webp'],
	'familia' => ['icone' => 'casa', 'resumo' => 'Cuida dos casais e das famílias da igreja, para que cada lar viva a fé no dia a dia.', 'quando' => '1º domingo do mês, às 19h', 'cor' => 'c4', 'foto' => 'p_familia.webp'],
	'missao-e-evangelismo' => ['icone' => 'globo', 'resumo' => 'Leva o evangelho para além das portas da igreja, em sintonia com o tema do ano: alcançar Curitiba para Cristo.', 'quando' => '2º domingo do mês, às 19h', 'cor' => 'c5', 'foto' => 'p_missoes.webp'],
	'mocidade' => ['icone' => 'pessoas', 'resumo' => 'Reúne os jovens e adolescentes para crescerem juntos na fé, em comunhão e no serviço.', 'quando' => '3º domingo do mês, às 19h', 'cor' => 'c6', 'foto' => 'p_jovens.webp'],
	'criancas' => ['icone' => 'crianca', 'resumo' => 'Ensina a Palavra de Deus às crianças, de um jeito adequado a cada idade.', 'quando' => '5º domingo do mês (quando houver), às 19h', 'cor' => 'c7', 'foto' => 'f_min_criancas.webp'],
];

foreach ($ministerios as $slug => $m) {
	$p = get_page_by_path('ministerios/' . $slug);
	if (!$p) {
		WP_CLI::warning("Ministério sem página: ministerios/{$slug}");
		continue;
	}
	ieadc_seed_campos($p->ID, ['ieadc_icone' => $m['icone'], 'ieadc_resumo' => $m['resumo'], 'ieadc_quando' => $m['quando'], 'ieadc_cor' => $m['cor']], "ministerios/{$slug}");
	ieadc_seed_capa($p->ID, $m['foto']);
}
