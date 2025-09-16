<?php
/**
 * Functions for WooCommerce Shipping Availability Checker
 */

// Don't allow direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue scripts and styles
 */
function shipping_checker_enqueue_scripts() {
    $version = '1.0.0';
    
    // Register and enqueue the JavaScript
    wp_register_script('wc-shipping-checker', '', array('jquery'), $version, true);
    wp_localize_script('wc-shipping-checker', 'wc_shipping_checker', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
    wp_enqueue_script('wc-shipping-checker');
    
    // Register and enqueue the custom detector script - ensure jQuery is available
    wp_register_script('wc-shipping-checker-custom', WC_SHIPPING_CHECKER_URL . 'shipping-checker-custom.js', array('jquery', 'wc-shipping-checker'), $version, true);
    wp_localize_script('wc-shipping-checker-custom', 'wc_shipping_checker', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
    wp_enqueue_script('wc-shipping-checker-custom');
    
    // Register and enqueue the CSS
    wp_register_style('wc-shipping-checker-style', WC_SHIPPING_CHECKER_URL . 'shipping-checker.css', array(), $version);
    wp_enqueue_style('wc-shipping-checker-style');
}
add_action('wp_enqueue_scripts', 'shipping_checker_enqueue_scripts');

/**
 * Handle AJAX request for ZIP code validation
 */
function validate_shipping_zip_code() {
    // Security check
    check_ajax_referer('validate_zip_code_nonce', 'security');

    // Get and sanitize inputs
    $zip_code = isset($_POST['zip_code']) ? sanitize_text_field($_POST['zip_code']) : '';
    $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : 'US';
    $state = isset($_POST['state']) ? sanitize_text_field($_POST['state']) : '';
    
    if (empty($zip_code)) {
        wp_send_json_error('Please enter a ZIP code.');
    }
    
    // Check if WooCommerce is active
    if (!class_exists('WC_Shipping_Zones') || !class_exists('WC_Shipping')) {
        wp_send_json_error('WooCommerce is not active or properly configured.');
    }
    
    // For better accuracy, use WC_Shipping to check actual shipping availability
    $wc_shipping = WC_Shipping::instance();
    $package = array(
        'destination' => array(
            'postcode' => $zip_code,
            'country'  => $country,
            'state'    => $state,
        ),
        'contents' => array(
            // Add a dummy product to ensure shipping calculation runs
            array(
                'data' => new WC_Product_Simple(),
                'quantity' => 1
            )
        ),
    );
    
    $shipping_for_package = $wc_shipping->calculate_shipping_for_package($package);
    
    if (empty($shipping_for_package) || empty($shipping_for_package['rates'])) {
        wp_send_json_error('No shipping methods are available for your location.');
        wp_die();
    }
    
    // Get available methods to display from the calculated rates
    $available_methods = array();
    foreach ($shipping_for_package['rates'] as $method_id => $method) {
        $available_methods[] = array(
            'id'    => $method_id,
            'label' => $method->label,
            'cost'  => $method->cost ? wc_price($method->cost) : ''
        );
    }
    
    // Return results
    if (!empty($available_methods)) {
        wp_send_json_success(array('methods' => $available_methods));
    } else {
        wp_send_json_error('No shipping methods are available for your location.');
    }
    
    wp_die();
}

// Register AJAX handlers for both logged-in and non-logged-in users
add_action('wp_ajax_validate_shipping_zip_code', 'validate_shipping_zip_code');
add_action('wp_ajax_nopriv_validate_shipping_zip_code', 'validate_shipping_zip_code');

/**
 * Handle AJAX request for getting states for a country
 */
function get_states_for_country() {
    // Security check
    check_ajax_referer('validate_zip_code_nonce', 'security');

    // Get and sanitize input
    $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
    
    if (empty($country)) {
        wp_send_json_error('Please provide a country code.');
    }
    
    // Check if WooCommerce is active
    if (!function_exists('WC')) {
        wp_send_json_error('WooCommerce is not active or properly configured.');
    }
    
    // Get states for the country
    $states = WC()->countries->get_states($country);
    
    // Return results
    wp_send_json_success(array('states' => $states));
}

// Register AJAX handlers for both logged-in and non-logged-in users
add_action('wp_ajax_get_states_for_country', 'get_states_for_country');
add_action('wp_ajax_nopriv_get_states_for_country', 'get_states_for_country');

/**
 * Handle AJAX request for retrieving state from ZIP code using API
 */
function get_state_from_zip() {
    // Security check
    check_ajax_referer('validate_zip_code_nonce', 'security');

    // Get and sanitize input
    $zip_code = isset($_POST['zip_code']) ? sanitize_text_field($_POST['zip_code']) : '';
    
    if (empty($zip_code)) {
        wp_send_json_error('Please provide a ZIP code.');
    }
    
    // Fetch state from API
    $api_url = 'https://api.sipcode.dev/zip/' . $zip_code;
    $response = wp_remote_get($api_url);
    
    if (is_wp_error($response)) {
        wp_send_json_error('Error connecting to ZIP code service.');
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);
    
    // Log to error log for debugging
    error_log('ZIP API response for ' . $zip_code . ': ' . print_r($data, true));
    
    // Normalize API response: handle array-of-objects or single object
    $first_result = null;
    if (is_array($data)) {
        $first_result = isset($data[0]) ? $data[0] : null;
    } elseif (is_object($data)) {
        $first_result = $data;
    }

    // Check if data is valid and contains state
    if (!$first_result || !isset($first_result->state_id)) {
        wp_send_json_error('Invalid ZIP code or state information not found.');
    }
    
    // Special check for California ZIPs that begin with 9
    if (substr($zip_code, 0, 1) === '9' && in_array(substr($zip_code, 0, 3), ['900', '902', '903', '904', '905', '906', '907', '908', '910', '911', '912', '913', '914', '915', '916', '917', '918', '919', '920', '921', '922', '923', '924', '925', '926', '927', '928', '930', '931', '932', '933', '934', '935', '936', '937', '938', '939', '940', '941', '942', '943', '944', '945', '946', '947', '948', '949', '950', '951', '952', '953', '954', '955', '956', '957', '958', '959', '960', '961'])) {
        // Force state to CA for California ZIP codes
        $first_result->state_id = 'CA';
        $first_result->state_name = 'California';
        error_log('Forcing ZIP ' . $zip_code . ' to California');
    }
    
    // Return the state info
    wp_send_json_success(array(
        'state_id' => isset($first_result->state_id) ? $first_result->state_id : '',
        'state_name' => isset($first_result->state_name) ? $first_result->state_name : '',
        'city' => isset($first_result->city) ? $first_result->city : ''
    ));
}

// Register AJAX handlers for both logged-in and non-logged-in users
add_action('wp_ajax_get_state_from_zip', 'get_state_from_zip');
add_action('wp_ajax_nopriv_get_state_from_zip', 'get_state_from_zip');

/**
 * Handle AJAX request to get nonce for shipping check
 */
function get_shipping_nonce() {
    $nonce = wp_create_nonce('validate_zip_code_nonce');
    wp_send_json_success(array('nonce' => $nonce));
}

// Register AJAX handlers for both logged-in and non-logged-in users
add_action('wp_ajax_get_shipping_nonce', 'get_shipping_nonce');
add_action('wp_ajax_nopriv_get_shipping_nonce', 'get_shipping_nonce');

/**
 * Register the shipping checker shortcode
 */
function wc_shipping_checker_shortcode($atts) {
    // Ensure styles and scripts are loaded
    shipping_checker_enqueue_scripts();
    
    // Process shortcode attributes
    $atts = shortcode_atts(array(
        'title' => 'DO WE SHIP TO YOU?',
        'button_text' => 'Search'
    ), $atts, 'shipping_checker');
    
    // Start output buffering
    ob_start();
    ?>
    <div class="shipping-checker-container">
        <h1 class="uppercase alt-font" style="text-align: center"><?php echo esc_html($atts['title']); ?></h1>
        <hr />
        
        <div class="zip-container">
            <div class="zip-checker">
                <h3 class="zip-title">CHECK YOUR ZIP CODE</h3>
                <div class="zip-input-field">
                    <input class="zip-input" type="text" id="zip_code_input" placeholder="Enter your zip code">
                    <button class="zip-btn" id="check_zip_code_button"><?php echo esc_html($atts['button_text']); ?></button>
                    <input type="hidden" id="country_input" value="US">
                    <input type="hidden" id="state_input" value="">
                </div>
                <h3 class="zip-notice" id="shipping_results"></h3>
                <div id="shipping_restrictions" class="shipping-restrictions" style="display:none;"></div>
            </div>
            <div>
                <p>
                    <br />Enter your Zip Code to see if we currently deliver in your area!
                    Vape Society Supply is always fully committed to providing our
                    customers with the best vaping products and customer service possible.
                    If you've had your eye on a specific Vape product or any Accessory
                    item, you might be usure if, where you live, this item can be shipped
                    out to you. Fortunately, we made it simple. All you need to do is
                    enter your Zip Code in the "Check Your Zip Code Field" and then click
                    search, and within a second, you'll know if we can currently ship to
                    your area.
                </p>
            </div>
            <?php wp_nonce_field('validate_zip_code_nonce', 'zip_code_security'); ?>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#check_zip_code_button').on('click', checkShippingAvailability);
        $('#zip_code_input').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                checkShippingAvailability();
            }
        });
        
        function checkShippingAvailability() {
            var zip_code = $('#zip_code_input').val();
            var security = $('#zip_code_security').val();
            
            // Hide any previous restriction messages
            $('#shipping_restrictions').hide();
            
            if (!zip_code) {
                $('#shipping_results').html('Please enter a ZIP code.');
                $('#shipping_results').css('color', '#d83131');
                return;
            }
            
            $('#shipping_results').html('Checking availability...');
            
            // First get the state from ZIP code
            $.ajax({
                url: wc_shipping_checker.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_state_from_zip',
                    zip_code: zip_code,
                    security: security
                },
                success: function(response) {
                    if (response.success) {
                        // We got the state, now check shipping availability
                        var state_id = response.data.state_id;
                        console.log('API Response - State ID:', state_id, 'State Name:', response.data.state_name, 'City:', response.data.city);
                        $('#state_input').val(state_id);
                        
                        // Check shipping availability with state and ZIP
                        checkShippingWithState(zip_code, 'US', state_id);
                    } else {
                        $('#shipping_results').html(response.data);
                        $('#shipping_results').css('color', '#d83131');
                    }
                },
                error: function() {
                    $('#shipping_results').html('Error retrieving location data. Please try again.');
                    $('#shipping_results').css('color', '#d83131');
                }
            });
        }
        
        function checkShippingWithState(zip_code, country, state) {
            var security = $('#zip_code_security').val();
            
            // Debug state value
            console.log('State ID:', state);
            
            // Display California shipping restrictions if state is CA
            if (state === 'CA') {
                console.log('California detected');
                // Show California specific restriction notice
                var restrictionHtml = '<h3>RESTRICTED SHIPPING & STATE REGULATIONS:</h3>';
                restrictionHtml += '<p>*ATTENTION CALIFORNIA CUSTOMERS: CALIFORNIA SHIPPING IS ONLY AVAILABLE FOR:</p>';
                restrictionHtml += '<p><a href="https://vapesocietysupplies.com/product-tag/tobacco-flavor/" target="_blank">TOBACCO FLAVORS</a> | ';
                restrictionHtml += '<a href="https://vapesocietysupplies.com/collections/devices/" target="_blank">VAPE HARDWARE</a></p>';
                
                $('#shipping_restrictions').html(restrictionHtml);
                $('#shipping_restrictions').css({
                    'color': '#d83131',
                    'margin-top': '15px',
                    'display': 'block'
                });
            }
            
            $('#shipping_results').html('Checking shipping availability...');
            
            $.ajax({
                url: wc_shipping_checker.ajax_url,
                type: 'POST',
                data: {
                    action: 'validate_shipping_zip_code',
                    zip_code: zip_code,
                    country: country,
                    state: state,
                    security: security
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.methods && response.data.methods.length > 0) {
                            $('#shipping_results').html('Great news! We do ship to your Zip Code!');
                            $('#shipping_results').css('color', 'green');
                            
                            // Keep showing California restriction if applicable
                            if (state === 'CA') {
                                $('#shipping_restrictions').show();
                            }
                        } else {
                            // Don't show error message for California
                            if (state !== 'CA') {
                                $('#shipping_results').html("We are sorry. We currently don't serve your Zip Code.");
                                $('#shipping_results').css('color', '#d83131');
                            } else {
                                // Clear any previous message for California
                                console.log('Hiding error message for California');
                                $('#shipping_results').html('');
                            }
                            
                            // Keep showing California restriction if applicable
                            if (state === 'CA') {
                                $('#shipping_restrictions').show();
                            }
                        }
                    } else {
                        // Don't show error message for California
                        if (state !== 'CA') {
                            $('#shipping_results').html("We are sorry. We currently don't serve your Zip Code.");
                            $('#shipping_results').css('color', '#d83131');
                        } else {
                            // Clear any previous message for California
                            console.log('Hiding error message for California (error case)');
                            $('#shipping_results').html('');
                        }
                        
                        // Keep showing California restriction if applicable
                        if (state === 'CA') {
                            $('#shipping_restrictions').show();
                        }
                    }
                },
                error: function() {
                    $('#shipping_results').html('An error occurred. Please try again.');
                    $('#shipping_results').css('color', '#d83131');
                }
            });
        }
    });
    </script>
    <?php
    
    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('shipping_checker', 'wc_shipping_checker_shortcode'); 
