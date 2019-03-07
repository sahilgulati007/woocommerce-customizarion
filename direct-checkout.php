<?php
/**
 * Plugin Name: WooCommerce customization
 * Plugin URI: http://yourdomain.com/
 * Description: woocommerce custommization .
 * Version: 1.0.0
 * Author: Sahil Gulati
 * Author URI: http://yourdomain.com/
 * Developer: Your Name
 * Developer URI: http://yourdomain.com/
 * Text Domain: woocommerce-extension
 * Domain Path: /languages
 *
 * Woo: 12345:342928dfsfhsf8429842374wdf4234sfd
 * WC requires at least: 2.2
 * WC tested up to: 2.3
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// display cart on checkout
add_action( 'woocommerce_before_checkout_form', 'sg_cart_on_checkout_page_only', 5 );

function sg_cart_on_checkout_page_only() {

    if ( is_wc_endpoint_url( 'order-received' ) ) return;



    echo do_shortcode('[woocommerce_cart]');

}

//  return to home if cart is empty

add_action( 'template_redirect', 'sg_redirect_empty_cart_checkout_to_home' );

function sg_redirect_empty_cart_checkout_to_home() {
    if ( is_cart() && is_checkout() && 0 == WC()->cart->get_cart_contents_count() && ! is_wc_endpoint_url( 'order-pay' ) && ! is_wc_endpoint_url( 'order-received' ) ) {
        wp_safe_redirect( home_url() );
        exit;
    }
}

// sort using stock
add_action( 'woocommerce_product_query', 'sg_sort_by_stock_status_then_alpha', 999 );

function sg_sort_by_stock_status_then_alpha( $query ) {
    if ( is_admin() ) return;
    $query->set( 'meta_key', '_stock_status' );
    $query->set( 'orderby', array( 'meta_value' => 'ASC' ) );
}

add_filter('woocommerce_shortcode_products_query', 'sg_sort_by_stock_status_shortcode', 999, 3);

function sg_sort_by_stock_status_shortcode( $args, $atts, $type ) {
    if ( $atts['orderby'] == "stock" ) {
        $args['orderby']  = array( 'meta_value' => 'ASC' );
        $args['meta_key'] = '_stock_status';
    }
    return $args;
}

// remove link of product in cart line
add_filter( 'woocommerce_cart_item_permalink', '__return_null' );

add_action( 'woocommerce_single_product_summary', 'sg_echo_short_desc_if_empty', 21 );


// add custom short decsription
function sg_echo_short_desc_if_empty() {
    global $post;
    if ( empty ( $post->post_excerpt  ) ) {
        $post_excerpt = '<p class="default-short-desc">';
        $post_excerpt .= 'This is the default, global, short description.<br>It will show if <b>no short description has been entered!</b>';
        $post_excerpt .= '</p>';
        echo $post_excerpt;
    }
}


// sale badage with discount percentage
add_action( 'woocommerce_before_shop_loop_item_title', 'sg_show_sale_percentage_loop', 25 );

function sg_show_sale_percentage_loop() {
    global $product;
    if ( ! $product->is_on_sale() ) return;
    if ( $product->is_type( 'simple' ) ) {
        $max_percentage = ( ( $product->get_regular_price() - $product->get_sale_price() ) / $product->get_regular_price() ) * 100;
    } elseif ( $product->is_type( 'variable' ) ) {
        $max_percentage = 0;
        foreach ( $product->get_children() as $child_id ) {
            $variation = wc_get_product( $child_id );
            $price = $variation->get_regular_price();
            $sale = $variation->get_sale_price();
            if ( $price != 0 && ! empty( $sale ) ) $percentage = ( $price - $sale ) / $price * 100;
            if ( $percentage > $max_percentage ) {
                $max_percentage = $percentage;
            }
        }
    }
    if ( $max_percentage > 0 ) echo "<div class='sale-perc'>-" . round($max_percentage) . "%</div>";
}


// privacy policy on register
add_action( 'woocommerce_register_form', 'sg_add_registration_privacy_policy', 11 );

function sg_add_registration_privacy_policy() {

    woocommerce_form_field( 'privacy_policy_reg', array(
        'type'          => 'checkbox',
        'class'         => array('form-row privacy'),
        'label_class'   => array('woocommerce-form__label woocommerce-form__label-for-checkbox checkbox'),
        'input_class'   => array('woocommerce-form__input woocommerce-form__input-checkbox input-checkbox'),
        'required'      => true,
        'label'         => 'I\'ve read and accept the <a href="/privacy-policy">Privacy Policy</a>',
    ));

}

// Show error if user does not tick

add_filter( 'woocommerce_registration_errors', 'sg_validate_privacy_registration', 10, 3 );

function sg_validate_privacy_registration( $errors, $username, $email ) {
    if ( ! is_checkout() ) {
        if ( ! (int) isset( $_POST['privacy_policy_reg'] ) ) {
            $errors->add( 'privacy_policy_reg_error', __( 'Privacy Policy consent is required!', 'woocommerce' ) );
        }
    }
    return $errors;
}

// privacy policy on checkout
add_action( 'woocommerce_review_order_before_submit', 'sg_add_checkout_privacy_policy', 9 );

function sg_add_checkout_privacy_policy() {

    woocommerce_form_field( 'privacy_policy', array(
        'type'          => 'checkbox',
        'class'         => array('form-row privacy'),
        'label_class'   => array('woocommerce-form__label woocommerce-form__label-for-checkbox checkbox'),
        'input_class'   => array('woocommerce-form__input woocommerce-form__input-checkbox input-checkbox'),
        'required'      => true,
        'label'         => 'I\'ve read and accept the <a href="/privacy-policy">Privacy Policy</a>',
    ));

}

// Show notice if customer does not tick

add_action( 'woocommerce_checkout_process', 'sg_not_approved_privacy' );

function sg_not_approved_privacy() {
    if ( ! (int) isset( $_POST['privacy_policy'] ) ) {
        wc_add_notice( __( 'Please acknowledge the Privacy Policy' ), 'error' );
    }
}