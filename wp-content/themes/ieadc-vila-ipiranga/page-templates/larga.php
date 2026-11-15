<?php
/**
 * Template Name: Página do protótipo (largura total)
 * Template Post Type: page, post
 * Description: Sem título nem margens do Blocksy. O conteúdo é montado com os blocos e padrões do protótipo.
 */
defined('ABSPATH') || exit;
get_header();
while (have_posts()) :
	the_post();
	echo '<div class="ieadc-ui ieadc-pagina">';
	the_content();
	echo '</div>';
endwhile;
get_footer();
