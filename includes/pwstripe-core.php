<?php defined( 'ABSPATH' ) or die( 'No script please!' );

class PWS_CORE {

	/**
	 * Constructor
	 **/
	public function __construct( ) {

	}

	/**
	 *
	 * Init function of the class.
	 *
	 * @since 1.0.1
	 */
	public function init() {

		// Add hooks and filters.
		add_action( 'wp_enqueue_scripts', array( $this, 'pay_with_stripe_scripts' ) );
		// Add Stripe Button Shortcode.
		add_shortcode( 'pwstripe_button', array( $this, 'pws_stripe_checkout' ) );
		// Enqueue Admin scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'pwstripe_admin_enqueue_scripts' ) );
		// Register Elementor Widget.
		add_action( 'elementor/widgets/widgets_registered',  array( $this, 'pwstripe_init_widgets' ) );
	}

	/**
	 *
	 * Pay With Stripe Scripts.
	 *
	 * @since 1.0.1
	 */
	public function pay_with_stripe_scripts() { 

		wp_register_script( 'pws-stripe', 'https://js.stripe.com/v3/', '', '3.0', true );
		wp_register_script( 'pws-stripe-js', plugins_url( 'js/pay-with-stripe.js',  __FILE__ ), array( 'jquery' ), PAY_WITH_STRIPE_VERSION );

		$success_url     = get_option( 'pws_success_url', get_site_url() );
		$cancel_url      = get_option( 'pws_cancel_url', get_site_url() );
		$pws_stripe_mode = get_option( 'pws_stripe_mode', 'test_mode' );
		$stripe_key      = '';

		// Check if it is the Live or the Test mode.
		if ( $pws_stripe_mode === 'live_mode' ) {
			$stripe_key = get_option( 'pws_live_publishable_key' );
		} else {
			$stripe_key = get_option( 'pws_test_publishable_key' );
		}

		wp_localize_script( 'pws-stripe-js', 'pwsConfig', array( 
			'ajaxurl'    => admin_url( 'admin-ajax.php' ),
			'stripeKey'  => $stripe_key,
			) );

	}

	/**
	 *
	 * Pay With Stripe Checkout.
	 *
	 * @since 1.0.1
	 */
	public function pws_stripe_checkout( $atts ) {

		// Enqueue the scripts only when the shortcode is executed.
		wp_enqueue_script( 'pws-stripe' );
		wp_enqueue_script( 'pws-stripe-js' );

		if ( ! empty( $atts ) ) {
			$atts = array_map( 'sanitize_text_field', $atts );
		}

		$button_text = __( 'Buy Now', 'flab-pwstripe' );
		$mode        = 'payment';
		$locale      = 'auto';
		$price_id    = '0';
		$success_url = '';
		$cancel_url  = '';
		$btn_radius  = '0px';
		$text_color  = '';
	

		// Button Text.
		if ( isset( $atts['text'] ) && ! empty( $atts['text'] ) ) {
			$button_text = $atts['text'];
		}

		// Price Identifier.
		if ( isset( $atts['price'] ) || ! empty( $atts['price'] ) ) {
			$price_id = $atts['price'];
		}


		$price_id = ' data-price="' . $price_id . '"';

		// Success URL.
		if ( ! isset( $atts['success_url'] ) || empty( $atts['success_url'] ) ) {
			$success_url = get_option( 'pws_success_url', get_site_url() );
		} else {
			$success_url = $atts['success_url'];
		}

		$success_url = ' data-success-url="' . $success_url . '"';

		// Cancel URL.
		if ( ! isset( $atts['cancel_url'] ) || empty( $atts['cancel_url'] ) ) {
			$cancel_url = get_option( 'pws_cancels_url', '' );
		} else {
			$cancel_url = $atts['cancel_url'];
		}

		$cancel_url = ' data-cancel-url="' . $cancel_url . '"';

		// Payment mode ( 1 time or recurring).
		if ( isset( $atts['mode'] ) && 'subscription' == $atts['mode'] ) {
			$mode = 'subscription';
		}

		$mode = ' data-mode="' . $mode . '"';

		// Locale currency.
		if ( isset( $atts['locale'] ) && ! empty( $atts['locale'] ) ) {
			$locale = $atts['locale'];
		}

		$locale = ' data-locale="' . $locale . '"';

		// Button Color.
		if ( isset( $atts['button_color'] ) || ! empty( $atts['button_color'] ) ) {
			$btn_color = $atts['button_color'];
		}

		// Text Color.
		if ( isset( $atts['text_color'] ) || ! empty( $atts['text_color'] ) ) {
			$text_color = $atts['text_color'];
		}

		// Button Font.
		if ( isset( $atts['font'] ) || ! empty( $atts['font'] ) ) {
			$btn_font = $atts['font'];
		}

		// Button Font.
		if ( isset( $atts['button_radius'] ) || ! empty( $atts['button_radius'] ) ) {
			$btn_radius = $atts['button_radius'] . 'px';
		}

		// Button Font size.
		if ( isset( $atts['button_size'] ) || ! empty( $atts['button_size'] ) ) {
			$button_size = $atts['button_size'] . 'px';
		}
	
		// Button Padding.
		if ( isset( $atts['padding'] ) || ! empty( $atts['padding'] ) ) {
			$button_padding = $atts['padding'];
		}

		$client_ref_id = 'flab-pws-' . uniqid();
		$button_code   = '<span title="' . __( $button_text, 'flab-pwstripe' ) . '" class="pws-submit" ' . $price_id . $mode . $success_url . $cancel_url . $locale . '" ';
		$button_code  .= 'style="cursor:pointer;font-size:' . $button_size . ';border-radius:' . $btn_radius . ';font-family:' . $btn_font . ';color:' . $text_color . ';background-color:' . $btn_color . ';">' . $button_text . '</span>';

		return $button_code;
	}

	/**
	 *
	 * Enqueue admin scripts.
	 *
	 * @since 1.0.1
	 */
	public function pwstripe_admin_enqueue_scripts() {

		global $hook;
		global $typenow;
	
		if ( ( 'post.php' === $hook && $typenow == 'stripe_product' ) || 'toplevel_page_pay-with-stripe-options' === $hook ) {
			wp_register_script( 'pwstripe-admin-js', plugins_url( 'includes/js/pay-with-stripe-admin.js', __FILE__ ), array( 'jquery' ), PAY_WITH_STRIPE_VERSION );
			wp_enqueue_script( 'pwstripe-admin-js' );
		}
	
	}

	/**
	 *
	 * Register Pay With Stripe Elementor Widget.
	 *
	 * @since 1.0.1
	 */
	public function pwstripe_init_widgets() {

			// Include Widget files
			require_once( __DIR__ . '/widgets/stripe-button.php' );

			// Register widget
			\Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \Stripe_Button_Widget() );

	}

}
