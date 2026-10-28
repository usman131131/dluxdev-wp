<?php
/**
 * Add-ons grid (e.g. "Add to your detail").
 * Fields: section_id, background (light|grey|dark), heading, intro, source (booking|manual), service (detail|wash),
 *         items[ name, price, text ]  ← only used when source = manual.
 *
 * Source "Booking Prices": names, prices and descriptions come from the Dlux Booking plugin
 * (Booking Prices › Detailing extras, or Wash extras). Cards can then be selected and added to the
 * package chosen in the Detailing packages section; the bottom bar books them together.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$source  = 'manual' === get_sub_field( 'source' ) ? 'manual' : 'booking';
$service = 'wash' === get_sub_field( 'service' ) ? 'wash' : 'detail';
$items   = [];
if ( 'booking' === $source && dlux_booking_active() ) {
	foreach ( dlux_showcase_extras( $service ) as $x ) {
		$items[] = [ 'name' => $x['name'], 'price' => $x['price'], 'text' => $x['desc'], 'ids' => $x['ids'] ];
	}
} else {
	$source = 'manual';
	$items  = array_values( array_filter( (array) ( get_sub_field( 'items' ) ?: [] ), function ( $a ) { return '' !== trim( (string) ( $a['name'] ?? '' ) ); } ) );
}
if ( ! $items ) return;
$pick = 'booking' === $source && dlux_booking_url();
$bg   = get_sub_field( 'background' );
$bg   = in_array( $bg, [ 'light', 'grey', 'dark' ], true ) ? $bg : 'grey';
?>
<section class="section addons addons--<?php echo esc_attr( $bg ); ?>"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?><?php echo $pick ? ' data-addons data-service="' . esc_attr( $service ) . '"' : ''; ?>>
	<div class="container">
		<?php if ( $h = get_sub_field( 'heading' ) ) : ?><h2 class="addons__title"><?php echo dlux_heading( $h ); // phpcs:ignore ?></h2><?php endif; ?>
		<?php if ( $t = get_sub_field( 'intro' ) ) : ?><p class="addons__intro"><?php echo wp_kses( dlux_tokens( $t ), [ 'br' => [] ] ); ?></p><?php endif; ?>
		<ul class="addons__grid">
			<?php foreach ( $items as $a ) :
				$price = trim( (string) ( $a['price'] ?? '' ) );
				$tag   = $pick ? 'button' : 'div';
				$attrs = $pick
					? ' type="button" aria-pressed="false" data-addon data-extra-ids="' . esc_attr( wp_json_encode( $a['ids'] ) ) . '" data-extra-name="' . esc_attr( $a['name'] ) . '" data-extra-price="' . esc_attr( is_numeric( $price ) ? (float) $price : 0 ) . '"'
					: '';
				?>
				<li>
					<<?php echo $tag . $attrs; // phpcs:ignore ?> class="addon<?php echo $pick ? ' addon--pick' : ''; ?>">
						<span class="addon__head">
							<span class="addon__name"><?php echo esc_html( $a['name'] ); ?></span>
							<?php if ( '' !== $price ) : ?><span class="addon__price"><?php echo esc_html( is_numeric( $price ) ? dlux_money( $price ) : $price ); ?></span><?php endif; ?>
						</span>
						<?php if ( ! empty( $a['text'] ) ) : ?><span class="addon__text"><?php echo wp_kses( $a['text'], [ 'br' => [] ] ); ?></span><?php endif; ?>
						<?php if ( $pick ) : ?>
							<span class="addon__toggle" aria-hidden="true"><span class="is-off"><?php esc_html_e( '+ Add', 'dlux' ); ?></span><span class="is-on"><?php esc_html_e( '✓ Added', 'dlux' ); ?></span><span class="is-na"><?php esc_html_e( 'Not offered with this package', 'dlux' ); ?></span></span>
						<?php endif; ?>
					</<?php echo $tag; // phpcs:ignore ?>>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
