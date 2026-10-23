<?php
/**
 * Shortcodes and front-end scripts.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function dlux_bk_enqueue() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	$url  = plugin_dir_url( DLUX_BOOKING_FILE );
	wp_enqueue_style( 'dlux-booking-fonts', 'https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,600;0,700;1,700;1,800&family=Barlow:wght@400;500;600;700&display=swap', array(), null ); // phpcs:ignore
	wp_enqueue_style( 'dlux-booking', $url . 'assets/dlux-booking.css', array(), DLUX_BOOKING_VER );
	wp_enqueue_script( 'dlux-booking', $url . 'assets/dlux-booking.js', array(), DLUX_BOOKING_VER, true );
	wp_localize_script(
		'dlux-booking',
		'DLUX_CFG',
		array(
			'api'        => esc_url_raw( rest_url( 'dlux/v1/' ) ),
			'confirmUrl' => dlux_bk_page_url( 'confirm' ),
			'bookUrl'    => dlux_bk_page_url( 'booking' ),
			'phone'      => dlux_bk_business()['phone'],
			'payCard'    => dlux_bk_stripe_ready(),
			'payShop'    => dlux_bk_pay_shop_on(),
		)
	);
}

function dlux_bk_mount( $kind ) {
	dlux_bk_enqueue();
	return '<div class="dlux-w" data-dlux="' . esc_attr( $kind ) . '"><p class="dlux-loading">Loading…</p><noscript>Please enable JavaScript to book online, or call us to book.</noscript></div>';
}

add_shortcode( 'dlux_booking', function () { return dlux_bk_mount( 'booking' ); } );
add_shortcode( 'dlux_menu', function () { return dlux_bk_mount( 'menu' ); } );
add_shortcode( 'dlux_confirmation', function () { return dlux_bk_mount( 'confirmation' ); } );
// Memberships were removed in 4.0. Keep the old shortcode from printing as text.
add_shortcode( 'dlux_memberships', '__return_empty_string' );

/* Booking and confirmation pages must never be cached. */
add_action(
	'template_redirect',
	function () {
		if ( ! is_page() ) {
			return;
		}
		$id = get_queried_object_id();
		if ( $id && in_array( $id, array( (int) dlux_bk_opt( 'booking_page', 0 ), (int) dlux_bk_opt( 'confirm_page', 0 ) ), true ) ) {
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
			nocache_headers();
		}
	}
);

/**
 * Pricing cards on the front page: any button that links to the booking page
 * gets ?package=...&size=... added, so the right package and size are
 * already chosen when the customer arrives.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		$book = dlux_bk_page_url( 'booking' );
		if ( ! $book ) {
			return;
		}
		wp_register_script( 'dlux-booking-links', false, array(), DLUX_BOOKING_VER, true );
		wp_enqueue_script( 'dlux-booking-links' );
		wp_add_inline_script(
			'dlux-booking-links',
			'(function(){var B=' . wp_json_encode( untrailingslashit( strtok( $book, '?' ) ) ) . ';' .
			'function slug(s){return String(s||"").toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g,"").replace(/&[a-z]+;/g,"").replace(/[^a-z0-9]+/g,"-").replace(/^-+|-+$/g,"");}' .
			'function run(){document.querySelectorAll(".package a[href]").forEach(function(a){try{var u=new URL(a.href,location.href);' .
			'if(u.origin+u.pathname.replace(/\/$/,"")!==B.replace(/\/$/,""))return;var card=a.closest(".package"),name=card.querySelector(".package__name");' .
			'var panel=card.closest("[role=tabpanel],.pricing__grid"),size="";if(panel&&panel.getAttribute("aria-labelledby")){var t=document.getElementById(panel.getAttribute("aria-labelledby"));if(t)size=t.textContent.trim();}' .
			'if(name&&!u.searchParams.has("package"))u.searchParams.set("package",slug(name.textContent));if(size&&!u.searchParams.has("size"))u.searchParams.set("size",size);a.href=u.toString();}catch(e){}});}' .
			'if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",run);else run();})();'
		);
	}
);
