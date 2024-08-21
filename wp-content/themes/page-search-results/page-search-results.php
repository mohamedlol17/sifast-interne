<?php
/**
 * Template Name: Search Results
 */

get_header();
?>

<link rel="stylesheet" href="<?php echo get_stylesheet_directory_uri(); ?>/style.css" type="text/css" media="all" />

<div class="search-results-container">
    <h1>Search Results</h1>
    <?php
    if (isset($_GET['s'])) {
        $search_term = sanitize_text_field($_GET['s']);
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_search_products';
        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE name LIKE %s OR description LIKE %s", '%' . $wpdb->esc_like($search_term) . '%', '%' . $wpdb->esc_like($search_term) . '%');
        $results = $wpdb->get_results($query);
        if (!empty($results)) {
            echo '<table>';
            echo '<thead><tr><th>Name</th><th>Description</th></tr></thead><tbody>';
            foreach ($results as $result) {
                echo '<tr><td>' . esc_html($result->name) . '</td><td>' . esc_html($result->description) . '</td></tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>No results found.</p>';
        }
    } else {
        echo '<p>Please enter a search term.</p>';
    }
    ?>
</div>

<?php
get_footer();
?>
