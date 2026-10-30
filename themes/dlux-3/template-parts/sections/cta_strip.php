<?php
/**
 * CTA strip: one-line heading with two buttons (e.g. "Ready to see your car shine?").
 * Fields: section_id, background (accent|dark), heading, text, primary_button, secondary_button.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$heading = get_sub_field( 'heading' );
// Secondary button shows as a phone icon; its link (and label, used for the tooltip) come from the field.
$sec     = get_sub_field( 'secondary_button' );
$icon    = '';
if ( ! empty( $sec['url'] ) ) {
	$label = ! empty( $sec['title'] ) ? $sec['title'] : __( 'Call us', 'dlux' );
	$icon  = sprintf(
		'<a class="cta-strip__call" href="%1$s"%3$s aria-label="%2$s" title="%2$s"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"/></svg></a>',
		esc_url( $sec['url'], [ 'http', 'https', 'tel', 'sms', 'mailto' ] ),
		esc_attr( $label ),
		'_blank' === ( $sec['target'] ?? '' ) ? ' target="_blank" rel="noopener"' : ''
	);
}
$btns    = dlux_button( get_sub_field( 'primary_button' ), 'cta-strip__btn cta-strip__btn--primary', false ) . $icon;
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
