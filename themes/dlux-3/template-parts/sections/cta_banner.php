<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$bg = get_sub_field( 'background_image' );
?>
<section class="cta"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?>>
	<?php if ( ! empty( $bg['ID'] ) ) echo wp_get_attachment_image( $bg['ID'], 'full', false, [ 'class' => 'cta__bg', 'alt' => '', 'loading' => 'lazy', 'sizes' => '100vw' ] ); ?>
	<div class="cta__overlay" aria-hidden="true"></div>
	<div class="container cta__inner">
		<h2 class="cta__title"><?php echo dlux_heading( get_sub_field( 'heading' ) ); // phpcs:ignore ?></h2>
		<?php if ( $t = get_sub_field( 'text' ) ) : ?>
			<p class="cta__text"><?php echo wp_kses( $t, [ 'br' => [] ] ); ?></p>
		<?php endif; ?>
		<?php echo dlux_button( get_sub_field( 'button' ), 'btn--primary btn--xl' ); // phpcs:ignore ?>
	</div>
</section>
