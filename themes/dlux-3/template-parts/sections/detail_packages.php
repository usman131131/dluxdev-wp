<?php
/**
 * Detailing packages: vehicle-size toggle + package cards + "What's included" panel.
 * Packages, prices and sizes come from the Dlux Booking plugin (Booking Prices › Detailing).
 * Fields: section_id, heading, intro, service (detail|wash), empty_text.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$service  = get_sub_field( 'service' ) ?: 'detail';
$packages = dlux_showcase_packages( $service );
$sizes    = dlux_showcase_sizes( $packages );
$book     = dlux_booking_url();
$empty    = get_sub_field( 'empty_text' );
if ( ! $packages && ! $empty ) return;
$first_size = $sizes ? $sizes[0] : '';
$open       = 0;
foreach ( $packages as $i => $p ) {
	if ( ! empty( $p['featured'] ) ) { $open = $i; break; }
}
$uid = 'dp-' . wp_unique_id();
?>
<section class="section dpk"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?> data-dpk data-book-url="<?php echo esc_url( $book ); ?>" data-service="<?php echo esc_attr( $service ); ?>">
	<div class="container">
		<div class="dpk__head">
			<div>
				<?php if ( $h = get_sub_field( 'heading' ) ) : ?><h2 class="dpk__title"><?php echo dlux_heading( $h ); // phpcs:ignore ?></h2><?php endif; ?>
				<?php if ( $t = get_sub_field( 'intro' ) ) : ?><p class="dpk__intro"><?php echo wp_kses( dlux_tokens( $t ), [ 'br' => [] ] ); ?></p><?php endif; ?>
			</div>
			<?php if ( count( $sizes ) > 1 ) : ?>
				<div class="dpk__sizes" role="radiogroup" aria-label="<?php esc_attr_e( 'Vehicle size', 'dlux' ); ?>">
					<?php foreach ( $sizes as $i => $size ) : ?>
						<button type="button" class="dpk__size" role="radio" aria-checked="<?php echo 0 === $i ? 'true' : 'false'; ?>" data-dpk-size="<?php echo esc_attr( $size ); ?>"><?php echo esc_html( $size ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( ! $packages ) : ?>
			<p class="dpk__empty"><?php echo esc_html( $empty ); ?></p>
		<?php else : ?>
			<div class="dpk__grid">
				<?php foreach ( $packages as $i => $p ) :
					$price = $p['prices'][ $first_size ] ?? null;
					$label = '' !== $p['price_label'] ? $p['price_label'] : __( 'From', 'dlux' );
					?>
					<button type="button" class="dpk-card<?php echo $p['featured'] ? ' is-featured' : ''; ?>" aria-expanded="<?php echo $i === $open ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $uid . '-' . $i ); ?>" data-dpk-card="<?php echo esc_attr( $i ); ?>" data-prices="<?php echo esc_attr( wp_json_encode( $p['prices'] ) ); ?>">
						<span class="dpk-card__media">
							<?php echo $p['image'] ? wp_get_attachment_image( $p['image'], 'dlux-card', false, [ 'alt' => '', 'loading' => 'lazy' ] ) : ''; // phpcs:ignore ?>
						</span>
						<span class="dpk-card__body">
							<?php if ( $p['featured'] && '' !== $p['badge'] ) : ?><span class="dpk-card__badge"><?php echo esc_html( $p['badge'] ); ?></span><?php endif; ?>
							<span class="dpk-card__name"><?php echo esc_html( $p['name'] ); ?></span>
							<?php if ( '' !== $p['desc'] ) : ?><span class="dpk-card__sub"><?php echo esc_html( $p['desc'] ); ?></span><?php endif; ?>
							<span class="dpk-card__label"><?php echo esc_html( $label ); ?></span>
							<span class="dpk-card__price" data-dpk-price><?php echo null !== $price ? esc_html( dlux_money( $price ) ) : esc_html__( 'Price on request', 'dlux' ); ?></span>
							<span class="dpk-card__more">
								<span class="dpk-card__more-closed"><?php esc_html_e( 'See what’s included', 'dlux' ); ?></span>
								<span class="dpk-card__more-open"><?php esc_html_e( 'Showing details below', 'dlux' ); ?></span>
							</span>
						</span>
					</button>
				<?php endforeach; ?>
			</div>

			<?php foreach ( $packages as $i => $p ) :
				$price = $p['prices'][ $first_size ] ?? null;
				$label = '' !== $p['price_label'] ? $p['price_label'] : __( 'From', 'dlux' );
				$text  = '' !== $p['long_desc'] ? $p['long_desc'] : $p['desc'];
				$imgs  = array_values( array_filter( [ $p['before_image'], $p['after_image'] ] ) );
				if ( ! $imgs && $p['image'] ) $imgs = [ $p['image'] ];
				?>
				<div class="dpk-panel" id="<?php echo esc_attr( $uid . '-' . $i ); ?>" data-dpk-panel="<?php echo esc_attr( $i ); ?>"<?php echo $i === $open ? '' : ' hidden'; ?>>
					<div class="dpk-panel__media<?php echo 2 === count( $imgs ) ? ' is-split' : ''; ?>">
						<?php foreach ( $imgs as $img ) echo wp_get_attachment_image( $img, 'large', false, [ 'alt' => '', 'loading' => 'lazy' ] ); // phpcs:ignore ?>
					</div>
					<div class="dpk-panel__body">
						<p class="dpk-panel__eyebrow"><?php esc_html_e( 'What’s included', 'dlux' ); ?></p>
						<h3 class="dpk-panel__name"><?php echo esc_html( $p['name'] ); ?></h3>
						<?php if ( '' !== $text ) : ?><p class="dpk-panel__text"><?php echo esc_html( $text ); ?></p><?php endif; ?>
						<?php if ( $p['features'] ) : ?>
							<ul class="tick-list">
								<?php foreach ( $p['features'] as $f ) : ?><li><?php echo esc_html( $f ); ?></li><?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<?php if ( '' !== $p['ideal_for'] ) : ?>
							<p class="dpk-panel__ideal"><strong><?php esc_html_e( 'Ideal for:', 'dlux' ); ?></strong> <?php echo esc_html( $p['ideal_for'] ); ?></p>
						<?php endif; ?>
						<div class="dpk-panel__foot">
							<div>
								<span class="dpk-panel__label"><?php echo esc_html( $label ); ?> · <span data-dpk-size-label><?php echo esc_html( $first_size ); ?></span></span>
								<strong class="dpk-panel__price" data-dpk-price><?php echo null !== $price ? esc_html( dlux_money( $price ) ) : esc_html__( 'Price on request', 'dlux' ); ?></strong>
							</div>
							<?php if ( $book ) :
								$url = add_query_arg( array_filter( [ 'service' => $service, 'package' => $p['id'], 'size' => $first_size ] ), $book );
								?>
								<a class="btn btn--primary dpk-panel__btn" href="<?php echo esc_url( $url ); ?>" data-dpk-book="<?php echo esc_attr( $p['id'] ); ?>"><span><?php echo esc_html( sprintf( __( 'Book %s', 'dlux' ), $p['name'] ) ); ?></span></a>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
</section>
