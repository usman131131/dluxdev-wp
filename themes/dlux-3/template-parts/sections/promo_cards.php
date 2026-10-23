<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$cards = get_sub_field( 'cards' );
if ( ! $cards ) return;
?>
<section class="section promos"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?>>
	<div class="container promos__grid">
		<?php foreach ( $cards as $c ) :
			$style   = 'dark' === $c['style'] ? 'dark' : 'accent';
			$btn_cls = 'accent' === $style ? 'btn--light btn--sm' : 'btn--primary btn--sm';
			$top     = 'top' === $c['button_position'];
			$id      = ! empty( $c['label'] ) ? ' id="' . esc_attr( sanitize_title( $c['label'] ) ) . '"' : '';
			// Gift voucher card opens the voucher popup (Dlux Booking › Gift Voucher Popup).
			$voucher = dlux_voucher_popup_enabled() && preg_match( '/gift|voucher/i', $c['label'] . ' ' . wp_strip_all_tags( (string) $c['heading'] ) );
			$button  = $c['button'];
			if ( $voucher && empty( $button['url'] ) ) {
				$button = [ 'url' => '#gift-voucher', 'title' => __( 'Buy a gift voucher', 'dlux' ), 'target' => '' ];
			}
			?>
			<article class="promo promo--<?php echo esc_attr( $style ); ?><?php echo ! empty( $c['image']['ID'] ) ? ' has-image' : ''; ?><?php echo $voucher ? ' is-voucher' : ''; ?>"<?php echo $id; // phpcs:ignore ?><?php echo $voucher ? ' data-voucher-open' : ''; ?>>
				<div class="promo__top">
					<?php if ( ! empty( $c['label'] ) ) : ?><p class="promo__label"><?php echo esc_html( $c['label'] ); ?></p><?php endif; ?>
					<?php if ( $top ) echo dlux_button( $button, $btn_cls ); // phpcs:ignore ?>
				</div>
				<h2 class="promo__title"><?php echo dlux_heading( $c['heading'] ); // phpcs:ignore ?></h2>
				<?php if ( ! empty( $c['text'] ) ) : ?>
					<p class="promo__text"><?php echo wp_kses( $c['text'], [ 'br' => [] ] ); ?></p>
				<?php endif; ?>
				<?php if ( ! $top ) echo dlux_button( $button, $btn_cls ); // phpcs:ignore ?>
				<?php if ( ! empty( $c['image']['ID'] ) ) echo wp_get_attachment_image( $c['image']['ID'], 'large', false, [ 'class' => 'promo__img', 'alt' => '', 'loading' => 'lazy' ] ); ?>
			</article>
		<?php endforeach; ?>
	</div>
</section>
