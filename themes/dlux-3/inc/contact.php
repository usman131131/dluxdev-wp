<?php
/**
 * Contact Us section: theme's own form, sent via AJAX (no form plugins).
 * Recipient and success text are read from the page's section row, never from the request.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Service options for the select: section setting, else Dlux Booking packages. */
function dlux_contact_services( $raw ) {
	$list = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $raw ) ) );
	if ( $list ) return array_values( $list );
	foreach ( [ 'wash', 'detail' ] as $g ) {
		$group = function_exists( 'dlux_booking_group' ) ? dlux_booking_group( $g ) : null;
		foreach ( (array) ( $group['packages'] ?? [] ) as $p ) {
			if ( ! empty( $p['name'] ) ) $list[] = $p['name'];
		}
	}
	return array_values( array_unique( $list ) );
}

/** Find the contact_us row on a page by index. */
function dlux_contact_row( $page_id, $index ) {
	if ( ! function_exists( 'get_field' ) ) return null;
	$rows = get_field( 'page_sections', $page_id );
	$row  = $rows[ $index ] ?? null;
	return ( is_array( $row ) && 'contact_us' === ( $row['acf_fc_layout'] ?? '' ) ) ? $row : null;
}

/**
 * Handle a contact submission (AJAX, with a normal-POST fallback when JS is off).
 * Sends the enquiry to the site admin email and a confirmation copy to the visitor.
 */
function dlux_contact_handle() {
	$is_ajax = wp_doing_ajax();
	$page_id = absint( $_POST['dlux_page'] ?? 0 );
	$index   = absint( $_POST['dlux_row'] ?? 0 );
	$back    = $page_id ? get_permalink( $page_id ) : home_url( '/' );
	$row     = $page_id ? dlux_contact_row( $page_id, $index ) : null;
	$anchor  = $row && ! empty( $row['section_id'] ) ? '#' . sanitize_title( $row['section_id'] ) : '';
	$success = $row && ! empty( $row['success_message'] ) ? $row['success_message'] : __( 'Thanks! Your message has been sent.', 'dlux' );

	$messages = [
		'sent'    => $success,
		'invalid' => __( 'Please add your name, a valid email and a message.', 'dlux' ),
		'error'   => __( 'Sorry, something went wrong. Please try again or contact us directly.', 'dlux' ),
	];
	$respond = function ( $code ) use ( $is_ajax, $back, $anchor, $messages ) {
		if ( $is_ajax ) {
			$data = [ 'message' => $messages[ $code ] ];
			'sent' === $code ? wp_send_json_success( $data ) : wp_send_json_error( $data, 'invalid' === $code ? 422 : 400 );
		}
		wp_safe_redirect( add_query_arg( 'contact', $code, $back ) . $anchor );
		exit;
	};

	if ( ! $row || ! isset( $_POST['dlux_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dlux_contact_nonce'] ) ), 'dlux_contact' ) ) {
		$respond( 'error' );
	}
	if ( ! empty( $_POST['dlux_website'] ) ) { // honeypot – pretend success
		$respond( 'sent' );
	}

	$name    = sanitize_text_field( wp_unslash( $_POST['dlux_name'] ?? '' ) );
	$email   = sanitize_email( wp_unslash( $_POST['dlux_email'] ?? '' ) );
	$phone   = sanitize_text_field( wp_unslash( $_POST['dlux_phone'] ?? '' ) );
	$service = sanitize_text_field( wp_unslash( $_POST['dlux_service'] ?? '' ) );
	$message = sanitize_textarea_field( wp_unslash( $_POST['dlux_message'] ?? '' ) );

	if ( '' === $name || ! is_email( $email ) || '' === $message ) {
		$respond( 'invalid' );
	}
	if ( $service && ! in_array( $service, dlux_contact_services( $row['service_options'] ?? '' ), true ) ) {
		$service = '';
	}

	$site    = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$admin   = get_option( 'admin_email' );
	$details = sprintf(
		"%s: %s\n%s: %s\n%s: %s\n%s: %s\n\n%s:\n%s",
		__( 'Name', 'dlux' ), $name,
		__( 'Email', 'dlux' ), $email,
		__( 'Phone', 'dlux' ), $phone ?: '—',
		__( 'Service', 'dlux' ), $service ?: '—',
		__( 'Message', 'dlux' ), $message
	);

	$rows = [
		[ __( 'Name', 'dlux' ), $name ],
		[ __( 'Email', 'dlux' ), $email ],
		[ __( 'Phone', 'dlux' ), $phone ],
		[ __( 'Service', 'dlux' ), $service ],
		[ __( 'Message', 'dlux' ), $message ],
	];

	// Branded HTML emails (DLUX template from the Dlux Booking plugin) – plain text if the plugin is off.
	$branded = function_exists( 'dlux_bk_mail' );

	// 1) To the site admin – reply goes straight to the visitor.
	/* translators: %s: sender name */
	$admin_subject = sprintf( __( 'New enquiry from %s', 'dlux' ), $name );
	$admin_headers = [ 'Reply-To: ' . $name . ' <' . $email . '>' ];
	$sent_admin    = $branded
		? dlux_bk_mail( $admin, $admin_subject, [
			'preheader' => wp_trim_words( $message, 18 ),
			'eyebrow'   => __( 'Contact form', 'dlux' ),
			'title'     => __( 'New enquiry', 'dlux' ),
			/* translators: %s: page URL */
			'intro'     => [ sprintf( __( 'Someone sent a message from %s.', 'dlux' ), $back ) ],
			'rows'      => $rows,
			/* translators: %s: sender name */
			'button'    => [ sprintf( __( 'Reply to %s', 'dlux' ), $name ), 'mailto:' . $email ],
		], $admin_headers )
		: wp_mail( $admin, $admin_subject, $details . "\n\n—\n" . sprintf( __( 'Sent from %s', 'dlux' ), $back ), $admin_headers );

	// 2) Confirmation to the visitor – replies go to the admin.
	/* translators: %s: site name */
	$user_subject = sprintf( __( 'We\'ve received your message – %s', 'dlux' ), $site );
	$user_headers = [ 'Reply-To: ' . $site . ' <' . $admin . '>' ];
	if ( $branded ) {
		dlux_bk_mail( $email, $user_subject, [
			'preheader' => __( 'Thanks for getting in touch – we’ll get back to you shortly.', 'dlux' ),
			'eyebrow'   => __( 'Message received', 'dlux' ),
			/* translators: %s: visitor name */
			'title'     => sprintf( __( 'Thanks, %s!', 'dlux' ), strtok( $name, ' ' ) ),
			'intro'     => [
				/* translators: %s: site name */
				sprintf( __( 'Thanks for getting in touch with %s. We’ve received your message and will get back to you shortly.', 'dlux' ), $site ),
				__( 'Here’s a copy of what you sent:', 'dlux' ),
			],
			'rows'      => $rows,
			'outro'     => [ __( 'Talk soon,', 'dlux' ) . "\n" . $site ],
		], $user_headers );
	} else {
		wp_mail(
			$email,
			$user_subject,
			sprintf(
				/* translators: 1: visitor name, 2: site name */
				__( "Hi %1\$s,\n\nThanks for getting in touch with %2\$s. We've received your message and will get back to you shortly.\n\nHere's a copy of what you sent:\n\n", 'dlux' ),
				$name,
				$site
			) . $details . "\n\n— " . $site,
			$user_headers
		);
	}

	$respond( $sent_admin ? 'sent' : 'error' );
}
add_action( 'wp_ajax_dlux_contact', 'dlux_contact_handle' );
add_action( 'wp_ajax_nopriv_dlux_contact', 'dlux_contact_handle' );
add_action( 'admin_post_dlux_contact', 'dlux_contact_handle' );
add_action( 'admin_post_nopriv_dlux_contact', 'dlux_contact_handle' );

/** Small icons for contact details. */
function dlux_contact_icon( $name ) {
	$paths = [
		'location' => '<path fill="currentColor" d="M12 2a7 7 0 00-7 7c0 5.2 7 13 7 13s7-7.8 7-13a7 7 0 00-7-7zm0 9.5A2.5 2.5 0 1112 6a2.5 2.5 0 010 5z"/>',
		'email'    => '<path fill="currentColor" d="M3 5h18a1 1 0 011 1v12a1 1 0 01-1 1H3a1 1 0 01-1-1V6a1 1 0 011-1zm9 7.2L4.4 7H4v.6l8 5.4 8-5.4V7h-.4L12 12.2z"/>',
		'message'  => '<path fill="currentColor" d="M4 3h16a2 2 0 012 2v11a2 2 0 01-2 2H9l-5 4v-4a2 2 0 01-2-2V5a2 2 0 012-2z"/>',
		'phone'    => '<path fill="currentColor" d="M6.6 10.8a15.1 15.1 0 006.6 6.6l2.2-2.2a1 1 0 011-.25 11.4 11.4 0 003.6.57 1 1 0 011 1V20a1 1 0 01-1 1A17 17 0 013 4a1 1 0 011-1h3.5a1 1 0 011 1c0 1.25.2 2.45.57 3.57a1 1 0 01-.25 1L6.6 10.8z"/>',
		'clock'    => '<path fill="currentColor" d="M12 2a10 10 0 100 20 10 10 0 000-20zm1 10.4l3.2 1.9-.8 1.3L11 13V7h2v5.4z"/>',
	];
	return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ( $paths[ $name ] ?? $paths['message'] ) . '</svg>';
}
