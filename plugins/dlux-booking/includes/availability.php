<?php
/**
 * Opening hours and free time slots.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Unpaid online bookings hold their time slot for this long (Stripe Checkout lasts 30 minutes). */
define( 'DLUX_BK_HOLD_MINUTES', 35 );

function dlux_bk_config() {
	return array(
		'slot'  => max( 10, (int) dlux_bk_opt( 'slot_length', 60 ) ),
		'bays'  => max( 1, (int) dlux_bk_opt( 'bays', 1 ) ),
		'ahead' => min( 90, max( 1, (int) dlux_bk_opt( 'days_ahead', 30 ) ) ),
		'lead'  => max( 0, (int) dlux_bk_opt( 'lead_time', 60 ) ),
	);
}

function dlux_bk_today() {
	return wp_date( 'Y-m-d' );
}

function dlux_bk_valid_day( $day ) {
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $day ) ) {
		return false;
	}
	$c    = dlux_bk_config();
	$last = wp_date( 'Y-m-d', time() + $c['ahead'] * DAY_IN_SECONDS );
	return $day >= dlux_bk_today() && $day <= $last;
}

/** @return array|null [ 'H:i', 'H:i' ] or null when closed. */
function dlux_bk_hours_for( $day ) {
	foreach ( dlux_bk_rows( 'closed_dates' ) as $row ) {
		if ( dlux_bk_norm_day( $row['date'] ?? '' ) === $day ) {
			return null;
		}
	}

	$weekday = ( new DateTimeImmutable( $day . ' 12:00:00', wp_timezone() ) )->format( 'N' );
	$rows    = dlux_bk_rows( 'hours' );
	if ( ! $rows ) {
		$hours = array( '09:00', '17:00' );
	} else {
		$hours = null;
		foreach ( $rows as $row ) {
			$days = array_map( 'strval', (array) ( $row['days'] ?? array() ) );
			if ( in_array( $weekday, $days, true ) ) {
				$hours = array( dlux_bk_norm_time( $row['open'] ?? '' ), dlux_bk_norm_time( $row['close'] ?? '' ) );
				break;
			}
		}
	}
	$hours = apply_filters( 'dlux_booking_hours', $hours, $day );
	if ( ! $hours || ! $hours[0] || ! $hours[1] || $hours[1] <= $hours[0] ) {
		return null;
	}
	return $hours;
}

/**
 * Bookings that hold a time on this day.
 *
 * @return array [ 'H:i' => count ]
 */
function dlux_bk_taken( $day, $exclude_id = 0 ) {
	$ids = get_posts(
		array(
			'post_type'        => 'dlux_booking',
			'post_status'      => array( 'publish', 'private', 'draft', 'pending', 'future' ),
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => true,
			'meta_query'       => array( array( 'key' => 'bk_date', 'value' => str_replace( '-', '', $day ) ) ), // phpcs:ignore
		)
	);
	$cutoff = time() - DLUX_BK_HOLD_MINUTES * MINUTE_IN_SECONDS;
	$taken  = array();
	foreach ( $ids as $id ) {
		if ( (int) $id === (int) $exclude_id ) {
			continue;
		}
		$status = (string) dlux_bk_get( $id, 'status' );
		if ( 'cancelled' === $status ) {
			continue;
		}
		if ( 'pending' === $status && 'pending' === (string) dlux_bk_get( $id, 'payment' ) && get_post_time( 'U', true, $id ) < $cutoff ) {
			continue; // Abandoned checkout.
		}
		$t = dlux_bk_norm_time( dlux_bk_get( $id, 'time' ) );
		if ( $t ) {
			$taken[ $t ] = ( $taken[ $t ] ?? 0 ) + 1;
		}
	}
	return $taken;
}

/** @return array list of [ time, label, free ] */
function dlux_bk_slots( $day ) {
	if ( ! dlux_bk_valid_day( $day ) ) {
		return array();
	}
	$hours = dlux_bk_hours_for( $day );
	if ( ! $hours ) {
		return array();
	}
	$c     = dlux_bk_config();
	$tz    = wp_timezone();
	$start = new DateTimeImmutable( $day . ' ' . $hours[0] . ':00', $tz );
	$end   = new DateTimeImmutable( $day . ' ' . $hours[1] . ':00', $tz );
	$min   = time() + $c['lead'] * MINUTE_IN_SECONDS;
	$taken = dlux_bk_taken( $day );
	$step  = new DateInterval( 'PT' . $c['slot'] . 'M' );
	$out   = array();

	for ( $t = $start; $t->add( $step ) <= $end; $t = $t->add( $step ) ) {
		if ( $t->getTimestamp() < $min ) {
			continue;
		}
		$hm    = $t->format( 'H:i' );
		$out[] = array(
			'time'  => $hm,
			'label' => dlux_bk_slot_label( $t, $t->add( $step ) ),
			'free'  => max( 0, $c['bays'] - ( $taken[ $hm ] ?? 0 ) ),
		);
	}
	return $out;
}

/** "9:00 AM – 10:00 AM" */
function dlux_bk_slot_label( DateTimeImmutable $start, DateTimeImmutable $end ) {
	return $start->format( 'g:i A' ) . ' – ' . $end->format( 'g:i A' );
}

function dlux_bk_slot_free( $day, $time ) {
	foreach ( dlux_bk_slots( $day ) as $s ) {
		if ( $s['time'] === $time ) {
			return $s['free'] > 0;
		}
	}
	return false;
}

/**
 * One-time (4.4.0): switch saved settings to 1-hour slots from 9am to 5pm
 * (9:00 AM – 10:00 AM … 4:00 PM – 5:00 PM). Days that are open stay open;
 * you can still change the times later in Booking Settings › Booking.
 */
add_action(
	'init',
	function () {
		if ( get_option( 'dlux_bk_hourly_9to5_440' ) ) {
			return;
		}
		update_option( 'dlux_bk_hourly_9to5_440', 1, false );

		if ( false !== get_option( 'options_dlux_bk_slot_length', false ) ) {
			update_option( 'options_dlux_bk_slot_length', 60 );
		}
		$rows = (int) get_option( 'options_dlux_bk_hours', 0 );
		for ( $i = 0; $i < $rows; $i++ ) {
			update_option( 'options_dlux_bk_hours_' . $i . '_open', '09:00:00' );
			update_option( 'options_dlux_bk_hours_' . $i . '_close', '17:00:00' );
		}
	},
	32
);
