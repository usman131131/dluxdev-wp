<?php
/**
 * Quote form section (template-parts/sections/quote_form.php).
 * Sent via AJAX (assets/js/quote.js) with a normal-POST fallback.
 * Each request is saved as a "Quote request" (inc/quote-requests.php) and emailed to the shop and the customer.
 * Recipient, interests and success text are read from the page's section row, never from the request.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** "What are you interested in?" options, one per line. */
function dlux_quote_interests( $raw ) {
	$list = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $raw ) ) );
	return array_values( array_unique( $list ) );
}

/** Icons for the contact list (phone, message, email, location, clock). */
function dlux_quote_icon( $name ) {
	$paths = [
		'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"/>',
		'message'  => '<path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/><path d="M8 10h.01M12 10h.01M16 10h.01"/>',
		'email'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
		'location' => '<path d="M12 21s-7-6.2-7-11a7 7 0 0114 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
	];
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $paths[ $name ] ?? $paths['message'] ) . '</svg>';
}

/** The quote_form row on a page, by index. */
function dlux_quote_row( $page_id, $index ) {
	if ( ! function_exists( 'get_field' ) ) return null;
	$rows = get_field( 'page_sections', $page_id );
	$row  = $rows[ $index ] ?? null;
	return ( is_array( $row ) && 'quote_form' === ( $row['acf_fc_layout'] ?? '' ) ) ? $row : null;
}

function dlux_quote_handle() {
	$is_ajax = wp_doing_ajax();
	$page_id = absint( $_POST['dlux_page'] ?? 0 );
	$index   = absint( $_POST['dlux_row'] ?? 0 );
	$back    = $page_id ? get_permalink( $page_id ) : home_url( '/' );
	$row     = $page_id ? dlux_quote_row( $page_id, $index ) : null;
	$anchor  = $row && ! empty( $row['section_id'] ) ? '#' . sanitize_title( $row['section_id'] ) : '';

	$messages = [
		'sent'    => $row && ! empty( $row['success_message'] ) ? $row['success_message'] : __( 'Thanks! We’ll be in touch with your quote soon.', 'dlux' ),
		'invalid' => __( 'Please add your name and a mobile number or email.', 'dlux' ),
		'error'   => __( 'Sorry, something went wrong. Please call or email us instead.', 'dlux' ),
	];
	$respond = function ( $code ) use ( $is_ajax, $back, $anchor, $messages ) {
		if ( $is_ajax ) {
			$data = [ 'message' => $messages[ $code ] ];
			'sent' === $code ? wp_send_json_success( $data ) : wp_send_json_error( $data, 'invalid' === $code ? 422 : 400 );
		}
		wp_safe_redirect( add_query_arg( 'quote', $code, $back ) . $anchor );
		exit;
	};

	if ( ! $row || ! isset( $_POST['dlux_quote_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dlux_quote_nonce'] ) ), 'dlux_quote' ) ) {
		$respond( 'error' );
	}
	if ( ! empty( $_POST['dlux_website'] ) ) { // honeypot
		$respond( 'sent' );
	}

	$name    = sanitize_text_field( wp_unslash( $_POST['dlux_name'] ?? '' ) );
	$email   = sanitize_email( wp_unslash( $_POST['dlux_email'] ?? '' ) );
	$phone   = sanitize_text_field( wp_unslash( $_POST['dlux_phone'] ?? '' ) );
	$car     = sanitize_text_field( wp_unslash( $_POST['dlux_car'] ?? '' ) );
	$message = sanitize_textarea_field( wp_unslash( $_POST['dlux_message'] ?? '' ) );
	$allowed = dlux_quote_interests( $row['interests'] ?? '' );
	$picked  = array_values( array_intersect( $allowed, array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['dlux_interests'] ?? [] ) ) ) );

	if ( '' === $name || ( ! is_email( $email ) && strlen( preg_replace( '/\D/', '', $phone ) ) < 8 ) ) {
		$respond( 'invalid' );
	}
	if ( ! is_email( $email ) ) {
		$email = '';
	}

	// Simple flood limit: 5 requests per visitor per 10 minutes.
	$ip_key = 'dlux_quote_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	$count  = (int) get_transient( $ip_key );
	if ( $count >= 5 ) {
		$respond( 'error' );
	}
	set_transient( $ip_key, $count + 1, 10 * MINUTE_IN_SECONDS );

	// 1) Save it as a Quote request.
	$post_id = wp_insert_post( [
		'post_type'   => 'dlux_quote',
		'post_status' => 'publish',
		'post_title'  => $name . ( $picked ? ' – ' . implode( ', ', $picked ) : '' ),
	], true );
	if ( is_wp_error( $post_id ) || ! $post_id ) {
		$respond( 'error' );
	}
	foreach ( [
		'status'    => 'new',
		'interests' => implode( ', ', $picked ),
		'name'      => $name,
		'phone'     => $phone,
		'email'     => $email,
		'car'       => $car,
		'message'   => $message,
		'page'      => $back,
	] as $k => $v ) {
		dlux_quote_set( $post_id, $k, $v );
	}

	$q = [
		'id'        => $post_id,
		'name'      => $name,
		'phone'     => $phone,
		'email'     => $email,
		'car'       => $car,
		'message'   => $message,
		'interests' => $picked,
		'page'      => $back,
	];
	$text = implode( "\n", array_map( function ( $r ) { return $r[0] . ': ' . ( '' !== $r[1] ? $r[1] : '—' ); }, [
		[ __( 'Request ref', 'dlux' ), '#Q-' . $post_id ],
		[ __( 'Interested in', 'dlux' ), implode( ', ', $picked ) ],
		[ __( 'Name', 'dlux' ), $name ],
		[ __( 'Mobile', 'dlux' ), $phone ],
		[ __( 'Email', 'dlux' ), $email ],
		[ __( 'Car', 'dlux' ), $car ],
		[ __( 'Message', 'dlux' ), $message ],
	] ) );

	$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$from = 'From: ' . $site . ' <' . get_option( 'admin_email' ) . '>';
	$to   = trim( (string) ( $row['recipient'] ?? '' ) );
	if ( ! is_email( $to ) ) {
		$to = function_exists( 'dlux_bk_opt' ) ? trim( (string) dlux_bk_opt( 'notify_email', '' ) ) : '';
		$to = $to ?: get_option( 'admin_email' );
	}

	// 2) Email the shop (reply goes to the customer).
	$headers = [ $from ];
	if ( $email ) $headers[] = 'Reply-To: ' . $name . ' <' . $email . '>';
	$subject = sprintf( __( 'New quote request #Q-%1$d: %2$s', 'dlux' ), $post_id, $name ) . ( $picked ? ' – ' . implode( ', ', $picked ) : '' );
	$sent    = [];
	if ( dlux_quote_send( $to, $subject, dlux_quote_email_html( $q, 'admin' ), $text . "\n\n" . admin_url( 'post.php?post=' . $post_id . '&action=edit' ), $headers ) ) {
		$sent[] = 'shop';
	}

	// 3) Email the customer (reply goes to the shop).
	if ( $email ) {
		$user_subject = sprintf( __( 'We’ve received your quote request #Q-%d', 'dlux' ), $post_id );
		if ( dlux_quote_send( $email, $user_subject, dlux_quote_email_html( $q, 'customer' ), sprintf( __( "Hi %s,\n\nThanks for asking for a quote. We'll get back to you within one business day.\n\n", 'dlux' ), $name ) . $text . "\n\n— " . $site, [ $from, 'Reply-To: ' . $site . ' <' . $to . '>' ] ) ) {
			$sent[] = 'customer';
		}
	}
	dlux_quote_set( $post_id, 'emailed', $sent ? implode( ' + ', $sent ) . ' – ' . wp_date( 'j M Y, g:i a' ) : __( 'Not sent (mail error)', 'dlux' ) );

	// The request is saved even if email fails, so the visitor gets the success message.
	$respond( 'sent' );
}
add_action( 'wp_ajax_dlux_quote', 'dlux_quote_handle' );
add_action( 'wp_ajax_nopriv_dlux_quote', 'dlux_quote_handle' );
add_action( 'admin_post_dlux_quote', 'dlux_quote_handle' );
add_action( 'admin_post_nopriv_dlux_quote', 'dlux_quote_handle' );
