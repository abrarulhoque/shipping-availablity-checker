=== WooCommerce ZIP Code Shipping Checker ===

A simple plugin that allows customers to check shipping availability by ZIP code without going through the checkout process.

== Description ==

This plugin connects your custom "Do We Ship To You?" page with WooCommerce's native shipping availability checker to provide a consistent experience across your site. It leverages WooCommerce's built-in shipping zones and methods, so you don't have to maintain a separate list of ZIP codes.

Key features:
* Uses WooCommerce's shipping zones and methods for accurate results
* Shortcode [shipping_checker] for adding the checker to any page or post
* Custom page template for creating a dedicated shipping checker page
* Responsive design
* AJAX-powered for fast results without page refresh

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/woocommerce-zip-code-shipping-checker` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Make sure WooCommerce is installed and activated.

== Usage ==

There are two ways to use this plugin:

= Using the shortcode =

Add the [shipping_checker] shortcode to any page or post. You can customize the appearance with these attributes:

* title - The heading text (default: "Do We Ship To You?")
* description - The text that appears below the heading (default: "Enter your ZIP code below to check if we can ship to your location.")
* button_text - The text on the submit button (default: "Check Availability")

Example:
[shipping_checker title="Check Shipping Availability" description="Enter your ZIP code to see if we deliver to your area" button_text="Check Now"]

= Using the Page Template =

1. Go to Pages > Add New
2. Create a new page with an appropriate title (e.g., "Shipping Availability Checker")
3. In the Page Attributes panel, select "Shipping Availability Checker" as the template
4. Publish the page
5. Visit the page to see the shipping checker in action

== Configuration ==

No additional configuration is needed! The plugin uses your existing WooCommerce shipping zones and methods.

Make sure your WooCommerce shipping zones are properly configured:
1. Go to WooCommerce > Settings > Shipping > Shipping Zones
2. Set up your shipping zones with appropriate regions and ZIP codes
3. Add shipping methods to each zone

== Frequently Asked Questions ==

= How does this plugin work? =

The plugin connects to WooCommerce's shipping zone system to check if a customer's ZIP code is within your shipping range. When a customer enters their ZIP code, the plugin creates a mock shipping package and asks WooCommerce if any shipping methods are available for that destination.

= Why doesn't it show shipping costs? =

Accurate shipping costs often depend on the cart contents (weight, dimensions, etc.). This plugin shows estimated costs where possible, but for precise shipping costs, customers should add products to their cart and proceed to checkout.

= Can I customize the appearance? =

Yes! The plugin includes a basic CSS file (shipping-checker.css) that you can edit to match your theme. You can also use the shortcode attributes to customize the text.

== Changelog ==

= 1.0.0 =
* Initial release 