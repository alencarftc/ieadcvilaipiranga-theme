<?php
/**
 * Cabeçalho do protótipo v3. Mantém a estrutura do Blocksy (#main-container e <main>)
 * para os modelos do tema pai continuarem funcionando.
 */
defined('ABSPATH') || exit;
$logo = get_stylesheet_directory_uri() . '/assets/img/logo_cor-300.webp'; // 300 px (2x do exibido); o original de 3015 px pesa 59 KB
?><!doctype html>
<html <?php language_attributes(); ?><?php echo blocksy_html_attr(); ?>>
<head>
	<?php do_action('blocksy:head:start'); ?>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, viewport-fit=cover">
	<?php wp_head(); ?>
	<?php do_action('blocksy:head:end'); ?>
</head>
<body <?php body_class(); ?> <?php echo blocksy_body_attr(); ?>>
<?php wp_body_open(); ?>
<?php /* O link "Pular para o conteúdo" vem do Blocksy (wp_body_open); um segundo, aqui, ficaria repetido para quem usa teclado. */ ?>
<svg width="0" height="0" style="position:absolute" aria-hidden="true"><defs><linearGradient id="gr-sol" x1="0" x2="1" y1="0" y2="0"><stop offset="0" stop-color="#FEB900"/><stop offset=".45" stop-color="#FE7D00"/><stop offset="1" stop-color="#EC4024"/></linearGradient></defs></svg>
<div id="main-container">
	<div class="topline ieadc-ui" role="region" aria-label="<?php esc_attr_e('Frase da igreja e redes sociais', 'ieadc'); ?>"><div class="wrap"><span class="frase"><?php echo esc_html(ieadc_dado('frase_topo')); ?></span>
		<nav aria-label="<?php esc_attr_e('Redes sociais', 'ieadc'); ?>">
			<?php foreach (['instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube'] as $chave => $rotulo) : $url = ieadc_dado($chave); if ($url) : ?>
				<a href="<?php echo esc_url($url); ?>" rel="noopener"><?php echo esc_html($rotulo); ?></a>
			<?php endif; endforeach; ?>
		</nav></div></div>
	<header class="header ieadc-ui"><div class="wrap">
		<a class="marca" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php esc_attr_e('Página inicial', 'ieadc'); ?>">
			<img src="<?php echo esc_url($logo); ?>" alt="Assembleia de Deus em Curitiba" width="147" height="44" decoding="async"><span>VILA<br>IPIRANGA</span>
		</a>
		<button class="menu-btn" type="button" aria-expanded="false" aria-controls="nav">MENU</button>
		<nav class="nav" id="nav" aria-label="<?php esc_attr_e('Menu principal', 'ieadc'); ?>">
			<?php
			wp_nav_menu([
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => '',
				'menu_id'        => '',
				'items_wrap'     => '<ul>%3$s</ul>',
				'walker'         => new IEADC_Walker_Nav(),
				'fallback_cb'    => false,
				'depth'          => 3,
			]);
			$pedido = ieadc_link('pedidos-de-oracao');
			$on = is_page('pedidos-de-oracao') ? ' on' : '';
			?>
			<a class="cta<?php echo esc_attr($on); ?>" href="<?php echo esc_url($pedido); ?>"><?php esc_html_e('Pedido de oração', 'ieadc'); ?></a>
		</nav>
	</div></header>
	<?php do_action('blocksy:header:after'); ?>
	<?php do_action('blocksy:content:before'); ?>
	<main <?php echo blocksy_main_attr(); ?>>
		<?php
		do_action('blocksy:content:top');
		blocksy_before_current_template();
