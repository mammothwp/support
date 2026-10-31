<?php
/**
 * Wires the winning copy of the package.
 *
 * @package MammothWP\Support
 * @since   1.0.0
 */

namespace MammothWP\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use MammothWP\Support\Admin\Menu;
use MammothWP\Support\Admin\Post_Handler;
use MammothWP\Support\Admin\Screen;
use MammothWP\Support\Kit\Kit_Integration;
use MammothWP\Support\Kit\Operations;
use MammothWP\Support\Mail\Attachment_Writer;
use MammothWP\Support\Mail\Mail_Transport;
use MammothWP\Support\Mail\Rate_Limiter;
use MammothWP\Support\Mail\Send_Log;
use MammothWP\Support\Mail\Ticket_Service;
use MammothWP\Support\Mail\Transport_Interface;
use MammothWP\Support\Report\Report_Builder;
use MammothWP\Support\Report\Report_Service;
use MammothWP\Support\Report\Sections\Package_Section;
use MammothWP\Support\Report\Sections\Plugins_Section;
use MammothWP\Support\Report\Sections\Server_Section;
use MammothWP\Support\Report\Sections\Site_Section;
use MammothWP\Support\Report\Sections\Theme_Section;
use MammothWP\Support\Report\Sections\Woo_Section;

/**
 * Composition root of the winning copy. Boots once per request.
 *
 * @since 1.0.0
 */
final class Kernel {

	/**
	 * The booted kernel.
	 *
	 * @var Kernel|null
	 */
	private static ?Kernel $instance = null;

	/**
	 * Registry.
	 *
	 * @var Plugin_Registry
	 */
	private Plugin_Registry $registry;

	/**
	 * Plugin factory.
	 *
	 * @var Plugin_Factory
	 */
	private Plugin_Factory $factory;

	/**
	 * Site-owner settings.
	 *
	 * @var Site_Settings
	 */
	private Site_Settings $settings;

	/**
	 * Cached Mammoth WP icon data URI.
	 *
	 * @var string|null
	 */
	private ?string $icon = null;

	/**
	 * Loaded wp-kit version ('' when missing).
	 *
	 * @var string
	 */
	private string $kit_version = '';

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $version Booted package version.
	 * @param string $path    Booted package directory.
	 */
	private function __construct( private string $version, private string $path ) {
		$this->settings = new Site_Settings();
		$this->registry = new Plugin_Registry();
		$this->factory  = new Plugin_Factory( $this->settings );
	}

	/**
	 * Boot the winning copy: replay queued registrations and hook into WordPress.
	 *
	 * @since 1.0.0
	 *
	 * @param string $version Package version.
	 * @param string $path    Package directory.
	 * @return void
	 */
	public static function boot( string $version, string $path ): void {
		if ( null !== self::$instance ) {
			return;
		}
		self::$instance = new self( $version, $path );
		self::$instance->start();
	}

	/**
	 * Replay queued registrations.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public static function flush_queue(): void {
		if ( null === self::$instance ) {
			return;
		}
		$state                         = mmwp_support_state();
		$queue                         = $state['queue'];
		$state['queue']                = array();
		$GLOBALS['mmwp_support_state'] = $state;
		foreach ( $queue as $item ) {
			self::$instance->registry->add( self::$instance->factory->create( (string) $item[0], (array) $item[1] ) );
		}
	}

	/**
	 * Hook everything up.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function start(): void {
		self::flush_queue();
		$this->kit_version = Kit_Integration::loaded_version();

		add_action( 'init', array( $this, 'load_textdomain' ), 1 );

		$screen = $this->screen();
		( new Menu( $this->registry, array( $this, 'location' ), array( $this, 'branding' ), $screen ) )->hooks();
		$handler = new Post_Handler( $this->tickets(), fn(): string => admin_url( $this->location()->admin_path() ) );
		add_action( 'admin_post_' . Config::ADMIN_POST_ACTION, array( $handler, 'handle' ) );

		Kit_Integration::register_if_supported(
			$this->kit_version,
			function (): void {
				Kit_Integration::register_adapters(
					new Operations(
						$this->reports(),
						$this->registry,
						$this->tickets(),
						fn(): bool => (bool) $this->branding()->get( 'show_vendor_names' )
					)
				);
			}
		);

		/**
		 * Fires after the winning copy of the package has booted.
		 *
		 * @since 1.0.0
		 *
		 * @param string $version Booted package version.
		 * @param string $path    Directory of the booted copy.
		 */
		do_action( 'mmwp_support_booted', $this->version, $this->path );
	}

	/**
	 * Load the package translations from its own languages/ directory.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		$locale = determine_locale();
		$file   = $this->path . '/languages/' . Config::TEXT_DOMAIN . '-' . $locale . '.mo';
		if ( is_readable( $file ) ) {
			load_textdomain( Config::TEXT_DOMAIN, $file );
		}
	}

	/**
	 * Resolved menu location (filter > constant > default).
	 *
	 * @since 1.0.0
	 *
	 * @return Menu_Location
	 */
	public function location(): Menu_Location {
		$default = Menu_Resolver::resolve( $this->registry->has_mammoth(), null, null )->to_array()['location'];

		/**
		 * Filters the canonical Support screen location. Wins over the constant and the default.
		 *
		 * @since 1.0.0
		 *
		 * @param string|null $location Null (no override) or toplevel|tools|settings|hidden|parent:<menu-slug>.
		 * @param string      $default  The location the package would use without an override.
		 */
		$filtered = apply_filters( 'mmwp_support_menu', null, $default );
		return Menu_Resolver::resolve( $this->registry->has_mammoth(), $this->settings->menu(), is_string( $filtered ) ? $filtered : null );
	}

	/**
	 * Resolved branding.
	 *
	 * @since 1.0.0
	 *
	 * @return Branding
	 */
	public function branding(): Branding {
		$plugins = $this->registry->all();
		$hosts   = array_filter(
			array_map(
				static fn( string $url ): string => (string) wp_parse_url( $url, PHP_URL_HOST ),
				array( home_url(), site_url(), content_url(), admin_url() )
			)
		);
		$base    = Branding_Resolver::resolve( $plugins, $this->settings->whitelabel(), null, $hosts, $this->icon() );

		/**
		 * Filters the resolved branding values (site-owner white-label; wins over everything).
		 *
		 * Keys: name, menu_title, page_title, icon, logo_url (local URL only), subject_prefix,
		 * ref_prefix, show_powered_by, show_vendor_names.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string,mixed> $values  Resolved branding values.
		 * @param string              $mode    mammoth, neutral, vendor or whitelabel.
		 */
		$filtered = apply_filters( 'mmwp_support_branding', $base->to_array(), $base->mode );
		if ( ! is_array( $filtered ) || $filtered === $base->to_array() ) {
			return $base;
		}
		return Branding_Resolver::resolve( $plugins, $this->settings->whitelabel(), $filtered, $hosts, $this->icon() );
	}

	/**
	 * Mammoth WP menu icon as an SVG data URI.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function icon(): string {
		if ( null !== $this->icon ) {
			return $this->icon;
		}
		$icon = Menu_Icon::from_file( $this->path . '/assets/mmwp-icon.svg' );

		/**
		 * Filters the Mammoth WP menu icon (SVG data URI or dashicon name).
		 *
		 * Use a 20x20 viewBox, one path, `fill="black"` so WordPress recolours it to the admin scheme.
		 *
		 * @since 1.0.1
		 *
		 * @param string $icon Data URI or dashicon name.
		 */
		$icon       = apply_filters( 'mmwp_support_menu_icon', $icon );
		$this->icon = is_string( $icon ) && Branding_Guard::is_valid_icon( $icon ) ? $icon : Menu_Icon::FALLBACK;
		return $this->icon;
	}

	/**
	 * Report service.
	 *
	 * @since 1.0.0
	 *
	 * @return Report_Service
	 */
	public function reports(): Report_Service {
		$redactor = new Redactor(
			array(
				ABSPATH => '[ABSPATH]/',
				( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : '' ) => '[WP_CONTENT_DIR]/',
				( defined( 'WP_PLUGIN_DIR' ) ? WP_PLUGIN_DIR : '' ) => '[WP_PLUGIN_DIR]/',
			)
		);
		$builder  = function ( array $log_lines ) use ( $redactor ): array {
			$sections = array( new Site_Section(), new Server_Section(), new Theme_Section(), new Plugins_Section(), new Package_Section( $this->package_facts() ) );
			if ( Woo_Section::is_active() ) {
				$sections[] = new Woo_Section();
			}
			return ( new Report_Builder( $sections, $redactor ) )->build( $log_lines );
		};
		return new Report_Service( $builder, $redactor );
	}

	/**
	 * Facts for the "Support package" section (no paths).
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,mixed>
	 */
	private function package_facts(): array {
		$state    = mmwp_support_state();
		$plugins  = array_map( static fn( Plugin_Definition $p ): string => trim( $p->slug . ' ' . $p->version ), $this->registry->all() );
		$location = $this->location();
		return array(
			'registered_plugins' => $plugins,
			'booted_version'     => $this->version,
			'copies_found'       => array_values( $state['copies'] ),
			'menu_location'      => $location->to_array()['location'] . ' (' . $location->source . ')',
			'branding_mode'      => $this->branding()->mode,
			'wp_kit_version'     => '' === $this->kit_version ? 'not loaded' : $this->kit_version,
		);
	}

	/**
	 * Ticket service.
	 *
	 * @since 1.0.0
	 *
	 * @return Ticket_Service
	 */
	public function tickets(): Ticket_Service {
		$reports = $this->reports();
		return new Ticket_Service(
			$this->registry,
			array( $this, 'branding' ),
			array( $this, 'transport' ),
			static fn( bool $include_log ): array => $reports->build( $include_log ),
			new Rate_Limiter(),
			new Send_Log(),
			new Attachment_Writer()
		);
	}

	/**
	 * Transport (filterable seam).
	 *
	 * @since 1.0.0
	 *
	 * @return Transport_Interface
	 */
	public function transport(): Transport_Interface {
		/**
		 * Filters the mail transport. Only MailTransport (wp_mail) ships. Any transport that sends
		 * data off-site needs the same explicit user action as the built-in one.
		 *
		 * @since 1.0.0
		 *
		 * @param Transport_Interface $transport Transport.
		 */
		$transport = apply_filters( 'mmwp_support_transport', new Mail_Transport() );
		return $transport instanceof Transport_Interface ? $transport : new Mail_Transport();
	}

	/**
	 * Admin screen.
	 *
	 * @since 1.0.0
	 *
	 * @return Screen
	 */
	private function screen(): Screen {
		return new Screen(
			$this->registry,
			array( $this, 'branding' ),
			$this->reports(),
			new Send_Log(),
			$this->asset_base(),
			$this->version,
			$this->kit_version
		);
	}

	/**
	 * URL of assets/ for the booted copy ('' when it is outside the content tree).
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function asset_base(): string {
		return Asset_Url::for_path( $this->path . '/assets/' );
	}
}
