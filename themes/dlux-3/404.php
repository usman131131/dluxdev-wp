<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header();
?>
<section class="section page-content not-found">
	<div class="container container--narrow">
		<p class="eyebrow"><?php esc_html_e( 'Error 404', 'dlux' ); ?></p>
		<h1 class="section-title"><?php esc_html_e( 'This page has driven off', 'dlux' ); ?></h1>
		<p><?php esc_html_e( 'The link may be old or mistyped. Head back to the homepage to see prices and book a wash.', 'dlux' ); ?></p>
		<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><span><?php esc_html_e( 'Go to homepage', 'dlux' ); ?></span></a>
	</div>
</section>
<?php
get_footer();
