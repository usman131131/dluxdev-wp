<?php
/**
 * Our work: grid of before/after (or single) images with captions.
 * Fields: section_id, background (light|dark), heading, intro, items[ before_image, after_image, label ].
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$items = array_filter( (array) ( get_sub_field( 'items' ) ?: [] ), function ( $it ) { return ! empty( $it['before_image']['ID'] ) || ! empty( $it['after_image']['ID'] ); } );
if ( ! $items ) return;
$bg = 'dark' === get_sub_field( 'background' ) ? 'dark' : 'light';
?>
<section class="section work work--<?php echo esc_attr( $bg ); ?>"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?>>
	<div class="container">
		<?php if ( $h = get_sub_field( 'heading' ) ) : ?><h2 class="work__title"><?php echo dlux_heading( $h ); // phpcs:ignore ?></h2><?php endif; ?>
		<?php if ( $t = get_sub_field( 'intro' ) ) : ?><p class="work__intro"><?php echo wp_kses( dlux_tokens( $t ), [ 'br' => [] ] ); ?></p><?php endif; ?>
		<ul class="work__grid">
			<?php foreach ( $items as $it ) :
				$before = $it['before_image']['ID'] ?? 0;
				$after  = $it['after_image']['ID'] ?? 0;
				$both   = $before && $after;
				?>
				<li class="work__item">
					<figure>
						<div class="work__media<?php echo $both ? ' is-split' : ''; ?>">
							<?php if ( $before ) : ?>
								<span class="work__half"><?php echo wp_get_attachment_image( $before, 'dlux-card', false, [ 'alt' => ( $it['label'] ?? '' ) . ( $both ? ' – ' . __( 'before', 'dlux' ) : '' ), 'loading' => 'lazy' ] ); ?><?php if ( $both ) : ?><span class="work__tag"><?php esc_html_e( 'Before', 'dlux' ); ?></span><?php endif; ?></span>
							<?php endif; ?>
							<?php if ( $after ) : ?>
								<span class="work__half"><?php echo wp_get_attachment_image( $after, 'dlux-card', false, [ 'alt' => ( $it['label'] ?? '' ) . ( $both ? ' – ' . __( 'after', 'dlux' ) : '' ), 'loading' => 'lazy' ] ); ?><?php if ( $both ) : ?><span class="work__tag work__tag--after"><?php esc_html_e( 'After', 'dlux' ); ?></span><?php endif; ?></span>
							<?php endif; ?>
						</div>
						<?php if ( ! empty( $it['label'] ) ) : ?><figcaption><?php echo esc_html( $it['label'] ); ?></figcaption><?php endif; ?>
					</figure>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
