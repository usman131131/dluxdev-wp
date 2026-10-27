<?php
/**
 * DLUX theme functions.
 */
if (!defined('ABSPATH'))
	exit;

define('DLUX_VERSION', '1.9.1');
define('DLUX_DIR', get_template_directory());
define('DLUX_URI', get_template_directory_uri());

require DLUX_DIR . '/inc/helpers.php';
require DLUX_DIR . '/inc/icons.php';
require DLUX_DIR . '/inc/booking.php';
require DLUX_DIR . '/inc/contact.php';
require DLUX_DIR . '/inc/voucher-popup.php';
require DLUX_DIR . '/inc/showcase.php';
require DLUX_DIR . '/inc/svg.php';
require DLUX_DIR . '/inc/quote-requests.php';
require DLUX_DIR . '/inc/quote.php';
require DLUX_DIR . '/inc/bd-services.php';

/* ---------- Classic editor (posts, pages and widgets) ---------- */
add_filter('use_block_editor_for_post', '__return_false', 100);
add_filter('use_block_editor_for_post_type', '__return_false', 100);
add_filter('use_widgets_block_editor', '__return_false');
add_filter('gutenberg_use_widgets_block_editor', '__return_false');

/* ---------- Theme setup ---------- */
add_action('after_setup_theme', function () {
	load_theme_textdomain('dlux', DLUX_DIR . '/languages');
	add_theme_support('title-tag');
	add_theme_support('post-thumbnails');
	add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets']);
	add_theme_support('responsive-embeds');
	register_nav_menus([
		'primary' => __('Primary Menu', 'dlux'),
		'footer' => __('Footer Menu', 'dlux'),
	]);
	add_image_size('dlux-card', 760, 420, true);
	add_image_size('dlux-thumb', 120, 120, true);
});

/* ---------- Assets ---------- */
// add_action('wp_enqueue_scripts', function () {
// 	wp_enqueue_style('dlux-main', DLUX_URI . '/assets/css/main.css', [], DLUX_VERSION);
// 	wp_add_inline_style('dlux-main', dlux_css_variables());
// 	wp_enqueue_script('dlux-main', DLUX_URI . '/assets/js/main.js', [], DLUX_VERSION, ['strategy' => 'defer', 'in_footer' => true]);
// });

add_action('wp_enqueue_scripts', function () {

	$css_path = get_stylesheet_directory() . '/assets/css/main.css';
	$js_path = get_stylesheet_directory() . '/assets/js/main.js';

	$css_version = file_exists($css_path)
		? filemtime($css_path)
		: DLUX_VERSION;

	$js_version = file_exists($js_path)
		? filemtime($js_path)
		: DLUX_VERSION;

	wp_enqueue_style(
		'dlux-main',
		DLUX_URI . '/assets/css/main.css',
		[],
		$css_version
	);

	wp_add_inline_style('dlux-main', dlux_css_variables());

	wp_enqueue_script(
		'dlux-main',
		DLUX_URI . '/assets/js/main.js',
		[],
		$js_version,
		[
			'strategy' => 'defer',
			'in_footer' => true,
		]
	);

});


/* ---------- Slick carousel (only on pages with the "Beyond the wash" section) ---------- */
add_action('wp_enqueue_scripts', function () {
	if (!dlux_page_has_layout('services')) return;

	wp_enqueue_style('slick', DLUX_URI . '/assets/vendor/slick/slick.css', [], '1.8.1');
	wp_enqueue_script('slick', DLUX_URI . '/assets/vendor/slick/slick.min.js', ['jquery'], '1.8.1', true);

	$path = get_stylesheet_directory() . '/assets/js/carousel.js';
	wp_enqueue_script(
		'dlux-carousel',
		DLUX_URI . '/assets/js/carousel.js',
		['jquery', 'slick'],
		file_exists($path) ? filemtime($path) : DLUX_VERSION,
		true
	);
}, 20);

/* ---------- Selectable packages + extras (Pricing / Beyond the wash) ---------- */
add_action('wp_enqueue_scripts', function () {
	if (!dlux_page_has_layout('pricing') && !dlux_page_has_layout('services')) return;

	$path = get_stylesheet_directory() . '/assets/js/booking-select.js';
	wp_enqueue_script(
		'dlux-booking-select',
		DLUX_URI . '/assets/js/booking-select.js',
		[],
		file_exists($path) ? filemtime($path) : DLUX_VERSION,
		true
	);
}, 20);

/* ---------- Contact Us form (AJAX) – only on pages with the section ---------- */
add_action('wp_enqueue_scripts', function () {
	if (!dlux_page_has_layout('contact_us')) return;

	$path = get_stylesheet_directory() . '/assets/js/contact.js';
	wp_enqueue_script(
		'dlux-contact',
		DLUX_URI . '/assets/js/contact.js',
		[],
		file_exists($path) ? filemtime($path) : DLUX_VERSION,
		true
	);
	wp_localize_script('dlux-contact', 'dluxContact', [
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'sending' => __('Sending…', 'dlux'),
		'success' => __('Thanks! Your message has been sent.', 'dlux'),
		'error'   => __('Sorry, something went wrong. Please try again or contact us directly.', 'dlux'),
	]);
}, 20);

/* ---------- Detailing packages toggle + Info tabs – only on pages with those sections ---------- */
add_action('wp_enqueue_scripts', function () {
	if (!dlux_page_has_layout('detail_packages') && !dlux_page_has_layout('info_tabs') && !dlux_page_has_layout('bd_services')) return;

	$path = get_stylesheet_directory() . '/assets/js/sections.js';
	wp_enqueue_script(
		'dlux-sections',
		DLUX_URI . '/assets/js/sections.js',
		[],
		file_exists($path) ? filemtime($path) : DLUX_VERSION,
		true
	);
}, 20);

/* ---------- Quote form (Beyond Detailing) – only on pages with the section ---------- */
add_action('wp_enqueue_scripts', function () {
	if (!dlux_page_has_layout('quote_form')) return;

	$path = get_stylesheet_directory() . '/assets/js/quote.js';
	wp_enqueue_script(
		'dlux-quote',
		DLUX_URI . '/assets/js/quote.js',
		[],
		file_exists($path) ? filemtime($path) : DLUX_VERSION,
		true
	);
	wp_localize_script('dlux-quote', 'dluxQuote', [
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'sending' => __('Sending…', 'dlux'),
		'success' => __('Thanks! We’ll be in touch with your quote soon.', 'dlux'),
		'invalid' => __('Please add a mobile number or email so we can reply.', 'dlux'),
		'error'   => __('Sorry, something went wrong. Please call or email us instead.', 'dlux'),
	]);
}, 20);

/* Preload the two most-used local fonts (heading + paragraph) */
add_action('wp_head', function () {
	foreach (['nokia-expanded/NokiaExpanded-Black.woff2', 'sequel-sans/SequelSansBody-Roman.woff2'] as $font) {
		printf('<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url(DLUX_URI . '/assets/fonts/' . $font));
	}
}, 2);

/* Colour overrides from Theme Settings */
function dlux_css_variables()
{
	$map = [
		'--accent' => dlux_option('accent_color'),
		'--bg' => dlux_option('background_color'),
		'--surface' => dlux_option('surface_color'),
	];
	$css = '';
	foreach ($map as $var => $val) {
		if ($val && preg_match('/^#[0-9a-fA-F]{3,8}$/', $val)) {
			$css .= $var . ':' . $val . ';';
		}
	}
	return $css ? ':root{' . $css . '}' : '';
}

/* ---------- Site icon from Theme Settings ---------- */
add_action('wp_head', function () {
	if (has_site_icon())
		return;
	$icon = dlux_option('site_icon');
	if (!empty($icon['url'])) {
		printf('<link rel="icon" href="%s">' . "\n", esc_url($icon['url']));
	}
}, 5);

/* ---------- Custom scripts (admin controlled) ---------- */
add_action('wp_head', function () {
	$s = dlux_option('head_scripts');
	if ($s)
		echo $s . "\n"; // phpcs:ignore -- entered by administrators.
}, 99);
add_action('wp_footer', function () {
	$s = dlux_option('footer_scripts');
	if ($s)
		echo $s . "\n"; // phpcs:ignore
}, 99);

/* ---------- Options page fallback ----------
 * If the "Theme Settings" options page was not imported via JSON,
 * register it in code so the field group still has a home. */
add_action('acf/init', function () {
	if (!function_exists('acf_add_options_page'))
		return;
	if (function_exists('acf_get_options_page') && acf_get_options_page('theme-settings'))
		return;
	acf_add_options_page([
		'page_title' => __('Theme Settings', 'dlux'),
		'menu_title' => __('Theme Settings', 'dlux'),
		'menu_slug' => 'theme-settings',
		'capability' => 'edit_theme_options',
		'icon_url' => 'dashicons-admin-customizer',
		'position' => 59,
	]);
}, 99);

/* ---------- Menus shortcut under Theme Settings ----------
 * Header/footer menus are managed in Appearance > Menus; this adds a
 * "Menus" link inside the Theme Settings admin menu as well. */
add_action('admin_menu', function () {
	global $admin_page_hooks;
	if (empty($admin_page_hooks['theme-settings']))
		return;
	add_submenu_page('theme-settings', __('Settings', 'dlux'), __('Settings', 'dlux'), 'edit_theme_options', 'theme-settings');
	add_submenu_page('theme-settings', __('Menus', 'dlux'), __('Header & Footer Menus', 'dlux'), 'edit_theme_options', 'nav-menus.php');
}, 999);

/* ---------- Notice if SCF/ACF is missing ---------- */
add_action('admin_notices', function () {
	if (function_exists('get_field'))
		return;
	echo '<div class="notice notice-warning"><p><strong>DLUX theme:</strong> ' .
		esc_html__('Install and activate Secure Custom Fields (or ACF Pro) to use page sections and Theme Settings.', 'dlux') .
		'</p></div>';
});

/* Body classes */
add_filter('body_class', function ($classes) {
	if (dlux_option('sticky_header', true))
		$classes[] = 'has-sticky-header';
	if (is_page() && dlux_page_starts_with_hero() && dlux_option('transparent_header', true)) {
		$classes[] = 'has-transparent-header';
	}
	return $classes;
});
