<?php
function radom_add_admin_menu()
{
    add_menu_page(
        'Radom Settings', // Page title
        'Radom Pay', // Menu title
        'manage_options', // Capability
        'radom-pay-plugin', // Menu slug
        'radom_pay_plugin_options', // Function that handles the page content
        plugins_url('images/logo.png', __FILE__), // Icon URL (optional)
        1 // Position (optional)
    );
}

add_action('admin_menu', 'radom_add_admin_menu');

function radom_pay_plugin_options()
{
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    // Show error/update messages
    settings_errors('radom_pay_plugin_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
    <?php
    // Output security fields for the registered setting 'radom_pay_plugin'
    settings_fields('radom_pay_plugin');
    // Output setting sections and their fields
    do_settings_sections('radom_pay_plugin');
    // Output save settings button
    submit_button('Save Settings');
    ?>
        </form>
    </div>
    <?php
}

function radom_pay_plugin_settings_init()
{
    // Register a new setting for 'radom_pay_plugin' page
    register_setting('radom_pay_plugin', 'radom_pay_plugin');
    // Register a new section in the 'radom_pay_plugin' page
    add_settings_section('radom_pay_plugin_section', __('Crypto Payment Infrastructure for the Internet', 'radom-pay-plugin'), 'radom_pay_plugin_section_callback', 'radom_pay_plugin');
    // Register new fields in the 'radom_pay_plugin_section' section, inside the 'radom_pay_plugin' page
    add_settings_field('radom_pay_plugin_field', __('API Key', 'radom-pay-plugin'), 'radom_pay_plugin_field_callback', 'radom_pay_plugin', 'radom_pay_plugin_section', ['label_for' => 'radom_pay_api_key']);
    add_settings_field('radom_pay_plugin_mainnet_token_field', __('Accepted Payment Methods', 'radom-pay-plugin'), 'radom_pay_plugin_mainnet_token_field_callback', 'radom_pay_plugin', 'radom_pay_plugin_section', ['label_for' => 'radom_pay_mainnet_tokens']);
    add_settings_field('radom_pay_plugin_testnet_token_field', __('Testnet Tokens (WARNING: Ensure ALL testnet tokens are disabled on production sites!)', 'radom-pay-plugin'), 'radom_pay_plugin_testnet_token_field_callback', 'radom_pay_plugin', 'radom_pay_plugin_section', ['label_for' => 'radom_pay_testnet_tokens']);
    add_settings_field('radom_webhook_endpoint_field', __('Webhook Endpoint', 'radom-pay-plugin'), 'radom_webhook_endpoint_field_callback', 'radom_pay_plugin', 'radom_pay_plugin_section', ['label_for' => 'radom_webhook_endpoint', 'class' => 'radom_class']);
    add_settings_field('radom_verification_key_field', __('Verification Key', 'radom-pay-plugin'), 'radom_verification_key_field_callback', 'radom_pay_plugin', 'radom_pay_plugin_section', ['label_for' => 'radom_verification_key']);
    add_settings_field('radom_pay_charge_customer_network_fee', __('Charge Customer Network Fee', 'radom-pay-plugin'), 'radom_pay_charge_customer_network_fee_field_callback', 'radom_pay_plugin', 'radom_pay_plugin_section', ['label_for' => 'radom_pay_charge_customer_network_fee']);
    add_settings_field('radom_pay_plugin_credit_link_field', __('Show Credit Link', 'radom-pay-plugin'), 'radom_pay_plugin_credit_link_field_callback', 'radom_pay_plugin', 'radom_pay_plugin_section', ['label_for' => 'show_credit_link']);
}

add_action('admin_init', 'radom_pay_plugin_settings_init');

function radom_pay_plugin_section_callback($args)
{
    ?>
    <p id="<?php echo esc_attr($args['id']); ?>"><?php esc_html_e('Before getting started please click ', 'radom-pay-plugin'); ?>
        <a href="https://dashboard.radom.network/profile?tab=Profile">here</a> to set up your seller profile which will
        be displayed to customers at checkout.</p>
    <br>
    <p id="<?php echo esc_attr($args['id']); ?>"><?php esc_html_e('Enter your API key, this can be generated ', 'radom-pay-plugin'); ?>
        <a href="https://dashboard.radom.network/developers?tab=API%20tokens">here.</a></p>
    <?php
}

function radom_pay_plugin_field_callback($args)
{
    $options = get_option('radom_pay_plugin');
    $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : '';
    ?>
    <input type="text" id="<?php echo esc_attr($args['label_for']); ?>"
           name="radom_pay_plugin[<?php echo esc_attr($args['label_for']); ?>]" value="<?php echo esc_attr($value); ?>">
    <?php
}

function radom_pay_plugin_mainnet_token_field_callback($args)
{
    $options = get_option('radom_pay_plugin');
    $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : [];
    $tokensAndNetworks = [
        'Bitcoin' => 'Bitcoin',
        'ETH' => 'Ethereum',
        'USDT (Ethereum)' => 'Ethereum_0xdAC17F958D2ee523a2206206994597C13D831ec7',
        'USDC (Ethereum)' => 'Ethereum_0xa0b86991c6218b36c1d19d4a2e9eb0ce3606eb48',
        'BNB' => 'BNB',
        'USDT (BNB)' => 'BNB_0x55d398326f99059ff775485246999027b3197955',
        'USDC (BNB)' => 'BNB_0x8ac76a51cc950d9822d68b83fe1ad97b32cd580d',
        'BUSD (BNB)' => 'BNB_0xe9e7cea3dedca5984780bafc599bd69add087d56',
        'Polygon' => 'Polygon',
        'USDT (Polygon)' => 'Polygon_0xc2132d05d31c914a87c6611c10748aeb04b58e8f',
        'USDC (Polygon)' => 'Polygon_0x3c499c542cEF5E3811e1192ce70d8cC03d5c3359',
        'USDC.e (Polygon)' => 'Polygon_0x2791bca1f2de4661ed88a30c99a7a9449aa84174',
        'Solana' => 'Solana',
        'USDC (Solana)' => 'Solana_EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v',
        'USDT (Solana)' => 'Solana_Es9vMFrzaCERmJfrF4H2FYD4KCoNkY11McCe8BenwNYB',
        'ETH (Base)' => 'Base',
        'USDC (Base)' => 'Base_0x833589fcd6edb6e08f4c7c32d4f71b54bda02913',
        'ETH (Arbitrum)' => 'Arbitrum',
        'USDC (Arbitrum)' => 'Arbitrum_0xaf88d065e77c8cc2239327c5edb3a432268e5831',
        'USDT (Arbitrum)' => 'Arbitrum_0xfd086bc7cd5c481dcc9c85ebe478a1c0b69fcbb9',
        'AVAX' => 'Avalanche',
        'USDT (Avalanche)' => 'Avalanche_0x9702230a8ea53601f5cd2dc00fdbc13d4df4a8c7',
        'TRX' => 'Tron',
        'USDT (TRON)' => 'Tron_TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
        'DOT' => 'Polkadot'
    ];
    ?>
    <select id="<?php echo esc_attr($args['label_for']); ?>"
            name="radom_pay_plugin[<?php echo esc_attr($args['label_for']); ?>][]" multiple>
        <?php
        foreach ($tokensAndNetworks as $tokenNetwork => $valueToStore) {
            $selected = in_array($valueToStore, $value) ? 'selected' : '';
            echo '<option value="' . esc_attr($valueToStore) . '" ' . $selected . '>' . esc_html($tokenNetwork) . '</option>';
        }
        ?>
    </select>
    <?php
}

function radom_pay_plugin_testnet_token_field_callback($args)
{
    $options = get_option('radom_pay_plugin');
    $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : [];
    $tokensAndNetworks = [
        'Bitcoin (BTC Testnet)' => 'BitcoinTestnet',
        'Ethereum (Sepolia Testnet)' => 'SepoliaTestnet',
        'USDT (Sepolia Testnet)' => 'SepoliaTestnet_0xE50d86c6dE38F9754f6777d2925377564Bf79482',
        'USDC (Sepolia Testnet)' => 'SepoliaTestnet_0xa4fCE8264370437e718aE207805b4e6233638b9E',
        'BNB (BNB Testnet)' => 'BNBTestnet',
        'Polygon (Mumbai Testnet)' => 'PolygonTestnet',
        'USDT (Mumbai Testnet)' => 'PolygonTestnet_0x70BE8802e2F3C6652B7e0814B478f66Ec52d9d88',
        'USDC (Mumbai Testnet)' => 'PolygonTestnet_0x8f8b1972eea072C3C228EbE8f9FEADe03927D70F',
    ];
    ?>
    <select id="<?php echo esc_attr($args['label_for']); ?>"
            name="radom_pay_plugin[<?php echo esc_attr($args['label_for']); ?>][]" multiple>
        <?php
        foreach ($tokensAndNetworks as $tokenNetwork => $valueToStore) {
            $selected = in_array($valueToStore, $value) ? 'selected' : '';
            echo '<option value="' . esc_attr($valueToStore) . '" ' . $selected . '>' . esc_html($tokenNetwork) . '</option>';
        }
        ?>
    </select>
    <?php
}

function radom_webhook_endpoint_field_callback($args)
{
    $webhook_endpoint = get_rest_url(null, '/radom/v1/webhook'); ?>
    <input id="<?php echo esc_attr($args['label_for']); ?>" type="text" value="<?php echo $webhook_endpoint; ?>"
           readonly/>
    <button id="copy_to_clipboard">Copy</button>

    <script>
        document.getElementById('copy_to_clipboard').addEventListener('click', function () {
            var copyText = document.getElementById("<?php echo esc_attr($args['label_for']); ?>");
            copyText.select();
            document.execCommand("copy");
            alert("Copied the text: " + copyText.value);
        });
    </script>
    <?php
}

function radom_verification_key_field_callback($args)
{
    $options = get_option('radom_pay_plugin');
    $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : '';
    ?>
    <input type="text" id="<?php echo esc_attr($args['label_for']); ?>"
           name="radom_pay_plugin[<?php echo esc_attr($args['label_for']); ?>]" value="<?php echo esc_attr($value); ?>">
    <?php
}

function radom_pay_charge_customer_network_fee_field_callback($args)
{
    $options = get_option('radom_pay_plugin');
    $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : 'yes';
    ?>
    <select id="<?php echo esc_attr($args['label_for']); ?>"
            name="radom_pay_plugin[<?php echo esc_attr($args['label_for']); ?>]">
        <option value="yes" <?php selected($value, 'yes'); ?>><?php _e('Yes', 'radom-pay-plugin'); ?></option>
        <option value="no" <?php selected($value, 'no'); ?>><?php _e('No', 'radom-pay-plugin'); ?></option>
    </select>
    <?php
}

function radom_pay_plugin_credit_link_field_callback($args)
{
    $options = get_option('radom_pay_plugin');
    $value = isset($options[$args['label_for']]) ? $options[$args['label_for']] : 'yes';
    ?>
    <input type="checkbox" id="<?php echo esc_attr($args['label_for']); ?>"
           name="radom_pay_plugin[<?php echo esc_attr($args['label_for']); ?>]" value="yes" <?php checked($value, 'yes'); ?>>
    <?php
}
?>
