<?php
/**
 * Support screen: the request form.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 *
 * @var array<string,mixed> $view View model.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mmwp_form     = $view['form'];
$mmwp_selected = null;
foreach ( $view['plugins'] as $mmwp_candidate ) {
	if ( $mmwp_candidate->slug === $view['selected'] ) {
		$mmwp_selected = $mmwp_candidate;
	}
}
?>
<h2><?php esc_html_e( 'Contact support', 'mammothwp-support' ); ?></h2>
<form method="post" action="<?php echo esc_url( $view['post_url'] ); ?>" id="mmwp-support-form" class="mmwp-support__form">
	<input type="hidden" name="action" value="<?php echo esc_attr( $view['action'] ); ?>" />
	<?php wp_nonce_field( $view['nonce_name'] ); ?>
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><label for="mmwp-support-plugin"><?php esc_html_e( 'Plugin', 'mammothwp-support' ); ?></label></th>
				<td>
					<select name="plugin" id="mmwp-support-plugin" required>
						<?php if ( null === $mmwp_selected ) : ?>
							<option value=""><?php esc_html_e( 'Choose a plugin…', 'mammothwp-support' ); ?></option>
						<?php endif; ?>
						<?php foreach ( $view['plugins'] as $mmwp_plugin ) : ?>
							<option value="<?php echo esc_attr( $mmwp_plugin->slug ); ?>" data-recipient="<?php echo esc_attr( $mmwp_plugin->email ); ?>" <?php selected( $view['selected'], $mmwp_plugin->slug ); ?>>
								<?php echo esc_html( $mmwp_plugin->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description" id="mmwp-support-recipient">
						<?php
						if ( null !== $mmwp_selected && $mmwp_selected->has_email() ) {
							/* translators: %s: recipient email address. */
							echo esc_html( sprintf( __( 'This message will be sent to %s.', 'mammothwp-support' ), $mmwp_selected->email ) );
						} elseif ( null !== $mmwp_selected ) {
							esc_html_e( 'This plugin has no valid support email address configured, so sending is disabled.', 'mammothwp-support' );
						}
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="mmwp-support-subject"><?php esc_html_e( 'Subject', 'mammothwp-support' ); ?></label></th>
				<td><input type="text" class="regular-text" name="subject" id="mmwp-support-subject" value="<?php echo esc_attr( $mmwp_form['subject'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="mmwp-support-message"><?php esc_html_e( 'Message', 'mammothwp-support' ); ?></label></th>
				<td><textarea name="message" id="mmwp-support-message" class="large-text" rows="8" required><?php echo esc_textarea( $mmwp_form['message'] ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label for="mmwp-support-name"><?php esc_html_e( 'Your name', 'mammothwp-support' ); ?></label></th>
				<td><input type="text" class="regular-text" name="name" id="mmwp-support-name" value="<?php echo esc_attr( $mmwp_form['name'] ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="mmwp-support-reply-to"><?php esc_html_e( 'Reply-to email', 'mammothwp-support' ); ?></label></th>
				<td><input type="email" class="regular-text" name="reply_to" id="mmwp-support-reply-to" value="<?php echo esc_attr( $mmwp_form['reply_to'] ); ?>" required /></td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Site report', 'mammothwp-support' ); ?></th>
				<td>
					<fieldset>
						<label for="mmwp-support-include-report">
							<input type="checkbox" name="include_report" id="mmwp-support-include-report" value="1" <?php checked( $mmwp_form['include_report'] ); ?> />
							<?php esc_html_e( 'Include site report', 'mammothwp-support' ); ?>
						</label>
						<?php if ( $view['log_ready'] ) : ?>
							<br />
							<label for="mmwp-support-include-log">
								<input type="checkbox" name="include_log" id="mmwp-support-include-log" value="1" <?php checked( $mmwp_form['include_log'] ); ?> />
								<?php esc_html_e( 'Include the last 50 lines of debug.log (needs the site report)', 'mammothwp-support' ); ?>
							</label>
						<?php endif; ?>
						<p class="description"><?php esc_html_e( 'Off by default. Nothing is sent until you press Send. The email always contains your message, name, reply-to address and the plugin name and version.', 'mammothwp-support' ); ?></p>
						<details class="mmwp-support__preview">
							<summary><?php esc_html_e( 'Preview what will be sent', 'mammothwp-support' ); ?></summary>
							<p class="description"><?php esc_html_e( 'This is the exact report text added to the email when the box above is ticked.', 'mammothwp-support' ); ?></p>
							<pre id="mmwp-report-preview" class="mmwp-support__pre"><?php echo esc_html( $view['report_text'] ); ?></pre>
							<?php if ( $view['log_ready'] ) : ?>
								<p class="description"><?php esc_html_e( 'Added only when the debug.log box is ticked (emails, IP addresses and tokens are redacted):', 'mammothwp-support' ); ?></p>
								<pre id="mmwp-log-preview" class="mmwp-support__pre"><?php echo esc_html( implode( "\n", $view['log_lines'] ) ); ?></pre>
							<?php endif; ?>
						</details>
						<p><button type="button" class="button" data-mmwp-copy="#mmwp-report-preview"><?php esc_html_e( 'Copy report', 'mammothwp-support' ); ?></button></p>
					</fieldset>
				</td>
			</tr>
		</tbody>
	</table>
	<p class="submit">
		<button type="submit" class="button button-primary" id="mmwp-support-submit" <?php disabled( null !== $mmwp_selected && ! $mmwp_selected->has_email() ); ?>><?php esc_html_e( 'Send', 'mammothwp-support' ); ?></button>
	</p>
</form>
