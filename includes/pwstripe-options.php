<?php
/**
 * Payments Stripe Gateway Options
 *
 * This will manage the plugin options.
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
 *
 */

if ( ! class_exists( 'Pay_With_Stripe_Options' ) ) {
	die();
}
/**
 *
 * Class Pay_With_Stripe_Options.
 *
 */
class Pay_With_Stripe_Options {

	/**
	 *
	 * Class Constructor.
	 *
	 */
	public function __construct() {

		//Add new admin menu options page for the Payments Stripe Gateway.
		add_action( 'admin_menu', array( $this, 'create_pay_with_stripe_options_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'pws_load_google_font' ) );

	}
	
	public function pws_load_google_font(){
		wp_enqueue_style( 'pws-googlefonts', 'https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@100;200;300&display=swap', array(), null );
	}

	// Create the Payments Stripe Gateway options page.
	public function create_pay_with_stripe_options_page() {
		add_menu_page(
			'Pay With Stripe',
			'Pay With Stripe',
			'manage_options',
			'pay-with-stripe-options',
			array( $this, 'pay_with_stripe_options_page_html' ),
			'dashicons-money-alt',
			150
		);
	}

	public function pay_with_stripe_options_page_html(){

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check if we are updating the options.
		if ( isset( $_POST['updated'] ) && 'true' === $_POST['updated'] ) {
			$this->handle_options_form();
		}

		$pws_stripe_mode = get_option( 'pws_stripe_mode', 'test_mode' );

		?>
		<script text="javascript">

			jQuery( document ).ready(function(){

				// Expand/colapse product line.
				jQuery( document ).on( 'click', '.pws-product-line', function(){
						var prodID = jQuery(this).attr('data-prod-id');
						jQuery(this).toggleClass( 'active' );
						jQuery('.data-prod-id-' + prodID).toggleClass( 'active' );
				});

				// Click to copy.
				jQuery( document ).on( 'click', '.pws-click-copy', function(e) {
					e.preventDefault();
					var copyText = jQuery( this ).prev();
					copyText.select();
					//copyText.setSelectionRange(0, 99999); /* For mobile devices */
					document.execCommand("copy");
					return false;
				});

				// Settings menu.
				jQuery( document ).on( 'click', '.pws-nav-wrapper h2', function(){
					jQuery( '.pws-nav-wrapper h2' ).removeClass( 'active' );
					jQuery( this ).addClass( 'active' );
					jQuery('.pws-sub-screen').removeClass( 'active' );
					jQuery( '.' + jQuery( this ).attr( 'data-sub-menu' ) ).addClass( 'active' );
					
					if ( jQuery( this ).attr( 'data-sub-menu' ) == 'subscription-sub' ) {
						jQuery( '.pay-with-stripe-settings-wrapper .submit' ).hide();
					} else {
						jQuery( '.pay-with-stripe-settings-wrapper .submit' ).show();
					}
					
				});

			});

		</script>

		<style>
			.pay-with-stripe-settings-header .fs-notice {
				margin-top: 90px!important;
				margin-right: 20px!important;
			}
			.pay-with-stripe-settings-wrapper {
				background: #fff;
				padding: 40px;
				font-family: 'Kumbh Sans';
			}
			.pay-with-stripe-settings-wrapper h2 {
				color: #094c68;
				border: 1px solid #DDD;
				padding: 10px;
				background: #fbfbfb;
				font-weight: 400;
			}
			.pay-with-stripe-settings-wrapper .pws-header {
				padding: 10px 20px;
				background: #ebeff3;
				color: #77787f;
				text-align: center;
			}
			.pay-with-stripe-settings-wrapper .form-table th {
				font-weight: 500;
				color: #666;
				font-family: "Kumbh Sans";
			}
			.pay-with-stripe-settings-wrapper span.helper {
				padding-left: 10px;
			}
			span.pay-with-stripe-settings-h1 {
				padding: 15px;
				float: left;
				font-size: 2em;
				color: #fff;
			}
			.pay-with-stripe-logo-admin {
				float: left;
				width: 280px;
			}
			.pay-with-stripe-settings-header {
				padding: 20px 0px 20px 20px;
				background: #FFF;
				margin-top:20px;
				height: 60px;
			}
			.pay-with-stripe-support-icon {
				margin-top: 25px;
				right: 70px;
				position: absolute;
				color: #50575e;
			}
			.pay-with-stripe-support-icon a, .pay-with-stripe-doc-icon a  {
				text-decoration: none;
				color: #50575e;
			}
			.pay-with-stripe-doc-icon {
				color: #8751ca;
				margin-top: 25px;
				right: 170px;
				position: absolute;
			}
			.pay-with-stripe-doc-icon a:hover, .pay-with-stripe-support-icon a:hover {
				color: #929292;
			}
			.toplevel_page_pay-with-stripe-options .wrap {
				display: grid;
			}
			.pay-with-stripe-settings-wrapper h2 {
				color: #094c68;
				border: 1px solid #ddd;
				padding: 10px;
				background: #fbfbfb;
				font-weight: 400;
			}
			.pws-welcome-box {
				background: #f7f7f7;
				min-height: 150px;
				text-align: center;
				padding-bottom: 50px;
			}
			#pws_stripe_mode {
				min-width: 390px;
			}
			.pws-welcome-box .intro-text {
				font-size: 15px;
				font-weight: 300;
				width: 60%;
				display: block;
				margin: auto;
				padding-bottom: 30px;
			}
			.pws-welcome-box img{
				width: 220px;
				padding: 5%;
			}
			.pws-welcome-box h3 {
				padding-top: 60px;
				padding-left: 50px;
				font-size: 30px;
				font-weight: 300;
			}
			.pws-button {
				font-size: 18px;
				font-weight: 600;
				text-align: justify;
				background: #8386dc;
				color: #fff;
				border-radius: 4px;
				padding: 5px 20px;
				top: -33px;
				position: relative;
			}
			.pws-nav-wrapper {
				background: #cc3173;
				width: 100%;
			}
			.pws-sub-screen{
				display:none;
				min-height: 600px;
			}
			.pws-sub-screen.active{
				display:block;
			}
			.pws-product-line .pws-prod-date-created {
				line-height: 45px;
			}
			.pws-product-line.active{
				background: #f7f7f7;
			}
			.pws-nav-wrapper h2 {
				background: #cc3173;
				border: 0;
				color: #fff;
				padding: 0 20px;
				min-width: 100px;
				text-align: center;
				color:#fff;
				font-size: 17px;
				font-weight: 200;
				font-family:"Kumbh Sans";
				margin: 20px 20px 0px 20px;
				cursor: pointer;
			}
			.pws-nav-wrapper h2.active .active-menu-img{
				opacity: 1;
			}
			.pws-nav-wrapper h2 .active-menu-img{
				opacity: 0;
			}
			.pws-nav-wrapper h2 span{
				padding-right: 15px;
				font-size: 30px;
				margin-top: -7px;
			}
			.pws-nav-wrapper h2.active{
				border-botom: 1px solid #333
			} 
			.pws-main-nav{
				display: inline-flex;
				width:100%;
			}
			.pay-with-stripe-settings-wrapper .form-table{
				margin-left: 15%;
			}
			.pws-product-img img {
				width:50px;
			}
			.pws-product-img {
				padding: 10px;
			}
			.pws-product-table tr{
				border-bottom:1px solid #f7f7f7;
			}
			.pws-product-table {
				width: 100%;
				display: inline-grid;
				margin-top: 20px;
			}
			.pws-product-table-header {
				padding-left: 100px;
				text-transform: uppercase;
				font-size: 11px;
				color: #2c2974;
				font-weight: 400;
				line-height: 30px;
				border-top: 1px solid #f7f7f7;
			}
			.pws-product-line {
				display: inline-flex;
				border-top: 1px solid #f7f7f7;
				border-bottom: 1px solid #f7f7f7;
			}
			.pws-product-line:hover{
				background: #f7f7f7;
				cursor: pointer;
			}
			.pws-product-title{
				padding-left: 30px;
				line-height: 45px;
				color: #404452;
				font-weight: 200;
				font-size: 14px;
			}
			.pws-prod-date-created {
				right: 120px;
				position: absolute;
			}
			.pws-prod-edit {
				right: 80px;
				position: absolute;
				padding-top: 10px;
				color: #929292;
			}
			.stripe-pricing-table{
				display: none;
				padding: 30px 20px;
			}
			.stripe-pricing-table.active{
				display: block;
			}
			.stripe_price_shortcode_col {
				width: 50%;
			}
			.stripe-pricing-table td {
				line-height: 40px;
			}
			.stripe-pricing-table td:nth-child(1) {
				width: 10%;
			}
			.stripe-pricing-table td:nth-child(2) {
				width: 25%;
			}
			.stripe-pricing-table td:nth-child(3) {
				width: 50%;
			}
			.stripe-pricing-table td:nth-child(4) {
				width: 25%;
			}
			.stripe-pricing-table table {
				border-collapse: collapse;
			}
			.stripe-pricing-table input {
				background: #f7f7f7;
			}
			.pws-click-copy {
				height: 30px;
				line-height: 25px;
				margin-left: 9px;
				cursor: pointer;
			}
			.pws-product-title span {
				width: 100%;
				float: left;
				line-height: 0px;
				font-size: 12px;
			}
			.pws-pro-features-holder h3{
				margin-top: 0px;
				font-size: 3em;
				color: #271789;
				margin-bottom: 0px;
				margin-right: 65px;
				line-height: 1em;
			}
			.mm-button-business-upgrade span.dashicons.dashicons-arrow-right-alt {
				margin-right: 10px;
				margin-top: 3px;
			}
			.pws-pro-features img {
				width: 50%;
				float: left;
				max-width: 700px;
			}
			.pws-pro-features-holder p{
				line-height: 30px;
				font-size: 18px;
			}
			.pws-pro-features-list{
				padding-top: 70px;
			}
			.pws-pro-features-list .dashicons-yes {
				color: #008000;
				font-size: 3em;
				padding-right: 20px;
			}
			.pws-pro-features-holder p strong {
				margin-left: 15px;
				line-height: 50px;
			}
			.active-menu-img {
				padding-top: 5px;
				margin-left: 40px;
				display: block;
			}
			.pws-keys-help{
				text-align: center;
				padding: 20px;
			}
			.active-menu-img img {
				width: 20px;
			}
			.pws-pro-features-holder .pws-trial-text{
				font-size:14px;
				margin-top: 60px;
				float: left;
				margin-left: 70px;
			}
			.pws-pro-features-holder .pws-trial-text a{
				margin-left:10px;
			}
			.button.mm-button-business-upgrade {
				background: #cc3273;
				color: #fff;
				border: none;
				padding: 5px 20px;
				margin-left: 60px;
			}
		</style>
		<div class="wrap">
		<h1><?php _e( 'Pay With Stripe Settings', 'flab-pwstripe' ); ?></h1>
		<div class='pay-with-stripe-settings-header'>
			<img src='<?php echo PAY_WITH_STRIPE_PLUGIN_URL ;?>includes/assets/img/logo_pay_with_stripe.webp' class="pay-with-stripe-logo-admin">
			<div class="pay-with-stripe-doc-icon"><a href="https://www.freshlightlab.com/documentation/?utm_source=pay-with-stripe-settings&utm_medium=user%20website&utm_campaign=documentation_link_menu_image" target="_blank"><i class="dashicons-before dashicons-admin-page"></i><span>Documentation</span></a></div>
			<div class="pay-with-stripe-support-icon">
				<a href="https://www.freshlightlab.com/contact-us/?utm_source=pay-with-stripe-settings&utm_medium=user%20website&utm_campaign=support_link_menu_image" target="_blank">
					<i class="dashicons dashicons-admin-users "></i>
					<span><?php _e( "Support", 'flab-pwstripe' );?></span>
				</a>
			</div>
		</div>
		<div class="pws-nav-wrapper">
			<div class="pws-main-nav">
				<h2 data-sub-menu="general-sub" class="active"><span class="dashicons dashicons-admin-settings"></span><?php _e( 'General', 'flab-pwstripe' );?><span class="active-menu-img"><img src="<?php echo plugins_url( 'assets/img/active_arrow.png', __FILE__ ); ?>"></span></h2>
				<h2 data-sub-menu="stripe-sub"><span class="dashicons dashicons-admin-plugins"></span><?php _e( 'Stripe', 'flab-pwstripe' );?><span class="active-menu-img"><img src="<?php echo plugins_url( 'assets/img/active_arrow.png', __FILE__ ); ?>"></span></h2>
				<h2 data-sub-menu="product-sub"><span class="dashicons dashicons-cart"></span><?php _e( 'Products', 'flab-pwstripe' );?><span class="active-menu-img"><img src="<?php echo plugins_url( 'assets/img/active_arrow.png', __FILE__ ); ?>"></span></h2>
				<h2 data-sub-menu="subscription-sub"><span class="dashicons dashicons-image-rotate"></span><?php _e( 'Subscriptions', 'flab-pwstripe' );?><span class="active-menu-img"><img src="<?php echo plugins_url( 'assets/img/active_arrow.png', __FILE__ ); ?>"></span></h2>
			</div>
		</div>
		<div class='pay-with-stripe-settings-wrapper'>
			<form method="POST">
				<?php wp_nonce_field( 'pws_options_update', 'pws_settings_form' ); ?>
				<?php settings_fields( 'payments-stripe-gateway-settings-group' ); ?>
				<?php do_settings_sections( 'payments-stripe-gateway-settings-group' ); ?>
				<input type="hidden" name="updated" value="true" />
			
			<!-- Getting Started -->
			<div class="stripe-sub pws-sub-screen">
			<h2 class="pws-header"><?php _e( 'Stripe Settings', 'flab-pwstripe' ); ?></h2>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( 'Live Publishable Key', 'flab-pwstripe' );?></th>
						<td><input name="pws_live_publishable_key" size="50" type="text" value="<?php echo get_option( 'pws_live_publishable_key' ) ; ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Live Secret Key', 'flab-pwstripe' );?></th>
						<td><input name="pws_live_secret_key" size="50" type="password" value="<?php echo get_option( 'pws_live_secret_key' ) ; ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Test Publishable Key', 'flab-pwstripe' );?></th>
						<td><input name="pws_test_publishable_key" size="50" type="text" value="<?php echo get_option( 'pws_test_publishable_key' ) ; ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Test Secret Key', 'flab-pwstripe' );?></th>
						<td><input name="pws_test_secret_key" size="50" type="password" value="<?php echo get_option( 'pws_test_secret_key' ) ; ?>" /></td>
					</tr>
				</table>
				<div class="pws-keys-help"><a href="https://stripe.com/docs/keys" target="_blank"><?php _e('Need help finding the keys?', 'flab-pwstripe')?></a></div>
			
			</div>
			<div class="product-sub pws-sub-screen">
			<?php

				$args = array(
				  'numberposts' => 10,
				  'post_type'   => 'stripe_product'
				);
				 
				$products = get_posts( $args );
				
				echo '<h2 class="pws-header">' . __( 'Products', 'flab-pwstripe' ) . '</h2>';
				if ( count( $products ) > 0 ) {
					echo '<div class="pws-product-table"><div class="pws-product-table-header"><span>' . __( 'Name', 'flab-pwstripe' ) . '</span><span class="pws-prod-date-created">' . __( 'Created', 'flab-pwstripe' ) . '</span></div>';
				} 

				foreach ( $products as $product ) {

					$pricings = get_posts( array(
						'post_type'   => 'stripe_price',
						'post_status' => 'publish',
						'post_parent' => $product->ID,
					));
			
					$output = '<div class="stripe-pricing-table data-prod-id-' . $product->ID  . '"><h3>' . __( 'Pricing', 'flab-pwstripe' ) . '</h3><table>';
					$output .= '<thead><tr><td><span>' . __( 'Price', 'flab-pwstripe' ) . '</span></td><td><span>' . __( 'Price ID', 'flab-pwstripe' ) . '</span></td><td class="stripe_price_shortcode_col"><span>' . __( 'Shortcode', 'flab-pwstripe' ) . '</span></td><td><span>' . __( 'Created', 'flab-pwstripe' ) . '</span></td></td></tr></thead>';
					$count = 0;

					foreach ( $pricings as $price ) {
						$count++;
						$pricename  = get_post_meta( $price->ID, 'stripe_price_unit_amount', true );
						$currency   = get_post_meta( $price->ID, 'stripe_price_currency', true );
						$price_id   = get_post_meta( $price->ID, 'stripe_price_id', true );
						$shortcode  = '[pwstripe_button text="' . __( 'Buy Now', 'flab-pwstripe' ) . '" price="' . $price_id . '"]';
						$price_type = get_post_meta( $price->ID, 'stripe_price_type', true );
						$pricename  = floatval( $pricename ) / 100;
						$created    = get_post_meta( $price->ID, 'stripe_price_created', true );
				
						if ('recurring' === $price_type ) {
							$pricename  .= ' / month';
						}
			
						$output .= '<tr><td>' . strtoupper( $currency ) . ' ' . $pricename . '</td>';
						$output .= '<td>' . $price_id . '</td>';
						$output .= '<td><input type="text" id="' . $price_id . '" readonly size="30" value="' . esc_attr( $shortcode ) . '"><button title="' . __( 'Click to Copy', 'flab-pwstripe' ) . '" alt="' . __( 'Click to Copy', 'flab-pwstripe' ) . '" class="pws-click-copy"><i class="dashicons dashicons-admin-page" alt="' . __( 'Copy Shortcode', 'flab-pwstripe' ) . '"></i></button></td>';
						$output .= '<td>' . date('jS \of F Y h:i:s A', $created ) . '</td></tr>';
					}

					$pricedetails = "";

					if ( $count == 1 ) {
						$pricedetails = '<span>' . strtoupper( $currency ) . ' ' . $pricename . '</span>';
					} else {
						$pricedetails = '<span>' . $count . ' ' . __( 'Prices', 'flab-pwstripe' ) . '</span>';
					}

					$output .= '</table></div>';
					echo '<div class="pws-product-line" data-prod-id="' .  $product->ID  . '"><div class="pws-product-img">';
					echo get_the_post_thumbnail( $product->ID, array(75, 50, true), array( 'class' => 'alignleft' ) );
					echo '</div><div class="pws-product-title">';
					echo $product->post_title;
					echo  $pricedetails . '</div><div class="pws-prod-date-created">' .  date('jS \of F Y', $created ) . '</div><div class="pws-prod-edit"><i class="dashicons-before dashicons-arrow-down-alt2"></i></div></div>';

					echo $output;

				}

				if ( count( $products ) > 0 ) {
					echo '</div>';
				}
				echo '<h2 class="pws-header">' . __( 'Product options', 'flab-pwstripe' ) . '</h2>';
				?>

				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( 'Sync your products', 'flab-pwstripe' );?></th>
						<td><select name="pws_stripe_mode" id="pws_stripe_mode" style="width:374px;">
								<option value="test_mode"  <?php if ( $pws_stripe_mode == 'test_mode' ) { echo 'selected'; } ?>><?php _e( 'Every Hour', 'flab-pwstripe' ); ?></option>
							</select>
						</td>
					</tr>
					<tr><th><?php _e( 'Need more sync periods?', 'flab-pwstripe' ); ?></th><td><label><a href="https://www.freshlightlab.com/pay-with-stripe//?utm_source=pay-with-stripe-settings&utm_medium=user%20website&utm_campaign=products_sync_periods"><?php _e( 'You can find them in the Premium version.', 'flab-pwstripe' ); ?></a></label></td></tr>
				</table>
				<?php

			?>
			</div>
			<div class="subscription-sub pws-sub-screen">
			<?php $this->create_subscription_options_upsell(); ?>
			</div>
			<div class="general-sub pws-sub-screen active">
				<h2 class="pws-header"><?php _e( 'Getting Started', 'flab-pwstripe' ); ?></h2>
				<div class="pws-welcome-box">
					<h3><?php _e( 'Welcome to Pay With Stripe', 'flab-pwstripe' ); ?></h3>
					<span class="intro-text"> <?php _e( 'Get familiar with Pay With Stripe by watching our Getting Started Video. It will guide you through the necessary steps to Accept Payments in you WordPress site.', 'flab-pwstripe' ); ?></span>
					<div>
						<iframe width="620" height="350" src="https://www.youtube.com/embed/VIhqQ92aG_w" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
					</div>
				</div>

				<!-- Main options -->
				<h2 class="pws-header"><?php _e( 'Main Options', 'flab-pwstripe' ); ?></h2>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php _e( 'Success URL', 'flab-pwstripe' );?></th>
						<td><input name="pws_success_url" size="50" type="text" value="<?php echo get_option( 'pws_success_url' ) ; ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Cancel URL', 'flab-pwstripe' );?></th>
						<td><input name="pws_cancel_url" size="50" type="text" value="<?php echo get_option( 'pws_cancel_url' ) ; ?>" /></td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e( 'Stripe Mode', 'flab-pwstripe' );?></th>
						<td><select name="pws_stripe_mode" id="pws_stripe_mode" style="width:374px;">
								<option value="live_mode"  <?php if ( $pws_stripe_mode == 'live_mode' ) { echo 'selected'; } ?>><?php _e( 'Live Data', 'flab-pwstripe' ); ?></option>
								<option value="test_mode"  <?php if ( $pws_stripe_mode == 'test_mode' ) { echo 'selected'; } ?>><?php _e( 'Test Data', 'flab-pwstripe' ); ?></option>
							</select>
						</td>
					</tr>
					
				</table>
			</div>
			<?php submit_button(); ?>
			</form>
		</div>
		</div>
		<?php
	}

	/**
	 *
	 * Validate the options of the submission form.
	 *
	 */
	public function handle_options_form(){
		if( ! isset( $_POST['pws_settings_form'] ) || ! wp_verify_nonce( $_POST['pws_settings_form'], 'pws_options_update' ) ) { ?>
			<div class="error">
				<p><?php _e( 'Sorry, your nonce was not correct. Please try again.', 'flab-pwstripe' );?></p>
			</div> 
			<?php
		} else {

			update_option( 'pws_stripe_mode', sanitize_text_field( $_POST['pws_stripe_mode'] ) );
			update_option( 'pws_live_publishable_key', sanitize_text_field( $_POST['pws_live_publishable_key'] ) );
			update_option( 'pws_live_secret_key', sanitize_text_field( $_POST['pws_live_secret_key'] ) );
			update_option( 'pws_test_publishable_key',sanitize_text_field( $_POST['pws_test_publishable_key'] ) );
			update_option( 'pws_test_secret_key', sanitize_text_field( $_POST['pws_test_secret_key'] ) );
			update_option( 'pws_success_url', sanitize_text_field( $_POST['pws_success_url'] ) );
			update_option( 'pws_cancel_url', sanitize_text_field( $_POST['pws_cancel_url'] ) );

			?>
			<div class="updated">
				<p><?php _e( 'Your Pay With Stripe settings were saved!', 'flab-pwstripe' );?></p>
			</div>
		<?php
		}
	}

	/**
	 *
	 * Create Subscriptions options upsell.
	 *
	 */
	public function create_subscription_options_upsell() {

		global  $pws_fs;

		if ( ! $pws_fs->is__premium_only() ) {

			$custom_html  = '<div class="pws-pro-features-holder">';
			$custom_html .= '<div class="pws-pro-features"><img src="' . plugins_url( 'assets/img/pws_subscriptions_upsell.webp', __FILE__ ) . '">';
			$custom_html .= '<div class="pws-pro-features-list"><h3>' . __( 'Grow your Recurring Revenue', 'flab-pwstripe' ) . '</h3><p><span class="dashicons dashicons-yes"></span><strong>' . __( 'Manage Stripe Subscriptions on your Website', 'flab-pwstripe' ) . '</strong></p>';
			$custom_html .= '<p><span class="dashicons dashicons-yes"></span><strong>' . __( 'Cancel Subscriptions in admin side', 'flab-pwstripe' ) . '</strong></p>';
			$custom_html .= '<p><span class="dashicons dashicons-yes"></span><strong>' . __( 'Pricing plans Widget (Elementor)', 'flab-pwstripe' ) . '</strong></p></div>';
			$custom_html .= '<p><a href="' . $pws_fs->get_upgrade_url() . '&cta=woo-settings#" class="button mm-button-business-upgrade"><span class="dashicons dashicons-arrow-right-alt"></span>' . __( 'GO PREMIUM', 'flab-pwstripe' ) . '</a></p>';
			$custom_html .= '<p class="pws-trial-text">' . __( 'Not sure if it has the right features?', 'flab-pwstripe' ) . '<a href="' . $pws_fs->get_trial_url() . '">' . esc_html( 'Start a Free trial', 'flab-pwstripe' ) . '</a></p>';
			$custom_html .= '</div></div>';
		} else {
			$custom_html  = '<div class="pws-pro-subscriptions-listr"><h2 class="pws-header">' . __( 'Subscriptions', 'flab-pwstripe' ). '</h2><span>' . __( 'No subscriptions found.', 'flab-pwstripe' ) . '</span></div>';
		}
		
		echo $custom_html;
	}

}
