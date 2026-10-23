<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* "Beyond the wash": detailing packages from the Dlux Booking plugin
 * (Booking Prices › Detailing), shown in the same card format as Pricing.
 * If the plugin is not active, the section's own image cards are used instead. */
$group    = dlux_booking_group( 'detail' );
$packages = [];
if ( dlux_booking_active() ) {
	$packages = dlux_booking_detail_cards();
}
$cards  = ( ! $packages && ! dlux_booking_active() ) ? get_sub_field( 'cards' ) : [];
$extras = $packages ? dlux_booking_section_extras( $group ) : [];
?>
<section class="section section--surface services"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?><?php echo $packages ? dlux_booking_attrs( 'detail' ) : ''; // phpcs:ignore ?>>
	<div class="container">
		<div class="section-head">
			<?php echo dlux_section_head( get_sub_field( 'eyebrow' ), get_sub_field( 'heading' ) ); // phpcs:ignore ?>
			<?php if ( $intro = get_sub_field( 'intro' ) ) : ?>
				<p class="section-intro"><?php echo wp_kses( $intro, [ 'br' => [] ] ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $packages ) : ?>
			<div class="services__slider">
				<button type="button" class="carousel-nav__btn carousel-nav__btn--prev" data-carousel-prev aria-label="<?php esc_attr_e( 'Previous', 'dlux' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</button>
				<div class="services__carousel" data-carousel data-equalize>
					<?php foreach ( $packages as $card ) get_template_part( 'template-parts/package-card', null, [ 'card' => $card ] ); ?>
				</div>
				<button type="button" class="carousel-nav__btn carousel-nav__btn--next" data-carousel-next aria-label="<?php esc_attr_e( 'Next', 'dlux' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				</button>
			</div>
		<?php elseif ( $cards ) : ?>
			<div class="cards">
				<?php foreach ( $cards as $c ) :
					$has_link = ! empty( $c['link']['url'] );
					$tag      = $has_link ? 'a' : 'div';
					$attrs    = $has_link ? ' href="' . esc_url( $c['link']['url'] ) . '"' . ( ! empty( $c['link']['target'] ) ? ' target="' . esc_attr( $c['link']['target'] ) . '"' : '' ) : '';
					?>
					<<?php echo $tag . $attrs; // phpcs:ignore ?> class="card">
						<div class="card__media">
							<?php if ( ! empty( $c['image']['ID'] ) ) echo wp_get_attachment_image( $c['image']['ID'], 'dlux-card', false, [ 'alt' => '', 'loading' => 'lazy' ] ); ?>
						</div>
						<div class="card__body">
							<h3 class="card__title"><?php echo esc_html( $c['title'] ); ?></h3>
							<?php if ( ! empty( $c['text'] ) ) : ?>
								<p class="card__text"><?php echo wp_kses( $c['text'], [ 'br' => [] ] ); ?></p>
							<?php endif; ?>
						</div>
					</<?php echo $tag; // phpcs:ignore ?>>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( $extras ) :
			$extra_heading = trim( wp_strip_all_tags( (string) get_sub_field( 'extra_heading' ) ) );
			?>
			<div class="extras">
				<p class="extras__title"><?php echo esc_html( $extra_heading ?: __( 'Extras', 'dlux' ) ); ?></p>
				<?php echo dlux_extras_select( $extras ); // phpcs:ignore ?>
			</div>
		<?php endif; ?>

		<?php if ( $packages ) echo dlux_booking_bar(); // phpcs:ignore ?>
	</div>
</section>
