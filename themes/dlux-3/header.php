<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'dlux' ); ?></a>

<header class="site-header" data-header>
	<div class="container site-header__inner">
		<?php echo dlux_logo( dlux_option( 'header_logo' ) ?: dlux_option( 'site_logo' ) ); // phpcs:ignore ?>

		<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
			<span class="nav-toggle__bar"></span><span class="nav-toggle__bar"></span><span class="nav-toggle__bar"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'dlux' ); ?></span>
		</button>

		<div class="site-nav" id="site-nav" data-nav>
			<nav aria-label="<?php esc_attr_e( 'Primary', 'dlux' ); ?>">
				<?php
				wp_nav_menu( [
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'menu',
					'depth'          => 1,
					'fallback_cb'    => 'dlux_header_menu_fallback',
				] );
				?>
			</nav>
			<div class="site-header__actions">
				<?php
				echo dlux_button( dlux_option( 'header_primary_button' ), 'btn--primary btn--sm' ); // phpcs:ignore
				// WhatsApp icon (Theme Settings › Header WhatsApp button) replaces the secondary button when set.
				$wa = function_exists( 'dlux_whatsapp_button' ) ? dlux_whatsapp_button() : '';
				echo $wa ? $wa : dlux_button( dlux_option( 'header_secondary_button' ), 'btn--outline btn--sm' ); // phpcs:ignore
				?>
			</div>
		</div>
	</div>
</header>

<main id="main" class="site-main">
