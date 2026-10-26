<?php
/**
 * Booking prices.
 *
 * Stored on their own SCF options page: Theme Settings › Booking Prices
 * (falls back to Bookings › Booking Prices if Theme Settings doesn't exist).
 * Values are saved in wp_options with the prefix "dlux_prices_", so they never
 * clash with Theme Settings fields. The homepage Pricing section is separate
 * and is NOT read by the plugin.
 *
 *  Vehicle sizes tab
 *    sizes                    repeater: label          (the sizes customers choose from, in order)
 *
 *  Wash packages tab
 *    wash_packages            repeater
 *      name, subtitle, duration, features, is_featured, badge
 *      prices                 repeater: size, price
 *    wash_extras              repeater: name, price    (offered with every wash package)
 *
 *  Detailing tab
 *    detail_packages          repeater
 *      name, subtitle, duration, features, is_featured, badge
 *      prices                 repeater: size, price
 *      extras                 repeater: name, price    (offered only with this package)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DLUX_BK_PRICES_ID', 'dlux_prices' );     // SCF post_id → option names "dlux_prices_*".
define( 'DLUX_BK_PRICES_PAGE', 'dlux-prices' );   // Admin page slug.

/** Any value → list of rows (arrays). SCF returns false for an empty repeater. */
function dlux_bk_as_rows( $v ) {
	return is_array( $v ) ? array_values( array_filter( $v, 'is_array' ) ) : array();
}

/** Read a field from the Booking Prices page. */
function dlux_bk_price_field( $name ) {
	return function_exists( 'get_field' ) ? get_field( $name, DLUX_BK_PRICES_ID ) : null;
}

function dlux_bk_prices_url() {
	return admin_url( 'admin.php?page=' . DLUX_BK_PRICES_PAGE );
}

/* ---------------- Options page ---------------- */

add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
			return;
		}
		$under_theme = dlux_bk_theme_options_exist();
		if ( $under_theme && function_exists( 'acf_update_options_page' ) ) {
			// SCF's default "redirect" makes a parent options page open its first
			// child instead of itself. Turn it off so Theme Settings keeps showing
			// its own fields once Booking Prices is added under it.
			acf_update_options_page( DLUX_BK_THEME_OPTIONS, array( 'redirect' => false ) );
		}
		acf_add_options_sub_page(
			array(
				'page_title'      => 'Booking Prices',
				'menu_title'      => 'Booking Prices',
				'menu_slug'       => DLUX_BK_PRICES_PAGE,
				'parent_slug'     => $under_theme ? DLUX_BK_THEME_OPTIONS : 'edit.php?post_type=dlux_booking',
				'capability'      => 'manage_options',
				'post_id'         => DLUX_BK_PRICES_ID,
				'autoload'        => false,
				'update_button'   => 'Save prices',
				'updated_message' => 'Prices saved.',
			)
		);
	},
	100 // After the theme registers Theme Settings (priority 99).
);

/**
 * Keep Theme Settings' own page as the first item in its submenu, so the
 * top-level "Theme Settings" link opens Theme Settings, not Booking Prices.
 */
add_action(
	'admin_menu',
	function () {
		global $submenu;
		$parent = DLUX_BK_THEME_OPTIONS;
		if ( empty( $submenu[ $parent ] ) || ! is_array( $submenu[ $parent ] ) || ! dlux_bk_theme_options_exist() ) {
			return;
		}
		$self = array();
		$rest = array();
		foreach ( $submenu[ $parent ] as $item ) {
			if ( isset( $item[2] ) && $parent === $item[2] ) {
				$self[] = $item;
			} else {
				$rest[] = $item;
			}
		}
		if ( ! $self ) {
			$page   = acf_get_options_page( $parent );
			$self[] = array(
				'Settings',
				is_array( $page ) && ! empty( $page['capability'] ) ? $page['capability'] : 'edit_theme_options',
				$parent,
				is_array( $page ) && ! empty( $page['page_title'] ) ? $page['page_title'] : 'Theme Settings',
			);
		}
		$submenu[ $parent ] = array_merge( array( $self[0] ), $rest ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	},
	1001 // After the theme adds its own submenu items (999).
);

/* ---------------- Fields ---------------- */

add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}
		$k = function ( $name ) {
			return 'field_dlux_px_' . $name;
		};

		// Extras repeater (name + price).
		$extras = function ( $key, $name, $label, $instructions, $width = '' ) use ( $k ) {
			$f = array(
				'key'          => $k( $key ),
				'name'         => $name,
				'label'        => $label,
				'type'         => 'repeater',
				'layout'       => 'table',
				'button_label' => 'Add extra',
				'instructions' => $instructions,
				'sub_fields'   => array(
					array(
						'key'   => $k( $key . '_name' ),
						'name'  => 'name',
						'label' => 'Name',
						'type'  => 'text',
					),
					array(
						'key'     => $k( $key . '_price' ),
						'name'    => 'price',
						'label'   => 'Price',
						'type'    => 'number',
						'prepend' => '$',
						'min'     => 0,
						'step'    => 0.01,
						'wrapper' => array( 'width' => '30' ),
					),
				),
			);
			if ( $width ) {
				$f['wrapper'] = array( 'width' => $width );
			}
			return $f;
		};

		// One package repeater. Wash and detailing use exactly the same fields;
		// detailing packages also get their own extras.
		$packages = function ( $p, $name, $label, $button, $instructions, $with_extras ) use ( $k, $extras ) {
			$sub = array(
				array(
					'key'      => $k( $p . '_name' ),
					'name'     => 'name',
					'label'    => 'Name',
					'type'     => 'text',
					'required' => 1,
					'wrapper'  => array( 'width' => '40' ),
				),
				array(
					'key'     => $k( $p . '_sub' ),
					'name'    => 'subtitle',
					'label'   => 'Subtitle',
					'type'    => 'text',
					'wrapper' => array( 'width' => '40' ),
				),
				array(
					'key'          => $k( $p . '_mins' ),
					'name'         => 'duration',
					'label'        => 'Duration (mins)',
					'type'         => 'number',
					'min'          => 0,
					'step'         => 5,
					'instructions' => 'Optional.',
					'wrapper'      => array( 'width' => '20' ),
				),
				array(
					'key'          => $k( $p . '_features' ),
					'name'         => 'features',
					'label'        => 'Features',
					'type'         => 'textarea',
					'rows'         => 3,
					'instructions' => 'One per line.',
					'wrapper'      => array( 'width' => '60' ),
				),
				array(
					'key'          => $k( $p . '_choose_title' ),
					'name'         => 'choose_title',
					'label'        => '"Choose from" heading',
					'type'         => 'text',
					'placeholder'  => 'Choose from:',
					'instructions' => 'Optional. Shown above the numbered list.',
					'wrapper'      => array( 'width' => '40' ),
				),
				array(
					'key'          => $k( $p . '_choose' ),
					'name'         => 'choose_from',
					'label'        => 'Choose from (numbered list)',
					'type'         => 'textarea',
					'rows'         => 3,
					'instructions' => 'Optional. One option per line – shown as 1, 2, 3 with "(or)" between them.',
					'wrapper'      => array( 'width' => '60' ),
				),
				array(
					'key'          => $k( $p . '_note' ),
					'name'         => 'note',
					'label'        => 'Card note',
					'type'         => 'textarea',
					'rows'         => 2,
					'new_lines'    => '',
					'instructions' => 'Optional short highlighted text at the bottom of the card, e.g. "Don\'t forget! Add a popular upgrade to your wash."',
				),
				array(
					'key'     => $k( $p . '_featured' ),
					'name'    => 'is_featured',
					'label'   => 'Featured / highlighted',
					'type'    => 'true_false',
					'ui'      => 1,
					'wrapper' => array( 'width' => '20' ),
				),
				array(
					'key'         => $k( $p . '_badge' ),
					'name'        => 'badge',
					'label'       => 'Badge text',
					'type'        => 'text',
					'placeholder' => 'Most popular',
					'wrapper'     => array( 'width' => '20' ),
				),
				array(
					'key'          => $k( $p . '_prices' ),
					'name'         => 'prices',
					'label'        => 'Price per vehicle size',
					'type'         => 'repeater',
					'layout'       => 'table',
					'button_label' => 'Add size price',
					'instructions' => 'Sizes come from the Vehicle sizes tab. Sizes without a row are not offered.',
					'wrapper'      => array( 'width' => $with_extras ? '50' : '100' ),
					'sub_fields'   => array(
						array(
							'key'     => $k( $p . '_price_size' ),
							'name'    => 'size',
							'label'   => 'Size',
							'type'    => 'select',
							'choices' => array(),
						),
						array(
							'key'     => $k( $p . '_price_price' ),
							'name'    => 'price',
							'label'   => 'Price',
							'type'    => 'number',
							'prepend' => '$',
							'min'     => 0,
							'step'    => 0.01,
						),
					),
				),
			);
			if ( $with_extras ) {
				$sub[] = $extras( $p . '_extras', 'extras', 'Extras for this package', 'Shown only when this package is chosen. Same price for every size.', '50' );

				// Detailing packages section (theme): card image + "What's included" panel.
				$sub[] = array(
					'key'          => $k( $p . '_show_msg' ),
					'name'         => '',
					'label'        => 'Detailing page',
					'type'         => 'message',
					'message'      => 'Used by the Detailing packages section: the card image, and the "What\'s included" panel shown when a package is clicked. All optional.',
				);
				$sub[] = array(
					'key'           => $k( $p . '_image' ),
					'name'          => 'image',
					'label'         => 'Card image',
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'thumbnail',
					'library'       => 'all',
					'wrapper'       => array( 'width' => '25' ),
				);
				$sub[] = array(
					'key'           => $k( $p . '_before' ),
					'name'          => 'before_image',
					'label'         => 'Before image',
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'thumbnail',
					'library'       => 'all',
					'instructions'  => 'Left half of the "What\'s included" picture.',
					'wrapper'       => array( 'width' => '25' ),
				);
				$sub[] = array(
					'key'           => $k( $p . '_after' ),
					'name'          => 'after_image',
					'label'         => 'After image',
					'type'          => 'image',
					'return_format' => 'id',
					'preview_size'  => 'thumbnail',
					'library'       => 'all',
					'instructions'  => 'Right half. With only one image it fills the whole picture.',
					'wrapper'       => array( 'width' => '25' ),
				);
				$sub[] = array(
					'key'          => $k( $p . '_price_label' ),
					'name'         => 'price_label',
					'label'        => 'Price label',
					'type'         => 'text',
					'placeholder'  => 'From',
					'instructions' => 'Shown above the price, e.g. "From" or "Price".',
					'wrapper'      => array( 'width' => '25' ),
				);
				$sub[] = array(
					'key'          => $k( $p . '_long_desc' ),
					'name'         => 'long_description',
					'label'        => '"What\'s included" text',
					'type'         => 'textarea',
					'rows'         => 3,
					'new_lines'    => '',
					'instructions' => 'Paragraph above the features list. Falls back to the subtitle.',
					'wrapper'      => array( 'width' => '60' ),
				);
				$sub[] = array(
					'key'          => $k( $p . '_ideal' ),
					'name'         => 'ideal_for',
					'label'        => 'Ideal for',
					'type'         => 'text',
					'placeholder'  => 'Cars being sold or bought',
					'wrapper'      => array( 'width' => '40' ),
				);
			}
			return array(
				'key'          => $k( $p ),
				'name'         => $name,
				'label'        => $label,
				'type'         => 'repeater',
				'layout'       => 'block',
				'collapsed'    => $k( $p . '_name' ),
				'button_label' => $button,
				'instructions' => $instructions,
				'sub_fields'   => $sub,
			);
		};

		acf_add_local_field_group(
			array(
				'key'      => 'group_dlux_bk_prices',
				'title'    => 'Booking Prices',
				'style'    => 'seamless',
				'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => DLUX_BK_PRICES_PAGE ) ) ),
				'fields'   => array(

					/* ---------- Vehicle sizes ---------- */
					array( 'key' => $k( 'tab_sizes' ), 'label' => 'Vehicle sizes', 'type' => 'tab', 'placement' => 'top' ),
					array(
						'key'          => $k( 'sizes' ),
						'name'         => 'sizes',
						'label'        => 'Vehicle sizes',
						'type'         => 'repeater',
						'layout'       => 'table',
						'button_label' => 'Add size',
						'instructions' => 'The sizes customers choose from, in this order (e.g. Sedan, SUV/SW, Large, Extra Large). After adding or renaming a size, click Save prices, then pick it in the Size dropdowns on the other tabs.',
						'sub_fields'   => array(
							array(
								'key'      => $k( 'size_label' ),
								'name'     => 'label',
								'label'    => 'Size',
								'type'     => 'text',
								'required' => 1,
							),
						),
					),

					/* ---------- Wash packages ---------- */
					array( 'key' => $k( 'tab_wash' ), 'label' => 'Wash packages', 'type' => 'tab', 'placement' => 'top' ),
					array(
						'key'           => $k( 'wash_title' ),
						'name'          => 'wash_title',
						'label'         => 'Heading on the booking page',
						'type'          => 'text',
						'default_value' => 'Car wash',
						'placeholder'   => 'Car wash',
						'wrapper'       => array( 'width' => '50' ),
					),
					$packages( 'wpk', 'wash_packages', 'Wash packages', 'Add wash package', 'e.g. Exterior Only, Express, Express Plus, Superior.', false ),
					$extras( 'wash_extras', 'wash_extras', 'Wash extras', 'Offered with every wash package, e.g. Spray wax, Engine bay clean. Same price for every vehicle size.' ),

					/* ---------- Detailing ---------- */
					array( 'key' => $k( 'tab_detail' ), 'label' => 'Detailing', 'type' => 'tab', 'placement' => 'top' ),
					array(
						'key'           => $k( 'detail_title' ),
						'name'          => 'detail_title',
						'label'         => 'Heading on the booking page',
						'type'          => 'text',
						'default_value' => 'Beyond Your Wash',
						'placeholder'   => 'Beyond Your Wash',
						'wrapper'       => array( 'width' => '50' ),
					),
					$packages( 'dp', 'detail_packages', 'Detailing packages', 'Add detailing package', 'Services beyond the wash, e.g. Paint Correction, Interior Detail, Full Detail, Ceramic Coating.', true ),
				),
			)
		);
	}
);

/* ---------------- Size dropdowns ---------------- */

/** Sizes from the Vehicle sizes tab, read straight from the options table. */
function dlux_bk_size_list() {
	$out = array();
	$n   = (int) get_option( DLUX_BK_PRICES_ID . '_sizes', 0 );
	for ( $i = 0; $i < $n; $i++ ) {
		$label = trim( (string) get_option( DLUX_BK_PRICES_ID . '_sizes_' . $i . '_label', '' ) );
		if ( '' !== $label && ! in_array( $label, $out, true ) ) {
			$out[] = $label;
		}
	}
	return $out;
}

/** Sizes already saved in any price row (wash or detailing). */
function dlux_bk_saved_price_sizes() {
	global $wpdb;
	$out = array();
	foreach ( array( 'wash_packages', 'detail_packages' ) as $field ) {
		$like = $wpdb->esc_like( DLUX_BK_PRICES_ID . '_' . $field . '_' ) . '%' . $wpdb->esc_like( '_prices_' ) . '%' . $wpdb->esc_like( '_size' );
		foreach ( (array) $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT option_value FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) ) as $s ) { // phpcs:ignore
			$s = trim( (string) $s );
			if ( '' !== $s && ! in_array( $s, $out, true ) ) {
				$out[] = $s;
			}
		}
	}
	return $out;
}

/**
 * Choices for both Size dropdowns: the Vehicle sizes list, plus any size
 * already saved in a price row but no longer in the list (marked "removed"),
 * so a saved value is never silently swapped for another size on save.
 */
function dlux_bk_size_choices( $field ) {
	$list    = dlux_bk_size_list();
	$choices = array();
	foreach ( $list as $s ) {
		$choices[ $s ] = $s;
	}
	foreach ( dlux_bk_saved_price_sizes() as $s ) {
		if ( ! isset( $choices[ $s ] ) ) {
			$choices[ $s ] = $s . ' (removed — choose another size)';
		}
	}
	if ( ! $choices ) {
		$choices = array( '' => 'Add sizes on the Vehicle sizes tab first' );
	}
	$field['choices'] = $choices;
	return $field;
}
add_filter( 'acf/load_field/key=field_dlux_px_wpk_price_size', 'dlux_bk_size_choices' );
add_filter( 'acf/load_field/key=field_dlux_px_dp_price_size', 'dlux_bk_size_choices' );

/* ---------------- Renaming a size ---------------- */

/** Size labels by row position, exactly as saved (used to detect renames). */
function dlux_bk_size_rows_raw() {
	$out = array();
	$n   = (int) get_option( DLUX_BK_PRICES_ID . '_sizes', 0 );
	for ( $i = 0; $i < $n; $i++ ) {
		$out[] = trim( (string) get_option( DLUX_BK_PRICES_ID . '_sizes_' . $i . '_label', '' ) );
	}
	return $out;
}

add_action(
	'acf/save_post',
	function ( $post_id ) {
		if ( DLUX_BK_PRICES_ID === $post_id ) {
			$GLOBALS['dlux_bk_sizes_before'] = dlux_bk_size_rows_raw();
		}
	},
	5 // Before SCF saves.
);

/**
 * When a size is renamed in its row (e.g. "SUV/SW" → "SUV"), update every
 * wash and detailing price row that used the old name, so prices carry over.
 * Only counts as a rename if the old name is gone and the new name is new,
 * so re-ordering sizes never changes anything.
 */
add_action(
	'acf/save_post',
	function ( $post_id ) {
		global $wpdb;
		if ( DLUX_BK_PRICES_ID !== $post_id || ! isset( $GLOBALS['dlux_bk_sizes_before'] ) ) {
			return;
		}
		$before = (array) $GLOBALS['dlux_bk_sizes_before'];
		$after  = dlux_bk_size_rows_raw();
		unset( $GLOBALS['dlux_bk_sizes_before'] );
		if ( count( $before ) !== count( $after ) ) {
			return;
		}
		foreach ( $after as $i => $new ) {
			$old = $before[ $i ];
			if ( '' === $old || '' === $new || $old === $new || in_array( $new, $before, true ) || in_array( $old, $after, true ) ) {
				continue;
			}
			foreach ( array( 'wash_packages', 'detail_packages' ) as $field ) {
				$like  = $wpdb->esc_like( DLUX_BK_PRICES_ID . '_' . $field . '_' ) . '%' . $wpdb->esc_like( '_prices_' ) . '%' . $wpdb->esc_like( '_size' );
				$names = $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value = %s", $like, $old ) ); // phpcs:ignore
				foreach ( (array) $names as $name ) {
					update_option( $name, $new, false );
				}
			}
		}
	},
	20 // After SCF saves.
);

/* ---------------- Reading the prices ---------------- */

/** Price as a positive float, or null. */
function dlux_bk_price( $v ) {
	return ( is_numeric( $v ) && (float) $v > 0 ) ? round( (float) $v, 2 ) : null;
}

/** Unique id within $used. */
function dlux_bk_unique_id( $base, array &$used ) {
	$id = $base;
	$i  = 2;
	while ( isset( $used[ $id ] ) ) {
		$id = $base . '-' . $i++;
	}
	$used[ $id ] = true;
	return $id;
}

/** Extras rows → list of { id, name, price }. */
function dlux_bk_read_extras( $rows, $prefix, array &$used ) {
	$out = array();
	foreach ( dlux_bk_as_rows( $rows ) as $r ) {
		$name  = trim( (string) ( $r['name'] ?? '' ) );
		$price = dlux_bk_price( $r['price'] ?? null );
		if ( '' === $name || null === $price ) {
			continue;
		}
		$out[] = array(
			'id'    => dlux_bk_unique_id( $prefix . sanitize_title( $name ), $used ),
			'name'  => $name,
			'price' => $price,
		);
	}
	return $out;
}

function dlux_bk_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ) ) );
}

/**
 * Package rows (wash or detailing, same fields) → packages.
 *
 * @param array      $rows          Repeater rows.
 * @param string     $group         'wash' or 'detail'.
 * @param array      $sizes         Allowed sizes; empty = any size.
 * @param array|null $shared_extras Extras for every package (wash), or null to read each package's own.
 * @param array      $used          Ids already used.
 * @param array      $warnings      Collects admin warnings.
 */
function dlux_bk_read_packages( $rows, $group, array $sizes, $shared_extras, array &$used, array &$warnings ) {
	$prefix = 'wash' === $group ? 'w-' : 'd-';
	$out    = array();
	foreach ( dlux_bk_as_rows( $rows ) as $p ) {
		$name = trim( (string) ( $p['name'] ?? '' ) );
		if ( '' === $name ) {
			continue;
		}
		$prices = array();
		foreach ( dlux_bk_as_rows( $p['prices'] ?? null ) as $row ) {
			$size  = trim( (string) ( $row['size'] ?? '' ) );
			$price = dlux_bk_price( $row['price'] ?? null );
			if ( '' === $size || null === $price ) {
				continue;
			}
			if ( $sizes && ! in_array( $size, $sizes, true ) ) {
				$warnings[] = '"' . $name . '" has a price for "' . $size . '", which is not in the Vehicle sizes list, so it is not offered.';
				continue;
			}
			$prices[ $size ] = $price;
		}
		$id    = dlux_bk_unique_id( $prefix . sanitize_title( $name ), $used );
		$out[] = array(
			'id'       => $id,
			'group'    => $group,
			'name'     => $name,
			'desc'     => trim( (string) ( $p['subtitle'] ?? '' ) ),
			'mins'     => max( 0, (int) ( $p['duration'] ?? 0 ) ),
			'features' => dlux_bk_lines( $p['features'] ?? '' ),
			'choose_title' => trim( (string) ( $p['choose_title'] ?? '' ) ),
			'choose'   => array_values( array_filter( array_map( function ( $o ) { return trim( preg_replace( '/\s*\(or\)\s*$/i', '', $o ) ); }, dlux_bk_lines( $p['choose_from'] ?? '' ) ) ) ), // "(or)" is added by the design, not typed.
			'note'     => trim( (string) ( $p['note'] ?? '' ) ),
			'featured' => ! empty( $p['is_featured'] ),
			'badge'    => ! empty( $p['is_featured'] ) ? trim( (string) ( $p['badge'] ?? '' ) ) : '',
			'prices'   => $prices,
			'image'        => (int) ( is_array( $p['image'] ?? null ) ? ( $p['image']['ID'] ?? 0 ) : ( $p['image'] ?? 0 ) ),
			'before_image' => (int) ( is_array( $p['before_image'] ?? null ) ? ( $p['before_image']['ID'] ?? 0 ) : ( $p['before_image'] ?? 0 ) ),
			'after_image'  => (int) ( is_array( $p['after_image'] ?? null ) ? ( $p['after_image']['ID'] ?? 0 ) : ( $p['after_image'] ?? 0 ) ),
			'price_label'  => trim( (string) ( $p['price_label'] ?? '' ) ),
			'long_desc'    => trim( (string) ( $p['long_description'] ?? '' ) ),
			'ideal_for'    => trim( (string) ( $p['ideal_for'] ?? '' ) ),
			'extras'   => null !== $shared_extras ? $shared_extras : dlux_bk_read_extras( $p['extras'] ?? null, 'dx-' . substr( $id, 2 ) . '-', $used ),
		);
	}
	return $out;
}

/**
 * The full price list.
 *
 * @return array {
 *   classes:  string[]  vehicle sizes in the Vehicle sizes order,
 *   groups:   array[]   { id: wash|detail, label, extras[] (shared, for display), packages[] },
 *   packages: array[]   every package: { id, group, name, desc, mins, features[], choose_title, choose[], note, featured, badge, prices{size:amount}, extras[] },
 *   warnings: string[]  setup problems, shown on Bookings › Prices & setup,
 * }
 */
function dlux_bk_menu() {
	static $menu = null;
	if ( null !== $menu ) {
		return $menu;
	}
	$used     = array();
	$warnings = array();
	$sizes    = array();
	foreach ( dlux_bk_as_rows( dlux_bk_price_field( 'sizes' ) ) as $r ) {
		$label = trim( (string) ( $r['label'] ?? '' ) );
		if ( '' !== $label && ! in_array( $label, $sizes, true ) ) {
			$sizes[] = $label;
		}
	}

	$wash_extras = dlux_bk_read_extras( dlux_bk_price_field( 'wash_extras' ), 'wx-', $used );
	$wash        = dlux_bk_read_packages( dlux_bk_price_field( 'wash_packages' ), 'wash', $sizes, $wash_extras, $used, $warnings );
	$detail      = dlux_bk_read_packages( dlux_bk_price_field( 'detail_packages' ), 'detail', $sizes, null, $used, $warnings );

	foreach ( array_merge( $wash, $detail ) as $p ) {
		if ( ! $p['prices'] ) {
			$warnings[] = '"' . $p['name'] . '" has no prices, so it is not offered.';
		}
	}
	$has_prices = function ( $p ) {
		return ! empty( $p['prices'] );
	};
	$wash   = array_values( array_filter( $wash, $has_prices ) );
	$detail = array_values( array_filter( $detail, $has_prices ) );

	// Sizes in list order; if the list is empty, sizes in the order they appear.
	$classes = $sizes;
	if ( ! $classes ) {
		foreach ( array_merge( $wash, $detail ) as $p ) {
			foreach ( array_keys( $p['prices'] ) as $s ) {
				if ( ! in_array( $s, $classes, true ) ) {
					$classes[] = $s;
				}
			}
		}
	}

	$groups = array();
	if ( $wash ) {
		$title    = trim( (string) dlux_bk_price_field( 'wash_title' ) );
		$groups[] = array(
			'id'       => 'wash',
			'label'    => '' !== $title ? $title : 'Car wash',
			'extras'   => $wash_extras,
			'packages' => $wash,
		);
	}
	if ( $detail ) {
		$title    = trim( (string) dlux_bk_price_field( 'detail_title' ) );
		$groups[] = array(
			'id'       => 'detail',
			'label'    => '' !== $title ? $title : 'Beyond Your Wash',
			'extras'   => array(),
			'packages' => $detail,
		);
	}

	$menu = apply_filters(
		'dlux_booking_menu',
		array(
			'classes'  => $classes,
			'groups'   => $groups,
			'packages' => array_merge( $wash, $detail ),
			'warnings' => array_values( array_unique( $warnings ) ),
		)
	);
	return $menu;
}

function dlux_bk_find_package( $id ) {
	foreach ( dlux_bk_menu()['packages'] as $p ) {
		if ( $p['id'] === $id ) {
			return $p;
		}
	}
	return null;
}

function dlux_bk_group_label( $group_id ) {
	foreach ( dlux_bk_menu()['groups'] as $g ) {
		if ( $g['id'] === $group_id ) {
			return $g['label'];
		}
	}
	return 'detail' === $group_id ? 'Beyond Your Wash' : 'Car wash';
}

/* ---------------- Converting older price data ---------------- */

/**
 * Old layout (homepage Pricing section, and Booking Prices in 4.1):
 * size rows, each with its own package rows. Turns it into the new layout:
 * a size list + one row per package with a price per size.
 *
 * @param array $size_rows [ { label, packages: [ { name, subtitle, duration, price, features, is_featured, badge } ] } ]
 * @return array [ 'sizes' => rows, 'packages' => rows ]
 */
function dlux_bk_convert_size_rows( $size_rows ) {
	$sizes    = array();
	$packages = array();
	foreach ( dlux_bk_as_rows( $size_rows ) as $size ) {
		$label = trim( (string) ( $size['label'] ?? '' ) );
		if ( '' === $label ) {
			continue;
		}
		if ( ! in_array( $label, $sizes, true ) ) {
			$sizes[] = $label;
		}
		foreach ( dlux_bk_as_rows( $size['packages'] ?? null ) as $p ) {
			$name = trim( (string) ( $p['name'] ?? '' ) );
			if ( '' === $name ) {
				continue;
			}
			$key = sanitize_title( $name );
			if ( ! isset( $packages[ $key ] ) ) {
				$packages[ $key ] = array(
					'name'        => $name,
					'subtitle'    => '',
					'duration'    => '',
					'features'    => '',
					'is_featured' => 0,
					'badge'       => '',
					'prices'      => array(),
				);
			}
			// First non-empty value wins for the text fields.
			foreach ( array( 'subtitle', 'duration', 'features', 'badge' ) as $f ) {
				if ( '' === (string) $packages[ $key ][ $f ] && isset( $p[ $f ] ) && is_scalar( $p[ $f ] ) ) {
					$packages[ $key ][ $f ] = (string) $p[ $f ];
				}
			}
			if ( ! empty( $p['is_featured'] ) ) {
				$packages[ $key ]['is_featured'] = 1;
			}
			if ( null !== dlux_bk_price( $p['price'] ?? null ) ) {
				$packages[ $key ]['prices'][] = array(
					'size'  => $label,
					'price' => $p['price'],
				);
			}
		}
	}
	return array(
		'sizes'    => array_map(
			function ( $s ) {
				return array( 'label' => $s );
			},
			$sizes
		),
		'packages' => array_values( $packages ),
	);
}

/** 4.1 Booking Prices data, read straight from wp_options (its fields no longer exist). */
function dlux_bk_read_v41_wash() {
	$o    = DLUX_BK_PRICES_ID . '_vehicle_sizes';
	$rows = array();
	$n    = (int) get_option( $o, 0 );
	for ( $i = 0; $i < $n; $i++ ) {
		$pk = array();
		$m  = (int) get_option( $o . '_' . $i . '_packages', 0 );
		for ( $j = 0; $j < $m; $j++ ) {
			$row = array();
			foreach ( array( 'name', 'subtitle', 'duration', 'price', 'features', 'is_featured', 'badge' ) as $f ) {
				$row[ $f ] = get_option( $o . '_' . $i . '_packages_' . $j . '_' . $f, '' );
			}
			$pk[] = $row;
		}
		$rows[] = array(
			'label'    => get_option( $o . '_' . $i . '_label', '' ),
			'packages' => $pk,
		);
	}
	return $rows;
}

/** Remove the 4.1 option rows once they have been converted. */
function dlux_bk_delete_v41_wash() {
	global $wpdb;
	foreach ( array( DLUX_BK_PRICES_ID . '_vehicle_sizes', '_' . DLUX_BK_PRICES_ID . '_vehicle_sizes' ) as $base ) {
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name = %s OR option_name LIKE %s", $base, $wpdb->esc_like( $base . '_' ) . '%' ) ); // phpcs:ignore
	}
	wp_cache_delete( 'alloptions', 'options' );
	wp_cache_delete( 'notoptions', 'options' );
}

/**
 * Runs once, on the first normal page load after updating:
 *  - from 4.1: converts the size-first wash prices into the new layout;
 *  - from 4.0: copies the homepage Pricing section and old Add-ons once.
 * Never overwrites prices that are already in the new layout.
 */
add_action(
	'init',
	function () {
		if ( ! did_action( 'acf/init' ) || ! function_exists( 'acf_get_field' ) || ! acf_get_field( 'field_dlux_px_wpk' ) ) {
			return;
		}
		$v42_done = get_option( 'dlux_bk_prices_v42' );
		$v40_done = get_option( 'dlux_bk_prices_imported' );
		if ( $v42_done && $v40_done ) {
			return;
		}
		update_option( 'dlux_bk_prices_v42', 1, false );
		update_option( 'dlux_bk_prices_imported', 1, false );

		$has_new = (int) get_option( DLUX_BK_PRICES_ID . '_wash_packages', 0 ) > 0 || (int) get_option( DLUX_BK_PRICES_ID . '_sizes', 0 ) > 0;
		if ( $has_new ) {
			return;
		}

		$source = array();
		if ( (int) get_option( DLUX_BK_PRICES_ID . '_vehicle_sizes', 0 ) > 0 ) {
			$source = dlux_bk_read_v41_wash(); // Updating from 4.1.
		} elseif ( ! $v40_done ) {
			// Updating from 4.0: one-time copy from the homepage Pricing section.
			$page = (int) get_option( 'options_dlux_bk_prices_page', 0 );
			$page = $page ? $page : (int) get_option( 'page_on_front' );
			if ( $page ) {
				foreach ( dlux_bk_as_rows( get_field( 'page_sections', $page ) ) as $section ) {
					if ( 'pricing' === ( $section['acf_fc_layout'] ?? '' ) ) {
						$source = dlux_bk_as_rows( $section['vehicle_sizes'] ?? null );
						break;
					}
				}
			}
		}

		$new = dlux_bk_convert_size_rows( $source );

		// Detailing rows may use sizes the wash didn't have: add them to the list too.
		$labels = wp_list_pluck( $new['sizes'], 'label' );
		foreach ( dlux_bk_saved_price_sizes() as $s ) {
			if ( ! in_array( $s, $labels, true ) ) {
				$labels[]       = $s;
				$new['sizes'][] = array( 'label' => $s );
			}
		}

		if ( $new['sizes'] ) {
			update_field( 'field_dlux_px_sizes', $new['sizes'], DLUX_BK_PRICES_ID );
		}
		if ( $new['packages'] ) {
			update_field( 'field_dlux_px_wpk', $new['packages'], DLUX_BK_PRICES_ID );
		}

		// Delete the 4.1 rows only once the new rows are confirmed saved.
		if ( (int) get_option( DLUX_BK_PRICES_ID . '_vehicle_sizes', 0 ) > 0 && (int) get_option( DLUX_BK_PRICES_ID . '_wash_packages', 0 ) === count( $new['packages'] ) && count( $new['packages'] ) > 0 ) {
			dlux_bk_delete_v41_wash();
		}

		// 4.0 Add-ons → Wash extras (only if there are no wash extras yet).
		if ( ! $v40_done && 0 === (int) get_option( DLUX_BK_PRICES_ID . '_wash_extras', 0 ) ) {
			$extras = array();
			$n      = (int) get_option( 'options_dlux_bk_addons', 0 );
			for ( $i = 0; $i < $n; $i++ ) {
				$name = (string) get_option( 'options_dlux_bk_addons_' . $i . '_name', '' );
				if ( '' !== trim( $name ) ) {
					$extras[] = array(
						'name'  => $name,
						'price' => get_option( 'options_dlux_bk_addons_' . $i . '_price', '' ),
					);
				}
			}
			if ( $extras ) {
				update_field( 'field_dlux_px_wash_extras', $extras, DLUX_BK_PRICES_ID );
			}
		}
	},
	30
);

/* One-time: rename the saved booking-page heading "Detailing" to "Beyond Your Wash" (custom headings are left alone). */
add_action(
	'init',
	function () {
		if ( get_option( 'dlux_bk_detail_title_421' ) ) {
			return;
		}
		update_option( 'dlux_bk_detail_title_421', 1, false );
		if ( 'Detailing' === get_option( DLUX_BK_PRICES_ID . '_detail_title' ) ) {
			update_option( DLUX_BK_PRICES_ID . '_detail_title', 'Beyond Your Wash', false );
		}
	},
	31
);
