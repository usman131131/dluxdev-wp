<?php
/**
 * Quote form (Beyond Detailing): contact details on the left, quote request form on the right.
 * Fields: section_id, eyebrow, heading, text, contact_items[ icon, label, value, link ],
 *         interests_label, interests, car_placeholder, notes_placeholder, footnote, button_text,
 *         recipient, success_message.
 * Sent by inc/quote.php (AJAX, normal POST fallback).
 */
if ( ! defined( 'ABSPATH' ) ) exit;
$items  = (array) ( get_sub_field( 'contact_items' ) ?: [] );
$status = isset( $_GET['quote'] ) ? sanitize_key( wp_unslash( $_GET['quote'] ) ) : ''; // phpcs:ignore
if ( ! $items ) {
	if ( $ph = dlux_option( 'phone' ) ) {
		$tel     = 'tel:' . preg_replace( '/[^0-9+]/', '', $ph );
		$items[] = [ 'icon' => 'phone', 'label' => __( 'Call us', 'dlux' ), 'value' => $ph, 'link' => $tel ];
	}
	if ( $em = dlux_option( 'email' ) ) {
		$items[] = [ 'icon' => 'email', 'label' => __( 'Email us', 'dlux' ), 'value' => $em, 'link' => 'mailto:' . $em ];
	}
}
$interests = dlux_quote_interests( get_sub_field( 'interests' ) );
$uid       = 'quote-' . get_row_index();
?>
<section class="section quote"<?php echo dlux_section_id( get_sub_field( 'section_id' ) ); // phpcs:ignore ?>>
	<div class="container quote__inner">
		<div class="quote__info">
			<?php if ( $e = get_sub_field( 'eyebrow' ) ) : ?><p class="bd-eyebrow bd-eyebrow--dark"><?php echo esc_html( dlux_tokens( $e ) ); ?></p><?php endif; ?>
			<?php if ( $h = get_sub_field( 'heading' ) ) : ?><h2 class="bd-title"><?php echo dlux_heading( $h ); // phpcs:ignore ?></h2><?php endif; ?>
			<?php if ( $t = get_sub_field( 'text' ) ) : ?><p class="quote__text"><?php echo wp_kses( dlux_tokens( $t ), [ 'br' => [] ] ); ?></p><?php endif; ?>
			<?php if ( $items ) : ?>
				<ul class="quote__contacts">
					<?php foreach ( $items as $it ) :
						if ( empty( $it['value'] ) && empty( $it['label'] ) ) continue;
						$link = trim( (string) ( $it['link'] ?? '' ) );
						?>
						<li>
							<span class="quote__icon"><?php echo dlux_quote_icon( $it['icon'] ?? 'message' ); // phpcs:ignore ?></span>
							<span>
								<strong><?php echo esc_html( $it['label'] ?? '' ); ?></strong>
								<?php if ( $link ) : ?>
									<a href="<?php echo esc_url( $link, [ 'http', 'https', 'mailto', 'tel', 'sms' ] ); ?>"><?php echo esc_html( $it['value'] ?? '' ); ?></a>
								<?php else : ?>
									<span><?php echo esc_html( $it['value'] ?? '' ); ?></span>
								<?php endif; ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<div class="quote__card">
			<div class="quote__notice<?php echo 'sent' === $status ? ' is-success' : ( $status ? ' is-error' : '' ); ?>" data-quote-notice role="status"<?php echo $status ? '' : ' hidden'; ?>>
				<?php
				if ( 'sent' === $status ) echo esc_html( get_sub_field( 'success_message' ) ?: __( 'Thanks! We’ll be in touch with your quote soon.', 'dlux' ) );
				elseif ( 'invalid' === $status ) esc_html_e( 'Please add your name and a mobile number or email.', 'dlux' );
				elseif ( $status ) esc_html_e( 'Sorry, something went wrong. Please call or email us instead.', 'dlux' );
				?>
			</div>
			<form class="quote__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-quote-form>
				<input type="hidden" name="action" value="dlux_quote">
				<input type="hidden" name="dlux_page" value="<?php echo esc_attr( get_queried_object_id() ); ?>">
				<input type="hidden" name="dlux_row" value="<?php echo esc_attr( get_row_index() - 1 ); ?>">
				<?php wp_nonce_field( 'dlux_quote', 'dlux_quote_nonce' ); ?>
				<p class="contact__hp" aria-hidden="true"><label>Website <input type="text" name="dlux_website" tabindex="-1" autocomplete="off"></label></p>

				<?php if ( $interests ) : ?>
					<fieldset class="quote__interests">
						<legend><?php echo esc_html( get_sub_field( 'interests_label' ) ?: __( 'What are you interested in?', 'dlux' ) ); ?></legend>
						<div class="quote__chips">
							<?php foreach ( $interests as $i => $label ) : ?>
								<label class="quote__chip">
									<input type="checkbox" name="dlux_interests[]" value="<?php echo esc_attr( $label ); ?>">
									<span><?php echo esc_html( $label ); ?></span>
								</label>
							<?php endforeach; ?>
						</div>
					</fieldset>
				<?php endif; ?>

				<div class="quote__row quote__row--3">
					<label class="quote__field"><span><?php esc_html_e( 'Name', 'dlux' ); ?></span><input type="text" name="dlux_name" autocomplete="name" required></label>
					<label class="quote__field"><span><?php esc_html_e( 'Mobile', 'dlux' ); ?></span><input type="tel" name="dlux_phone" autocomplete="tel"></label>
					<label class="quote__field"><span><?php esc_html_e( 'Email', 'dlux' ); ?></span><input type="email" name="dlux_email" autocomplete="email"></label>
				</div>
				<div class="quote__row quote__row--3">
					<label class="quote__field"><span><?php esc_html_e( 'Car make, model & year', 'dlux' ); ?></span><input type="text" name="dlux_car" placeholder="<?php echo esc_attr( get_sub_field( 'car_placeholder' ) ?: 'e.g. Toyota RAV4 2025' ); ?>"></label>
				</div>
				<label class="quote__field"><span><?php esc_html_e( 'Anything else we should know?', 'dlux' ); ?></span><textarea name="dlux_message" rows="4" placeholder="<?php echo esc_attr( (string) get_sub_field( 'notes_placeholder' ) ); ?>"></textarea></label>

				<div class="quote__foot">
					<?php if ( $f = get_sub_field( 'footnote' ) ) : ?><p class="quote__footnote"><?php echo esc_html( $f ); ?></p><?php endif; ?>
					<button type="submit" class="btn btn--primary quote__submit" data-quote-submit><span><?php echo esc_html( get_sub_field( 'button_text' ) ?: __( 'Request my quote', 'dlux' ) ); ?></span></button>
				</div>
			</form>
		</div>
	</div>
</section>
