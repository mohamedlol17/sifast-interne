<?php
/**
 * Plugin Name: Custom Search Plugin
 * Plugin URI: http://yourwebsite.com
 * Description: A custom search engine for residences with autocomplete.
 * Version: 1.0
 * Author: Ayoub
 * Author URI: http://yourwebsite.com
 */

defined('ABSPATH') or die('No script kiddies please!');

// Hook for adding admin menus
add_action('admin_menu', 'custom_search_plugin_menu');

function custom_search_plugin_menu() {
    add_menu_page(
        'Custom Search',           // Page title
        'Custom Search',           // Menu title
        'manage_options',          // Capability
        'custom_search',           // Menu slug
        'custom_search_plugin_settings_page', // Callback function
        'dashicons-search',        // Icon URL
        6                          // Position
    );
}

function custom_search_plugin_settings_page() {
    ?>
    <div class="wrap">
        <h2>Custom Search Plugin Settings</h2>
        <p>Configure your custom search plugin settings here.</p>
        <form id="custom-search-form-admin" class="custom-search-form">
            <input type="text" id="custom-search-input-admin" placeholder="Search for residences..." />
            <button type="button" id="custom-search-button-admin">Search</button>
            <div id="autocomplete-results-admin" class="autocomplete-results"></div>
        </form>
        
        <div id="search-results-admin" class="search-results"></div>
        
        <h2>All Residences</h2>
        <div id="all-residences-admin">
            <?php display_initial_items(); ?>
        </div>
    </div>
    <?php
}

// Function to display initial items
function display_initial_items() {
    $response = wp_remote_get('https://admin.arpej.fr/api/wordpress/residences/', [
        'headers' => [
            'X-Auth-Key' => 'wordpress',
            'X-Auth-Secret' => 'f4ae4d1a35cf653bed2e78623cc1cfd0'
        ]
    ]);

    if (!is_wp_error($response)) {
       $body = wp_remote_retrieve_body($response);
        $items = json_decode($body, true);
        
        if (!empty($items)) {
            foreach ($items as $item) {
                $imageUrl = !empty($item['pictures']) && isset($item['pictures'][0]) ? $item['pictures'][0]['url'] : 'default-image-url.jpg';
                $rentAmountFrom = $item['preview']['rent_amount_from'] ?? 'N/A';
                $residence_services = isset($item['services']) ? urlencode(json_encode($item['services'])) : urlencode(json_encode([]));

                // Construct the details URL
                $detailsUrl = home_url('/wp-content/plugins/SEO_autoc/details.php') . '?' . http_build_query([
                    'id' => $item['id'],
                    'title' => $item['title'],
                    'city' => $item['city'],
                    'address' => $item['address'],
                    'img' => $imageUrl,
                    'price' => $rentAmountFrom,
                    'residence_services' => $residence_services
                ]);

                echo '<div class="item-block">';
                echo '<img src="' . esc_url($imageUrl) . '" alt="' . esc_attr($item['title']) . '" style="width:100px;height:auto;">';
                echo '<h3>' . esc_html($item['title']) . '</h3>';
                echo '<p>' . esc_html($item['address']) . ', ' . esc_html($item['city']) . '</p>';
                echo '<a href="' . esc_url($detailsUrl) . '" class="view-more">View More</a>';
                echo '</div>';
            }
        } else {
            echo '<p>No items found.</p>';
        }
    } else {
        echo '<p>Unable to retrieve items.</p>';
    }
}

// Enqueue scripts and styles
function custom_search_plugin_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script(
        'custom-search-autocomplete',
        plugins_url('/js/autocomplete-frontend.js', __FILE__),
        array('jquery'),
        null,
        true
    );

    wp_enqueue_style(
        'custom-search-styles',
        plugins_url('/css/frontend-styles.css', __FILE__)
    );

    // Fetch data from the API
    $response = wp_remote_get('https://admin.arpej.fr/api/wordpress/residences/');
    if (is_array($response) && !is_wp_error($response)) {
        $api_data = json_decode(wp_remote_retrieve_body($response), true);
    } else {
        $api_data = array();
    }

    wp_localize_script(
        'custom-search-autocomplete',
        'search',
        array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'apiData' => $api_data
        )
    );

    wp_enqueue_script(
        'custom-search-widget',
        plugins_url('/js/autocomplete-widget.js', __FILE__),
        array('jquery'),
        null,
        true
    );

    wp_localize_script(
        'custom-search-widget',
        'search_widget',
        array(
            'ajaxurl' => admin_url('admin-ajax.php')
        )
    );
}
add_action('wp_enqueue_scripts', 'custom_search_plugin_scripts');
add_action('admin_enqueue_scripts', 'custom_search_plugin_scripts');

// Normalize string for search
function normalize_string($string) {
    $string = strtolower($string);
    $string = preg_replace('/\s+/', ' ', $string); // Remove extra spaces
    return trim($string);
}

// Handle AJAX request for autocomplete
function custom_search_autocomplete() {
    $term = sanitize_text_field($_GET['term']);
    $term = strtolower($term);

    $response = wp_remote_get('https://admin.arpej.fr/api/wordpress/residences/', [
        'headers' => [
            'X-Auth-Key' => 'wordpress',
            'X-Auth-Secret' => 'f4ae4d1a35cf653bed2e78623cc1cfd0'
        ]
    ]);

    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $results = json_decode($body, true);

        if (!empty($results)) {
            $suggestions = array();
            foreach ($results as $result) {
                if (stripos($result['title'], $term) !== false) {
                    $suggestions[] = $result['title'];
                }
                if (stripos($result['address'], $term) !== false) {
                    $suggestions[] = $result['address'];
                }
            }
            $suggestions = array_unique($suggestions);
            echo json_encode($suggestions);
        } else {
            echo json_encode(array());
        }
    } else {
        echo json_encode(array());
    }
    wp_die();
}
add_action('wp_ajax_custom_search_autocomplete', 'custom_search_autocomplete');
add_action('wp_ajax_nopriv_custom_search_autocomplete', 'custom_search_autocomplete');

// Handle AJAX request for search
function custom_search_ajax() {
    $term = sanitize_text_field($_POST['term']);
    $normalized_term = normalize_string($term);

    $response = wp_remote_get('https://admin.arpej.fr/api/wordpress/residences/', [
        'headers' => [
            'X-Auth-Key' => 'wordpress',
            'X-Auth-Secret' => 'f4ae4d1a35cf653bed2e78623cc1cfd0'
        ]
    ]);

    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $results = json_decode($body, true);

        $filtered_results = array_filter($results, function ($item) use ($normalized_term) {
            $title_normalized = normalize_string($item['title']);
            $address_normalized = normalize_string($item['address']);
            return strpos($title_normalized, $normalized_term) !== false || strpos($address_normalized, $normalized_term) !== false;
        });

        if (!empty($filtered_results)) {
            foreach ($filtered_results as $result) {
                $imageUrl = !empty($result['pictures']) && isset($result['pictures'][0]) ? $result['pictures'][0]['url'] : 'default-image-url.jpg';
                $rentAmountFrom = $result['preview']['rent_amount_from'] ?? 'N/A';
                $residence_services = isset($result['services']) ? urlencode(json_encode($result['services'])) : urlencode(json_encode([]));

                $detailsUrl = add_query_arg([
                    'zip_code' => urlencode($zip_code['zip_code']),
                    'id' => urlencode($result['id']),
                    'title' => urlencode($result['title']),
                    'city' => urlencode($result['city']),
                    'address' => urlencode($result['address']),
                    'img' => urlencode($imageUrl),
                    'price' => urlencode($rentAmountFrom),
                    'residence_services' => $residence_services
                ], home_url('/wp-content/plugins/SEO_autoc/details.php'));

                echo '<div class="search-result-block">';
                echo '<img src="' . esc_url($imageUrl) . '" alt="Image of ' . esc_attr($result['title']) . '" style="width:100px;height:auto;">';
                echo '<h2>' . esc_html($result['title']) . '</h2>';
                echo '<p>' . esc_html($result['address']) . ', ' . esc_html($result['city']) . '</p>';
                echo '<p>Starting from: €' . esc_html($rentAmountFrom) . '</p>';
                echo '<a href="' . esc_url($detailsUrl) . '" class="view-more">View More</a>';
                echo '</div>';
            }
        } else {
            echo '<p>No results found matching your criteria.</p>';
        }
    } else {
        echo '<p>Error accessing API.</p>';
    }

    wp_die();
}
add_action('wp_ajax_custom_search_ajax', 'custom_search_ajax');
add_action('wp_ajax_nopriv_custom_search_ajax', 'custom_search_ajax');

// Handle AJAX request for single residence details
function custom_search_residence_details() {
    if (!isset($_GET['residence_id'])) {
        wp_send_json_error('No residence ID provided');
    }

    $residence_id = sanitize_text_field($_GET['residence_id']);
    

    $response = wp_remote_get('https://admin.arpej.fr/api/wordpress/residences/' . $residence_id, [
        'headers' => [
            'X-Auth-Key' => 'wordpress',
            'X-Auth-Secret' => 'f4ae4d1a35cf653bed2e78623cc1cfd0'
        ]
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error('Error fetching residence details');
    }

    $body = wp_remote_retrieve_body($response);
    $residence = json_decode($body, true);

    if (empty($residence)) {
        wp_send_json_error('No details found for the given residence ID');
    }

    wp_send_json($residence);
}
add_action('wp_ajax_custom_search_residence_details', 'custom_search_residence_details');
add_action('wp_ajax_nopriv_custom_search_residence_details', 'custom_search_residence_details');

// Shortcode for custom search form
function custom_search_shortcode() {
    ob_start();
    ?>
    <style>
    .custom-search-form {
        margin: 20px 0;
        position: relative;
        display: block;
        width: 100%;
    }

    .custom-search-form input[type="text"] {
        width: calc(100% - 120px);
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    .custom-search-form button {
        position: absolute;
        right: 0;
        top: 0;
        height: 100%;
        padding: 10px;
        border: none;
        background-color: #0073aa;
        color: white;
        border-radius: 4px;
        cursor: pointer;
    }

    .search-results, .autocomplete-results {
        margin-top: 10px;
    }

    .search-result-block {
        border-bottom: 1px solid #ddd;
        padding: 10px 0;
    }

    .item-block {
        border-bottom: 1px solid #ddd;
        padding: 10px 0;
        display: flex;
        align-items: center;
    }

    .item-block img {
        margin-right: 10px;
        width: 100px;
        height: auto;
    }
    </style>
    
    <form id="custom-search-form" class="custom-search-form">
        <input type="text" id="custom-search-input" placeholder="Search for residences..." />
        <button type="button" id="custom-search-button">Search</button>
        <div id="autocomplete-results" class="autocomplete-results"></div>
    </form>
    
    <div id="search-results" class="search-results"></div>

    <h2>All Residences</h2>
    <div id="all-residences">
        <?php display_initial_items(); ?>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        function performSearch(query) {
            $.ajax({
                url: search.ajaxurl,
                type: 'POST',
                data: {
                    action: 'custom_search_ajax',
                    term: query
                },
                success: function(response) {
                    $('#search-results').html(response);
                }
            });
        }

        $('#custom-search-input').on('input', function() {
            var query = $(this).val();
            if (query.length < 3) {
                $('#autocomplete-results').empty();
                return;
            }

            $.ajax({
                url: search.ajaxurl,
                type: 'GET',
                data: {
                    action: 'custom_search_autocomplete',
                    term: query
                },
                success: function(response) {
                    var suggestions = JSON.parse(response);
                    var suggestionHtml = suggestions.map(function(suggestion) {
                        return '<div class="autocomplete-suggestion">' + suggestion + '</div>';
                    }).join('');

                    $('#autocomplete-results').html(suggestionHtml);
                }
            });
        });

        $(document).on('click', '.autocomplete-suggestion', function() {
            var query = $(this).text();
            $('#custom-search-input').val(query);
            $('#autocomplete-results').empty();
            performSearch(query);
        });

        $('#custom-search-button').on('click', function() {
            var query = $('#custom-search-input').val();
            performSearch(query);
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('custom_search', 'custom_search_shortcode');

// Widget class
class Custom_Search_Widget extends WP_Widget {
    function __construct() {
        parent::__construct(
            'custom_search_widget',
            'Custom Search Widget',
            array('description' => __('Displays a search form and results in the sidebar.', 'text_domain'))
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
        echo $args['before_title'] . 'Custom Search' . $args['after_title'];
        ?>
        <form id="custom-search-form-sidebar" class="custom-search-form">
            <input type="text" id="custom-search-input-sidebar" placeholder="Search for residences..." />
            <button type="button" id="custom-search-button-sidebar">Search</button>
            <div id="autocomplete-results-sidebar" class="autocomplete-results"></div>
        </form>
        
        <div id="search-results-sidebar" class="search-results"></div>

        <h2>All Residences</h2>
        <div id="all-residences-sidebar">
            <?php display_initial_items(); ?>
        </div>
        <?php
        echo $args['after_widget'];
    }

    public function form($instance) {
        // Backend form for widget options (if any)
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        return $instance;
    }
}

function register_custom_search_widget() {
    register_widget('Custom_Search_Widget');
}
add_action('widgets_init', 'register_custom_search_widget');

// Enqueue admin scripts and styles
function custom_search_admin_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script(
        'custom-search-autocomplete-admin',
        plugins_url('/js/autocomplete-admin.js', __FILE__),
        array('jquery'),
        null,
        true
    );

    wp_enqueue_style(
        'custom-search-admin-styles',
        plugins_url('/css/admin-styles.css', __FILE__)
    );

    wp_localize_script(
        'custom-search-autocomplete-admin',
        'search_admin',
        array(
            'ajaxurl' => admin_url('admin-ajax.php')
        )
    );
}
add_action('admin_enqueue_scripts', 'custom_search_admin_scripts');