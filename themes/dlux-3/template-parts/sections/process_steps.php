<?php
/**
 * Process steps (e.g. "How it works · Booked in a minute").
 * Fields: section_id, background (light|grey|dark), eyebrow, heading, steps[ title, text ], button.
 * Step numbers (01, 02…) are added automatically.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$steps = array_values( array_filter( (array) ( get_sub_field( 'steps' ) ?: [] ), function ( $s ) { return '' !== trim( (string) ( $s['title'] ?? '' ) ); } ) );
if ( ! $steps ) return;
$bg = get_sub_field( 'background' );
$bg = in_array( $bg, [ 'light', 'grey', 'dark' ], true ) ? $bg : 'light';
?>
<section class="section psteps psteps--<?php echo esc_attr( $bg ); ?>"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?>>
	<div class="container">
		<?php if ( $e = get_sub_field( 'eyebrow' ) ) : ?><p class="psteps__eyebrow"><?php echo esc_html( dlux_tokens( $e ) ); ?></p><?php endif; ?>
		<?php if ( $h = get_sub_field( 'heading' ) ) : ?><h2 class="psteps__title"><?php echo dlux_heading( $h ); // phpcs:ignore ?></h2><?php endif; ?>
		<ol class="psteps__grid">
			<?php foreach ( $steps as $i => $s ) : ?>
				<li class="pstep">
					<span class="pstep__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
					<h3 class="pstep__title"><?php echo esc_html( $s['title'] ); ?></h3>
					<?php if ( ! empty( $s['text'] ) ) : ?><p class="pstep__text"><?php echo wp_kses( $s['text'], [ 'br' => [], 'strong' => [], 'em' => [] ] ); ?></p><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
		<?php if ( $b = dlux_button( get_sub_field( 'button' ), 'btn--primary', false ) ) : ?>
			<div class="btn-group psteps__btn"><?php echo $b; // phpcs:ignore ?></div>
		<?php endif; ?>
	</div>
</section>
