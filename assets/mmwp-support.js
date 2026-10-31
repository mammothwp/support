/**
 * Support screen progressive enhancement: send through the REST endpoint, copy report, recipient line.
 * Without JavaScript the form posts to admin-post.php and uses the same service.
 *
 * @package MammothWP\Support
 */
( function () {
	'use strict';

	var config = window.mmwpSupport;
	if ( ! config ) {
		return;
	}

	var form = document.getElementById( 'mmwp-support-form' );
	var notices = document.getElementById( 'mmwp-support-notices' );

	/**
	 * Show a core-style notice.
	 *
	 * @param {string} type    success or error.
	 * @param {string} message Text.
	 * @param {string} mailto  Optional mailto fallback URL.
	 * @return {void}
	 */
	function showNotice( type, message, mailto ) {
		var box = document.createElement( 'div' );
		var text = document.createElement( 'p' );
		box.className = 'notice notice-' + type;
		text.textContent = message;
		box.appendChild( text );
		if ( mailto ) {
			var link = document.createElement( 'a' );
			link.className = 'button';
			link.href = mailto;
			link.textContent = config.i18n.mailto;
			var para = document.createElement( 'p' );
			para.appendChild( link );
			box.appendChild( para );
		}
		notices.replaceChildren( box );
		box.scrollIntoView( { block: 'nearest' } );
	}

	/**
	 * Copy the text of an element to the clipboard.
	 *
	 * @param {string} selector Element selector.
	 * @return {void}
	 */
	function copyFrom( selector ) {
		var source = document.querySelector( selector );
		if ( ! source || ! navigator.clipboard ) {
			showNotice( 'error', config.i18n.copyFail );
			return;
		}
		navigator.clipboard.writeText( source.textContent ).then(
			function () {
				showNotice( 'success', config.i18n.copied );
			},
			function () {
				showNotice( 'error', config.i18n.copyFail );
			}
		);
	}

	/**
	 * Collect the form into the REST payload.
	 *
	 * @return {Object} Payload.
	 */
	function payload() {
		var data = new FormData( form );
		return {
			plugin: data.get( 'plugin' ) || '',
			subject: data.get( 'subject' ) || '',
			message: data.get( 'message' ) || '',
			name: data.get( 'name' ) || '',
			reply_to: data.get( 'reply_to' ) || '',
			include_report: data.get( 'include_report' ) === '1',
			include_log: data.get( 'include_log' ) === '1'
		};
	}

	/**
	 * Submit through fetch.
	 *
	 * @param {Event} event Submit event.
	 * @return {void}
	 */
	function onSubmit( event ) {
		var submit = document.getElementById( 'mmwp-support-submit' );
		event.preventDefault();
		submit.disabled = true;
		showNotice( 'info', config.i18n.sending );

		fetch( config.sendUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce },
			body: JSON.stringify( payload() )
		} )
			.then( function ( response ) {
				return response.json().then( function ( body ) {
					return { ok: response.ok, body: body };
				} );
			} )
			.then( function ( result ) {
				if ( result.ok ) {
					showNotice( 'success', config.i18n.sent + ' ' + result.body.ref );
					return;
				}
				var data = result.body.data || {};
				showNotice( 'error', result.body.message || config.i18n.failed, data.mailto );
			} )
			.catch( function () {
				showNotice( 'error', config.i18n.failed );
			} )
			.then( function () {
				submit.disabled = false;
			} );
	}

	/**
	 * Update the recipient line and the Send button when the plugin changes.
	 *
	 * @param {Event} event Change event.
	 * @return {void}
	 */
	function onPluginChange( event ) {
		var option = event.target.selectedOptions[ 0 ];
		var line = document.getElementById( 'mmwp-support-recipient' );
		var submit = document.getElementById( 'mmwp-support-submit' );
		var recipient = option ? option.getAttribute( 'data-recipient' ) : '';
		line.textContent = recipient ? config.i18n.sendTo.replace( '%s', recipient ) : ( option && option.value !== '' ? config.i18n.noEmail : '' );
		submit.disabled = !! option && option.value !== '' && ! recipient;
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-mmwp-copy]' );
		if ( button ) {
			copyFrom( button.getAttribute( 'data-mmwp-copy' ) );
		}
	} );

	if ( form ) {
		form.addEventListener( 'submit', onSubmit );
		form.querySelector( '#mmwp-support-plugin' ).addEventListener( 'change', onPluginChange );
	}
}() );
