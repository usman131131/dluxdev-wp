<?php
/**
 * Bridge to the Dlux Booking plugin.
 *
 * Wash packages, wash extras and detailing packages are read from the plugin
 * (Theme Settings › Booking Prices) through dlux_bk_menu(). Cards are
 * normalised to one shape so both the Pricing and "Beyond the wash"
 * sections render them with the same template part.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Is the booking plugin active? */
function dlux_booking_active() {
	return function_exists( 'dlux_bk_menu' );
}

/** A plugin price group ('wash' or 'detail'), or null. */
function dlux_booking_group( $id ) {
	if ( ! dlux_booking_active() ) return null;
	$menu = dlux_bk_menu();
	foreach ( (array) ( $menu['groups'] ?? [] ) as $g ) {
		if ( isset( $g['id'] ) && $g['id'] === $id ) return $g;
	}
	return null;
}

/** Booking page URL (plugin setting, then Theme Settings › Booking URL). */
function dlux_booking_url() {
	$url = function_exists( 'dlux_bk_page_url' ) ? dlux_bk_page_url( 'booking' ) : '';
	return $url ? $url : (string) dlux_option( 'booking_url', '' );
}

/** $34 / $34.50 */
function dlux_money( $n ) {
	$n = (float) $n;
	return '$' . ( floor( $n ) == $n ? number_format( $n ) : number_format( $n, 2 ) );
}

/**
 * Plugin package → card data.
 * Prices follow the plugin's Vehicle sizes order.
 */
function dlux_booking_card( array $p ) {
	$menu   = dlux_bk_menu();
	$order  = (array) ( $menu['classes'] ?? [] );
	$prices = [];
	foreach ( $order as $size ) {
		if ( isset( $p['prices'][ $size ] ) ) $prices[ $size ] = $p['prices'][ $size ];
	}
	foreach ( (array) $p['prices'] as $size => $amount ) { // any size not in the list
		if ( ! isset( $prices[ $size ] ) ) $prices[ $size ] = $amount;
	}

	$button = null;
	if ( $book = dlux_booking_url() ) {
		$button = [
			/* translators: %s: package name */
			'title'  => sprintf( __( 'Book %s', 'dlux' ), $p['name'] ),
			'url'    => add_query_arg( [ 'service' => $p['group'], 'package' => $p['id'] ], $book ),
			'target' => '',
		];
	}

	return [
		'id'       => (string) ( $p['id'] ?? '' ),
		'group'    => (string) ( $p['group'] ?? '' ),
		'name'     => (string) $p['name'],
		'desc'     => (string) ( $p['desc'] ?? '' ),
		'mins'     => (int) ( $p['mins'] ?? 0 ),
		'features' => (array) ( $p['features'] ?? [] ),
		'choose_title' => (string) ( $p['choose_title'] ?? '' ),
		'choose'   => array_values( array_filter( array_map( function ( $o ) { return trim( preg_replace( '/\s*\(or\)\s*$/i', '', (string) $o ) ); }, (array) ( $p['choose'] ?? [] ) ) ) ),
		'note'     => (string) ( $p['note'] ?? '' ),
		'featured' => ! empty( $p['featured'] ),
		'badge'    => (string) ( $p['badge'] ?? '' ),
		'prices'   => $prices,
		'button'   => $button,
		'extras'   => 'detail' === ( $p['group'] ?? '' ) ? (array) ( $p['extras'] ?? [] ) : [],
	];
}

/** Old Pricing-section rows (SCF) → card data. Used only if the plugin is off. */
function dlux_field_card( array $p ) {
	$prices = [];
	foreach ( (array) ( $p['prices'] ?? [] ) as $row ) {
		$size  = $row['size'] ?? '';
		$label = is_array( $size ) ? ( $size['label'] ?? '' ) : (string) $size;
		if ( '' !== $label ) $prices[ $label ] = $row['price'];
	}
	return [
		'name'     => (string) ( $p['name'] ?? '' ),
		'desc'     => (string) ( $p['subtitle'] ?? '' ),
		'mins'     => 0,
		'features' => array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) ( $p['features'] ?? '' ) ) ) ),
		'featured' => ! empty( $p['is_featured'] ),
		'badge'    => (string) ( $p['badge'] ?? '' ),
		'prices'   => $prices,
		'button'   => $p['button'] ?? null,
		'extras'   => [],
	];
}

/** Pills list for extras: [ { name, price } ]. */
function dlux_extras_pills( array $extras, $class = '' ) {
	if ( ! $extras ) return '';
	$out = '<ul class="extras__list' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '">';
	foreach ( $extras as $x ) {
		if ( empty( $x['name'] ) ) continue;
		$out .= '<li class="extra-pill"><span class="extra-pill__name">' . esc_html( $x['name'] ) . '</span>';
		if ( isset( $x['price'] ) && '' !== $x['price'] && null !== $x['price'] ) {
			$out .= '<span class="extra-pill__price">' . esc_html( dlux_money( $x['price'] ) ) . '</span>';
		}
		$out .= '</li>';
	}
	return $out . '</ul>';
}

/**
 * Selectable extras for a section.
 * Wash: shared group extras (same id for every package).
 * Detail: each package has its own extras, so identical name + price are merged
 * into one pill that remembers its id per package.
 *
 * @return array[] { name, price, ids: [ package_id => extra_id ], all: bool }
 */
function dlux_booking_section_extras( $group ) {
	if ( ! $group ) return [];
	$out = [];
	if ( ! empty( $group['extras'] ) ) {
		foreach ( (array) $group['extras'] as $x ) {
			if ( empty( $x['id'] ) || empty( $x['name'] ) ) continue;
			$out[] = [ 'name' => $x['name'], 'price' => $x['price'] ?? '', 'ids' => [ '*' => $x['id'] ] ];
		}
		return $out;
	}
	foreach ( (array) $group['packages'] as $p ) {
		foreach ( (array) ( $p['extras'] ?? [] ) as $x ) {
			if ( empty( $x['id'] ) || empty( $x['name'] ) ) continue;
			$key = strtolower( trim( $x['name'] ) ) . '|' . (float) ( $x['price'] ?? 0 );
			if ( ! isset( $out[ $key ] ) ) $out[ $key ] = [ 'name' => $x['name'], 'price' => $x['price'] ?? '', 'ids' => [] ];
			$out[ $key ]['ids'][ $p['id'] ] = $x['id'];
		}
	}
	return array_values( $out );
}

/** Selectable extras pills (multi-select). */
function dlux_extras_select( array $extras ) {
	if ( ! $extras ) return '';
	$out = '<ul class="extras__list">';
	foreach ( $extras as $x ) {
		$has_price = isset( $x['price'] ) && '' !== $x['price'] && null !== $x['price'];
		$out .= '<li><button type="button" class="extra-pill" aria-pressed="false"'
			. ' data-extra-ids="' . esc_attr( wp_json_encode( $x['ids'] ) ) . '"'
			. ' data-extra-name="' . esc_attr( $x['name'] ) . '"'
			. ' data-extra-price="' . esc_attr( $has_price ? (float) $x['price'] : 0 ) . '">'
			. '<span class="extra-pill__name">' . esc_html( $x['name'] ) . '</span>'
			. ( $has_price ? '<span class="extra-pill__price">' . esc_html( dlux_money( $x['price'] ) ) . '</span>' : '' )
			. '</button></li>';
	}
	return $out . '</ul>';
}

/** "Book now" bar shown once a package is selected. */
function dlux_booking_bar() {
	ob_start();
	?>
	<div class="booking-bar" data-booking-bar hidden>
		<div class="booking-bar__summary">
			<span class="booking-bar__label"><?php esc_html_e( 'Your selection', 'dlux' ); ?></span>
			<strong class="booking-bar__title" data-bb-title></strong>
			<span class="booking-bar__extras" data-bb-extras></span>
		</div>
		<div class="booking-bar__total">
			<span><?php esc_html_e( 'Total', 'dlux' ); ?></span>
			<strong data-bb-total></strong>
		</div>
		<a class="btn btn--primary booking-bar__btn" href="#" data-bb-link><span><?php esc_html_e( 'Book now', 'dlux' ); ?></span><span class="btn__arrow" aria-hidden="true">&rarr;</span></a>
	</div>
	<?php
	return ob_get_clean();
}

/** Attributes that turn a section into a selectable booking section. */
function dlux_booking_attrs( $service ) {
	$url = dlux_booking_url();
	if ( ! $url ) return '';
	return ' data-booking-select data-book-url="' . esc_url( $url ) . '" data-service="' . esc_attr( $service ) . '"';
}

/**
 * Every detailing package in Booking Prices, in the admin order.
 * Bookable packages come from the plugin menu (exact ids); a package the plugin
 * leaves out of online booking (no price, or a price for a size that isn't in the
 * Vehicle sizes list) is still shown – as "Price on request", not selectable.
 */
function dlux_booking_detail_cards() {
	$group  = dlux_booking_group( 'detail' );
	$by_id  = [];
	foreach ( (array) ( $group['packages'] ?? [] ) as $p ) $by_id[ $p['id'] ] = $p;

	if ( ! function_exists( 'dlux_bk_read_packages' ) || ! function_exists( 'dlux_bk_price_field' ) ) {
		return array_map( 'dlux_booking_card', array_values( $by_id ) );
	}

	$menu  = dlux_bk_menu();
	$used  = [];
	$warn  = [];
	$raw   = dlux_bk_read_packages( dlux_bk_price_field( 'detail_packages' ), 'detail', (array) $menu['classes'], null, $used, $warn );
	$cards = [];
	foreach ( $raw as $p ) {
		if ( isset( $by_id[ $p['id'] ] ) ) {
			$cards[] = dlux_booking_card( $by_id[ $p['id'] ] );
			unset( $by_id[ $p['id'] ] );
		} else {
			$card           = dlux_booking_card( $p );
			$card['id']     = '';      // not bookable online → not selectable
			$card['prices'] = [];
			$card['quote']  = true;
			$cards[]        = $card;
		}
	}
	foreach ( $by_id as $p ) $cards[] = dlux_booking_card( $p ); // anything left over
	return $cards;
}
