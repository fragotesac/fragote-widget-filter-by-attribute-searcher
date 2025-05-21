<?php
/**
 * Plugin Name: FraGoTe WooCommerce Attribute Filter Search
 * Description: Extends the WooCommerce "Product Filter by Attribute" widget by adding a search box to filter attribute values.
 * Version:     1.0.0
 * Author: FraGoTe Software Factory
 * Author URI: https://www.fragote.com
 * Text Domain: wc-attribute-filter-search
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WC_Attribute_Filter_Search Class
 */
class WC_Attribute_Filter_Search {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_footer', array( $this, 'add_search_input_to_widget' ) );
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_scripts() {
		// Enqueue the main JavaScript file.
		wp_enqueue_script(
			'wc-attribute-filter-search',
			plugin_dir_url( __FILE__ ) . 'assets/js/attribute-filter-search.js',
			array( 'jquery' ), // Dependency on jQuery.
			'1.0.0',
			true // Enqueue in the footer.
		);

		// Enqueue the main CSS file.
		wp_enqueue_style(
			'wc-attribute-filter-search',
			plugin_dir_url( __FILE__ ) . 'assets/css/attribute-filter-search.css',
			array(),
			'1.0.0'
		);
	}

	/**
	 * Add the search input field to the "Product Filter by Attribute" widget.
	 * This uses JavaScript to inject the input after the DOM is ready.
	 * This is a more robust way to add elements to existing widgets without
	 * directly modifying their PHP output, which can be complex and prone to conflicts.
	 */
	public function add_search_input_to_widget() {
		// We don't need to add PHP here, as the JavaScript will handle the DOM manipulation.
		// This function is just a placeholder to ensure the script runs in the footer.
	}
}

// Initialize the plugin.
new WC_Attribute_Filter_Search();

// Create the assets directory and files if they don't exist.
// This is for demonstration purposes within a single file. In a real plugin,
// these files would be part of the plugin's distribution.
if ( ! is_dir( plugin_dir_path( __FILE__ ) . 'assets/js' ) ) {
	mkdir( plugin_dir_path( __FILE__ ) . 'assets/js', 0755, true );
}
if ( ! is_dir( plugin_dir_path( __FILE__ ) . 'assets/css' ) ) {
	mkdir( plugin_dir_path( __FILE__ ) . 'assets/css', 0755, true );
}

// Write the JavaScript content to the file.
file_put_contents(
	plugin_dir_path( __FILE__ ) . 'assets/js/attribute-filter-search.js',
	'
(function($) {
    $(document).ready(function() {
        // Select all WooCommerce "Filter Products by Attribute" widgets (including block-based ones).
        // The new block-based widgets use \'wp-block-woocommerce-attribute-filter\'.
        // The older widgets typically use \'woocommerce-widget-layered-nav\'.
        // We\'ll target both for broader compatibility.
        $(".wp-block-woocommerce-attribute-filter, .woocommerce-widget-layered-nav").each(function() {
            var $widget = $(this);
            // The list of attributes can be either \'wc-block-attribute-filter-list\' (for blocks)
            // or \'woocommerce-widget-layered-nav-list\' (for older widgets).
            var $attributeList = $widget.find("ul.wc-block-attribute-filter-list, ul.woocommerce-widget-layered-nav-list");

            // Check if the attribute list exists and contains items.
            if ($attributeList.length && $attributeList.children("li").length > 0) {
                // Create the search input field.
                var $searchInput = $("<input>")
                    .attr({
                        type: "text",
                        placeholder: "Search attributes...",
                        "aria-label": "Search attribute values"
                    })
                    .addClass("wc-attribute-filter-search-input");

                // Find the "Show more" button if it exists.
                var $showMoreButton = $attributeList.find("li.show-more button");

                // Prepend the search input to the widget.
                // For block-based filters, prepend before the ul.
                // For older widgets, it\'s usually before the ul too.
                $attributeList.before($searchInput);

                // Debounce function to limit how often the filter function is called.
                var debounceTimer;
                function debounce(func, delay) {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(func, delay);
                }

                // Store initial hidden state for "Show more" functionality.
                // This will be used to restore visibility when search is cleared.
                // We need to store a map of original display styles or a class.
                // For simplicity, we\'ll just re-evaluate hidden state on clear for now,
                // assuming the \'hidden\' attribute is the primary way they are hidden.
                var initialHiddenItems = $attributeList.children("li[hidden]");


                // Attach keyup event listener to the search input.
                $searchInput.on("keyup", function() {
                    var searchTerm = $(this).val().toLowerCase().trim();

                    debounce(function() {
                        if (searchTerm === "") {
                            // If search term is empty, restore initial state.
                            $attributeList.children("li").show().removeAttr("hidden"); // Show all first
                            initialHiddenItems.attr("hidden", ""); // Re-hide initially hidden ones
                            if ($showMoreButton.length) {
                                $showMoreButton.parent("li").show(); // Show the \'Show more\' button\'s li
                            }
                        } else {
                            // Hide the "Show more" button when searching.
                            if ($showMoreButton.length) {
                                $showMoreButton.parent("li").hide();
                            }

                            // Iterate over each attribute list item.
                            $attributeList.children("li").each(function() {
                                var $listItem = $(this);
                                // Skip the "Show more" button\'s li during active search.
                                if ($listItem.hasClass("show-more")) {
                                    return true; // continue to the next iteration
                                }

                                // Get the text content of the label within the list item.
                                // This handles the structure <label><input><svg><span>Text</span></label>
                                var listItemText = $listItem.find("span.wc-block-components-checkbox__label").text().toLowerCase();

                                // Show or hide the list item based on the search term.
                                if (listItemText.includes(searchTerm)) {
                                    $listItem.show().removeAttr("hidden"); // Ensure it\'s visible and remove hidden attribute
                                } else {
                                    $listItem.hide();
                                }
                            });
                        }
                    }, 250); // 250ms debounce delay.
                });
            }
        });
    });
})(jQuery);
'
);

// Write the CSS content to the file.
file_put_contents(
	plugin_dir_path( __FILE__ ) . 'assets/css/attribute-filter-search.css',
	'
.wc-attribute-filter-search-input {
    width: 100%;
    padding: 8px 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box; /* Include padding and border in the element\'s total width and height */
    font-size: 14px;
    line-height: 1.5;
    -webkit-appearance: none; /* Remove default styling for some browsers */
    -moz-appearance: none;
    appearance: none;
}

.wc-attribute-filter-search-input:focus {
    outline: none;
    border-color: #999;
    box-shadow: 0 0 0 1px #999;
}

/* Ensure the list items are initially visible if not filtered */
.woocommerce-widget-layered-nav-list li,
.wc-block-attribute-filter-list li { /* Also target the new block list items */
    display: list-item; /* Reset to default list item display */
}
'
);
?>
