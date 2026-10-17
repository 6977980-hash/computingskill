<?php
/**
 * [cs_contact_form] — a minimal, dependency-free contact form.
 *
 * Sends to the site's own admin email (get_option('admin_email')) via
 * wp_mail(), never a hard-coded address. Uses a nonce + honeypot field
 * for basic spam/CSRF protection. No third-party form plugin required,
 * but nothing here conflicts with installing one later.
 *
 * @package ComputingSkill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cs_handle_contact_form() {
	if ( empty( $_POST['cs_contact_submit'] ) ) {
		return;
	}

	if ( ! isset( $_POST['cs_contact_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['cs_contact_nonce'] ), 'cs_contact_form' ) ) {
		$GLOBALS['cs_contact_status'] = array( 'type' => 'error', 'message' => __( 'Security check failed. Please reload the page and try again.', 'computingskill' ) );
		return;
	}

	// Honeypot: real users never fill this hidden field.
	if ( ! empty( $_POST['cs_contact_website'] ) ) {
		$GLOBALS['cs_contact_status'] = array( 'type' => 'success', 'message' => __( 'Thanks — your message has been sent.', 'computingskill' ) );
		return;
	}

	$name    = isset( $_POST['cs_contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cs_contact_name'] ) ) : '';
	$email   = isset( $_POST['cs_contact_email'] ) ? sanitize_email( wp_unslash( $_POST['cs_contact_email'] ) ) : '';
	$message = isset( $_POST['cs_contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cs_contact_message'] ) ) : '';

	if ( empty( $name ) || empty( $email ) || ! is_email( $email ) || empty( $message ) ) {
		$GLOBALS['cs_contact_status'] = array( 'type' => 'error', 'message' => __( 'Please fill in your name, a valid email address, and a message.', 'computingskill' ) );
		return;
	}

	$to      = get_option( 'admin_email' );
	$subject = sprintf( '[%s] New contact form message from %s', get_bloginfo( 'name' ), $name );
	$body    = "Name: {$name}\nEmail: {$email}\n\nMessage:\n{$message}";
	$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );

	$sent = wp_mail( $to, $subject, $body, $headers );

	$GLOBALS['cs_contact_status'] = $sent
		? array( 'type' => 'success', 'message' => __( 'Thanks — your message has been sent. We\'ll get back to you as soon as we can.', 'computingskill' ) )
		: array( 'type' => 'error', 'message' => __( 'Sorry, something went wrong sending your message. Please try again later.', 'computingskill' ) );
}
add_action( 'template_redirect', 'cs_handle_contact_form' );

/**
 * The page cache kept /contact/ for days (deep audit 6.3, Oct 2026), so
 * every visitor got the same security token long after WordPress stopped
 * accepting it (12-24h) and "Security check failed" ate the message. Two
 * fixes: the page holding the form is never cached, and the form fetches a
 * fresh token from admin-ajax.php (never cached) when it loads, in case a
 * cache layer keeps the page anyway.
 */
function cs_contact_page_has_form() {
	return is_page( 'contact' ) || is_singular() && has_shortcode( (string) get_post_field( 'post_content', get_queried_object_id() ), 'cs_contact_form' );
}

function cs_contact_page_no_cache() {
	if ( ! cs_contact_page_has_form() ) {
		return;
	}
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	do_action( 'litespeed_control_set_nocache', 'contact form token' );
	nocache_headers();
}
add_action( 'template_redirect', 'cs_contact_page_no_cache', 1 );

function cs_contact_fresh_nonce() {
	nocache_headers();
	wp_send_json_success( array( 'nonce' => wp_create_nonce( 'cs_contact_form' ) ) );
}
add_action( 'wp_ajax_cs_contact_nonce', 'cs_contact_fresh_nonce' );
add_action( 'wp_ajax_nopriv_cs_contact_nonce', 'cs_contact_fresh_nonce' );

function cs_contact_form_shortcode() {
	ob_start();
	$status = isset( $GLOBALS['cs_contact_status'] ) ? $GLOBALS['cs_contact_status'] : null;
	?>
	<div class="cs-contact-form-wrap">
		<?php if ( $status ) : ?>
			<p class="cs-contact-status cs-contact-<?php echo esc_attr( $status['type'] ); ?>" role="alert"><?php echo esc_html( $status['message'] ); ?></p>
		<?php endif; ?>
		<form class="cs-contact-form" method="post" action="">
			<?php wp_nonce_field( 'cs_contact_form', 'cs_contact_nonce' ); ?>
			<p class="cs-hp-field" aria-hidden="true"><label>Website<input type="text" name="cs_contact_website" tabindex="-1" autocomplete="off"></label></p>

			<div class="cs-field">
				<label for="cs-contact-name"><?php esc_html_e( 'Name', 'computingskill' ); ?></label>
				<input type="text" id="cs-contact-name" name="cs_contact_name" required value="<?php echo isset( $_POST['cs_contact_name'] ) ? esc_attr( wp_unslash( $_POST['cs_contact_name'] ) ) : ''; ?>">
			</div>
			<div class="cs-field">
				<label for="cs-contact-email"><?php esc_html_e( 'Email', 'computingskill' ); ?></label>
				<input type="email" id="cs-contact-email" name="cs_contact_email" required value="<?php echo isset( $_POST['cs_contact_email'] ) ? esc_attr( wp_unslash( $_POST['cs_contact_email'] ) ) : ''; ?>">
			</div>
			<div class="cs-field">
				<label for="cs-contact-message"><?php esc_html_e( 'Message', 'computingskill' ); ?></label>
				<textarea id="cs-contact-message" name="cs_contact_message" rows="6" required><?php echo isset( $_POST['cs_contact_message'] ) ? esc_textarea( wp_unslash( $_POST['cs_contact_message'] ) ) : ''; ?></textarea>
			</div>
			<button type="submit" name="cs_contact_submit" value="1"><?php esc_html_e( 'Send Message', 'computingskill' ); ?></button>
		</form>
		<script>
		(function () {
			var field = document.querySelector('.cs-contact-form input[name="cs_contact_nonce"]');
			if (!field || !window.fetch) { return; }
			fetch(<?php echo wp_json_encode( add_query_arg( 'action', 'cs_contact_nonce', admin_url( 'admin-ajax.php' ) ) ); ?>, { credentials: 'same-origin', cache: 'no-store' })
				.then(function (r) { return r.json(); })
				.then(function (d) { if (d && d.success && d.data.nonce) { field.value = d.data.nonce; } })
				.catch(function () {});
		})();
		</script>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'cs_contact_form', 'cs_contact_form_shortcode' );
