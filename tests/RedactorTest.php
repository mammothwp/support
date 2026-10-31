<?php
/**
 * Redaction pass.
 *
 * @package MammothWP\Support
 */

namespace MammothWP\Support\Tests;

use MammothWP\Support\Redactor;

final class RedactorTest extends TestCase {

	/**
	 * Redactor with typical paths.
	 *
	 * @return Redactor
	 */
	private function redactor(): Redactor {
		return new Redactor(
			array(
				'/var/www/site/web/wp/'  => '[ABSPATH]/',
				'/var/www/site/web/app/' => '[WP_CONTENT_DIR]/',
			)
		);
	}

	public function test_absolute_paths_are_stripped(): void {
		$out = $this->redactor()->redact( 'Error in /var/www/site/web/wp/wp-includes/x.php and /var/www/site/web/app/plugins/a/b.php and /home/deploy/.ssh/id' );
		$this->assertStringNotContainsString( '/var/www', $out );
		$this->assertStringContainsString( '[ABSPATH]/wp-includes/x.php', $out );
		$this->assertStringContainsString( '[WP_CONTENT_DIR]/plugins/a/b.php', $out );
		$this->assertStringNotContainsString( 'deploy', $out );
	}

	public function test_emails_and_ips_are_removed(): void {
		$out = $this->redactor()->redact( 'user a.b+c@example.co.uk from 203.0.113.9 and 2001:db8::ff00:42:8329 ok' );
		$this->assertSame( 'user [email] from [ip] and [ip] ok', $out );
	}

	public function test_secrets_by_key_name_are_redacted(): void {
		$cases = array(
			"DB_PASSWORD = 'hunter2'",
			'define("AUTH_KEY", "x") api_key=abcDEF123',
			'"client_secret": "s3cr3t-value"',
			'token: abc123',
			'Authorization: Bearer abcdef.ghijkl-123',
		);
		foreach ( $cases as $case ) {
			$out = $this->redactor()->redact( $case );
			foreach ( array( 'hunter2', 'abcDEF123', 's3cr3t-value', 'abc123', 'abcdef.ghijkl-123' ) as $secret ) {
				$this->assertStringNotContainsString( $secret, $out, $case );
			}
		}
	}

	/**
	 * Dummy Stripe-style key; assembled at runtime so secret scanners don't flag the fixture.
	 *
	 * @return string
	 */
	private static function fake_stripe_key(): string {
		return implode( '', array( 'sk_', 'live_', '4eC39HqLyjWDarjtT1zdp7dc', '9a8b7c6d' ) );
	}

	public function test_url_credentials_jwt_and_long_tokens_are_removed(): void {
		// Key-shaped values are assembled at runtime: see tests/README.md.
		$jwt    = implode( '.', array( implode( '', array( 'eyJ', 'hbGciOiJIUzI1NiJ9' ) ), implode( '', array( 'eyJ', 'zdWIiOiIxMjM0NTY3ODkwIn0' ) ), 'sig' ) );
		$stripe = self::fake_stripe_key();
		$out    = $this->redactor()->redact( "https://admin:pw@db.example.org/x $jwt $stripe" );
		$this->assertStringNotContainsString( 'admin:pw', $out );
		$this->assertStringNotContainsString( $jwt, $out );
		$this->assertStringNotContainsString( '4eC39HqLyjWDarjtT1zdp7dc', $out );
	}

	public function test_versions_names_and_plain_words_are_kept(): void {
		$text = 'PHP 8.3.6, MariaDB 10.11.6, WooCommerce 9.1.0, Acme Widgets 2.3.4, a-very-long-plugin-name-without-any-digits-at-all';
		$this->assertSame( $text, $this->redactor()->redact( $text ) );
	}

	public function test_redact_deep_covers_nested_structures_and_keeps_keys(): void {
		$out = $this->redactor()->redact_deep(
			array(
				'a'    => array(
					'b' => 'me@x.org',
					'n' => 5,
				),
				'list' => array( '10.0.0.1' ),
			)
		);
		$this->assertSame(
			array(
				'a'    => array(
					'b' => '[email]',
					'n' => 5,
				),
				'list' => array( '[ip]' ),
			),
			$out
		);
	}
}
