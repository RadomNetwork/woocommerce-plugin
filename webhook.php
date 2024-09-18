<?php
/**
 * Radom Webhook Handler
 */

// Include the necessary WP functions
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/wp-includes/rest-api.php';

function simplified_radom_webhook_handler(WP_REST_Request $request)
{
	// Get the webhook data from the request
	$webhook_data = $request->get_json_params();

	// Ensure the necessary data is present
	if (!isset($webhook_data['eventType'], $webhook_data['eventData'], $webhook_data['radomData'])) {
		return new WP_Error('missing_data', 'Invalid webhook data', array(
			'status' => 400
		));
	}

	// Only handle 'managedPayment' events
	if ($webhook_data['eventType'] !== 'managedPayment') {
		return new WP_Error('invalid_event_type', 'Event type not supported', array(
			'status' => 400
		));
	}

	// Verify the verification key
	$incoming_key = $request->get_header('radom-verification-key');
	$options = get_option('radom_pay_plugin');
	$stored_key = isset($options['radom_verification_key']) ? $options['radom_verification_key'] : '';

	if (!$incoming_key || $incoming_key !== $stored_key) {
		return new WP_Error('invalid_verification_key', 'Invalid verification key', array(
			'status' => 403
		));
	}

	// Get the checkout session ID from the webhook data
	$checkout_session_id = $webhook_data['radomData']['checkoutSession']['checkoutSessionId'];

	// Look up the order by the checkout session ID
	$order_args = array(
		'post_type' => 'shop_order',
		'post_status' => 'any',
		'posts_per_page' => -1,
		'meta_query' => array(
			array(
				'key' => 'radom_checkout_session_id',
				'value' => $checkout_session_id,
				'compare' => '='
			)
		)
	);
	$orders = get_posts($order_args);

	if (!empty($orders)) {
		$order = wc_get_order($orders[0]->ID);

		// Update the order status to 'processing'
		$order->update_status('processing', __('Order status updated by the simplified webhook.'), true);
	} else {
		return new WP_Error('invalid_checkout_id', 'Could not find order with checkout session ID ' . $checkout_session_id, array(
			'status' => 400));
		error_log('No orders found.');
	}

	// Respond with a 200 OK
	return new WP_REST_Response('Webhook processed successfully', 200);
}

add_action('rest_api_init', function () {
	register_rest_route('radom/v1', '/webhook', array(
		'methods' => 'POST',
		'callback' => 'simplified_radom_webhook_handler'
	));
});
