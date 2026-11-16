<?php
/**
 * Página 404 acolhedora (fase 4): mesmo cabeçalho e rodapé do site, texto simples e links para as páginas principais.
 * A resposta é 404 e fica fora do Google (noindex em inc/marketing.php). Só links para páginas que existem no menu do site.
 */
defined('ABSPATH') || exit;
get_header();
$destinos = [
	['Página inicial', ''],
	['Horários e localização', 'horarios-e-localizacao'],
	['Eventos', 'eventos'],
	['Ministérios', 'ministerios'],
	['Conteúdos', 'artigos'],
	['Pedido de oração', 'pedidos-de-oracao'],
	['Contato', 'contato'],
];
?>
<div class="ieadc-ui ieadc-pagina">
	<section class="hero sem-foto"><div class="wrap"><div class="conteudo">
		<span class="eyebrow">Página não encontrada</span>
		<h1 class="h1">Não achamos esta página</h1>
		<?php echo ieadc_swoosh(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<p class="lead">O endereço pode ter mudado ou ter sido digitado diferente. Mas você é bem-vindo: veja por onde seguir.</p>
	</div></div></section>
	<section class="sec"><div class="wrap">
		<h2 class="h3">Para onde você quer ir?</h2>
		<nav class="nf-links" aria-label="Páginas principais">
			<?php foreach ($destinos as [$rotulo, $slug]) : ?>
				<a class="btn out" href="<?php echo esc_url($slug === '' ? home_url('/') : ieadc_link($slug)); ?>"><?php echo esc_html($rotulo); ?></a>
			<?php endforeach; ?>
		</nav>
	</div></section>
</div>
<?php
get_footer();
