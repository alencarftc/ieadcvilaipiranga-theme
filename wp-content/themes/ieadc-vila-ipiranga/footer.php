<?php
/**
 * Rodapé do protótipo v3: dados da igreja, cultos, contato, links, mapa do site e linha da Sede.
 */
defined('ABSPATH') || exit;
blocksy_after_current_template();
do_action('blocksy:content:bottom');
$logo   = get_stylesheet_directory_uri() . '/assets/img/logo_cor.webp';
$redes  = ['instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube'];
$cultos = array_filter(array_map('trim', explode("\n", ieadc_dado('cultos'))));
?>
	</main>
	<?php do_action('blocksy:content:after'); ?>
	<footer class="rodape ieadc-ui"><div class="wrap">
		<div class="cols topo-rod">
			<div>
				<span class="logo-b"><img src="<?php echo esc_url($logo); ?>" alt="Assembleia de Deus em Curitiba" width="120" height="40" loading="lazy"></span>
				<p style="margin-top:21px"><b style="color:#fff"><?php echo esc_html(ieadc_dado('nome')); ?></b><br><?php echo esc_html(ieadc_dado('nome_longo')); ?></p>
				<div class="redes">
					<?php foreach ($redes as $chave => $rotulo) : $url = ieadc_dado($chave); if (!$url) { continue; } ?>
						<a href="<?php echo esc_url($url); ?>" aria-label="<?php echo esc_attr($rotulo); ?>" rel="noopener"><?php echo ieadc_icone($chave); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
					<?php endforeach; ?>
				</div>
			</div>
			<div><h4>Cultos</h4><ul>
				<?php foreach ($cultos as $c) : ?><li><?php echo esc_html($c); ?></li><?php endforeach; ?>
			</ul></div>
			<div><h4>Contato</h4><ul>
				<li><?php echo esc_html(ieadc_dado('endereco')); ?></li>
				<li><a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', ieadc_dado('telefone'))); ?>"><?php echo esc_html(ieadc_dado('telefone')); ?></a></li>
				<li><a href="mailto:<?php echo esc_attr(antispambot(ieadc_dado('email'))); ?>"><?php echo esc_html(antispambot(ieadc_dado('email'))); ?></a></li>
			</ul></div>
			<div><h4>Outros links</h4><ul>
				<li><a href="<?php echo esc_url(ieadc_dado('site_sede')); ?>">Site da IEADC</a></li>
				<?php foreach ($redes as $chave => $rotulo) : $url = ieadc_dado($chave); if (!$url) { continue; } ?>
					<li><a href="<?php echo esc_url($url); ?>" rel="noopener"><?php echo esc_html($rotulo); ?></a></li>
				<?php endforeach; ?>
			</ul></div>
		</div>
		<?php echo ieadc_mapa_site(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="base"><span><?php echo esc_html(ieadc_dado('sede')); ?></span><span class="tema"><?php echo esc_html(ieadc_dado('tema_ano')); ?></span></div>
	</div></footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
