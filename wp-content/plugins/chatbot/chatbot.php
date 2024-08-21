<?php
/*
Plugin Name: Apartment Search Chatbot
Plugin URI: http://yourwebsite.com/
Description: This plugin adds a chatbot shortcode to WordPress for searching apartments by budget or city.
Version: 1.0
Author: Your Name
Author URI: http://yourwebsite.com/
*/

function apartment_search_chatbot_shortcode() {
    ob_start();
    $output = '<div id="apartment-chatbot" style="font-family: Arial, sans-serif;">';

    // Chat box container
    $output .= '<div id="chat-box" style="max-height: 400px; overflow-y: scroll; border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 8px; background-color: #f9f9f9; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">';

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'apartment_search_chatbot')) {
        if (isset($_POST['budget'])) {
            $budget = sanitize_text_field($_POST['budget']);
            $output .= handleBudgetSearch($budget);
        } elseif (isset($_POST['city'])) {
            $city = sanitize_text_field($_POST['city']);
            $output .= handleCitySearch($city);
        } elseif (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'search_budget':
                    $output .= '<form method="post">';
                    $output .= wp_nonce_field('apartment_search_chatbot', '_wpnonce', true, false);
                    $output .= '<div style="margin-bottom: 10px;">Enter your budget: <input type="number" name="budget" required style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;"></div>';
                    $output .= '<button type="submit" style="background-color: #0073aa; color: #fff; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">Search</button>';
                    $output .= '</form>';
                    break;
                case 'search_city':
                    $output .= '<form method="post">';
                    $output .= wp_nonce_field('apartment_search_chatbot', '_wpnonce', true, false);
                    $output .= '<div style="margin-bottom: 10px;">Enter the city: <input type="text" name="city" required style="padding: 5px; border-radius: 4px; border: 1px solid #ccc;"></div>';
                    $output .= '<button type="submit" style="background-color: #0073aa; color: #fff; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer;">Search</button>';
                    $output .= '</form>';
                    break;
            }
        }
    } else {
        // Initial message
        $output .= '<div class="chat-bubble bot-message" style="background-color: #e0f7fa; padding: 15px; border-radius: 8px;">';
        $output .= 'Please select an option to search for apartments.';
        $output .= '</div>';
    }

    $output .= '</div>'; // Close chat-box

    // Buttons placed directly below the chat bubble
    $output .= get_initial_buttons();

    $output .= '</div>'; // Close apartment-chatbot
    echo $output;
    return ob_get_clean();
}

function handleBudgetSearch($budget) {
    $apartments = fetch_apartment_data_with_retries();
    if (is_wp_error($apartments)) {
        return '<p>Error: ' . $apartments->get_error_message() . '</p>';
    }
    $matchedApartments = filterApartmentsByBudget($apartments, $budget);
    return formatApartmentResults($matchedApartments);
}

function handleCitySearch($city) {
    $apartments = fetch_apartment_data_with_retries();
    if (is_wp_error($apartments)) {
        return '<p>Error: ' . $apartments->get_error_message() . '</p>';
    }
    $matchedApartments = filterApartmentsByCity($apartments, $city);
    return formatApartmentResults($matchedApartments);
}

function make_api_request($url, $args = [], $method = 'GET') {
    $default_args = [
        'method'    => $method,
        'timeout'   => 20,
        'headers'   => [
            'X-Auth-Key'    => 'wordpress',
            'X-Auth-Secret' => 'f4ae4d1a35cf653bed2e78623cc1cfd0',
            'Accept'        => 'application/json'
        ],
    ];

    // Merge any additional arguments
    $request_args = wp_parse_args($args, $default_args);

    // Make the API request
    $response = wp_remote_request($url, $request_args);

    // Check for errors in the response
    if (is_wp_error($response)) {
        return new WP_Error('api_error', 'Error fetching data: ' . $response->get_error_message());
    }

    // Check for a valid HTTP status code
    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code != 200) {
        return new WP_Error('http_error', 'Unexpected HTTP response: ' . $status_code);
    }

    // Check if the content type is JSON
    $content_type = wp_remote_retrieve_header($response, 'content-type');
    if (strpos($content_type, 'application/json') === false) {
        return new WP_Error('invalid_content_type', 'Expected JSON, but received: ' . $content_type);
    }

    // Retrieve and clean the JSON response body
    $body = wp_remote_retrieve_body($response);
    $body = trim($body);
    $body = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $body);

    // Decode the cleaned JSON response
    $data = json_decode($body, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('json_error', 'Error decoding JSON response: ' . json_last_error_msg());
    }

    return $data;
}

function fetch_apartment_data_v2() {
    $url = 'https://admin.arpej.fr/api/wordpress/residences/';

    // Fetch data using the helper function
    $data = make_api_request($url);

    // If there's an error, return it
    if (is_wp_error($data)) {
        return $data;
    }

    // Process the data as needed, or just return it for further use
    return $data;
}

function fetch_apartment_data_with_retries($retry_count = 3) {
    $attempt = 0;
    $data = null;

    while ($attempt < $retry_count) {
        $data = fetch_apartment_data_v2();

        // If no error, return the data
        if (!is_wp_error($data)) {
            return $data;
        }

        // Increment the attempt counter
        $attempt++;
    }

    // If all retries fail, return the last error encountered
    return $data;
}

function filterApartmentsByBudget($apartments, $budget) {
    return array_filter($apartments, function($apt) use ($budget) {
        return abs($apt['preview']['rent_amount_from'] - $budget) <= 100; // Adjust as necessary
    });
}

function filterApartmentsByCity($apartments, $city) {
    return array_filter($apartments, function($apt) use ($city) {
        return strtolower($apt['city']) === strtolower($city);
    });
}

function formatApartmentResults($apartments) {
    if (empty($apartments)) {
        return '<div class="chat-bubble bot-message" style="background-color: #f44336; color: white; padding: 15px; border-radius: 8px;">Bot: No apartments found.</div>';
    }

    $output = '<div class="chat-bubble bot-message" style="background-color: #e0f7fa; padding: 15px; border-radius: 8px;">';
    $output .= '<div class="apartment-list" style="display: flex; flex-direction: column; gap: 15px;">';

    foreach ($apartments as $apt) {
        // Construct the URL for the details.php file with the necessary query parameters
        $details_url = add_query_arg([
            'title' => urlencode($apt['title']),
            'address' => urlencode($apt['address']),
            'city' => urlencode($apt['city']),
            'image' => urlencode($apt['pictures'][0]['url']),
            'price' => urlencode($apt['preview']['rent_amount_from']),
            'services' => urlencode(json_encode($apt['preview']['residence_services'])),
        ], home_url('/wp-content/plugins/chatbot/details1.php'));

        $output .= '<div class="apartment-item" style="display: flex; gap: 15px; padding: 15px; border: 1px solid #ddd; border-radius: 8px; background-color: #fff;">';
        $output .= '<div class="apartment-image">';
        $output .= '<img src="' . esc_url($apt['pictures'][0]['url']) . '" alt="' . esc_attr($apt['title']) . '" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">';
        $output .= '</div>';
        $output .= '<div class="apartment-details" style="flex-grow: 1;">';
        $output .= '<h3 style="margin: 0; font-size: 1.2em; color: #333;">' . esc_html($apt['title']) . '</h3>';
        $output .= '<p style="margin: 5px 0; font-size: 0.9em; color: #555;">' . esc_html($apt['address']) . ', ' . esc_html($apt['city']) . '</p>';
        $output .= '<a href="' . esc_url($details_url) . '" target="_blank" style="color: #0073aa; text-decoration: none; font-size: 0.9em; font-weight: bold;">View More</a>';
        $output .= '</div>';
        $output .= '</div>'; // Close apartment-item
    }

    $output .= '</div>'; // Close apartment-list
    $output .= '</div>'; // Close chat-bubble bot-message

    return $output;
}

// Function to generate the initial buttons
function get_initial_buttons() {
    $buttons = '<div style="margin-top: 10px; display: flex; gap: 10px;">';
    $buttons .= '<form method="post">';
    $buttons .= wp_nonce_field('apartment_search_chatbot', '_wpnonce', true, false);
    $buttons .= '<button type="submit" name="action" value="search_budget" style="background-color: #0073aa; color: #fff; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">Search by Budget</button>';
    $buttons .= '</form>';
    $buttons .= '<form method="post">';
    $buttons .= wp_nonce_field('apartment_search_chatbot', '_wpnonce', true, false);
    $buttons .= '<button type="submit" name="action" value="search_city" style="background-color: #28a745; color: #fff; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">Search by City</button>';
    $buttons .= '</form>';
    $buttons .= '</div>';

    return $buttons;
}

// Register the shortcode in WordPress
add_shortcode('apartment_search_chatbot', 'apartment_search_chatbot_shortcode');
?>
