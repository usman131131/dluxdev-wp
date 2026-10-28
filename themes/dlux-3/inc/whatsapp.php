<?php
/**
 * Header WhatsApp button.
 * Settings: Theme Settings › "Header WhatsApp button" box (fields registered here, no import needed).
 * When a number or link is set, the header shows a WhatsApp icon instead of the secondary button.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) return;
	acf_add_local_field_group( [
		'key'        => 'group_dlux_whatsapp',
		'title'      => __( 'Header WhatsApp button', 'dlux' ),
		'menu_order' => 5,
		'location'   => [ [ [ 'param' => 'options_page', 'operator' => '==', 'value' => 'theme-settings' ] ] ],
		'fields'     => [
			[
				'key'           => 'field_dlux_wa_enabled',
				'name'          => 'whatsapp_enabled',
				'label'         => __( 'Show WhatsApp button in the header', 'dlux' ),
				'type'          => 'true_false',
				'ui'            => 1,
				'default_value' => 1,
				'instructions'  => __( 'Replaces the header’s secondary button (e.g. "Contact us") with a WhatsApp icon. Turn off to show the secondary button again.', 'dlux' ),
				'wrapper'       => [ 'width' => '100' ],
			],
			[
				'key'          => 'field_dlux_wa_number',
				'name'         => 'whatsapp_number',
				'label'        => __( 'WhatsApp number', 'dlux' ),
				'type'         => 'text',
				'placeholder'  => '0410 111 190',
				'instructions' => __( 'Mobile number on WhatsApp. Local numbers (0410…) get the country code added automatically.', 'dlux' ),
				'wrapper'      => [ 'width' => '33' ],
			],
			[
				'key'          => 'field_dlux_wa_message',
				'name'         => 'whatsapp_message',
				'label'        => __( 'Pre-filled message', 'dlux' ),
				'type'         => 'text',
				'placeholder'  => 'Hi Dlux, I have a question about…',
				'instructions' => __( 'Optional. Typed into the chat for the visitor.', 'dlux' ),
				'wrapper'      => [ 'width' => '34' ],
			],
			[
				'key'          => 'field_dlux_wa_link',
				'name'         => 'whatsapp_link',
				'label'        => __( 'Or a custom WhatsApp link', 'dlux' ),
				'type'         => 'url',
				'placeholder'  => 'https://wa.me/61410111190',
				'instructions' => __( 'Optional. Used instead of the number, e.g. a wa.me or WhatsApp Business link.', 'dlux' ),
				'wrapper'      => [ 'width' => '33' ],
			],
			[
				'key'           => 'field_dlux_wa_label',
				'name'          => 'whatsapp_label',
				'label'         => __( 'Button label', 'dlux' ),
				'type'          => 'text',
				'default_value' => 'Chat on WhatsApp',
				'instructions'  => __( 'Shown as a tooltip, read out by screen readers, and shown next to the icon in the mobile menu.', 'dlux' ),
			],
		],
	] );
} );

/** WhatsApp link from Theme Settings, or '' when not set / switched off. */
function dlux_whatsapp_url() {
	$on = dlux_option( 'whatsapp_enabled', 1 );
	if ( ! $on || '0' === $on ) return '';

	$link = trim( (string) dlux_option( 'whatsapp_link', '' ) );
	if ( $link ) return $link;

	$num = preg_replace( '/[^0-9+]/', '', (string) dlux_option( 'whatsapp_number', '' ) );
	if ( '' === $num ) return '';
	if ( 0 === strpos( $num, '+' ) ) {
		$num = substr( $num, 1 );
	} elseif ( 0 === strpos( $num, '00' ) ) {
		$num = substr( $num, 2 );
	} elseif ( 0 === strpos( $num, '0' ) ) {
		$cc  = function_exists( 'dlux_bk_opt' ) ? (string) dlux_bk_opt( 'sms_country', '+61' ) : '+61';
		$num = preg_replace( '/\D/', '', $cc ?: '61' ) . ltrim( $num, '0' );
	}
	$url = 'https://wa.me/' . preg_replace( '/\D/', '', $num );
	$msg = trim( (string) dlux_option( 'whatsapp_message', '' ) );
	return $msg ? add_query_arg( 'text', rawurlencode( $msg ), $url ) : $url;
}

/** Header WhatsApp icon button. */
function dlux_whatsapp_button() {
	$url = dlux_whatsapp_url();
	if ( ! $url ) return '';
	$label = trim( (string) dlux_option( 'whatsapp_label', '' ) ) ?: __( 'Chat on WhatsApp', 'dlux' );
	return sprintf(
		'<a class="header-whatsapp" href="%1$s" target="_blank" rel="noopener" aria-label="%2$s" title="%2$s">%3$s<span class="header-whatsapp__label">%4$s</span></a>',
		esc_url( $url ),
		esc_attr( $label ),
		'<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.64-2.05-.17-.3-.02-.46.13-.6.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.6-.92-2.2-.24-.58-.49-.5-.67-.5h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.22 1.36.19 1.87.12.57-.09 1.75-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.12-.27-.2-.57-.35zM12.04 21.5h-.01a9.43 9.43 0 01-4.8-1.32l-.35-.2-3.57.93.95-3.48-.22-.36a9.4 9.4 0 01-1.44-5.02c0-5.2 4.24-9.44 9.45-9.44a9.38 9.38 0 016.68 2.77 9.38 9.38 0 012.76 6.68c0 5.2-4.24 9.44-9.45 9.44zm8.04-17.48A11.3 11.3 0 0012.04.7C5.77.7.66 5.8.66 12.08c0 2 .52 3.96 1.52 5.69L.57 23.7l6.07-1.6a11.33 11.33 0 005.4 1.38h.01c6.27 0 11.38-5.11 11.38-11.39 0-3.04-1.18-5.9-3.35-8.06z"/></svg>',
		esc_html( $label )
	);
}
