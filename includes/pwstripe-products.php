<?php
/**
 * Payments Stripe Gateway Product
 *
 * This will manage the plugin options.
 *
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU General Public License Version 3
 *
 * @since 1.0
 */

if ( ! class_exists( 'PWS_Stripe_Product' ) ) {
	die();
}
/**
 *
 * Class PWS_Stripe_Product.
 *
 * @since 1.0
 */
class PWS_Stripe_Product {

	/**
	 *
	 * Class Constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {
	}

	/**
	 *
	 * Init functions.
	 *
	 * @since 1.0
	 */
	public function init() {

		add_action( 'add_meta_boxes', array( $this, 'pwstripe_add_meta_boxes' ), 1  );
		add_image_size( 'stripe_product-admin-post-featured-image', 80, 80, false );
		add_action( 'admin_head', array( $this, 'pwstripe_admin_head' ) );
		add_action( 'wp_trash_post', array( $this, 'pwstripe_trash_products_clear_all_childs' ) );
		add_action( 'delete_post', array( $this, 'pwstripe_delete_products_clear_all_childs' ) );
		add_action( 'init', array( $this, 'pwstripe_register_cpt' ) );
		add_filter( 'manage_stripe_product_posts_columns', array( $this, 'stripe_product_add_post_admin_thumbnail_column' ), 2 ) ;
		add_action( 'manage_stripe_product_posts_custom_column', array( $this, 'stripe_product_show_post_thumbnail_column') , 5, 2 );

	}

	/**
	 *
	 * Get products from Stripe.
	 *
	 * @since 1.0.1
	 */
	public function pws_get_products() {

		require_once(ABSPATH . "wp-admin" . '/includes/image.php');
		require_once(ABSPATH . "wp-admin" . '/includes/file.php');
		require_once(ABSPATH . "wp-admin" . '/includes/media.php');
	
		$post_type   = 'stripe_product';
		$pws_payment = new PWS_PAYMENTS();
		$products    = $pws_payment->get_products_list();
	
		foreach ( $products as $product ) {

			if ( $product->active ) {
				$product_desc = '';
				if ( $product->description != NULL ) {
					$product_desc = $product->description;
				}
	
				$post = array(
					'post_name'    => sanitize_title( $product->name ),
					'post_title'   => $product->name,
					'post_status'  => 'publish',
					'post_type'    => $post_type,
					'post_content' => $product_desc,
				);
	
				$matching = get_posts( array(
					'post_type'   => $post_type,
					'post_status' => 'publish',
					'meta_query'  => array(
						array(
							'key'   => 'stripe_product_id',
							'value' => $product->id,
						)
					)
				));
	
				if ( ! empty( $matching ) ) {
					$post_id = $matching[0]->ID;

					$arg = array(
						'ID'           => $post_id,
						'post_name'    => sanitize_title( $product->name ),
						'post_title'   => $product->name,
						'post_content' => $product_desc,
					);

					wp_update_post( $arg );
				} else {
					$post_id = wp_insert_post( $post );
				}
	
				if ( $post_id > 0 ) {
	
					$attachments = get_attached_media( '', $post_id );
	
					foreach ($attachments as $attachment) {
						wp_delete_attachment( $attachment->ID, 'true' );
					}
	
					foreach( $product->images as $image ) {
	
						$file             = array();
						$file['name']     = basename( $image ) . '.png';
						$file['tmp_name'] = download_url( $image );
						$attachmentId     = media_handle_sideload( $file, $post_id );
	
						set_post_thumbnail( $post_id, $attachmentId );
	
					}

					update_post_meta( $post_id, 'stripe_product_id' ,wp_kses_post( sanitize_text_field( $product->id ) ) );
	
					// Call the prices API and inser the prices.
					$prices = $pws_payment->get_stripe_prices( $product->id );
					foreach ( $prices as $price ) {
						if ( $price->active ) {
	
							// Taxonomy
							//$price->billing_scheme
							//$price->livemode (boolean)
							//$price->type (boolean)
	
							$post_price = array(
								'post_content' => '',
								'post_name'    => sanitize_title( 'Price - ' . $price->id ),
								'post_title'   => sanitize_title( 'Price - ' . $price->id ),
								'post_status'  => 'publish',
								'post_type'    => 'stripe_price',
								'post_parent'  =>  $post_id,
							);
	
							$matchingPrice = get_posts( array(
								'post_type'   => 'stripe_price',
								'post_status' => 'publish',
								'meta_query'  => array(
									array(
										'key'   => 'stripe_price_id',
										'value' => $price->id,
									)
								)
							));
	
							if ( ! empty( $matchingPrice ) ) {
								$post_price_id = $matchingPrice[0]->ID;
							} else {
								$post_price_id = wp_insert_post( $post_price );
							}
	
							if ( $post_price_id > 0 ) {
	
								update_post_meta( $post_price_id, 'stripe_price_id' , sanitize_text_field( $price->id ) );
								update_post_meta( $post_price_id, 'stripe_price_billing_scheme' , sanitize_text_field( $price->billing_scheme ) );
								update_post_meta( $post_price_id, 'stripe_price_type' , sanitize_text_field( $price->type ) );
								update_post_meta( $post_price_id, 'stripe_price_product' , sanitize_text_field( $price->product ) );
								update_post_meta( $post_price_id, 'stripe_price_currency' , sanitize_text_field( $price->currency ) );
								update_post_meta( $post_price_id, 'stripe_price_unit_amount' , sanitize_text_field( $price->unit_amount ) );
								update_post_meta( $post_price_id, 'stripe_price_created' , sanitize_text_field( $price->created ) );
	
							}
						}
					}
				}
			}
		}
	}

	/**
	 *
	 * Register Stripe Product CPT.
	 *
	 * @since 1.0.1
	 */
	public function pwstripe_register_cpt() {

		// Stripe Products.
		register_post_type( 'stripe_product',
			array(
			'labels' => array(
				'name'               => __( 'Product','flab-pwstripe' ),
				'singular_name'      => __( 'Product','flab-pwstripe' ),
				'menu_name'          => __( 'Stripe Products','flab-pwstripe' ),
				'name_admin_bar'     => __( 'Product','flab-pwstripe' ),
				'add_new'            => __( 'Add new','flab-pwstripe' ),
				'add_new_item'       => __( 'Add new Product','flab-pwstripe' ),
				'new_item'           => __( 'New Product','flab-pwstripe' ),
				'edit_item'          => __( 'Edit Product','flab-pwstripe' ),
				'view_item'          => __( 'View Product','flab-pwstripe' ),
				'all_items'          => __( 'All Products','flab-pwstripe' ),
				'search_items'       => __( 'Search Product','flab-pwstripe' ),
				'not_found'          => __( 'Product not found','flab-pwstripe' ),
				'not_found_in_trash' => __( 'Product not found in trash','flab-pwstripe' ),
			),
			'public' => false,
			'has_archive' => false,
			'menu_position' => 5,
			'rewrite' => array( 'slug' => 'stripe_product' ),
			'supports' => array(
				'title',
				'editor',
				'thumbnail',
				'custom-fields',
				),
			)
		);

		// Stripe Prices.
		register_post_type( 'stripe_price',
			array(
				'label'           => __( 'Prices', 'flab-pwstripe' ),
				'public'          => false,
				'hierarchical'    => false,
				'supports'        => false,
				'rewrite'         => false,
			)

		);
	}

	/**
	 *
	 * Register Stripe Product Taxonomy.
	 *
	 * @since 1.0.1
	 */
	public function pwstripe_register_taxonomy() {

		$labels = array(
			'name'              => __( 'Year', 'flab-pwstripe' ),
			'singular_name'     => __( 'Year', 'flab-pwstripe' ),
			'search_items'      => __( 'Search Year', 'flab-pwstripe' ),
			'all_items'         => __( 'All Years', 'flab-pwstripe' ),
			'edit_item'         => __( 'Edit Year', 'flab-pwstripe' ),
			'update_item'       => __( 'Update Year', 'flab-pwstripe' ),
			'add_new_item'      => __( 'Add New Year', 'flab-pwstripe' ),
			'new_item_name'     => __( 'New Year Name', 'flab-pwstripe' ),
			'menu_name'         => __( 'Year', 'flab-pwstripe' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'sort'              => true,
			'args'              => array( 'orderby' => 'term_order' ),
			'rewrite'           => array( 'slug'    => 'car-year' ),
			'show_admin_column' => true,
		);

		register_taxonomy( 'car-year', array( 'stripe_product' ), $args);

		$labels = array(
				'name'              => __( 'Make', 'flab-pwstripe' ),
				'singular_name'     => __( 'Make', 'flab-pwstripe' ),
				'search_items'      => __( 'Search Make', 'flab-pwstripe' ),
				'all_items'         => __( 'All Makes', 'flab-pwstripe' ),
				'edit_item'         => __( 'Edit Make', 'flab-pwstripe' ),
				'update_item'       => __( 'Update Make', 'flab-pwstripe' ),
				'add_new_item'      => __( 'Add New Make', 'flab-pwstripe' ),
				'new_item_name'     => __( 'New Make Name', 'flab-pwstripe' ),
				'menu_name'         => __( 'Make', 'flab-pwstripe' ),
			);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'sort'              => true,
			'args'              => array( 'orderby' => 'term_order' ),
			'rewrite'           => array( 'slug'    => 'make' ),
			'show_admin_column' => true,
			'show_count'      => true,
			'hide_empty'      => true,
		);

		register_taxonomy( 'make', array( 'stripe_product' ), $args);

		$labels = array(
			'name'              => __( 'Model', 'flab-pwstripe' ),
			'singular_name'     => __( 'Model', 'flab-pwstripe' ),
			'search_items'      => __( 'Search Model', 'flab-pwstripe' ),
			'all_items'         => __( 'All Models', 'flab-pwstripe' ),
			'edit_item'         => __( 'Edit Models', 'flab-pwstripe' ),
			'update_item'       => __( 'Update Models', 'flab-pwstripe' ),
			'add_new_item'      => __( 'Add New Model', 'flab-pwstripe' ),
			'new_item_name'     => __( 'New Model Name', 'flab-pwstripe' ),
			'menu_name'         => __( 'Model', 'flab-pwstripe' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'sort'              => true,
			'args'              => array( 'orderby' => 'term_order' ),
			'rewrite'           => array( 'slug'    => 'model' ),
			'show_admin_column' => true,
		);

		register_taxonomy( 'model', array( 'stripe_product' ), $args);

	}

	/**
	 *
	 * Add Stripe Product Thumbnail in admin list.
	 *
	 * @since 1.0.1
	 */
	public function stripe_product_add_post_admin_thumbnail_column( $stripe_product_columns ) {

		unset( $stripe_product_columns['title'] );
		unset( $stripe_product_columns['date'] );

		return array_merge ( $stripe_product_columns, array ( 
			'stripe_product_thumb' => __( 'Image' ),
			'title'                => __( 'Title' ),
			'date'                 => __( 'Date' ),
		) );

	}

	/**
	 *
	 * Show Stripe Product Thumbnail in admin list.
	 *
	 * @since 1.0.1
	 */
	public function stripe_product_show_post_thumbnail_column( $stripe_product_columns, $stripe_product_thumb_id ) {

		switch( $stripe_product_columns ) {
			
			case 'stripe_product_thumb':
				if( function_exists('the_post_thumbnail') ) {
					echo the_post_thumbnail( 'stripe_product-admin-post-featured-image' );
				}
			break;
		}
	}

	/**
	 *
	 * Add CSS to Stripe Product admin components.
	 *
	 * @since 1.0.1
	 */
	public function pwstripe_admin_head() {
		global $post_type;
		if ( 'stripe_product' == $post_type ) {
			?><style type="text/css"> 
			th#stripe_product_thumb { 
				width: 90px; 
			} 
			.stripe_pricing_table button {
				background: #fff;
				margin-left: 10px;
				cursor: pointer;
			}
			.stripe_pricing_table thead { 
				font-size: 11px;
				font-weight: 600;
				line-height: normal;
			}
			.stripe_price_shortcode_col td {
				font-size: 9px;
			}
			.stripe_pricing_table tr {
				line-height: 30px;
				border-bottom: 1 px solid rgb(128, 128, 128);
			}
			.stripe_pricing_table td {
				padding-right: 30px;
			}
			</style>
		<?php
		}
	}

	/**
	 *
	 * Delete Stripe Product childs info.
	 *
	 * @since 1.0
	 */
	public function pwstripe_delete_product_childs($post_id, $permanently ){

		global $wpdb;
		global $post_type;
		
		if ( $post_type != 'stripe_product' ) {
			return;
		}

		$childs = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type in ('stripe_price', 'attachment') AND post_parent = %d", $post_id ) );


		if ( empty( $childs ) ) {
			return;
		}

		foreach( $childs as $post ) {
			if ( $permanently ) {
				wp_delete_post( $post->ID, true );
			} else {
				wp_trash_post( $post->ID );
			}
		}

	}

	/**
	 *
	 * Delete all Stripe Product childs.
	 *
	 * @since 1.0.1
	 */
	public function pwstripe_delete_products_clear_all_childs( $post_id ) {
		// When deleting a product also delete all the Prices and attachments of the product.
		$this->pwstripe_delete_product_childs( $post_id, true );
	}

	
	/**
	 *
	 * Trash all Stripe Product childs.
	 *
	 * @since 1.0.1
	 */
	public function pwstripe_trash_products_clear_all_childs( $post_id ) {
		// When sending a product to trash also trash all the Prices and attachments of the product.
		$this->pwstripe_delete_product_childs( $post_id, false );
	}

	/**
	 *
	 * Create the Stripe Product metabox info.
	 *
	 * @since 1.0.1
	 */
	public function pwstripe_metabox_output_product_details( $post ){

		$output        = '<table class="stripe_pricing_table">';
		$attachments   = get_attached_media( '', $post->ID );
		$product_image = '';
		
		foreach ($attachments as $attachment) {
			$product_image .= wp_get_attachment_image( $attachment->ID, array('60', '100'), "", array( "class" => "img-responsive" ) );
		}

		$output .= '<tr><td>Name</td><td>' . $post->post_title . '</td><td></td><td>' . $product_image . '</td>';
		$output .= '<tr><td>Description</td><td>' . $post->post_content . '</td><td></td><td></td>';
		$output .= '</table>';

		echo $output;
	}


	/**
	 *
	 * Create the Stripe Product metabox pricing info.
	 *
	 * @since 1.0.1
	 */
	public function pwstripe_metabox_output_product_pricing( $post ){

		$pricings = get_posts( array(
			'post_type'   => 'stripe_price',
			'post_status' => 'publish',
			'post_parent' => $post->ID,
		));

		$output = '<table class="stripe_pricing_table">';
		$output .= '<thead><tr><td><span>Price</span></td><td><span>Price ID</span></td><td class="stripe_price_shortcode_col"><span>Shortcode</span></td><td><span>Created</span></td></td></tr></thead>';

		foreach ( $pricings as $price ) {

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
			$output .= '<td><input type="text" id="' . $price_id . '" readonly size="30" value="' . esc_attr( $shortcode ) . '"><button title="' . __( 'Click to Copy', 'flab-pwstripe' ) . '" class="pws-click-copy"><i class="dashicons dashicons-admin-page" alt="Copy Shortcode"></i></button></td>';
			$output .= '<td>' . date('jS \of F Y h:i:s A', $created ) . '</td></tr>';
		}

		$output .= '</table>';
		echo $output;
	}

	/**
	 *
	 * Add Metaboxes to the Stripe Product admin page.
	 *
	 * @since 1.0.1
	 */
	public function pwstripe_add_meta_boxes() {
		add_meta_box( 'stripe-product-product-details', __( 'Product Details', 'flab-pwstripe' ), array( $this, 'pwstripe_metabox_output_product_details' ), 'stripe_product', 'normal', 'high' );
		add_meta_box( 'stripe-product-pricing-data', __( 'Pricing', 'flab-pwstripe' ), array( $this, 'pwstripe_metabox_output_product_pricing' ), 'stripe_product', 'normal', 'high' );
	}

}
