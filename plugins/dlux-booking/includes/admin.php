<?php
/**
 * Bookings › Prices & setup (read-only check page) and the Settings link.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'admin_menu',
	function () {
		$parent = 'edit.php?post_type=dlux_booking';
		add_submenu_page( $parent, 'Prices & setup', 'Prices & setup', 'manage_options', 'dlux-bk-setup', 'dlux_bk_setup_page' );
		if ( dlux_bk_theme_options_exist() ) {
			add_submenu_page( $parent, 'Booking settings', 'Settings', 'manage_options', 'admin.php?page=' . DLUX_BK_THEME_OPTIONS );
		}
	},
	1000
);

function dlux_bk_setup_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$ok   = function ( $yes, $good, $bad ) {
		return $yes
			? '<span style="color:#13612a">&#10003; ' . wp_kses_post( $good ) . '</span>'
			: '<span style="color:#8a1f1f">&#10007; ' . wp_kses_post( $bad ) . '</span>';
	};
	$mode     = dlux_bk_stripe_mode();
	$secret   = dlux_bk_stripe_secret();
	$whsec    = trim( (string) dlux_bk_opt( 'stripe_' . $mode . '_webhook', '' ) );
	$prefix   = 'live' === $mode ? array( 'sk_live_', 'rk_live_' ) : array( 'sk_test_', 'rk_test_' );
	$key_ok   = $secret && ( 0 === strpos( $secret, $prefix[0] ) || 0 === strpos( $secret, $prefix[1] ) );
	$book     = dlux_bk_page_url( 'booking' );
	$confirm  = dlux_bk_page_url( 'confirm' );
	$menu     = dlux_bk_menu();
	$c        = dlux_bk_config();
	$settings = dlux_bk_settings_url();

	echo '<div class="wrap"><h1>Prices &amp; setup</h1>';
	echo '<p>A read-only check of what the booking page uses. Change settings in <a href="' . esc_url( $settings ) . '">Booking &amp; Payments settings</a>.</p>';

	echo '<h2>Checklist</h2><table class="widefat striped" style="max-width:900px"><tbody>';
	echo '<tr><th style="width:220px">SCF / ACF</th><td>' . $ok( function_exists( 'get_field' ), 'Active', 'Not active. Install Secure Custom Fields or ACF Pro.' ) . '</td></tr>'; // phpcs:ignore
	echo '<tr><th>Stripe mode</th><td><b>' . esc_html( ucfirst( $mode ) ) . '</b>' . ( 'test' === $mode ? ' <span class="description">(no real money is taken)</span>' : '' ) . '</td></tr>';
	echo '<tr><th>Stripe secret key</th><td>' . $ok( $key_ok, 'Set', $secret ? 'Doesn\'t look like a ' . esc_html( $mode ) . ' key (should start with ' . esc_html( $prefix[0] ) . ')' : 'Missing' ) . '</td></tr>'; // phpcs:ignore
	echo '<tr><th>Webhook signing secret</th><td>' . $ok( 0 === strpos( $whsec, 'whsec_' ), 'Set', 'Missing. Payments still work, but bookings are confirmed only when the customer reaches the confirmation page or by the hourly check.' ) . '<br><code>' . esc_html( rest_url( 'dlux/v1/stripe-webhook' ) ) . '</code></td></tr>'; // phpcs:ignore
	echo '<tr><th>Booking page</th><td>' . $ok( (bool) $book, '<a href="' . esc_url( $book ) . '" target="_blank">' . esc_html( $book ) . '</a>', 'Not chosen' ) . '</td></tr>'; // phpcs:ignore
	echo '<tr><th>Confirmation page</th><td>' . $ok( (bool) $confirm, '<a href="' . esc_url( $confirm ) . '" target="_blank">' . esc_html( $confirm ) . '</a>', 'Not chosen' ) . '</td></tr>'; // phpcs:ignore
	$counts = array();
	foreach ( $menu['groups'] as $g ) {
		$counts[] = count( $g['packages'] ) . ' ' . strtolower( $g['label'] );
	}
	echo '<tr><th>Packages</th><td>' . $ok( (bool) $menu['packages'], esc_html( implode( ', ', $counts ) . ' package(s), ' . count( $menu['classes'] ) . ' vehicle size(s)' ), 'None with prices yet. Add sizes, then packages with a price per size.' ) . ' &nbsp;<a href="' . esc_url( dlux_bk_prices_url() ) . '">Edit Booking Prices</a></td></tr>'; // phpcs:ignore
	echo '<tr><th>Time slots</th><td>Every ' . (int) $c['slot'] . ' min · ' . (int) $c['bays'] . ' car(s) per slot · bookable ' . (int) $c['ahead'] . ' days ahead · ' . (int) $c['lead'] . ' min notice</td></tr>';
	echo '<tr><th>Timezone</th><td>' . esc_html( wp_timezone_string() ) . ' <span class="description">(Settings › General)</span></td></tr>';
	echo '</tbody></table>';

	if ( ! empty( $menu['warnings'] ) ) {
		echo '<div class="notice notice-warning inline" style="max-width:900px"><p><b>Check these on the <a href="' . esc_url( dlux_bk_prices_url() ) . '">Booking Prices</a> page:</b></p><ul style="list-style:disc;margin-left:20px">';
		foreach ( $menu['warnings'] as $w ) {
			echo '<li>' . esc_html( $w ) . '</li>';
		}
		echo '</ul></div>';
	}

	// Opening hours for the next 7 days.
	echo '<h2>Next 7 days</h2><table class="widefat striped" style="max-width:600px"><tbody>';
	for ( $i = 0; $i < 7; $i++ ) {
		$d = wp_date( 'Y-m-d', time() + $i * DAY_IN_SECONDS );
		$h = dlux_bk_hours_for( $d );
		echo '<tr><th style="width:180px">' . esc_html( wp_date( 'D j M', strtotime( $d . ' 12:00:00' ) ) ) . '</th><td>' . ( $h ? esc_html( dlux_bk_time_label( $h[0] ) . ' – ' . dlux_bk_time_label( $h[1] ) ) : '<em>Closed</em>' ) . '</td></tr>';
	}
	echo '</tbody></table>';

	// Price tables, one per group.
	if ( ! $menu['groups'] ) {
		echo '<h2>Packages</h2><p>No packages with prices yet. Add them on the <a href="' . esc_url( dlux_bk_prices_url() ) . '">Booking Prices</a> page.</p>';
	}
	foreach ( $menu['groups'] as $g ) {
		echo '<h2>' . esc_html( $g['label'] ) . '</h2>';
		echo '<table class="widefat striped" style="max-width:1100px"><thead><tr><th>Package</th><th>Link id</th>';
		foreach ( $menu['classes'] as $cl ) {
			echo '<th>' . esc_html( $cl ) . '</th>';
		}
		echo '<th>Extras</th></tr></thead><tbody>';
		foreach ( $g['packages'] as $p ) {
			echo '<tr><td><b>' . esc_html( $p['name'] ) . '</b>' . ( $p['featured'] ? ' <span class="description">(featured)</span>' : '' ) . ( $p['mins'] ? '<br><span class="description">' . (int) $p['mins'] . ' mins</span>' : '' ) . '</td><td><code>' . esc_html( $p['id'] ) . '</code></td>';
			foreach ( $menu['classes'] as $cl ) {
				echo '<td>' . ( isset( $p['prices'][ $cl ] ) ? esc_html( dlux_bk_money( $p['prices'][ $cl ] ) ) : '<span class="description">—</span>' ) . '</td>';
			}
			$x = array();
			foreach ( $p['extras'] as $e ) {
				$x[] = esc_html( $e['name'] . ' ' . dlux_bk_money( $e['price'] ) );
			}
			echo '<td>' . ( 'wash' === $g['id'] && $x ? '<span class="description">Wash extras (' . count( $x ) . ')</span>' : ( $x ? implode( '<br>', $x ) : '<span class="description">none</span>' ) ) . '</td></tr>'; // phpcs:ignore
		}
		echo '</tbody></table>';
		if ( $g['extras'] ) {
			echo '<p><b>Wash extras:</b> ';
			$x = array();
			foreach ( $g['extras'] as $e ) {
				$x[] = esc_html( $e['name'] . ' ' . dlux_bk_money( $e['price'] ) );
			}
			echo implode( ' · ', $x ) . '</p>'; // phpcs:ignore
		}
	}
	if ( $book && $menu['packages'] ) {
		$first = $menu['packages'][0];
		$size  = array_keys( $first['prices'] )[0];
		echo '<p class="description">Link straight to a package: <code>' . esc_html( add_query_arg( array( 'package' => $first['id'], 'size' => $size ), $book ) ) . '</code></p>';
	}
	echo '</div>';
}
