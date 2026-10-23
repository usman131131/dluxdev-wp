<?php
/**
 * One package card. Expects $args['card'] from dlux_booking_card() / dlux_field_card().
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$c = $args['card'] ?? null;
if ( ! $c || '' === $c['name'] ) return;
$featured = $c['featured'];
?>
<article class="package<?php echo $featured ? ' is-featured' : ''; ?><?php echo $c['mins'] ? ' has-mins' : ''; ?>"<?php if ( ! empty( $c['id'] ) ) : ?> data-package="<?php echo esc_attr( $c['id'] ); ?>" data-package-name="<?php echo esc_attr( $c['name'] ); ?>"<?php endif; ?>>
	<?php if ( $c['mins'] ) : ?>
		<span class="package__mins" aria-label="<?php echo esc_attr( sprintf( __( '%d minutes', 'dlux' ), $c['mins'] ) ); ?>">
			<span class="package__mins-num" aria-hidden="true"><?php echo esc_html( $c['mins'] ); ?></span>
			<span class="package__mins-unit" aria-hidden="true"><?php echo ! empty( $args['mins_star'] ) ? esc_html__( 'Mins*', 'dlux' ) : esc_html__( 'Min', 'dlux' ); ?></span>
		</span>
	<?php endif; ?>

	<?php if ( $featured && '' !== $c['badge'] ) : ?>
		<span class="package__badge"><?php echo esc_html( $c['badge'] ); ?></span>
	<?php endif; ?>
	<div class="package__head" data-eq="head">
		<h3 class="package__name"><?php echo esc_html( $c['name'] ); ?></h3>
		<?php if ( '' !== $c['desc'] ) : ?>
			<p class="package__sub"><?php echo esc_html( $c['desc'] ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( ! $c['prices'] && ! empty( $c['quote'] ) ) : ?>
		<div class="package__prices package__prices--quote" data-eq="prices">
			<span class="package__quote"><?php esc_html_e( 'Price on request', 'dlux' ); ?></span>
			<a class="package__quote-link" href="<?php echo esc_url( dlux_option( 'phone' ) ? 'tel:' . preg_replace( '/[^0-9+]/', '', dlux_option( 'phone' ) ) : home_url( '/#contact' ) ); ?>"><?php esc_html_e( 'Get a quote', 'dlux' ); ?> &rarr;</a>
		</div>
	<?php endif; ?>

	<?php if ( $c['prices'] ) : ?>
		<div class="package__prices" data-eq="prices" role="group" aria-label="<?php echo esc_attr( sprintf( __( '%s – choose your vehicle size', 'dlux' ), $c['name'] ) ); ?>">
			<?php foreach ( $c['prices'] as $label => $price ) :
				$has = '' !== $price && null !== $price;
				?>
				<button type="button" class="package__price-row" data-size="<?php echo esc_attr( $label ); ?>" data-price="<?php echo esc_attr( $has ? (float) $price : '' ); ?>" aria-pressed="false"<?php echo $has ? '' : ' disabled'; ?>>
					<span class="package__size"><?php echo esc_html( $label ); ?></span>
					<span class="package__amount"><?php echo $has ? esc_html( dlux_money( $price ) ) : esc_html__( 'POA', 'dlux' ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php
	$choose       = (array) ( $c['choose'] ?? [] );
	$choose_title = (string) ( $c['choose_title'] ?? '' );
	$note         = (string) ( $c['note'] ?? '' );
	if ( $c['features'] || $choose || '' !== $note ) :
		?>
		<div class="package__body" data-eq="features">
			<?php if ( $c['features'] ) : ?>
				<p class="package__label"><?php esc_html_e( 'Includes', 'dlux' ); ?></p>
				<ul class="package__features">
					<?php foreach ( $c['features'] as $f ) : ?>
						<li><?php echo esc_html( $f ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( $choose ) : ?>
				<p class="package__choose-title"><?php echo esc_html( '' !== $choose_title ? $choose_title : __( 'Choose from:', 'dlux' ) ); ?></p>
				<?php if ( ! empty( $c['id'] ) ) : // bookable → pick one option ?>
					<ol class="package__choose package__choose--select" role="radiogroup" aria-label="<?php echo esc_attr( '' !== $choose_title ? $choose_title : __( 'Choose from', 'dlux' ) ); ?>">
						<?php foreach ( $choose as $i => $opt ) : ?>
							<li>
								<button type="button" class="package__option" role="radio" aria-checked="false" data-option="<?php echo esc_attr( $i + 1 ); ?>" data-option-text="<?php echo esc_attr( $opt ); ?>">
									<span class="package__option-dot" aria-hidden="true"></span>
									<span><?php echo esc_html( $opt ); ?></span>
								</button>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php else : ?>
					<ol class="package__choose">
						<?php foreach ( $choose as $i => $opt ) : ?>
							<li>
								<?php echo esc_html( $opt ); ?>
								<?php if ( $i < count( $choose ) - 1 ) : ?><span class="package__or"><?php esc_html_e( '(or)', 'dlux' ); ?></span><?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( '' !== $note ) : ?>
				<p class="package__note"><?php echo wp_kses( nl2br( esc_html( $note ) ), [ 'br' => [] ] ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php /* Per-package add-ons are hidden for now (shown in the section's Extras row instead). */ ?>

	<?php /* Book buttons removed – booking happens from the section's "Book now" bar. */ ?>
</article>
