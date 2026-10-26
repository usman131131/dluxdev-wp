<?php
/**
 * Info tabs (e.g. "Ceramic & graphene coatings explained").
 * Fields: section_id, eyebrow, heading, summary_heading, summary_text,
 *         tabs[ label, badge, heading, intro, cards[ title, text, list ], summary_heading, summary_text ].
 * A tab's own heading / summary replaces the section's one while that tab is open.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$tabs = array_values( array_filter( (array) ( get_sub_field( 'tabs' ) ?: [] ), function ( $t ) { return '' !== trim( (string) ( $t['label'] ?? '' ) ); } ) );
if ( ! $tabs ) return;
$uid        = 'it-' . wp_unique_id();
$section_sh = (string) get_sub_field( 'summary_heading' );
$section_st = (string) get_sub_field( 'summary_text' );
?>
<section class="section info-tabs"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?> data-info-tabs>
	<div class="container">
		<div class="info-tabs__box">
			<?php if ( $e = get_sub_field( 'eyebrow' ) ) : ?><p class="info-tabs__eyebrow"><?php echo esc_html( dlux_tokens( $e ) ); ?></p><?php endif; ?>
			<?php
			$section_heading = (string) get_sub_field( 'heading' );
			foreach ( $tabs as $i => $t ) :
				$h = '' !== trim( (string) ( $t['heading'] ?? '' ) ) ? $t['heading'] : $section_heading;
				if ( '' === trim( $h ) ) continue;
				?>
				<h2 class="info-tabs__title" data-it-for="<?php echo esc_attr( "$uid-panel-$i" ); ?>"<?php echo 0 === $i ? '' : ' hidden'; ?>><?php echo dlux_heading( $h ); // phpcs:ignore ?></h2>
			<?php endforeach; ?>

			<?php if ( count( $tabs ) > 1 ) : ?>
				<div class="info-tabs__list" role="tablist">
					<?php foreach ( $tabs as $i => $t ) : ?>
						<button type="button" class="info-tabs__tab" role="tab" id="<?php echo esc_attr( "$uid-tab-$i" ); ?>" aria-controls="<?php echo esc_attr( "$uid-panel-$i" ); ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>"<?php echo 0 === $i ? '' : ' tabindex="-1"'; ?>>
							<?php echo esc_html( $t['label'] ); ?>
							<?php if ( ! empty( $t['badge'] ) ) : ?><small><?php echo esc_html( $t['badge'] ); ?></small><?php endif; ?>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php foreach ( $tabs as $i => $t ) : ?>
				<div class="info-tabs__panel" role="tabpanel" id="<?php echo esc_attr( "$uid-panel-$i" ); ?>" aria-labelledby="<?php echo esc_attr( "$uid-tab-$i" ); ?>"<?php echo 0 === $i ? '' : ' hidden'; ?>>
					<?php if ( ! empty( $t['intro'] ) ) : ?><p class="info-tabs__intro"><?php echo wp_kses( $t['intro'], [ 'br' => [], 'strong' => [], 'em' => [] ] ); ?></p><?php endif; ?>
					<?php if ( ! empty( $t['cards'] ) ) : ?>
						<ul class="info-tabs__grid">
							<?php foreach ( (array) $t['cards'] as $c ) :
								if ( empty( $c['title'] ) ) continue;
								$list = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) ( $c['list'] ?? '' ) ) ) );
								?>
								<li class="info-card">
									<h3 class="info-card__title"><?php echo esc_html( $c['title'] ); ?></h3>
									<?php if ( ! empty( $c['text'] ) ) : ?><p class="info-card__text"><?php echo wp_kses( $c['text'], [ 'br' => [], 'strong' => [], 'em' => [] ] ); ?></p><?php endif; ?>
									<?php if ( $list ) : ?>
										<ul class="tick-list tick-list--sm">
											<?php foreach ( $list as $li ) : ?><li><?php echo esc_html( $li ); ?></li><?php endforeach; ?>
										</ul>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php
					$sh = '' !== trim( (string) ( $t['summary_heading'] ?? '' ) ) ? $t['summary_heading'] : $section_sh;
					$st = '' !== trim( wp_strip_all_tags( (string) ( $t['summary_text'] ?? '' ) ) ) ? $t['summary_text'] : $section_st;
					if ( $sh || $st ) :
						?>
						<div class="info-tabs__summary">
							<?php if ( $sh ) : ?><h3 class="info-tabs__summary-title"><?php echo esc_html( $sh ); ?></h3><?php endif; ?>
							<?php if ( $st ) : ?><div class="info-tabs__summary-text"><?php echo wp_kses_post( $st ); ?></div><?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>

		</div>
	</div>
</section>
