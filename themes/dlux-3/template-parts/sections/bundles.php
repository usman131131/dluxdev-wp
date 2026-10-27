<?php
/**
 * Bundles (Beyond Detailing): one featured package with numbered parts, plus combo cards.
 * Fields: section_id, eyebrow, heading, text,
 *         featured_badge, featured_title, featured_text, featured_price_label, featured_price, featured_button,
 *         featured_items[ title, text ], cards[ title, text, list, link ].
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$f_title = get_sub_field( 'featured_title' );
$f_items = array_filter( (array) ( get_sub_field( 'featured_items' ) ?: [] ), function ( $i ) { return '' !== trim( (string) ( $i['title'] ?? '' ) ); } );
$cards   = array_filter( (array) ( get_sub_field( 'cards' ) ?: [] ), function ( $c ) { return '' !== trim( (string) ( $c['title'] ?? '' ) ); } );
if ( ! $f_title && ! $cards ) return;
$price = trim( (string) get_sub_field( 'featured_price' ) );
?>
<section class="section bundles"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?>>
	<div class="container">
		<?php if ( $e = get_sub_field( 'eyebrow' ) ) : ?><p class="bd-eyebrow"><?php echo esc_html( dlux_tokens( $e ) ); ?></p><?php endif; ?>
		<?php if ( $h = get_sub_field( 'heading' ) ) : ?><h2 class="bd-display"><?php echo dlux_heading( $h ); // phpcs:ignore ?></h2><?php endif; ?>
		<?php if ( $t = get_sub_field( 'text' ) ) : ?><p class="bundles__intro"><?php echo wp_kses( dlux_tokens( $t ), [ 'br' => [] ] ); ?></p><?php endif; ?>

		<?php if ( $f_title ) : ?>
			<article class="bundle-feature">
				<div class="bundle-feature__head">
					<div>
						<?php if ( $b = get_sub_field( 'featured_badge' ) ) : ?><span class="bundle-feature__badge"><?php echo esc_html( $b ); ?></span><?php endif; ?>
						<h3 class="bundle-feature__title"><?php echo dlux_heading( $f_title ); // phpcs:ignore ?></h3>
						<?php if ( $ft = get_sub_field( 'featured_text' ) ) : ?><p class="bundle-feature__text"><?php echo wp_kses( $ft, [ 'br' => [] ] ); ?></p><?php endif; ?>
					</div>
					<?php
					$btn = dlux_button( get_sub_field( 'featured_button' ), 'btn--primary', false );
					if ( '' !== $price || $btn ) :
						?>
						<div class="bundle-feature__price">
							<?php if ( '' !== $price ) : ?>
								<?php if ( $pl = get_sub_field( 'featured_price_label' ) ) : ?><span class="bundle-feature__label"><?php echo esc_html( $pl ); ?></span><?php endif; ?>
								<strong><?php echo esc_html( is_numeric( $price ) ? dlux_money( $price ) : $price ); ?></strong>
							<?php endif; ?>
							<?php echo $btn; // phpcs:ignore ?>
						</div>
					<?php endif; ?>
				</div>
				<?php if ( $f_items ) : ?>
					<ol class="bundle-feature__items">
						<?php $n = 0; foreach ( $f_items as $it ) : $n++; ?>
							<li>
								<span class="pstep__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) $n, 2, '0', STR_PAD_LEFT ) ); ?></span>
								<h4><?php echo esc_html( $it['title'] ); ?></h4>
								<?php if ( ! empty( $it['text'] ) ) : ?><p><?php echo wp_kses( $it['text'], [ 'br' => [] ] ); ?></p><?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
			</article>
		<?php endif; ?>

		<?php if ( $cards ) : ?>
			<ul class="bundles__grid">
				<?php foreach ( $cards as $c ) :
					$list = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) ( $c['list'] ?? '' ) ) ) );
					$link = $c['link'] ?? null;
					?>
					<li class="bundle-card">
						<h3 class="bundle-card__title"><?php echo esc_html( $c['title'] ); ?></h3>
						<?php if ( ! empty( $c['text'] ) ) : ?><p class="bundle-card__text"><?php echo wp_kses( $c['text'], [ 'br' => [] ] ); ?></p><?php endif; ?>
						<?php if ( $list ) : ?>
							<ul class="tick-list tick-list--sm">
								<?php foreach ( $list as $li ) : ?><li><?php echo esc_html( $li ); ?></li><?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<?php if ( ! empty( $link['url'] ) ) : ?>
							<a class="bd-link" href="<?php echo esc_url( $link['url'] ); ?>"<?php echo ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : ''; ?>><?php echo esc_html( $link['title'] ?: __( 'Learn more', 'dlux' ) ); ?></a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
