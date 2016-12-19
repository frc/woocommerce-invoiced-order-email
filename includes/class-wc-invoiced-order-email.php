<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * A custom Invoiced Order WooCommerce Email class
 *
 * @since 0.1
 * @extends \WC_Email
 */
class WC_Invoiced_Order_Email extends WC_Email {
    /**
     * Set email defaults
     *
     * @since 0.1
     */
    public function __construct() {
        // Handle localisation
        $this->load_plugin_textdomain();
        add_action( 'init', array( $this, 'load_localisation' ), 0 );

        // set ID, this simply needs to be a unique name
        $this->id = 'wc_invoiced_order';
        // this is the title in WooCommerce Email settings
        $this->title = __('Invoiced Order', 'woocommerce-invoiced-order-email');
        // this is the description in WooCommerce email settings
        $this->description = __('Invoiced Order Notification emails are sent when a customer places an order with billing type cheque', 'woocommerce-invoiced-order-email');
        // these are the default heading and subject lines that can be overridden using the settings
        $this->heading = __('Invoiced Order', 'woocommerce-invoiced-order-email');
        $this->subject = __('Invoiced Order', 'woocommerce-invoiced-order-email');
        // these define the locations of the templates that this email should use, we'll just use the new order template since this email is similar
        $this->template_html  = 'emails/admin-invoiced-order.php';
        $this->template_plain = 'emails/plain/admin-invoiced-order.php';
        // Trigger on new paid orders
        add_action( 'woocommerce_order_status_pending_to_processing_notification', array( $this, 'trigger' ) );
        add_action( 'woocommerce_order_status_pending_to_completed_notification', array( $this, 'trigger' ) );
        add_action( 'woocommerce_order_status_pending_to_on-hold_notification', array( $this, 'trigger' ) );
        add_action( 'woocommerce_order_status_failed_to_processing_notification', array( $this, 'trigger' ) );
        add_action( 'woocommerce_order_status_failed_to_completed_notification', array( $this, 'trigger' ) );
        add_action( 'woocommerce_order_status_failed_to_on-hold_notification', array( $this, 'trigger' ) );
        // Call parent constructor to load any other defaults not explicity defined here
        parent::__construct();
        // this sets the recipient to the settings defined below in init_form_fields()
        $this->recipient = $this->get_option( 'recipient' );
        // if none was entered, just use the WP admin email as a fallback
        if ( ! $this->recipient )
            $this->recipient = get_option( 'admin_email' );
    }
    /**
     * Determine if the email should actually be sent and setup email merge variables
     *
     * @since 0.1
     * @param int $order_id
     */
    public function trigger( $order_id ) {
        // bail if no order ID is present
        if ( ! $order_id )
            return;
        // setup order object
        $this->object = new WC_Order( $order_id );
        // bail if billing method is not invoiced
        error_log(print_r( $this->object->payment_method, true ));
        if ( ! in_array( $this->object->payment_method, array( 'cheque' ) ) )
            return;
        // replace variables in the subject/headings
        $this->find[] = '{order_date}';
        $this->replace[] = date_i18n( woocommerce_date_format(), strtotime( $this->object->order_date ) );
        $this->find[] = '{order_number}';
        $this->replace[] = $this->object->get_order_number();
        if ( ! $this->is_enabled() || ! $this->get_recipient() )
            return;
        // woohoo, send the email!
        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
    }
    /**
     * get_content_html function.
     *
     * @since 0.1
     * @return string
     */
    public function get_content_html() {
        ob_start();
        woocommerce_get_template( $this->template_html, array(
            'order'         => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => true,
            'plain_text'    => false,
            'email'         => ''
        ) );
        return ob_get_clean();
    }
    /**
     * get_content_plain function.
     *
     * @since 0.1
     * @return string
     */
    public function get_content_plain() {
        ob_start();
        woocommerce_get_template( $this->template_plain, array(
            'order'         => $this->object,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => true,
            'plain_text'    => false,
            'email'         => ''
        ) );
        return ob_get_clean();
    }
    /**
     * Initialize Settings Form Fields
     *
     * @since 2.0
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled'    => array(
                'title'   => 'Enable/Disable',
                'type'    => 'checkbox',
                'label'   => __('Enable this email notification', 'woocommerce' ),
                'default' => 'yes'
            ),
            'recipient'  => array(
                'title'       => 'Recipient(s)',
                'type'        => 'text',
                'description' => sprintf( 'Enter recipients (comma separated) for this email. Defaults to <code>%s</code>.', esc_attr( get_option( 'admin_email' ) ) ),
                'placeholder' => '',
                'default'     => ''
            ),
            'subject'    => array(
                'title'       => 'Subject',
                'type'        => 'text',
                'description' => sprintf( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', $this->subject ),
                'placeholder' => '',
                'default'     => ''
            ),
            'heading'    => array(
                'title'       => 'Email Heading',
                'type'        => 'text',
                'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.' ), $this->heading ),
                'placeholder' => '',
                'default'     => ''
            ),
            'email_type' => array(
                'title'       => 'Email type',
                'type'        => 'select',
                'description' => 'Choose which format of email to send.',
                'default'     => 'html',
                'class'       => 'email_type',
                'options'     => array(
                    'plain'     => __( 'Plain text', 'woocommerce' ),
                    'html'      => __( 'HTML', 'woocommerce' ),
                    'multipart' => __( 'Multipart', 'woocommerce' ),
                )
            )
        );
    }

    public function load_localisation () {
        load_plugin_textdomain( 'woocommerce-invoiced-order-email', false, 'woocommerce-invoiced-order-email/languages/' );
    } 

    public function load_plugin_textdomain () {
        $domain = 'woocommerce-invoiced-order-email';

        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );
        load_textdomain( $domain, WP_PLUGIN_DIR . '/' . $domain . '/languages/' . $domain . '_' . $locale . '.mo' );
        load_plugin_textdomain( $domain, false, 'woocommerce-invoiced-order-email/languages/' );
    } 

} // end \WC_Invoiced_Order_Email class