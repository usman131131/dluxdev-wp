<?php
/**
 * "Bookings" custom post type and its fields.
 *
 * Every booking is a dlux_booking post. Its details are SCF/ACF fields
 * stored as post meta with the bk_ prefix (bk_date, bk_time, bk_status ...).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dlux_bk_caps() {
	return array(
		'edit_dlux_bookings',
		'edit_others_dlux_bookings',
		'edit_published_dlux_bookings',
		'edit_private_dlux_bookings',
		'publish_dlux_bookings',
		'read_private_dlux_bookings',
		'delete_dlux_bookings',
		'delete_others_dlux_bookings',
		'delete_published_dlux_bookings',
		'delete_private_dlux_bookings',
	);
}

add_action(
	'init',
	function () {
		register_post_type(
			'dlux_booking',
			array(
				'labels'          => array(
					'name'               => 'Bookings',
					'singular_name'      => 'Booking',
					'menu_name'          => 'Bookings',
					'add_new'            => 'Add booking',
					'add_new_item'       => 'Add booking',
					'edit_item'          => 'Edit booking',
					'new_item'           => 'New booking',
					'view_item'          => 'View booking',
					'search_items'       => 'Search bookings',
					'not_found'          => 'No bookings found.',
					'not_found_in_trash' => 'No bookings in the Trash.',
					'all_items'          => 'All bookings',
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'show_in_rest'    => false,
				'menu_position'   => 26,
				'menu_icon'       => 'dashicons-calendar-alt',
				'supports'        => array( 'title' ),
				'capability_type' => array( 'dlux_booking', 'dlux_bookings' ),
				'map_meta_cap'    => true,
				'rewrite'         => false,
				'query_var'       => false,
			)
		);
	}
);

/* ---------------- Choices ---------------- */

function dlux_bk_status_choices() {
	return array(
		'pending'   => 'Awaiting payment',
		'confirmed' => 'Confirmed',
		'completed' => 'Completed',
		'cancelled' => 'Cancelled',
		'no_show'   => 'No show',
	);
}

function dlux_bk_payment_choices() {
	return array(
		'pending'  => 'Awaiting payment',
		'paid'     => 'Paid online',
		'unpaid'   => 'Pay at shop',
		'refunded' => 'Refunded',
		'failed'   => 'Failed',
	);
}

/* ---------------- Fields ---------------- */

/** Field name => field key. Meta keys are 'bk_' . name. */
function dlux_bk_field_keys() {
	$names = array( 'status', 'payment', 'date', 'time', 'service', 'vehicle_size', 'package', 'option', 'addons', 'total', 'name', 'phone', 'email', 'rego', 'vehicle', 'notes', 'channel', 'consent', 'stripe_session', 'stripe_payment_intent', 'amount_paid', 'paid_at' );
	$out   = array();
	foreach ( $names as $n ) {
		$out[ $n ] = 'field_dlux_bkb_' . $n;
	}
	return $out;
}

add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}
		$k  = dlux_bk_field_keys();
		$ro = array( 'readonly' => 1 );

		acf_add_local_field_group(
			array(
				'key'                   => 'group_dlux_bk_booking',
				'title'                 => 'Booking details',
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'hide_on_screen'        => array( 'permalink', 'the_content', 'excerpt', 'discussion', 'comments', 'slug', 'author', 'format', 'page_attributes', 'featured_image', 'categories', 'tags', 'send-trackbacks' ),
				'location'              => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'dlux_booking' ) ) ),
				'fields'                => array(
					array( 'key' => 'field_dlux_bkb_tab_booking', 'label' => 'Booking', 'type' => 'tab' ),
					array(
						'key'           => $k['status'],
						'name'          => 'bk_status',
						'label'         => 'Status',
						'type'          => 'select',
						'choices'       => dlux_bk_status_choices(),
						'default_value' => 'confirmed',
						'wrapper'       => array( 'width' => '25' ),
					),
					array(
						'key'           => $k['payment'],
						'name'          => 'bk_payment',
						'label'         => 'Payment',
						'type'          => 'select',
						'choices'       => dlux_bk_payment_choices(),
						'default_value' => 'unpaid',
						'wrapper'       => array( 'width' => '25' ),
					),
					array(
						'key'            => $k['date'],
						'name'           => 'bk_date',
						'label'          => 'Date',
						'type'           => 'date_picker',
						'display_format' => 'D j M Y',
						'return_format'  => 'Y-m-d',
						'first_day'      => 1,
						'required'       => 1,
						'wrapper'        => array( 'width' => '25' ),
					),
					array(
						'key'            => $k['time'],
						'name'           => 'bk_time',
						'label'          => 'Time',
						'type'           => 'time_picker',
						'display_format' => 'g:i a',
						'return_format'  => 'H:i',
						'required'       => 1,
						'wrapper'        => array( 'width' => '25' ),
					),
					array(
						'key'     => $k['service'],
						'name'    => 'bk_service',
						'label'   => 'Service',
						'type'    => 'text',
						'placeholder' => 'Car wash / Detailing',
						'wrapper' => array( 'width' => '25' ),
					),
					array(
						'key'     => $k['vehicle_size'],
						'name'    => 'bk_vehicle_size',
						'label'   => 'Vehicle size',
						'type'    => 'text',
						'wrapper' => array( 'width' => '25' ),
					),
					array(
						'key'     => $k['package'],
						'name'    => 'bk_package',
						'label'   => 'Package',
						'type'    => 'text',
						'wrapper' => array( 'width' => '25' ),
					),
					array(
						'key'     => $k['total'],
						'name'    => 'bk_total',
						'label'   => 'Total',
						'type'    => 'number',
						'prepend' => '$',
						'step'    => 0.01,
						'wrapper' => array( 'width' => '25' ),
					),
					array(
						'key'          => $k['option'],
						'name'         => 'bk_option',
						'label'        => 'Chosen option',
						'type'         => 'text',
						'instructions' => 'The "Choose from" option the customer picked (if the package has options).',
					),
					array(
						'key'          => $k['addons'],
						'name'         => 'bk_addons',
						'label'        => 'Extras',
						'type'         => 'textarea',
						'rows'         => 2,
						'instructions' => 'One per line.',
					),

					array( 'key' => 'field_dlux_bkb_tab_customer', 'label' => 'Customer', 'type' => 'tab' ),
					array(
						'key'     => $k['name'],
						'name'    => 'bk_name',
						'label'   => 'Name',
						'type'    => 'text',
						'wrapper' => array( 'width' => '33' ),
					),
					array(
						'key'     => $k['phone'],
						'name'    => 'bk_phone',
						'label'   => 'Mobile',
						'type'    => 'text',
						'wrapper' => array( 'width' => '33' ),
					),
					array(
						'key'     => $k['email'],
						'name'    => 'bk_email',
						'label'   => 'Email',
						'type'    => 'email',
						'wrapper' => array( 'width' => '33' ),
					),
					array(
						'key'     => $k['rego'],
						'name'    => 'bk_rego',
						'label'   => 'Rego',
						'type'    => 'text',
						'wrapper' => array( 'width' => '33' ),
					),
					array(
						'key'     => $k['vehicle'],
						'name'    => 'bk_vehicle',
						'label'   => 'Make, model & colour',
						'type'    => 'text',
						'wrapper' => array( 'width' => '33' ),
					),
					array(
						'key'     => $k['channel'],
						'name'    => 'bk_channel',
						'label'   => 'How they found us',
						'type'    => 'text',
						'wrapper' => array( 'width' => '33' ),
					),
					array(
						'key'   => $k['notes'],
						'name'  => 'bk_notes',
						'label' => 'Customer notes',
						'type'  => 'textarea',
						'rows'  => 3,
					),
					array(
						'key'     => $k['consent'],
						'name'    => 'bk_consent',
						'label'   => 'Happy to get offers',
						'type'    => 'true_false',
						'ui'      => 1,
						'message' => '',
					),

					array( 'key' => 'field_dlux_bkb_tab_payment', 'label' => 'Stripe', 'type' => 'tab' ),
					array_merge(
						array(
							'key'          => $k['stripe_session'],
							'name'         => 'bk_stripe_session',
							'label'        => 'Checkout session',
							'type'         => 'text',
							'instructions' => 'Filled in automatically.',
							'wrapper'      => array( 'width' => '50' ),
						),
						$ro
					),
					array_merge(
						array(
							'key'     => $k['stripe_payment_intent'],
							'name'    => 'bk_stripe_payment_intent',
							'label'   => 'Payment ID',
							'type'    => 'text',
							'wrapper' => array( 'width' => '50' ),
						),
						$ro
					),
					array_merge(
						array(
							'key'     => $k['amount_paid'],
							'name'    => 'bk_amount_paid',
							'label'   => 'Amount paid',
							'type'    => 'text',
							'prepend' => '$',
							'wrapper' => array( 'width' => '50' ),
						),
						$ro
					),
					array_merge(
						array(
							'key'     => $k['paid_at'],
							'name'    => 'bk_paid_at',
							'label'   => 'Paid at',
							'type'    => 'text',
							'wrapper' => array( 'width' => '50' ),
						),
						$ro
					),
				),
			)
		);
	}
);

/* ---------------- Read / write helpers ---------------- */

/**
 * Save one booking field. Uses SCF/ACF when available so the admin screen
 * shows the value; falls back to plain post meta.
 */
function dlux_bk_set( $post_id, $name, $value ) {
	$keys  = dlux_bk_field_keys();
	$value = is_string( $value ) ? wp_slash( $value ) : $value; // WordPress unslashes meta on save.
	if ( function_exists( 'update_field' ) && isset( $keys[ $name ] ) ) {
		update_field( $keys[ $name ], $value, $post_id );
	} else {
		update_post_meta( $post_id, 'bk_' . $name, $value );
	}
}

/** Raw meta value (dates are stored Ymd, times H:i:s). */
function dlux_bk_get( $post_id, $name ) {
	return get_post_meta( $post_id, 'bk_' . $name, true );
}

/** Ymd or Y-m-d -> Y-m-d. */
function dlux_bk_norm_day( $v ) {
	$v = preg_replace( '/\D/', '', (string) $v );
	return 8 === strlen( $v ) ? substr( $v, 0, 4 ) . '-' . substr( $v, 4, 2 ) . '-' . substr( $v, 6, 2 ) : '';
}

/** H:i:s or H:i -> H:i. */
function dlux_bk_norm_time( $v ) {
	return preg_match( '/^(\d{1,2}):(\d{2})/', (string) $v, $m ) ? sprintf( '%02d:%s', (int) $m[1], $m[2] ) : '';
}

function dlux_bk_time_label( $hm ) {
	if ( ! preg_match( '/^(\d{1,2}):(\d{2})/', (string) $hm, $m ) ) {
		return (string) $hm;
	}
	$h = (int) $m[1];
	return ( $h % 12 ?: 12 ) . ( '00' === $m[2] ? '' : ':' . $m[2] ) . ( $h >= 12 ? 'pm' : 'am' );
}

/** Booked time slot as "9:00 AM – 10:00 AM" (start + time slot length). */
function dlux_bk_time_range( $day, $hm ) {
	try {
		$start = new DateTimeImmutable( $day . ' ' . $hm, wp_timezone() );
	} catch ( Exception $e ) {
		return dlux_bk_time_label( $hm );
	}
	$mins = function_exists( 'dlux_bk_config' ) ? (int) dlux_bk_config()['slot'] : 60;
	$end  = $start->add( new DateInterval( 'PT' . max( 10, $mins ) . 'M' ) );
	return $start->format( 'g:i A' ) . ' – ' . $end->format( 'g:i A' );
}

function dlux_bk_money( $n ) {
	return '$' . number_format( (float) $n, 2 );
}

/** Everything about one booking, in one array. */
function dlux_bk_booking( $post_id ) {
	$post_id = (int) $post_id;
	if ( ! $post_id || 'dlux_booking' !== get_post_type( $post_id ) ) {
		return null;
	}
	$items = get_post_meta( $post_id, '_dlux_items', true );
	if ( is_string( $items ) && '' !== $items ) {
		$items = json_decode( $items, true ); // Bookings made with 4.0.
	}
	$items = is_array( $items ) ? array_values( array_filter( $items, 'is_array' ) ) : array();
	foreach ( $items as $i => $it ) {
		$items[ $i ] = array(
			'name'  => (string) ( $it['name'] ?? '' ),
			'price' => isset( $it['price'] ) ? (float) $it['price'] : null,
		);
	}
	if ( ! $items ) {
		// Bookings added by hand in the admin.
		$items = array();
		$pkg   = (string) dlux_bk_get( $post_id, 'package' );
		if ( '' !== $pkg ) {
			$items[] = array( 'name' => $pkg, 'price' => null );
		}
		foreach ( preg_split( '/\r\n|\r|\n/', (string) dlux_bk_get( $post_id, 'addons' ) ) as $line ) {
			if ( '' !== trim( $line ) ) {
				$items[] = array( 'name' => trim( $line ), 'price' => null );
			}
		}
	}
	$day  = dlux_bk_norm_day( dlux_bk_get( $post_id, 'date' ) );
	$time = dlux_bk_norm_time( dlux_bk_get( $post_id, 'time' ) );

	return array(
		'id'       => $post_id,
		'status'   => (string) ( dlux_bk_get( $post_id, 'status' ) ?: 'confirmed' ),
		'payment'  => (string) ( dlux_bk_get( $post_id, 'payment' ) ?: 'unpaid' ),
		'day'      => $day,
		'time'     => $time,
		'when'     => $day ? wp_date( 'l j F', strtotime( $day . ' 12:00:00' ) ) . ( $time ? ', ' . dlux_bk_time_label( $time ) : '' ) : '',
		'when_slot' => $day ? wp_date( 'l j F', strtotime( $day . ' 12:00:00' ) ) . ( $time ? ', ' . dlux_bk_time_range( $day, $time ) : '' ) : '',
		'service'  => (string) dlux_bk_get( $post_id, 'service' ),
		'mins'     => (int) get_post_meta( $post_id, '_dlux_mins', true ),
		'cls'      => (string) dlux_bk_get( $post_id, 'vehicle_size' ),
		'package'  => (string) dlux_bk_get( $post_id, 'package' ),
		'option'   => (string) dlux_bk_get( $post_id, 'option' ),
		'items'    => $items,
		'total'    => (float) dlux_bk_get( $post_id, 'total' ),
		'name'     => (string) dlux_bk_get( $post_id, 'name' ),
		'phone'    => (string) dlux_bk_get( $post_id, 'phone' ),
		'email'    => (string) dlux_bk_get( $post_id, 'email' ),
		'rego'     => (string) dlux_bk_get( $post_id, 'rego' ),
		'vehicle'  => (string) dlux_bk_get( $post_id, 'vehicle' ),
		'notes'    => (string) dlux_bk_get( $post_id, 'notes' ),
		'channel'  => (string) dlux_bk_get( $post_id, 'channel' ),
		'session'  => (string) dlux_bk_get( $post_id, 'stripe_session' ),
		'intent'   => (string) dlux_bk_get( $post_id, 'stripe_payment_intent' ),
		'token'    => (string) get_post_meta( $post_id, '_dlux_token', true ),
		'livemode' => (bool) get_post_meta( $post_id, '_dlux_livemode', true ),
	);
}

/** Title shown in the bookings list, e.g. "ABC123 · Jane Smith · Tue 14 Oct, 9:30am". */
function dlux_bk_refresh_title( $post_id ) {
	global $wpdb;
	$b = dlux_bk_booking( $post_id );
	if ( ! $b ) {
		return;
	}
	$parts = array_filter(
		array(
			$b['rego'],
			$b['name'],
			$b['day'] ? wp_date( 'D j M', strtotime( $b['day'] . ' 12:00:00' ) ) . ( $b['time'] ? ', ' . dlux_bk_time_label( $b['time'] ) : '' ) : '',
		)
	);
	$title = $parts ? implode( ' · ', $parts ) : 'Booking #' . $post_id;
	// Direct update, so no save hooks run again.
	$wpdb->update( $wpdb->posts, array( 'post_title' => $title ), array( 'ID' => $post_id ) );
	clean_post_cache( $post_id );
}

/* Bookings added or edited in the admin. */
add_action(
	'acf/save_post',
	function ( $post_id ) {
		if ( ! is_numeric( $post_id ) || 'dlux_booking' !== get_post_type( $post_id ) ) {
			return;
		}
		if ( ! get_post_meta( $post_id, '_dlux_token', true ) ) {
			update_post_meta( $post_id, '_dlux_token', wp_generate_password( 32, false ) );
		}
		$rego = (string) dlux_bk_get( $post_id, 'rego' );
		$up   = strtoupper( preg_replace( '/[^a-z0-9]/i', '', $rego ) );
		if ( $up !== $rego ) {
			update_post_meta( $post_id, 'bk_rego', $up );
		}
		dlux_bk_refresh_title( $post_id );
	},
	20
);

/* Title field is generated, so hide it on the edit screen. */
add_action(
	'admin_head',
	function () {
		$s = get_current_screen();
		if ( $s && 'dlux_booking' === $s->post_type && 'post' === $s->base ) {
			echo '<style>#titlediv{display:none}</style>';
		}
	}
);

/* ---------------- Admin list ---------------- */

add_filter(
	'manage_dlux_booking_posts_columns',
	function () {
		return array(
			'cb'           => '<input type="checkbox">',
			'title'        => 'Booking',
			'dlux_when'    => 'When',
			'dlux_cust'    => 'Customer',
			'dlux_items'   => 'Services',
			'dlux_total'   => 'Total',
			'dlux_payment' => 'Payment',
			'dlux_status'  => 'Status',
		);
	}
);

add_action(
	'manage_dlux_booking_posts_custom_column',
	function ( $col, $post_id ) {
		$b = dlux_bk_booking( $post_id );
		if ( ! $b ) {
			return;
		}
		$tag = function ( $text, $cls ) {
			return '<span class="dlux-tag ' . esc_attr( $cls ) . '">' . esc_html( $text ) . '</span>';
		};
		switch ( $col ) {
			case 'dlux_when':
				if ( $b['day'] ) {
					echo '<b>' . esc_html( wp_date( 'D j M Y', strtotime( $b['day'] . ' 12:00:00' ) ) ) . '</b><br>' . esc_html( dlux_bk_time_label( $b['time'] ) );
				}
				break;
			case 'dlux_cust':
				echo '<span class="dlux-plate">' . esc_html( $b['rego'] ) . '</span><br>' . esc_html( $b['name'] );
				if ( $b['phone'] ) {
					echo '<br><a href="tel:' . esc_attr( preg_replace( '/[^\d+]/', '', $b['phone'] ) ) . '">' . esc_html( $b['phone'] ) . '</a>';
				}
				if ( $b['vehicle'] || $b['cls'] ) {
					echo '<br><span class="dlux-muted">' . esc_html( trim( $b['cls'] . ( $b['vehicle'] ? ' · ' . $b['vehicle'] : '' ), ' ·' ) ) . '</span>';
				}
				break;
			case 'dlux_items':
				if ( $b['service'] ) {
					echo '<span class="dlux-muted">' . esc_html( $b['service'] ) . '</span><br>';
				}
				echo esc_html( implode( ' + ', wp_list_pluck( $b['items'], 'name' ) ) );
				if ( $b['option'] ) {
					echo '<br><span class="dlux-muted">Option: ' . esc_html( $b['option'] ) . '</span>';
				}
				if ( $b['notes'] ) {
					echo '<br><em class="dlux-muted">' . esc_html( wp_trim_words( $b['notes'], 12 ) ) . '</em>';
				}
				break;
			case 'dlux_total':
				echo '<b>' . esc_html( dlux_bk_money( $b['total'] ) ) . '</b>';
				break;
			case 'dlux_payment':
				$map = array( 'paid' => 'dlux-ok', 'pending' => 'dlux-warn', 'unpaid' => 'dlux-blue', 'refunded' => 'dlux-bad', 'failed' => 'dlux-bad' );
				$ch  = dlux_bk_payment_choices();
				echo $tag( $ch[ $b['payment'] ] ?? ucfirst( $b['payment'] ), $map[ $b['payment'] ] ?? '' ); // phpcs:ignore
				break;
			case 'dlux_status':
				$map = array( 'confirmed' => 'dlux-blue', 'completed' => 'dlux-ok', 'pending' => 'dlux-warn', 'cancelled' => 'dlux-bad', 'no_show' => 'dlux-bad' );
				$ch  = dlux_bk_status_choices();
				echo $tag( $ch[ $b['status'] ] ?? ucfirst( $b['status'] ), $map[ $b['status'] ] ?? '' ); // phpcs:ignore
				break;
		}
	},
	10,
	2
);

add_filter(
	'manage_edit-dlux_booking_sortable_columns',
	function ( $cols ) {
		$cols['dlux_when'] = 'dlux_when';
		return $cols;
	}
);

/* Remove "Quick edit" (it would skip the booking fields). */
add_filter(
	'post_row_actions',
	function ( $actions, $post ) {
		if ( 'dlux_booking' === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] );
		}
		return $actions;
	},
	10,
	2
);

/* Today / Upcoming / Past quick links. */
add_filter(
	'views_edit-dlux_booking',
	function ( $views ) {
		$cur  = isset( $_GET['dlux_range'] ) ? sanitize_key( $_GET['dlux_range'] ) : ''; // phpcs:ignore
		$base = admin_url( 'edit.php?post_type=dlux_booking' );
		foreach ( array( 'today' => 'Today', 'tomorrow' => 'Tomorrow', 'upcoming' => 'Upcoming', 'past' => 'Past' ) as $k => $label ) {
			$views[ 'dlux_' . $k ] = '<a href="' . esc_url( add_query_arg( 'dlux_range', $k, $base ) ) . '"' . ( $cur === $k ? ' class="current" aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a>';
		}
		return $views;
	}
);

/* Status / payment / date filters. */
add_action(
	'restrict_manage_posts',
	function ( $post_type ) {
		if ( 'dlux_booking' !== $post_type ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$st  = isset( $_GET['dlux_status'] ) ? sanitize_key( $_GET['dlux_status'] ) : '';
		$pay = isset( $_GET['dlux_payment'] ) ? sanitize_key( $_GET['dlux_payment'] ) : '';
		$day = isset( $_GET['dlux_day'] ) ? sanitize_text_field( wp_unslash( $_GET['dlux_day'] ) ) : '';
		if ( isset( $_GET['dlux_range'] ) ) {
			echo '<input type="hidden" name="dlux_range" value="' . esc_attr( sanitize_key( $_GET['dlux_range'] ) ) . '">';
		}
		// phpcs:enable
		echo '<select name="dlux_status"><option value="">All statuses</option>';
		foreach ( dlux_bk_status_choices() as $k => $v ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $st, $k, false ) . '>' . esc_html( $v ) . '</option>';
		}
		echo '</select><select name="dlux_payment"><option value="">All payments</option>';
		foreach ( dlux_bk_payment_choices() as $k => $v ) {
			echo '<option value="' . esc_attr( $k ) . '"' . selected( $pay, $k, false ) . '>' . esc_html( $v ) . '</option>';
		}
		echo '</select><input type="date" name="dlux_day" value="' . esc_attr( $day ) . '" title="Booking date">';
	}
);

add_action(
	'pre_get_posts',
	function ( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() || 'dlux_booking' !== $q->get( 'post_type' ) ) {
			return;
		}
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$range = isset( $_GET['dlux_range'] ) ? sanitize_key( $_GET['dlux_range'] ) : '';
		$st    = isset( $_GET['dlux_status'] ) ? sanitize_key( $_GET['dlux_status'] ) : '';
		$pay   = isset( $_GET['dlux_payment'] ) ? sanitize_key( $_GET['dlux_payment'] ) : '';
		$day   = isset( $_GET['dlux_day'] ) ? preg_replace( '/\D/', '', wp_unslash( $_GET['dlux_day'] ) ) : '';
		// phpcs:enable
		$today    = wp_date( 'Ymd' );
		$tomorrow = wp_date( 'Ymd', time() + DAY_IN_SECONDS );

		$date_clause = array( 'key' => 'bk_date', 'compare' => 'EXISTS' );
		$dir         = 'DESC';
		if ( 8 === strlen( $day ) ) {
			$date_clause = array( 'key' => 'bk_date', 'value' => $day );
			$dir         = 'ASC';
		} elseif ( 'today' === $range ) {
			$date_clause = array( 'key' => 'bk_date', 'value' => $today );
			$dir         = 'ASC';
		} elseif ( 'tomorrow' === $range ) {
			$date_clause = array( 'key' => 'bk_date', 'value' => $tomorrow );
			$dir         = 'ASC';
		} elseif ( 'upcoming' === $range ) {
			$date_clause = array( 'key' => 'bk_date', 'value' => $today, 'compare' => '>=' );
			$dir         = 'ASC';
		} elseif ( 'past' === $range ) {
			$date_clause = array( 'key' => 'bk_date', 'value' => $today, 'compare' => '<' );
		}
		if ( isset( $_GET['order'] ) ) { // phpcs:ignore
			$dir = 'asc' === strtolower( sanitize_key( $_GET['order'] ) ) ? 'ASC' : 'DESC'; // phpcs:ignore
		}

		$mq = array(
			'relation' => 'AND',
			'dlux_d'   => $date_clause,
			'dlux_t'   => array( 'key' => 'bk_time', 'compare' => 'EXISTS' ),
		);
		if ( $st && isset( dlux_bk_status_choices()[ $st ] ) ) {
			$mq[] = array( 'key' => 'bk_status', 'value' => $st );
		}
		if ( $pay && isset( dlux_bk_payment_choices()[ $pay ] ) ) {
			$mq[] = array( 'key' => 'bk_payment', 'value' => $pay );
		}
		$q->set( 'meta_query', $mq );

		$orderby = $q->get( 'orderby' );
		if ( ! $orderby || 'dlux_when' === $orderby ) {
			$q->set(
				'orderby',
				array(
					'dlux_d' => $dir,
					'dlux_t' => $dir,
				)
			);
		}
	}
);

/* Small styles for the list and edit screens. */
add_action(
	'admin_head',
	function () {
		$s = get_current_screen();
		if ( ! $s || 'dlux_booking' !== $s->post_type ) {
			return;
		}
		echo '<style>
		.dlux-tag{display:inline-block;padding:2px 8px;border-radius:10px;font-size:12px;line-height:1.6;background:#eee;color:#333;white-space:nowrap}
		.dlux-ok{background:#d7f5dd;color:#13612a}.dlux-warn{background:#fff1c9;color:#7a5a00}
		.dlux-bad{background:#fde0e0;color:#8a1f1f}.dlux-blue{background:#dceafe;color:#1d4f91}
		.dlux-plate{font-family:monospace;font-weight:700;letter-spacing:1px}.dlux-muted{color:#777}
		.post-type-dlux_booking .column-dlux_when{width:120px}.post-type-dlux_booking .column-dlux_total{width:80px}
		.post-type-dlux_booking .column-dlux_payment,.post-type-dlux_booking .column-dlux_status{width:130px}
		</style>';
	}
);

/* Side box with a link to the payment in Stripe. */
add_action(
	'add_meta_boxes_dlux_booking',
	function ( $post ) {
		add_meta_box(
			'dlux_bk_stripe',
			'Payment',
			function ( $post ) {
				$b = dlux_bk_booking( $post->ID );
				if ( ! $b || ( ! $b['intent'] && ! $b['session'] ) ) {
					echo '<p class="dlux-muted">No online payment for this booking.</p>';
					return;
				}
				$base = 'https://dashboard.stripe.com/' . ( $b['livemode'] ? '' : 'test/' );
				if ( $b['intent'] ) {
					echo '<p><a class="button" target="_blank" rel="noopener" href="' . esc_url( $base . 'payments/' . $b['intent'] ) . '">View payment in Stripe</a></p>';
					echo '<p class="dlux-muted">Refunds are made in Stripe. The booking is marked "Refunded" automatically.</p>';
				} else {
					echo '<p class="dlux-muted">The customer hasn\'t paid yet.</p>';
				}
			},
			'dlux_booking',
			'side'
		);
	}
);
