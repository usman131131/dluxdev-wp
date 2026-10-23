<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();

while ( have_posts() ) :
	the_post();
	if ( function_exists( 'have_rows' ) && have_rows( 'page_sections' ) ) {
		get_template_part( 'template-parts/page-builder' );
	} else {
		get_template_part( 'template-parts/content', 'page' );
	}
endwhile;

get_footer();
