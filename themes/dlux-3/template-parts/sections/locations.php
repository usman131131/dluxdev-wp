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
				echo dlux_button( get_sub_field( 'primary_button' ), 'btn--primary btn--sm' ); // phpcs:ignore
				echo dlux_button( get_sub_field( 'secondary_button' ), 'btn--outline btn--sm' ); // phpcs:ignore
				?>
			</div>
		</div>

		<?php if ( $map ) : ?>
			<div class="visit__map"><?php echo dlux_map( $map ); // phpcs:ignore ?></div>
		<?php endif; ?>
	</div>
</section>
