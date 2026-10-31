<?php
/**
 * Support screen: wrapper.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 *
 * @var array<string,mixed> $view View model.
 */

use MammothWP\Support\Admin\View;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap mmwp-support">
	<?php View::render( 'header', $view ); ?>
	<h1><?php echo esc_html( (string) $view['branding']->get( 'page_title' ) ); ?></h1>
	<div id="mmwp-support-notices" aria-live="polite">
		<?php View::render( 'notices', $view ); ?>
	</div>
	<?php View::render( 'plugins', $view ); ?>
	<?php View::render( 'form', $view ); ?>
	<?php View::render( 'log', $view ); ?>
	<?php View::render( 'footer', $view ); ?>
</div>
