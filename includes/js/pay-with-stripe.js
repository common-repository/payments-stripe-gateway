jQuery( document ).ready(function() {

	jQuery( document ).on( 'click', '.pws-submit', function() {

		const publishableKey   = pwsConfig.stripeKey;
		const stripe_pws       = Stripe( publishableKey, { apiVersion: '2020-08-27' } );
		var mode               = jQuery(this).attr( 'data-mode' );
		var price              = jQuery(this).attr( 'data-price' );
		var successUrl         = jQuery(this).attr( 'data-success-url' )
		var cancelURL          = jQuery(this).attr( 'data-cancel-url' )
		var locale             = jQuery(this).attr( 'data-locale' )
		var clientReferenceID  = 'flabs-pay-with-stripe';

		if ( cancelURL == '' ){
			cancelURL = window.location.href;
		}

		stripe_pws.redirectToCheckout({
		lineItems: [{
					price: price,
					quantity: 1
				}],
			mode: mode,
			successUrl: successUrl,
			cancelUrl: cancelURL,
			clientReferenceId: clientReferenceID,
			locale: locale,
		})
		.then(function (result) {
			if (result.error) {
				console.error('PWS Error:' + result.error.message );
			}
		})
		.catch(function(error) {
			console.error('PWS Error:', error);
		});
	});
});