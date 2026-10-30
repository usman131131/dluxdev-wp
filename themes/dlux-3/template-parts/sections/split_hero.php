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
			// Secondary button shows as a phone icon; its link (and label, used for the tooltip) come from the field.
			$sec  = get_sub_field( 'secondary_button' );
			$icon = '';
			if ( ! empty( $sec['url'] ) ) {
				$label  = ! empty( $sec['title'] ) ? $sec['title'] : __( 'Call us', 'dlux' );
				$target = ! empty( $sec['target'] ) ? $sec['target'] : '';
				$icon   = sprintf(
					'<a class="hero-call" href="%1$s"%3$s aria-label="%2$s" title="%2$s"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"/></svg></a>',
					esc_url( $sec['url'], [ 'http', 'https', 'tel', 'sms', 'mailto' ] ),
					esc_attr( $label ),
					'_blank' === $target ? ' target="_blank" rel="noopener"' : ''
				);
			}
			$btns = dlux_button( get_sub_field( 'primary_button' ), 'btn--primary', false ) . $icon;
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
