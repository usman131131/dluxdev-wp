<?php
/**
 * Gift voucher popup.
 * Content, form fields and sign-ups live in the Dlux Booking plugin (Gift Voucher Popup page);
 * the theme opens it when the gift voucher promo card is clicked.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function dlux_voucher_popup_enabled() {
	return function_exists( 'dlux_bk_voucher_popup_html' );
}

function dlux_voucher_popup_on_page() {
	return dlux_voucher_popup_enabled() && dlux_page_has_layout( 'promo_cards' );
}

add_action( 'wp_enqueue_scripts', function () {
	if ( ! dlux_voucher_popup_on_page() ) return;
	$path = get_stylesheet_directory() . '/assets/js/voucher.js';
	wp_enqueue_script( 'dlux-voucher', DLUX_URI . '/assets/js/voucher.js', [], file_exists( $path ) ? filemtime( $path ) : DLUX_VERSION, true );
	wp_localize_script( 'dlux-voucher', 'dluxVoucher', [
		'sending' => __( 'Sending…', 'dlux' ),
		'error'   => __( 'Sorry, something went wrong. Please try again.', 'dlux' ),
	] );
}, 20 );

add_action( 'wp_footer', function () {
	if ( ! dlux_voucher_popup_on_page() ) return;
	?>
	<div class="voucher-modal" id="gift-voucher" data-voucher-modal role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Gift voucher', 'dlux' ); ?>" hidden>
		<div class="voucher-modal__overlay" data-voucher-close></div>
		<div class="voucher-modal__box" tabindex="-1">
			<button type="button" class="voucher-modal__close" data-voucher-close aria-label="<?php esc_attr_e( 'Close', 'dlux' ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
			</button>
			<?php echo dlux_bk_voucher_popup_html(); // phpcs:ignore -- escaped in the plugin. ?>
		</div>
	</div>
	<?php
}, 5 );
