<?php
/**
 * Stripe Checkout (one-time payments). No Stripe library needed:
 * the plugin talks to the Stripe API with wp_remote_request().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dlux_bk_stripe_mode() {
	return 'live' === dlux_bk_opt( 'stripe_mode', 'test' ) ? 'live' : 'test';
}

function dlux_bk_stripe_secret( $mode = null ) {
	$mode = $mode ? $mode : dlux_bk_stripe_mode();
	return trim( (string) dlux_bk_opt( 'stripe_' . $mode . '_secret', '' ) );
}

function dlux_bk_stripe_ready() {
	return '' !== dlux_bk_stripe_secret();
}

/**
 * Call the Stripe API.
 *
 * @param string      $method GET or POST.
 * @param string      $path   e.g. 'checkout/sessions'.
 * @param array       $params Form parameters.
 * @param string|null $mode   'test' / 'live'; defaults to the current mode.
 * @param string      $idem   Idempotency key for POSTs.
 * @return array|WP_Error
 */
function dlux_bk_stripe( $method, $path, $params = array(), $mode = null, $idem = '' ) {
	$key = dlux_bk_stripe_secret( $mode );
	if ( '' === $key ) {
		return new WP_Error( 'dlux_stripe', 'Stripe is not set up yet.' );
	}
	$url  = 'https://api.stripe.com/v1/' . ltrim( $path, '/' );
	$args = array(
		'method'  => $method,
		'timeout' => 30,
		'headers' => array( 'Authorization' => 'Bearer ' . $key ),
	);
	if ( 'GET' === $method ) {
		if ( $params ) {
			$url .= '?' . http_build_query( $params, '', '&' );
		}
	} else {
		$args['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
		$args['body']                    = http_build_query( $params, '', '&' );
		if ( $idem ) {
			$args['headers']['Idempotency-Key'] = $idem;
		}
	}
	$r = wp_remote_request( $url, $args );
	if ( is_wp_error( $r ) ) {
		return new WP_Error( 'dlux_stripe', 'Could not reach Stripe: ' . $r->get_error_message() );
	}
	$code = (int) wp_remote_retrieve_response_code( $r );
	$data = json_decode( wp_remote_retrieve_body( $r ), true );
	if ( $code >= 400 || ! is_array( $data ) ) {
		$msg = is_array( $data ) && ! empty( $data['error']['message'] ) ? $data['error']['message'] : 'Stripe error (' . $code . ').';
		return new WP_Error( 'dlux_stripe', $msg, array( 'status' => $code ) );
	}
	return $data;
}

/** Amount in the smallest unit (cents). */
function dlux_bk_cents( $amount ) {
	return (int) round( (float) $amount * 100 );
}

/**
 * Create the Checkout session for a booking.
 *
 * @return array|WP_Error Stripe session.
 */
function dlux_bk_create_checkout( $booking_id ) {
	$b = dlux_bk_booking( $booking_id );
	if ( ! $b ) {
		return new WP_Error( 'dlux_stripe', 'Booking not found.' );
	}
	$biz      = dlux_bk_business();
	$currency = dlux_bk_currency();
	$when     = $b['when'];

	$line_items = array();
	foreach ( $b['items'] as $i => $item ) {
		$line_items[] = array(
			'quantity'   => 1,
			'price_data' => array(
				'currency'     => $currency,
				'unit_amount'  => dlux_bk_cents( $item['price'] ),
				'product_data' => array(
					'name'        => 0 === $i ? $item['name'] . ( $b['cls'] ? ' (' . $b['cls'] . ')' : '' ) : $item['name'],
					'description' => 0 === $i ? trim( $b['service'] . ' · ' . $when . ' · ' . $b['rego'], ' ·' ) : 'Extra',
				),
			),
		);
	}

	$confirm = dlux_bk_page_url( 'confirm' );
	$book    = dlux_bk_page_url( 'booking' );
	$confirm = $confirm ? $confirm : home_url( '/' );
	$book    = $book ? $book : home_url( '/' );
	$q       = array( 'booking' => $b['id'], 't' => $b['token'] );

	$params = array(
		'mode'                => 'payment',
		'line_items'          => $line_items,
		'client_reference_id' => (string) $b['id'],
		'metadata'            => array(
			'booking_id' => $b['id'],
			'rego'       => $b['rego'],
			'when'       => $when,
		),
		'payment_intent_data' => array(
			'description' => $biz['name'] . ' booking #' . $b['id'] . ': ' . $b['rego'] . ', ' . $when,
			'metadata'    => array( 'booking_id' => $b['id'] ),
		),
		'success_url'         => add_query_arg( $q, $confirm ),
		'cancel_url'          => add_query_arg( array_merge( $q, array( 'cancelled' => 1 ) ), $book ),
		// Stripe's minimum is 30 minutes; the time slot is held for the same time.
		'expires_at'          => time() + 31 * MINUTE_IN_SECONDS,
	);
	if ( $b['email'] && is_email( $b['email'] ) ) {
		$params['customer_email']                     = $b['email'];
		$params['payment_intent_data']['receipt_email'] = $b['email'];
	}

	$params = apply_filters( 'dlux_booking_checkout_params', $params, $b );
	return dlux_bk_stripe( 'POST', 'checkout/sessions', $params, null, 'dlux-bk-' . $b['id'] . '-' . $b['token'] );
}

/* ---------------- Payment state changes ---------------- */

function dlux_bk_mark_paid( $booking_id, $session ) {
	$b = dlux_bk_booking( $booking_id );
	if ( ! $b ) {
		return;
	}
	if ( 'paid' !== $b['payment'] ) {
		dlux_bk_set( $booking_id, 'payment', 'paid' );
		if ( in_array( $b['status'], array( 'pending', 'cancelled' ), true ) ) {
			dlux_bk_set( $booking_id, 'status', 'confirmed' );
		}
		if ( ! empty( $session['payment_intent'] ) ) {
			$pi = is_array( $session['payment_intent'] ) ? $session['payment_intent']['id'] : $session['payment_intent'];
			dlux_bk_set( $booking_id, 'stripe_payment_intent', (string) $pi );
		}
		if ( isset( $session['amount_total'] ) ) {
			dlux_bk_set( $booking_id, 'amount_paid', number_format( (int) $session['amount_total'] / 100, 2, '.', '' ) );
		}
		dlux_bk_set( $booking_id, 'paid_at', wp_date( 'Y-m-d H:i:s' ) );
		update_post_meta( $booking_id, '_dlux_livemode', empty( $session['livemode'] ) ? 0 : 1 );

		if ( 'cancelled' === $b['status'] ) {
			// Paid after the slot was released. Rare, but tell the shop.
			update_post_meta( $booking_id, '_dlux_late_payment', 1 );
		}
		do_action( 'dlux_booking_paid', $booking_id );
	}
	dlux_bk_send_emails( $booking_id );
}

function dlux_bk_mark_cancelled( $booking_id, $payment = 'failed' ) {
	$b = dlux_bk_booking( $booking_id );
	if ( ! $b || 'pending' !== $b['payment'] ) {
		return; // Only unpaid online bookings are cancelled automatically.
	}
	dlux_bk_set( $booking_id, 'status', 'cancelled' );
	dlux_bk_set( $booking_id, 'payment', $payment );
	do_action( 'dlux_booking_cancelled', $booking_id );
}

/** Which mode (test/live) a booking's Checkout session was made in. */
function dlux_bk_booking_mode( $booking_id ) {
	$m = get_post_meta( $booking_id, '_dlux_mode', true );
	return in_array( $m, array( 'test', 'live' ), true ) ? $m : dlux_bk_stripe_mode();
}

/**
 * Ask Stripe about an unpaid booking (used when the webhook hasn't arrived yet).
 *
 * @return string 'paid', 'open', 'expired' or 'unknown'.
 */
function dlux_bk_sync( $booking_id ) {
	$b = dlux_bk_booking( $booking_id );
	if ( ! $b ) {
		return 'unknown';
	}
	if ( 'paid' === $b['payment'] ) {
		return 'paid';
	}
	if ( 'pending' !== $b['payment'] || ! $b['session'] ) {
		return 'unknown';
	}
	$s = dlux_bk_stripe( 'GET', 'checkout/sessions/' . rawurlencode( $b['session'] ), array(), dlux_bk_booking_mode( $booking_id ) );
	if ( is_wp_error( $s ) ) {
		return 'unknown';
	}
	if ( 'paid' === ( $s['payment_status'] ?? '' ) || 'no_payment_required' === ( $s['payment_status'] ?? '' ) ) {
		dlux_bk_mark_paid( $booking_id, $s );
		return 'paid';
	}
	if ( 'expired' === ( $s['status'] ?? '' ) ) {
		dlux_bk_mark_cancelled( $booking_id, 'failed' );
		return 'expired';
	}
	return 'open';
}

/**
 * Cancel an unpaid booking and close its Checkout page so it can't be paid later.
 *
 * @return string Final payment state.
 */
function dlux_bk_cancel_unpaid( $booking_id ) {
	$b = dlux_bk_booking( $booking_id );
	if ( ! $b || 'pending' !== $b['payment'] ) {
		return $b ? $b['payment'] : 'unknown';
	}
	if ( $b['session'] ) {
		$r = dlux_bk_stripe( 'POST', 'checkout/sessions/' . rawurlencode( $b['session'] ) . '/expire', array(), dlux_bk_booking_mode( $booking_id ) );
		if ( is_wp_error( $r ) ) {
			// Maybe it was just paid, or had already expired.
			$state = dlux_bk_sync( $booking_id );
			if ( 'paid' === $state ) {
				return 'paid';
			}
		}
	}
	dlux_bk_mark_cancelled( $booking_id, 'failed' );
	return 'failed';
}

/** Find a booking by Checkout session, checking it really belongs to it. */
function dlux_bk_booking_for_session( $session ) {
	$id = (int) ( $session['metadata']['booking_id'] ?? ( $session['client_reference_id'] ?? 0 ) );
	if ( $id && 'dlux_booking' === get_post_type( $id ) && dlux_bk_get( $id, 'stripe_session' ) === ( $session['id'] ?? '' ) ) {
		return $id;
	}
	return 0;
}

/* ---------------- Webhook ---------------- */

function dlux_bk_verify_signature( $payload, $header ) {
	$parts = array();
	foreach ( explode( ',', (string) $header ) as $kv ) {
		$kv = explode( '=', trim( $kv ), 2 );
		if ( 2 === count( $kv ) ) {
			$parts[ $kv[0] ][] = $kv[1];
		}
	}
	$t = isset( $parts['t'][0] ) ? (int) $parts['t'][0] : 0;
	if ( ! $t || empty( $parts['v1'] ) || abs( time() - $t ) > 600 ) {
		return false;
	}
	foreach ( array( 'test', 'live' ) as $mode ) {
		$secret = trim( (string) dlux_bk_opt( 'stripe_' . $mode . '_webhook', '' ) );
		if ( '' === $secret ) {
			continue;
		}
		$expected = hash_hmac( 'sha256', $t . '.' . $payload, $secret );
		foreach ( $parts['v1'] as $sig ) {
			if ( hash_equals( $expected, $sig ) ) {
				return true;
			}
		}
	}
	return false;
}

function dlux_bk_webhook( WP_REST_Request $request ) {
	$payload = $request->get_body();
	if ( ! dlux_bk_verify_signature( $payload, $request->get_header( 'stripe_signature' ) ) ) {
		return new WP_REST_Response( array( 'error' => 'Invalid signature.' ), 400 );
	}
	$event = json_decode( $payload, true );
	$type  = (string) ( $event['type'] ?? '' );
	$obj   = (array) ( $event['data']['object'] ?? array() );

	switch ( $type ) {
		case 'checkout.session.completed':
		case 'checkout.session.async_payment_succeeded':
			$id = dlux_bk_booking_for_session( $obj );
			if ( $id && in_array( $obj['payment_status'] ?? '', array( 'paid', 'no_payment_required' ), true ) ) {
				dlux_bk_mark_paid( $id, $obj );
			}
			break;

		case 'checkout.session.async_payment_failed':
		case 'checkout.session.expired':
			$id = dlux_bk_booking_for_session( $obj );
			if ( $id ) {
				dlux_bk_mark_cancelled( $id, 'failed' );
			}
			break;

		case 'charge.refunded':
			$pi = (string) ( $obj['payment_intent'] ?? '' );
			if ( $pi ) {
				$ids = get_posts(
					array(
						'post_type'      => 'dlux_booking',
						'post_status'    => 'any',
						'posts_per_page' => 1,
						'fields'         => 'ids',
						'meta_key'       => 'bk_stripe_payment_intent', // phpcs:ignore
						'meta_value'     => $pi, // phpcs:ignore
					)
				);
				if ( $ids && ! empty( $obj['refunded'] ) ) {
					dlux_bk_set( $ids[0], 'payment', 'refunded' );
					dlux_bk_set( $ids[0], 'status', 'cancelled' );
				}
			}
			break;
	}
	return new WP_REST_Response( array( 'received' => true ), 200 );
}

/* ---------------- Hourly clean-up of abandoned checkouts ---------------- */

add_action(
	'dlux_bk_cleanup',
	function () {
		$ids = get_posts(
			array(
				'post_type'        => 'dlux_booking',
				'post_status'      => 'any',
				'posts_per_page'   => 50,
				'fields'           => 'ids',
				'suppress_filters' => true,
				'meta_query'       => array( array( 'key' => 'bk_payment', 'value' => 'pending' ) ), // phpcs:ignore
				'date_query'       => array(
					array(
						'column' => 'post_date_gmt',
						'before' => ( DLUX_BK_HOLD_MINUTES + 5 ) . ' minutes ago',
					),
				),
			)
		);
		foreach ( $ids as $id ) {
			if ( 'paid' !== dlux_bk_sync( $id ) ) {
				dlux_bk_cancel_unpaid( $id );
			}
		}
	}
);
