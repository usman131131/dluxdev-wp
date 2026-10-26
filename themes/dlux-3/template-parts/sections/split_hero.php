<?php
/**
 * Split hero: text left, image right, optional highlights strip underneath.
 * Fields: section_id, eyebrow, heading, text, primary_button, secondary_button, image, highlights[ text ].
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$image      = get_sub_field( 'image' );
$highlights = array_filter( (array) ( get_sub_field( 'highlights' ) ?: [] ), function ( $h ) { return is_array( $h ) && '' !== trim( (string) ( $h['text'] ?? '' ) ); } );
?>
<section class="split-hero"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?>>
	<div class="container split-hero__inner<?php echo empty( $image['ID'] ) ? ' split-hero__inner--text' : ''; ?>">
		<div class="split-hero__content">
			<?php if ( $e = get_sub_field( 'eyebrow' ) ) : ?>
				<p class="eyebrow split-hero__eyebrow"><?php echo esc_html( dlux_tokens( $e ) ); ?></p>
			<?php endif; ?>
			<h1 class="split-hero__title"><?php echo dlux_heading( get_sub_field( 'heading' ) ); // phpcs:ignore ?></h1>
			<?php if ( $t = get_sub_field( 'text' ) ) : ?>
				<p class="split-hero__text"><?php echo wp_kses( dlux_tokens( $t ), [ 'br' => [] ] ); ?></p>
			<?php endif; ?>
			<?php
			$btns = dlux_button( get_sub_field( 'primary_button' ), 'btn--primary', false ) . dlux_button( get_sub_field( 'secondary_button' ), 'btn--ghost', false );
			if ( $btns ) echo '<div class="btn-group">' . $btns . '</div>'; // phpcs:ignore
			?>
		</div>
		<?php if ( ! empty( $image['ID'] ) ) : ?>
			<div class="split-hero__media">
				<?php echo wp_get_attachment_image( $image['ID'], 'large', false, [ 'alt' => $image['alt'] ?? '', 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(max-width: 900px) 100vw, 600px' ] ); ?>
			</div>
		<?php endif; ?>
	</div>
	<?php if ( $highlights ) : ?>
		<div class="highlights">
			<ul class="container highlights__list">
				<?php foreach ( $highlights as $h ) : ?>
					<li><?php echo esc_html( dlux_tokens( $h['text'] ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</section>
