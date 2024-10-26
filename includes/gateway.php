<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
/**
 *@version 1.0
 *@author ZonalHost <support@zonalhost.com>
 *@package 2CPay
 *@license http://www.gnu.org/licenses/gpl-2.0.html GPLv2 or later
*/
class ZH_2CPay extends WC_Payment_Gateway {
    // Setup our Gateway's id, description and other values
    function __construct() {
        global $post;
        $this->params = array();
	// The global ID for this Payment method
	$this->id = "zh_2cpay";
        $this->order_button_text  = __( 'Proceed to 2Checkout', 'woocommerce' );
        $this->method_title = __( "2CPay", 'zh-2cpay' );
        $this->method_description = __( "2Checkout Payment Gateway Plugin for WooCommerce", 'zh-2cpay' );
        $this->title = __( "2Checkout 2CPay", 'zh-2cpay' );
        $this->icon = null;
        $this->has_fields = false;
        // This basically defines your settings which are then loaded with init_settings()
	$this->init_form_fields();
        $this->supports = array(
                            'products'
                        );
        $this->init_settings();
	// Turn these settings into variables we can use
	foreach ( $this->settings as $setting_key => $value ) {
	    $this->$setting_key = $value;
	}
	// Save settings
	if ( is_admin() ) {
	    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}
        $this->ischeckout_success();
    }
    
    function ischeckout_success(){
        global $post, $woocommerce;
	if (isset($_REQUEST['merchant_order_id'])) {
	    $order = new WC_Order($_REQUEST['merchant_order_id']);
	    $header_received = json_encode(print_r($_REQUEST, true));
	    if ($this->debug == '1') {
		$order->add_order_note( __( 'Header Received: ' .$header_received , 'zh-2cpay' ) );
	    }
	    if($_GET['2cpay'] == 'approved'){
		$hashSecretWord = $this->sec_word; //2Checkout Secret Word
		$hashSid = $this->store_id; //2Checkout account number
		$hashTotal = $order->order_total; //Sale total to validate against
		$hashOrder = $_REQUEST['order_number']; //2Checkout Order Number
		$hashKey = $hashSecretWord . $hashSid . $hashOrder . $hashTotal;
		if ($this->debug == '1') {
		    $order->add_order_note( __( 'Hash Key: ' . $hashKey , 'zh-2cpay' ) );
		    $order->add_order_note( __( 'Hash Value: ' . strtoupper(md5($hashKey)) , 'zh-2cpay' ) );
		}
		$StringToHash = strtoupper(md5($hashKey));
		if ($StringToHash != $_REQUEST['key']) {
		    wc_add_notice('2Checkout payment could not be completed. Error - Hash Mismatch', 'error');
		    $order->add_order_note( __( '2Checkout payment could not be completed. Error - Hash Mismatch.') );
		    return false;
		} else {
		    $order->payment_complete();
		    $woocommerce->cart->empty_cart();
		    wc_add_notice('Your payment was successful', 'success');
		    return true;
		}
	    } else {
		wc_add_notice('2Checkout payment could not be completed. Error - Invalid headers received.', 'error');
		$order->add_order_note( __( '2Checkout payment could not be completed. Error - Invalid headers received.') );
	    }
	    return false;
	}
    }
    public function addParam($key, $value) {
        $this->params[$key] = $value;
    }
    // Build the administration fields for this specific Gateway
    public function init_form_fields() {
	$this->form_fields = array(
		'enabled' => array(
			'title'		=> __( 'Enable / Disable', 'zh-2cpay' ),
			'label'		=> __( 'Enable this payment gateway', 'zh-2cpay' ),
			'type'		=> 'checkbox',
			'default'	=> 'no',
		),
		'title' => array(
			'title'		=> __( 'Title', 'zh-2cpay' ),
			'type'		=> 'text',
			'desc_tip'	=> __( '2Checkout (Pay by Credit/Debit Card).', 'zh-2cpay' ),
			'default'	=> __( '2Checkout Debit/Credit Card', 'zh-2cpay' ),
		),
		'description' => array(
			'title'		=> __( 'Description', 'zh-2cpay' ),
			'type'		=> 'textarea',
			'desc_tip'	=> __( '2Checkout (Pay by Credit/Debit Card).', 'zh-2cpay' ),
			'default'	=> __( '2Checkout (Pay by Credit/Debit Card).', 'zh-2cpay' ),
			'css'		=> 'max-width:350px;'
		),
		'store_id' => array(
			'title'		=> __( 'Store Id', 'zh-2cpay' ),
			'type'		=> 'text',
			'desc_tip'	=> __( 'Store id can be found from your 2Checkout account.', 'zh-2cpay' ),
		),
		'sec_word' => array(
			'title'		=> __( 'Secret Word', 'zh-2cpay' ),
			'type'		=> 'password',
			'desc_tip'	=> __( 'Secret word can be found from your 2Checkout account.', 'zh-2cpay' ),
		),
		'testmode' => array(
			'title'		=> __( 'Test Mode', 'zh-2cpay' ),
			'label'		=> __( 'Enable Test Mode', 'zh-2cpay' ),
			'type'		=> 'select',
			'description' => __( 'Place the payment gateway in test mode.', 'zh-2cpay' ),
			'options'     => array(
				'0'          => __( 'No', 'woocommerce' ),
				'1' => __( 'Yes', 'woocommerce' )
			)
		),
		'debug' => array(
			'title'		=> __( 'Debug Mode', 'zh-2cpay' ),
			'label'		=> __( 'Enable Debug Mode', 'zh-2cpay' ),
			'type'		=> 'select',
			'description' => __( 'Store headers as order note received from 2Checkout.', 'zh-2cpay' ),
			'options'     => array(
				'0'          => __( 'No', 'woocommerce' ),
				'1' => __( 'Yes', 'woocommerce' )
			)
		)
	);		
    }
    
    public function is_valid_for_use() {
	return in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_2cpay_supported_currencies', array( 'AUD', 'BRL', 'CAD', 'MXN', 'NZD', 'HKD', 'SGD', 'USD', 'EUR', 'JPY', 'TRY', 'NOK', 'CZK', 'DKK', 'HUF', 'ILS', 'MYR', 'PHP', 'PLN', 'SEK', 'CHF', 'TWD', 'THB', 'GBP', 'RMB', 'RUB' ) ) );
    }
    
    public function admin_options() {
        if ( $this->is_valid_for_use() ) {
            parent::admin_options();
        } else {
        ?>
            <div class="inline error"><p><strong><?php _e( 'Gateway Disabled', 'woocommerce' ); ?></strong>: <?php _e( '2Checkout does not support your store currency.', 'woocommerce' ); ?></p></div>
        <?php
        }
    }
    
    public function get_transaction_url( $order ) {
        if ( $this->testmode ) {
            return $this->view_transaction_url = 'https://sandbox.2checkout.com/checkout/purchase?';
        } else {
            return $this->view_transaction_url = 'https://www.2checkout.com/checkout/purchase?';
        }
    }
    
    public function process_payment( $order_id ) {
        $order = new WC_Order($order_id);
        $redirect = $this->get_transaction_url($order);
        $this->params['sid'] = $this->store_id;
        $this->params['secret_word'] = $this->sec_word;
        $this->addParam('mode', '2CO');
        $this->addParam('li_0_type', 'product');
        $this->addParam('li_0_name', 'Cart Purchase:' . $order->order_key);
        $this->addParam('li_0_price', $order->order_total);
        $this->addParam('li_0_product_id', $order->order_key);
        $this->addParam('li_0_tangible', 'N');
        
        //Customer Billing Information
        $this->addParam('first_name', $order->billing_first_name);
        $this->addParam('last_name', $order->billing_last_name);
        $this->addParam('email', $order->billing_email);
        $this->addParam('phone', $order->billing_phone);
        $this->addParam('street_address', $order->billing_address_1);
        $this->addParam('street_address2', $order->billing_address_2);
        $this->addParam('city', $order->billing_city);
        $this->addParam('state', $order->billing_state);
        $this->addParam('zip', $order->billing_postcode);
        $this->addParam('country', $order->billing_country);
        $this->addParam('merchant_order_id', $order->id);
	$this->addParam('x_receipt_link_url', $order->get_checkout_order_received_url() . '&2cpay=approved');
        $redirect .= http_build_query($this->params);
	$order->add_order_note('Order placed and customer redirected to 2Checkout.');
        return array(
                    'result'   => 'success',
                    'redirect' => $redirect
		);
    }
}
