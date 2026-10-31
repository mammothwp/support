<?php
/**
 * Support screen: recent sends (time, plugin, ref, result; never contents).
 *
 * @package MammothWP\Support
 * @since   1.0.0
 *
 * @var array<string,mixed> $view View model.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( array() === $view['send_log'] ) {
	return;
}
?>
<h2><?php esc_html_e( 'Recent requests', 'mammothwp-support' ); ?></h2>
<table class="widefat striped mmwp-support__log">
	<thead>
		<tr>
			<th scope="col"><?php esc_html_e( 'Time', 'mammothwp-support' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Plugin', 'mammothwp-support' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Reference', 'mammothwp-support' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Result', 'mammothwp-support' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $view['send_log'] as $mmwp_entry ) : ?>
			<tr>
				<td><?php echo esc_html( wp_date( 'Y-m-d H:i', (int) $mmwp_entry['time'] ) ); ?></td>
				<td><?php echo esc_html( (string) $mmwp_entry['plugin'] ); ?></td>
				<td><code><?php echo esc_html( (string) $mmwp_entry['ref'] ); ?></code></td>
				<td><?php echo 'sent' === $mmwp_entry['result'] ? esc_html__( 'Sent', 'mammothwp-support' ) : esc_html__( 'Failed', 'mammothwp-support' ); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
