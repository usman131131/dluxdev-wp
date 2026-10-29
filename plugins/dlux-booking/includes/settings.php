<?php
/**
 * Booking settings.
 *
 * The fields are added as a "Booking & Payments" box on the theme's
 * Theme Settings options page (slug: theme-settings). Values are stored by
 * SCF/ACF in the wp_options table (option names start with options_dlux_bk_).
 *
 * If the Theme Settings page doesn't exist (e.g. another theme is active),
 * a "Booking Settings" page is added under Bookings instead.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DLUX_BK_THEME_OPTIONS', 'theme-settings' );
define( 'DLUX_BK_OWN_OPTIONS', 'dlux-booking-settings' );

/**
 * Read a booking setting.
 *
 * @param string $name    Setting name without the dlux_bk_ prefix.
 * @param mixed  $default Used when the setting is empty.
 */
function dlux_bk_opt( $name, $default = null ) {
	$key = 'dlux_bk_' . $name;
	if ( function_exists( 'get_field' ) ) {
		$v = get_field( $key, 'option' );
	} else {
		$v = get_option( 'options_' . $key, null );
	}
	return ( null === $v || '' === $v || array() === $v ) ? $default : $v;
}

/**
 * Rows of a repeater setting. Always an array of arrays: an empty repeater
 * saved in the admin comes back from SCF as false, which must mean "no rows".
 */
function dlux_bk_rows( $name ) {
	$v = dlux_bk_opt( $name, array() );
	return is_array( $v ) ? array_values( array_filter( $v, 'is_array' ) ) : array();
}

/** Admin URL of the page that holds the booking settings. */
function dlux_bk_settings_url() {
	$slug = dlux_bk_theme_options_exist() ? DLUX_BK_THEME_OPTIONS : DLUX_BK_OWN_OPTIONS;
	return admin_url( 'admin.php?page=' . $slug );
}

function dlux_bk_theme_options_exist() {
	return function_exists( 'acf_get_options_page' ) && acf_get_options_page( DLUX_BK_THEME_OPTIONS );
}

/* Fallback options page, only when the theme's Theme Settings page is missing. */
add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_options_sub_page' ) || dlux_bk_theme_options_exist() ) {
			return;
		}
		acf_add_options_sub_page(
			array(
				'page_title'  => 'Booking Settings',
				'menu_title'  => 'Settings',
				'menu_slug'   => DLUX_BK_OWN_OPTIONS,
				'parent_slug' => 'edit.php?post_type=dlux_booking',
				'capability'  => 'manage_options',
			)
		);
	},
	100 // After the theme registers Theme Settings (priority 99).
);

/* The field group. */
add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		$webhook = rest_url( 'dlux/v1/stripe-webhook' );
		$k       = function ( $name ) {
			return 'field_dlux_bk_' . $name;
		};
		$days    = array(
			'1' => 'Mon',
			'2' => 'Tue',
			'3' => 'Wed',
			'4' => 'Thu',
			'5' => 'Fri',
			'6' => 'Sat',
			'7' => 'Sun',
		);

		acf_add_local_field_group(
			array(
				'key'        => 'group_dlux_bk_settings',
				'title'      => 'Booking & Payments',
				'menu_order' => 50,
				'style'      => 'default',
				'location'   => array(
					array( array( 'param' => 'options_page', 'operator' => '==', 'value' => DLUX_BK_THEME_OPTIONS ) ),
					array( array( 'param' => 'options_page', 'operator' => '==', 'value' => DLUX_BK_OWN_OPTIONS ) ),
				),
				'fields'     => array(

					/* ---------- Stripe ---------- */
					array( 'key' => $k( 'tab_stripe' ), 'label' => 'Stripe', 'type' => 'tab', 'placement' => 'top' ),
					array(
						'key'           => $k( 'stripe_mode' ),
						'name'          => 'dlux_bk_stripe_mode',
						'label'         => 'Mode',
						'type'          => 'button_group',
						'choices'       => array( 'test' => 'Test mode', 'live' => 'Live mode' ),
						'default_value' => 'test',
						'instructions'  => 'Use Test mode while setting up (card 4242 4242 4242 4242, any future date, any CVC). Switch to Live mode to take real payments.',
					),
					array(
						'key'          => $k( 'stripe_test_secret' ),
						'name'         => 'dlux_bk_stripe_test_secret',
						'label'        => 'Test secret key',
						'type'         => 'password',
						'instructions' => 'Stripe › Developers › API keys (Test mode). Starts with sk_test_ (or rk_test_ for a restricted key).',
						'wrapper'      => array( 'width' => '50' ),
					),
					array(
						'key'          => $k( 'stripe_test_webhook' ),
						'name'         => 'dlux_bk_stripe_test_webhook',
						'label'        => 'Test webhook signing secret',
						'type'         => 'password',
						'instructions' => 'Starts with whsec_. See the webhook URL below.',
						'wrapper'      => array( 'width' => '50' ),
					),
					array(
						'key'          => $k( 'stripe_live_secret' ),
						'name'         => 'dlux_bk_stripe_live_secret',
						'label'        => 'Live secret key',
						'type'         => 'password',
						'instructions' => 'Starts with sk_live_ (or rk_live_).',
						'wrapper'      => array( 'width' => '50' ),
					),
					array(
						'key'          => $k( 'stripe_live_webhook' ),
						'name'         => 'dlux_bk_stripe_live_webhook',
						'label'        => 'Live webhook signing secret',
						'type'         => 'password',
						'instructions' => 'Starts with whsec_.',
						'wrapper'      => array( 'width' => '50' ),
					),
					array(
						'key'           => $k( 'currency' ),
						'name'          => 'dlux_bk_currency',
						'label'         => 'Currency',
						'type'          => 'text',
						'default_value' => 'aud',
						'placeholder'   => 'aud',
						'maxlength'     => 3,
						'wrapper'       => array( 'width' => '25' ),
					),
					array(
						'key'     => $k( 'webhook_info' ),
						'label'   => 'Webhook URL',
						'type'    => 'message',
						'message' => 'In Stripe › Developers › Webhooks, add an endpoint with this URL:<br><code>' . esc_html( $webhook ) . '</code><br><br>Events to send: <code>checkout.session.completed</code>, <code>checkout.session.async_payment_succeeded</code>, <code>checkout.session.async_payment_failed</code>, <code>checkout.session.expired</code>, <code>charge.refunded</code>.<br>Do this once in Test mode and once in Live mode; each gives its own signing secret.',
						'wrapper' => array( 'width' => '75' ),
					),

					/* ---------- Booking ---------- */
					array( 'key' => $k( 'tab_booking' ), 'label' => 'Booking', 'type' => 'tab', 'placement' => 'top' ),
					array(
						'key'           => $k( 'booking_page' ),
						'name'          => 'dlux_bk_booking_page',
						'label'         => 'Booking page',
						'type'          => 'post_object',
						'post_type'     => array( 'page' ),
						'return_format' => 'id',
						'allow_null'    => 1,
						'instructions'  => 'The page with the [dlux_booking] shortcode.',
						'wrapper'       => array( 'width' => '50' ),
					),
					array(
						'key'           => $k( 'confirm_page' ),
						'name'          => 'dlux_bk_confirm_page',
						'label'         => 'Confirmation page',
						'type'          => 'post_object',
						'post_type'     => array( 'page' ),
						'return_format' => 'id',
						'allow_null'    => 1,
						'instructions'  => 'The page with the [dlux_confirmation] shortcode. Stripe sends customers here after paying.',
						'wrapper'       => array( 'width' => '50' ),
					),
					array(
						'key'     => $k( 'prices_info' ),
						'label'   => 'Packages & prices',
						'type'    => 'message',
						'message' => 'Wash packages, detailing packages and extras are set on the <a href="' . esc_url( admin_url( 'admin.php?page=dlux-prices' ) ) . '">Booking Prices</a> page.',
					),
					array(
						'key'           => $k( 'pay_at_shop' ),
						'name'          => 'dlux_bk_pay_at_shop',
						'label'         => 'Pay at the shop',
						'type'          => 'true_false',
						'ui'            => 1,
						'default_value' => 1,
						'message'       => 'Let customers choose to pay at the shop instead of paying online by card.',
						'instructions'  => 'Bookings paid at the shop are confirmed straight away and marked "Pay at shop".',
					),
					array(
						'key'           => $k( 'slot_length' ),
						'name'          => 'dlux_bk_slot_length',
						'label'         => 'Time slot length (minutes)',
						'type'          => 'number',
						'default_value' => 60,
						'min'           => 10,
						'max'           => 240,
						'step'          => 5,
						'wrapper'       => array( 'width' => '25' ),
					),
					array(
						'key'           => $k( 'bays' ),
						'name'          => 'dlux_bk_bays',
						'label'         => 'Cars per time slot',
						'type'          => 'number',
						'default_value' => 1,
						'min'           => 1,
						'max'           => 50,
						'instructions'  => 'How many bookings one time slot can take.',
						'wrapper'       => array( 'width' => '25' ),
					),
					array(
						'key'           => $k( 'days_ahead' ),
						'name'          => 'dlux_bk_days_ahead',
						'label'         => 'Book up to (days ahead)',
						'type'          => 'number',
						'default_value' => 30,
						'min'           => 1,
						'max'           => 90,
						'wrapper'       => array( 'width' => '25' ),
					),
					array(
						'key'           => $k( 'lead_time' ),
						'name'          => 'dlux_bk_lead_time',
						'label'         => 'Minimum notice (minutes)',
						'type'          => 'number',
						'default_value' => 60,
						'min'           => 0,
						'instructions'  => 'Same-day times sooner than this are hidden.',
						'wrapper'       => array( 'width' => '25' ),
					),
					array(
						'key'          => $k( 'hours' ),
						'name'         => 'dlux_bk_hours',
						'label'        => 'Online booking hours',
						'type'         => 'repeater',
						'layout'       => 'table',
						'button_label' => 'Add hours',
						'instructions' => 'Days not listed are closed for online booking. Leave empty for 9am–5pm every day. The last time offered is one slot before closing.',
						'sub_fields'   => array(
							array(
								'key'     => $k( 'hours_days' ),
								'name'    => 'days',
								'label'   => 'Days',
								'type'    => 'checkbox',
								'choices' => $days,
								'layout'  => 'horizontal',
							),
							array(
								'key'            => $k( 'hours_open' ),
								'name'           => 'open',
								'label'          => 'Open',
								'type'           => 'time_picker',
								'display_format' => 'g:i a',
								'return_format'  => 'H:i',
							),
							array(
								'key'            => $k( 'hours_close' ),
								'name'           => 'close',
								'label'          => 'Close',
								'type'           => 'time_picker',
								'display_format' => 'g:i a',
								'return_format'  => 'H:i',
							),
						),
					),
					array(
						'key'          => $k( 'closed_dates' ),
						'name'         => 'dlux_bk_closed_dates',
						'label'        => 'Closed dates',
						'type'         => 'repeater',
						'layout'       => 'table',
						'button_label' => 'Add closed date',
						'instructions' => 'Public holidays, staff days, etc.',
						'sub_fields'   => array(
							array(
								'key'            => $k( 'closed_date' ),
								'name'           => 'date',
								'label'          => 'Date',
								'type'           => 'date_picker',
								'display_format' => 'D j M Y',
								'return_format'  => 'Y-m-d',
								'first_day'      => 1,
							),
							array(
								'key'   => $k( 'closed_note' ),
								'name'  => 'note',
								'label' => 'Note (admin only)',
								'type'  => 'text',
							),
						),
					),
					/* ---------- Business & emails ---------- */
					array( 'key' => $k( 'tab_business' ), 'label' => 'Business & emails', 'type' => 'tab', 'placement' => 'top' ),
					array(
						'key'          => $k( 'business_name' ),
						'name'         => 'dlux_bk_business_name',
						'label'        => 'Business name',
						'type'         => 'text',
						'placeholder'  => get_bloginfo( 'name' ),
						'instructions' => 'Shown on the Stripe payment page, confirmation page and emails.',
						'wrapper'      => array( 'width' => '50' ),
					),
					array(
						'key'     => $k( 'business_phone' ),
						'name'    => 'dlux_bk_business_phone',
						'label'   => 'Phone',
						'type'    => 'text',
						'wrapper' => array( 'width' => '50' ),
					),
					array(
						'key'          => $k( 'business_address' ),
						'name'         => 'dlux_bk_business_address',
						'label'        => 'Address',
						'type'         => 'text',
						'instructions' => 'Used for "Get directions" and the calendar event.',
					),
					array(
						'key'          => $k( 'notify_email' ),
						'name'         => 'dlux_bk_notify_email',
						'label'        => 'Send new booking emails to',
						'type'         => 'text',
						'placeholder'  => get_option( 'admin_email' ),
						'instructions' => 'One or more addresses, separated by commas. Leave empty for the site admin email.',
					),

					/* ---------- SMS (ClickSend) ---------- */
					array( 'key' => $k( 'tab_sms' ), 'label' => 'SMS (ClickSend)', 'type' => 'tab', 'placement' => 'top' ),
					array(
						'key'          => $k( 'sms_on' ),
						'name'         => 'dlux_bk_sms_on',
						'label'        => 'Send booking SMS',
						'type'         => 'true_false',
						'ui'           => 1,
						'message'      => 'Text the customer and the shop when a booking is confirmed.',
						'instructions' => 'Sent once per booking: when a card payment goes through, or straight away for pay-at-the-shop bookings. Every attempt is listed in Bookings › SMS log.',
					),
					array(
						'key'          => $k( 'clicksend_username' ),
						'name'         => 'dlux_bk_clicksend_username',
						'label'        => 'ClickSend username',
						'type'         => 'text',
						'instructions' => 'ClickSend dashboard › Developers › API Credentials (usually your login email).',
						'wrapper'      => array( 'width' => '50' ),
					),
					array(
						'key'          => $k( 'clicksend_key' ),
						'name'         => 'dlux_bk_clicksend_key',
						'label'        => 'API key',
						'type'         => 'password',
						'instructions' => 'ClickSend dashboard › Developers › API Credentials. Kept on your server only.',
						'wrapper'      => array( 'width' => '50' ),
					),
					array(
						'key'          => $k( 'sms_from' ),
						'name'         => 'dlux_bk_sms_from',
						'label'        => 'Send from (optional)',
						'type'         => 'text',
						'placeholder'  => 'DLUXWASH or +61…',
						'instructions' => 'Leave empty to use ClickSend’s shared number. Or enter your dedicated ClickSend number, or a sender ID up to 11 letters (in Australia sender IDs must be registered first).',
						'wrapper'      => array( 'width' => '50' ),
					),
					array(
						'key'          => $k( 'sms_admin_to' ),
						'name'         => 'dlux_bk_sms_admin_to',
						'label'        => 'Send shop SMS to',
						'type'         => 'text',
						'placeholder'  => '0410 111 190',
						'instructions' => 'One or more mobile numbers, separated by commas. Leave empty to only text the customer.',
						'wrapper'      => array( 'width' => '50' ),
					),
					array(
						'key'           => $k( 'sms_customer' ),
						'name'          => 'dlux_bk_sms_customer',
						'label'         => 'Customer message',
						'type'          => 'textarea',
						'rows'          => 3,
						'new_lines'     => '',
						'default_value' => dlux_bk_sms_default( 'customer' ),
						'instructions'  => 'Tags: {first_name} {name} {business} {when} {package} {option} {size} {extras} {total} {payment} {rego} {shop_phone} {address}',
						'wrapper'       => array( 'width' => '50' ),
					),
					array(
						'key'           => $k( 'sms_admin' ),
						'name'          => 'dlux_bk_sms_admin',
						'label'         => 'Shop message',
						'type'          => 'textarea',
						'rows'          => 3,
						'new_lines'     => '',
						'default_value' => dlux_bk_sms_default( 'admin' ),
						'instructions'  => 'Same tags, plus {phone} and {email} (the customer\'s).',
						'wrapper'       => array( 'width' => '50' ),
					),
				),
			)
		);
	}
);

/**
 * A phone number in international format (+61410111190), for tel:/sms:/WhatsApp links.
 * Local numbers get the country code (filter: dlux_booking_phone_country, default +61).
 */
function dlux_bk_phone_e164( $raw ) {
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
	$cc = '+' . ltrim( preg_replace( '/[^0-9]/', '', (string) apply_filters( 'dlux_booking_phone_country', '+61' ) ), '+' );
	return $cc . ltrim( $n, '0' );
}

/** Settings with defaults applied. */
function dlux_bk_business() {
	return array(
		'name'    => (string) dlux_bk_opt( 'business_name', get_bloginfo( 'name' ) ),
		'address' => (string) dlux_bk_opt( 'business_address', '' ),
		'phone'   => (string) dlux_bk_opt( 'business_phone', '' ),
	);
}

/** Can customers choose to pay at the shop? (On unless switched off.) */
function dlux_bk_pay_shop_on() {
	$v = dlux_bk_opt( 'pay_at_shop', 1 );
	return ! ( false === $v || 0 === $v || '0' === $v );
}

function dlux_bk_page_url( $which ) {
	$id = (int) dlux_bk_opt( $which . '_page', 0 );
	return $id ? get_permalink( $id ) : '';
}

function dlux_bk_currency() {
	$c = strtolower( preg_replace( '/[^a-z]/i', '', (string) dlux_bk_opt( 'currency', 'aud' ) ) );
	return 3 === strlen( $c ) ? $c : 'aud';
}
