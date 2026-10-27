<?php
/**
 * Hero with side card (Beyond Detailing).
 * Fields: section_id, eyebrow, heading, text, primary_button, secondary_button,
 *         card_eyebrow, card_heading, card_text, card_button, highlights[ text ].
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$highlights = array_filter( (array) ( get_sub_field( 'highlights' ) ?: [] ), function ( $h ) { return is_array( $h ) && '' !== trim( (string) ( $h['text'] ?? '' ) ); } );
$card_h     = get_sub_field( 'card_heading' );
$card_t     = get_sub_field( 'card_text' );
$card_b     = dlux_button( get_sub_field( 'card_button' ), 'btn--white', false );
$has_card   = $card_h || $card_t || $card_b;
?>
<section class="bd-hero"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?>>
	<div class="container bd-hero__inner<?php echo $has_card ? '' : ' bd-hero__inner--solo'; ?>">
		<div class="bd-hero__content">
			<?php if ( $e = get_sub_field( 'eyebrow' ) ) : ?><p class="eyebrow bd-eyebrow"><?php echo esc_html( dlux_tokens( $e ) ); ?></p><?php endif; ?>
			<h1 class="bd-hero__title"><?php echo dlux_heading( get_sub_field( 'heading' ) ); // phpcs:ignore ?></h1>
			<?php if ( $t = get_sub_field( 'text' ) ) : ?><p class="bd-hero__text"><?php echo wp_kses( dlux_tokens( $t ), [ 'br' => [] ] ); ?></p><?php endif; ?>
			<?php
			$btns = dlux_button( get_sub_field( 'primary_button' ), 'btn--primary', false ) . dlux_button( get_sub_field( 'secondary_button' ), 'btn--ghost', false );
			if ( $btns ) echo '<div class="btn-group">' . $btns . '</div>'; // phpcs:ignore
			?>
		</div>
		<?php if ( $has_card ) : ?>
			<aside class="bd-hero__card">
				<?php if ( $ce = get_sub_field( 'card_eyebrow' ) ) : ?><p class="bd-eyebrow"><?php echo esc_html( dlux_tokens( $ce ) ); ?></p><?php endif; ?>
				<?php if ( $card_h ) : ?><h2 class="bd-hero__card-title"><?php echo dlux_heading( $card_h ); // phpcs:ignore ?></h2><?php endif; ?>
				<?php if ( $card_t ) : ?><p class="bd-hero__card-text"><?php echo wp_kses( dlux_tokens( $card_t ), [ 'br' => [] ] ); ?></p><?php endif; ?>
				<?php if ( $card_b ) echo '<div class="bd-hero__card-btn">' . $card_b . '</div>'; // phpcs:ignore ?>
			</aside>
		<?php endif; ?>
	</div>
	<?php if ( $highlights ) : ?>
		<div class="highlights highlights--caps">
			<ul class="container highlights__list">
				<?php foreach ( $highlights as $h ) : ?><li><?php echo esc_html( dlux_tokens( $h['text'] ) ); ?></li><?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</section>
