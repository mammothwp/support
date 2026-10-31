<?php
/**
 * Support screen: small header (mark + name). The only branded element on the screen.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 *
 * @var array<string,mixed> $view View model.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mmwp_branding = $view['branding'];
$mmwp_logo     = (string) $mmwp_branding->get( 'logo_url' );
$mmwp_icon     = (string) $mmwp_branding->get( 'icon' );
?>
<div class="mmwp-support__header">
	<?php if ( '' !== $mmwp_logo ) : ?>
		<img class="mmwp-support__mark" src="<?php echo esc_url( $mmwp_logo ); ?>" alt="" width="20" height="20" />
	<?php elseif ( 0 === strpos( $mmwp_icon, 'data:image/svg+xml' ) ) : ?>
		<img class="mmwp-support__mark" src="<?php echo esc_attr( $mmwp_icon ); ?>" alt="" width="20" height="20" />
	<?php else : ?>
		<span class="dashicons <?php echo esc_attr( $mmwp_icon ); ?>" aria-hidden="true"></span>
	<?php endif; ?>
	<span class="mmwp-support__name"><?php echo esc_html( (string) $mmwp_branding->get( 'name' ) ); ?></span>
</div>
