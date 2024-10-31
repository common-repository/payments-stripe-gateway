<?php defined( 'ABSPATH' ) or die( 'No script please!' );

class PWS_PAYMENTS {

	private $secret_key;
	private $customer_id;
	private $user_email;
	private $user_id;

	/**
	 * Constructor
	 **/
	public function __construct( ) {

		$this->secret_key = '';
		$this->load_stripe_lib();
		$this->get_secret_key();

		// Set our Stripe keys
		if ( $this->secret_key != '' ) {

		try {

			\Stripe\Stripe::setApiKey( $this->secret_key );

			} catch ( \Exception $e ) {
				$error_msg = __( 'Stripe API error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
				PWS_PAYMENTS::log( $error_msg );
			} catch ( \Throwable $e ) {
				$error_msg = __( 'Stripe API error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
				PWS_PAYMENTS::log( $error_msg );
			}
		}

		\Stripe\Stripe::setAppInfo(
			'WordPress Pay With Stripe',
			PAY_WITH_STRIPE_VERSION,
			'https://www.freshlightlab.com/contact-us',
			'pp_partner_JzXijpn4QgLKiF'
		);
	}

	/*
	 * Log errors and other data if necessary
	 */
	static public function log( $message, $type = 'error' ) {
		if ( $type == 'error' ) {
			error_log( $message);
		}
	}

	/*
	* Cancel Payment Intent
	*/
	public function cancel_payment_intent( $intent_id ){

		try {

			$intent = $this->get_payment_intent( $intent_id );
			$intent->cancel( [] );

		} catch ( \Exception $e ) {

			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );

			return false;
		} catch ( \Throwable $e ) {

			$error_msg = __( 'Stripe API error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );

			return false;
		}

		return $intent;

	}

	/*
	* Confirm PaymentIntent
	*/
	public function confirm_payment_intent( $payment_intent ) {

		try {
			// To create a PaymentIntent for confirmation, see our guide at: https://stripe.com/docs/payments/payment-intents/creating-payment-intents#creating-for-automatic
			$pm = \Stripe\PaymentIntent::retrieve(
				$payment_intent->id
			);
		} catch ( \Exception $e ) {
			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );

			return array(
				'error'         => true,
				'msg'           => $error_msg
			);
		} catch ( \Throwable $e ) {
			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );

			return array(
				'error'         => true,
				'msg'           => $error_msg
			);
		}

		try {
			$payment_method = $this->get_payment_method();
			$payment_method = $payment_method->id;

			$pm->confirm([
				'payment_method' => $payment_method,
				'receipt_email'  => $this->get_user_email()
				]);
		} catch ( \Exception $e ) {
			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );
			return array(
				'error'         => true,
				'msg'           => $error_msg
			);
		} catch ( \Throwable $e ) {
			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );

			return array(
				'error'         => true,
				'msg'           => $error_msg
			);
		}

	}

	/*
	* Create customer on Stripe
	*/
	public function create_customer() {

		$current_user  = wp_get_current_user();
		$user_id       = $current_user->ID;
		$full_name     = $current_user->user_firstname . ' ' . $current_user->user_lastname;

		if ( '' === trim( $full_name ) ) {
			$full_name = $current_user->display_name ;
		}

		$username                 = $current_user->user_login;
		$customer_params['name']  = $full_name;
		$customer_params['email'] = $current_user->user_email;

		try {
			$customer = \Stripe\Customer::create( $customer_params );
		} catch ( \Exception $e ) {
			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );
		} catch ( \Throwable $e ) {
			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );
		}

		// Let's save the Stripe customer id to use later.
		update_user_meta( $user_id, 'stripe_customer_id', $customer->id );
		$this->customer_id = $customer->id;

	}

	/*
	* Create Payment Intent
	*/
	public function create_payment_intent( $product_name, $description, $amount, $batch_id = 0, $force_error = false ) {

		try {

			$receipt_email  = $this->get_user_email();
			$currency       = 'USD';
			$payment_method = $this->get_payment_method();

			if ( $payment_method != null ) {
				$payment_method = $payment_method->id;
			}

			$customer_id    = $this->get_customer_id();
			if( $force_error ) {
				$customer_id = null;
			}
			$capture_method = 'manual';
			$confirm        = true;

			if ( $payment_method == '' ) {
				$customer_id    = null;
				$capture_method = 'automatic';
				$confirm        = false;
			}

			$pi_params = array(
				'amount'               => $amount,
				'currency'             => $currency,
				'payment_method_types' => ['card'],
				'payment_method'       => $payment_method,
				'customer'             => $customer_id,
				'capture_method'       => $capture_method,
				'confirm'              => $confirm,
			);

			$metadata['Product Name'] = $product_name;

			if ( isset( $metadata ) && ! empty( $metadata ) ) {
				$pi_params['metadata'] = $metadata;
			}

			$pi_params['description']   = $description;
			$pi_params['receipt_email'] = $receipt_email;

			// Create Stripe Intent of Payment.
			$intent = \Stripe\PaymentIntent::create( $pi_params );
			return $intent;

		} catch ( \Exception $e ) {
			$stripe_error = $e->getMessage();
			$error_msg = __( 'Stripe Error:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );
		} catch ( \Throwable $e ) {
			$stripe_error = $e->getMessage();
			$error_msg = __( 'Stripe Error:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );
		}

		// email admin with error
		$to = get_bloginfo( 'admin_email' );
		$subject = 'Stripe Error';
		$headers = array('Content-Type: text/html; charset=UTF-8');
		$body = '<br>There was a problem charging the customer ' . $receipt_email . '.<br><br>';
		$body .= 'Batch ID: ' . $batch_id . '<br>';
		$body .= 'Cost: $' . ( $amount / 100 ) . '<br><br>';
		$body .= 'Stripe Error: ' . $stripe_error;
		wp_mail( $to, $subject, $body, $headers );

		return false;
	}

	/*
	* Create Card Payment method
	*/
	public function create_payment_method_cart( $card_number, $card_exp_month, $card_exp_year, $card_cvc ) {

		try {

			$payment_method = \Stripe\PaymentMethod::create([
			'type'     => 'card',
			'card'     => [
					'number'    => $card_number,
					'exp_month' => $card_exp_month,
					'exp_year'  => $card_exp_year,
					'cvc'       => $card_cvc,
					],
				]);
		} catch ( \Exception $e ) {
			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );
		} catch ( \Throwable $e ) {
			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );
		}

		try {

			$pm = \Stripe\PaymentMethod::retrieve(
				$payment_method->id
			);
		} catch ( \Exception $e ) {
			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );
		} catch ( \Throwable $e ) {
			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );
		}

		try {
			$pm->attach([
				'customer' => $this->get_customer_id(),
			]);
		} catch ( \Exception $e ) {
			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );
		} catch ( \Throwable $e ) {
			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );
		}

	}

	/*
	* Create SetupIntent on Stripe
	*/
	public function create_setup_intent() {

		try {
			$setup_intent = \Stripe\SetupIntent::create([
				'payment_method_types' => ['card'],
				'customer'             => $this->get_customer_id(),
				'description'          => 'Skipmatrix Batch Service',
				'usage'                => 'off_session',
			  ]);
		} catch ( \Exception $e ) {
			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );
		} catch ( \Throwable $e ) {
			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );

		}

		return $setup_intent;

	}

	/*
	* Get customer ID
	*/
	public function get_customer_id() {

		if ( $this->customer_id == '' ) {

			$user_id = get_current_user_id();

			if ( $user_id == 0 ){
				$user_id = $this->user_id;
			}

			//$this->customer_id = get_user_meta( $user_id, 'stripe_customer_id', true );
			//$this->customer_id = 'cus_JBGV68g3Va5eZu';
			$this->customer_id = 'cus_JJTNMFDdQiBj8t';//info@freshlight.pt

		}

		return $this->customer_id;
	}

	/*
	* Get Customer Detaisl
	*/
	private function get_customer(){

		try {
			$customer = \Stripe\Customer::retrieve( $this->get_customer_id() );
		} catch ( \Exception $e ) {

			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );
			return '';

		} catch ( \Throwable $e ) {

			$error_msg = __( 'Stripe API error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );
			return '';

		}

		return $customer;
	}

	/*
	* Get Current Card
	*/
	public function get_current_card(){

		try {
			$customer = $this->get_customer();
		} catch ( \Exception $e ) {

			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );

			return false;
		} catch ( \Throwable $e ) {

			$error_msg = __( 'Stripe API error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );

			return false;
		}


	}

	/*
	* Get Payment Intent
	*/
	public function get_payment_intent( $intent_id ){

		try {

			$intent = \Stripe\PaymentIntent::retrieve(
				$intent_id, []
			);

		} catch ( \Exception $e ) {

			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );

			return false;
		} catch ( \Throwable $e ) {

			$error_msg = __( 'Stripe API error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );

			return false;
		}


		return $intent;

	}

	/*
	* Get Payment Method
	*/
	public function get_payment_method(){

		try {
			$payment_methods = \Stripe\PaymentMethod::all([
				'customer' => $this->get_customer_id(),
				'type'     => 'card',
				'limit'    => '1'
			  ]);
		} catch ( \Exception $e ) {

			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );

			return null;
		} catch ( \Throwable $e ) {

			$error_msg = __( 'Stripe API error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );

			return null;
		}

		$payment_method = null;

		foreach( $payment_methods as $payment_method ){
			$payment_method = $payment_method;
		}

		return $payment_method;

	}

	/*
	* Get List of Stripe prices of sepecific product
	*/
	public function get_stripe_prices( $product_id ) {

		try {
			$products = \Stripe\Price::all([
				'limit'    => '10',
				'product'  => $product_id
			  ]);
		} catch ( \Exception $e ) {

			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );

			return null;
		} catch ( \Throwable $e ) {

			$error_msg = __( 'Stripe API error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );

			return null;
		}

		return $products;

	}

	/*
	* Get List of products
	*/
	public function get_products_list(){

		try {
			$products = \Stripe\Product::all([
				'limit'    => '10'
			  ]);
		} catch ( \Exception $e ) {

			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );

			return null;
		} catch ( \Throwable $e ) {

			$error_msg = __( 'Stripe API error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );

			return null;
		}

		return $products;

	}

	/*
	* Get User Email
	*/
	private function get_user_email() {

		if ( $this->user_email == '' ) {

			$current_user     = wp_get_current_user();
			$this->user_email = $current_user->user_email ;
		}
		$this->user_email = "info@freshlight.pt";
		return $this->user_email;
	}

	/*
	* Get Secret Key
	*/
	private function get_secret_key(){
		//$test_mode = get_optiond( 'PWS_PAYMENTS-stripe_test_mode', 'options' );
        $test_mode = true;
		if ( $test_mode ) {
			$this->secret_key = get_option( 'pws_test_secret_key' );
		} else {
			$this->secret_key = get_option( 'pws_live_secret_key' );
		}

	}

	/*
	* Get Public Key
	*/
	public function get_public_key(){
		$public_key = '';
		//$test_mode = get_field( 'PWS_PAYMENTS-stripe_test_mode', 'options' );
		$test_mode = true;

		if ( $test_mode ) {
			$public_key = get_option( 'pws_test_publishable_key' );
		} else {
			$public_key= get_option( 'pws_live_publishable_key' );
		}

		return $public_key;
	}

	/*
	* Load Stripe Files
	*/
	private function load_stripe_lib() {
		if ( ! class_exists( '\Stripe\Stripe' ) ) {
			require_once PWS_PAYMENTS_DIR . '/vendor/stripe/init.php';
		} else {
			$declared = new \ReflectionClass( '\Stripe\Stripe' );
			$path     = $declared->getFileName();
			$own_path = PWS_PAYMENTS_DIR . '/vendor/stripe/lib/Stripe.php';
		}
	}

	/*
	* Set User id
	*/
	public function set_user_id( $user_id ) {
		$this->user_id = $user_id;
	}

	/*
	* Validate customer account on stripe
	*/
	public function validate_customer_account(){

		$customer_id = $this->get_customer_id();

		if ( '' === $customer_id || $customer_id == null ) {
			return false;
		}

		try {
			$customer = \Stripe\Customer::retrieve( $customer_id );
		} catch ( \Exception $e ) {

			$error_msg = __( 'Error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );

			return false;
		} catch ( \Throwable $e ) {

			$error_msg = __( 'Stripe API error occurred:', 'pay-with-stripe' ) . ' ' . $e->getMessage();
			PWS_PAYMENTS::log( $error_msg );

			return false;
		}

		$this->customer_id = $customer->id;

		return true;

	}
}
