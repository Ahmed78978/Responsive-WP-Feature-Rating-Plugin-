<?php
/**
 * Plugin Name: Post Features
 * Description: A simple WordPress plugin to add, display, and delete features with ratings in posts.
 * Version: 1.5
 * Author: Ahmed Zahid
 */

// Register custom meta box for post features
function add_post_features_meta_box() {
    add_meta_box(
        'post_features_meta_box',
        'Post Features',
        'post_features_meta_box_callback',
        'post',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_post_features_meta_box');

function enqueue_plugin_styles() {
    // Enqueue the stylesheet
    // Ensure that 'styles.css' is in the same directory as the current PHP file.
    wp_enqueue_style('plugin-styles', plugin_dir_url(__FILE__) . 'styles.css', array(), '1.0.0', 'all');
    
    // Enqueue the script
    // Ensure that 'script.js' is in the same directory as the current PHP file.
    wp_enqueue_script('plugin-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
}

// Hook the function to the wp_enqueue_scripts action
add_action('wp_enqueue_scripts', 'enqueue_plugin_styles');

// Activation Page
function activation_page() {
    if (isset($_POST['activation_key'])) {
        $activation_key = sanitize_text_field($_POST['activation_key']);
        // Check if the entered key matches the activation key
        if ($activation_key === '&^$(&*^T)&%^&%GOHG)^*%%&(^G') {
            update_option('plugin_activated', true);
            echo '<div class="updated"><p>Plugin activated successfully!</p></div>';
        } else {
            echo '<div class="error"><p>Invalid activation key. Please try again.</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>Plugin Activation</h1>
        <form method="post" action="">
            <label for="activation_key">Enter Activation Key:</label>
            <input type="text" name="activation_key" required>
            <button type="submit" class="button button-primary">Activate Plugin</button>
        </form>
    </div>
    <?php
}

// Display content of post features meta box
function post_features_meta_box_callback($post) {
    $plugin_activated = get_option('plugin_activated', false);

    if (!$plugin_activated) {
        // Plugin not activated, limit features to 3
        echo '<p><strong>Note:</strong> Plugin is not activated. You can only add up to 3 features per post.</p>';
    }

    ?>
    <label for="post_features">Features:</label><br>
    <div class="post-features-container">
        <?php
        $features = get_post_meta($post->ID, '_post_features', true);
        $feature_limit = $plugin_activated ? PHP_INT_MAX : 3; // If activated, set limit to infinity

        if (!empty($features)) {
            foreach ($features as $index => $feature) {
                echo '<div class="feature-box">';
                echo '<p class="feature-name"><input type="text" name="feature_name[]" value="' . esc_attr($feature['name']) . '" placeholder="Feature Name"></p>';
                echo '<p class="feature-rating"><input type="number" name="feature_rating[]" value="' . esc_attr($feature['rating']) . '" placeholder="Rating" min="1" max="10"></p>';
                echo '<button type="button" class="button button-secondary remove-feature-btn" data-index="' . esc_attr($index) . '">Remove</button>';
                echo '</div>';
            }
        }
        ?>
    </div>
    <button type="button" class="button button-secondary" id="add-feature-btn">Add Feature</button>
    <script>
        // JavaScript to dynamically add new feature fields and remove features
        document.getElementById('add-feature-btn').addEventListener('click', function() {
            var container = document.querySelector('.post-features-container');
            var featureBox = document.createElement('div');
            featureBox.classList.add('feature-box');
            featureBox.innerHTML = '<p class="feature-name"><input type="text" name="feature_name[]" placeholder="Feature Name"></p>' +
                                  '<p class="feature-rating"><input type="number" name="feature_rating[]" placeholder="Rating" min="1" max="10"></p>' +
                                  '<button type="button" class="button button-secondary remove-feature-btn">Remove</button>';
            container.appendChild(featureBox);
        });

        // Event delegation to handle removing features
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('remove-feature-btn')) {
                var featureBox = e.target.closest('.feature-box');
                if (featureBox) {
                    featureBox.remove();
                }
            }
        });
    </script>
    <?php
}

// Save post features meta box data
function save_post_features_meta_box_data($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['feature_name']) && isset($_POST['feature_rating'])) {
        $features = array();
        $plugin_activated = get_option('plugin_activated', false);

        if (!$plugin_activated) {
            $cou = 0;
            foreach ($_POST['feature_name'] as $key => $name) {
                $rating = isset($_POST['feature_rating'][$key]) ? intval($_POST['feature_rating'][$key]) : 1;

                $features[] = array(
                    'name'   => sanitize_text_field($name),
                    'rating' => max(1, min(10, $rating)), // Ensure rating is between 1 and 10
                );
                $cou += 1;
                if ($cou >= 3) {
                    break;
                }
            }
        } else {
            foreach ($_POST['feature_name'] as $key => $name) {
                $rating = isset($_POST['feature_rating'][$key]) ? intval($_POST['feature_rating'][$key]) : 1;

                $features[] = array(
                    'name'   => sanitize_text_field($name),
                    'rating' => max(1, min(10, $rating)), // Ensure rating is between 1 and 10
                );
            }
        }

        update_post_meta($post_id, '_post_features', $features);
    }
}
add_action('save_post', 'save_post_features_meta_box_data');


// Add Activation Page
function add_activation_page() {
    add_menu_page('Activation', 'Activation', 'manage_options', 'activation', 'activation_page');
}

function generate_rating_card($overallRating, $features) {
    echo '<div class="aps-rating-card">';
    echo '<div class="aps-rating-text-box">';
    echo '<h3 class="no-margin uppercase">Our Rating</h3>';
    echo '<p><em>The overall rating is based on review by our experts</em></p>';
    echo '</div>';

    echo '<div class="aps-rating-bar-box">';
    echo '<div class="aps-overall-rating aps-animated" data-bar="true" data-rating="' . esc_attr($overallRating) . '" itemprop="aggregateRating" itemtype="https://schema.org/AggregateRating" itemscope="">';
    echo '<span class="aps-total-wrap">';
    echo '<span class="aps-total-bar aps-orange-bg" data-type="bar" style="width: ' . esc_attr($overallRating * 10) . '%;"></span>';
    echo '</span>';
    echo '<span class="aps-rating-total" data-type="num">' . esc_html($overallRating) . '</span>';
    echo '<meta itemprop="ratingValue" content="' . esc_attr($overallRating) . '">';
    echo '<meta itemprop="reviewCount" content="' . count($features) . '">';
    echo '</div>';
    echo '<div class="meta-elems" itemprop="review" itemtype="https://schema.org/Review" itemscope="">';
    echo '<meta itemprop="name" content="' . esc_attr($features[0]['name']) . '">';
    echo '<meta itemprop="reviewBody" content="' . esc_attr($features[0]['reviewBody']) . '">';
    echo '<span itemprop="author" itemtype="https://schema.org/Organization" itemscope="">';
    echo '<meta itemprop="name" content="' . esc_attr($features[0]['author']) . '">';
    echo '</span>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="clear"></div>';
    
    echo '<ul class="aps-pub-rating aps-row clearfix">';
    foreach ($features as $feature) {
        $color = '';

    if (esc_attr($feature['rating']) <= 3) {
        $color = '#e32139'; // Red for low rating
    } else if (esc_attr($feature['rating']) <= 7) {
        $color = '#f38522'; // Orange for mid-range rating
    } else {
        $color = '#5cb85c'; // Green for high rating
    }




        echo '<li>';
        echo '<div class="aps-rating-box aps-animated" data-bar="true" data-rating="' . esc_attr($feature['rating']) . '">';
        echo '<span class="aps-rating-asp">';
        echo '<strong>' . esc_html($feature['name']) . '</strong>';
        echo '<span class="aps-rating-num">';
        echo '<span class="aps-rating-fig" data-type="num">' . esc_html($feature['rating']) . '</span> / 10';
        echo '</span>';
        echo '</span>';
        echo '<span class="aps-rating-wrap">';
        echo '<span class="aps-rating-bar -bg" data-type="bar" style="width: ' . esc_attr($feature['rating'] * 10) . '%; background-color:' . esc_attr($color) . ' ;"></span>';
        echo '</span>';
        echo '</div>';
        echo '</li>';
    }
    echo '</ul>';
    
    echo '</div>';
}


$features = array(
    array('name' => 'Design', 'rating' => 7, 'barColor' => 'orange'),
    array('name' => 'Display', 'rating' => 5, 'barColor' => 'orange'),
    array('name' => 'Performance', 'rating' => 2, 'barColor' => 'red'),
    // Add more features as needed
);





add_action('admin_menu', 'add_activation_page');

function display_post_features_shortcode($atts) {
    ob_start();

    // Fetch all features for the current post
    $features = get_post_meta(get_the_ID(), '_post_features', true);

    // Calculate the average rating
    
   






    // Output the rating container and average rating
   
    // Output each feature with its rating and bar
    if($features){
        $total_ratings = count($features);
        $overallRating = $total_ratings > 0 ? array_sum(wp_list_pluck($features, 'rating')) / $total_ratings : 0;
        $overallRating=round($overallRating);
        generate_rating_card($overallRating, $features);
    }

    echo '</div>'; // Close rating-container

    return ob_get_clean();
}
add_shortcode('display_post_features', 'display_post_features_shortcode');
