<?php

/**
 * Plugin Name: infolib Plugin
 * Plugin URI: http://infolib.com/plugin
 * Description: Plugin for woocommerce custom api
 * Version: 1.0.0
 * Author: Balti Ahmed
 * Author URI: https://abalti.com/
 * Text Domain: infolib
 * WC requires at least: 3.0
 * WC tested up to: 5.6.0
 *
 * @package CartFlows
 */

use MailPoetVendor\Doctrine\ORM\Mapping\Id;

defined('ABSPATH') or die('');

class InfolibPlugin
{
    function activate()
    {
        global $wpdb;
        $row = $wpdb->get_results("SELECT order_flag FROM wp_posts'");

        if (empty($row)) {
            $wpdb->query("ALTER TABLE wp_posts ADD order_flag INT(1) NOT NULL DEFAULT 1");
        }
    }

    function deactivate()
    {
    }

    function uninstall()
    {
    }
}

add_action('rest_api_init', 'customEndPointOrder');

function customEndPointOrder()
{
    register_rest_route('/wc/v3/orders-by-flag/', 'search', array(
        'methods' => 'GET',
        'callback' => 'customGetOrder',
        'permission_callback' => function ($request) {
            return is_user_logged_in();
        },

    ));
}


function customGetOrder($params)
{
    //definition des arguments du looop 
    $orders = new WP_Query(array(
        'post_type' => 'shop_order',
        'post_status'    => 'wc-processing'

    ));
    //définition des variables pour la génération du json
    $order_process = array();
    $order_item = array();
    while ($orders->have_posts()) {
        $orders->the_post();
        if ($params['order'] == $orders->posts[0]->order_flag) {
            $wc_order = wc_get_order(get_the_ID());
            foreach ($wc_order->get_items() as $item) {
                array_push($order_item, array(

                    "id" => $item->get_data()['id'],
                    "name" => $item->get_data()['name'],
                    "product_id" => $item->get_data()['product_id'],
                    "variation_id" => $item->get_data()['variation_id'],
                    "quantity" => $item->get_data()['quantity'],
                    "tax_class" => $item->get_data()['tax_class'],
                    "subtotal" => $item->get_data()['subtotal'],
                    "subtotal_tax" => $item->get_data()['subtotal_tax'],
                    "total" => $item->get_data()['total'],
                    "total_tax" => $item->get_data()['total_tax'],
                    "taxes" => $item->get_data()['taxes'],
                    "meta_data" => $item->get_data()['meta_data'],
                    "sku" => $item->get_data()['sku'],
                    "price" => $item->get_data()['price']
                ));
            }
            $wc_order_row = $wc_order->get_data();
            
            array_push($order_process, array(
                'order_flag' => $orders->posts[0]->order_flag,
                'id' => $wc_order->get_id(),
                "parent_id" => $wc_order_row['parent_id'],
                "number" => $wc_order_row['number'],
                "order_key" => $wc_order_row['order_key'],
                "created_via" => $wc_order_row['created_via'],
                "version" => $wc_order_row['version'],
                "status" => $wc_order_row['status'],
                "currency" => $wc_order_row['currency'],
                "date_created" => $wc_order_row['date_created'],
                "date_created_gmt" => $wc_order_row['date_created_gmt'],
                "date_modified" => $wc_order_row['date_modified'],
                "date_modified_gmt" => $wc_order_row['date_modified_gmt'],
                "discount_total" => $wc_order_row['discount_total'],
                "discount_tax" => $wc_order_row['discount_tax'],
                "shipping_total" => $wc_order_row['shipping_total'],
                "shipping_tax" => $wc_order_row['shipping_tax'],
                "cart_tax" => $wc_order_row['cart_tax'],
                "total" => $wc_order_row['total'],
                "total_tax" => $wc_order_row['total_tax'],
                "prices_include_tax" => $wc_order_row['prices_include_tax'],
                "customer_id" => $wc_order_row['customer_id'],
                "customer_ip_address" => $wc_order_row['customer_ip_address'],
                "customer_user_agent" => $wc_order_row['customer_user_agent'],
                "customer_note" => $wc_order_row['customer_note'],
                'billing' => $wc_order_row['billing'],
                'shipping' => $wc_order_row['shipping'],
                "payment_method" => $wc_order_row['payment_method'],
                "payment_method_title" => $wc_order_row['payment_method_title'],
                "transaction_id" => $wc_order_row['transaction_id'],
                "date_paid" => $wc_order_row['date_paid'],
                "date_paid_gmt" => $wc_order_row['date_paid_gmt'],
                "date_completed" => $wc_order_row['date_completed'],
                "date_completed_gmt" => $wc_order_row['date_completed_gmt'],
                "cart_hash" => $wc_order_row['cart_hash'],
                'line_items' => $order_item
            ));
        }
    }


    $customer = get_users();

    $customers = new  WC_Customer(2);

    return $customers->get_data();
}



add_action('rest_api_init', 'customEndPointUpdateOrderFlag');

function customEndPointUpdateOrderFlag()
{
    register_rest_route('/wc/v3/orders-by-flag/', 'search', array(
        'methods' => 'PUT',
        'callback' => 'customUpdateOrderFlag',
        'permission_callback' => function ($request) {
            return is_user_logged_in();
        },

    ));
}


function customUpdateOrderFlag($params)
{


    global $wpdb;
    $row = $wpdb->get_results("SELECT order_flag FROM wp_posts WHERE `wp_posts`.`post_type` = 'shop_order' AND `wp_posts`.`ID` =" . $params['id']);

    if (!empty($row)) {

        $wpdb->query("UPDATE `wp_posts` SET `order_flag` = '" . $params['order'] . "' WHERE `wp_posts`.`ID` =" . $params['id']);


        //definition des arguments du loop 
        $orders = new WP_Query(array(
            'post_type' => 'shop_order',
            'post_status'    => 'wc-processing'

        ));
        //définition des variables pour la génération du json
        $order_process = array();
        $order_item = array();
        while ($orders->have_posts()) {
            $orders->the_post();
            if ($params['order'] == $orders->posts[0]->order_flag) {
                $wc_order = wc_get_order(get_the_ID());
                foreach ($wc_order->get_items() as $item) {
                    array_push($order_item, array(

                        "id" => $item->get_data()['id'],
                        "name" => $item->get_data()['name'],
                        "product_id" => $item->get_data()['product_id'],
                        "variation_id" => $item->get_data()['variation_id'],
                        "quantity" => $item->get_data()['quantity'],
                        "tax_class" => $item->get_data()['tax_class'],
                        "subtotal" => $item->get_data()['subtotal'],
                        "subtotal_tax" => $item->get_data()['subtotal_tax'],
                        "total" => $item->get_data()['total'],
                        "total_tax" => $item->get_data()['total_tax'],
                        "taxes" => $item->get_data()['taxes'],
                        "meta_data" => $item->get_data()['meta_data'],
                        "sku" => $item->get_data()['sku'],
                        "price" => $item->get_data()['price']
                    ));
                }
                $wc_order_row = $wc_order->get_data();
                array_push($order_process, array(
                    'order_flag' => $params['order'],
                    'id' => $wc_order->get_id(),
                    "parent_id" => $wc_order_row['parent_id'],
                    "number" => $wc_order_row['number'],
                    "order_key" => $wc_order_row['order_key'],
                    "created_via" => $wc_order_row['created_via'],
                    "version" => $wc_order_row['version'],
                    "status" => $wc_order_row['status'],
                    "currency" => $wc_order_row['currency'],
                    "date_created" => $wc_order_row['date_created'],
                    "date_created_gmt" => $wc_order_row['date_created_gmt'],
                    "date_modified" => $wc_order_row['date_modified'],
                    "date_modified_gmt" => $wc_order_row['date_modified_gmt'],
                    "discount_total" => $wc_order_row['discount_total'],
                    "discount_tax" => $wc_order_row['discount_tax'],
                    "shipping_total" => $wc_order_row['shipping_total'],
                    "shipping_tax" => $wc_order_row['shipping_tax'],
                    "cart_tax" => $wc_order_row['cart_tax'],
                    "total" => $wc_order_row['total'],
                    "total_tax" => $wc_order_row['total_tax'],
                    "prices_include_tax" => $wc_order_row['prices_include_tax'],
                    "customer_id" => $wc_order_row['customer_id'],
                    "customer_ip_address" => $wc_order_row['customer_ip_address'],
                    "customer_user_agent" => $wc_order_row['customer_user_agent'],
                    "customer_note" => $wc_order_row['customer_note'],
                    'billing' => $wc_order_row['billing'],
                    'shipping' => $wc_order_row['shipping'],
                    "payment_method" => $wc_order_row['payment_method'],
                    "payment_method_title" => $wc_order_row['payment_method_title'],
                    "transaction_id" => $wc_order_row['transaction_id'],
                    "date_paid" => $wc_order_row['date_paid'],
                    "date_paid_gmt" => $wc_order_row['date_paid_gmt'],
                    "date_completed" => $wc_order_row['date_completed'],
                    "date_completed_gmt" => $wc_order_row['date_completed_gmt'],
                    "cart_hash" => $wc_order_row['cart_hash'],
                    'line_items' => $order_item
                ));
            }
        }

        return $order_process;
    } else {
        return new WP_Error('no_posts', __('No post found'), array('status' => 404));
    }
}


if (class_exists('InfolibPlugin')) {
    $infolibPluginn = new InfolibPlugin();
}

//activation
register_activation_hook(__FILE__, array($infolibPluginn, 'activate'));
