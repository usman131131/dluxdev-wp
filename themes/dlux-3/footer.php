<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
</main>

<footer class="site-footer">
	<div class="container site-footer__inner">
		<?php echo dlux_logo( dlux_option( 'footer_logo' ) ?: dlux_option( 'site_logo' ), 'logo logo--footer' ); // phpcs:ignore ?>

		<nav class="site-footer__nav" aria-label="<?php esc_attr_e( 'Footer', 'dlux' ); ?>">
			<?php
			wp_nav_menu( [
				'theme_location' => 'footer',
				'container'      => false,
				'menu_class'     => 'menu',
				'depth'          => 1,
				'fallback_cb'    => 'dlux_footer_menu_fallback',
			] );
			?>
		</nav>

		<div class="site-footer__meta">
			<?php $socials = dlux_option( 'social_links', [] ); ?>
			<?php if ( $socials ) : ?>
				<ul class="socials">
					<?php foreach ( $socials as $s ) :
						if ( empty( $s['url'] ) ) continue; ?>
						<li>
							<a href="<?php echo esc_url( $s['url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ucfirst( $s['platform'] ) ); ?>">
								<?php echo dlux_social_icon( $s['platform'] ); // phpcs:ignore ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<p class="copyright"><?php echo esc_html( dlux_tokens( dlux_option( 'copyright_text', '© {year} ' . get_bloginfo( 'name' ) . '. All Rights Reserved.' ) ) ); ?></p>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
