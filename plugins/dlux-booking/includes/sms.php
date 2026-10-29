<?php
/**
 * Booking SMS via ClickSend: one text to the customer, one to the shop.
 * Sent with the confirmation emails (card payment confirmed, or a pay-at-the-shop booking).
 * Settings: Theme Settings › Booking & Payments › SMS (ClickSend). Log: Bookings › SMS log.
 *
 * API: POST https://rest.clicksend.com/v3/sms/send (HTTP Basic auth: username + API key).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dlux_bk_sms_default( $who ) {
	return 'admin' === $who
		? 'New booking: {package}{option} ({size}) – {when}. {name}, {phone}, rego {rego}. {total} ({payment}).'
		: 'Hi {first_name}, your {business} booking is confirmed: {package}{option} ({size}) on {when}. Total {total} ({payment}). Questions? Call {shop_phone}.';
}

/** Switched on and has credentials. */
function dlux_bk_sms_on() {
	$on = dlux_bk_opt( 'sms_on', 0 );
	return $on && '0' !== $on && trim( (string) dlux_bk_opt( 'clicksend_username' ) ) && trim( (string) dlux_bk_opt( 'clicksend_key' ) );
}

/** "0410 111 190" → "+61410111190". */
function dlux_bk_sms_number( $raw ) {
	return dlux_bk_phone_e164( $raw );
}

/** Send one SMS. Returns true or an error message. Every attempt is written to the SMS log. */
function dlux_bk_sms_send( $to, $body, $booking_id = 0, $who = '' ) {
	$raw  = $to;
	$user = trim( (string) dlux_bk_opt( 'clicksend_username' ) );
	$key  = trim( (string) dlux_bk_opt( 'clicksend_key' ) );
	$from = trim( (string) dlux_bk_opt( 'sms_from' ) );
	$to   = dlux_bk_phone_e164( $to );
	$body = mb_substr( (string) $body, 0, 1000 );

	if ( ! $user || ! $key || ! $to ) {
		$missing = array_keys( array_filter( array( 'ClickSend username' => ! $user, 'API key' => ! $key, 'phone number' => ! $to ) ) );
		$msg     = 'Not sent – missing ' . implode( ', ', $missing ) . '.';
		dlux_bk_sms_log( array( 'booking' => $booking_id, 'who' => $who, 'to' => $to ? $to : (string) $raw, 'status' => 'failed', 'error' => $msg, 'body' => $body ) );
		return $msg;
	}

	$message = array( 'source' => 'dlux-booking', 'to' => $to, 'body' => $body );
	if ( '' !== $from ) {
		// A number (dedicated ClickSend number) or a registered alphanumeric sender ID (max 11 characters).
		$message['from'] = preg_match( '/^[+0-9\s()-]+$/', $from ) ? dlux_bk_phone_e164( $from ) : mb_substr( $from, 0, 11 );
	}

	$res = wp_remote_post(
		'https://rest.clicksend.com/v3/sms/send',
		array(
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $user . ':' . $key ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
			),
			'body'    => wp_json_encode( array( 'messages' => array( $message ) ) ),
		)
	);
	$entry = array(
		'booking' => $booking_id,
		'who'     => $who,
		'to'      => $to,
		'from'    => $message['from'] ?? 'ClickSend shared number',
		'body'    => $body,
	);

	if ( is_wp_error( $res ) ) {
		$msg = 'Could not reach ClickSend: ' . $res->get_error_message();
		dlux_bk_sms_log( $entry + array( 'status' => 'failed', 'error' => $msg ) );
		return $msg;
	}

	$code = (int) wp_remote_retrieve_response_code( $res );
	$data = json_decode( wp_remote_retrieve_body( $res ), true );
	$data = is_array( $data ) ? $data : array();
	$m    = $data['data']['messages'][0] ?? array();
	$mst  = strtoupper( (string) ( $m['status'] ?? '' ) );

	if ( 200 === $code && 'SUCCESS' === $mst ) {
		// Accepted by ClickSend. Delivery can still fail later – look up the message ID in ClickSend › Messaging › SMS › History.
		dlux_bk_sms_log(
			$entry + array(
				'status'     => 'sent',
				'http'       => $code,
				'message_id' => (string) ( $m['message_id'] ?? '' ),
				'provider'   => $mst,
				'price'      => isset( $m['message_price'] ) ? (string) $m['message_price'] : '',
				'parts'      => isset( $m['message_parts'] ) ? (int) $m['message_parts'] : 0,
			)
		);
		return true;
	}

	if ( 401 === $code ) {
		$msg = 'ClickSend rejected the login – check the username and API key (Dashboard › Developers › API Credentials).';
	} elseif ( $mst && 'SUCCESS' !== $mst ) {
		$msg = 'ClickSend could not send this message: ' . $mst . ' – ' . dlux_bk_clicksend_explain( $mst );
	} else {
		$msg = 'ClickSend error ' . $code
			. ( ! empty( $data['response_code'] ) ? ' (' . $data['response_code'] . ')' : '' )
			. ( ! empty( $data['response_msg'] ) ? ': ' . $data['response_msg'] : '' );
	}
	dlux_bk_sms_log(
		$entry + array(
			'status'   => 'failed',
			'http'     => $code,
			'provider' => $mst ? $mst : (string) ( $data['response_code'] ?? '' ),
			'error'    => $msg,
		)
	);
	return $msg;
}

/** Plain-English meaning of ClickSend message statuses. */
function dlux_bk_clicksend_explain( $status ) {
	$map = array(
		'INSUFFICIENT_CREDIT'    => 'the ClickSend account is out of credit. Top up in ClickSend › Billing.',
		'INVALID_RECIPIENT'      => 'the mobile number is not valid.',
		'COUNTRY_NOT_ENABLED'    => 'sending to this country is not enabled on the ClickSend account.',
		'INVALID_SENDER_ID'      => 'the "Send from" sender ID is not allowed. Leave it empty or use a registered sender ID / your ClickSend number.',
		'ALPHA_TAG_NOT_ALLOWED'  => 'alphanumeric sender IDs are not allowed for this number. Leave "Send from" empty or use your ClickSend number.',
		'SENDER_ID_NOT_VERIFIED' => 'the sender ID has not been verified in ClickSend yet.',
		'DUPLICATED_MESSAGE'     => 'the same message was sent to this number moments ago.',
		'EMPTY_MESSAGE'          => 'the message text is empty.',
		'TOO_MANY_RECIPIENTS'    => 'too many recipients in one request.',
	);
	return $map[ $status ] ?? 'see ClickSend › Messaging › SMS › History for details.';
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
	$key = '_dlux_sms_skip_' . sanitize_key( $who );
	if ( $booking_id && get_post_meta( $booking_id, $key, true ) === md5( $why ) ) {
		return;
	}
	if ( $booking_id ) {
		update_post_meta( $booking_id, $key, md5( $why ) );
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
	foreach ( array( 'clicksend_username' => 'ClickSend username', 'clicksend_key' => 'API key' ) as $k => $label ) {
		if ( ! trim( (string) dlux_bk_opt( $k ) ) ) {
			$p[] = $label . ' is empty.';
		}
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
		$r  = dlux_bk_sms_send( $to, 'Test SMS from the ' . wp_specialchars_decode( dlux_bk_business()['name'], ENT_QUOTES ) . ' booking system. If you got this, ClickSend is working.', 0, 'test' );
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
	$settings = function_exists( 'dlux_bk_settings_url' ) ? dlux_bk_settings_url() : admin_url();
	?>
	<div class="wrap">
		<h1>SMS log</h1>
		<p>Every booking text (customer and shop) with ClickSend’s reply. “Sent” means ClickSend accepted it – if a sent message never arrives, look up its message ID in <a href="https://dashboard.clicksend.com/messaging-history/sms/outbound" target="_blank" rel="noopener">ClickSend › Messaging › SMS › History</a> to see the delivery status.</p>

		<?php if ( 'test-ok' === $done ) : ?>
			<div class="notice notice-success"><p>Test SMS accepted by ClickSend – see the first line below.</p></div>
		<?php elseif ( 'test-failed' === $done ) : ?>
			<div class="notice notice-error"><p>Test SMS failed – the reason is in the first line below.</p></div>
		<?php elseif ( 'cleared' === $done ) : ?>
			<div class="notice notice-success"><p>Log cleared.</p></div>
		<?php endif; ?>

		<?php if ( $problems ) : ?>
			<div class="notice notice-warning inline"><p><strong>SMS won’t be sent for bookings yet:</strong><br><?php echo wp_kses( implode( '<br>', array_map( 'esc_html', $problems ) ), array( 'br' => array() ) ); ?><br>Fix this in <a href="<?php echo esc_url( $settings ); ?>">Theme Settings › SMS (ClickSend)</a>.</p></div>
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
							<a href="<?php echo esc_url( (string) get_edit_post_link( (int) $e['booking'] ) ); ?>">#<?php echo (int) $e['booking']; ?></a><br>
						<?php endif; ?>
						<small><?php echo esc_html( $e['who'] ?? '' ); ?></small>
					</td>
					<td><?php echo esc_html( $e['to'] ?? '' ); ?><?php if ( ! empty( $e['from'] ) ) : ?><br><small>from <?php echo esc_html( $e['from'] ); ?></small><?php endif; ?></td>
					<td><strong style="color:<?php echo esc_attr( $colors[ $e['status'] ?? '' ] ?? '#333' ); ?>"><?php echo esc_html( ucfirst( (string) ( $e['status'] ?? '' ) ) ); ?></strong>
						<?php if ( ! empty( $e['http'] ) ) : ?><br><small>HTTP <?php echo (int) $e['http']; ?></small><?php endif; ?></td>
					<td>
						<?php if ( ! empty( $e['message_id'] ) ) : ?>Message ID: <code><?php echo esc_html( $e['message_id'] ); ?></code><br><?php endif; ?>
						<?php if ( ! empty( $e['provider'] ) ) : ?>ClickSend status: <?php echo esc_html( $e['provider'] ); ?><br><?php endif; ?>
						<?php if ( ! empty( $e['parts'] ) ) : ?><small><?php echo (int) $e['parts']; ?> part<?php echo 1 === (int) $e['parts'] ? '' : 's'; ?><?php echo ! empty( $e['price'] ) ? ' · cost ' . esc_html( $e['price'] ) : ''; ?></small><br><?php endif; ?>
						<?php if ( ! empty( $e['error'] ) ) : ?><span style="color:#b91c1c"><?php echo esc_html( $e['error'] ); ?></span><?php endif; ?>
					</td>
					<td style="max-width:320px"><small><?php echo esc_html( $e['body'] ?? '' ); ?></small></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php
}
