<?php
/**
 * Consent, subject/ref formatting, rate limit, failure fallback.
 *
 * @package MammothWP\Support
 */

namespace MammothWP\Support\Tests;

use Brain\Monkey\Filters;
use MammothWP\Support\Branding;
use MammothWP\Support\Branding_Resolver;
use MammothWP\Support\Mail\Ticket_Formatter;
use MammothWP\Support\Mail\Ticket_Request;

final class TicketServiceTest extends ServiceTestCase {

	public function test_the_report_is_not_built_or_sent_unless_the_box_is_ticked(): void {
		$result = $this->service()->send( $this->request() );
		$this->assertTrue( $result['sent'] );
		$this->assertSame( 0, $this->report_calls, 'No report is even built without consent.' );
		$this->assertCount( 1, $this->sent );
		$this->assertStringNotContainsString( '== Site ==', $this->sent[0]->body );
		$this->assertSame( array(), $this->sent[0]->attachments );
		$this->assertStringContainsString( 'Report: not included', $this->sent[0]->body );
	}

	public function test_an_unticked_checkbox_in_raw_input_means_no_report(): void {
		foreach ( array( '', '0', 'false', 'no', false, null ) as $value ) {
			$this->assertFalse( Ticket_Request::truthy( $value ), var_export( $value, true ) );
		}
		$this->assertTrue( Ticket_Request::truthy( '1' ) );
		$this->assertFalse( $this->request()->include_report );
	}

	public function test_with_consent_the_report_is_appended_and_attached_then_cleaned_up(): void {
		$this->service()->send( $this->request( array( 'include_report' => '1' ) ) );
		$this->assertSame( 1, $this->report_calls );
		$this->assertStringContainsString( "== Site ==\nwp_version: 6.8", $this->sent[0]->body );
		$this->assertCount( 1, $this->attachments_seen );
		$this->assertSame( 'site-report-shop.example.org-2024-05-06.json', $this->attachments_seen[0]['name'] );
		$this->assertTrue( $this->attachments_seen[0]['exists'] );
		$this->assertSame( '{"site":{"wp_version":"6.8"}}', $this->attachments_seen[0]['contents'] );
		$this->assertFileDoesNotExist( $this->attachments_seen[0]['path'] );
	}

	public function test_attachment_can_be_filtered_off(): void {
		Filters\expectApplied( 'mmwp_support_attach_report' )->andReturn( false );
		$this->service()->send( $this->request( array( 'include_report' => '1' ) ) );
		$this->assertSame( array(), $this->sent[0]->attachments );
		$this->assertStringContainsString( '== Site ==', $this->sent[0]->body );
	}

	public function test_headers_recipient_and_neutral_subject_with_ref(): void {
		$this->service()->send( $this->request() );
		$message = $this->sent[0];
		$this->assertSame( 'help@acme.test', $message->to );
		$this->assertSame( array( 'Reply-To: Owner <owner@example.org>' ), $message->headers, 'Reply-To only: From stays the site default.' );
		$this->assertMatchesRegularExpression( '/^\[Support\]\[acme\] Checkout fails \(SUP-20240506-[0-9A-F]{6}\)$/', $message->subject );
		$this->assertStringStartsWith( 'It does not work.', $message->body );
	}

	public function test_mammoth_branding_gives_mammoth_prefix_and_ref(): void {
		$this->branding = new Branding( Branding_Resolver::mammoth( 'dashicons-sos' ), 'mammoth' );
		$result         = $this->service()->send( $this->request() );
		$this->assertMatchesRegularExpression( '/^MMWP-20240506-[0-9A-F]{6}$/', $result['ref'] );
		$this->assertStringStartsWith( '[Mammoth WP][acme] Checkout fails (MMWP-', $this->sent[0]->subject );
	}

	public function test_whitelabel_branding_removes_every_mammoth_trace_from_the_email(): void {
		$this->registry->add(
			$this->definition(
				array(
					'slug'   => 'm',
					'vendor' => 'Mammoth WP',
					'name'   => 'Share',
				)
			)
		);
		$this->branding = Branding_Resolver::resolve( $this->registry->all(), true, null, array(), 'dashicons-sos' );
		$result         = $this->service()->send( $this->request( array( 'plugin' => 'm' ) ) );
		$this->assertStringStartsWith( 'SUP-', $result['ref'] );
		$this->assertStringNotContainsString( 'Mammoth', $this->sent[0]->subject );
		$this->assertStringStartsWith( '[Support][m]', $this->sent[0]->subject );
	}

	public function test_subject_is_filterable_and_never_multiline(): void {
		$this->assertSame( '[X][a] one two (R-1)', Ticket_Formatter::subject( '[X]', 'a', "one\r\ntwo", 'R-1' ) );
		$this->assertStringNotContainsString( "\n", Ticket_Formatter::subject( '[X]', 'a', "bad\nBcc: x@y.z", 'R' ) );
		$this->assertSame( 'ABCDEF', substr( Ticket_Formatter::ref( 'SUP', self::NOW, 'abcdefgh' ), -6 ) );
	}

	public function test_a_plugin_without_a_valid_email_cannot_send(): void {
		$this->registry->add(
			$this->definition(
				array(
					'slug'  => 'noemail',
					'email' => '',
				)
			)
		);
		$result = $this->service()->send( $this->request( array( 'plugin' => 'noemail' ) ) );
		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'mmwp_support_no_email', $result->get_error_code() );
		$this->assertSame( array(), $this->sent );
	}

	public function test_validation_errors(): void {
		$cases = array(
			'mmwp_support_unknown_plugin'   => array( 'plugin' => 'ghost' ),
			'mmwp_support_empty_message'    => array( 'message' => '   ' ),
			'mmwp_support_invalid_reply_to' => array( 'reply_to' => 'nope' ),
		);
		foreach ( $cases as $code => $override ) {
			$result = $this->service()->send( $this->request( $override ) );
			$this->assertSame( $code, $result->get_error_code() );
		}
		$this->assertSame( array(), $this->sent );
	}

	public function test_rate_limit_allows_three_per_window_then_blocks_then_recovers(): void {
		$service = $this->service();
		for ( $i = 0; $i < 3; $i++ ) {
			$this->assertTrue( $service->send( $this->request() )['sent'] );
		}
		$blocked = $service->send( $this->request() );
		$this->assertSame( 'mmwp_support_rate_limited', $blocked->get_error_code() );
		$this->assertSame( 429, $blocked->get_error_data()['status'] );
		$this->assertCount( 3, $this->sent );

		$this->now += 601;
		$this->assertTrue( $service->send( $this->request() )['sent'] );
	}

	public function test_rate_limit_is_filterable(): void {
		Filters\expectApplied( 'mmwp_support_rate_limit' )->andReturn(
			array(
				'max'    => 1,
				'window' => 60,
			)
		);
		$service = $this->service();
		$this->assertTrue( $service->send( $this->request() )['sent'] );
		$this->assertSame( 'mmwp_support_rate_limited', $service->send( $this->request() )->get_error_code() );
	}

	public function test_dry_run_returns_the_email_without_sending_or_counting(): void {
		$service = $this->service();
		for ( $i = 0; $i < 5; $i++ ) {
			$result = $service->send( $this->request( array( 'include_report' => '1' ) ), true );
		}
		$this->assertTrue( $result['dry_run'] );
		$this->assertFalse( $result['sent'] );
		$this->assertSame( 'help@acme.test', $result['to'] );
		$this->assertStringContainsString( '== Site ==', $result['body'] );
		$this->assertSame( 'site-report-shop.example.org-2024-05-06.json', $result['attachment'] );
		$this->assertSame( array(), $this->sent );
		$this->assertSame( array(), $this->store['o_mmwp_support_log'] ?? array(), 'Dry runs are not logged.' );
	}

	public function test_mail_failure_returns_mailto_fallback_and_logs_the_failure(): void {
		$this->transport_result = false;
		$result                 = $this->service()->send( $this->request() );
		$this->assertSame( 'mmwp_support_mail_failed', $result->get_error_code() );
		$data = $result->get_error_data();
		$this->assertStringStartsWith( 'mailto:help%40acme.test?subject=', $data['mailto'] );
		$this->assertStringContainsString( rawurlencode( 'It does not work.' ), $data['mailto'] );
		$this->assertMatchesRegularExpression( '/^SUP-20240506-/', $data['ref'] );
		$this->assertSame( 'failed', $this->store['o_mmwp_support_log'][0]['result'] );
	}

	public function test_send_log_keeps_the_last_twenty_without_contents(): void {
		$service = $this->service();
		Filters\expectApplied( 'mmwp_support_rate_limit' )->andReturn(
			array(
				'max'    => 100,
				'window' => 60,
			)
		);
		for ( $i = 0; $i < 25; $i++ ) {
			$this->now = self::NOW + $i;
			$service->send( $this->request() );
		}
		$log = $this->store['o_mmwp_support_log'];
		$this->assertCount( 20, $log );
		$this->assertSame( self::NOW + 24, $log[0]['time'] );
		$this->assertSame( array( 'time', 'plugin', 'ref', 'result' ), array_keys( $log[0] ) );
	}
}
