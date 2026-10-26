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
