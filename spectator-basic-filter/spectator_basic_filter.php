<?php

/**
 * Plugin Name: Spectator Basic Filter
 * Author: Wishbone.Design
 * Version: 1.0
 * License: GPLv2 or later
 * Description: Spectator Basic Filter
 */

defined('ABSPATH') || exit;

/**
 * WC_Admin_Importers Class.
 */




class WP_SPECTATOR_BASIC_FILTER
{

    public static $directory_path;
    public static $directory_url;
    public static $plugin_slug = 'spectator-basic-filter';
    public static $plugin_basename = ''; // Values set at function `set_plugin_vars`

    /**
     * Constructor.
     */
    public function __construct()
    {

        // Add Custom Meta Box
        add_action( 'add_meta_boxes', array( $this, 'custom_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_primary_category_meta' ) );

        $this->set_plugin_vars();
        $this->hooks();
        $this->include_shorcodes();
        $this->register_styles();
    }
    // Add Custom Meta Box functions Start
    public function custom_meta_box() {
        add_meta_box(
            'primary_category', // ID
            'Primary Category', // Title
            array($this,'primary_category_callback'), // Callback function
            'post', // Post type
            'side', // Context
            'default' // Priority
        );
    }
    public function primary_category_callback( $post ) {
            wp_nonce_field( basename( __FILE__ ), 'primary_category_nonce' );
            $primary_category = get_post_meta( $post->ID, 'primary_category', true );
            $categories = get_categories( array(
                'hide_empty' => false,
            ) );
            $used_categories = array();
            $args = array(
                'post_type' => 'post',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'primary_category',
                        'value' => '',
                        'compare' => '!=',
                    ),
                ),
            );
            $query = new WP_Query( $args );
            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    $used_categories[] = get_post_meta( get_the_ID(), 'primary_category', true );
                }
            }
            wp_reset_postdata();
            $used_categories = array_unique( $used_categories );
            ?>
            <p>
                <label for="primary_category">Primary Category:</label>
                <select name="primary_category" style="display: block;width: 80%;max-width: 100%;margin: 10px 0;">
                    <?php foreach ( $categories as $category ) : ?>
                        <?php if ( $category->slug !== 'uncategorized' ) : ?>
                        <option value="<?php echo $category->slug; ?>" <?php selected( $primary_category, $category->slug ); ?>><?php echo $category->name; ?></option>
                        <?php endif; ?>
                    <?php  endforeach; ?>
                    <?php foreach ( $used_categories as $category_slug ) : ?>
                        <?php if ( ! in_array( $category_slug, wp_list_pluck( $categories, 'slug' ) ) ) : ?>
                            <?php $category = get_term_by( 'slug', $category_slug, 'category' ); ?>
                            <option value="<?php echo $category->slug; ?>" <?php selected( $primary_category, $category->slug ); ?>><?php echo $category->name; ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="new_primary_category" placeholder="Add new primary category" style="display: block;width: 94%;max-width: 100%;"/>
            </p>
            <?php
        }
    public function save_primary_category_meta( $post_id ) {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }

            if ( ! isset( $_POST['primary_category_nonce'] ) || ! wp_verify_nonce( $_POST['primary_category_nonce'], basename( __FILE__ ) ) ) {
                return;
            }

            if ( isset( $_POST['primary_category'] ) ) {
                update_post_meta( $post_id, 'primary_category', sanitize_text_field( $_POST['primary_category'] ) );
            }

            if ( isset( $_POST['new_primary_category'] ) ) {
                $new_category = sanitize_text_field( $_POST['new_primary_category'] );
                $slug = sanitize_title( $new_category );
                $cat_id = wp_insert_category( array(
                    'cat_name' => $new_category,
                    'category_nicename' => $slug,
                    'category_parent' => 0,
                    'taxonomy' => 'category',
                ) );
                if ( ! is_wp_error( $cat_id ) ) {
                    update_post_meta( $post_id, 'primary_category', $slug );
                }
            }
        }
    // Add Custom Meta Box functions End
    /**
     * Include Hooks.
     */
    public function hooks()
    {
        //hooks
        add_action('wp_ajax_get_product_data', array($this, 'get_product_data'));
        add_action('wp_ajax_nopriv_get_product_data', array($this, 'get_product_data'));
    }

    /**
     * Define plugin variables.
     */
    public function set_plugin_vars()
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        self::$directory_path = plugin_dir_path(__FILE__);
        self::$directory_url = plugin_dir_url(__FILE__);
        self::$plugin_basename = plugin_basename(__FILE__);
    }

    /**
     * Include Shortcodes.
     */
    public function include_shorcodes()
    {
        //Shortcode
        add_shortcode('spectator_basic_filter', array($this, 'spectator_basic_shortcode'));

    }


    /**
     * Register_styles.
     */
    public function register_styles()
    {
        // files
        wp_register_style('bootstrap-css','https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css');
        wp_register_style('spectator-filter-css', self::$directory_url . 'css/spectator_filter.css', array(), microtime());

        wp_register_style('flatpickr-css', self::$directory_url . 'css/flatpickr.min.css');


        wp_register_script('popper', 'https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js', array('jquery'), null, true);
        wp_register_script('spectator-filter-js', self::$directory_url . 'js/spectator_filter.js', array('jquery'), null, true);
        wp_register_script('bootstrap-js','https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js', array('jquery'), null, true);
        wp_register_script('flatpickr-js', self::$directory_url . 'js/flatpickr.min.js', array('jquery'), null, true);


        wp_register_script('vue-js', 'https://cdn.jsdelivr.net/npm/vue/dist/vue.js', array('jquery'), null, true);


    }


    /**
     * Enqueue_Files.
     */
    public function enqueue_files()
    {
        // files
        wp_enqueue_style('flatpickr-css');
        wp_enqueue_style('spectator-filter-css');
        wp_enqueue_style('bootstrap-css');


        wp_enqueue_script('popper');
        wp_enqueue_script('bootstrap-js');
        wp_enqueue_script('flatpickr-js');

        wp_enqueue_script('vue-js');
        wp_enqueue_script('spectator-filter-js');

        $localize = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
        );
        wp_localize_script('spectator-filter-js', 'spectatorObj', $localize);

    }


    function spectator_basic_shortcode($atts)
    {

        $this->enqueue_files();

        $prime_cat = !empty($_GET['prime_cat']) ? $_GET['prime_cat'] : '';
//        $page = isset($atts["search_page"]) ? true : false;


        // Get all primary categories
        $primary_categories = array();
        $args = array(
            'meta_key' => 'primary_category',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'posts_per_page' => -1
        );
        $query = new WP_Query($args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $primary_category = get_post_meta(get_the_ID(), 'primary_category', true);
                if (!in_array($primary_category, $primary_categories)) {
                    $primary_categories[] = $primary_category;
                }
            }
        }
        wp_reset_query();

        // Create select box with primary categories
        $select_options = '';

        $select_options .= '<a href="#" data-prime_cat="" class="spectator-item link-loc link dropdown-item"><div class="spectator-item">Choose A Category</div></a>';

        foreach ($primary_categories as $category) {
            $select_options .= ' <a href="#" data-prime_cat="' . $category . '" class="spectator-item link-loc link dropdown-item">'.ucwords(str_replace('-', ' ', $category)).'</a>';
        }

        $select_box = ' <div class="spectator-dropdown dropdown show"> 
                        <a class="btn btn-secondary dropdown-toggle" href="#" role="button" id="destinationLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">'
                                . ($prime_cat != '' ? $prime_cat : 'Choose A Category') . '</a>
                    
                        <div class="dropdown-menu" aria-labelledby="destinationLink" style="z-index: 9999 !important"> ' . $select_options . '</div>
                    </div>';

        ob_start();
        ?>
        <div id="spectatorItemFilter">
            <div class="container">
                <div class="row">
                    <div class="col-md-4">
                        <?php echo $select_box ?>
                    </div>
                    <div class="col-md-8"></div>
                </div>
                <div class="row" id="post_container">
                    <?php
                    if($prime_cat != '') {
                    $args = array (
                        'post_type' => 'post',
                        'meta_query' => array(
                            array(
                                'key' => 'primary_category',
                                'value' => $prime_cat,
                                'compare' => '=',
                            )
                        )
                    );
                    }else{
                        $args = array(
                            'post_type' => 'post',
                            'meta_key' => 'primary_category',
                            'order' => 'ASC',
                            'posts_per_page' => -1
                        );
                    }

                    $query = new WP_Query($args);
                    while ($query->have_posts()) : $query->the_post(); ?>
                        <div class="col-md-4">
                            <div class="item-post">
                                <div class="post-img">
                                    <?php the_post_thumbnail(); ?>
                                </div>
                                <a href="<?php echo get_the_permalink(); ?>"><h4 class="post-title"><?php the_title(); ?></h4></a>
                            </div>
                        </div>
                    <?php endwhile;
                    wp_reset_postdata();
                    ?>
                </div>
            </div>
        </div>
        </div>

        <?php
        return ob_get_clean();
    }
}

new WP_SPECTATOR_BASIC_FILTER();


?>