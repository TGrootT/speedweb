<?php
use TenWebOptimizer\OptimizerCriticalCss;
use TenWebOptimizer\OptimizerUtils;
class OptimizerDataRepository{
    public $modes = array();
    public function __construct()
    {
        $this->modes = \TenWebOptimizer\OptimizerUtils::get_modes();
    }
    public function get_incompatible_active_plugins() {
        $data_for_response = array(
            'success'=>false,
            'message'=>"Cannot get incompatible plugins",
        );
        try {
            $data_for_response["success"] = true;
            $data_for_response["message"] = "Successfully";
            $two_incompatible_plugins =  OptimizerUtils::get_conflicting_plugins();
            $data_for_response["two_incompatible_plugins"] = $two_incompatible_plugins;
            return $data_for_response;
        } catch(Exception $exception) {
            return new $data_for_response;
        }
    }
    public function get_modes($page_id) {
        $data_for_response = array(
            'success' => false,
            'message' => "Mode not found",
        );

        try {
            if( $page_id === "all" ) {
                $mode = get_option( "two_default_mode", OptimizerUtils::MODES["extreme"] );
            } else if ($page_id === "front_page") {
                $mode = get_option( "two_mode_front_page" );
            } else if ( false !== strpos( $page_id, 'term_' ) ) {
                $term_id = (int)ltrim( $page_id, 'term_' );
                $mode = get_term_meta( $term_id, "two_mode", true );
            } else if ( false !== strpos( $page_id, 'user_' ) ) {
                $user_id = (int)ltrim( $page_id, 'user_' );
                $mode = get_user_meta( $user_id, "two_mode", true );
            } else {
                $mode = get_post_meta( $page_id, "two_mode", true );
            }
            if(is_array($mode) && isset($mode["mode"]) && isset($this->modes[$mode["mode"]])){
                $mode = $mode["mode"];
                $data_for_response["success"] = true;
                $data_for_response["message"] = "success";
                $data_for_response["mode"] = $mode;
            }
        } catch (Exception $exception) {
            $data_for_response['message'] = 'Error in getting modes';
            $data_for_response['error'] = $exception->getMessage().' in '.$exception->getFile().' on '.$exception->getLine();
            return $data_for_response;
        }

        return $data_for_response;
    }


    public function get_global_data(){
        $data_for_response = array(
            'success' => false,
            'message' => "Data not found",
            'data' => []
        );

        try {
            $global_mode = get_option("two_default_mode", OptimizerUtils::MODES["extreme"]);
            if (is_array($global_mode) && isset($global_mode["mode"])) {
                $global_mode = $global_mode["mode"];
            } else {
                $global_mode = false;
            }
            $count_posts = wp_count_posts( 'post' );
            $count_pages = wp_count_posts( 'page' );
            $count_terms = (int)get_terms(array('fields' => 'count', 'hide_empty' => false));
            $two_update_available = OptimizerUtils::check_plugin_update();
            global $TwoSettings;
            $global_data = array(
                'update_available' => $two_update_available,
                'site_url' => site_url(),
                'version'=> TENWEB_SO_VERSION,
                'global_mode' => $global_mode,
                'test_mode' => 'on' == $TwoSettings->get_settings("two_test_mode") ? 'on' : 'off',
                'page_count' => $count_pages->publish,
                'post_count' => $count_posts->publish,
                'term_count' => $count_terms,
            );
            $data_for_response['success'] = true;
            $data_for_response['message'] = 'Data found successfully';
            $data_for_response ["data"] = $global_data;
        } catch (Exception $exception) {
            $data_for_response['message'] = 'Error in getting data';
            $data_for_response['error'] = $exception->getMessage().' in '.$exception->getFile().' on '.$exception->getLine();
            return $data_for_response;
        }
        return $data_for_response;
    }

    public function get_pages($is_custom = 0) {

        $data_for_response = array(
            'success' => false,
            'message' => "Pages not found",
            'data' => []
        );

        try {
            $so_pages_list = array(
                'pages' => array(),
            );
            $two_optimized_pages = \TenWebOptimizer\OptimizerUtils::getCriticalPages();

            $args = array(
                'post_type' => 'page',
                'meta_key' => 'two_mode',
            );
            $optimized_posts = new WP_Query($args);
            if (isset($optimized_posts->posts)) {
                foreach ($optimized_posts->posts as $post) {
                    if (isset($post->ID) && !isset($two_optimized_pages[$post->ID])) {
                        $two_optimized_pages[$post->ID] = array(
                            'id' => $post->ID,
                            'title' => $post->post_title,
                            'url' => get_permalink( $post->ID ),
                            'status' => "success",
                        );
                    }
                }
            }
            if (is_array($two_optimized_pages)) {
                foreach ($two_optimized_pages as $so_page) {
                    $so_page_data = array(
                        'page_id' => $so_page["id"],
                        'title' => $so_page["title"],
                        'url' => $so_page["url"],
                        'status' => $so_page["status"],
                    );


                    if ($so_page["id"] === "front_page") {
                        $page_mode = get_option("two_mode_front_page");
                        $two_optimized_date_front_page = get_option("two_optimized_date_front_page");
                    }
                    else if ( false !== strpos( $so_page["id"], 'term_' ) ) {
                        $so_page["id"] = (int)ltrim( $so_page["id"], 'term_' );
                        $so_page_data["page_id"] = 'term_' . (int)ltrim( $so_page_data["page_id"], 'term_' );
                        $page_mode = get_term_meta($so_page["id"], "two_mode", true);
                        $two_optimized_date = get_term_meta($so_page["id"], "two_optimized_date", true);
                    }
                    else if ( false !== strpos( $so_page["id"], 'user_' ) ) {
                        $so_page["id"] = (int)ltrim( $so_page["id"], 'user_' );
                        $so_page_data["page_id"] = 'user_' . (int)ltrim( $so_page_data["page_id"], 'user_' );
                        $page_mode = get_user_meta($so_page["id"], "two_mode", true);
                        $two_optimized_date = get_user_meta($so_page["id"], "two_optimized_date", true);
                    }
                    else {
                        $so_page["id"] = (int)$so_page["id"];
                        $so_page_data["page_id"] = (int)$so_page_data["page_id"];
                        $page_mode = get_post_meta($so_page["id"], "two_mode", true);
                        $two_optimized_date = get_post_meta($so_page["id"], "two_optimized_date", true);
                    }
                    if (is_array($page_mode) && isset($page_mode["mode"])) {
                        $page_mode_name = $page_mode["mode"];
                    } else {
                        $page_mode_name = false;
                    }
                    $so_page_data["mode"] = $page_mode_name;
                    if ((int)$is_custom === 1) {
                        if (!isset($page_mode["is_custom"]) || !$page_mode["is_custom"]) {
                            continue;
                        }
                    }

                    if (isset($so_page["critical_date"])) {
                        $so_page_data["date"] = $so_page["critical_date"];
                    } else {
                        if (isset($two_optimized_date)) {
                            $so_page_data["date"] = $two_optimized_date;
                        } elseif (isset($two_optimized_date_front_page)) {
                            $so_page_data["date"] = $two_optimized_date_front_page;
                        }
                    }

                    $so_page_data["is_custom"] = 0;
                    if (isset($page_mode["is_custom"])) {
                        $so_page_data["is_custom"] = $page_mode["is_custom"];
                    }

                    $so_pages_list["pages"][] = $so_page_data;
                }
                $data_for_response["success"] = true;
                $data_for_response["message"] = "Pages found successfully";
                $data_for_response["data"] = $so_pages_list;
            }
        } catch (Exception $exception) {
            $data_for_response['message'] = 'Error in getting pages';
            $data_for_response['error'] = $exception->getMessage().' in '.$exception->getFile().' on '.$exception->getLine();

            return $data_for_response;
        }

        return $data_for_response;
    }

    public function get_page_cache_status() {
        $data_for_response = array(
            'success'=>false,
            'page_cache'=>true,
            'message'=>"Cannot get page cache status",
            'clear_cache_date' => '',
        );
        try {
            global $TwoSettings;
            $data_for_response['clear_cache_date'] = $TwoSettings->get_settings("two_clear_cache_date" , "" );
            $two_page_cache = $TwoSettings->get_settings("two_page_cache" , "");
            $data_for_response["success"] = true;
            if ($two_page_cache === "on"){
                $data_for_response["message"] = "Page cache enabled";
                $data_for_response["page_cache"] = true;
            } else {
                $data_for_response["message"] = "Page cache disabled";
                $data_for_response["page_cache"] = false;
            }
        } catch (Exception $exception) {
            $data_for_response['message'] = 'Error in getting page cache status';
            $data_for_response['error'] = $exception->getMessage().' in '.$exception->getFile().' on '.$exception->getLine();
            return $data_for_response;
        }

        return $data_for_response;
    }
    public function get_webp_status() {
        $data_for_response = array(
            'success' => false,
            'message' => "Cannot get webp status.",
        );
        try {
            global $TwoSettings;
            $webp_status = array();

            if (TENWEB_SO_HOSTED_ON_10WEB) {
                $webp_status["hosting"] = '10Web';
                $webp_status["webp_delivery"] = $TwoSettings->get_settings("two_enable_nginx_webp_delivery");
            } else {
                if (TENWEB_SO_HOSTED_ON_NGINX) {
                    $webp_status["hosting"] = 'NGINX';
                } else {
                    $webp_status["hosting"] = 'APACHE';
                    $webp_status["htaccess_writable"] = TENWEB_SO_HTACCESS_WRITABLE;
                    $webp_status["webp_delivery"] = $TwoSettings->get_settings("two_enable_htaccess_webp_delivery");
                }
                $two_webp_delivery_working = \TenWebOptimizer\OptimizerUtils::testWebPDelivery();
                $webp_status["webp_delivery_working"] = $two_webp_delivery_working;
                $webp_status["picture_webp_delivery"] = $TwoSettings->get_settings("two_enable_picture_webp_delivery");
            }

            if ($webp_status) {
                $data_for_response["success"] = true;
                $data_for_response["message"] = "WebP status collected successfully.";
                $data_for_response["data"] = $webp_status;
            }
        } catch (Exception $exception) {
            $data_for_response['message'] = 'Error in getting webp status';
            $data_for_response['error'] = $exception->getMessage().' in '.$exception->getFile().' on '.$exception->getLine();

            return $data_for_response;
        }

        return $data_for_response;
    }
}
