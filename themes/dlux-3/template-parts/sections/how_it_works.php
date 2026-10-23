<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$steps = get_sub_field( 'steps' );
?>
<section class="section how"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?>>
	<div class="container">
		<div class="section-head section-head--split">
			<div><?php echo dlux_section_head( get_sub_field( 'eyebrow' ), get_sub_field( 'heading' ) ); // phpcs:ignore ?></div>
			<?php echo dlux_button( get_sub_field( 'button' ), 'btn--outline' ); // phpcs:ignore ?>
		</div>

		<?php if ( $steps ) : ?>
			<ol class="steps">
				<?php foreach ( $steps as $n => $s ) : ?>
					<li class="step">
						<span class="step__num" aria-hidden="true"><?php echo esc_html( str_pad( $n + 1, 2, '0', STR_PAD_LEFT ) ); ?></span>
						<h3 class="step__title"><?php echo esc_html( $s['title'] ); ?></h3>
						<p class="step__text"><?php echo wp_kses( $s['text'], [ 'br' => [] ] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>
	</div>
</section>
