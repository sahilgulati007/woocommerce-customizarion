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

// shipping
//add_filter( 'woocommerce_package_rates', 'bbloomer_woocommerce_tiered_shipping', 999, 2 );
//
//function bbloomer_woocommerce_tiered_shipping( $rates, $package ) {
//    $cart_weight = WC()->cart->cart_contents_weight;
//    if ( isset( $rates['flat_rate:84'] ) ) {
//        $rates['flat_rate:84']->cost = 5 * round ( $cart_weight );
//    }
//    return $rates;
//}

add_filter( 'woocommerce_package_rates', 'bbloomer_woocommerce_tiered_shipping', 9999, 2 );

function bbloomer_woocommerce_tiered_shipping( $rates, $package ) {

    if ( WC()->cart->cart_contents_weight < 1 ) {

        if ( isset( $rates['flat_rate:5'] ) ) unset( $rates['flat_rate:6'], $rates['flat_rate:8'] );

    } elseif ( WC()->cart->cart_contents_weight < 5 ) {

        if ( isset( $rates['flat_rate:5'] ) ) unset( $rates['flat_rate:5'], $rates['flat_rate:8'] );

    } else {

        if ( isset( $rates['flat_rate:5'] ) ) unset( $rates['flat_rate:5'], $rates['flat_rate:6'] );

    }

    return $rates;

}

// Edit Single Product Page and shop page Add to Cart when product already in cart

add_filter( 'woocommerce_product_single_add_to_cart_text', 'bbloomer_custom_add_cart_button_single_product' );

function bbloomer_custom_add_cart_button_single_product( $label ) {

    foreach( WC()->cart->get_cart() as $cart_item_key => $values ) {
        $product = $values['data'];
        if( get_the_ID() == $product->get_id() ) {
            $label = __('Already in Cart. Add again?', 'woocommerce');
        }
    }

    return $label;

}


// Edit Loop Pages Add to Cart

add_filter( 'woocommerce_product_add_to_cart_text', 'bbloomer_custom_add_cart_button_loop', 99, 2 );

function bbloomer_custom_add_cart_button_loop( $label, $product ) {

    if ( $product->get_type() == 'simple' && $product->is_purchasable() && $product->is_in_stock() ) {

        foreach( WC()->cart->get_cart() as $cart_item_key => $values ) {
            $_product = $values['data'];
            if( get_the_ID() == $_product->get_id() ) {
                $label = __('Already in Cart. Add again?', 'woocommerce');
            }
        }

    }

    return $label;

}



// multi vendor
add_action( 'woocommerce_product_options_pricing', 'bbloomer_add_seller_to_products' );

function bbloomer_add_seller_to_products() {

    woocommerce_wp_text_input( array(
            'id' => 'seller',
            'class' => 'short',
            'label' => __( 'Seller', 'woocommerce' )
        )
    );

}

add_action( 'save_post', 'bbloomer_save_seller' );

function bbloomer_save_seller( $product_id ) {

    if ( isset( $_POST['seller'] ) ) {
        update_post_meta( $product_id, 'seller', $_POST['seller'] );
    }

}

add_action( 'woocommerce_single_product_summary', 'bbloomer_display_seller', 25 );

function bbloomer_display_seller() {

    global $product;

    if ( $seller = get_post_meta( $product->get_id(), 'seller', true ) ) {
        echo '
<div class="woocommerce_seller"><b>';
        _e( 'Seller: ', 'woocommerce' );
        echo '<span>' . $seller . '</span>';
        echo '</b></div>
 
';
    }

}


//// #1 Add New Product Type to Select Dropdown
//
//add_filter( 'product_type_selector', 'bbloomer_add_custom_product_type' );
//
//function bbloomer_add_custom_product_type( $types ){
//    $types[ 'custom' ] = 'Custom product';
//    return $types;
//}
//
//// --------------------------
//// #2 Add New Product Type Class
//
//add_action( 'init', 'bbloomer_create_custom_product_type' );
//
//function bbloomer_create_custom_product_type(){
//    class WC_Product_Custom extends WC_Product {
//        public function get_type() {
//            return 'custom';
//        }
//    }
//}
//
//// --------------------------
//// #3 Load New Product Type Class
//
//add_filter( 'woocommerce_product_class', 'bbloomer_woocommerce_product_class', 10, 2 );
//
//function bbloomer_woocommerce_product_class( $classname, $product_type ) {
//    if ( $product_type == 'custom' ) {
//        $classname = 'WC_Product_Custom';
//    }
//    return $classname;
//}

// custom product type in woocommerce
add_action( 'init', 'register_demo_product_type' );

function register_demo_product_type() {

    class WC_Product_Demo extends WC_Product {

        public function __construct( $product ) {
            $this->product_type = 'demo';
            parent::__construct( $product );
        }
    }
}


add_filter( 'product_type_selector', 'add_demo_product_type' );

function add_demo_product_type( $types ){
    $types[ 'demo' ] = __( 'Demo product', 'dm_product' );

    return $types;
}

add_action( 'woocommerce_product_data_panels', 'demo_product_tab_product_tab_content' );

add_filter( 'woocommerce_product_data_tabs', 'demo_product_tab' );

function demo_product_tab( $tabs) {

    $tabs['demo'] = array(
        'label'    => __( 'Demo Product', 'dm_product' ),
        'target' => 'demo_product_options',
        'class'  => 'show_if_demo_product',
    );
    return $tabs;
}

function demo_product_tab_product_tab_content()
{

    ?>
    <div id='demo_product_options' class='panel woocommerce_options_panel'><?php
    ?>
    <div class='options_group'><?php

    woocommerce_wp_text_input(
        array(
            'id' => 'demo_product_info',
            'label' => __('Demo Product Spec', 'dm_product'),
            'placeholder' => '',
            'desc_tip' => 'true',
            'description' => __('Enter Demo product Info.', 'dm_product'),
            'type' => 'text'
        )
    );
        woocommerce_wp_text_input(
            array(
                'id' => 'demo_product_info2',
                'label' => __('Demo Product Spec1', 'dm_product1'),
                'placeholder' => '',
                'desc_tip' => 'true',
                'description' => __('Enter Demo product Info.', 'dm_product1'),
                'type' => 'text'
            )
        );
    ?></div>
    </div><?php
}


add_action( 'woocommerce_process_product_meta', 'save_demo_product_settings' );

function save_demo_product_settings( $post_id ){

    $demo_product_info = $_POST['demo_product_info'];

    if( !empty( $demo_product_info ) ) {

        update_post_meta( $post_id, 'demo_product_info', esc_attr( $demo_product_info ) );
    }
}

add_action( 'woocommerce_single_product_summary', 'demo_product_front' );

function demo_product_front () {
    global $product;

    if ( 'demo' == $product->get_type() ) {
        echo( get_post_meta( $product->get_id(), 'demo_product_info' )[0] );

    }
}

// not working divelery date
// -------------------------------
// 1. Display Checkout Calendar if Shipping Selected

add_action( 'woocommerce_review_order_before_payment', 'bbloomer_echo_acf_date_picker' );

function bbloomer_echo_acf_date_picker( $checkout ) {

    echo '<div id="show-if-shipping" style="display:none"><h3>Delivery Date</h3>';

    woocommerce_form_field( 'delivery_date', array(
        'type'          => 'text',
        'class'         => array('form-row-wide'),
        'id'            => 'datepicker',
        'required'      => true,
        'label'         => __('Select Delivery Date'),
        'placeholder'       => __('Click to open calendar'),
    ));

    echo '</div>';

}

add_action( 'woocommerce_after_checkout_form', 'bbloomer_show_hide_calendar' );

function bbloomer_show_hide_calendar( $available_gateways ) {

    ?>

    <script type="text/javascript">

        function show_calendar( val ) {
            if ( val.match("^flat_rate") || val.match("^free_shipping") ) {
                jQuery('#show-if-shipping').fadeIn();
            } else {
                jQuery('#show-if-shipping').fadeOut();
            }
        }

        jQuery(document).ajaxComplete(function() {
            var val = jQuery('input[name^="shipping_method"]:checked').val();
            show_calendar( val );
        });

    </script>

    <?php

}

add_action( 'woocommerce_checkout_process', 'bbloomer_validate_new_checkout_fields' );

function bbloomer_validate_new_checkout_fields() {

    if ( isset( $_POST['delivery_date'] ) && empty( $_POST['delivery_date'] ) ) wc_add_notice( __( 'Please select the Delivery Date' ), 'error' );

}

// -------------------------------
// 2. Load JQuery Datepicker

add_action( 'woocommerce_after_checkout_form', 'bbloomer_enable_datepicker', 10 );

function bbloomer_enable_datepicker() {

    ?>

    <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>

    <?php

}

// -------------------------------
// 3. Load Calendar Dates

add_action( 'woocommerce_after_checkout_form', 'bbloomer_load_calendar_dates', 20 );

function bbloomer_load_calendar_dates( $available_gateways ) {

    ?>

    <script type="text/javascript">

        jQuery(document).ready(function($) {

            $('#datepicker').click(function() {

                $('#datepicker').datepicker({
                    dateFormat: 'dd-mm-yy',
                    maxDate: "+2m",
                    minDate: 1,
                }).datepicker( "show" );

            });

        });

    </script>

    <?php

}

// -------------------------------
// 4. Save & show date as order meta

add_action( 'woocommerce_checkout_update_order_meta', 'bbloomer_save_date_weight_order' );

function bbloomer_save_date_weight_order( $order_id ) {

    global $woocommerce;

    if ( $_POST['delivery_date'] ) update_post_meta( $order_id, '_delivery_date', esc_attr( $_POST['delivery_date'] ) );

}

add_action( 'woocommerce_admin_order_data_after_billing_address', 'bbloomer_delivery_weight_display_admin_order_meta' );

function bbloomer_delivery_weight_display_admin_order_meta( $order ) {

    echo '<p><strong>Delivery Date:</strong> ' . get_post_meta( $order->get_id(), '_delivery_date', true ) . '</p>';

}

//displaying sale prices in detail
add_filter( 'woocommerce_get_price_html', 'bbloomer_simple_product_price_format', 10, 2 );

function bbloomer_simple_product_price_format( $price, $product ) {

    if ( $product->is_on_sale() && $product->is_type('simple') ) {
        $price = sprintf( __( '<div class="was-now-save"><div class="was">WAS %1$s</div><div class="now">NOW %2$s</div><div class="save">SAVE %3$s</div></div>', 'woocommerce' ), wc_price ( $product->get_regular_price() ), wc_price( $product->get_sale_price() ), wc_price( $product->get_regular_price() - $product->get_sale_price() )  );
    }

    return $price;
}

// custom badge on single product page and display idf its elected in back end
// -----------------------------------------
// 1. Add new checkbox to product edit page (General tab)

add_action( 'woocommerce_product_options_general_product_data', 'bbloomer_add_badge_checkbox_to_products' );

function bbloomer_add_badge_checkbox_to_products() {
    woocommerce_wp_checkbox( array(
            'id' => 'custom_badge',
            'class' => '',
            'label' => 'Show Custom Badge'
        )
    );
}

// -----------------------------------------
// 2. Save checkbox via custom field

add_action( 'save_post', 'bbloomer_save_badge_checkbox_to_post_meta' );

function bbloomer_save_badge_checkbox_to_post_meta( $product_id ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
        return;
    if ( isset( $_POST['custom_badge'] ) ) {
        update_post_meta( $product_id, 'custom_badge', $_POST['custom_badge'] );
    } else delete_post_meta( $product_id, 'custom_badge' );
}

// -----------------------------------------
// 3. Display badge @ single product page if checkbox checked

add_action( 'woocommerce_single_product_summary', 'bbloomer_display_badge_if_checkbox', 6 );

function bbloomer_display_badge_if_checkbox() {
    global $product;
    if ( get_post_meta( $product->get_id(), 'custom_badge', true ) ) {
        echo '
<div class="woocommerce-message">CUSTOM BADGE!</div>
 
';
    }
}

// custom filed ech single in variation
// -----------------------------------------
// 1. Add custom field input @ Product Data > Variations > Single Variation

add_action( 'woocommerce_variation_options_pricing', 'bbloomer_add_custom_field_to_variations', 10, 3 );

function bbloomer_add_custom_field_to_variations( $loop, $variation_data, $variation ) {
    woocommerce_wp_text_input( array(
            'id' => 'custom_field[' . $loop . ']',
            'class' => 'short',
            'label' => __( 'Custom Field', 'woocommerce' ),
            'value' => get_post_meta( $variation->ID, 'custom_field', true )
        )
    );
}

// -----------------------------------------
// 2. Save custom field on product variation save

add_action( 'woocommerce_save_product_variation', 'bbloomer_save_custom_field_variations', 10, 2 );

function bbloomer_save_custom_field_variations( $variation_id, $i ) {
    $custom_field = $_POST['custom_field'][$i];
    if ( ! empty( $custom_field ) ) {
        update_post_meta( $variation_id, 'custom_field', esc_attr( $custom_field ) );
    } else delete_post_meta( $variation_id, 'custom_field' );
}

// -----------------------------------------
// 3. Store custom field value into variation data

add_filter( 'woocommerce_available_variation', 'bbloomer_add_custom_field_variation_data' );

function bbloomer_add_custom_field_variation_data( $variations ) {
    $variations['custom_field'] = '<div class="woocommerce_custom_field">Custom Field: <span>' . get_post_meta( $variations[ 'variation_id' ], 'custom_field', true ) . '</span></div>';
    return $variations;
}

//apply coupon code
add_action( 'woocommerce_before_cart', 'bbloomer_apply_coupon' );

function bbloomer_apply_coupon() {
    $coupon_code = 'freeweek';
    if ( WC()->cart->has_discount( $coupon_code ) ) return;
    WC()->cart->add_discount( $coupon_code );
    wc_print_notices();
}