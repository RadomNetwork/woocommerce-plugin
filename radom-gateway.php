<?php
if (!defined("ABSPATH")) {
    exit(); // Exit if accessed directly
}

class WC_Radom_Gateway extends WC_Payment_Gateway
{
    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {
        $this->id = "radom_gateway";
        $this->icon = plugins_url("images/checkouticon.svg", __FILE__);
        $this->has_fields = false;
        $this->method_title = __("Radom", "woocommerce");
        $this->method_description = __("Payments using crypto via Radom Pay.", "woocommerce");
        $this->title = __("Pay with Crypto", "woocommerce"); // Add this line
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();
    
        // Save settings
        add_action(
            "woocommerce_update_options_payment_gateways_" . $this->id, [
                $this,
                "process_admin_options",
            ]
        );
    }    

    /**
     * Initialise Gateway Settings Form Fields
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            "enabled" => [
                "title" => __("Enable/Disable", "woocommerce"),
                "type" => "checkbox",
                "label" => __("Enable Radom Payment", "woocommerce"),
                "default" => "yes",
            ],
            "charge_customer_network_fee" => [
                "title" => __("Charge Customer Network Fee", "woocommerce"),
                "type" => "select",
                "options" => [
                    "yes" => __("Yes", "woocommerce"),
                    "no" => __("No", "woocommerce"),
                ],
                "default" => "yes",
            ],
            "show_credit_link" => [
                "title" => __("Show Credit Link", "woocommerce"),
                "type" => "checkbox",
                "label" => __("Show 'Powered by Radom' link in footer", "woocommerce"),
                "default" => "yes",
            ],
        ];
    }    

    /**
     * Process the payment and return the result.
     *
     * @param  int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
    
        $options = get_option("radom_pay_plugin");
        $api_key = isset($options["radom_pay_api_key"])
            ? $options["radom_pay_api_key"]
            : "";
    
        $stored_mainnet_tokens = isset($options["radom_pay_mainnet_tokens"])
            ? $options["radom_pay_mainnet_tokens"]
            : [];
        $stored_testnet_tokens = isset($options["radom_pay_testnet_tokens"])
            ? $options["radom_pay_testnet_tokens"]
            : [];
    
        $charge_customer_network_fee = isset($options["radom_pay_charge_customer_network_fee"]) && $options["radom_pay_charge_customer_network_fee"] === 'no' ? false : true;
    
        $methods = [];
    
        foreach (
            array_merge($stored_mainnet_tokens, $stored_testnet_tokens)
            as $network_and_token
        ) {
            $parts = explode("_", $network_and_token);
            if ($parts[0] === "") {
                array_shift($parts); // remove leading empty element if present
            }
            $network = $parts[0];
            $token = isset($parts[1]) ? $parts[1] : null;
            $method = ["network" => $network];
            if ($token) {
                $method["token"] = $token;
            }
            $methods[] = $method;
        }
    
        $payload = [
            //'cancelUrl' => $this->get_return_url( $order ),
            "successUrl" => $order->get_checkout_order_received_url(),
            "currency" => $order->get_currency(),
            "expiresAt" => time() + 60 * 60, // current time + 60 minutes
            "gateway" => ["managed" => ["methods" => $methods]],
            "lineItems" => $this->get_line_items($order),
            "chargeCustomerNetworkFee" => $charge_customer_network_fee,
            "metadata" => [
                ["key" => "wp_order_id", "value" => strval($order->get_id())],
            ],
        ];
    
        error_log("Payload: " . print_r($payload, true));
    
        // Create a Radom checkout session
        $response = wp_remote_post(
            "https://api.radom.network/checkout_session",
            [
                "headers" => [
                    "Authorization" => $api_key,
                    "Content-Type" => "application/json",
                ],
                "body" => json_encode($payload),
            ]
        );
    
        error_log(print_r($response, true));
    
        if (is_wp_error($response)) {
            wc_add_notice(
                __("Payment error:", "woothemes") .
                " " .
                $response->get_error_message(),
                "error"
            );
            return;
        }
    
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        $checkout_url = $response_body["checkoutSessionUrl"];
    
        update_post_meta(
            $order_id,
            "radom_checkout_session_id",
            $response_body["checkoutSessionId"]
        );
        error_log(print_r($response, true));
    
        return [
            "result" => "success",
            "redirect" => $checkout_url,
        ];
    }    

    private function get_line_items($order)
    {
        $line_items = [];
        foreach ($order->get_items() as $item_id => $item_data) {
            // Get item details
            $product = $item_data->get_product();
            $product_name = $product->get_name();
            $item_total = $item_data->get_total();
            $item_description = $product->get_description();
            $item_currency = $order->get_currency();
            $item_quantity = $item_data->get_quantity();
            $item_price = $item_total / $item_quantity;
    
            // If the product is a subscription product
            $chargingIntervalSeconds = 0;
            if (class_exists("WC_Subscriptions_Product") && WC_Subscriptions_Product::is_subscription($product)) {
                // Get the subscription period and interval
                $subscription_period = WC_Subscriptions_Product::get_period($product);
                $subscription_interval = WC_Subscriptions_Product::get_interval($product);
    
                // Convert period and interval to seconds (assuming period is either 'week', 'month', 'year')
                switch ($subscription_period) {
                case "week":
                    $chargingIntervalSeconds = $subscription_interval * 7 * 24 * 60 * 60;
                    break;
                case "month":
                    $chargingIntervalSeconds = $subscription_interval * 30 * 24 * 60 * 60;
                    break;
                case "year":
                    $chargingIntervalSeconds = $subscription_interval * 365 * 24 * 60 * 60;
                    break;
                }
            }
    
            // Add line items according to the quantity
            for ($i = 0; $i < $item_quantity; $i++) {
                $line_items[] = [
                "itemData" => [
                "chargingIntervalSeconds" => $chargingIntervalSeconds,
                "currency" => $item_currency,
                "description" => $item_description,
                "isMetered" => false,
                "name" => $product_name,
                "price" => $item_price,
                ],
                ];
            }
        }
    
        // Add shipping cost as a separate line item
        $shipping_total = $order->get_shipping_total();
        if ($shipping_total > 0) {
            $line_items[] = [
            "itemData" => [
            "chargingIntervalSeconds" => 0,
            "currency" => $order->get_currency(),
            "description" => __("Shipping", "woocommerce"),
            "isMetered" => false,
            "name" => __("Shipping", "woocommerce"),
            "price" => $shipping_total,
            ],
            ];
        }
    
        return $line_items;
    }    
}

/**
 * Add the gateway to WooCommerce
 */
function add_radom_gateway($methods)
{
    $methods[] = "WC_Radom_Gateway";
    return $methods;
}

add_filter("woocommerce_payment_gateways", "add_radom_gateway");
