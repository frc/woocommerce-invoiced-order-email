<?php
/**
 * Plugin Name: WooCommerce Custom Invoiced Order Email
 * Description: Plugin for adding a custom WooCommerce email that sends customer service an email when an order is received with billing type cheque
 * Author: Ahti Nurminen, Frantic
 * Author URI: http://www.frantic.com
 * Version: 0.1
 * Text Domain: wc-invoiced-order
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
 
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 *  Add a custom email to the list of emails WooCommerce should load
 *
 * @since 0.1
 * @param array $email_classes available email classes
 * @return array filtered available email classes
 */
function add_invoiced_order_woocommerce_email( $email_classes ) {
 
    // include the custom email class
    require( 'includes/class-wc-invoiced-order-email.php' );
 


    // add the email class to the list of email classes that WooCommerce loads
    $email_classes['WC_Invoiced_Order_Email'] = new WC_Invoiced_Order_Email();
 
    return $email_classes;
 
}
add_filter( 'woocommerce_email_classes', 'add_invoiced_order_woocommerce_email' );
