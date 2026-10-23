<?php
if (!defined('ABSPATH'))
	exit;

/** Get a Theme Settings value with a default (used only when the value was never set). */
function dlux_option($name, $default = null)
{
	if (!function_exists('get_field'))
		return $default;
	$v = get_field($name, 'option');
	return (null === $v || '' === $v) ? $default : $v;
}

/** Replace tokens like {year}, {rating}, {since}. */
function dlux_tokens($text)
{
	return strtr((string) $text, [
		'{year}' => wp_date('Y'),
		'{rating}' => (string) dlux_option('google_rating', '4.9'),
		'{since}' => (string) dlux_option('established_year', '2010'),
	]);
}

/** Headings allow <span> (accent), <br>, <em>, <strong>. */
function dlux_heading($html)
{
	$allowed = ['span' => ['class' => []], 'br' => [], 'em' => [], 'strong' => []];
	return wp_kses(dlux_tokens($html), $allowed);
}

/** Render an SCF link field as a button. */
function dlux_button($link, $class = 'btn--primary', $arrow = true)
{
	if (empty($link['url']))
		return '';
	$title = !empty($link['title']) ? $link['title'] : __('Learn more', 'dlux');
	$target = !empty($link['target']) ? $link['target'] : '_self';
	return sprintf(
		'<a class="btn %1$s" href="%2$s" target="%3$s"%4$s><span>%5$s</span>%6$s</a>',
		esc_attr($class),
		esc_url($link['url']),
		esc_attr($target),
		'_blank' === $target ? ' rel="noopener"' : '',
		esc_html($title),
		$arrow ? '<span class="btn__arrow" aria-hidden="true">&rarr;</span>' : ''
	);
}

/** Star rating markup. */
function dlux_stars($rating = 5, $label = true)
{
	$rating = max(0, min(5, (int) round((float) $rating)));
	$attr = $label
		? ' role="img" aria-label="' . esc_attr(sprintf(__('%d out of 5 stars', 'dlux'), $rating)) . '"'
		: ' aria-hidden="true"';
	$out = '<span class="stars"' . $attr . '>';
	for ($i = 1; $i <= 5; $i++) {
		$out .= '<span class="star' . ($i > $rating ? ' is-off' : '') . '">&#9733;</span>';
	}
	return $out . '</span>';
}

/** Section id attribute. */
function dlux_section_id($id)
{
	$id = sanitize_title((string) $id);
	return $id ? ' id="' . esc_attr($id) . '"' : '';
}

/** Eyebrow + heading block. */
function dlux_section_head($eyebrow, $heading, $tag = 'h2')
{
	$out = '';
	if ($eyebrow)
		$out .= '<p class="eyebrow eyebrow--large">' . esc_html(dlux_tokens($eyebrow)) . '</p>';
	if ($heading)
		$out .= '<' . $tag . ' class="section-title">' . dlux_heading($heading) . '</' . $tag . '>';
	return $out;
}

/** Logo markup (image or text fallback). */
function dlux_logo($image = null, $class = 'logo')
{
	$home = esc_url(home_url('/'));
	$name = get_bloginfo('name');
	if (!empty($image['ID'])) {
		$img = wp_get_attachment_image($image['ID'], 'medium', false, ['alt' => $name, 'class' => 'logo__img']);
		return '<a class="' . esc_attr($class) . '" href="' . $home . '" rel="home">' . $img . '</a>';
	}
	$text = $name ? strtoupper(strtok($name, ' ')) : 'DLUX';
	return '<a class="' . esc_attr($class) . ' logo--text" href="' . $home . '" rel="home">' . esc_html($text) . '<span class="logo__dot">.</span></a>';
}

/** Map embed: accepts iframe HTML or a plain URL. */
function dlux_map($embed)
{
	$embed = trim((string) $embed);
	if (!$embed)
		return '';
	if (false === stripos($embed, '<iframe') && filter_var($embed, FILTER_VALIDATE_URL)) {
		$embed = '<iframe src="' . esc_url($embed) . '"></iframe>';
	}
	$allowed = [
		'iframe' => [
			'src' => [],
			'width' => [],
			'height' => [],
			'style' => [],
			'allowfullscreen' => [],
			'loading' => [],
			'referrerpolicy' => [],
			'title' => [],
			'frameborder' => [],
			'allow' => [],
		]
	];
	$embed = wp_kses($embed, $allowed);
	if (false === stripos($embed, 'loading='))
		$embed = str_ireplace('<iframe', '<iframe loading="lazy"', $embed);
	if (false === stripos($embed, 'title='))
		$embed = str_ireplace('<iframe', '<iframe title="' . esc_attr__('Map', 'dlux') . '"', $embed);
	return $embed;
}

/** Does the current page's first section use the hero layout? */
function dlux_page_starts_with_hero()
{
	if (!function_exists('get_field'))
		return false;
	$rows = get_field('page_sections', get_queried_object_id());
	return !empty($rows[0]['acf_fc_layout']) && 'hero' === $rows[0]['acf_fc_layout'];
}

/** Fallback menu when none is assigned. */
function dlux_menu_fallback($args)
{
	$links = [
		'#prices' => __('Prices', 'dlux'),
		'#detailing' => __('Detailing', 'dlux'),
		'#memberships' => __('Memberships', 'dlux'),
		'#gift-vouchers' => __('Gift vouchers', 'dlux'),
		'#visit' => __('Visit us', 'dlux'),
	];
	$class = !empty($args['menu_class']) ? $args['menu_class'] : 'menu';
	echo '<ul class="' . esc_attr($class) . '">';
	foreach ($links as $href => $label) {
		echo '<li class="menu-item"><a href="' . esc_url(home_url('/') . $href) . '">' . esc_html($label) . '</a></li>';
	}
	echo '</ul>';
}

/**
 * Header fallback: the header menu comes only from Appearance > Menus ("Primary Menu").
 * Visitors see nothing until a menu is assigned; admins see a link to set it up.
 */
function dlux_header_menu_fallback($args)
{
	if (!current_user_can('edit_theme_options'))
		return;
	printf(
		'<ul class="%1$s"><li class="menu-item"><a href="%2$s">%3$s</a></li></ul>',
		esc_attr(!empty($args['menu_class']) ? $args['menu_class'] : 'menu'),
		esc_url(admin_url('nav-menus.php?action=locations')),
		esc_html__('+ Assign a Primary Menu', 'dlux')
	);
}

/**
 * Footer fallback: the footer menu comes only from Appearance > Menus ("Footer Menu").
 * Visitors see nothing until a menu is assigned; admins see a link to set it up.
 */
function dlux_footer_menu_fallback($args)
{
	if (!current_user_can('edit_theme_options'))
		return;
	printf(
		'<ul class="%1$s"><li class="menu-item"><a href="%2$s">%3$s</a></li></ul>',
		esc_attr(!empty($args['menu_class']) ? $args['menu_class'] : 'menu'),
		esc_url(admin_url('nav-menus.php?action=locations')),
		esc_html__('+ Assign a Footer Menu', 'dlux')
	);
}

/** Does the current page use a given flexible-content layout? */
function dlux_page_has_layout( $layout ) {
	if ( ! function_exists( 'get_field' ) || ! is_singular() ) return false;
	$rows = get_field( 'page_sections', get_queried_object_id() );
	if ( ! is_array( $rows ) ) return false;
	foreach ( $rows as $row ) {
		if ( isset( $row['acf_fc_layout'] ) && $layout === $row['acf_fc_layout'] ) return true;
	}
	return false;
}

/**
 * Link for a Visit us row (Theme Settings › Locations):
 * Directions URL if filled in, otherwise worked out from the text –
 * email → mailto:, phone number → tel:, street address → Google Maps.
 */
function dlux_location_link( array $l ) {
	if ( ! empty( $l['directions_url'] ) ) return (string) $l['directions_url'];
	$text = trim( (string) ( $l['address'] ?? '' ) );
	if ( '' === $text ) return '';
	if ( is_email( $text ) ) return 'mailto:' . $text;
	$digits = preg_replace( '/[^0-9+]/', '', $text );
	if ( preg_match( '/^[0-9+\s().-]+$/', $text ) && strlen( $digits ) >= 8 ) return 'tel:' . $digits;
	return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $text );
}

/** The street address among the Locations rows (not the email / phone rows). */
function dlux_location_address( array $locations ) {
	foreach ( $locations as $l ) {
		$text = trim( (string) ( $l['address'] ?? '' ) );
		if ( '' === $text || is_email( $text ) || preg_match( '/^[0-9+\s().-]+$/', $text ) ) continue;
		return $text;
	}
	return '';
}

/**
 * Google map with a marker on the business address.
 * Keeps satellite view if the saved embed used it. Falls back to the saved embed.
 */
function dlux_map_with_marker( $embed, array $locations ) {
	$address = dlux_location_address( $locations );
	if ( '' === $address ) return $embed;
	$satellite = (bool) preg_match( '/!5e1|[?&]t=k/', (string) $embed );
	$src = 'https://maps.google.com/maps?q=' . rawurlencode( $address ) . '&t=' . ( $satellite ? 'k' : 'm' ) . '&z=17&ie=UTF8&iwloc=&output=embed';
	return '<iframe src="' . esc_url( $src ) . '" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="' . esc_attr( $address ) . '"></iframe>';
}
