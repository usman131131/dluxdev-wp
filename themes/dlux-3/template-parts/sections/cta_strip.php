<?php
/**
 * CTA strip: one-line heading with two buttons (e.g. "Ready to see your car shine?").
 * Fields: section_id, background (accent|dark), heading, text, primary_button, secondary_button.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$heading = get_sub_field( 'heading' );
$btns    = dlux_button( get_sub_field( 'primary_button' ), 'cta-strip__btn cta-strip__btn--primary', false )
	. dlux_button( get_sub_field( 'secondary_button' ), 'cta-strip__btn cta-strip__btn--secondary', false );
if ( ! $heading && ! $btns ) return;
$bg = 'dark' === get_sub_field( 'background' ) ? 'dark' : 'accent';
?>
<section class="cta-strip cta-strip--<?php echo esc_attr( $bg ); ?>"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?>>
	<div class="container cta-strip__inner">
		<div>
			<?php if ( $heading ) : ?><h2 class="cta-strip__title"><?php echo dlux_heading( $heading ); // phpcs:ignore ?></h2><?php endif; ?>
			<?php if ( $t = get_sub_field( 'text' ) ) : ?><p class="cta-strip__text"><?php echo wp_kses( dlux_tokens( $t ), [ 'br' => [] ] ); ?></p><?php endif; ?>
		</div>
		<?php if ( $btns ) : ?><div class="cta-strip__btns"><?php echo $btns; // phpcs:ignore ?></div><?php endif; ?>
	</div>
</section>
