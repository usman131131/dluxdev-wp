<?php
/**
 * Our Services (Beyond Detailing): numbered service cards that switch a details panel.
 *
 * Fields: section_id, heading, intro, services[
 *   name, card_text, card_meta,                                  ← card
 *   eyebrow, title, text, features, ideal_for,                   ← panel (common)
 *   options_heading, options[ title, text ], partner_note,       ← side column
 *   blocks (flexible: highlight_cards, callout, steps, comparison, text_card, list_columns),
 *   price_label, price, button                                   ← footer
 * ]
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$services = array_values( array_filter( (array) ( get_sub_field( 'services' ) ?: [] ), function ( $s ) { return '' !== trim( (string) ( $s['name'] ?? '' ) ); } ) );
if ( ! $services ) return;
$sid  = sanitize_title( (string) get_sub_field( 'section_id' ) );
$uid  = $sid ? $sid : 'services-' . get_row_index();
$lines = function ( $raw ) { return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $raw ) ) ) ); };
$kses  = [ 'br' => [], 'strong' => [], 'em' => [], 'span' => [ 'class' => [] ] ];
?>
<section class="section bds"<?php echo dlux_section_id( $sid ); // phpcs:ignore ?> data-bds>
	<div class="container">
		<?php if ( $h = get_sub_field( 'heading' ) ) : ?><h2 class="bd-title"><?php echo dlux_heading( $h ); // phpcs:ignore ?></h2><?php endif; ?>
		<?php if ( $t = get_sub_field( 'intro' ) ) : ?><p class="bds__intro"><?php echo wp_kses( dlux_tokens( $t ), [ 'br' => [] ] ); ?></p><?php endif; ?>

		<div class="bds__cards" style="--bds-cols:<?php echo esc_attr( min( 5, count( $services ) ) ); ?>">
			<?php foreach ( $services as $i => $s ) :
				$slug = $uid . '-' . ( sanitize_title( $s['name'] ) ?: $i );
				?>
				<button type="button" class="bds-card" aria-expanded="<?php echo 0 === $i ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $slug ); ?>" data-bds-card>
					<span class="pstep__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
					<span class="bds-card__name"><?php echo esc_html( $s['name'] ); ?></span>
					<?php if ( ! empty( $s['card_text'] ) ) : ?><span class="bds-card__text"><?php echo esc_html( $s['card_text'] ); ?></span><?php endif; ?>
					<span class="bds-card__foot">
						<?php if ( ! empty( $s['card_meta'] ) ) : ?><span class="bds-card__meta"><?php echo esc_html( $s['card_meta'] ); ?></span><?php endif; ?>
						<span class="bds-card__more"><span class="is-closed"><?php esc_html_e( 'See details', 'dlux' ); ?></span><span class="is-open"><?php esc_html_e( 'Showing details below', 'dlux' ); ?></span></span>
					</span>
				</button>
			<?php endforeach; ?>
		</div>

		<?php foreach ( $services as $i => $s ) :
			$slug     = $uid . '-' . ( sanitize_title( $s['name'] ) ?: $i );
			$features = $lines( $s['features'] ?? '' );
			$options  = array_filter( (array) ( $s['options'] ?? [] ), function ( $o ) { return '' !== trim( (string) ( $o['title'] ?? '' ) ); } );
			$note     = trim( (string) ( $s['partner_note'] ?? '' ) );
			$blocks   = (array) ( $s['blocks'] ?? [] );
			$price    = trim( (string) ( $s['price'] ?? '' ) );
			$btn      = dlux_button( $s['button'] ?? null, 'btn--primary', false );
			?>
			<article class="bds-panel" id="<?php echo esc_attr( $slug ); ?>" data-bds-panel<?php echo 0 === $i ? '' : ' hidden'; ?>>
				<div class="bds-panel__top<?php echo ( $options || $note ) ? '' : ' is-single'; ?>">
					<div class="bds-panel__main">
						<?php if ( ! empty( $s['eyebrow'] ) ) : ?><p class="bd-eyebrow bd-eyebrow--dark"><?php echo esc_html( $s['eyebrow'] ); ?></p><?php endif; ?>
						<h3 class="bds-panel__title"><?php echo dlux_heading( ! empty( $s['title'] ) ? $s['title'] : $s['name'] ); // phpcs:ignore ?></h3>
						<?php if ( ! empty( $s['text'] ) ) : ?><p class="bds-panel__text"><?php echo wp_kses( $s['text'], $kses ); ?></p><?php endif; ?>
						<?php if ( $features ) : ?>
							<ul class="tick-list bds-panel__list"><?php foreach ( $features as $f ) : ?><li><?php echo esc_html( $f ); ?></li><?php endforeach; ?></ul>
						<?php endif; ?>
						<?php if ( ! empty( $s['ideal_for'] ) ) : ?><p class="dpk-panel__ideal"><strong><?php esc_html_e( 'Ideal for:', 'dlux' ); ?></strong> <?php echo esc_html( $s['ideal_for'] ); ?></p><?php endif; ?>
					</div>
					<?php if ( $options || $note ) : ?>
						<aside class="bds-panel__side">
							<?php if ( $options ) : ?>
								<p class="bd-eyebrow bd-eyebrow--dark"><?php echo esc_html( ! empty( $s['options_heading'] ) ? $s['options_heading'] : __( 'Options', 'dlux' ) ); ?></p>
								<ul class="bds-options">
									<?php foreach ( $options as $o ) : ?>
										<li><strong><?php echo esc_html( $o['title'] ); ?></strong><?php if ( ! empty( $o['text'] ) ) : ?><span><?php echo esc_html( $o['text'] ); ?></span><?php endif; ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
							<?php if ( '' !== $note ) : ?>
								<p class="bds-panel__note"><?php echo dlux_quote_icon( 'location' ); // phpcs:ignore ?><span><?php echo esc_html( $note ); ?></span></p>
							<?php endif; ?>
						</aside>
					<?php endif; ?>
				</div>

				<?php if ( $blocks ) : ?>
					<div class="bds-panel__blocks">
						<?php foreach ( $blocks as $b ) dlux_bds_block( $b, $lines ); ?>
					</div>
				<?php endif; ?>

				<?php if ( '' !== $price || $btn ) : ?>
					<div class="bds-panel__foot">
						<div>
							<span class="bds-panel__label"><?php echo esc_html( ! empty( $s['price_label'] ) ? $s['price_label'] : __( 'Pricing', 'dlux' ) ); ?></span>
							<strong class="bds-panel__price"><?php echo esc_html( '' !== $price ? ( is_numeric( $price ) ? dlux_money( $price ) : $price ) : __( 'Quote on inquiry', 'dlux' ) ); ?></strong>
						</div>
						<?php echo $btn; // phpcs:ignore ?>
					</div>
				<?php endif; ?>
			</article>
		<?php endforeach; ?>
	</div>
</section>
