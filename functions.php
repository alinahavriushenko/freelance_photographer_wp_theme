<?php


if ( ! isset( $content_width ) ) {
	$content_width = 800; /* pixels */
}


if ( ! function_exists( 'myfirsttheme_setup' ) ) :

	/**
	 * Sets up theme defaults and registers support for various
	 * WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme
	 * hook, which runs before the init hook. The init hook is too late
	 * for some features, such as indicating support post thumbnails.
	 */
	function theme_setup() {

		/**
		 * Make theme available for translation.
		 * Translations can be placed in the /languages/ directory.
		 */
		load_theme_textdomain( 'freelance-theme', get_template_directory() . '/languages' );

		/**
		 * Add default posts and comments RSS feed links to <head>.
		 */
		add_theme_support( 'automatic-feed-links' );

		/**
		 * Enable support for post thumbnails and featured images.
		 */
		add_theme_support( 'post-thumbnails' );

		/**
		 * Add support for two custom navigation menus.
		 */
		register_nav_menus( array(
			'main-menu'   => __( 'Primary Menu', 'freelance-theme' ),
            'footer' => __( 'Footer Menu', 'freelance-theme' ),
            'mobile-menu' => __('Mobile Menu', 'freelance-theme'),
		) );

		/**
		 * Enable support for the following post formats:
		 * aside, gallery, quote, image, and video
		 */
		add_theme_support( 'post-formats', array( 'aside', 'gallery', 'quote', 'image', 'video' ) );
        add_theme_support( 'custom-logo' );


        wp_enqueue_style( 'style', get_stylesheet_uri() );
	}
endif;
add_action( 'after_setup_theme', 'theme_setup' );


// menu classes

function menu_class($classes)
{
    $classes[] = 'nav-item';
    return $classes;
}

function menu_link_class($attrs)
{
    $attrs['class'] = 'nav-link';
    return $attrs;
}
add_filter('nav_menu_css_class', 'menu_class');
add_filter('nav_menu_link_attributes', 'menu_link_class');

// enqueue js

function enqueue_custom_scripts() {

	wp_enqueue_script('jquery');
    wp_enqueue_script('custom-script', get_template_directory_uri() . '/js/scripts.js', array('jquery'), '1.0', true);

}
add_action('wp_enqueue_scripts', 'enqueue_custom_scripts');


// Localize the reference PHP value

	function reference_value() {

	$reference = get_field('reference');
    wp_localize_script('custom-script', 'php_vars', array(
        'reference' => esc_attr($reference),
    ));
}

add_action('wp_enqueue_scripts', 'reference_value');

// Google Fonts

function enqueue_custom_fonts() {

    wp_enqueue_style('custom-fonts', get_stylesheet_directory_uri() . '/assets/fonts/fonts.css', array(), '1.0.0', 'all');
}

add_action('wp_enqueue_scripts', 'enqueue_custom_fonts');

// Font Awesome

function enqueue_font_awesome() {
    wp_enqueue_style('fontawesome', get_template_directory_uri() . '/assets/fonts/fontawesome6/css/fontawesome.css');
    wp_enqueue_style('fontawesome-brands', get_template_directory_uri() . '/assets/fonts/fontawesome6/css/brands.css');
    wp_enqueue_style('fontawesome-solid', get_template_directory_uri() . '/assets/fonts/fontawesome6/css/solid.css');
}
add_action('wp_enqueue_scripts', 'enqueue_font_awesome');

// Ajax & Lightbox

function load_more_photos_scripts() {
    // Enqueue 'load-more-script' and 'load-lightbox' JavaScript files
    wp_enqueue_script('load-more-script', get_template_directory_uri() . '/js/load-more.js', array('jquery'), '1.0', true);
    wp_enqueue_script('load-lightbox', get_template_directory_uri() . '/js/lightbox.js', array('jquery'), '1.0', true);

    // Localize 'load-more-script' to pass AJAX URL
    wp_localize_script('load-more-script', 'load_more_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));

    // Localize 'load-lightbox' to pass AJAX URL
    wp_localize_script('load-lightbox', 'load_lightbox_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
}
add_action('wp_enqueue_scripts', 'load_more_photos_scripts');



// Load more button

function load_more_photos() {
    
    $page = $_POST['page']; // Get the page number sent via POST request
    $args = array(
        'post_type' => 'photo',
        'posts_per_page' => 12,
        'paged' => $page,
    );

    $custom_photos_query = new WP_Query($args);

    if ($custom_photos_query->have_posts()) {
        while ($custom_photos_query->have_posts()) {
            $custom_photos_query->the_post();
            get_template_part('templates_parts/photo_block'); // Load and display the template part for each photo
        }
        wp_reset_postdata(); // Reset post data
    }

    die(); // End the script execution
}
add_action('wp_ajax_load_more_photos', 'load_more_photos');
add_action('wp_ajax_nopriv_load_more_photos', 'load_more_photos');


// Photo filters

function load_all_photos() {
    $args = array(
        'post_type' => 'photo',
        'posts_per_page' => 8,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    $custom_photos_query = new WP_Query($args);

    ob_start();
    if ($custom_photos_query->have_posts()) {
        while ($custom_photos_query->have_posts()) {
            $custom_photos_query->the_post();
            get_template_part('templates_parts/photo_block');
        }
    } else {
        echo 'Aucune photo trouvée';
    }
    wp_reset_postdata();
    $photo_output_html = ob_get_clean();

    echo $photo_output_html;
    wp_die();
}

// Retrieve the photo categories

function categories() {
    $categories = get_terms(array(
        'taxonomy' => 'categorie',
        'hide_empty' => false,
    ));

    $options = '<option value="">Catégories</option>';
    foreach ($categories as $category) {
        $options .= '<option value="' . $category->slug . '">' . $category->name . '</option>';
    }

    echo $options;
    wp_die();
}

// Retrieve the photo formats

function formats() {
	$formats=get_terms(array(
        'taxonomy' => 'format',
        'hide_empty' => false,
    ));

    $options = '<option value="">Formats</option>';
	foreach ($formats as $format) {
		$options .= '<option value="' . $format->slug . '">' . $format->name . '</option>';
	}

    echo $options;
    wp_die();
}

// Filter the photos

function filter_photos() {
    $category = sanitize_text_field($_POST['category']);
    $format = sanitize_text_field($_POST['format']);
    $sortByDate = sanitize_text_field($_POST['sortByDate']);

     $args = array(
        'post_type' => 'photo',
        'posts_per_page' => 8,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    // adjust sorting by date

    if (empty($category) && empty($format)) {
        if ($sortByDate === 'ASC' || $sortByDate === 'DESC') {
            $args['order'] = $sortByDate;
        }
    } else {
        $args['order'] = $sortByDate;
        $args['tax_query'] = array(
            'relation' => 'OR',
            array(
                'taxonomy' => 'categorie',
                'field' => 'slug',
                'terms' => $category,
            ),
            array(
                'taxonomy' => 'format',
                'field' => 'slug',
                'terms' => $format,
            ),
        );
    }

    $custom_photos_query = new WP_Query($args);


    ob_start();
    if ($custom_photos_query->have_posts()) {
        while ($custom_photos_query->have_posts()) {
            $custom_photos_query->the_post();
            get_template_part('templates_parts/photo_block');
        }
    } else {
        echo 'Aucune photo trouvée';
    }
    wp_reset_postdata();
    $photo_output_html = ob_get_clean();

    echo $photo_output_html;
    wp_die();
}

// Register AJAX actions for filtering photos
add_action('wp_ajax_load_all_photos', 'load_all_photos');
add_action('wp_ajax_nopriv_load_all_photos', 'load_all_photos');
add_action('wp_ajax_get_categories', 'categories');
add_action('wp_ajax_nopriv_get_categories', 'categories');
add_action('wp_ajax_get_formats', 'formats');
add_action('wp_ajax_nopriv_get_formats', 'formats');
add_action('wp_ajax_filter_photos', 'filter_photos');
add_action('wp_ajax_nopriv_filter_photos', 'filter_photos');