<?php
/**
 * Partners (Beyond Detailing): logo cards for specialist partners.
 * Fields: section_id, eyebrow, heading, text, partners[ logo, name, tag, text, link ].
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$partners = array_filter( (array) ( get_sub_field( 'partners' ) ?: [] ), function ( $p ) { return '' !== trim( (string) ( $p['name'] ?? '' ) ) || ! empty( $p['logo']['ID'] ); } );
if ( ! $partners ) return;
?>
<section class="section partners"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?>>
	<div class="container">
		<?php if ( $e = get_sub_field( 'eyebrow' ) ) : ?><p class="bd-eyebrow bd-eyebrow--dark"><?php echo esc_html( dlux_tokens( $e ) ); ?></p><?php endif; ?>
		<?php if ( $h = get_sub_field( 'heading' ) ) : ?><h2 class="bd-title"><?php echo dlux_heading( $h ); // phpcs:ignore ?></h2><?php endif; ?>
		<?php if ( $t = get_sub_field( 'text' ) ) : ?><p class="partners__intro"><?php echo wp_kses( dlux_tokens( $t ), [ 'br' => [], 'strong' => [] ] ); ?></p><?php endif; ?>
		<ul class="partners__grid">
			<?php foreach ( $partners as $p ) :
				$link = $p['link'] ?? null;
				?>
				<li class="partner">
					<?php if ( ! empty( $p['logo']['ID'] ) ) : ?>
						<div class="partner__logo"><?php echo wp_get_attachment_image( $p['logo']['ID'], 'medium', false, [ 'alt' => $p['name'] ?? '', 'loading' => 'lazy' ] ); ?></div>
					<?php endif; ?>
					<?php if ( ! empty( $p['name'] ) ) : ?><h3 class="partner__name"><?php echo esc_html( $p['name'] ); ?></h3><?php endif; ?>
					<?php if ( ! empty( $p['tag'] ) ) : ?><span class="partner__tag"><?php echo esc_html( $p['tag'] ); ?></span><?php endif; ?>
					<?php if ( ! empty( $p['text'] ) ) : ?><p class="partner__text"><?php echo wp_kses( $p['text'], [ 'br' => [] ] ); ?></p><?php endif; ?>
					<?php if ( ! empty( $link['url'] ) ) : ?>
						<a class="bd-link bd-link--dark" href="<?php echo esc_url( $link['url'] ); ?>"<?php echo ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : ''; ?>><?php echo esc_html( $link['title'] ?: __( 'Visit website', 'dlux' ) ); ?></a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
