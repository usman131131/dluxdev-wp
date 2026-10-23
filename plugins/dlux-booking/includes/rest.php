<?php
/**
 * REST API used by the booking widget (namespace dlux/v1).
 *
 *   GET  /menu                       packages, sizes and add-ons
 *   GET  /availability?day=Y-m-d     time slots for a day
 *   POST /booking                    create a booking + Stripe Checkout session
 *   GET  /booking-status?id=&t=      booking details for the confirmation page
 *   POST /booking-cancel             customer came back from Stripe without paying
 *   POST /stripe-webhook             Stripe events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'rest_api_init',
	function () {
		$ns   = 'dlux/v1';
		$open = '__return_true';
		register_rest_route( $ns, '/menu', array( 'methods' => 'GET', 'callback' => 'dlux_bk_rest_menu', 'permission_callback' => $open ) );
		register_rest_route( $ns, '/availability', array( 'methods' => 'GET', 'callback' => 'dlux_bk_rest_availability', 'permission_callback' => $open ) );
		register_rest_route( $ns, '/booking', array( 'methods' => 'POST', 'callback' => 'dlux_bk_rest_create', 'permission_callback' => $open ) );
		register_rest_route( $ns, '/booking-status', array( 'methods' => 'GET', 'callback' => 'dlux_bk_rest_status', 'permission_callback' => $open ) );
		register_rest_route( $ns, '/booking-cancel', array( 'methods' => 'POST', 'callback' => 'dlux_bk_rest_cancel', 'permission_callback' => $open ) );
		register_rest_route( $ns, '/stripe-webhook', array( 'methods' => 'POST', 'callback' => 'dlux_bk_webhook', 'permission_callback' => $open ) );
	}
);

/** Request value as plain text. Arrays/objects (malformed requests) become ''. */
function dlux_bk_str( $v ) {
	return is_scalar( $v ) ? trim( (string) $v ) : '';
}

function dlux_bk_err( $msg, $status = 400 ) {
	return new WP_REST_Response( array( 'error' => $msg ), $status );
}

function dlux_bk_nocache( WP_REST_Response $r ) {
	$r->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );
	return $r;
}

function dlux_bk_rest_menu() {
	$m = dlux_bk_menu();
	$c = dlux_bk_config();
	return dlux_bk_nocache(
		new WP_REST_Response(
			array(
				'classes'      => $m['classes'],
				'groups'       => $m['groups'],
				'maxDaysAhead' => $c['ahead'],
				'today'        => dlux_bk_today(), // Site date, so visitors in other timezones see the right days.
				'currency'     => dlux_bk_currency(),
				'ready'        => dlux_bk_stripe_ready(),
			)
		)
	);
}

function dlux_bk_rest_availability( WP_REST_Request $r ) {
	$day = dlux_bk_str( $r->get_param( 'day' ) );
	if ( ! dlux_bk_valid_day( $day ) ) {
		return dlux_bk_err( 'Please choose a date in the next ' . dlux_bk_config()['ahead'] . ' days.' );
	}
	return dlux_bk_nocache( new WP_REST_Response( array( 'day' => $day, 'slots' => dlux_bk_slots( $day ) ) ) );
}

/** Simple per-visitor limit on new bookings. */
function dlux_bk_rate_limited() {
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'x';
	$key = 'dlux_bk_rl_' . md5( $ip );
	$n   = (int) get_transient( $key );
	if ( $n >= 8 ) {
		return true;
	}
	set_transient( $key, $n + 1, 15 * MINUTE_IN_SECONDS );
	return false;
}

function dlux_bk_rest_create( WP_REST_Request $r ) {
	global $wpdb;
	$p = $r->get_json_params();
	$p = is_array( $p ) ? $p : array();

	if ( ! empty( $p['website'] ) ) { // Honeypot.
		return dlux_bk_err( 'Something went wrong. Please call us to book.' );
	}
	// How they'll pay: online by card (Stripe) or at the shop.
	$card_on = dlux_bk_stripe_ready();
	$shop_on = dlux_bk_pay_shop_on();
	$pay     = 'shop' === dlux_bk_str( $p['pay'] ?? '' ) ? 'shop' : 'card';
	if ( 'card' === $pay && ! $card_on && $shop_on ) {
		$pay = 'shop';
	}
	if ( ( 'card' === $pay && ! $card_on ) || ( 'shop' === $pay && ! $shop_on ) ) {
		return dlux_bk_err( 'Online booking is not set up yet. Please call us to book.', 503 );
	}

	/* What */
	$menu = dlux_bk_menu();
	$cls  = dlux_bk_str( $p['cls'] ?? '' );
	if ( ! in_array( $cls, $menu['classes'], true ) ) {
		return dlux_bk_err( 'Please choose your vehicle size.' );
	}
	$pkg = dlux_bk_find_package( dlux_bk_str( $p['package'] ?? '' ) );
	if ( ! $pkg || ! isset( $pkg['prices'][ $cls ] ) ) {
		return dlux_bk_err( 'Please choose a package for your vehicle size.' );
	}
	$items = array( array( 'id' => $pkg['id'], 'name' => $pkg['name'], 'price' => (float) $pkg['prices'][ $cls ] ) );
	// Only extras that belong to the chosen package are accepted.
	$want = is_array( $p['extras'] ?? null ) ? array_unique( array_map( 'dlux_bk_str', $p['extras'] ) ) : array();
	foreach ( $pkg['extras'] as $x ) {
		if ( in_array( $x['id'], $want, true ) ) {
			$items[] = array( 'id' => $x['id'], 'name' => $x['name'], 'price' => (float) $x['price'] );
		}
	}
	$total = round( array_sum( wp_list_pluck( $items, 'price' ) ), 2 );

	// "Choose from" options: one must be picked when the package has them (price doesn't change).
	$option  = '';
	$choices = (array) ( $pkg['choose'] ?? array() );
	if ( $choices ) {
		$want = dlux_bk_str( $p['option'] ?? '' );
		foreach ( $choices as $i => $c ) {
			if ( $want === $c || ( ctype_digit( $want ) && (int) $want === $i + 1 ) ) {
				$option = $c;
				break;
			}
		}
		if ( '' === $option ) {
			return dlux_bk_err( 'Please choose one option for ' . $pkg['name'] . '.' );
		}
	}

	/* When */
	$day  = dlux_bk_str( $p['day'] ?? '' );
	$time = dlux_bk_str( $p['time'] ?? '' );
	if ( ! dlux_bk_valid_day( $day ) || ! preg_match( '/^\d{2}:\d{2}$/', $time ) ) {
		return dlux_bk_err( 'Please choose a day and time.' );
	}

	/* Who */
	$name    = sanitize_text_field( dlux_bk_str( $p['name'] ?? '' ) );
	$phone   = sanitize_text_field( dlux_bk_str( $p['phone'] ?? '' ) );
	$email   = sanitize_email( dlux_bk_str( $p['email'] ?? '' ) );
	$rego    = strtoupper( preg_replace( '/[^a-z0-9]/i', '', dlux_bk_str( $p['rego'] ?? '' ) ) );
	$vehicle = sanitize_text_field( dlux_bk_str( $p['vehicle'] ?? '' ) );
	$notes   = mb_substr( sanitize_textarea_field( is_scalar( $p['notes'] ?? '' ) ? (string) ( $p['notes'] ?? '' ) : '' ), 0, 1000 );
	$channel = sanitize_text_field( dlux_bk_str( $p['channel'] ?? '' ) );
	$consent = ! empty( $p['consent'] );

	if ( strlen( $rego ) < 2 || strlen( $rego ) > 10 ) {
		return dlux_bk_err( 'Please enter your rego.' );
	}
	if ( '' === $name ) {
		return dlux_bk_err( 'Please enter your name.' );
	}
	if ( strlen( preg_replace( '/\D/', '', $phone ) ) < 8 ) {
		return dlux_bk_err( 'Please enter your mobile number.' );
	}
	if ( ! is_email( $email ) ) {
		return dlux_bk_err( 'Please enter your email address.' );
	}
	if ( dlux_bk_rate_limited() ) {
		return dlux_bk_err( 'Too many booking attempts. Please wait a few minutes, or call us.', 429 );
	}

	/* Hold the time slot. A database lock stops two people taking the last space at once. */
	$lock = $wpdb->prefix . 'dlux_bk_slots';
	$wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 10)', $lock ) );

	if ( ! dlux_bk_slot_free( $day, $time ) ) {
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		return dlux_bk_err( 'Sorry, that time has just been taken. Please choose another time.', 409 );
	}

	$id = wp_insert_post(
		array(
			'post_type'   => 'dlux_booking',
			'post_status' => 'publish',
			'post_title'  => $rego . ' · ' . $name,
		),
		true
	);
	if ( is_wp_error( $id ) ) {
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		return dlux_bk_err( 'We couldn\'t save your booking. Please try again, or call us.', 500 );
	}

	update_post_meta( $id, '_dlux_token', wp_generate_password( 32, false ) );
	update_post_meta( $id, '_dlux_items', $items ); // Stored as an array (JSON + post meta would strip the backslashes).
	update_post_meta( $id, '_dlux_mode', dlux_bk_stripe_mode() );
	update_post_meta( $id, '_dlux_mins', (int) $pkg['mins'] );
	$fields = array(
		'status'       => 'shop' === $pay ? 'confirmed' : 'pending',
		'payment'      => 'shop' === $pay ? 'unpaid' : 'pending', // "unpaid" = Pay at shop.
		'date'         => str_replace( '-', '', $day ),
		'time'         => $time . ':00',
		'service'      => dlux_bk_group_label( $pkg['group'] ),
		'vehicle_size' => $cls,
		'package'      => $pkg['name'],
		'option'       => $option,
		'addons'       => implode( "\n", wp_list_pluck( array_slice( $items, 1 ), 'name' ) ),
		'total'        => $total,
		'name'         => $name,
		'phone'        => $phone,
		'email'        => $email,
		'rego'         => $rego,
		'vehicle'      => $vehicle,
		'notes'        => $notes,
		'channel'      => $channel,
		'consent'      => $consent ? 1 : 0,
	);
	foreach ( $fields as $k => $v ) {
		dlux_bk_set( $id, $k, $v );
	}
	dlux_bk_refresh_title( $id );

	$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );

	/* Pay at the shop: confirmed now, emails to the customer and the shop, straight to the confirmation page. */
	if ( 'shop' === $pay ) {
		do_action( 'dlux_booking_created', $id );
		dlux_bk_send_emails( $id );
		$token   = get_post_meta( $id, '_dlux_token', true );
		$confirm = dlux_bk_page_url( 'confirm' );
		return dlux_bk_nocache(
			new WP_REST_Response(
				array(
					'bookingId'  => $id,
					'token'      => $token,
					'confirmUrl' => $confirm ? add_query_arg( array( 'booking' => $id, 't' => $token ), $confirm ) : '',
				)
			)
		);
	}

	/* Stripe */
	$session = dlux_bk_create_checkout( $id );
	if ( is_wp_error( $session ) || empty( $session['url'] ) ) {
		dlux_bk_mark_cancelled( $id, 'failed' );
		if ( is_wp_error( $session ) ) {
			error_log( '[Dlux Booking] Stripe: ' . $session->get_error_message() ); // phpcs:ignore
		}
		return dlux_bk_err( 'We couldn\'t start the payment. Please try again, or call us.', 502 );
	}
	dlux_bk_set( $id, 'stripe_session', $session['id'] );
	update_post_meta( $id, '_dlux_livemode', empty( $session['livemode'] ) ? 0 : 1 );

	do_action( 'dlux_booking_created', $id );

	return dlux_bk_nocache(
		new WP_REST_Response(
			array(
				'bookingId'   => $id,
				'token'       => get_post_meta( $id, '_dlux_token', true ),
				'checkoutUrl' => $session['url'],
			)
		)
	);
}

/** Booking id + token from the request, or null. */
function dlux_bk_rest_booking( WP_REST_Request $r ) {
	$id    = absint( dlux_bk_str( $r->get_param( 'id' ) ) );
	$token = dlux_bk_str( $r->get_param( 't' ) );
	$saved = $id ? (string) get_post_meta( $id, '_dlux_token', true ) : '';
	if ( ! $id || '' === $token || '' === $saved || ! hash_equals( $saved, $token ) || 'dlux_booking' !== get_post_type( $id ) ) {
		return null;
	}
	return $id;
}

function dlux_bk_rest_status( WP_REST_Request $r ) {
	$id = dlux_bk_rest_booking( $r );
	if ( ! $id ) {
		return dlux_bk_err( 'Booking not found.', 404 );
	}
	if ( 'pending' === dlux_bk_get( $id, 'payment' ) ) {
		dlux_bk_sync( $id ); // In case the webhook hasn't arrived yet.
	}
	$b = dlux_bk_booking( $id );
	return dlux_bk_nocache(
		new WP_REST_Response(
			array(
				'status'    => $b['status'],
				'payment'   => $b['payment'],
				'firstName' => trim( (string) strtok( $b['name'], ' ' ) ),
				'rego'      => $b['rego'],
				'day'       => $b['day'],
				'time'      => $b['time'],
				'mins'      => $b['mins'] ? $b['mins'] : dlux_bk_config()['slot'],
				'when'      => $b['when'],
				'items'     => array_map(
					function ( $i ) {
						return array( 'name' => $i['name'] );
					},
					$b['items']
				),
				'total'     => $b['total'],
				'business'  => dlux_bk_business(),
			)
		)
	);
}

function dlux_bk_rest_cancel( WP_REST_Request $r ) {
	$id = dlux_bk_rest_booking( $r );
	if ( ! $id ) {
		return dlux_bk_err( 'Booking not found.', 404 );
	}
	$state = dlux_bk_cancel_unpaid( $id );
	return dlux_bk_nocache( new WP_REST_Response( array( 'payment' => $state ) ) );
}
