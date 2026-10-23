<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* Cards and extras come from the Dlux Booking plugin (Booking Prices › Wash packages).
 * If the plugin is not active, the section's own Packages rows are used instead. */
$group  = dlux_booking_group( 'wash' );
$cards  = [];
$extras = [];
if ( $group ) {
	foreach ( (array) $group['packages'] as $p ) $cards[] = dlux_booking_card( $p );
	$extras = dlux_booking_section_extras( $group );
} elseif ( ! dlux_booking_active() ) {
	foreach ( (array) ( get_sub_field( 'packages' ) ?: [] ) as $p ) $cards[] = dlux_field_card( $p );
}
?>
<section class="section section--surface pricing"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?><?php echo $group ? dlux_booking_attrs( 'wash' ) : ''; // phpcs:ignore ?>>
	<div class="container">
		<div class="section-head">
			<?php echo dlux_section_head( get_sub_field( 'eyebrow' ), get_sub_field( 'heading' ) ); // phpcs:ignore ?>
		</div>

		<?php if ( $cards ) : ?>
			<div class="pricing__grid" data-equalize>
				<?php foreach ( $cards as $card ) get_template_part( 'template-parts/package-card', null, [ 'card' => $card, 'mins_star' => true ] ); ?>
			</div>
		<?php endif; ?>

		<?php if ( $extras ) : ?>
			<div class="extras">
				<p class="extras__title"><?php esc_html_e( 'Popular upgrades', 'dlux' ); ?> <small><?php esc_html_e( '(from)', 'dlux' ); ?></small></p>
				<?php echo dlux_extras_select( $extras ); // phpcs:ignore ?>
			</div>
		<?php endif; ?>

		<?php if ( $group ) echo dlux_booking_bar(); // phpcs:ignore ?>

		<?php if ( $note = get_sub_field( 'footnote' ) ) : ?>
			<p class="pricing__note"><?php echo wp_kses( $note, [ 'br' => [], 'a' => [ 'href' => [] ] ] ); ?></p>
		<?php endif; ?>
	</div>
</section>
