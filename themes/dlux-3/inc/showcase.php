<?php
/**
 * Data for the Detailing packages section (template-parts/sections/detail_packages.php).
 * Packages come from the Dlux Booking plugin (Booking Prices); the card image,
 * before/after images, "What's included" text, "Ideal for" and price label are
 * extra fields on each detailing package.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Bookable packages of a plugin group ('detail' or 'wash'), normalised for the section. */
function dlux_showcase_packages( $service = 'detail' ) {
	$group = dlux_booking_group( $service );
	if ( ! $group ) return [];
	$order = function_exists( 'dlux_bk_menu' ) ? (array) ( dlux_bk_menu()['classes'] ?? [] ) : [];
	$out   = [];
	foreach ( (array) ( $group['packages'] ?? [] ) as $p ) {
		if ( empty( $p['name'] ) ) continue;
		$prices = [];
		foreach ( $order as $size ) {
			if ( isset( $p['prices'][ $size ] ) ) $prices[ $size ] = (float) $p['prices'][ $size ];
		}
		foreach ( (array) ( $p['prices'] ?? [] ) as $size => $amount ) {
			if ( ! isset( $prices[ $size ] ) ) $prices[ $size ] = (float) $amount;
		}
		$out[] = [
			'id'           => (string) ( $p['id'] ?? '' ),
			'name'         => (string) $p['name'],
			'desc'         => (string) ( $p['desc'] ?? '' ),
			'features'     => array_values( array_filter( (array) ( $p['features'] ?? [] ) ) ),
			'featured'     => ! empty( $p['featured'] ),
			'badge'        => (string) ( $p['badge'] ?? '' ),
			'prices'       => $prices,
			'image'        => (int) ( $p['image'] ?? 0 ),
			'before_image' => (int) ( $p['before_image'] ?? 0 ),
			'after_image'  => (int) ( $p['after_image'] ?? 0 ),
			'price_label'  => (string) ( $p['price_label'] ?? '' ),
			'long_desc'    => (string) ( $p['long_desc'] ?? '' ),
			'ideal_for'    => (string) ( $p['ideal_for'] ?? '' ),
		];
	}
	return $out;
}

/** Vehicle sizes that at least one of these packages is priced for, in the Vehicle sizes order. */
function dlux_showcase_sizes( array $packages ) {
	$order = function_exists( 'dlux_bk_menu' ) ? (array) ( dlux_bk_menu()['classes'] ?? [] ) : [];
	$used  = [];
	foreach ( $packages as $p ) {
		foreach ( array_keys( $p['prices'] ) as $s ) $used[ $s ] = true;
	}
	$sizes = array_values( array_filter( $order, function ( $s ) use ( $used ) { return isset( $used[ $s ] ); } ) );
	foreach ( array_keys( $used ) as $s ) {
		if ( ! in_array( $s, $sizes, true ) ) $sizes[] = $s;
	}
	return $sizes;
}

/**
 * Extras for the Add-ons grid, from Booking Prices.
 * Detailing: each package has its own extras, so rows with the same name + price are merged
 * into one card that knows its extra id per package. Wash: shared extras ('*').
 *
 * @return array[] { name, price, desc, ids: [ package_id|'*' => extra_id ] }
 */
function dlux_showcase_extras( $service = 'detail' ) {
	$group = dlux_booking_group( $service );
	if ( ! $group ) return [];
	$out = [];
	if ( ! empty( $group['extras'] ) ) {
		foreach ( (array) $group['extras'] as $x ) {
			if ( empty( $x['id'] ) || empty( $x['name'] ) ) continue;
			$out[] = [ 'name' => $x['name'], 'price' => $x['price'] ?? '', 'desc' => (string) ( $x['desc'] ?? '' ), 'ids' => [ '*' => $x['id'] ] ];
		}
		return $out;
	}
	foreach ( (array) ( $group['packages'] ?? [] ) as $p ) {
		foreach ( (array) ( $p['extras'] ?? [] ) as $x ) {
			if ( empty( $x['id'] ) || empty( $x['name'] ) ) continue;
			$key = strtolower( trim( $x['name'] ) ) . '|' . (float) ( $x['price'] ?? 0 );
			if ( ! isset( $out[ $key ] ) ) $out[ $key ] = [ 'name' => $x['name'], 'price' => $x['price'] ?? '', 'desc' => '', 'ids' => [] ];
			if ( '' === $out[ $key ]['desc'] && ! empty( $x['desc'] ) ) $out[ $key ]['desc'] = (string) $x['desc'];
			$out[ $key ]['ids'][ $p['id'] ] = $x['id'];
		}
	}
	return array_values( $out );
}

/** Selection bar for Detailing packages + Add-ons grid (printed once, in the footer). */
function dlux_pick_bar() {
	$url = dlux_booking_url();
	if ( ! $url ) return '';
	ob_start();
	?>
	<div class="pick-bar" data-pick-bar data-book-url="<?php echo esc_url( $url ); ?>" hidden>
		<div class="container pick-bar__inner">
			<div class="pick-bar__summary">
				<span class="pick-bar__label"><?php esc_html_e( 'Your selection', 'dlux' ); ?></span>
				<strong class="pick-bar__title" data-pb-title></strong>
				<span class="pick-bar__extras" data-pb-extras></span>
			</div>
			<div class="pick-bar__total">
				<span><?php esc_html_e( 'Total', 'dlux' ); ?></span>
				<strong data-pb-total></strong>
			</div>
			<a class="btn btn--primary pick-bar__btn" href="<?php echo esc_url( $url ); ?>" data-pb-link><span><?php esc_html_e( 'Book now', 'dlux' ); ?></span><span class="btn__arrow" aria-hidden="true">&rarr;</span></a>
			<button type="button" class="pick-bar__clear" data-pb-clear aria-label="<?php esc_attr_e( 'Clear selection', 'dlux' ); ?>">&times;</button>
		</div>
	</div>
	<?php
	return (string) ob_get_clean();
}

add_action( 'wp_footer', function () {
	if ( ! function_exists( 'dlux_page_has_layout' ) || ! dlux_booking_active() ) return;
	if ( dlux_page_has_layout( 'detail_packages' ) || dlux_page_has_layout( 'addons_grid' ) ) {
		echo dlux_pick_bar(); // phpcs:ignore
	}
} );
