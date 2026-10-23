<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$section_id = get_sub_field( 'section_id' );
$items      = get_sub_field( 'contact_items' ) ?: [];
$status     = isset( $_GET['contact'] ) ? sanitize_key( wp_unslash( $_GET['contact'] ) ) : ''; // phpcs:ignore

/* Fallback details from Theme Settings */
if ( ! $items ) {
	$locs = (array) dlux_option( 'locations', [] );
	if ( ! empty( $locs[0]['address'] ) ) {
		$items[] = [ 'icon' => 'location', 'label' => __( 'Visit us', 'dlux' ), 'value' => $locs[0]['address'], 'link' => $locs[0]['directions_url'] ?? '' ];
	}
	if ( $e = dlux_option( 'email' ) ) {
		$items[] = [ 'icon' => 'email', 'label' => __( 'Email us', 'dlux' ), 'value' => $e, 'link' => 'mailto:' . $e ];
	}
	if ( $ph = dlux_option( 'phone' ) ) {
		$items[] = [ 'icon' => 'message', 'label' => __( 'Text us or call after hours', 'dlux' ), 'value' => $ph, 'link' => 'tel:' . preg_replace( '/[^0-9+]/', '', $ph ) ];
	}
}
$socials  = get_sub_field( 'show_socials' ) ? (array) dlux_option( 'social_links', [] ) : [];
$services = dlux_contact_services( get_sub_field( 'service_options' ) );
$uid      = 'contact-' . get_row_index();
?>
<section class="section contact"<?php echo dlux_section_id( $section_id ); // phpcs:ignore ?>>
	<div class="container">
		<div class="contact__card">
			<aside class="contact__info">
				<div class="contact__intro">
					<?php if ( get_sub_field( 'show_logo' ) ) echo dlux_logo( dlux_option( 'site_logo' ), 'logo contact__logo' ); // phpcs:ignore ?>
					<?php if ( $t = get_sub_field( 'tagline' ) ) : ?>
						<p class="contact__tagline"><?php echo wp_kses( $t, [ 'br' => [] ] ); ?></p>
					<?php endif; ?>
				</div>

				<?php if ( $items ) : ?>
					<ul class="contact__details">
						<?php foreach ( $items as $it ) :
							if ( empty( $it['value'] ) ) continue;
							$link = $it['link'] ?? '';
							?>
							<li class="contact__detail">
								<span class="contact__icon"><?php echo dlux_contact_icon( $it['icon'] ?? '' ); // phpcs:ignore ?></span>
								<span>
									<?php if ( ! empty( $it['label'] ) ) : ?><span class="contact__label"><?php echo esc_html( $it['label'] ); ?></span><?php endif; ?>
									<?php if ( $link ) : ?>
										<a class="contact__value" href="<?php echo esc_url( $link, [ 'http', 'https', 'mailto', 'tel', 'sms' ] ); ?>"<?php echo 0 === strpos( $link, 'http' ) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $it['value'] ); ?></a>
									<?php else : ?>
										<span class="contact__value"><?php echo esc_html( $it['value'] ); ?></span>
									<?php endif; ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( $socials ) : ?>
					<ul class="socials contact__socials">
						<?php foreach ( $socials as $s ) :
							if ( empty( $s['url'] ) ) continue; ?>
							<li><a href="<?php echo esc_url( $s['url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ucfirst( $s['platform'] ) ); ?>"><?php echo dlux_social_icon( $s['platform'] ); // phpcs:ignore ?></a></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</aside>

			<div class="contact__form-wrap">
				<?php if ( $h = get_sub_field( 'form_heading' ) ) : ?>
					<h2 class="contact__title"><?php echo dlux_heading( $h ); // phpcs:ignore ?></h2>
				<?php endif; ?>

				<div class="contact__notice<?php echo 'sent' === $status ? ' is-success' : ( $status ? ' is-error' : '' ); ?>" data-contact-notice role="status" aria-live="polite"<?php echo $status ? '' : ' hidden'; ?>>
					<?php if ( 'sent' === $status ) : ?>
						<?php echo esc_html( get_sub_field( 'success_message' ) ?: __( 'Thanks! Your message has been sent.', 'dlux' ) ); ?>
					<?php elseif ( 'invalid' === $status ) : ?>
						<?php esc_html_e( 'Please add your name, a valid email and a message.', 'dlux' ); ?>
					<?php elseif ( 'error' === $status ) : ?>
						<?php esc_html_e( 'Sorry, something went wrong. Please try again or contact us directly.', 'dlux' ); ?>
					<?php endif; ?>
				</div>

					<form class="contact__form" data-contact-form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="dlux_contact">
						<input type="hidden" name="dlux_page" value="<?php echo esc_attr( get_queried_object_id() ); ?>">
						<input type="hidden" name="dlux_row" value="<?php echo esc_attr( get_row_index() - 1 ); ?>">
						<?php wp_nonce_field( 'dlux_contact', 'dlux_contact_nonce' ); ?>
						<p class="contact__hp" aria-hidden="true"><label>Website <input type="text" name="dlux_website" tabindex="-1" autocomplete="off"></label></p>

						<div class="contact__row">
							<label class="contact__field">
								<span class="screen-reader-text"><?php esc_html_e( 'Name', 'dlux' ); ?></span>
								<input type="text" name="dlux_name" placeholder="<?php esc_attr_e( 'Name', 'dlux' ); ?>" autocomplete="name" required>
							</label>
							<label class="contact__field">
								<span class="screen-reader-text"><?php esc_html_e( 'Email', 'dlux' ); ?></span>
								<input type="email" name="dlux_email" placeholder="<?php esc_attr_e( 'Email', 'dlux' ); ?>" autocomplete="email" required>
							</label>
						</div>

						<label class="contact__field">
							<span class="screen-reader-text"><?php esc_html_e( 'Phone (optional)', 'dlux' ); ?></span>
							<input type="tel" name="dlux_phone" placeholder="<?php esc_attr_e( 'Phone (Optional)', 'dlux' ); ?>" autocomplete="tel">
						</label>

						<?php if ( $services ) : ?>
							<label class="contact__field contact__field--select">
								<span class="screen-reader-text"><?php esc_html_e( 'Service', 'dlux' ); ?></span>
								<select name="dlux_service" id="<?php echo esc_attr( $uid ); ?>-service">
									<option value=""><?php esc_html_e( 'Select a Service…', 'dlux' ); ?></option>
									<?php foreach ( $services as $s ) : ?>
										<option value="<?php echo esc_attr( $s ); ?>"><?php echo esc_html( $s ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						<?php endif; ?>

						<label class="contact__field">
							<span class="screen-reader-text"><?php esc_html_e( 'Message', 'dlux' ); ?></span>
							<textarea name="dlux_message" rows="4" placeholder="<?php esc_attr_e( 'How can we help?', 'dlux' ); ?>" required></textarea>
						</label>

						<button type="submit" class="btn btn--primary contact__submit" data-contact-submit><span><?php echo esc_html( get_sub_field( 'button_text' ) ?: __( 'Send Request', 'dlux' ) ); ?></span></button>
					</form>
			</div>
		</div>
	</div>
</section>
