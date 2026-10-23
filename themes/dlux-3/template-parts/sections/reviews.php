<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$reviews = get_sub_field( 'reviews' );
$button  = get_sub_field( 'button' );
if ( empty( $button['url'] ) && ( $g = dlux_option( 'google_reviews_url' ) ) ) {
	$button = [ 'url' => $g, 'title' => __( 'Read all Google reviews', 'dlux' ), 'target' => '_blank' ];
}
?>
<section class="section section--surface reviews"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?>>
	<div class="container">
		<div class="section-head section-head--split">
			<div><?php echo dlux_section_head( get_sub_field( 'eyebrow' ), get_sub_field( 'heading' ) ); // phpcs:ignore ?></div>
			<?php echo dlux_button( $button, 'btn--outline' ); // phpcs:ignore ?>
		</div>

		<?php if ( $reviews ) : ?>
			<div class="reviews__grid">
				<?php foreach ( $reviews as $r ) : ?>
					<figure class="review">
						<figcaption class="review__head">
							<span class="review__name"><?php echo esc_html( $r['name'] ); ?></span>
							<?php echo dlux_stars( $r['rating'] ?: 5 ); // phpcs:ignore ?>
						</figcaption>
						<blockquote class="review__text"><p><?php echo wp_kses( $r['text'], [ 'br' => [] ] ); ?></p></blockquote>
					</figure>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
