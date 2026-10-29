<?php
/**
 * Confirmation emails: one to the customer, one to the shop (branded HTML, see email-template.php).
 * Sent once: when a card booking is paid, or straight away for a pay-at-the-shop booking.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dlux_bk_send_emails( $booking_id ) {
	// Washing and detailing bookings both email the customer and the shop.
	// Each email has its own "sent" flag, so one that failed is tried again next time.
	if ( get_post_meta( $booking_id, '_dlux_mailed', true ) ) {
		return; // Emailed by an older version (both at once).
	}
	// Short lock so the Stripe webhook and the confirmation page can't both send at the same moment.
	$lock = (int) get_post_meta( $booking_id, '_dlux_mailing', true );
	if ( $lock && $lock > time() - 60 ) {
		return;
	}
	delete_post_meta( $booking_id, '_dlux_mailing' );
	if ( ! add_post_meta( $booking_id, '_dlux_mailing', time(), true ) ) {
		return;
	}
	$b = dlux_bk_booking( $booking_id );
	if ( ! $b ) {
		delete_post_meta( $booking_id, '_dlux_mailing' );
		return;
	}

	$biz     = dlux_bk_business();
	$package = implode( ' + ', wp_list_pluck( $b['items'], 'name' ) );
	$total   = dlux_bk_money( $b['total'] );
	$from    = 'From: ' . wp_specialchars_decode( $biz['name'], ENT_QUOTES ) . ' <' . get_option( 'admin_email' ) . '>';
	$late    = (bool) get_post_meta( $booking_id, '_dlux_late_payment', true );
	$shop    = 'unpaid' === $b['payment']; // Pay at the shop.

	$size_pkg = $package . ( $b['cls'] ? ' (' . $b['cls'] . ')' : '' );
	$extras   = implode( ', ', wp_list_pluck( array_slice( $b['items'], 1 ), 'name' ) );
	$main     = $b['items'] ? $b['items'][0]['name'] : $b['package'];

	/* Customer */
	if ( $b['email'] && is_email( $b['email'] ) && ! get_post_meta( $booking_id, '_dlux_mailed_customer', true ) ) {
		$first = trim( strtok( $b['name'], ' ' ) );
		$args  = array(
			'preheader' => 'Your booking is confirmed for ' . $b['when_slot'] . '.',
			'eyebrow'   => 'Booking confirmed',
			'title'     => 'You’re booked in' . ( $first ? ', ' . $first : '' ) . '!',
			'intro'     => array(
				'Hi ' . ( $first ? $first : 'there' ) . ',',
				'Thanks for booking with ' . $biz['name'] . '. Your booking is confirmed' . ( $shop ? ' – you can pay at the shop on the day.' : ' and paid.' ),
			),
			'rows'      => array(
				array( 'When', $b['when_slot'] ),
				array( 'Service', $b['service'] ),
				array( 'Package', $main ),
				array( 'Option', $b['option'] ),
				array( 'Vehicle size', $b['cls'] ),
				array( 'Extras', $extras ),
				array( 'Rego', $b['rego'] ),
				array( 'Where', $biz['address'] ),
			),
			'total'     => array( $shop ? 'To pay at the shop' : 'Paid by card', $total ),
			'button'    => $biz['address'] ? array( 'Get directions', 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $biz['name'] . ' ' . $biz['address'] ) ) : array(),
			'outro'     => array_filter(
				array(
					$biz['phone'] ? 'Need to change your booking? Call us on ' . $biz['phone'] . '.' : '',
					'See you soon,' . "\n" . $biz['name'],
				)
			),
		);
		$args = apply_filters( 'dlux_booking_customer_email_args', $args, $b );
		$html = dlux_bk_admin_booking_email_html(
			$b,
			array(
				'for'       => 'customer',
				'url'       => $args['button'][1] ?? '',
				'preheader' => $args['preheader'] ?? '',
			)
		);
		if ( dlux_bk_mail_raw( $b['email'], 'Your booking is confirmed: ' . $b['when_slot'], $html, dlux_bk_email_text( $args ), array( $from ) ) ) {
			update_post_meta( $booking_id, '_dlux_mailed_customer', time() );
		}
	}

	/* Shop */
	$to = trim( (string) dlux_bk_opt( 'notify_email', '' ) );
	$to = apply_filters( 'dlux_booking_admin_email', $to ? $to : get_option( 'admin_email' ) );
	if ( $to && ! get_post_meta( $booking_id, '_dlux_mailed_admin', true ) ) {
		$args = array(
			'preheader' => $b['rego'] . ' · ' . $b['when_slot'],
			'eyebrow'   => 'New online booking',
			'title'     => $shop ? 'New booking – pay at the shop' : 'New booking – paid',
			'alert'     => $late ? 'This customer paid after their checkout had timed out. Please check the time slot is still free.' : '',
			'rows'      => array(
				array( 'When', $b['when_slot'] ),
				array( 'Service', $b['service'] ),
				array( 'Package', $main ),
				array( 'Option', $b['option'] ),
				array( 'Vehicle size', $b['cls'] ),
				array( 'Extras', $extras ),
				array( 'Name', $b['name'] ),
				array( 'Mobile', $b['phone'] ),
				array( 'Email', $b['email'] ),
				array( 'Rego', $b['rego'] ),
				array( 'Vehicle', $b['vehicle'] ),
				array( 'Notes', $b['notes'] ),
				array( 'Found us via', $b['channel'] ),
			),
			'total'     => array( $shop ? 'To pay at the shop' : 'Paid by card', $total ),
			'button'    => array( 'Open booking', admin_url( 'post.php?post=' . $b['id'] . '&action=edit' ) ),
		);
		$args    = apply_filters( 'dlux_booking_admin_email_args', $args, $b );
		$headers = array( $from );
		if ( $b['email'] && is_email( $b['email'] ) ) {
			$headers[] = 'Reply-To: ' . $b['email'];
		}
		$html = dlux_bk_admin_booking_email_html( $b, array( 'late' => $late, 'url' => $args['button'][1] ?? '' ) );
		if ( dlux_bk_mail_raw( $to, 'New booking: ' . $b['rego'] . ', ' . $b['when_slot'], $html, dlux_bk_email_text( $args ), $headers ) ) {
			update_post_meta( $booking_id, '_dlux_mailed_admin', time() );
		}
	}

	delete_post_meta( $booking_id, '_dlux_mailing' );
}
