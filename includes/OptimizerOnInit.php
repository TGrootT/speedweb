<?php

namespace TenWebOptimizer;

class OptimizerOnInit
{
    public function __construct()
    {
        add_action( 'init', array( $this, 'two_register_meta'));
        add_action( 'init', array( $this, 'two_plugin_add_new_image_size'));
        add_action( 'admin_bar_menu', array( $this, 'two_admin_bar'), 999999);
        if (strtolower(TWO_SO_ORGANIZATION_NAME) == '10web' && !\TenWebOptimizer\OptimizerUtils::is_paid_user()) {
            add_action('enqueue_block_editor_assets', array($this, 'two_block_editor_assets'));

            if (is_plugin_active( 'elementor/elementor.php' )) {
                require_once __DIR__.'/OptimizerElementor.php';
                new OptimizerElementor();
            }
        }
        // Add Optimize column to the posts list table.
        add_filter( 'manage_post_posts_columns', array( $this, 'two_add_column_to_posts'));
        add_filter( 'manage_page_posts_columns', array( $this, 'two_add_column_to_posts'));
        add_filter( 'cron_schedules', array( $this, 'two_nps_cron_schedule' ) );
        if ( ! wp_next_scheduled( 'two_check_nps' ) ) {
            $nps_data = get_option('two_nps_data');
            $domain_id = get_site_option('tenweb_domain_id');
            if ((!defined('TWO_INCOMPATIBLE_ERROR') || !TWO_INCOMPATIBLE_ERROR)
                && OptimizerUtils::is_tenweb_booster_connected()
                && !TENWEB_SO_HOSTED_ON_10WEB && strtolower(TWO_SO_ORGANIZATION_NAME) == '10web' ) {
                if (!isset($nps_data['nps']) && isset($domain_id) ) {
                    wp_schedule_event(time(), 'every_six_hours', 'two_check_nps' );
                }
            }
        }
        add_action( 'two_check_nps', array( $this, 'two_check_nps_data' ));
        add_action('admin_notices', array( $this, 'two_admin_nps_survey' ));
        add_action( 'wp_ajax_two_send_nps_survey_data', array( $this, 'two_send_nps_survey_data' ));
        // Call the action on finishing the given page optimization.
        add_action( 'two_page_optimized', array( $this, 'two_page_optimized'));
        // Call the action on removing the page critical CSS.
        add_action('two_page_optimized_removed', array( $this, 'two_page_optimized_removed'));

        add_action( 'wp_ajax_two_optimized_notif_closed', array( $this, 'two_optimized_notif_closed' ));
        add_action( 'wp_ajax_two_is_page_optimized', array( $this, 'two_is_page_optimized' ));
        add_action( 'wp_ajax_two_recount_score', array( $this, 'two_recount_score' ));
        add_action( 'wp_ajax_two_get_page_score', array( $this, 'two_get_page_score' ));
        add_action( 'wp_ajax_two_get_optimized_images', array( $this, 'two_get_optimized_images' ));
        add_action( 'wp_ajax_two_sign_up_dashboard_magic_link', array( $this, 'two_sign_up_dashboard_magic_link' ));
    }

    public function two_register_meta() {
        $allowed_post_types = array('post', 'page');
        foreach ($allowed_post_types as $type) {
            register_post_meta($type, 'two_page_speed', [
                'show_in_rest' => array(
                    'schema' => array(
                        'type' => 'object',
                        'properties' => array(
                            'previous_score' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'desktop_score' => array(
                                        'type' => 'number',
                                    ),
                                    'desktop_tti' => array(
                                        'type' => 'string',
                                    ),
                                    'mobile_score' => array(
                                        'type' => 'number',
                                    ),
                                    'mobile_tti' => array(
                                        'type' => 'string',
                                    ),
                                    'date' => array(
                                        'type' => 'string',
                                    ),
                                    'status' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'current_score' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'desktop_score' => array(
                                        'type' => 'number',
                                    ),
                                    'desktop_tti' => array(
                                        'type' => 'string',
                                    ),
                                    'mobile_score' => array(
                                        'type' => 'number',
                                    ),
                                    'mobile_tti' => array(
                                        'type' => 'string',
                                    ),
                                    'date' => array(
                                        'type' => 'string',
                                    ),
                                    'status' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'single' => TRUE,
                'type' => 'object'
            ]);
        }
    }

    public function two_plugin_add_new_image_size() {
        add_image_size('tenweb_optimizer_mobile', 600, 600, false);
        add_image_size('tenweb_optimizer_tablet', 768, 1024, false);
    }

    /* Run admin bar functionality */
    public function two_admin_bar( $wp_admin_bar ) {
        /* post status not to show the admin bar */
        $post_status = array(
            'private',
            'future',
            'draft'
        );
        if ( (isset($_GET['post']) && isset($_GET['action']) && sanitize_text_field($_GET['action']) == 'edit')
            || ( strtolower(TWO_SO_ORGANIZATION_NAME) != '10web' )
            || ( get_the_ID() && array_search(get_post(get_the_ID())->post_status,$post_status) !== false )
            /* remove admin bar for mailpoet plugin(it is blocked all other plugins styles and scripts)*/
            || (isset($_GET['page']) && ( strpos( $_GET['page'], 'mailpoet' ) !== false) )
            // Do not show admin topbar on some pages that break it. (Gravity Forms)
            || ( isset($_GET['page']) && ( 0 === strpos( $_GET['page'], 'gf_' )))
            || !current_user_can('administrator') ) {
            // Do not show admin topbar on Booster page.
            return false;
        }

        require_once TENWEB_SO_PLUGIN_DIR . '/OptimizerAdminBar.php';
        new OptimizerAdminBar($wp_admin_bar);
    }

    public function two_block_editor_assets() {
        //check non-cached pages
        $urlIsOptimizable = false;
        if ( get_the_ID() ) {
            $page_url = get_permalink(get_the_ID());
            $urlIsOptimizable = \TenWebOptimizer\OptimizerUrl::urlIsOptimizable($page_url);
        }
        if ( $urlIsOptimizable && current_user_can('administrator')) {
            wp_enqueue_script('two-sidebar-plugin', TENWEB_SO_URL . '/assets/js/gutenberg/sidebar-plugin-compiled.js', array(
                'wp-plugins',
                'wp-edit-post'
            ), TENWEB_SO_VERSION);
            wp_localize_script('two-sidebar-plugin', 'two_speed', array(
                'nonce' => wp_create_nonce('two_ajax_nonce'),
                'ajax_url' => admin_url('admin-ajax.php'),
                'optimize_entire_website' => self::two_reached_limit(),
                'critical_pages' => \TenWebOptimizer\OptimizerUtils::getCriticalPages(),
            ));
            wp_enqueue_style('two_speed_css', TENWEB_SO_URL . '/assets/css/speed.css', array('two-open-sans'), TENWEB_SO_VERSION);
        }
    }

    public function two_add_column_to_posts( $columns ) {
        if ( \TenWebOptimizer\OptimizerUtils::is_paid_user() ) {
            return $columns;
        }

        $offset = array_search('author', array_keys($columns));

        return array_merge(array_slice($columns, 0, $offset), [ 'two-speed' => '<b>' . TWO_SO_ORGANIZATION_NAME . ' Booster' . '</b>' ], array_slice($columns, $offset, NULL));
    }

    public function two_nps_cron_schedule( $schedules ) {
        $schedules['every_six_hours'] = array(
            'interval' => 21600, // Every 6 hours
            'display'  => __( 'Every 6 hours' ),
        );
        return $schedules;
    }

    public function two_check_nps_data() {
        OptimizerNPS::update_nps_survey_data();
    }

    public function two_admin_nps_survey() {
        global $pagenow;
        if ( $pagenow == 'index.php' || $pagenow == 'edit.php' ) {
            require_once TENWEB_SO_PLUGIN_DIR . '/views/nps_survey_popup.php';
        }
    }

    public function two_send_nps_survey_data() {
        $nonce = isset($_POST["nonce"]) ? sanitize_text_field($_POST["nonce"]) : '';

        if (!wp_verify_nonce($nonce, 'two_ajax_nonce')) {
            die('Permission Denied.');
        }
        $nps_rate = isset($_POST["nps_rate"]) ? (int)($_POST["nps_rate"]) : '';
        $nps_from = isset($_POST["nps_from"]) ? sanitize_text_field($_POST["nps_from"]) : '';
        $nps_data = get_option('two_nps_data');
        $nps_data['show_nps_survey'] = 0;
        update_option( //just to be a 100% sure it won't show the banner again
            'two_nps_data',
            $nps_data,
            false
        );
        if ( $nps_rate == 10 ) {
            $nps_data['nps'] = $nps_rate;
            $nps_data['show_share_love'] = 1;
        } else if ( $nps_rate == 11 ) {
            //this is local per site solution now, so why we are not updating rate in performance in this case
            $nps_data['show_share_love'] = 0;
        } else {
            $nps_data['nps'] = $nps_rate;
            $args = array( 'nps' => $nps_rate, 'source' => $nps_from );
            OptimizerNPS::set_nps_survey_data( $args );
        }
        update_option(
            'two_nps_data',
            $nps_data,
            false
        );

        wp_send_json_success(array('status' => 'success'));
    }

    public function two_page_optimized($post_id) {
        if ($post_id == 'front_page') {
            // If front page is a page and has ID, check and save the score also as post meta.
            $post_id = url_to_postid(get_home_url());
            if ($post_id) {
                \TenWebSC\TWScoreChecker::twsc_check_score($post_id);
            }
        }
        else {
            \TenWebSC\TWScoreChecker::twsc_check_score($post_id);
        }
        \TenWebWpTransients\OptimizerTransients::delete('two_optimize_inprogress_' . $post_id );
        /* Keeping all posts statuses which is optimized and notif popup view is not shown yet */
        $two_optimization_notif_status = get_option('two_optimization_notif_status');
        $two_optimization_notif_status[$post_id] = 'optimized_not_closed';
        update_option('two_optimization_notif_status', $two_optimization_notif_status, 1);
    }

    public function two_page_optimized_removed($post_id) {
        if ( $post_id == 'front_page' ) {
            delete_option('two-front-page-speed');
            // If front page is a page and has ID, check and save the score also as post meta.
            $post_id = url_to_postid(get_home_url());
            if ($post_id) {
                delete_post_meta($post_id, 'two_page_speed');
            }
        }
        else {
            delete_post_meta($post_id, 'two_page_speed');
        }
        \TenWebWpTransients\OptimizerTransients::delete('two_optimize_inprogress_' . $post_id );
    }

    public function two_optimized_notif_closed() {
        $nonce = isset($_POST["nonce"]) ? sanitize_text_field($_POST["nonce"]) : '';

        if (!wp_verify_nonce($nonce, 'two_ajax_nonce')) {
            die('Permission Denied.');
        }

        $post_id = isset($_POST["post_id"]) ? intval($_POST["post_id"]) : 0;
        /* Keeping all posts statuses which is in progress or optimized to manage notif popup view one time for each case */
        $two_optimization_notif_status = get_option('two_optimization_notif_status');
        unset($two_optimization_notif_status[$post_id]);
        update_option('two_optimization_notif_status', $two_optimization_notif_status, 1);
        wp_send_json_success(array('status' => 'success'));
    }

    public function two_is_page_optimized() {
        $nonce = isset($_POST["nonce"]) ? sanitize_text_field( $_POST["nonce"] ) : '';

        if ( !wp_verify_nonce($nonce, 'two_ajax_nonce') ) {
            die('Permission Denied.');
        }

        $post_id = isset($_POST["post_id"]) ? intval( $_POST["post_id"] ) : 0;

        $page_score = get_post_meta( $post_id, 'two_page_speed', true );

        if ( !\TenWebWpTransients\OptimizerTransients::get('two_optimize_inprogress_' . $post_id) && !empty($page_score) ) {
            wp_send_json_success($page_score);
        }

        wp_send_json_error(array('status' => 'pending'));
    }

    public function two_recount_score() {
        $nonce = isset($_POST["nonce"]) ? sanitize_text_field( $_POST["nonce"] ) : '';

        if ( !wp_verify_nonce($nonce, 'two_ajax_nonce') ) {
            die('Permission Denied.');
        }

        $post_id = isset($_POST['post_id']) ? sanitize_text_field($_POST['post_id']) : 0;
        $reanalyze_score_for = isset($_POST['reanalyze_score_for']) ? sanitize_text_field($_POST['reanalyze_score_for']) : FALSE;

        $page_score = \TenWebSC\TWScoreChecker::twsc_recount_score( $post_id, $reanalyze_score_for );
        wp_send_json_success($page_score);
    }

    /* Get page score info for js */
    public function two_get_page_score() {
        $nonce = isset($_POST["nonce"]) ? sanitize_text_field( $_POST["nonce"] ) : '';

        if ( !wp_verify_nonce($nonce, 'two_ajax_nonce') ) {
            die('Permission Denied.');
        }

        $post_id = isset($_POST['post_id']) ? sanitize_text_field($_POST['post_id']) : 0;

        if ( $post_id == 'front_page' || $post_id == get_option('page_on_front') ) {
            $page_score = get_option('two-front-page-speed');
        }
        else {
            $page_score = get_post_meta($post_id, 'two_page_speed', TRUE);
        }

        wp_send_json_success($page_score);
    }

    /* Get website images total count and optimized images count from endpoint */
    public function two_get_optimized_images() {
        $nonce = isset($_POST["nonce"]) ? sanitize_text_field( $_POST["nonce"] ) : '';

        if ( !wp_verify_nonce($nonce, 'two_ajax_nonce') ) {
            die('Permission Denied.');
        }

        $two_images_count = \TenWebWpTransients\OptimizerTransients::get("two_images_count");

        if ( !empty($two_images_count) ) {
            return;
        }
        $workspace_id = (int)get_site_option(TENWEBIO_MANAGER_PREFIX . '_workspace_id', 0);
        $domain_id = (int)get_option(TENWEBIO_MANAGER_PREFIX . '_domain_id', 0);
        $access_token = get_site_option('tenweb_access_token');
        $url = TENWEBIO_API_URL . "/compress/workspaces/" . $workspace_id . "/domains/" . $domain_id . "/stat";
        $args = array(
            'timeout' => 15,
            'headers' => array(
                "accept" => "application/x.10weboptimizer.v3+json",
                "authorization" => "Bearer " . $access_token,
            ),
        );
        $response = wp_remote_get($url, $args);
        $images_data = array();
        if ( is_array($response) && !is_wp_error($response) ) {
            $body = json_decode($response['body'], 1);
            if ( isset($body['status']) && $body['status'] == 200 ) {
                $data = $body['data'];

                $total_not_compressed_images_count = (int)($data['not_compressed']['full'] + $data['not_compressed']['thumbs'] + $data['not_compressed']['other']);
                $total_compressed_images_count = (int)($data['compressed']['full'] + $data['compressed']['thumbs'] + $data['compressed']['other']);
                $total_images_count = (int)($total_not_compressed_images_count + $total_compressed_images_count);
                $pages_compressed = $data['pages_compressed'];
                $count = 0;
                foreach ( $pages_compressed as $page ) {
                    $count += $page['images_count'];
                }
                $images_data = array('total_images_count' => (int)$total_images_count, 'optimized_images_count' => (int)$count);
                \TenWebWpTransients\OptimizerTransients::set( 'two_images_count', $images_data, DAY_IN_SECONDS );
            }
        } else {
            $images_data = array('total_images_count' => 0, 'optimized_images_count' => 0);
            \TenWebWpTransients\OptimizerTransients::set( 'two_images_count', $images_data, DAY_IN_SECONDS );
        }

        wp_send_json_success($images_data);
    }

    // Check if optimized pages limit reached.
    public static function two_reached_limit() {
        if ( !\TenWebOptimizer\OptimizerUtils::is_paid_user() && count(\TenWebOptimizer\OptimizerUtils::getCriticalPages()) >= 6 ) {
            $domain_id = intval(get_option(TENWEBIO_MANAGER_PREFIX . '_domain_id', 0));
            return TENWEB_DASHBOARD . "/websites/" . $domain_id . "/booster/pro";
        }
        return false;
    }
}