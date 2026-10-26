<?php
/**
 * Service cards (e.g. Ceramic coating; PPF, window tint & wraps).
 * Fields: section_id, title_style (large|standard), logo, eyebrow, heading, text,
 *         cards[ image, title, text, price_label, price, price_note ], primary_button, secondary_button, footnote.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$cards = array_filter( (array) ( get_sub_field( 'cards' ) ?: [] ), function ( $c ) { return '' !== trim( (string) ( $c['title'] ?? '' ) ); } );
$large = 'large' === get_sub_field( 'title_style' );
$logo  = get_sub_field( 'logo' );
?>
<section class="section svc<?php echo $large ? ' svc--large' : ''; ?>"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?>>
	<div class="container">
		<div class="svc__head">
			<?php if ( ! empty( $logo['ID'] ) ) : ?>
				<div class="svc__logo"><?php echo wp_get_attachment_image( $logo['ID'], 'medium', false, [ 'alt' => $logo['alt'] ?? '', 'loading' => 'lazy' ] ); ?></div>
			<?php endif; ?>
			<?php if ( $e = get_sub_field( 'eyebrow' ) ) : ?><p class="eyebrow svc__eyebrow"><?php echo esc_html( dlux_tokens( $e ) ); ?></p><?php endif; ?>
			<?php if ( $h = get_sub_field( 'heading' ) ) : ?><h2 class="svc__title"><?php echo dlux_heading( $h ); // phpcs:ignore ?></h2><?php endif; ?>
			<?php if ( $t = get_sub_field( 'text' ) ) : ?><p class="svc__text"><?php echo wp_kses( dlux_tokens( $t ), [ 'br' => [], 'strong' => [], 'em' => [] ] ); ?></p><?php endif; ?>
		</div>

		<?php if ( $cards ) : ?>
			<ul class="svc__grid">
				<?php foreach ( $cards as $c ) :
					$price = trim( (string) ( $c['price'] ?? '' ) );
					$note  = trim( (string) ( $c['price_note'] ?? '' ) );
					?>
					<li class="svc-card">
						<div class="svc-card__media">
							<?php if ( ! empty( $c['image']['ID'] ) ) echo wp_get_attachment_image( $c['image']['ID'], 'dlux-card', false, [ 'alt' => '', 'loading' => 'lazy' ] ); ?>
						</div>
						<div class="svc-card__body">
							<h3 class="svc-card__title"><?php echo esc_html( $c['title'] ); ?></h3>
							<?php if ( ! empty( $c['text'] ) ) : ?><p class="svc-card__text"><?php echo wp_kses( $c['text'], [ 'br' => [] ] ); ?></p><?php endif; ?>
							<?php if ( '' !== $price ) : ?>
								<?php if ( ! empty( $c['price_label'] ) ) : ?><span class="svc-card__label"><?php echo esc_html( $c['price_label'] ); ?></span><?php endif; ?>
								<strong class="svc-card__price"><?php echo esc_html( is_numeric( $price ) ? dlux_money( $price ) : $price ); ?></strong>
							<?php endif; ?>
							<?php if ( '' !== $note ) : ?><span class="svc-card__note"><?php echo esc_html( $note ); ?></span><?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php
		$btns = dlux_button( get_sub_field( 'primary_button' ), 'btn--primary', false ) . dlux_button( get_sub_field( 'secondary_button' ), 'btn--ghost', false );
		if ( $btns ) echo '<div class="btn-group svc__buttons">' . $btns . '</div>'; // phpcs:ignore
		?>

		<?php if ( $f = get_sub_field( 'footnote' ) ) : ?>
			<div class="svc__footnote"><?php echo wp_kses_post( $f ); ?></div>
		<?php endif; ?>
	</div>
</section>
