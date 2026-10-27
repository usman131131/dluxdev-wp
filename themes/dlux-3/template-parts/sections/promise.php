<?php
/**
 * Promise box (Beyond Detailing): dark rounded box with shield icon, text and ticked points.
 * Fields: section_id, background (light|grey|dark), show_icon, eyebrow, heading, text, points, footnote.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$points = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) get_sub_field( 'points' ) ) ) );
$bg     = get_sub_field( 'background' );
$bg     = in_array( $bg, [ 'light', 'grey', 'dark' ], true ) ? $bg : 'light';
$icon   = get_sub_field( 'show_icon' );
$icon   = null === $icon || (bool) $icon;
?>
<section class="section promise promise--<?php echo esc_attr( $bg ); ?>"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?>>
	<div class="container">
		<div class="promise__box">
			<div class="promise__head<?php echo $icon ? ' has-icon' : ''; ?>">
				<?php if ( $icon ) : ?>
					<svg class="promise__icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5l7.5 3v6c0 4.6-3.2 8.6-7.5 10-4.3-1.4-7.5-5.4-7.5-10v-6l7.5-3z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M8.5 12.2l2.4 2.4 4.6-4.8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				<?php endif; ?>
				<div>
					<?php if ( $e = get_sub_field( 'eyebrow' ) ) : ?><p class="bd-eyebrow"><?php echo esc_html( dlux_tokens( $e ) ); ?></p><?php endif; ?>
					<?php if ( $h = get_sub_field( 'heading' ) ) : ?><h2 class="bd-display bd-display--sm"><?php echo dlux_heading( $h ); // phpcs:ignore ?></h2><?php endif; ?>
				</div>
			</div>
			<?php if ( $t = get_sub_field( 'text' ) ) : ?><div class="promise__text"><?php echo wp_kses_post( $t ); ?></div><?php endif; ?>
			<?php if ( $points ) : ?>
				<ul class="tick-list promise__points">
					<?php foreach ( $points as $pt ) : ?><li><?php echo esc_html( $pt ); ?></li><?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( $f = get_sub_field( 'footnote' ) ) : ?><p class="promise__footnote"><?php echo esc_html( $f ); ?></p><?php endif; ?>
		</div>
	</div>
</section>
