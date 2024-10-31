<?php
/**
 * Elementor Widget.
 *
 *
 * @since 1.0.0
 */
class Stripe_Button_Widget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * Retrieve the widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'stripe_button';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve the widget title.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Stripe Button', 'flab-pwstripe' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve the widget icon.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-button';
	}

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the oEmbed widget belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'general, stripe' ];
	}

	/**
	 * Register the widget controls.
	 *
	 * Input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function _register_controls() {

		$this->start_controls_section(
			'pwstripe_section',
			[
				'label' => __( 'Settings', 'flab-pwstripe' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$products = get_posts( array(
			'post_type'   => 'stripe_product',
			'post_status' => 'publish',
		));

		foreach ( $products as $product ) {
			
			$prices = get_posts( array(
				'post_type'   => 'stripe_price',
				'post_status' => 'publish',
				'post_parent' => $product->ID
			));

			foreach ( $prices as $price ) {
				$pricename                 = get_post_meta( $price->ID, 'stripe_price_unit_amount', true );
				$currency                  = get_post_meta( $price->ID, 'stripe_price_currency', true );
				$price_id                  = get_post_meta( $price->ID, 'stripe_price_id', true );
				$price_type                = get_post_meta( $price->ID, 'stripe_price_type', true );
				$pricename                 =  strtoupper( $currency ) . ' ' . floatval( $pricename ) / 100;

				$pricename = $product->post_title . ' - ' . $pricename;

				if ( $price_type === 'recurring' ) {
					$pricename.= ' / month';
				}

				$products_list[$price_id] = $pricename;
			}
			
		}

		$this->add_control(
			'stripe_product',
			[
				'label'    => __( 'Product', 'flab-pwstripe' ),
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'multiple' => false,
				'options'  => $products_list,
				'default'  => '',
			]
		);

		$this->add_control(
			'payment_mode',
			[
				'label'    => __( 'Payment Mode', 'flab-pwstripe' ),
				'type'     => \Elementor\Controls_Manager::SELECT2,
				'multiple' => false,
				'options'  => array( 'subscription' => 'Subscription','payment' => 'Payment' ),
				'default'  => 'payment',
			]
		);

		$this->add_control(

			'api_id',
			[
				'label'       => __( 'Price ID', 'flab-pwstripe' ),
				'type'        => \Elementor\Controls_Manager::HIDDEN,
			]
		);

		$this->add_control(

			'page_tks',
			[
				'label'       => __( 'Thank you page URL', 'flab-pwstripe' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( 'site.com/thankyou', 'flab-pwstripe' ),
				'description' => __( 'Thank you page URL after the payment is made with success.', 'flab-pwstripe' ),
			]
		);
		
		$this->add_control(

			'page_rec',
			[
				'label'       => __( 'Cancel URL', 'flab-pwstripe' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( 'site.com/cancel', 'flab-pwstripe' ),
				'description' => __( 'Cancel page URL.', 'flab-pwstripe' ),
			]
		);

		$this->add_control(

			'btn_text',
			[
				'label'       => __( 'Button Text', 'flab-pwstripe' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => __( 'Buy Now', 'flab-pwstripe' ),
				'description' => __( 'Text that will display in the button.', 'flab-pwstripe' ),
			]
		);
		
		$this->add_control(
			'btn_color',
			[
				'label'   => __( 'Button Color', 'flab-pwstripe' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => __( '#0070c9', 'flab-pwstripe' ),
			]
		);
		
		$this->add_control(
			'btn_text_color',
			[
				'label'   => __( 'Text Color', 'flab-pwstripe' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'default' => __( '#FFF', 'flab-pwstripe' ),
			]
		);
		
		$this->add_control(
			'btn_font',
			[
				'label'   => __( 'Text Font', 'flab-pwstripe' ),
				'type'    => \Elementor\Controls_Manager::FONT,
				'default' => __( "'Open Sans', sans-serif", 'flab-pwstripe' ),
			]
		);
		
		$this->add_control(

			'btn_size',
			[
				'label'       => __( 'Font Size', 'flab-pwstripe' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'placeholder' => __( '0', 'flab-pwstripe' ),
				'default'     => '15',
			]
		);

		$this->add_control(

			'btn_padding',
			[
				'label'       => __( 'Padding', 'flab-pwstripe' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'default' => array('top' =>'10', 'right' => '25', 'bottom' => '10', 'left' => '25', 'isLinked' => false),
				'selectors' => [
					'{{WRAPPER}} .pws-submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
				],
			]
		);
		
		$this->add_control(

			'btn_radius',
			[
				'label'       => __( 'Button radius', 'flab-pwstripe' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'placeholder' => __( '0', 'flab-pwstripe' ),
				'description' => '',
			]
		);

		$this->end_controls_section();

}

	/**
	 * Render oEmbed widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {

		$settings   = $this->get_settings_for_display();
		$price_id   = isset($settings['stripe_product']) ? $settings['stripe_product'] : '';
		$public_key = isset($settings['public_key']) ? $settings['public_key'] : '';
		$page_tks   = isset($settings['page_tks']) ? $settings['page_tks'] : '';
		$page_rec   = isset($settings['page_rec']) ? $settings['page_rec'] : '';
		$btn_color  = isset($settings['btn_color']) ? $settings['btn_color'] : '';
		$text_color = isset($settings['btn_text_color']) ? $settings['btn_text_color'] : '';
		$btn_radius = isset($settings['btn_radius']) ? $settings['btn_radius'] : '';
		$btn_font   = isset($settings['btn_font']) ? $settings['btn_font'] : '';
		$btn_size   = isset($settings['btn_size']) ? $settings['btn_size'] : '';
		$btn_text   = isset($settings['btn_text']) ? $settings['btn_text'] : '';
		$mode   = isset($settings['payment_mode']) ? $settings['payment_mode'] : 'payment';

		echo do_shortcode('[pwstripe_button mode="' . $mode . '" text="' . $btn_text . '"  success_url="' . $page_tks . '" cancel_url="' . $page_rec . '" text_color="' . $text_color . '" button_color="' . $btn_color . '" button_radius="' . $btn_radius . '" button_size="' . $btn_size . '" font="' . $btn_font . '" price="' . $price_id . '"]');

	}

}
