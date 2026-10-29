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
		<div class="work__slider">
		<?php if ( count( $items ) > 1 ) : ?>
			<button type="button" class="carousel-nav__btn carousel-nav__btn--prev" data-work-prev aria-label="<?php esc_attr_e( 'Previous photos', 'dlux' ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
		<?php endif; ?>
		<ul class="work__grid"<?php echo count( $items ) > 1 ? ' data-work-carousel' : ''; ?>>
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
		<?php if ( count( $items ) > 1 ) : ?>
			<button type="button" class="carousel-nav__btn carousel-nav__btn--next" data-work-next aria-label="<?php esc_attr_e( 'Next photos', 'dlux' ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</button>
		<?php endif; ?>
		</div>
	</div>
</section>
