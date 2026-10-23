<?php
if ( ! defined( 'ABSPATH' ) ) exit;

while ( have_rows( 'page_sections' ) ) :
	the_row();
	$layout = sanitize_key( get_row_layout() );
	if ( locate_template( 'template-parts/sections/' . $layout . '.php' ) ) {
		get_template_part( 'template-parts/sections/' . $layout );
	}
endwhile;
