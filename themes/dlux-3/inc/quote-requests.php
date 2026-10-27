<?php
/**
 * Quote requests: "Quote requests" post type (SCF fields) + the two quote emails
 * (shop + customer) in the DLux booking-email design.
 * Submissions come from the Quote form section (inc/quote.php).
 */
if ( ! defined( 'ABSPATH' ) ) exit;

/* ---------------- Post type ---------------- */

add_action( 'init', function () {
	register_post_type( 'dlux_quote', [
		'labels'          => [
			'name'               => __( 'Quote requests', 'dlux' ),
			'singular_name'      => __( 'Quote request', 'dlux' ),
			'menu_name'          => __( 'Quote requests', 'dlux' ),
			'all_items'          => __( 'All quote requests', 'dlux' ),
			'edit_item'          => __( 'Quote request', 'dlux' ),
			'search_items'       => __( 'Search quote requests', 'dlux' ),
			'not_found'          => __( 'No quote requests yet.', 'dlux' ),
			'not_found_in_trash' => __( 'No quote requests in the Trash.', 'dlux' ),
		],
		'public'          => false,
		'show_ui'         => true,
		'show_in_menu'    => true,
		'show_in_rest'    => false,
		'menu_position'   => 26,
		'menu_icon'       => 'dashicons-email-alt',
		'supports'        => [ 'title' ],
		'capability_type' => 'post',
		'map_meta_cap'    => true,
		'capabilities'    => [ 'create_posts' => 'do_not_allow' ], // only created by the form
	] );
} );

/** Status choices. */
function dlux_quote_statuses() {
	return [
		'new'       => __( 'New', 'dlux' ),
		'contacted' => __( 'Contacted', 'dlux' ),
		'quoted'    => __( 'Quote sent', 'dlux' ),
		'booked'    => __( 'Booked', 'dlux' ),
		'closed'    => __( 'Closed / not going ahead', 'dlux' ),
	];
}

/* ---------------- SCF fields ---------------- */

add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) return;
	$f = function ( $name, $label, $type, $extra = [] ) {
		return array_merge( [ 'key' => 'field_dlux_q_' . $name, 'name' => 'q_' . $name, 'label' => $label, 'type' => $type ], $extra );
	};
	acf_add_local_field_group( [
		'key'      => 'group_dlux_quote_request',
		'title'    => __( 'Quote request', 'dlux' ),
		'position' => 'acf_after_title',
		'style'    => 'default',
		'location' => [ [ [ 'param' => 'post_type', 'operator' => '==', 'value' => 'dlux_quote' ] ] ],
		'fields'   => [
			$f( 'status', __( 'Status', 'dlux' ), 'select', [ 'choices' => dlux_quote_statuses(), 'default_value' => 'new', 'wrapper' => [ 'width' => '30' ] ] ),
			$f( 'interests', __( 'Interested in', 'dlux' ), 'text', [ 'wrapper' => [ 'width' => '70' ] ] ),
			$f( 'name', __( 'Name', 'dlux' ), 'text', [ 'wrapper' => [ 'width' => '34' ] ] ),
			$f( 'phone', __( 'Mobile', 'dlux' ), 'text', [ 'wrapper' => [ 'width' => '33' ] ] ),
			$f( 'email', __( 'Email', 'dlux' ), 'email', [ 'wrapper' => [ 'width' => '33' ] ] ),
			$f( 'car', __( 'Car make, model & year', 'dlux' ), 'text' ),
			$f( 'message', __( 'Message', 'dlux' ), 'textarea', [ 'rows' => 5, 'new_lines' => 'br' ] ),
			$f( 'notes', __( 'Internal notes', 'dlux' ), 'textarea', [ 'rows' => 3, 'instructions' => __( 'Only visible here. Not sent to the customer.', 'dlux' ) ] ),
			$f( 'page', __( 'Sent from', 'dlux' ), 'url', [ 'wrapper' => [ 'width' => '50' ] ] ),
			$f( 'emailed', __( 'Emails sent', 'dlux' ), 'text', [ 'readonly' => 1, 'wrapper' => [ 'width' => '50' ] ] ),
		],
	] );
} );

/** Save one field: SCF when available (keeps the field reference), else plain meta. */
function dlux_quote_set( $post_id, $name, $value ) {
	if ( function_exists( 'update_field' ) ) {
		update_field( 'field_dlux_q_' . $name, $value, $post_id );
	} else {
		update_post_meta( $post_id, 'q_' . $name, $value );
	}
}

function dlux_quote_get( $post_id, $name ) {
	return (string) get_post_meta( $post_id, 'q_' . $name, true );
}

/* ---------------- Admin list ---------------- */

add_filter( 'manage_dlux_quote_posts_columns', function () {
	return [
		'cb'          => '<input type="checkbox">',
		'title'       => __( 'Request', 'dlux' ),
		'q_interests' => __( 'Interested in', 'dlux' ),
		'q_contact'   => __( 'Contact', 'dlux' ),
		'q_car'       => __( 'Car', 'dlux' ),
		'q_status'    => __( 'Status', 'dlux' ),
		'date'        => __( 'Received', 'dlux' ),
	];
} );

add_action( 'manage_dlux_quote_posts_custom_column', function ( $col, $id ) {
	switch ( $col ) {
		case 'q_interests':
			echo esc_html( dlux_quote_get( $id, 'interests' ) ?: '—' );
			break;
		case 'q_contact':
			$p = dlux_quote_get( $id, 'phone' );
			$e = dlux_quote_get( $id, 'email' );
			if ( $p ) echo '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $p ) ) . '">' . esc_html( $p ) . '</a><br>';
			if ( $e ) echo '<a href="mailto:' . esc_attr( $e ) . '">' . esc_html( $e ) . '</a>';
			break;
		case 'q_car':
			echo esc_html( dlux_quote_get( $id, 'car' ) ?: '—' );
			break;
		case 'q_status':
			$s = dlux_quote_get( $id, 'status' ) ?: 'new';
			$l = dlux_quote_statuses()[ $s ] ?? $s;
			echo '<span class="dlux-q-status dlux-q-status--' . esc_attr( $s ) . '">' . esc_html( $l ) . '</span>';
			break;
	}
}, 10, 2 );

add_action( 'admin_head', function () {
	$s = get_current_screen();
	if ( ! $s || 'dlux_quote' !== $s->post_type ) return;
	echo '<style>.dlux-q-status{display:inline-block;padding:2px 8px;border-radius:10px;background:#f0f0f1;font-size:12px;font-weight:600}.dlux-q-status--new{background:#fde7ec;color:#b3123a}.dlux-q-status--booked{background:#dcfce7;color:#166534}.dlux-q-status--quoted{background:#e0f2fe;color:#075985}.column-q_status{width:120px}</style>';
} );

/* "New" count bubble in the menu. */
add_action( 'admin_menu', function () {
	global $menu;
	$q = new WP_Query( [ 'post_type' => 'dlux_quote', 'post_status' => 'publish', 'fields' => 'ids', 'posts_per_page' => 1, 'no_found_rows' => false, 'meta_key' => 'q_status', 'meta_value' => 'new' ] ); // phpcs:ignore
	$n = (int) $q->found_posts;
	if ( ! $n ) return;
	foreach ( (array) $menu as $i => $item ) {
		if ( 'edit.php?post_type=dlux_quote' === ( $item[2] ?? '' ) ) {
			$menu[ $i ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . $n . '</span></span>'; // phpcs:ignore
			break;
		}
	}
}, 999 );

/* ---------------- Emails (DLux booking-email design) ---------------- */

/**
 * @param array  $q   id, name, phone, email, car, message, interests[], page
 * @param string $for 'admin' or 'customer'
 */
function dlux_quote_email_html( array $q, $for = 'admin' ) {
	$e        = function ( $s ) { return esc_html( (string) $s ); };
	$hf       = "font-family:'Barlow Condensed','Arial Narrow',Arial,Helvetica,sans-serif;";
	$bf       = "font-family:'Barlow',Arial,Helvetica,sans-serif;";
	$customer = 'customer' === $for;
	$ref      = '#Q-' . $q['id'];
	$first    = trim( (string) strtok( $q['name'], ' ' ) );
	$when     = wp_date( 'l j F, g:i A' );
	$shop_ph  = (string) ( function_exists( 'dlux_option' ) ? dlux_option( 'phone', '0410 111 190' ) : '0410 111 190' );
	$shop_tel = preg_replace( '/[^0-9+]/', '', $shop_ph );
	$e164     = function_exists( 'dlux_bk_sms_number' ) ? dlux_bk_sms_number( $q['phone'] ) : preg_replace( '/[^0-9+]/', '', $q['phone'] );
	$parts    = preg_split( '/\s+/', trim( $q['name'] ), -1, PREG_SPLIT_NO_EMPTY );
	$initials = $parts ? mb_strtoupper( mb_substr( $parts[0], 0, 1 ) . ( count( $parts ) > 1 ? mb_substr( end( $parts ), 0, 1 ) : '' ) ) : '';
	$title    = $customer ? __( 'Quote request received', 'dlux' ) : __( 'New quote request', 'dlux' );
	$pre      = $customer
		? sprintf( __( 'Thanks %s, we’ve got your request and will reply within one business day.', 'dlux' ), $first ?: __( 'for asking', 'dlux' ) )
		: trim( implode( ', ', $q['interests'] ) . ( $q['car'] ? ' · ' . $q['car'] : '' ) . ' · ' . $q['name'] );
	$edit     = admin_url( 'post.php?post=' . (int) $q['id'] . '&action=edit' );
	$protos   = [ 'http', 'https', 'mailto', 'tel', 'sms' ];
	$btn      = function ( $href, $icon, $label ) use ( $bf, $protos ) {
		return '<a href="' . esc_url( $href, $protos ) . '" class="b" style="display:block;background:#000000;color:#ffffff;' . $bf . 'font-size:15px;font-weight:700;text-align:center;padding:12px 0;border-radius:10px;"><img src="https://img.icons8.com/ios-filled/50/ffffff/' . esc_attr( $icon ) . '.png" width="16" height="16" alt="" style="vertical-align:middle;border:0;margin-right:6px;"><span style="vertical-align:middle;">' . esc_html( $label ) . '</span></a>';
	};
	ob_start();
	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light only">
<title><?php echo $e( $title . ' ' . $ref ); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  body { margin:0; padding:0; background:#e9e9eb; }
  a { text-decoration:none; }
  .h { font-family:'Barlow Condensed','Arial Narrow',Arial,Helvetica,sans-serif; }
  .b { font-family:'Barlow',Arial,Helvetica,sans-serif; }
  @media (max-width:520px) {
    .px { padding-left:20px !important; padding-right:20px !important; }
    .stack { display:block !important; width:100% !important; }
    .stack-gap { padding:0 0 10px 0 !important; }
  }
</style>
</head>
<body style="margin:0;padding:0;background:#e9e9eb;">
<div style="display:none;max-height:0;overflow:hidden;"><?php echo $e( $pre ); ?></div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#e9e9eb;">
<tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:580px;">

<!-- HEADER -->
<tr><td style="background:#000000;border-radius:16px 16px 0 0;padding:22px 30px 18px;" class="px">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
    <td class="h" style="<?php echo $hf; ?>font-size:30px;font-weight:800;color:#ffffff;letter-spacing:1px;line-height:1;">DLUXWASH<span style="color:#D81E4A;">.</span></td>
    <td align="right">
      <span class="b" style="<?php echo $bf; ?>font-size:12px;color:#b9b9bd;"><?php esc_html_e( 'Request ref', 'dlux' ); ?></span><br>
      <span class="h" style="<?php echo $hf; ?>font-size:20px;font-weight:800;color:#ffffff;letter-spacing:1px;"><?php echo $e( $ref ); ?></span>
    </td>
  </tr></table>
</td></tr>
<tr><td style="background:#D81E4A;height:4px;line-height:4px;font-size:0;">&nbsp;</td></tr>

<!-- HERO -->
<tr><td style="background:#141414;padding:30px 30px 30px;" class="px">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
    <td valign="middle" style="padding-right:12px;">
      <div style="width:36px;height:36px;line-height:36px;border-radius:18px;background:#D81E4A;color:#ffffff;text-align:center;font-family:Arial,sans-serif;font-size:20px;font-weight:bold;"><?php echo $customer ? '&#10003;' : '&#9993;'; ?></div>
    </td>
    <td valign="middle"><h1 class="h" style="margin:0;<?php echo $hf; ?>font-size:34px;font-weight:800;line-height:1;color:#ffffff;"><?php echo $e( $title ); ?></h1></td>
  </tr></table>
  <p class="b" style="margin:18px 0 0;padding-top:18px;border-top:1px solid #333336;<?php echo $bf; ?>font-size:15px;line-height:1.55;color:#d4d4d8;">
    <?php
    if ( $customer ) {
      echo $e( sprintf( __( 'Thanks %s! We’ve received your quote request and will get back to you within one business day.', 'dlux' ), $first ?: '' ) );
    } else {
      echo $e( sprintf( __( 'Received %s from the quote form.', 'dlux' ), $when ) );
    }
    ?>
  </p>
</td></tr>

<!-- REQUEST CARD -->
<tr><td style="background:#ffffff;padding:28px 30px 8px;" class="px">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:2px solid #000000;border-radius:14px;">
    <tr><td style="padding:20px 22px 18px;">
      <p class="b" style="margin:0 0 10px;<?php echo $bf; ?>font-size:13px;font-weight:600;color:#55555b;"><?php echo $customer ? esc_html__( 'You asked about', 'dlux' ) : esc_html__( 'Interested in', 'dlux' ); ?></p>
      <?php if ( $q['interests'] ) : ?>
        <?php foreach ( $q['interests'] as $i ) : ?><span class="b" style="display:inline-block;margin:0 6px 8px 0;background:#fde7ec;color:#D81E4A;<?php echo $bf; ?>font-size:13px;font-weight:700;padding:6px 12px;border-radius:20px;"><?php echo $e( $i ); ?></span><?php endforeach; ?>
      <?php else : ?>
        <p class="h" style="margin:0;<?php echo $hf; ?>font-size:24px;font-weight:800;color:#000000;"><?php esc_html_e( 'General enquiry', 'dlux' ); ?></p>
      <?php endif; ?>
    </td></tr>
    <?php if ( $q['car'] ) : ?>
    <tr><td style="background:#f4f4f5;border-top:2px solid #000000;padding:14px 22px;<?php echo $q['message'] ? '' : 'border-radius:0 0 12px 12px;'; ?>">
      <p class="b" style="margin:0;<?php echo $bf; ?>font-size:13px;font-weight:600;color:#55555b;"><?php esc_html_e( 'Car', 'dlux' ); ?></p>
      <p class="h" style="margin:2px 0 0;<?php echo $hf; ?>font-size:26px;font-weight:800;line-height:1.1;color:#000000;"><?php echo $e( $q['car'] ); ?></p>
    </td></tr>
    <?php endif; ?>
    <?php if ( $q['message'] ) : ?>
    <tr><td style="border-top:<?php echo $q['car'] ? '1px solid #dcdce0' : '2px solid #000000'; ?>;padding:14px 22px 18px;">
      <p class="b" style="margin:0 0 4px;<?php echo $bf; ?>font-size:13px;font-weight:600;color:#55555b;"><?php esc_html_e( 'Message', 'dlux' ); ?></p>
      <p class="b" style="margin:0;<?php echo $bf; ?>font-size:15px;line-height:1.55;color:#111111;"><?php echo nl2br( $e( $q['message'] ) ); ?></p>
    </td></tr>
    <?php endif; ?>
  </table>
</td></tr>

<!-- CUSTOMER -->
<tr><td style="background:#ffffff;padding:20px 30px 8px;" class="px">
  <?php if ( $customer ) : ?><p class="b" style="margin:0 0 10px;<?php echo $bf; ?>font-size:13px;font-weight:600;color:#55555b;"><?php esc_html_e( 'Your details', 'dlux' ); ?></p><?php endif; ?>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
    <td width="48" valign="middle"><div class="h" style="width:44px;height:44px;line-height:44px;border-radius:22px;background:#D81E4A;color:#ffffff;text-align:center;<?php echo $hf; ?>font-size:20px;font-weight:800;"><?php echo $e( $initials ); ?></div></td>
    <td valign="middle" style="padding-left:12px;">
      <p class="b" style="margin:0;<?php echo $bf; ?>font-size:18px;font-weight:700;color:#000000;"><?php echo $e( $q['name'] ); ?></p>
      <p class="b" style="margin:2px 0 0;<?php echo $bf; ?>font-size:14px;color:#55555b;"><?php echo implode( ' &nbsp;|&nbsp; ', array_filter( [ $e( $q['phone'] ), $e( $q['email'] ) ] ) ); // phpcs:ignore ?></p>
    </td>
  </tr></table>
  <?php
  if ( ! $customer ) :
    $b = array_filter( [
      $e164 ? $btn( 'tel:' . $e164, 'phone', __( 'Call', 'dlux' ) ) : '',
      $e164 ? $btn( 'sms:' . $e164, 'sms', __( 'Text', 'dlux' ) ) : '',
      $q['email'] ? $btn( 'mailto:' . $q['email'], 'new-post', __( 'Email', 'dlux' ) ) : '',
    ] );
    if ( $b ) :
      $b = array_values( $b );
      $n = count( $b );
      ?>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:14px;"><tr>
    <?php foreach ( $b as $i => $html ) : ?><td width="<?php echo esc_attr( floor( 100 / $n ) ); ?>%" style="<?php echo 1 === $n ? '' : ( 0 === $i ? 'padding-right:5px;' : ( $n - 1 === $i ? 'padding-left:5px;' : 'padding:0 3px;' ) ); ?>"><?php echo $html; // phpcs:ignore ?></td><?php endforeach; ?>
  </tr></table>
  <?php endif; endif; ?>
</td></tr>

<!-- CTA -->
<tr><td style="background:#ffffff;padding:20px 30px 30px;border-radius:0 0 16px 16px;" class="px">
  <?php if ( $customer ) : ?>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#D81E4A;border-radius:14px;"><tr>
    <td style="padding:18px 22px;">
      <p class="b" style="margin:0;<?php echo $bf; ?>font-size:16px;font-weight:700;color:#ffffff;"><?php esc_html_e( 'Got photos?', 'dlux' ); ?></p>
      <p class="b" style="margin:2px 0 0;<?php echo $bf; ?>font-size:13px;color:#ffd6df;"><?php esc_html_e( 'Photos help us quote dents, wheels and wraps. Text them through and quote your request ref.', 'dlux' ); ?></p>
    </td>
  </tr></table>
  <a href="<?php echo esc_url( 'sms:' . $shop_tel, $protos ); ?>" class="b" style="display:block;margin-top:14px;background:#000000;color:#ffffff;<?php echo $bf; ?>font-size:17px;font-weight:700;text-align:center;padding:17px 0;border-radius:12px;"><?php echo $e( sprintf( __( 'Text photos to %s', 'dlux' ), $shop_ph ) ); ?></a>
  <?php else : ?>
  <a href="<?php echo esc_url( $edit ); ?>" class="b" style="display:block;background:#D81E4A;color:#ffffff;<?php echo $bf; ?>font-size:17px;font-weight:700;text-align:center;padding:17px 0;border-radius:12px;"><?php esc_html_e( 'Open request', 'dlux' ); ?></a>
  <?php if ( $q['page'] ) : ?><p class="b" style="margin:10px 0 0;text-align:center;<?php echo $bf; ?>font-size:12px;color:#8a8a90;"><?php echo $e( sprintf( __( 'Sent from %s', 'dlux' ), $q['page'] ) ); ?></p><?php endif; ?>
  <?php endif; ?>
</td></tr>

<!-- SIGNATURE -->
<tr><td style="padding:22px 0 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#000000;border-radius:16px;">
  <tr><td style="padding:28px 30px 10px;" class="px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
      <td valign="top">
        <p class="h" style="margin:0;<?php echo $hf; ?>font-size:26px;font-weight:800;color:#ffffff;line-height:1;">DLUXWASH<span style="color:#D81E4A;">.</span></p>
        <p class="b" style="margin:4px 0 0;<?php echo $bf; ?>font-size:13px;color:#b9b9bd;">Hand Car Wash &amp; Detailing</p>
      </td>
      <td align="right" valign="top"><span class="b" style="display:inline-block;border:1px solid #D81E4A;color:#D81E4A;<?php echo $bf; ?>font-size:12px;font-weight:700;padding:5px 10px;border-radius:20px;">Trusted since 2010</span></td>
    </tr></table>
  </td></tr>
  <tr><td style="padding:12px 30px 0;" class="px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="b" style="<?php echo $bf; ?>font-size:14px;line-height:1.5;"><tr>
      <td class="stack stack-gap" width="50%" valign="top" style="padding-right:12px;">
        <p style="margin:0 0 2px;color:#D81E4A;font-weight:700;font-size:12px;"><img src="https://img.icons8.com/ios-filled/50/D81E4A/marker.png" width="16" height="16" alt="" style="vertical-align:middle;border:0;margin-right:6px;"><span style="vertical-align:middle;">Yarraville wash</span></p>
        <a href="https://www.google.com/maps/search/?api=1&amp;query=DLux+Hand+Car+Wash+321+Williamstown+Road+Yarraville+VIC+3013" style="color:#ffffff;text-decoration:underline;">321 Williamstown Road<br>Yarraville VIC 3013</a>
        <p style="margin:6px 0 0;color:#b9b9bd;">Open 7 days, 9am to 5pm</p>
      </td>
      <td class="stack" width="50%" valign="top">
        <p style="margin:0 0 2px;color:#D81E4A;font-weight:700;font-size:12px;">Truganina studio</p>
        <p style="margin:0;color:#ffffff;">Ceramic coating, PPF,<br>window tint &amp; wraps</p>
        <p style="margin:6px 0 0;color:#b9b9bd;">By inquiry</p>
      </td>
    </tr></table>
  </td></tr>
  <tr><td style="padding:18px 30px 0;" class="px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td width="50%" style="padding-right:5px;"><a href="tel:0410111190" title="Call DLux" style="display:block;background:#D81E4A;border-radius:10px;text-align:center;padding:13px 0;"><img src="https://img.icons8.com/ios-filled/50/ffffff/phone.png" width="22" height="22" alt="Call DLux" style="display:inline-block;vertical-align:middle;border:0;"></a></td><td width="50%" style="padding-left:5px;"><a href="mailto:info@dluxwash.com.au" title="Email DLux" style="display:block;background:#D81E4A;border-radius:10px;text-align:center;padding:13px 0;"><img src="https://img.icons8.com/ios-filled/50/ffffff/new-post.png" width="22" height="22" alt="Email DLux" style="display:inline-block;vertical-align:middle;border:0;"></a></td></tr></table>
  </td></tr>
  <tr><td style="padding:20px 30px 18px;" class="px">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="b" style="<?php echo $bf; ?>font-size:13px;"><tr>
      <td class="stack" style="padding:0 18px 8px 0;"><a href="https://www.instagram.com/dlux.handcarwash" style="color:#ffffff;"><img src="https://img.icons8.com/ios-filled/50/D81E4A/instagram-new.png" width="18" height="18" alt="Instagram" style="vertical-align:middle;border:0;"> <span style="vertical-align:middle;">@dlux.handcarwash</span></a></td>
      <td class="stack" style="padding:0 18px 8px 0;"><a href="https://www.facebook.com/dluxhandcarwash" style="color:#ffffff;"><img src="https://img.icons8.com/ios-filled/50/D81E4A/facebook-new.png" width="18" height="18" alt="Facebook" style="vertical-align:middle;border:0;"> <span style="vertical-align:middle;">@dluxhandcarwash</span></a></td>
      <td class="stack" style="padding:0 0 8px 0;"><a href="https://www.tiktok.com/@dluxhandcarwash" style="color:#ffffff;"><img src="https://img.icons8.com/ios-filled/50/D81E4A/tiktok.png" width="18" height="18" alt="TikTok" style="vertical-align:middle;border:0;"> <span style="vertical-align:middle;">@dluxhandcarwash</span></a></td>
    </tr></table>
  </td></tr>
  <tr><td style="padding:0 30px 28px;" class="px">
    <a href="https://search.google.com/local/writereview?placeid=ChIJqbziy0hn1moRymkYXvp-Dxs" title="Leave DLux a Google review" style="display:block;background:#ffffff;border-radius:12px;padding:14px 16px;text-align:center;">
      <img src="https://img.icons8.com/color/48/google-logo.png" width="22" height="22" alt="Google" style="vertical-align:middle;border:0;margin-right:8px;">
      <span class="b" style="vertical-align:middle;<?php echo $bf; ?>font-size:16px;font-weight:700;color:#000000;">Leave us a review</span>
      <span style="vertical-align:middle;font-family:Arial,sans-serif;font-size:16px;color:#FBBC04;letter-spacing:1px;margin-left:8px;">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
    </a>
  </td></tr>
</table>
</td></tr>

<tr><td align="center" class="b" style="padding:16px 12px 0;<?php echo $bf; ?>font-size:12px;color:#8a8a90;">
  <?php echo $customer ? esc_html__( 'You’re receiving this because you asked DLux for a quote.', 'dlux' ) : esc_html__( 'Sent by the quote form on the DLux website.', 'dlux' ); ?>
</td></tr>

</table>
</td></tr>
</table>
</body>
</html>
	<?php
	return (string) ob_get_clean();
}

/** Send a ready-made HTML email with a plain-text alternative. */
function dlux_quote_send( $to, $subject, $html, $text, array $headers = [] ) {
	if ( function_exists( 'dlux_bk_mail_raw' ) ) {
		return dlux_bk_mail_raw( $to, $subject, $html, $text, $headers );
	}
	$headers[] = 'Content-Type: text/html; charset=UTF-8';
	$alt       = function ( $phpmailer ) use ( $text ) { $phpmailer->AltBody = $text; }; // phpcs:ignore
	add_action( 'phpmailer_init', $alt );
	$sent = wp_mail( $to, $subject, $html, $headers );
	remove_action( 'phpmailer_init', $alt );
	return $sent;
}
