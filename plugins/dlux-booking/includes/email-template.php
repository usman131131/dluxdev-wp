<?php
/**
 * Branded HTML email template (DLUX colours, logo header, footer, details table).
 *
 * Used by every email the plugin sends (bookings + gift voucher) and, when the
 * plugin is active, by the theme's Contact Us form.
 *
 *   dlux_bk_mail( $to, $subject, array(
 *       'preheader' => 'Short preview text',
 *       'eyebrow'   => 'Booking confirmed',
 *       'title'     => 'You’re booked in!',
 *       'intro'     => array( 'Hi Sam,', 'Thanks for booking…' ),   // plain text paragraphs
 *       'alert'     => 'Optional highlighted warning',
 *       'rows'      => array( array( 'When', 'Monday 20 October, 9:00 AM – 10:00 AM' ), … ),
 *       'total'     => array( 'Paid by card', '$135.00' ),
 *       'html'      => '<p>Optional rich content (e.g. from a WYSIWYG field)</p>',
 *       'button'    => array( 'Open booking', 'https://…' ),
 *       'outro'     => array( 'See you soon,', 'DLUX' ),
 *   ), $headers );
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Brand colours: the theme's accent colour when set, else DLUX pink. */
function dlux_bk_email_colors() {
	$accent = function_exists( 'get_field' ) ? (string) get_field( 'accent_color', 'option' ) : '';
	if ( ! preg_match( '/^#[0-9a-fA-F]{6}$/', $accent ) ) {
		$accent = '#F43F5E';
	}
	return array(
		'accent'  => $accent,
		'bg'      => '#0B0B0D',
		'card'    => '#18181B',
		'row'     => '#202024',
		'line'    => '#2E2E33',
		'text'    => '#FFFFFF',
		'muted'   => '#B4B4BB',
	);
}

/** Logo URL for the header (PNG/JPG/GIF only – most email apps can't show SVG). */
function dlux_bk_email_logo() {
	$url = '';
	if ( function_exists( 'get_field' ) ) {
		$logo = get_field( 'site_logo', 'option' );
		if ( is_array( $logo ) && ! empty( $logo['url'] ) ) {
			$url = $logo['url'];
		} elseif ( is_numeric( $logo ) ) {
			$url = (string) wp_get_attachment_image_url( (int) $logo, 'medium' );
		}
	}
	if ( ! $url && has_custom_logo() ) {
		$url = (string) wp_get_attachment_image_url( (int) get_theme_mod( 'custom_logo' ), 'medium' );
	}
	$url = apply_filters( 'dlux_email_logo_url', $url );
	return ( $url && ! preg_match( '/\.svg(\?|$)/i', $url ) ) ? $url : '';
}

/** Full HTML email. */
function dlux_bk_email_html( array $a ) {
	$c    = dlux_bk_email_colors();
	$biz  = function_exists( 'dlux_bk_business' ) ? dlux_bk_business() : array( 'name' => get_bloginfo( 'name' ), 'address' => '', 'phone' => '' );
	$name = wp_specialchars_decode( $biz['name'] ? $biz['name'] : get_bloginfo( 'name' ), ENT_QUOTES );
	$logo = dlux_bk_email_logo();
	$font = "font-family:Arial,Helvetica,sans-serif;";
	$head = "font-family:'Arial Black',Arial,Helvetica,sans-serif;";
	$p    = function ( $text, $style = '' ) use ( $c, $font ) {
		return '<p style="margin:0 0 14px;' . $font . 'font-size:15px;line-height:1.6;color:' . $c['text'] . ';' . $style . '">' . nl2br( esc_html( $text ) ) . '</p>';
	};

	ob_start();
	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="dark light">
<meta name="supported-color-schemes" content="dark light">
<title><?php echo esc_html( $a['title'] ?? $name ); ?></title>
</head>
<body style="margin:0;padding:0;background:<?php echo esc_attr( $c['bg'] ); ?>;">
<?php if ( ! empty( $a['preheader'] ) ) : ?>
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;"><?php echo esc_html( $a['preheader'] ); ?></div>
<?php endif; ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:<?php echo esc_attr( $c['bg'] ); ?>;">
<tr><td align="center" style="padding:28px 12px;">
	<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px;">

		<!-- Header -->
		<tr><td style="padding:8px 4px 22px;" align="left">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="text-decoration:none;">
				<?php if ( $logo ) : ?>
					<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $name ); ?>" height="36" style="display:block;height:36px;width:auto;border:0;">
				<?php else : ?>
					<span style="<?php echo $head; // phpcs:ignore ?>font-size:30px;font-weight:900;letter-spacing:-1px;color:#FFFFFF;text-transform:uppercase;"><?php echo esc_html( strtoupper( strtok( $name, ' ' ) ) ); ?><span style="color:<?php echo esc_attr( $c['accent'] ); ?>;">.</span></span>
				<?php endif; ?>
			</a>
		</td></tr>

		<!-- Card -->
		<tr><td style="background:<?php echo esc_attr( $c['card'] ); ?>;border:1px solid <?php echo esc_attr( $c['line'] ); ?>;border-top:4px solid <?php echo esc_attr( $c['accent'] ); ?>;border-radius:16px;padding:32px 28px;">

			<?php if ( ! empty( $a['eyebrow'] ) ) : ?>
				<p style="margin:0 0 8px;<?php echo $font; // phpcs:ignore ?>font-size:12px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:<?php echo esc_attr( $c['accent'] ); ?>;"><?php echo esc_html( $a['eyebrow'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $a['title'] ) ) : ?>
				<h1 style="margin:0 0 20px;<?php echo $head; // phpcs:ignore ?>font-size:26px;line-height:1.15;font-weight:900;text-transform:uppercase;color:#FFFFFF;"><?php echo esc_html( $a['title'] ); ?></h1>
			<?php endif; ?>

			<?php
			foreach ( (array) ( $a['intro'] ?? array() ) as $line ) {
				echo $p( $line ); // phpcs:ignore
			}
			?>

			<?php if ( ! empty( $a['alert'] ) ) : ?>
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:6px 0 18px;">
					<tr><td style="background:#3A1520;border:1px solid <?php echo esc_attr( $c['accent'] ); ?>;border-radius:10px;padding:12px 16px;<?php echo $font; // phpcs:ignore ?>font-size:14px;line-height:1.5;color:#FECDD3;"><?php echo esc_html( $a['alert'] ); ?></td></tr>
				</table>
			<?php endif; ?>

			<?php if ( ! empty( $a['html'] ) ) : ?>
				<div style="<?php echo $font; // phpcs:ignore ?>font-size:15px;line-height:1.6;color:#FFFFFF;"><?php echo wp_kses_post( str_replace( array( '<p>', '<a ' ), array( '<p style="margin:0 0 14px;color:#FFFFFF;">', '<a style="color:' . esc_attr( $c['accent'] ) . ';" ' ), $a['html'] ) ); ?></div>
			<?php endif; ?>

			<?php if ( ! empty( $a['rows'] ) || ! empty( $a['total'] ) ) : ?>
				<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 22px;border-collapse:separate;border:1px solid <?php echo esc_attr( $c['line'] ); ?>;border-radius:12px;overflow:hidden;">
					<?php
					$i = 0;
					foreach ( (array) ( $a['rows'] ?? array() ) as $row ) :
						if ( ! isset( $row[1] ) || '' === trim( (string) $row[1] ) ) {
							continue;
						}
						$bg = ( $i++ % 2 ) ? $c['row'] : $c['card'];
						?>
						<tr>
							<td valign="top" width="38%" style="background:<?php echo esc_attr( $bg ); ?>;padding:12px 16px;border-bottom:1px solid <?php echo esc_attr( $c['line'] ); ?>;<?php echo $font; // phpcs:ignore ?>font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:<?php echo esc_attr( $c['muted'] ); ?>;"><?php echo esc_html( $row[0] ); ?></td>
							<td valign="top" style="background:<?php echo esc_attr( $bg ); ?>;padding:12px 16px;border-bottom:1px solid <?php echo esc_attr( $c['line'] ); ?>;<?php echo $font; // phpcs:ignore ?>font-size:15px;line-height:1.5;color:#FFFFFF;"><?php echo nl2br( esc_html( $row[1] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					<?php if ( ! empty( $a['total'] ) ) : ?>
						<tr>
							<td style="background:<?php echo esc_attr( $c['accent'] ); ?>;padding:14px 16px;<?php echo $font; // phpcs:ignore ?>font-size:13px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#FFFFFF;"><?php echo esc_html( $a['total'][0] ); ?></td>
							<td align="right" style="background:<?php echo esc_attr( $c['accent'] ); ?>;padding:14px 16px;<?php echo $head; // phpcs:ignore ?>font-size:22px;font-weight:900;color:#FFFFFF;"><?php echo esc_html( $a['total'][1] ); ?></td>
						</tr>
					<?php endif; ?>
				</table>
			<?php endif; ?>

			<?php if ( ! empty( $a['button'][1] ) ) : ?>
				<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:4px 0 20px;">
					<tr><td style="background:<?php echo esc_attr( $c['accent'] ); ?>;border-radius:999px;">
						<a href="<?php echo esc_url( $a['button'][1], array( 'http', 'https', 'mailto', 'tel' ) ); ?>" style="display:inline-block;padding:14px 28px;<?php echo $font; // phpcs:ignore ?>font-size:14px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#FFFFFF;text-decoration:none;border-radius:999px;"><?php echo esc_html( $a['button'][0] ); ?> &rarr;</a>
					</td></tr>
				</table>
			<?php endif; ?>

			<?php
			foreach ( (array) ( $a['outro'] ?? array() ) as $line ) {
				echo $p( $line, 'color:' . $c['muted'] . ';' ); // phpcs:ignore
			}
			?>
		</td></tr>

		<!-- Footer -->
		<tr><td align="center" style="padding:24px 12px 8px;<?php echo $font; // phpcs:ignore ?>font-size:12px;line-height:1.7;color:<?php echo esc_attr( $c['muted'] ); ?>;">
			<?php if ( $logo ) : ?>
				<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( $name ); ?>" height="22" style="display:block;margin:0 auto 10px;height:22px;width:auto;border:0;opacity:.85;">
			<?php else : ?>
				<p style="margin:0 0 8px;<?php echo $head; // phpcs:ignore ?>font-size:16px;font-weight:900;color:#FFFFFF;text-transform:uppercase;"><?php echo esc_html( strtoupper( strtok( $name, ' ' ) ) ); ?><span style="color:<?php echo esc_attr( $c['accent'] ); ?>;">.</span></p>
			<?php endif; ?>
			<strong style="color:#FFFFFF;"><?php echo esc_html( $name ); ?></strong><br>
			<?php if ( $biz['address'] ) : ?><?php echo esc_html( $biz['address'] ); ?><br><?php endif; ?>
			<?php if ( $biz['phone'] ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $biz['phone'] ) ); ?>" style="color:<?php echo esc_attr( $c['accent'] ); ?>;text-decoration:none;"><?php echo esc_html( $biz['phone'] ); ?></a> &nbsp;·&nbsp; <?php endif; ?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:<?php echo esc_attr( $c['accent'] ); ?>;text-decoration:none;"><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></a><br>
			<span style="color:#6B6B73;">&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( $name ); ?></span>
		</td></tr>

	</table>
</td></tr>
</table>
</body>
</html>
	<?php
	return (string) ob_get_clean();
}

/** Plain-text version (sent alongside the HTML for email apps that prefer text). */
function dlux_bk_email_text( array $a ) {
	$out = array();
	if ( ! empty( $a['title'] ) ) {
		$out[] = $a['title'];
		$out[] = '';
	}
	foreach ( (array) ( $a['intro'] ?? array() ) as $l ) {
		$out[] = $l;
	}
	if ( ! empty( $a['alert'] ) ) {
		$out[] = '';
		$out[] = '!! ' . $a['alert'] . ' !!';
	}
	if ( ! empty( $a['html'] ) ) {
		$out[] = '';
		$out[] = trim( wp_strip_all_tags( str_replace( array( '</p>', '<br>', '<br />' ), "\n", $a['html'] ) ) );
	}
	if ( ! empty( $a['rows'] ) ) {
		$out[] = '';
		foreach ( $a['rows'] as $r ) {
			if ( isset( $r[1] ) && '' !== trim( (string) $r[1] ) ) {
				$out[] = $r[0] . ': ' . $r[1];
			}
		}
	}
	if ( ! empty( $a['total'] ) ) {
		$out[] = $a['total'][0] . ': ' . $a['total'][1];
	}
	if ( ! empty( $a['button'][1] ) ) {
		$out[] = '';
		$out[] = $a['button'][0] . ': ' . $a['button'][1];
	}
	if ( ! empty( $a['outro'] ) ) {
		$out[] = '';
		foreach ( (array) $a['outro'] as $l ) {
			$out[] = $l;
		}
	}
	return html_entity_decode( implode( "\n", $out ), ENT_QUOTES, 'UTF-8' );
}

/** Send a branded email (HTML + plain-text alternative). */
function dlux_bk_mail( $to, $subject, array $a, array $headers = array() ) {
	$headers[] = 'Content-Type: text/html; charset=UTF-8';
	$text      = dlux_bk_email_text( $a );
	$alt       = function ( $phpmailer ) use ( $text ) {
		$phpmailer->AltBody = $text; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
	};
	add_action( 'phpmailer_init', $alt );
	$sent = wp_mail( $to, $subject, dlux_bk_email_html( $a ), $headers );
	remove_action( 'phpmailer_init', $alt );
	return $sent;
}

/**
 * "Booking confirmed" email (DLux design), for the shop or the customer.
 *
 * @param array $b     Booking from dlux_bk_booking().
 * @param array $extra {
 *   for:       'admin' (default) or 'customer',
 *   late:      bool   late-payment warning (shop only),
 *   url:       string button link ("Open booking" for the shop, "Get directions" for the customer),
 *   preheader: string optional preview text,
 * }
 */
function dlux_bk_admin_booking_email_html( array $b, array $extra = array() ) {
	$e  = function ( $s ) { return esc_html( (string) $s ); };
	$hf = "font-family:'Barlow Condensed','Arial Narrow',Arial,Helvetica,sans-serif;";
	$bf = "font-family:'Barlow',Arial,Helvetica,sans-serif;";

	// Date and time.
	$day_name = '';
	$date_s   = '';
	$start    = '';
	$end      = '';
	if ( $b['day'] ) {
		$ts       = strtotime( $b['day'] . ' 12:00:00' );
		$day_name = wp_date( 'l j F', $ts ); // Friday 23 October
		$date_s   = wp_date( 'D j M', $ts ); // Fri 23 Oct
		if ( $b['time'] ) {
			try {
				$st    = new DateTimeImmutable( $b['day'] . ' ' . $b['time'], wp_timezone() );
				$slot  = function_exists( 'dlux_bk_config' ) ? (int) dlux_bk_config()['slot'] : 60;
				$start = $st->format( 'g:i A' );
				$end   = $st->add( new DateInterval( 'PT' . max( 10, $slot ) . 'M' ) )->format( 'g:i A' );
			} catch ( Exception $ex ) {
				$start = dlux_bk_time_label( $b['time'] );
			}
		}
	}

	// Booking.
	$ref      = '#DL-' . $b['id'];
	$rego     = $b['rego'] ? strtoupper( $b['rego'] ) : '—';
	$service  = $b['service'] ? $b['service'] : 'Car wash';
	$package  = $b['items'] ? $b['items'][0]['name'] : $b['package'];
	$extras   = implode( ', ', wp_list_pluck( array_slice( $b['items'], 1 ), 'name' ) );
	$duration = $b['mins'] > 0 ? 'Approx. ' . $b['mins'] . ' min' : '';
	$amount   = dlux_bk_money( $b['total'] );
	$shop     = 'unpaid' === $b['payment'];
	$url      = ! empty( $extra['url'] ) ? $extra['url'] : admin_url( 'post.php?post=' . $b['id'] . '&action=edit' );
	$customer = 'customer' === ( $extra['for'] ?? 'admin' );
	$late     = ! $customer && ! empty( $extra['late'] );
	if ( $customer && empty( $extra['url'] ) ) {
		$url = '';
	}
	$details  = array_filter(
		array(
			'Option'  => $b['option'],
			'Extras'  => $extras,
			'Vehicle' => $b['vehicle'],
			'Notes'   => $b['notes'],
		),
		function ( $v ) { return '' !== trim( (string) $v ); }
	);

	// Vehicle sizes (from the Prices page), with the booked one highlighted.
	$norm  = function ( $s ) { return strtolower( preg_replace( '/[^a-z0-9]/i', '', (string) $s ) ); };
	$sizes = array();
	if ( function_exists( 'dlux_bk_menu' ) ) {
		$menu  = dlux_bk_menu();
		$sizes = isset( $menu['classes'] ) ? array_values( (array) $menu['classes'] ) : array();
	}
	if ( ! $sizes ) {
		$sizes = array( 'Sedan', 'SUV / SW', 'Large', 'Extra Large' );
	}
	if ( $b['cls'] && ! in_array( $norm( $b['cls'] ), array_map( $norm, $sizes ), true ) ) {
		$sizes[] = $b['cls'];
	}

	// Customer.
	$cust     = trim( $b['name'] );
	$parts    = preg_split( '/\s+/', $cust, -1, PREG_SPLIT_NO_EMPTY );
	$initials = '';
	if ( $parts ) {
		$initials = mb_strtoupper( mb_substr( $parts[0], 0, 1 ) . ( count( $parts ) > 1 ? mb_substr( end( $parts ), 0, 1 ) : '' ) );
	}
	$mobile   = $b['phone'];
	$e164     = function_exists( 'dlux_bk_sms_number' ) ? dlux_bk_sms_number( $mobile ) : preg_replace( '/[^0-9+]/', '', $mobile );
	$email    = is_email( $b['email'] ) ? $b['email'] : '';
	$contact  = implode( ' &nbsp;|&nbsp; ', array_filter( array( $e( $mobile ), $e( $email ) ) ) );
	$buttons  = array_filter(
		array(
			$e164 ? array( 'tel:' . $e164, 'phone', 'Call' ) : null,
			$e164 ? array( 'sms:' . $e164, 'sms', 'Text' ) : null,
			$email ? array( 'mailto:' . $email, 'new-post', 'Email' ) : null,
		)
	);

	$title     = 'Booking confirmed: ' . $rego . ( $date_s ? ', ' . $date_s : '' ) . ( $start ? ', ' . $start : '' );
	$preheader = implode( ', ', array_filter( array( $package, $b['cls'] ? strtolower( $b['cls'] ) : '' ) ) ) . '. '
		. trim( $date_s . ( $start ? ', ' . $start : '' ) ) . '. '
		. ( $cust ? $cust . ', ' : '' ) . $amount . ( $shop ? ' to collect at the shop.' : ' paid by card.' );
	if ( ! empty( $extra['preheader'] ) ) {
		$preheader = $extra['preheader'];
	}
	$terms_url = apply_filters( 'dlux_booking_terms_url', home_url( '/terms-and-conditions/' ) );
	$protocols = array( 'http', 'https', 'mailto', 'tel', 'sms' );

	ob_start();
	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light only">
<title><?php echo $e( $title ); ?></title>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Barlow:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  body { margin:0; padding:0; background:#e9e9eb; }
  a { text-decoration:none; }
  .h { font-family:'Barlow Condensed','Arial Narrow',Arial,Helvetica,sans-serif; }
  .b { font-family:'Barlow',Arial,Helvetica,sans-serif; }
  @media (max-width:520px) {
    .px { padding-left:20px !important; padding-right:20px !important; }
    .time { font-size:46px !important; }
    .plate { font-size:30px !important; letter-spacing:3px !important; }
    .stack { display:block !important; width:100% !important; }
    .stack-gap { padding:0 0 10px 0 !important; }
    .size { font-size:11px !important; }
  }
</style>
</head>
<body style="margin:0;padding:0;background:#e9e9eb;">
<div style="display:none;max-height:0;overflow:hidden;"><?php echo $e( $preheader ); ?></div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#e9e9eb;">
<tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:580px;">

<!-- ============ HEADER ============ -->
<tr><td style="background:#000000;border-radius:16px 16px 0 0;padding:22px 30px 18px;" class="px">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
    <td class="h" style="<?php echo $hf; ?>font-size:30px;font-weight:800;color:#ffffff;letter-spacing:1px;line-height:1;">DLUXWASH<span style="color:#D81E4A;">.</span></td>
    <td align="right">
      <span class="b" style="<?php echo $bf; ?>font-size:12px;color:#b9b9bd;">Booking ref</span><br>
      <span class="h" style="<?php echo $hf; ?>font-size:20px;font-weight:800;color:#ffffff;letter-spacing:1px;"><?php echo $e( $ref ); ?></span>
    </td>
  </tr></table>
</td></tr>
<tr><td style="background:#D81E4A;height:4px;line-height:4px;font-size:0;">&nbsp;</td></tr>

<!-- ============ HERO: TIME + PLATE ============ -->
<tr><td style="background:#141414;padding:30px 30px 32px;" class="px">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:22px;"><tr>
    <td valign="middle" style="padding-right:12px;">
      <div style="width:36px;height:36px;line-height:36px;border-radius:18px;background:#D81E4A;color:#ffffff;text-align:center;font-family:Arial,sans-serif;font-size:20px;font-weight:bold;">&#10003;</div>
    </td>
    <td valign="middle">
      <h1 class="h" style="margin:0;<?php echo $hf; ?>font-size:34px;font-weight:800;line-height:1;color:#ffffff;">Booking confirmed</h1>
    </td>
  </tr></table>
  <?php if ( $late ) : ?>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:18px;"><tr>
    <td class="b" style="background:#3a1520;border:1px solid #D81E4A;border-radius:10px;padding:12px 16px;<?php echo $bf; ?>font-size:14px;line-height:1.5;color:#ffd6df;">This customer paid after their checkout had timed out. Please check the time slot is still free.</td>
  </tr></table>
  <?php endif; ?>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #333336;padding-top:20px;"><tr>
    <td class="stack stack-gap" valign="bottom" style="padding-right:16px;">
      <p class="b" style="margin:20px 0 4px;<?php echo $bf; ?>font-size:15px;font-weight:600;color:#D81E4A;"><?php echo $e( $day_name ); ?></p>
      <p class="h time" style="margin:0;<?php echo $hf; ?>font-size:58px;font-weight:800;line-height:0.95;color:#ffffff;"><?php echo $e( $start ); ?></p>
      <?php if ( $end ) : ?>
      <p class="b" style="margin:8px 0 0;<?php echo $bf; ?>font-size:14px;color:#b9b9bd;">Arrival window until <?php echo $e( $end ); ?></p>
      <?php endif; ?>
    </td>
    <td class="stack" valign="bottom" align="right">
      <!-- Number plate -->
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff;border:3px solid #ffffff;border-radius:8px;">
        <tr><td style="border:2px solid #000000;border-radius:5px;padding:8px 16px 2px;" align="center">
          <span class="h plate" style="<?php echo $hf; ?>font-size:38px;font-weight:800;letter-spacing:4px;color:#000000;line-height:1;"><?php echo $e( $rego ); ?></span><br>
          <span class="b" style="<?php echo $bf; ?>font-size:9px;font-weight:700;letter-spacing:2px;color:#000000;">VICTORIA</span>
        </td></tr>
      </table>
    </td>
  </tr></table>
</td></tr>

<!-- ============ SERVICE CARD ============ -->
<tr><td style="background:#ffffff;padding:28px 30px 8px;" class="px">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:2px solid #000000;border-radius:14px;">
    <tr><td style="padding:20px 22px 18px;">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
        <td>
          <span class="b" style="display:inline-block;background:#fde7ec;color:#D81E4A;<?php echo $bf; ?>font-size:12px;font-weight:700;padding:5px 11px;border-radius:20px;"><?php echo $e( $service ); ?></span>
          <p class="h" style="margin:10px 0 0;<?php echo $hf; ?>font-size:36px;font-weight:800;line-height:1;color:#000000;"><?php echo $e( $package ); ?></p>
          <?php foreach ( $details as $label => $value ) : ?>
          <p class="b" style="margin:6px 0 0;<?php echo $bf; ?>font-size:14px;line-height:1.4;color:#33333a;"><strong style="color:#000000;"><?php echo $e( $label ); ?>:</strong> <?php echo nl2br( $e( $value ) ); ?></p>
          <?php endforeach; ?>
        </td>
        <?php if ( $duration ) : ?>
        <td align="right" valign="top" class="b" style="<?php echo $bf; ?>font-size:14px;font-weight:600;color:#55555b;white-space:nowrap;"><?php echo $e( $duration ); ?></td>
        <?php endif; ?>
      </tr></table>
    </td></tr>

    <!-- Vehicle size scale -->
    <tr><td style="background:#f4f4f5;border-top:2px solid #000000;border-radius:0 0 12px 12px;padding:16px 22px 18px;">
      <p class="b" style="margin:0 0 10px;<?php echo $bf; ?>font-size:13px;font-weight:600;color:#55555b;">Vehicle size</p>
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><?php
		$n    = count( $sizes );
		$w    = floor( 100 / max( 1, $n ) ) . '%';
		$last = $n - 1;
		foreach ( $sizes as $i => $size ) {
			$on  = $b['cls'] && $norm( $size ) === $norm( $b['cls'] );
			$pad = 0 === $i ? 'padding-right:4px;' : ( $last === $i ? 'padding-left:4px;' : 'padding:0 2px;' );
			$css = $on
				? 'font-weight:700;background:#000000;color:#ffffff;border:1px solid #000000;'
				: 'font-weight:600;background:#ffffff;color:#9a9aa0;border:1px solid #dcdce0;';
			echo '<td width="' . esc_attr( $w ) . '" style="' . $pad . '"><div class="b size" style="' . $bf . 'font-size:13px;' . $css . 'text-align:center;padding:10px 0;border-radius:8px;">' . ( $on ? '&#10003; ' : '' ) . $e( $size ) . '</div></td>'; // phpcs:ignore
		}
		?></tr></table>
    </td></tr>
  </table>
</td></tr>

<!-- ============ CUSTOMER ============ -->
<tr><td style="background:#ffffff;padding:20px 30px 8px;" class="px">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
    <td width="48" valign="middle">
      <div class="h" style="width:44px;height:44px;line-height:44px;border-radius:22px;background:#D81E4A;color:#ffffff;text-align:center;<?php echo $hf; ?>font-size:20px;font-weight:800;"><?php echo $e( $initials ); ?></div>
    </td>
    <td valign="middle" style="padding-left:12px;">
      <p class="b" style="margin:0;<?php echo $bf; ?>font-size:18px;font-weight:700;color:#000000;"><?php echo $e( $cust ); ?></p>
      <?php if ( $contact ) : ?>
      <p class="b" style="margin:2px 0 0;<?php echo $bf; ?>font-size:14px;color:#55555b;"><?php echo $contact; // phpcs:ignore -- escaped above ?></p>
      <?php endif; ?>
      <?php if ( $b['channel'] && ! $customer ) : ?>
      <p class="b" style="margin:2px 0 0;<?php echo $bf; ?>font-size:13px;color:#8a8a90;">Found us via <?php echo $e( $b['channel'] ); ?></p>
      <?php endif; ?>
    </td>
  </tr></table>
  <?php if ( $buttons && ! $customer ) : ?>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:14px;"><tr>
	<?php
	$buttons = array_values( $buttons );
	$n       = count( $buttons );
	foreach ( $buttons as $i => $btn ) {
		$pad = 1 === $n ? '' : ( 0 === $i ? 'padding-right:5px;' : ( $n - 1 === $i ? 'padding-left:5px;' : 'padding:0 3px;' ) );
		echo '<td width="' . esc_attr( floor( 100 / $n ) . '%' ) . '" style="' . $pad . '"><a href="' . esc_url( $btn[0], $protocols ) . '" class="b" style="display:block;background:#000000;color:#ffffff;' . $bf . 'font-size:15px;font-weight:700;text-align:center;padding:12px 0;border-radius:10px;"><img src="https://img.icons8.com/ios-filled/50/ffffff/' . esc_attr( $btn[1] ) . '.png" width="16" height="16" alt="" style="vertical-align:middle;border:0;margin-right:6px;"><span style="vertical-align:middle;">' . $e( $btn[2] ) . '</span></a></td>'; // phpcs:ignore
	}
	?>
  </tr></table>
  <?php endif; ?>
</td></tr>

<!-- ============ PAYMENT + CTA ============ -->
<tr><td style="background:#ffffff;padding:20px 30px 30px;border-radius:0 0 16px 16px;" class="px">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#D81E4A;border-radius:14px;">
    <tr>
      <td style="padding:18px 22px;">
        <p class="b" style="margin:0;<?php echo $bf; ?>font-size:16px;font-weight:700;color:#ffffff;"><?php echo $shop ? 'Pay at the shop' : 'Paid by card'; ?></p>
        <p class="b" style="margin:2px 0 0;<?php echo $bf; ?>font-size:13px;color:#ffd6df;"><?php echo $customer ? ( $shop ? 'Pay when you arrive.' : 'Paid online. Nothing to pay on the day.' ) : ( $shop ? 'Not paid yet. Collect on arrival.' : 'Paid online. Nothing to collect.' ); ?></p>
      </td>
      <td align="right" class="h" style="padding:18px 22px;<?php echo $hf; ?>font-size:40px;font-weight:800;color:#ffffff;line-height:1;"><?php echo $e( $amount ); ?></td>
    </tr>
  </table>
  <?php if ( $url ) : ?>
  <a href="<?php echo esc_url( $url ); ?>" class="b" style="display:block;margin-top:14px;background:#000000;color:#ffffff;<?php echo $bf; ?>font-size:17px;font-weight:700;text-align:center;padding:17px 0;border-radius:12px;"><?php echo $customer ? 'Get directions' : 'Open booking'; ?></a>
  <?php endif; ?>
</td></tr>

<!-- ============ GOOD TO KNOW ============ -->
<tr><td style="padding:22px 0 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#ffffff;border-radius:16px;">
  <tr><td class="px" style="padding:26px 30px 24px;">
    <p class="h" style="margin:0 0 14px;<?php echo $hf; ?>font-size:24px;font-weight:800;color:#000000;">Good to know</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="b" style="<?php echo $bf; ?>font-size:14px;line-height:1.5;color:#33333a;">
	<?php
	$notes = array(
		array( 'Times are estimates.', 'We\'re a drive-in service, so cars join the queue on arrival. Your slot is an arrival window, and finish times depend on the queue and your car\'s condition.' ),
		array( 'Heavily soiled cars cost extra.', 'Tree sap, tar, bug splatter, sand, pet hair, caked mud or a very dirty interior take extra time. We inspect your car on arrival and quote before we start. Nothing is added without your OK.' ),
		array( 'No guaranteed removal.', 'We can\'t promise complete removal of tree sap, tar, bug etching, stains or odours, as results depend on how long they\'ve been there.' ),
		array( 'Remove your valuables.', 'Please take out personal items and cash before handing over your keys. You don\'t have to remove child seats, but taking them out lets us clean underneath them more thoroughly.' ),
		array( 'Tell us about any existing damage.', 'Please let us know about any scratches, dents, chips or other damage before we start. We\'ll note it on your booking.' ),
		array( 'Your car may be parked on the street.', 'Once your car is finished, we may park it on the street nearby so the driveway stays clear for the queue. Staff will hand back your keys and show you where it is.' ),
		array( 'Need to change your time?', 'Call or email us before your booking and quote your booking reference.' ),
	);
	foreach ( $notes as $note ) {
		echo '<tr><td width="22" valign="top" style="padding:0 0 12px;"><div style="width:8px;height:8px;border-radius:4px;background:#D81E4A;margin-top:7px;"></div></td><td valign="top" style="padding:0 0 12px;"><strong style="color:#000000;">' . $e( $note[0] ) . '</strong> ' . $e( $note[1] ) . '</td></tr>'; // phpcs:ignore
	}
	?>
    </table>
    <p class="b" style="margin:14px 0 0;<?php echo $bf; ?>font-size:13px;color:#55555b;">By keeping this booking you agree to our <a href="<?php echo esc_url( $terms_url ); ?>" style="color:#D81E4A;font-weight:700;text-decoration:underline;">terms and conditions</a>.</p>
  </td></tr>
</table>
</td></tr>

<!-- ============ SIGNATURE ============ -->
<tr><td style="padding:22px 0 0;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#000000;border-radius:16px;">
  <tr><td style="padding:28px 30px 10px;" class="px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
      <td valign="top">
        <p class="h" style="margin:0;<?php echo $hf; ?>font-size:26px;font-weight:800;color:#ffffff;line-height:1;">DLUXWASH<span style="color:#D81E4A;">.</span></p>
        <p class="b" style="margin:4px 0 0;<?php echo $bf; ?>font-size:13px;color:#b9b9bd;">Hand Car Wash &amp; Detailing</p>
      </td>
      <td align="right" valign="top">
        <span class="b" style="display:inline-block;border:1px solid #D81E4A;color:#D81E4A;<?php echo $bf; ?>font-size:12px;font-weight:700;padding:5px 10px;border-radius:20px;">Trusted since 2010</span>
      </td>
    </tr></table>
  </td></tr>

  <tr><td style="padding:12px 30px 0;" class="px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="b" style="<?php echo $bf; ?>font-size:14px;line-height:1.5;">
      <tr>
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
      </tr>
    </table>
  </td></tr>

  <tr><td style="padding:18px 30px 0;" class="px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td width="50%" style="padding-right:5px;"><a href="tel:0410111190" title="Call DLux" style="display:block;background:#D81E4A;border-radius:10px;text-align:center;padding:13px 0;"><img src="https://img.icons8.com/ios-filled/50/ffffff/phone.png" width="22" height="22" alt="Call DLux" style="display:inline-block;vertical-align:middle;border:0;"></a></td><td width="50%" style="padding-left:5px;"><a href="mailto:info@dluxwash.com.au" title="Email DLux" style="display:block;background:#D81E4A;border-radius:10px;text-align:center;padding:13px 0;"><img src="https://img.icons8.com/ios-filled/50/ffffff/new-post.png" width="22" height="22" alt="Email DLux" style="display:inline-block;vertical-align:middle;border:0;"></a></td></tr></table>
  </td></tr>

  <!-- Socials -->
  <tr><td style="padding:20px 30px 18px;" class="px">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="b" style="<?php echo $bf; ?>font-size:13px;"><tr>
      <td class="stack" style="padding:0 18px 8px 0;">
        <a href="https://www.instagram.com/dlux.handcarwash" style="color:#ffffff;">
          <img src="https://img.icons8.com/ios-filled/50/D81E4A/instagram-new.png" width="18" height="18" alt="Instagram" style="vertical-align:middle;border:0;"> <span style="vertical-align:middle;">@dlux.handcarwash</span>
        </a>
      </td>
      <td class="stack" style="padding:0 18px 8px 0;">
        <a href="https://www.facebook.com/dluxhandcarwash" style="color:#ffffff;">
          <img src="https://img.icons8.com/ios-filled/50/D81E4A/facebook-new.png" width="18" height="18" alt="Facebook" style="vertical-align:middle;border:0;"> <span style="vertical-align:middle;">@dluxhandcarwash</span>
        </a>
      </td>
      <td class="stack" style="padding:0 0 8px 0;">
        <a href="https://www.tiktok.com/@dluxhandcarwash" style="color:#ffffff;">
          <img src="https://img.icons8.com/ios-filled/50/D81E4A/tiktok.png" width="18" height="18" alt="TikTok" style="vertical-align:middle;border:0;"> <span style="vertical-align:middle;">@dluxhandcarwash</span>
        </a>
      </td>
    </tr></table>
  </td></tr>

  <!-- Google review -->
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
  You’re receiving this because you booked online with DLux.
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
function dlux_bk_mail_raw( $to, $subject, $html, $text, array $headers = array() ) {
	$headers[] = 'Content-Type: text/html; charset=UTF-8';
	$alt       = function ( $phpmailer ) use ( $text ) {
		$phpmailer->AltBody = $text; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
	};
	add_action( 'phpmailer_init', $alt );
	$sent = wp_mail( $to, $subject, $html, $headers );
	remove_action( 'phpmailer_init', $alt );
	return $sent;
}
