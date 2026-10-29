<?php
/**
 * Plugin Name:       Dlux Booking
 * Description:       Online wash and detailing bookings for Dlux Hand Car Wash & Detailing. Prices are set on the Booking Prices page, payments go through Stripe Checkout, and bookings are stored in WordPress.
 * Version:           5.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Dlux Hand Car Wash & Detailing
 * License:           Proprietary
 *
 * Requires Secure Custom Fields (SCF) or ACF Pro.
 *
 * Shortcodes:
 *   [dlux_booking]       Booking page: one-time wash, paid by card through Stripe Checkout
 *   [dlux_menu]          Price list (from the Booking Prices page)
 *   [dlux_confirmation]  "You're booked in" page (Stripe returns customers here)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DLUX_BOOKING_VER', '5.1.0' );
define( 'DLUX_BOOKING_FILE', __FILE__ );
define( 'DLUX_BOOKING_DIR', plugin_dir_path( __FILE__ ) );

require DLUX_BOOKING_DIR . 'includes/settings.php';
require DLUX_BOOKING_DIR . 'includes/cpt.php';
require DLUX_BOOKING_DIR . 'includes/prices.php';
require DLUX_BOOKING_DIR . 'includes/availability.php';
require DLUX_BOOKING_DIR . 'includes/stripe.php';
require DLUX_BOOKING_DIR . 'includes/email-template.php';
require DLUX_BOOKING_DIR . 'includes/sms.php';
require DLUX_BOOKING_DIR . 'includes/emails.php';
require DLUX_BOOKING_DIR . 'includes/rest.php';
require DLUX_BOOKING_DIR . 'includes/app-api.php';
require DLUX_BOOKING_DIR . 'includes/admin.php';
require DLUX_BOOKING_DIR . 'includes/frontend.php';
require DLUX_BOOKING_DIR . 'includes/voucher.php';

/* ---------------- Install / upgrade ---------------- */

/**
 * Runs on activation and whenever the plugin version changes
 * (uploading a new zip over the old one does not fire the activation hook).
 */
function dlux_bk_install() {
	foreach ( array( 'administrator', 'editor' ) as $role_name ) {
		$role = get_role( $role_name );
		if ( ! $role ) {
			continue;
		}
		foreach ( dlux_bk_caps() as $cap ) {
			$role->add_cap( $cap );
		}
	}

	if ( ! wp_next_scheduled( 'dlux_bk_cleanup' ) ) {
		wp_schedule_event( time() + 300, 'hourly', 'dlux_bk_cleanup' );
	}

	// Twilio was replaced by ClickSend: delete the old Twilio credentials (incl. the auth token).
	foreach ( array( 'twilio_sid', 'twilio_token', 'twilio_from', 'sms_country' ) as $opt ) {
		delete_option( 'options_dlux_bk_' . $opt );
		delete_option( '_options_dlux_bk_' . $opt );
	}

	// Settings from the old Supabase version are no longer used.
	delete_option( 'dlux_booking_settings' );

	update_option( 'dlux_bk_version', DLUX_BOOKING_VER, false );
}
register_activation_hook( __FILE__, 'dlux_bk_install' );

add_action(
	'init',
	function () {
		if ( get_option( 'dlux_bk_version' ) !== DLUX_BOOKING_VER ) {
			dlux_bk_install();
		}
	},
	20
);

register_deactivation_hook(
	__FILE__,
	function () {
		wp_clear_scheduled_hook( 'dlux_bk_cleanup' );
		wp_clear_scheduled_hook( 'dlux_mail_check' ); // left over from 3.x
	}
);

/* ---------------- SCF / ACF missing ---------------- */

add_action(
	'admin_notices',
	function () {
		if ( function_exists( 'acf_add_local_field_group' ) || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>Dlux Booking:</strong> please install and activate Secure Custom Fields (or ACF Pro). Booking settings and booking details need it.</p></div>';
	}
);
