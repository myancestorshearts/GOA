<?php
/*
Plugin Name:  {COMPANY_NAME}
Plugin URI:   {COMPANY_URL}
Description:  Sync order with {COMPANY_NAME} for fulfillment. 
Version:      1.0
Author:       {COMPANY_NAME} 
Author URI:   {COMPANY_URL}
License:      GPL2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
Text Domain:  wpb-tutorial
Domain Path:  goa/v1
*/

add_action('rest_api_init', function () {
  register_rest_route( 'goa/v1', '/orders', array(
    'methods' => 'GET',
    'callback' => 'getOrders',
  ));
});
  /**
 * Grab latest post title by an author!
 *
 * @param array $data Options for the function.
 * or null if none.
 */
function getOrders( $data ) {
  if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
    return wp_send_json([
      'result' => 'failure',
      'message' => 'Not authorized'
    ]);
  }
  
  if ($_SERVER['HTTP_AUTHORIZATION'] != '{AUTHORIZATION_KEY}') {
    return wp_send_json([
      'result' => 'failure',
      'message' => 'Not authorized'
    ]);
  }

  try {

    $exported_orders = [];

    $order_args = [
      'numberposts' => isset($_GET['take']) ? $_GET['take'] : 1
    ];

    $created_filters = [];
    $start = 0;
    $end = time();
    if (isset($_GET['start'])) $start = strtotime($_GET['start']);
    if (isset($_GET['end'])) $end = strtotime($_GET['end']); 

    $order_args['date_created'] = $start . '...' . $end;

    $orders = wc_get_orders($order_args);


    // Loop through each WC_Order object
    foreach($orders as $order) {
      if (get_class($order) == Automattic\WooCommerce\Admin\Overrides\OrderRefund::class) {
        
        $exported_order = [];
        $exported_order['reference'] = $order->get_id();
        $exported_order['status'] = $order->get_status();
        $exported_order['type'] = 'REFUND';
        $exported_orders[] = $exported_order;
      }
      else if (get_class($order) == Automattic\WooCommerce\Admin\Overrides\Order::class) {
        $exported_order = [];
        $exported_order['reference'] = $order->get_id();
        $exported_order['status'] = $order->get_status();
        $exported_order['type'] = 'ORDER';
        $exported_order['created'] = $order->get_date_created();
        
        // customer information
        $user = $order->get_user();
        if (isset($user)) {
          $exported_order['customer_full_name'] = $user->display_name;
          $exported_order['customer_email'] = $user->user_email;
        }

        //shipping information
        if ($order->has_shipping_address()) {
          $exported_order['street_1'] = $order->get_shipping_address_1();
          $exported_order['street_2'] = $order->get_shipping_address_2();
          $exported_order['city'] = $order->get_shipping_city();
          $exported_order['postal'] = $order->get_shipping_postcode();
          $exported_order['state'] = $order->get_shipping_state();
          $exported_order['country'] = $order->get_shipping_country();
        }

        //Items Information
        $items = $order->get_items();
        $exported_order['items'] = [];
        foreach($items as $item){
          
          $exported_item = [];
          $exported_item['product_name'] = $item->get_name();
          $exported_item['quantity'] = $item->get_quantity();

          $product = $item->get_product();
          
          if (isset($product)) {
            $exported_item['sku'] = $product->get_sku();
          }
          else $exported_item['sku'] = '';

          $exported_order['items'][] = $exported_item;
        } 

        $exported_orders[] = $exported_order;
      }
    }

    $response = [
      'result' => 'success',
      'data' => [
        'orders' => $exported_orders
      ]
    ];

    wp_send_json($response);
  }
  catch (\Exception $ex) {
    wp_send_json([
      'result' => 'failure',
      'message' => $ex->getMessage()
    ]); 
  }
}


add_action('rest_api_init', function () {
  register_rest_route( 'goa/v1', '/order/complete', array(
    'methods' => 'POST',
    'callback' => 'completeOrder',
  ));
});
  /**
 * Grab latest post title by an author!
 *
 * @param array $data Options for the function.
 * or null if none.
 */
function completeOrder( $data ) {

  if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
    return wp_send_json([
      'result' => 'failure',
      'message' => 'Not authorized'
    ]);
  }
  
  if ($_SERVER['HTTP_AUTHORIZATION'] != '{AUTHORIZATION_KEY}') {
    return wp_send_json([
      'result' => 'failure',
      'message' => 'Not authorized'
    ]);
  }

  if (!isset($_POST['id'])) {
    return wp_send_json([
      'result' => 'failure',
      'message' => 'Invalid id'
    ]);
  }

  $order_id = $_POST['id'];
  $order = wc_get_order($order_id);

  if (!$order) {
    return wp_send_json([
      'result' => 'failure',
      'message' => 'Invalid id'
    ]);
  }

  $order->update_status('completed');

  $response = [
    'result' => 'success'
  ];

  wp_send_json($response);
}
?>
