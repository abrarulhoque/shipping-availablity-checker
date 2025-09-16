<?php
/**
 * Template Name: Shipping Availability Checker
 *
 * A custom page template for checking shipping availability by ZIP code
 */
get_header();
?>

<div class="shipping-checker-container">
    <h1 class="uppercase alt-font" style="text-align: center">DO WE SHIP TO YOU?</h1>
    <hr />
    
    <div class="zip-container">
        <div class="zip-checker">
            <h3 class="zip-title">CHECK YOUR ZIP CODE</h3>
            <div class="zip-input-field">
                <input class="zip-input" type="text" id="zip_code_input" placeholder="Enter your zip code">
                <button class="zip-btn" id="check_zip_code_button">Search</button>
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
    // Event handlers
    $('#check_zip_code_button').on('click', checkShippingAvailability);
    $('#zip_code_input').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            checkShippingAvailability();
        }
    });
    
    /**
     * Show error message with styling
     */
    function showError(message) {
        $('#shipping_results').html(message).css('color', '#d83131');
    }
    
    /**
     * Display California restrictions
     */
    function showCaliforniaRestrictions() {
        var restrictionHtml = '<h3>RESTRICTED SHIPPING & STATE REGULATIONS:</h3>' +
            '<p>*ATTENTION CALIFORNIA CUSTOMERS: CALIFORNIA SHIPPING IS ONLY AVAILABLE FOR:</p>' +
            '<p><a href="https://vapesocietysupplies.com/product-tag/tobacco-flavor/" target="_blank">TOBACCO FLAVORS</a> | ' +
            '<a href="https://vapesocietysupplies.com/collections/devices/" target="_blank">VAPE HARDWARE</a></p>';
        
        $('#shipping_restrictions')
            .html(restrictionHtml)
            .css({
                'color': '#d83131',
                'margin-top': '15px',
                'display': 'block'
            });
    }
    
    /**
     * Main function to check shipping availability
     */
    function checkShippingAvailability() {
        var zip_code = $('#zip_code_input').val();
        var security = $('#zip_code_security').val();
        
        // Hide any previous restriction messages
        $('#shipping_restrictions').hide();
        
        if (!zip_code) {
            showError('Please enter a ZIP code.');
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
                    $('#state_input').val(state_id);
                    
                    // Check shipping availability with state and ZIP
                    checkShippingWithState(zip_code, 'US', state_id);
                } else {
                    showError(response.data);
                }
            },
            error: function() {
                showError('Error retrieving location data. Please try again.');
            }
        });
    }
    
    /**
     * Check shipping with state information
     */
    function checkShippingWithState(zip_code, country, state) {
        var security = $('#zip_code_security').val();
        
        // Display California shipping restrictions if state is CA
        if (state === 'CA') {
            showCaliforniaRestrictions();
            // For California, don't show any error message, just clear it immediately
            $('#shipping_results').html('');
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
                if (response.success && response.data.methods && response.data.methods.length > 0) {
                    $('#shipping_results').html('Great news! We do ship to your Zip Code!').css('color', 'green');
                } else {
                    // Don't show error message for California
                    if (state !== 'CA') {
                        showError("We are sorry. We currently don't serve your Zip Code.");
                    } else {
                        // Clear any previous message for California
                        $('#shipping_results').html('');
                    }
                }
                
                // Keep showing California restriction if applicable
                if (state === 'CA') {
                    $('#shipping_restrictions').show();
                }
            },
            error: function() {
                if (state !== 'CA') {
                    showError('An error occurred. Please try again.');
                } else {
                    // Clear any previous message for California
                    $('#shipping_results').html('');
                }
            }
        });
    }
});
</script>

<?php get_footer(); ?> 