<?php
/**
 * Plugin Name: Pay With Stripe
 * Description: Sell your products with Stripe in 5 minutes. Using the Stripe checkout and Stripe checkout was never easier.
 * Version: 1.2.1
 * Author: Freshlight Lab
 * Tested up to: 5.9
 * Text Domain: flab-pwstripe
 * Domain Path: /languages/
 * License: GPLv2
 *
 */

 if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'PAY_WITH_STRIPE_VERSION', '1.2.1' );
define( 'PAY_WITH_STRIPE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'PAY_WITH_STRIPE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PWS_PAYMENTS_DIR', dirname( __FILE__ ) );

if ( ! function_exists( 'pws_fs' ) ) {
	// Create a helper function for easy SDK access.
	function pws_fs() {
		global $pws_fs;

		if ( ! isset( $pws_fs ) ) {
			// Activate multisite network integration.
			if ( ! defined( 'WP_FS__PRODUCT_8498_MULTISITE' ) ) {
				define( 'WP_FS__PRODUCT_8498_MULTISITE', true );
			}

			// Include Freemius SDK.
			require_once dirname(__FILE__) . '/freemius/start.php';

			$pws_fs = fs_dynamic_init( array(
                'id'                  => '8498',
                'slug'                => 'payments-stripe-gateway',
                'type'                => 'plugin',
                'public_key'          => 'pk_c09f03949c3efba9dc87cce85230a',
                'premium_suffix'      => 'Professional',
                // If your plugin is a serviceware, set this option to false.
				'is_premium'          => false,
                'has_premium_version' => true,
                'has_affiliation'     => 'selected',
				'has_addons'          => false,
                'has_paid_plans'      => true,
                'trial'               => array(
                    'days'               => 7,
                    'is_require_payment' => true,
                ),
                'menu'                => array(
                    'slug'           => 'pay-with-stripe-options',
                ),
				'is_live'        => true,
            ) );
		}

		return $pws_fs;
	}

	// Init Freemius.
	pws_fs();
	// Signal that SDK was initiated.
	do_action( 'pws_fs_loaded' );
}

// Require Pay With Stripe Core functions.
require_once dirname( __FILE__ ) . '/includes/pwstripe-core.php';
// Require Stripe Manager functions.
require_once dirname( __FILE__ ) . '/includes/pwstripe-manager.php';
// Require Stripe Products.
require_once dirname( __FILE__ ) . '/includes/pwstripe-products.php';
// Require options logic.
require_once dirname( __FILE__ ) . '/includes/pwstripe-options.php';

// Initialize the Pay With Stripe Core.
$pws_core = new PWS_Core();
$pws_core->init();

// Initialize the Pay With Stripe options.
new Pay_With_Stripe_Options();

// Initialize the Pay With Stripe Products.
global $pws_product;

$pws_product = new PWS_Stripe_Product();
$pws_product->init();

// Schedule the sync action to be done hourly via WP-Cron.
register_activation_hook( __FILE__, 'pwstripe_products_setup_schedule' );
// Remove Schedule on deactivation.
register_deactivation_hook(__FILE__, 'pwstripe_products_clear_schedule' );

function pwstripe_sync_products(){
	$pws_product = new PWS_Stripe_Product();
	$pws_product->pws_get_products();
}

function pwstripe_products_setup_schedule(){
	if ( ! wp_next_scheduled( 'pwstripe_sync_products' ) ) {
		wp_schedule_event( time(), 'hourly', 'pwstripe_sync_products' );
	}
}

function pwstripe_products_clear_schedule(){
	wp_clear_scheduled_hook( 'pwstripe_sync_products' );
}
add_action( 'pwstripe_sync_products', 'pwstripe_sync_products' );