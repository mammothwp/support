<?php
/**
 * Support screen: registered plugins.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 *
 * @var array<string,mixed> $view View model.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mmwp_show_vendor = (bool) $view['branding']->get( 'show_vendor_names' );
?>
<h2><?php esc_html_e( 'Plugins', 'mammothwp-support' ); ?></h2>
<table class="widefat striped mmwp-support__plugins">
	<thead>
		<tr>
			<th scope="col"><?php esc_html_e( 'Plugin', 'mammothwp-support' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Version', 'mammothwp-support' ); ?></th>
			<?php if ( $mmwp_show_vendor ) : ?>
				<th scope="col"><?php esc_html_e( 'Vendor', 'mammothwp-support' ); ?></th>
			<?php endif; ?>
			<th scope="col"><?php esc_html_e( 'Documentation', 'mammothwp-support' ); ?></th>
			<th scope="col"><?php esc_html_e( 'Support email set', 'mammothwp-support' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $view['plugins'] as $mmwp_plugin ) : ?>
			<tr>
				<td><?php echo esc_html( $mmwp_plugin->name ); ?></td>
				<td><?php echo esc_html( $mmwp_plugin->version ); ?></td>
				<?php if ( $mmwp_show_vendor ) : ?>
					<td><?php echo esc_html( $mmwp_plugin->vendor ); ?></td>
				<?php endif; ?>
				<td>
					<?php if ( '' !== $mmwp_plugin->docs_url ) : ?>
						<a href="<?php echo esc_url( $mmwp_plugin->docs_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Documentation', 'mammothwp-support' ); ?></a>
					<?php endif; ?>
				</td>
				<td><?php echo $mmwp_plugin->has_email() ? esc_html__( 'Yes', 'mammothwp-support' ) : esc_html__( 'No', 'mammothwp-support' ); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
