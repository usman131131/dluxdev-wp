<?php
/**
 * Gift voucher popup: editable content + sign-up form.
 *
 * - Content and form labels: Theme Settings › Gift Voucher Popup (or Bookings › Gift Voucher Popup).
 * - Every sign-up (name, email, date) is saved in the "Sign-ups" repeater on that page.
 * - Each sign-up emails the subscriber ("Email to subscriber" tab) and the shop.
 * - The theme shows the popup; this file renders its content and handles the form (admin-ajax).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DLUX_BK_VOUCHER_ID', 'dlux_voucher' );        // SCF post_id → option names "dlux_voucher_*".
define( 'DLUX_BK_VOUCHER_PAGE', 'dlux-voucher-popup' ); // Admin page slug.

function dlux_bk_voucher_defaults() {
	return array(
		'content'           => '<h2>A LITTLE GIFT, JUST FOR YOU!</h2>' . "\n" .
			'<h3>Enjoy 10% OFF your next purchase!</h3>' . "\n" .
			'<p>Sign up below to unlock your exclusive discount and treat yourself to something special.</p>',
		'name_placeholder'  => 'Your Name',
		'email_placeholder' => 'Your Email Address',
		'button_text'       => 'GET MY 10% OFF',
		'success_message'   => 'Thanks! You’re on the list – check your inbox soon.',
		'email_subject'     => 'Your 10% OFF is on its way!',
		'email_message'     => '<p>Hi {name},</p>' . "\n" .
			'<p>Thanks for signing up – a little gift, just for you! Enjoy <strong>10% OFF</strong> your next purchase.</p>' . "\n" .
			'<p>Just mention this email when you visit or book with us and we’ll apply your discount.</p>' . "\n" .
			'<p>See you soon,<br>{business}</p>',
	);
}

function dlux_bk_voucher_field( $name ) {
	$d = dlux_bk_voucher_defaults();
	$v = function_exists( 'get_field' ) ? get_field( $name, DLUX_BK_VOUCHER_ID ) : null;
	return ( null === $v || '' === $v || false === $v ) ? ( $d[ $name ] ?? '' ) : $v;
}

/* ---------------- Options page ---------------- */

add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
			return;
		}
		acf_add_options_sub_page(
			array(
				'page_title'      => 'Gift Voucher Popup',
				'menu_title'      => 'Gift Voucher Popup',
				'menu_slug'       => DLUX_BK_VOUCHER_PAGE,
				'parent_slug'     => dlux_bk_theme_options_exist() ? DLUX_BK_THEME_OPTIONS : 'edit.php?post_type=dlux_booking',
				'capability'      => 'manage_options',
				'post_id'         => DLUX_BK_VOUCHER_ID,
				'autoload'        => false,
				'update_button'   => 'Save',
				'updated_message' => 'Gift voucher popup saved.',
			)
		);
	},
	101 // After Booking Prices (100).
);

/* ---------------- Fields ---------------- */

add_action(
	'acf/init',
	function () {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}
		$d = dlux_bk_voucher_defaults();
		$k = function ( $name ) {
			return 'field_dlux_vc_' . $name;
		};
		acf_add_local_field_group(
			array(
				'key'      => 'group_dlux_voucher_popup',
				'title'    => 'Gift Voucher Popup',
				'style'    => 'seamless',
				'location' => array(
					array( array( 'param' => 'options_page', 'operator' => '==', 'value' => DLUX_BK_VOUCHER_PAGE ) ),
				),
				'fields'   => array(
					array( 'key' => $k( 'tab_popup' ), 'label' => 'Popup', 'type' => 'tab', 'placement' => 'top' ),
					array(
						'key'           => $k( 'content' ),
						'name'          => 'content',
						'label'         => 'Popup text',
						'type'          => 'wysiwyg',
						'tabs'          => 'all',
						'toolbar'       => 'full',
						'media_upload'  => 0,
						'default_value' => $d['content'],
						'instructions'  => 'Shown above the form when someone clicks the gift voucher card.',
					),
					array(
						'key'           => $k( 'name_ph' ),
						'name'          => 'name_placeholder',
						'label'         => 'Name field',
						'type'          => 'text',
						'default_value' => $d['name_placeholder'],
						'wrapper'       => array( 'width' => '33' ),
					),
					array(
						'key'           => $k( 'email_ph' ),
						'name'          => 'email_placeholder',
						'label'         => 'Email field',
						'type'          => 'text',
						'default_value' => $d['email_placeholder'],
						'wrapper'       => array( 'width' => '33' ),
					),
					array(
						'key'           => $k( 'button' ),
						'name'          => 'button_text',
						'label'         => 'Button text',
						'type'          => 'text',
						'default_value' => $d['button_text'],
						'wrapper'       => array( 'width' => '34' ),
					),
					array(
						'key'           => $k( 'success' ),
						'name'          => 'success_message',
						'label'         => 'Message after signing up',
						'type'          => 'text',
						'default_value' => $d['success_message'],
					),
					array( 'key' => $k( 'tab_email' ), 'label' => 'Email to subscriber', 'type' => 'tab', 'placement' => 'top' ),
					array(
						'key'           => $k( 'email_subject' ),
						'name'          => 'email_subject',
						'label'         => 'Subject',
						'type'          => 'text',
						'default_value' => $d['email_subject'],
					),
					array(
						'key'           => $k( 'email_message' ),
						'name'          => 'email_message',
						'label'         => 'Message',
						'type'          => 'wysiwyg',
						'tabs'          => 'all',
						'toolbar'       => 'full',
						'media_upload'  => 0,
						'default_value' => $d['email_message'],
						'instructions'  => 'Sent to the person who signed up, at the email they entered. Use {name} for their name and {business} for your business name.',
					),
					array( 'key' => $k( 'tab_signups' ), 'label' => 'Sign-ups', 'type' => 'tab', 'placement' => 'top' ),
					array(
						'key'          => $k( 'signups' ),
						'name'         => 'signups',
						'label'        => 'Sign-ups',
						'type'         => 'repeater',
						'layout'       => 'table',
						'button_label' => 'Add sign-up',
						'instructions' => 'Everyone who filled in the gift voucher popup. New sign-ups are added automatically; the same email is only saved once.',
						'sub_fields'   => array(
							array( 'key' => $k( 'su_name' ), 'name' => 'name', 'label' => 'Name', 'type' => 'text' ),
							array( 'key' => $k( 'su_email' ), 'name' => 'email', 'label' => 'Email', 'type' => 'email' ),
							array( 'key' => $k( 'su_date' ), 'name' => 'date', 'label' => 'Signed up', 'type' => 'text', 'readonly' => 1 ),
						),
					),
				),
			)
		);
	}
);

/* ---------------- Front end ---------------- */

/** Popup content + form (the theme wraps it in its modal). */
function dlux_bk_voucher_popup_html() {
	$content = dlux_bk_voucher_field( 'content' );
	ob_start();
	?>
	<div class="dlux-voucher">
		<div class="dlux-voucher__content"><?php echo wp_kses_post( wpautop( $content ) ); ?></div>
		<form class="dlux-voucher__form" data-voucher-form method="post" action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" novalidate>
			<input type="hidden" name="action" value="dlux_bk_voucher">
			<?php wp_nonce_field( 'dlux_bk_voucher', 'dlux_voucher_nonce' ); ?>
			<p class="dlux-voucher__hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></p>
			<label class="dlux-voucher__field">
				<span class="screen-reader-text"><?php echo esc_html( dlux_bk_voucher_field( 'name_placeholder' ) ); ?></span>
				<input type="text" name="name" required autocomplete="name" placeholder="<?php echo esc_attr( dlux_bk_voucher_field( 'name_placeholder' ) ); ?>">
			</label>
			<label class="dlux-voucher__field">
				<span class="screen-reader-text"><?php echo esc_html( dlux_bk_voucher_field( 'email_placeholder' ) ); ?></span>
				<input type="email" name="email" required autocomplete="email" placeholder="<?php echo esc_attr( dlux_bk_voucher_field( 'email_placeholder' ) ); ?>">
			</label>
			<button type="submit" class="dlux-voucher__submit"><span><?php echo esc_html( dlux_bk_voucher_field( 'button_text' ) ); ?></span></button>
			<p class="dlux-voucher__notice" data-voucher-notice role="status" aria-live="polite" hidden></p>
		</form>
	</div>
	<?php
	return ob_get_clean();
}

/* ---------------- Form handler ---------------- */

function dlux_bk_voucher_signup() {
	if ( ! isset( $_POST['dlux_voucher_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['dlux_voucher_nonce'] ) ), 'dlux_bk_voucher' ) ) {
		wp_send_json_error( array( 'message' => 'Sorry, something went wrong. Please refresh the page and try again.' ), 400 );
	}
	$success = array( 'message' => dlux_bk_voucher_field( 'success_message' ) );
	if ( ! empty( $_POST['website'] ) ) { // Honeypot.
		wp_send_json_success( $success );
	}

	$name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	if ( '' === $name || ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'Please enter your name and a valid email address.' ), 422 );
	}

	// Same email only once.
	$rows = function_exists( 'get_field' ) ? get_field( 'signups', DLUX_BK_VOUCHER_ID, false ) : array();
	foreach ( (array) $rows as $row ) {
		$existing = is_array( $row ) ? ( $row['email'] ?? ( $row['field_dlux_vc_su_email'] ?? '' ) ) : '';
		if ( strtolower( (string) $existing ) === strtolower( $email ) ) {
			wp_send_json_success( $success );
		}
	}

	$saved = function_exists( 'add_row' ) && add_row(
		'field_dlux_vc_signups',
		array(
			'field_dlux_vc_su_name'  => $name,
			'field_dlux_vc_su_email' => $email,
			'field_dlux_vc_su_date'  => wp_date( 'j M Y, g:i a' ),
		),
		DLUX_BK_VOUCHER_ID
	);
	if ( ! $saved ) {
		wp_send_json_error( array( 'message' => 'Sorry, we couldn’t save your details. Please try again.' ), 500 );
	}

	$biz  = dlux_bk_business();
	$from = 'From: ' . wp_specialchars_decode( $biz['name'], ENT_QUOTES ) . ' <' . get_option( 'admin_email' ) . '>';

	// 1) The subscriber – at the email they typed in the popup.
	$tokens  = array(
		'{name}'     => esc_html( $name ),
		'{business}' => esc_html( $biz['name'] ),
	);
	$subject = wp_strip_all_tags( strtr( (string) dlux_bk_voucher_field( 'email_subject' ), array( '{name}' => $name, '{business}' => wp_specialchars_decode( $biz['name'], ENT_QUOTES ) ) ) );
	$body    = wpautop( strtr( (string) dlux_bk_voucher_field( 'email_message' ), $tokens ) );
	dlux_bk_mail(
		$email,
		$subject,
		array(
			'preheader' => $subject,
			'eyebrow'   => 'A little gift, just for you',
			'title'     => $subject,
			'html'      => $body,
		),
		array( $from, 'Reply-To: ' . wp_specialchars_decode( $biz['name'], ENT_QUOTES ) . ' <' . get_option( 'admin_email' ) . '>' )
	);

	// 2) The shop (same address as new booking emails).
	$to = trim( (string) dlux_bk_opt( 'notify_email', '' ) );
	$to = apply_filters( 'dlux_booking_admin_email', $to ? $to : get_option( 'admin_email' ) );
	if ( $to ) {
		dlux_bk_mail(
			$to,
			'New gift voucher sign-up: ' . $name,
			array(
				'preheader' => $name . ' signed up for the 10% off offer.',
				'eyebrow'   => 'Gift voucher popup',
				'title'     => 'New sign-up',
				'intro'     => array( 'Someone just signed up through the gift voucher popup.' ),
				'rows'      => array(
					array( 'Name', $name ),
					array( 'Email', $email ),
					array( 'Signed up', wp_date( 'j M Y, g:i a' ) ),
				),
				'button'    => array( 'View all sign-ups', admin_url( 'admin.php?page=' . DLUX_BK_VOUCHER_PAGE ) ),
			),
			array( $from, 'Reply-To: ' . $email )
		);
	}

	wp_send_json_success( $success );
}
add_action( 'wp_ajax_dlux_bk_voucher', 'dlux_bk_voucher_signup' );
add_action( 'wp_ajax_nopriv_dlux_bk_voucher', 'dlux_bk_voucher_signup' );
