<?php
/*
Plugin Name: Print Management with Biblio Dispatch
Description: The Print Management with Biblio Dispatch plugin streamlines print services by enabling user registration and login.With seamless integration into your WordPress site, it enhances order management and improves efficiency for your print service operations.
Version: 1.2
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
if(!defined('ORDER_DISPATCH_URL')) {
    define('ORDER_DISPATCH_URL','https://bibliodispatch.com/authorsuite/webhookOrders');
}
if(!defined('WEBHOOK_URL')) {
    define('WEBHOOK_URL',get_site_url().'/wp-json/wc/v3/webhooks');

}
if(!defined('AUTH_URL')) {
    define('AUTH_URL',get_site_url()."/wp-json/wc/v3/products");
}
if(!defined('REGISTER_URL')) {
    define('REGISTER_URL',"https://bibliodispatch.com/authorsuite/wordpressAuth");
}
// Add a "Settings" link to the plugin action links
function wc_api_key_check_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=wc-api-key-check-settings') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_api_key_check_plugin_action_links');

// Add the settings page to the options page (not in a separate menu)
function wc_api_key_check_add_settings_page() {
    add_options_page(
        'Print Management Biblio Dispatch Plugin Settings',
        'Print Management Biblio Dispatch',
        'manage_options',
        'wc-api-key-check-settings',
        'wc_api_key_check_settings_page'
    );
}
add_action('admin_menu', 'wc_api_key_check_add_settings_page');

// Enqueue the necessary scripts
function wc_api_key_check_enqueue_custom_styles() {
    $toastr_css_version = filemtime(plugin_dir_path(__FILE__) . 'css/toastr.min.css');
    $toastr_js_version = filemtime(plugin_dir_path(__FILE__) . 'js/toastr.min.js');
    $custom_style_version = filemtime(plugin_dir_path(__FILE__) . 'css/style.css');

    // Enqueue Toastr CSS
    wp_enqueue_style('toastr-css', plugin_dir_url(__FILE__) . 'css/toastr.min.css', array(), $toastr_css_version);
    
    // Enqueue Toastr JS with async and defer attributes
    wp_enqueue_script('toastr-js', plugin_dir_url(__FILE__) . 'js/toastr.min.js', array('jquery'), $toastr_js_version, true);
    wp_script_add_data('toastr-js', 'async', true);
    wp_script_add_data('toastr-js', 'defer', true);

    wp_enqueue_script('custom-script', plugin_dir_url(__FILE__) . 'js/custom-script.js', array('jquery'), '1.0.0', true);
    
    // Localize script to pass PHP variables to JavaScript
    wp_localize_script('toastr-js', 'wcApiVars', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'register_url' => esc_url(REGISTER_URL),
        'api_keys_url' => esc_url(admin_url('admin.php?page=wc-settings&tab=advanced&section=keys'))
    ]);

    // Enqueue your custom styles or scripts if needed
    wp_enqueue_style('wc-api-custom-style', plugin_dir_url(__FILE__) . 'css/style.css', array(), $custom_style_version);
}
add_action('admin_enqueue_scripts', 'wc_api_key_check_enqueue_custom_styles');



//--------Prompt to user for configuration of plugin-------
add_action('admin_notices', 'check_plugin_configuration_prompt');

function check_plugin_configuration_prompt() {
    // Fetch the saved options (replace 'plugin_option_key' with the actual option keys)
    $consumer_key = get_option('plugin_consumer_key');
    $consumer_secret = get_option('plugin_consumer_secret');
    $store_name = get_option('plugin_store_name');
    $site_url = get_option('plugin_site_url');
    
    // If any configuration is missing, display a notice
    if (!$consumer_key || !$consumer_secret || !$store_name || !$site_url) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p><strong>Plugin is activated but not fully configured. Please configure the plugin</p>';
        echo '</div>';
    }
}



//---------------Save Data Starts----------------
add_action('wp_ajax_wc_api_key_check_save_data', 'wc_api_key_check_save_data');
add_action('wp_ajax_nopriv_wc_api_key_check_save_data', 'wc_api_key_check_save_data');

function wc_api_key_check_save_data() {
    
    if (!isset($_POST['_wc_api_nonce'])) {
        wp_send_json_error(['message' => 'Nonce is missing.']);
        return;
    }
    
    // Unsplash the nonce value
    $nonce = sanitize_text_field(wp_unslash($_POST['_wc_api_nonce']));
    
    // Verify the nonce
    if (!wp_verify_nonce($nonce, 'wc_api_key_check_save_data')) {
        wp_send_json_error(['message' => 'Invalid nonce - nonce has already been used or is invalid.']);
        return;
    }
    
    global $wpdb;

    // Get the current user ID
    $user_id = get_current_user_id();
    $response = array();

    $consumer_key = isset($_POST['consumer_key']) ? sanitize_text_field(wp_unslash($_POST['consumer_key'])) : '';
    $consumer_secret = isset($_POST['consumer_secret']) ? sanitize_text_field(wp_unslash($_POST['consumer_secret'])) : '';
    $store_name = isset($_POST['store_name']) ? sanitize_text_field(wp_unslash($_POST['store_name'])) : '';
    $site_url = isset($_POST['site_url']) ? sanitize_text_field(wp_unslash($_POST['site_url'])) : '';


     // Define option names
     $consumer_key_option = 'wc_api_consumer_key_' . $user_id;
     $consumer_secret_option = 'wc_api_consumer_secret_' . $user_id;
     $store_name_option = 'wc_api_store_name_' . $user_id;
     $site_url_option = 'wc_api_site_url_' . $user_id;
     $flag_option = 'wc_api_flag_' . $user_id;
    
    $auth_url = AUTH_URL;
    $auth_headers = [
        'Authorization' => 'Basic ' . base64_encode("$consumer_key:$consumer_secret"),
        'timeout' => 30,
        'sslverify' => false // Avoid SSL verification issues
    ];
    
    // Make the request using wp_remote_get()
    $response = wp_remote_get($auth_url, ['headers' => $auth_headers]);
    
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        wp_send_json_error(['error' => $error_message]);
        exit;
    }
    
    $http_status = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);
    
    if ($http_status == 200) {
        // Credentials are valid, proceed with creating the webhook
    
        $delivery_url = ORDER_DISPATCH_URL;
        global $wpdb;
        $escaped_delivery_url = esc_sql($delivery_url);
        
        // Fetch the webhook from the database
      	$results = $wpdb->get_results($wpdb->prepare("SELECT webhook_id, delivery_url FROM {$wpdb->prefix}wc_webhooks WHERE delivery_url = %s",$escaped_delivery_url)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		
        if ($results) {
            // Webhook already exists, update configuration
            update_option($consumer_key_option, $consumer_key);
            update_option($consumer_secret_option, $consumer_secret);
            update_option($store_name_option, $store_name);
            update_option($site_url_option, $site_url);
            update_option($flag_option, 1);
    
            wp_send_json_success(['message' => 'Configurations updated successfully']);
        } else {
            // Webhook doesn't exist, create new webhook
            update_option($consumer_key_option, $consumer_key);
            update_option($consumer_secret_option, $consumer_secret);
            update_option($store_name_option, $store_name);
            update_option($site_url_option, $site_url);
            update_option($flag_option, 1);
    
            // Call the function to create the WooCommerce webhook
            $response_data = create_woocommerce_webhook($consumer_key, $consumer_secret);
    
            if ($response_data) {
                wp_send_json_success(['message' => 'Webhook created successfully!', 'data' => $response_data]);
            } else {
                wp_send_json_error(['message' => 'Failed to create webhook.']);
            }
        }
    } else {
        // Handle invalid credentials or other errors
        $error_message = $response_data['message'] ?? 'Invalid consumer key or secret.';
        wp_send_json_error([
            'statusCode' => $http_status,
            'message' => $error_message,
        ]);
    }

}

//---------------Save Data Ends----------------

//-------------- Create Webhook Starts --------------------------------

function create_woocommerce_webhook($consumer_key, $consumer_secret)
{

    $api_url = WEBHOOK_URL; 

    // Webhook data
    $webhook_data = [
        'name' => 'Webhook Order Created',
        'topic' => 'order.created',
        'delivery_url' => ORDER_DISPATCH_URL,  
        'secret' => $consumer_secret,  
        'status' => 'active'
    ];
    
    // API URL
    $api_url = WEBHOOK_URL;
    
    // Base64 encode credentials
    $auth = base64_encode("$consumer_key:$consumer_secret");
    
    // Set up request headers
    $headers = [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Basic ' . $auth
    ];
    
    // Set up request arguments
    $args = [
        'headers' => $headers,
        'body'    => wp_json_encode($webhook_data),
        'timeout' => 30
    ];
    
    // Make the POST request
    $response = wp_remote_post($api_url, $args);
    
    if (is_wp_error($response)) {
        // Handle the error
        $error_message = $response->get_error_message();
        wp_send_json_error('Curl error: ' . $error_message);
    } else {
        // Parse the response
        $body = wp_remote_retrieve_body($response);
        wp_send_json_success('Response: ' . $body);
    }
}

//-------------- Create Webhook Starts Ends--------------------------------



//--------------Remove Webhook Starts  --------------------------------
register_uninstall_hook(__FILE__, 'remove_webhook_from_woocommerce');

// Deactivation hook: triggers when the plugin is deactivated
register_deactivation_hook(__FILE__, 'remove_webhook_from_woocommerce');

function remove_webhook_from_woocommerce() {
    global $wpdb;
    $user_id = get_current_user_id();
    $consumer_key_option = 'wc_api_consumer_key_' . $user_id;
    $consumer_secret_option = 'wc_api_consumer_secret_' . $user_id;
    $store_name_option = 'wc_api_store_name_' . $user_id;
    $site_url_option = 'wc_api_site_url_' . $user_id;
    $flag_option = 'wc_api_flag_' . $user_id;  
    $delivery_url = ORDER_DISPATCH_URL; 
    // Fetch all webhook IDs from the WooCommerce webhooks table
	$escaped_delivery_url = esc_sql($delivery_url);
	$webhook_ids = $wpdb->get_results($wpdb->prepare("SELECT webhook_id FROM {$wpdb->prefix}wc_webhooks WHERE delivery_url LIKE %s",$escaped_delivery_url)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	
    if (!empty($webhook_ids)) {
		
        // Delete each webhook by ID
         foreach ($webhook_ids as $webhook) {
				// Ensure you are accessing the correct property from the object
				$webhook_id = $webhook->webhook_id; // Adjust this if the property name is different
				// Prepare the delete query
				$query = $wpdb->prepare("DELETE FROM {$wpdb->prefix}wc_webhooks WHERE webhook_id = %d", $webhook_id);
				// Execute the delete
				$wpdb->delete("{$wpdb->prefix}wc_webhooks", array('webhook_id' => $webhook_id));  // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
        update_option($flag_option, 0);
        // Reset the other options to an empty string
        update_option($consumer_key_option, '');
        update_option($consumer_secret_option, '');
        update_option($store_name_option, '');
        update_option($site_url_option, '');
    }
}
//--------------Remove Webhook Ends  --------------------------------

// Add the settings page content
function wc_api_key_check_settings_page() {
    global $wpdb;

    $user_id = get_current_user_id();
    $consumer_key_option = 'wc_api_consumer_key_' . $user_id;
    $consumer_secret_option = 'wc_api_consumer_secret_' . $user_id;
    $store_name_option = 'wc_api_store_name_' . $user_id;
    $site_url_option = 'wc_api_site_url_' . $user_id;
    $flag_option = 'wc_api_flag_' . $user_id;

    // Check if options already exist
    $consumer_key = get_option($consumer_key_option);
    $consumer_secret = get_option($consumer_secret_option);
    $store_name = get_option($store_name_option);
    $site_url = get_option($site_url_option);
    $flag_value = get_option($flag_option, 0);
    
    // Get store details
    $store_name = get_bloginfo('name');
    $full_url = site_url();

    $plugin_url = plugins_url('/images/', __FILE__);
    $current_user = wp_get_current_user();
    $admin_email = get_option('admin_email');
    ?>

        <div class="wrap">
            <h1>Settings</h1>

            <!-- Tab Navigation -->
            <h2 class="nav-tab-wrapper">
                <a href="#general" class="nav-tab nav-tab-active">General</a>
            </h2>

            <!-- General Settings -->
                <div id="general" class="settings-section">
                <?php wp_nonce_field('wc_api_key_check_save_data', '_wc_api_nonce'); ?>
                    <table class="form-table">
                        <form target="_blank" action="<?php echo esc_url(REGISTER_URL); ?>" id="biblioDispatchPlugin" method="POST">
                            <tr>
                                <th scope="row">
                                    <label for="store_name">Name</label>
                                </th>
                                <td>
                                    <input type="text" id="name" class="wide-input" name="name" value="<?php echo esc_attr($current_user->user_login); ?>", readonly>
                                    <input type="hidden" name="referer" id="referer">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="store_name">Email</label>
                                </th>
                                <td>
                                    <input type="text" id="email" class="wide-input" name="email" value="<?php echo esc_attr($admin_email); ?>" readonly>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="consumer_key">Consumer Key</label>
                                </th>
                                <td>
                                    <input type="text" id="consumer_key" class="wide-input" name="consumer_key" value="<?php echo esc_attr($consumer_key); ?>" required>  
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="consumer_secret">Consumer Secret</label>
                                </th>
                                <td>
                                    <input type="text" id="consumer_secret" class="wide-input" name="consumer_secret" value="<?php echo esc_attr($consumer_secret); ?>" required>
                                    <div class="submit">
                                        <input type="button" id="generate_keys" value="Generate Keys" class="button-primary" style="float:left">
                                    </div>
                                </td>
                            </tr>
                           
                            <tr>
                                <th scope="row">
                                    <label for="store_name">Store Name</label>
                                </th>
                                <td>
                                    <input type="text" id="store_name" class="wide-input" name="store_name" value="<?php echo esc_attr($store_name); ?>" readonly>
                                    <input type="hidden" id="platform_id" name="platform_id" value="2" readonly>
                                </td>
                            </tr>               
    
                            <tr>
                                <th scope="row">
                                    <label for="store_name">Site Url</label>
                                </th>
                                <td>
                                    <input type="text" id="site_url" class="wide-input" name="site_url" value="<?php echo esc_attr($full_url); ?>" readonly>
                                    
                                </td>
                            </tr>
                        </form>
                    </table>
                </div>

                <!-- Save Button -->
                <div class="submit">
                        <div>
                            <button type="button" id="save" value="Access Portal" class="button-primary" style="float:left">Save Details</button>
                        </div>
                        <div>
                            <button type="submit" id="connect" data-flag="<?php echo esc_attr($flag_value); ?>" value="Access Portal" class="button-primary" style="float:left;margin-left:5%">Access Portal</button>
                        </div>
                        
                </div>
        </div>
  
    <?php
}