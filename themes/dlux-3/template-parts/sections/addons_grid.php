<?php
/**
 * Add-ons grid (e.g. "Add to your detail").
 * Fields: section_id, background (light|grey|dark), heading, intro, items[ name, price, text ].
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$items = array_values( array_filter( (array) ( get_sub_field( 'items' ) ?: [] ), function ( $a ) { return '' !== trim( (string) ( $a['name'] ?? '' ) ); } ) );
if ( ! $items ) return;
$bg = get_sub_field( 'background' );
$bg = in_array( $bg, [ 'light', 'grey', 'dark' ], true ) ? $bg : 'grey';
?>
<section class="section addons addons--<?php echo esc_attr( $bg ); ?>"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?>>
	<div class="container">
		<?php if ( $h = get_sub_field( 'heading' ) ) : ?><h2 class="addons__title"><?php echo dlux_heading( $h ); // phpcs:ignore ?></h2><?php endif; ?>
		<?php if ( $t = get_sub_field( 'intro' ) ) : ?><p class="addons__intro"><?php echo wp_kses( dlux_tokens( $t ), [ 'br' => [] ] ); ?></p><?php endif; ?>
		<ul class="addons__grid">
			<?php foreach ( $items as $a ) :
				$price = trim( (string) ( $a['price'] ?? '' ) );
				?>
				<li class="addon">
					<div class="addon__head">
						<h3 class="addon__name"><?php echo esc_html( $a['name'] ); ?></h3>
						<?php if ( '' !== $price ) : ?><span class="addon__price"><?php echo esc_html( is_numeric( $price ) ? dlux_money( $price ) : $price ); ?></span><?php endif; ?>
					</div>
					<?php if ( ! empty( $a['text'] ) ) : ?><p class="addon__text"><?php echo wp_kses( $a['text'], [ 'br' => [] ] ); ?></p><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
