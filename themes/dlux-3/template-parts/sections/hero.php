<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$bg      = get_sub_field( 'background_image' );
$overlay = get_sub_field( 'overlay_opacity' );
$overlay = ( '' === $overlay || null === $overlay ) ? 60 : (int) $overlay;
$stats   = get_sub_field( 'stats' );
?>
<section class="hero" style="--overlay:<?php echo esc_attr( $overlay / 100 ); ?>">
	<?php if ( ! empty( $bg['ID'] ) ) :
		echo wp_get_attachment_image( $bg['ID'], 'full', false, [ 'class' => 'hero__bg', 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '100vw' ] );
	endif; ?>
	<div class="hero__overlay" aria-hidden="true"></div>

	<div class="container hero__inner">
		<?php if ( $e = get_sub_field( 'eyebrow' ) ) : ?>
			<p class="eyebrow"><?php echo esc_html( dlux_tokens( $e ) ); ?></p>
		<?php endif; ?>

		<h1 class="hero__title"><?php echo dlux_heading( get_sub_field( 'heading' ) ); // phpcs:ignore ?></h1>

		<?php if ( $d = get_sub_field( 'description' ) ) : ?>
			<p class="hero__text"><?php echo wp_kses( $d, [ 'br' => [] ] ); ?></p>
		<?php endif; ?>

		<div class="btn-group">
			<?php
			echo dlux_button( get_sub_field( 'primary_button' ), 'btn--outline btn--wide' ); // phpcs:ignore
			echo dlux_button( get_sub_field( 'secondary_button' ), 'btn--primary' ); // phpcs:ignore
			?>
		</div>

		<?php if ( $stats ) : ?>
			<ul class="hero__stats">
				<?php foreach ( $stats as $s ) : ?>
					<li>
						<strong><?php echo esc_html( dlux_tokens( $s['title'] ) ); ?></strong>
						<span>
							<?php if ( ! empty( $s['show_stars'] ) ) echo dlux_stars( 5, false ); // phpcs:ignore ?>
							<?php echo esc_html( dlux_tokens( $s['subtitle'] ) ); ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
