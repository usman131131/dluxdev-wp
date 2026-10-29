<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$locations = get_sub_field( 'show_locations' ) ? dlux_option( 'locations', [] ) : [];
$map       = get_sub_field( 'map_embed' ) ?: dlux_option( 'map_embed' );
$map       = dlux_map_with_marker( $map, (array) dlux_option( 'locations', [] ) );
?>
<section class="section visit"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?>>
	<div class="container visit__grid">
		<div class="visit__content">
			<?php echo dlux_section_head( get_sub_field( 'eyebrow' ), get_sub_field( 'heading' ) ); // phpcs:ignore ?>
			<?php if ( $t = get_sub_field( 'text' ) ) : ?>
				<p class="section-intro"><?php echo wp_kses( $t, [ 'br' => [] ] ); ?></p>
			<?php endif; ?>

			<?php if ( $locations ) : ?>
				<ul class="locations">
					<?php foreach ( $locations as $l ) :
						$link = dlux_location_link( $l );
						$tag  = $link ? 'a' : 'div';
						?>
						<li class="location">
							<<?php echo $tag; // phpcs:ignore ?> class="location__link"<?php if ( $link ) : ?> href="<?php echo esc_url( $link, [ 'http', 'https', 'mailto', 'tel', 'sms' ] ); ?>"<?php echo 0 === strpos( $link, 'http' ) ? ' target="_blank" rel="noopener"' : ''; ?><?php endif; ?>>
								<?php if ( ! empty( $l['image']['ID'] ) ) echo wp_get_attachment_image( $l['image']['ID'], 'dlux-thumb', false, [ 'class' => 'location__img', 'alt' => '', 'loading' => 'lazy' ] ); ?>
								<span class="location__text">
									<strong class="location__name"><?php echo esc_html( $l['name'] ); ?></strong>
									<?php if ( ! empty( $l['address'] ) ) : ?><span class="location__address"><?php echo esc_html( $l['address'] ); ?></span><?php endif; ?>
									<?php if ( ! empty( $l['opening_hours'] ) ) : ?><span class="location__hours"><?php echo wp_kses( nl2br( $l['opening_hours'] ), [ 'br' => [] ] ); ?></span><?php endif; ?>
								</span>
							</<?php echo $tag; // phpcs:ignore ?>>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<div class="btn-group">
				<?php
				// Primary button shows as a call icon. Number: Theme Settings › Header call & WhatsApp buttons
				// (or the Theme Settings phone); if neither is set, the button's own link is used.
				$primary  = get_sub_field( 'primary_button' );
				$call_url = function_exists( 'dlux_call_url' ) ? dlux_call_url() : '';
				$call_url = $call_url ?: ( $primary['url'] ?? '' );
				if ( $call_url ) {
					$label = ! empty( $primary['title'] ) ? $primary['title'] : ( trim( (string) dlux_option( 'call_label', '' ) ) ?: __( 'Call us', 'dlux' ) );
					printf(
						'<a class="visit-call" href="%1$s" aria-label="%2$s" title="%2$s"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M5 4h4l2 5-2.5 1.5a11 11 0 005 5L15 13l5 2v4a2 2 0 01-2 2A16 16 0 013 6a2 2 0 012-2z"/></svg></a>',
						esc_url( $call_url, [ 'tel', 'http', 'https', 'sms', 'mailto' ] ),
						esc_attr( $label )
					);
				}
				echo dlux_button( get_sub_field( 'secondary_button' ), 'btn--outline btn--sm' ); // phpcs:ignore
				?>
			</div>
		</div>

		<?php if ( $map ) : ?>
			<div class="visit__map"><?php echo dlux_map( $map ); // phpcs:ignore ?></div>
		<?php endif; ?>
	</div>
</section>
