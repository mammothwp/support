<?php
/**
 * Support screen: optional "Powered by" line (read from the resolved branding).
 *
 * @package MammothWP\Support
 * @since   1.0.0
 *
 * @var array<string,mixed> $view View model.
 */

use MammothWP\Support\Config;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mmwp_branding = $view['branding'];
if ( ! $mmwp_branding->get( 'show_powered_by' ) ) {
	return;
}
?>
<p class="description mmwp-support__powered">
	<?php
	if ( $mmwp_branding->is_mammoth() ) {
		printf(
			/* translators: %s: linked brand name. */
			esc_html__( 'Powered by %s', 'mammothwp-support' ),
			'<a href="' . esc_url( Config::MAMMOTH_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( (string) $mmwp_branding->get( 'name' ) ) . '</a>'
		);
		return;
	}
	/* translators: %s: brand name. */
	echo esc_html( sprintf( __( 'Powered by %s', 'mammothwp-support' ), (string) $mmwp_branding->get( 'name' ) ) );
	?>
</p>
