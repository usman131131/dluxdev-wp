<?php
/**
 * Booking SMS via Twilio: one text to the customer, one to the shop.
 * Sent with the confirmation emails (card payment confirmed, or a pay-at-the-shop booking).
 * Settings: Theme Settings › SMS (Twilio).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dlux_bk_sms_default( $who ) {
	return 'admin' === $who
		? 'New booking: {package}{option} ({size}) – {when}. {name}, {phone}, rego {rego}. {total} ({payment}).'
		: 'Hi {first_name}, your {business} booking is confirmed: {package}{option} ({size}) on {when}. Total {total} ({payment}). Questions? Call {shop_phone}.';
}

function dlux_bk_sms_on() {
	$on = dlux_bk_opt( 'sms_on', 0 );
	return $on && '0' !== $on && dlux_bk_opt( 'twilio_sid' ) && dlux_bk_opt( 'twilio_token' ) && dlux_bk_opt( 'twilio_from' );
}

/** "0410 111 190" → "+61410111190". */
function dlux_bk_sms_number( $raw ) {
	$n = preg_replace( '/[^0-9+]/', '', (string) $raw );
	if ( '' === $n ) {
		return '';
	}
	if ( 0 === strpos( $n, '+' ) ) {
		return $n;
	}
	if ( 0 === strpos( $n, '00' ) ) {
		return '+' . substr( $n, 2 );
	}
	$cc = '+' . ltrim( preg_replace( '/[^0-9]/', '', (string) dlux_bk_opt( 'sms_country', '+61' ) ), '+' );
	return $cc . ltrim( $n, '0' );
}

/** Send one SMS. Returns true or an error message. Every attempt is written to the SMS log. */
function dlux_bk_sms_send( $to, $body, $booking_id = 0, $who = '' ) {
	$raw = $to;
	$sid   = trim( (string) dlux_bk_opt( 'twilio_sid' ) );
	$token = trim( (string) dlux_bk_opt( 'twilio_token' ) );
	$from  = trim( (string) dlux_bk_opt( 'twilio_from' ) );
	$to    = dlux_bk_sms_number( $to );
	if ( ! $sid || ! $token || ! $from || ! $to ) {
		$missing = array_keys( array_filter( array( 'Account SID' => ! $sid, 'Auth token' => ! $token, 'Send from' => ! $from, 'phone number' => ! $to ) ) );
		$msg     = 'Not sent – missing ' . implode( ', ', $missing ) . '.';
		dlux_bk_sms_log( array( 'booking' => $booking_id, 'who' => $who, 'to' => $to ? $to : (string) $raw, 'status' => 'failed', 'error' => $msg, 'body' => $body ) );
		return $msg;
	}
	$args = array( 'To' => $to, 'Body' => mb_substr( $body, 0, 1500 ) );
	if ( 0 === strpos( $from, 'MG' ) ) {
		$args['MessagingServiceSid'] = $from;
	} else {
		$args['From'] = preg_match( '/^[+0-9\s()-]+$/', $from ) ? dlux_bk_sms_number( $from ) : $from;
	}
	$res = wp_remote_post(
		'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode( $sid ) . '/Messages.json',
		array(
			'timeout' => 15,
			'headers' => array( 'Authorization' => 'Basic ' . base64_encode( $sid . ':' . $token ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
			'body'    => $args,
		)
	);
	$entry = array(
		'booking' => $booking_id,
		'who'     => $who,
		'to'      => $to,
		'from'    => $args['From'] ?? $args['MessagingServiceSid'],
		'body'    => $args['Body'],
	);
	if ( is_wp_error( $res ) ) {
		$msg = 'Could not reach Twilio: ' . $res->get_error_message();
		dlux_bk_sms_log( $entry + array( 'status' => 'failed', 'error' => $msg ) );
		return $msg;
	}
	$code = (int) wp_remote_retrieve_response_code( $res );
	$data = json_decode( wp_remote_retrieve_body( $res ), true );
	$data = is_array( $data ) ? $data : array();
	if ( $code >= 200 && $code < 300 ) {
		// Accepted by Twilio. Delivery can still fail later – check the SID in Twilio › Monitor › Messaging logs.
		dlux_bk_sms_log(
			$entry + array(
				'status'     => 'sent',
				'http'       => $code,
				'sid'        => (string) ( $data['sid'] ?? '' ),
				'twilio'     => (string) ( $data['status'] ?? '' ),
				'error'      => ! empty( $data['error_message'] ) ? (string) $data['error_message'] : '',
				'error_code' => (string) ( $data['error_code'] ?? '' ),
			)
		);
		return true;
	}
	$msg = 'Twilio error ' . $code . ( ! empty( $data['code'] ) ? ' (code ' . $data['code'] . ')' : '' ) . ( ! empty( $data['message'] ) ? ': ' . $data['message'] : '' );
	dlux_bk_sms_log(
		$entry + array(
			'status'     => 'failed',
			'http'       => $code,
			'error'      => $msg,
			'error_code' => (string) ( $data['code'] ?? '' ),
			'more_info'  => (string) ( $data['more_info'] ?? '' ),
		)
	);
	return $msg;
}

/* ---------------- SMS log ---------------- */

/** Add one line to the SMS log (newest first, last 300 kept). */
function dlux_bk_sms_log( array $e ) {
	$log = get_option( 'dlux_bk_sms_log', array() );
	$log = is_array( $log ) ? $log : array();
	array_unshift( $log, $e + array( 'time' => time() ) );
	update_option( 'dlux_bk_sms_log', array_slice( $log, 0, 300 ), false );
}

/** Log a "skipped" reason once per booking (the send runs again each time payment is re-checked). */
function dlux_bk_sms_log_skip( $booking_id, $who, $why ) {
	if ( $booking_id && ! add_post_meta( $booking_id, '_dlux_sms_skip_' . sanitize_key( $who ), md5( $why ), true ) ) {
		if ( get_post_meta( $booking_id, '_dlux_sms_skip_' . sanitize_key( $who ), true ) === md5( $why ) ) {
			return;
		}
		update_post_meta( $booking_id, '_dlux_sms_skip_' . sanitize_key( $who ), md5( $why ) );
	}
	dlux_bk_sms_log( array( 'booking' => $booking_id, 'who' => $who, 'status' => 'skipped', 'error' => $why ) );
}

/** Why SMS is not being attempted at all (empty = ready). */
function dlux_bk_sms_problems() {
	$p  = array();
	$on = dlux_bk_opt( 'sms_on', 0 );
	if ( ! $on || '0' === $on ) {
		$p[] = '"Send booking SMS" is switched off.';
	}
	foreach ( array( 'twilio_sid' => 'Account SID', 'twilio_token' => 'Auth token', 'twilio_from' => 'Send from' ) as $k => $label ) {
		if ( ! trim( (string) dlux_bk_opt( $k ) ) ) {
			$p[] = $label . ' is empty.';
		}
	}
	$sid = trim( (string) dlux_bk_opt( 'twilio_sid' ) );
	if ( $sid && 0 !== strpos( $sid, 'AC' ) ) {
		$p[] = 'Account SID should start with "AC" (an API key SID starting with "SK" won’t work here).';
	}
	return $p;
}

/** Text the customer and the shop about a confirmed booking (each once). */
function dlux_bk_send_sms( $booking_id ) {
	if ( ! dlux_bk_sms_on() ) {
		$why = dlux_bk_sms_problems();
		dlux_bk_sms_log_skip( $booking_id, 'all', $why ? implode( ' ', $why ) : 'SMS is not set up.' );
		return;
	}
	$b = dlux_bk_booking( $booking_id );
	if ( ! $b ) {
		return;
	}
	$biz  = dlux_bk_business();
	$shop = 'unpaid' === $b['payment'];
	$tags = array(
		'{first_name}' => trim( (string) strtok( $b['name'], ' ' ) ),
		'{name}'       => $b['name'],
		'{business}'   => wp_specialchars_decode( $biz['name'], ENT_QUOTES ),
		'{when}'       => $b['when_slot'],
		'{package}'    => $b['items'] ? $b['items'][0]['name'] : $b['package'],
		'{option}'     => $b['option'] ? ' – ' . $b['option'] : '',
		'{size}'       => $b['cls'],
		'{extras}'     => implode( ', ', wp_list_pluck( array_slice( $b['items'], 1 ), 'name' ) ),
		'{total}'      => dlux_bk_money( $b['total'] ),
		'{payment}'    => $shop ? 'pay at the shop' : 'paid',
		'{rego}'       => $b['rego'],
		'{phone}'      => $b['phone'],
		'{email}'      => $b['email'],
		'{shop_phone}' => $biz['phone'],
		'{address}'    => $biz['address'],
	);
	$fill = function ( $tpl ) use ( $tags ) {
		return trim( preg_replace( '/[ \t]{2,}/', ' ', strtr( (string) $tpl, $tags ) ) );
	};
	$errors = array();

	// Customer.
	if ( ! $b['phone'] ) {
		dlux_bk_sms_log_skip( $booking_id, 'customer', 'The booking has no mobile number.' );
	}
	if ( $b['phone'] && ! get_post_meta( $booking_id, '_dlux_sms_customer', true ) ) {
		$tpl = (string) dlux_bk_opt( 'sms_customer', '' );
		$r   = dlux_bk_sms_send( $b['phone'], $fill( '' !== trim( $tpl ) ? $tpl : dlux_bk_sms_default( 'customer' ) ), $booking_id, 'customer' );
		if ( true === $r ) {
			update_post_meta( $booking_id, '_dlux_sms_customer', time() );
		} else {
			$errors[] = 'Customer: ' . $r;
		}
	}

	// Shop.
	$to = array_filter( array_map( 'trim', explode( ',', (string) dlux_bk_opt( 'sms_admin_to', '' ) ) ) );
	if ( ! $to ) {
		dlux_bk_sms_log_skip( $booking_id, 'shop', '"Send shop SMS to" is empty.' );
	}
	if ( $to && ! get_post_meta( $booking_id, '_dlux_sms_admin', true ) ) {
		$tpl  = (string) dlux_bk_opt( 'sms_admin', '' );
		$body = $fill( '' !== trim( $tpl ) ? $tpl : dlux_bk_sms_default( 'admin' ) );
		$ok   = false;
		foreach ( $to as $n ) {
			$r = dlux_bk_sms_send( $n, $body, $booking_id, 'shop' );
			if ( true === $r ) {
				$ok = true;
			} else {
				$errors[] = 'Shop (' . $n . '): ' . $r;
			}
		}
		if ( $ok ) {
			update_post_meta( $booking_id, '_dlux_sms_admin', time() );
		}
	}

	if ( $errors ) {
		update_post_meta( $booking_id, '_dlux_sms_error', implode( ' | ', $errors ) );
	} else {
		delete_post_meta( $booking_id, '_dlux_sms_error' );
	}
}

/* ---------------- Admin: Bookings › SMS log ---------------- */

add_action(
	'admin_menu',
	function () {
		add_submenu_page( 'edit.php?post_type=dlux_booking', 'SMS log', 'SMS log', 'manage_options', 'dlux-bk-sms-log', 'dlux_bk_sms_log_page' );
	},
	20
);

/** Test SMS + clear log. */
add_action(
	'admin_post_dlux_bk_sms_tools',
	function () {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'dlux_bk_sms_tools' ) ) {
			wp_die( 'Not allowed.' );
		}
		$back = admin_url( 'edit.php?post_type=dlux_booking&page=dlux-bk-sms-log' );
		if ( ! empty( $_POST['clear'] ) ) {
			delete_option( 'dlux_bk_sms_log' );
			wp_safe_redirect( add_query_arg( 'done', 'cleared', $back ) );
			exit;
		}
		$to = sanitize_text_field( wp_unslash( $_POST['to'] ?? '' ) );
		$r  = dlux_bk_sms_send( $to, 'Test SMS from ' . wp_specialchars_decode( dlux_bk_business()['name'], ENT_QUOTES ) . ' booking system. If you got this, Twilio is working.', 0, 'test' );
		wp_safe_redirect( add_query_arg( 'done', true === $r ? 'test-ok' : 'test-failed', $back ) );
		exit;
	}
);

function dlux_bk_sms_log_page() {
	$log      = get_option( 'dlux_bk_sms_log', array() );
	$log      = is_array( $log ) ? $log : array();
	$problems = dlux_bk_sms_problems();
	$done     = isset( $_GET['done'] ) ? sanitize_key( wp_unslash( $_GET['done'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$colors   = array( 'sent' => '#15803d', 'failed' => '#b91c1c', 'skipped' => '#a16207' );
	?>
	<div class="wrap">
		<h1>SMS log</h1>
		<p>Every booking text (customer and shop), with Twilio’s reply. “Sent” means Twilio accepted it – if a sent message never arrives, look up its SID in <a href="https://console.twilio.com/us1/monitor/logs/sms" target="_blank" rel="noopener">Twilio › Monitor › Messaging logs</a> to see the delivery error.</p>

		<?php if ( 'test-ok' === $done ) : ?>
			<div class="notice notice-success"><p>Test SMS accepted by Twilio – see the first line below.</p></div>
		<?php elseif ( 'test-failed' === $done ) : ?>
			<div class="notice notice-error"><p>Test SMS failed – the error is in the first line below.</p></div>
		<?php elseif ( 'cleared' === $done ) : ?>
			<div class="notice notice-success"><p>Log cleared.</p></div>
		<?php endif; ?>

		<?php if ( $problems ) : ?>
			<div class="notice notice-warning inline"><p><strong>SMS won’t be sent for bookings yet:</strong><br><?php echo wp_kses( implode( '<br>', array_map( 'esc_html', $problems ) ), array( 'br' => array() ) ); ?><br>Fix this in Settings › SMS (Twilio).</p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin:16px 0;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
			<input type="hidden" name="action" value="dlux_bk_sms_tools">
			<?php wp_nonce_field( 'dlux_bk_sms_tools' ); ?>
			<input type="text" name="to" placeholder="Mobile number, e.g. 0410 111 190" class="regular-text">
			<button class="button button-primary" type="submit">Send test SMS</button>
			<button class="button" type="submit" name="clear" value="1" onclick="return confirm('Clear the SMS log?')">Clear log</button>
		</form>

		<table class="widefat striped">
			<thead><tr><th>Time</th><th>Booking</th><th>To</th><th>Status</th><th>Details</th><th>Message</th></tr></thead>
			<tbody>
			<?php if ( ! $log ) : ?>
				<tr><td colspan="6">Nothing logged yet. Make a booking or send a test SMS.</td></tr>
			<?php endif; ?>
			<?php foreach ( $log as $e ) : ?>
				<tr>
					<td><?php echo esc_html( wp_date( 'j M Y, g:i:s a', (int) ( $e['time'] ?? 0 ) ) ); ?></td>
					<td>
						<?php if ( ! empty( $e['booking'] ) ) : ?>
							<a href="<?php echo esc_url( get_edit_post_link( (int) $e['booking'] ) ); ?>">#<?php echo (int) $e['booking']; ?></a><br>
						<?php endif; ?>
						<small><?php echo esc_html( $e['who'] ?? '' ); ?></small>
					</td>
					<td><?php echo esc_html( $e['to'] ?? '' ); ?><?php if ( ! empty( $e['from'] ) ) : ?><br><small>from <?php echo esc_html( $e['from'] ); ?></small><?php endif; ?></td>
					<td><strong style="color:<?php echo esc_attr( $colors[ $e['status'] ?? '' ] ?? '#333' ); ?>"><?php echo esc_html( ucfirst( (string) ( $e['status'] ?? '' ) ) ); ?></strong>
						<?php if ( ! empty( $e['http'] ) ) : ?><br><small>HTTP <?php echo (int) $e['http']; ?></small><?php endif; ?></td>
					<td>
						<?php if ( ! empty( $e['sid'] ) ) : ?>SID: <code><?php echo esc_html( $e['sid'] ); ?></code><br><?php endif; ?>
						<?php if ( ! empty( $e['twilio'] ) ) : ?>Twilio status: <?php echo esc_html( $e['twilio'] ); ?><br><?php endif; ?>
						<?php if ( ! empty( $e['error'] ) ) : ?><span style="color:#b91c1c"><?php echo esc_html( $e['error'] ); ?></span><br><?php endif; ?>
						<?php if ( ! empty( $e['error_code'] ) ) : ?><a href="<?php echo esc_url( ! empty( $e['more_info'] ) ? $e['more_info'] : 'https://www.twilio.com/docs/api/errors/' . rawurlencode( $e['error_code'] ) ); ?>" target="_blank" rel="noopener">What does error <?php echo esc_html( $e['error_code'] ); ?> mean?</a><?php endif; ?>
					</td>
					<td style="max-width:320px"><small><?php echo esc_html( $e['body'] ?? '' ); ?></small></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}

