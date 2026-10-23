<?php
/**
 * Template Name: Coming Soon
 *
 * Stand-alone "coming soon" page (no header/footer menus).
 * Contact details use Theme Settings when filled in, otherwise the defaults below.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$phone   = dlux_option( 'phone', '0410 111 190' );
$email   = dlux_option( 'email', 'info@dluxwash.com.au' );
$locs    = (array) dlux_option( 'locations', [] );
$address = ! empty( $locs[0]['address'] ) ? $locs[0]['address'] : '321–325 Williamstown Rd, Yarraville VIC';
$map     = ! empty( $locs[0]['directions_url'] ) ? $locs[0]['directions_url'] : 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $address );
$socials = (array) dlux_option( 'social_links', [] );
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'coming-soon-page' ); ?>>
<?php wp_body_open(); ?>

<main class="soon">
	<div class="soon__glow" aria-hidden="true"></div>

	<div class="container soon__inner">
		<?php echo dlux_logo( dlux_option( 'site_logo' ), 'logo soon__logo' ); // phpcs:ignore ?>

		<div class="soon__content">
			<p class="eyebrow"><?php esc_html_e( 'Hand car wash & detailing · Yarraville', 'dlux' ); ?></p>
			<h1 class="soon__title"><?php echo wp_kses( __( 'Something <span>shiny</span><br>is coming soon', 'dlux' ), [ 'span' => [], 'br' => [] ] ); ?></h1>
			<p class="soon__text"><?php esc_html_e( 'We’re polishing up our new website. Online bookings, memberships and gift vouchers are on their way – in the meantime, we’re still washing and detailing every day. Call, text or drop in and we’ll look after you.', 'dlux' ); ?></p>

			<ul class="soon__contact">
				<li>
					<span class="contact__icon"><?php echo function_exists( 'dlux_contact_icon' ) ? dlux_contact_icon( 'location' ) : ''; // phpcs:ignore ?></span>
					<span>
						<span class="contact__label"><?php esc_html_e( 'Visit us', 'dlux' ); ?></span>
						<a class="contact__value" href="<?php echo esc_url( $map ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $address ); ?></a>
					</span>
				</li>
				<li>
					<span class="contact__icon"><?php echo function_exists( 'dlux_contact_icon' ) ? dlux_contact_icon( 'message' ) : ''; // phpcs:ignore ?></span>
					<span>
						<span class="contact__label"><?php esc_html_e( 'Call or text', 'dlux' ); ?></span>
						<a class="contact__value" href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
					</span>
				</li>
				<li>
					<span class="contact__icon"><?php echo function_exists( 'dlux_contact_icon' ) ? dlux_contact_icon( 'email' ) : ''; // phpcs:ignore ?></span>
					<span>
						<span class="contact__label"><?php esc_html_e( 'Email us', 'dlux' ); ?></span>
						<a class="contact__value" href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
					</span>
				</li>
				<li>
					<span class="contact__icon"><?php echo function_exists( 'dlux_contact_icon' ) ? dlux_contact_icon( 'clock' ) : ''; // phpcs:ignore ?></span>
					<span>
						<span class="contact__label"><?php esc_html_e( 'Open', 'dlux' ); ?></span>
						<span class="contact__value"><?php esc_html_e( 'Every day, 9am – 5pm', 'dlux' ); ?></span>
					</span>
				</li>
			</ul>

			<div class="btn-group">
				<a class="btn btn--primary" href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><span><?php esc_html_e( 'Call us now', 'dlux' ); ?></span><span class="btn__arrow" aria-hidden="true">&rarr;</span></a>
				<a class="btn btn--outline" href="<?php echo esc_url( $map ); ?>" target="_blank" rel="noopener"><span><?php esc_html_e( 'Get directions', 'dlux' ); ?></span><span class="btn__arrow" aria-hidden="true">&rarr;</span></a>
			</div>
		</div>

		<div class="soon__footer">
			<?php if ( $socials ) : ?>
				<ul class="socials">
					<?php foreach ( $socials as $s ) :
						if ( empty( $s['url'] ) ) continue; ?>
						<li><a href="<?php echo esc_url( $s['url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ucfirst( $s['platform'] ) ); ?>"><?php echo dlux_social_icon( $s['platform'] ); // phpcs:ignore ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<p class="copyright"><?php echo esc_html( dlux_tokens( dlux_option( 'copyright_text', '© {year} ' . get_bloginfo( 'name' ) . '. All Rights Reserved.' ) ) ); ?></p>
		</div>
	</div>
</main>

<?php wp_footer(); ?>
</body>
</html>
