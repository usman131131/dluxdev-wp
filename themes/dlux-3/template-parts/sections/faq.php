<?php
/**
 * FAQ accordion. Uses <details>, so it works without JavaScript.
 * Fields: section_id, background (light|grey|dark), eyebrow, heading, items[ question, answer ], add_schema.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$items = array_values( array_filter( (array) ( get_sub_field( 'items' ) ?: [] ), function ( $q ) { return '' !== trim( (string) ( $q['question'] ?? '' ) ); } ) );
if ( ! $items ) return;
$bg = get_sub_field( 'background' );
$bg = in_array( $bg, [ 'light', 'grey', 'dark' ], true ) ? $bg : 'grey';
?>
<section class="section faq faq--<?php echo esc_attr( $bg ); ?>"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?>>
	<div class="container faq__inner">
		<?php if ( $e = get_sub_field( 'eyebrow' ) ) : ?><p class="psteps__eyebrow"><?php echo esc_html( dlux_tokens( $e ) ); ?></p><?php endif; ?>
		<?php if ( $h = get_sub_field( 'heading' ) ) : ?><h2 class="faq__title"><?php echo dlux_heading( $h ); // phpcs:ignore ?></h2><?php endif; ?>
		<div class="faq__list">
			<?php foreach ( $items as $q ) : ?>
				<details class="faq__item">
					<summary class="faq__q"><span><?php echo esc_html( $q['question'] ); ?></span><span class="faq__icon" aria-hidden="true"></span></summary>
					<div class="faq__a"><?php echo wp_kses_post( wpautop( (string) ( $q['answer'] ?? '' ) ) ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	if ( get_sub_field( 'add_schema' ) ) {
		$schema = [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => array_map( function ( $q ) {
				return [
					'@type'          => 'Question',
					'name'           => wp_strip_all_tags( $q['question'] ),
					'acceptedAnswer' => [ '@type' => 'Answer', 'text' => wp_strip_all_tags( (string) ( $q['answer'] ?? '' ) ) ],
				];
			}, $items ),
		];
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . '</script>'; // phpcs:ignore
	}
	?>
</section>
