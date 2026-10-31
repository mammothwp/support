<?php
/**
 * Support screen: notices (send result, wp-kit degrade).
 *
 * @package MammothWP\Support
 * @since   1.0.0
 *
 * @var array<string,mixed> $view View model.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mmwp_notice = $view['notice'];
?>
<?php if ( $view['kit_notice'] ) : ?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: loaded wp-kit version or "none", 2: required wp-kit version. */
					__( 'The REST API, WP-CLI and Abilities for Support are switched off because another plugin loaded an older or missing wp-kit (loaded: %1$s, required: %2$s). This screen and sending email still work.', 'mammothwp-support' ),
					'' === $view['kit_loaded'] ? __( 'none', 'mammothwp-support' ) : $view['kit_loaded'],
					\MammothWP\Support\Config::WPKIT_MIN_VERSION
				)
			);
			?>
		</p>
	</div>
<?php endif; ?>
<?php if ( ! empty( $mmwp_notice['ok'] ) ) : ?>
	<div class="notice notice-success">
		<p>
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: recipient email address, 2: reference code. */
					__( 'Your message was sent to %1$s. Reference: %2$s', 'mammothwp-support' ),
					$mmwp_notice['to'],
					$mmwp_notice['ref']
				)
			);
			?>
		</p>
	</div>
<?php elseif ( array() !== $mmwp_notice ) : ?>
	<div class="notice notice-error">
		<p><?php echo esc_html( (string) $mmwp_notice['message'] ); ?></p>
		<?php if ( '' !== (string) ( $mmwp_notice['mailto'] ?? '' ) ) : ?>
			<p>
				<a class="button" href="<?php echo esc_url( (string) $mmwp_notice['mailto'], array( 'mailto' ) ); ?>"><?php esc_html_e( 'Send it from your email app instead', 'mammothwp-support' ); ?></a>
				<button type="button" class="button" data-mmwp-copy="#mmwp-report-preview"><?php esc_html_e( 'Copy report', 'mammothwp-support' ); ?></button>
			</p>
			<p class="description"><?php esc_html_e( 'Tip: the site could not send email. Check its mail configuration (an SMTP plugin or a transactional mail service usually fixes this).', 'mammothwp-support' ); ?></p>
		<?php endif; ?>
	</div>
<?php endif; ?>
