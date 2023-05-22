<?php
namespace TenWebOptimizer;

class OptimizerCriticalCss
{
    public $critical_enabled = false;
    public $critical_font_enabled = false;
    public $uncritical_load_type = null;
    public $critical_data = null;
    public $critical_css = null;

    public $uncritical_css = null;
    public $critical_bg = null;
    public $critical_fonts = null;

    public $status = false;
    public $use_uncritical = false;
    public $two_critical_pages = null;
    public $page_id = null;


    public function __construct($id = null)
    {
        global $TwoSettings;
        $critical_status = $TwoSettings->get_settings("two_critical_status");
        if ($critical_status === "true") {
            $this->critical_enabled = true;
        }

        if($TwoSettings->get_settings("two_critical_font_status") === "true"){
            $this->critical_font_enabled = true;
        }
        $this->two_critical_pages = OptimizerUtils::getCriticalPages();
        if ($id === null) {
            global $post;
            if (is_front_page()) {
                $id = '';
                if(!empty($this->two_critical_pages) && !empty($post)) {
                    if(!empty($this->two_critical_pages[$post->ID])) {
                        $id = $post->ID; // translated home page
                    } else if(isset($this->two_critical_pages['front_page'])) {
                        $id = OptimizerUtils::get_post_id($this->two_critical_pages['front_page']["url"]);
                    }
                }
            }
            if(!isset($id) || $id===0 || empty($id)){
                $id = OptimizerUtils::get_post_id();
            }
        }
        $this->page_id = $id;
        OptimizerUtils::two_critical_status($id);
        $this->getCriticalCssData($id);
    }

    public static function getCriticalCssApi($request_data)
    {
        global $TwoSettings;
        $newly_connected_website = isset($request_data['newly_connected_website']) ? $request_data['newly_connected_website'] : false;
        $two_flow_critical_start = get_option("two_flow_critical_start");

        if(!TENWEB_SO_HOSTED_ON_10WEB && !$newly_connected_website && $two_flow_critical_start !=="1" && !\Tenweb_Authorization\Login::get_instance()->check_logged_in()){
            return;
        }

        if(empty($request_data["page_id"])){
            return;
        }
        $critical_key = "two_critical_".$request_data["page_id"];
        $critical_key_transient = \TenWebWpTransients\OptimizerTransients::get($critical_key);

        if(!$newly_connected_website && $critical_key_transient === "1"){
            return;
        }
        \TenWebWpTransients\OptimizerTransients::set( $critical_key, "1", MINUTE_IN_SECONDS*15);
        try {
            $response_data = null;
            if (filter_var($request_data["url"], FILTER_VALIDATE_URL) === false) {
                $page_data = OptimizerUtils::get_permalink_name_by_id( $request_data["page_id"] );
                $request_data["url"] = $page_data[ 'url' ];
            }
            if(empty($request_data["url"])){
                return;
            }
            $check_redirect = OptimizerUtils::check_page_has_no_redirects($request_data["url"], true);
            if(!$check_redirect){
                return;
            }
            $url = wp_parse_url($request_data["url"], PHP_URL_QUERY) ? $request_data["url"] . '&' . $request_data["url_query"] : $request_data["url"] . '?' . $request_data["url_query"];
            $request_data["url"] = $url;

            $critical_flag = \TenWebWpTransients\OptimizerTransients::get("two_critical_flag");
            $critical_flag = (int)$critical_flag;
            if($critical_flag>0){
                $critical_flag++;
                \TenWebWpTransients\OptimizerTransients::set("two_critical_flag" , $critical_flag, 24 * HOUR_IN_SECONDS);
            }else{
                \TenWebWpTransients\OptimizerTransients::set("two_critical_flag" , 1, 24 * HOUR_IN_SECONDS);
            }
            $page_id = sanitize_text_field( $request_data["page_id"] );
            $critical_in_progress_key = "two_critical_in_progress_".$page_id;
            \TenWebWpTransients\OptimizerTransients::set($critical_in_progress_key, "1" , 30 * MINUTE_IN_SECONDS);

            if( !TENWEB_SO_HOSTED_ON_10WEB ){
                $flow_id = get_site_option(TENWEB_PREFIX . '_flow_id', false);
                $notification_id = get_site_option(TENWEB_PREFIX . '_notification_id',false);



                $request_data["notification_id"] = $notification_id;
                if($newly_connected_website && $request_data["page_id"] == "front_page"){
                    update_option( "two_flow_critical_start", "1");
                    $request_data["flow_id"] = $flow_id;
                }else if(isset($request_data["flow_id"])){
                    unset($request_data["flow_id"]);
                }
                $domain_id = get_site_option('tenweb_domain_id');
                $access_token = get_site_option(TENWEB_PREFIX . '_access_token');
                $critical_token = get_option("two_critical" . $page_id);
                if(!$critical_token || empty($critical_token)){
                    $critical_token = wp_generate_uuid4().bin2hex(random_bytes(12));
                }

                if($access_token && $domain_id){
                    $home_url = trailingslashit(home_url());
                    $callback_url = add_query_arg( array( 'rest_route'=>'/tenweb_so/v1/set_critical'), $home_url );
                    $request_data["callback_url"] = $callback_url;
                    $request_data["action"] = "two_set_critical";
                    $request_data["token"] = $critical_token;

                    $first_critical_generation_flag = get_site_option("first_critical_generation_flag");
                    if($first_critical_generation_flag !== "1" && $request_data["page_id"]==="front_page"){
                        update_site_option("first_critical_generation_flag","1");
                    }
                    if($first_critical_generation_flag === "1"){
                        unset($request_data["flow_id"], $request_data["notification_id"]);
                    }


                    $res = wp_remote_post( TENWEB_SO_CRITICAL_URL."/v1/critical/".$domain_id."/create", array(
                        'timeout'     => 5,
                        'redirection' => 5,
                        'httpversion' => '1.0',
                        'blocking'    => true,
                        'headers'     => array(
                            "accept" => "application/x.10webperformance.v1+json",
                            "authorization" => "Bearer ".$access_token,
                        ),
                        'body'        => array(
                            'critical_data'=>$request_data,
                        ),
                        'cookies'     => array()
                    ));
                    if ( wp_remote_retrieve_response_code( $res ) !== 200 ) {
                        $critical_key = "two_critical_".$page_id;
                        \TenWebWpTransients\OptimizerTransients::delete($critical_key);
                        \TenWebWpTransients\OptimizerTransients::delete($critical_in_progress_key);
                        if ( OptimizerUrl::isCriticalSavedInSettings( $page_id ) ) {
                            $two_critical_pages = $TwoSettings->get_settings("two_critical_pages");
                            unset($two_critical_pages[$page_id]);
                            unset($two_critical_pages[""]);
                            $TwoSettings->update_setting("two_critical_pages", $two_critical_pages);
                        }
                        else {
                            delete_post_meta( $page_id, 'two_critical_pages' );
                        }
                        /* unset optimize_inprogress */
                        \TenWebWpTransients\OptimizerTransients::delete('two_optimize_inprogress_' . $page_id );
                        /* Keeping all posts statuses which is in progress or optimized to manage notif popup view one time for each case */
                        $two_optimization_notif_status = get_option('two_optimization_notif_status');
                        unset($two_optimization_notif_status[$page_id]);
                        update_option('two_optimization_notif_status', $two_optimization_notif_status, 1);
                        if ($first_critical_generation_flag !== '1' && $page_id === 'front_page') {
                            OptimizerUtils::update_connection_flow_progress("running", "ccss_generation_queue_rejected");
                        }

                    }
                    OptimizerLogger::add_critical_css_log($request_data, $newly_connected_website,$flow_id, wp_remote_retrieve_response_code($res));
                }
                update_option("two_critical".$page_id, $critical_token, false);
            }
            else if (true === \TenwebServices::manager_ready()) {
                $response = \TenwebServices::do_request(TENWEB_API_URL . '/domains/critical-css', array(
                    "body"   => array(
                        'critical_data'=>$request_data
                    ),
                    "method" => "POST",
                    "blocking" => false
                ));

                OptimizerLogger::add_critical_css_log($request_data, $newly_connected_website, get_site_option(TENWEB_PREFIX . '_flow_id'), wp_remote_retrieve_response_code($response));

                if (!is_wp_error($response)) {
                    $response_data = array(
                        "status" => "success",
                    );
                }
            } else {
                $response_data = array(
                    "status" => "error",
                    "error"  => "Tenweb Manager not ready"
                );
            }

        } catch (\Exception $e) {
            $response_data = array(
                "status" => "error",
                "error"  => $e->getMessage()
            );

        }

        return $response_data;
    }


    public static function getDefaultSettings()
    {
        return array(
            'desktop_width'  => 1280,
            'desktop_height' => 800,
            'mobile_width'   => 360,
            'mobile_height'  => 640,
            'url_query'      => 'PageSpeed=off&two_nooptimize=1'
        );

    }

    public static function checkManagerIsActive()
    {
        if (!defined('TENWEB_INCLUDES_DIR')) {
            return false;
        }
        include_once TENWEB_INCLUDES_DIR . '/class-tenweb-services.php';

        if (!defined('TENWEB_API_URL')) {
            return false;
        }

        return true;
    }

    public static function setCritical($data)
    {
        global $TwoSettings;
        $tenweb_domain_id = get_option('tenweb_domain_id');
        $url = $data["page_url"];
        $page_id = $data["page_id"];
        $initiator = "auto";
        if(isset($data["initiator"]) && !empty($data["initiator"])){
            $initiator = $data["initiator"];
        }
        $url_query = $data["url_query"];
        $wait_until = "domcontentloaded";
        if(isset($data["wait_until"])){
            $wait_until =$data["wait_until"];
        }
        if (isset($data["url_query"])) {
            $url_query = $data["url_query"];
        }
        if (is_array($data["page_sizes"]) && isset($tenweb_domain_id)) {
            if(empty($data["page_sizes"])){
                $default_settings = $TwoSettings->get_settings("two_critical_sizes");
                if ( empty( $default_settings ) && isset($default_settings["two_critical_sizes"]) && !empty($default_settings["two_critical_sizes"]) ) {
                    $default_settings = $default_settings["two_critical_sizes"];
                }
                if(isset($default_settings["two_critical_sizes"]) && !empty($default_settings["two_critical_sizes"])){
                    $data["page_sizes"] = $default_settings;
                }
            }

            $request_data = array(
                'url'=>$url,
                'page_id' => $page_id,
                'initiator' => $initiator,
                'sizes'=>array_values($data["page_sizes"]),
                'url_query'=>$url_query,
                'wait_until'=>$wait_until,
                'tenweb_domain_id'=>$tenweb_domain_id,
                'newly_connected_website'=> isset($data['newly_connected_website']) ? $data['newly_connected_website'] : false
            );
            if ($request_data['newly_connected_website'] && get_site_option(TENWEB_PREFIX . '_flow_id')) {
                $request_data["initiator"] = "automatic_during_onboarding_flow";
                $request_data['flow_id'] = get_site_option(TENWEB_PREFIX . '_flow_id');
            }
            self::getCriticalCssApi($request_data);
        }
    }

    public function getCriticalCssData($id)
    {
        if (is_array($this->two_critical_pages) && isset($this->two_critical_pages[$id])) {
            $critical_page = $this->two_critical_pages[$id];
            if(isset($this->two_critical_pages[$id]["critical_css"]) && !empty($this->two_critical_pages[$id]["critical_css"])){
                $this->critical_css = $this->two_critical_pages[$id]["critical_css"];
                $file_dir = TWO_CACHE_DIR . "critical/" . $this->critical_css;
                if(!file_exists($file_dir)){
                    if(isset($critical_page["status"]) && $critical_page["status"]==="success"){
                        global $TwoSettings;
                        $this->two_critical_pages[$id]["status"] = "not_started";
                        $TwoSettings->update_setting("two_critical_pages", $this->two_critical_pages);
                    }
                    return;
                }
            }else{
                return;
            }

            if (isset($critical_page["load_type"])) {
                $this->uncritical_load_type = $critical_page["load_type"];
            }else{
                $this->uncritical_load_type = "async";
            }
            if (isset($critical_page["status"])) {
                $this->status = $critical_page["status"];
            }else{
                $this->status = "not_started";
            }

            if(isset($this->two_critical_pages[$id]["uncritical_css"]) && !empty($this->two_critical_pages[$id]["uncritical_css"])){
                $this->uncritical_css = $this->two_critical_pages[$id]["uncritical_css"];
            }
            if(isset($this->two_critical_pages[$id]["critical_bg"]) && !empty($this->two_critical_pages[$id]["critical_bg"])){
                $this->critical_bg = $this->two_critical_pages[$id]["critical_bg"];
            }
            if(isset($this->two_critical_pages[$id]["critical_fonts"]) && !empty($this->two_critical_pages[$id]["critical_fonts"])){
                if(file_exists(TWO_CACHE_DIR . "critical/" .$this->two_critical_pages[$id]["critical_fonts"])){
                    $this->critical_fonts = json_decode(file_get_contents(TWO_CACHE_DIR . "critical/" .$this->two_critical_pages[$id]["critical_fonts"]));
                }
            }
            if(isset($this->two_critical_pages[$id]["use_uncritical"]) && $this->two_critical_pages[$id]["use_uncritical"]=="true"){
                $this->use_uncritical = true;
            }
        }
    }

    /**
     * Generate Critical CSS for a single post.
     * @param $post_id
     */
    public static function generate_critical_css_by_id( $post_id , $rightAfterConnect = false, $initiator = '')
    {
        $tenweb_subscription_id = \TenWebWpTransients\OptimizerTransients::get(TENWEB_PREFIX . '_subscription_id');
        $is_free = (in_array((int)$tenweb_subscription_id, TENWEB_SO_FREE_SUBSCRIPTION_IDS)  && !TENWEB_SO_HOSTED_ON_10WEB);
        global $TwoSettings;
        if ($TwoSettings->get_settings('two_critical_status') === "true" || $is_free) {
            $post_data = OptimizerUtils::get_permalink_name_by_id( $post_id );
            $post_url = $post_data[ 'url' ];
            $post_title = $post_data[ 'title' ];

            $waitUntil = 'domcontentloaded';
            $data = array(
                'action' => 'two_critical',
                'two_critical_sizes' => $TwoSettings->get_settings("two_critical_sizes"),
                'data' => array(
                    'page_url' => $post_url,
                    'page_id' => $post_id,
                    'newly_connected_website' => $rightAfterConnect,
                    'page_sizes' => $TwoSettings->get_settings("two_critical_sizes"),
                    'wait_until' => $waitUntil,
                    'url_query' => $TwoSettings->get_settings("two_critical_url_args"),
                    'task' => 'generate',
                    'initiator' => $initiator,
                    'clear_cache' => true
                ),
            );
            if($rightAfterConnect && $post_id == "front_page"){
                $data["data"]["flow_id"] =  get_site_option(TENWEB_PREFIX . '_flow_id');
            }
            $two_critical_pages = array();
            $two_critical_pages[$post_id] = array(
                'title' => $post_title,
                'url' => $post_url,
                'id' => $post_id,
                'sizes' => $TwoSettings->get_settings("two_critical_sizes"),
                'load_type' => 'async',
                'wait_until' => $waitUntil,
                'status'=>'in_progress'
            );
            $data["two_critical_pages"] = $two_critical_pages;
            $response = self::generateCriticalCSS($data);
        }

    }

    public static function generateCriticalCSS($data)
    {
        global $TwoSettings;
        $return_data = array(
            "success" => false,
        );
        $two_critical_pages = OptimizerUtils::getCriticalPages();
        OptimizerUtils::init_defines();
        if (!defined('TWO_CACHE_DIR')) {
            define('TWO_CACHE_DIR', OptimizerCache::get_path());
        }
        if(isset($data["two_critical_pages"]) && is_array($data["two_critical_pages"])){
            foreach ($data["two_critical_pages"] as $key => $critical_page_data){
                if(isset($two_critical_pages[$key])){
                    if(isset($two_critical_pages[$key]["critical_css"])){
                        $data["two_critical_pages"][$key]["critical_css"] = $two_critical_pages[$key]["critical_css"];
                    }
                    if(isset($two_critical_pages[$key]["uncritical_css"])){
                        $data["two_critical_pages"][$key]["uncritical_css"] = $two_critical_pages[$key]["uncritical_css"];
                    }
                    if(isset($two_critical_pages[$key]["critical_fonts"])){
                        $data["two_critical_pages"][$key]["critical_fonts"] = $two_critical_pages[$key]["critical_fonts"];
                    }
                    if(isset($two_critical_pages[$key]["critical_bg"])){
                        $data["two_critical_pages"][$key]["critical_bg"] = $two_critical_pages[$key]["critical_bg"];
                    }
                }
            }
            // Restore front_page when regenerating criticals for other pages.
            foreach ( $two_critical_pages as $key => $critical_page_data ) {
                if ( !isset( $data[ "two_critical_pages" ][ $key ] ) && OptimizerUrl::isCriticalSavedInSettings( $key ) ) {
                    $data[ "two_critical_pages" ][ $key ] = $critical_page_data;
                }
            }
            $TwoSettings->update_setting("two_critical_pages" , $data["two_critical_pages"]);
        }
        if(isset($data["two_critical_sizes"])){
            $TwoSettings->update_setting("two_critical_sizes" , $data["two_critical_sizes"]);
        }
        if(isset($data["data"]) && is_array($data["data"]) && isset($data["data"]["page_id"]) && isset($data["data"]["page_url"]) && isset($data["data"]["page_sizes"]) ){
            $critical_in_progress_key = "two_critical_in_progress_".$data["data"]["page_id"];
            \TenWebWpTransients\OptimizerTransients::set($critical_in_progress_key, "1" , 30 * MINUTE_IN_SECONDS);
            OptimizerCriticalCss::setCritical($data["data"]);
            $return_data["success"] = true;
        }
        return $return_data;
    }


    public static function createCriticalCSS($file_path, $firstImportOfCss = false, $file_content = "" , $css_import_no_rest = false){
        OptimizerUtils::init_defines();
        if (!defined('TWO_CACHE_DIR')) {
            define('TWO_CACHE_DIR', OptimizerCache::get_path());
        }
        global $TwoSettings;
        if(!empty($file_content) || (file_exists($file_path) && is_readable($file_path))) {
            try {
                if(empty($file_content)){
                    $critical_data_json = file_get_contents($file_path);
                }else{
                    $critical_data_json = $file_content;
                }
                $two_critical_pages = OptimizerUtils::getCriticalPages();
                if (!empty($critical_data_json)) {
                    $critical_data = json_decode($critical_data_json, true);
                    if (isset($critical_data["page_data"]["page_id"])) {
                        if (isset($critical_data["subscription_id"]) && (int)$critical_data["subscription_id"] > 0) {
                            \TenWebWpTransients\OptimizerTransients::set(TENWEB_PREFIX . '_subscription_id', (int)$critical_data["subscription_id"], 12 * HOUR_IN_SECONDS);
                        }
                        $page_id = $critical_data["page_data"]["page_id"];
                        $critical_key = "two_critical_".$page_id;
                        \TenWebWpTransients\OptimizerTransients::delete($critical_key);
                        if (isset($two_critical_pages[$page_id])) {
                            $critical_page = $two_critical_pages[$page_id];
                            if (isset($critical_data["critical_css"]) && !empty($critical_data["critical_css"])) {
                                $cssMinifier = new OptimizerCSSMin();
                                $critical_css = $cssMinifier->run($critical_data["critical_css"], false);
                                $cache = new OptimizerCache($page_id, 'critical', "all", "critical");
                                $critical_page["critical_css"] = basename($cache->getname(true));
                                $cache->cache($critical_css, 'text/critical');
                            }
                            if (isset($critical_data["uncritical_css"]) && !empty($critical_data["uncritical_css"])) {
                                $cssMinifier = new OptimizerCSSMin();
                                $uncritical_css = $cssMinifier->run($critical_data["uncritical_css"], false);
                                $cache = new OptimizerCache($page_id, 'critical', "all" ,"uncritical");
                                $critical_page["uncritical_css"] = basename($cache->getname(true));
                                $uncritical_css = "/* 10Web Booster Uncritical CSS  */\n" . $critical_data["uncritical_css"];
                                $cache->cache($uncritical_css, 'text/critical');
                            }
                            if (isset($critical_data["critical_fonts"])) {
                                $critical_fonts = $critical_data["critical_fonts"];
                                $critical_fonts_json = json_encode($critical_fonts);
                                $cache = new OptimizerCache($page_id, 'font', "all", "critical_font");
                                $critical_page["critical_fonts"] = basename($cache->getname(true));
                                $cache->cache($critical_fonts_json, 'text/json');
                            }
                            if (isset($critical_data["critical_bg"])) {
                                $critical_bg = $critical_data["critical_bg"];
                                // Regenerate missing image sizes.
                                foreach ($critical_bg as $bg) {
                                    $id = OptimizerUtils::getImageIdByUrl($bg['bg_url']);
                                    if ($id) {
                                        $attachment = get_post($id);
                                        OptimizerUtils::wp_maybe_generate_attachment_metadata($attachment);
                                    }
                                }
                                $critical_bg_json = json_encode($critical_bg);

                                $cache = new OptimizerCache($page_id, 'font', "all", "critical_bg");
                                $critical_page["critical_bg"] = basename($cache->getname(true));
                                $cache->cache($critical_bg_json, 'text/json');
                            }
                            if (!empty($critical_page["critical_css"])) {
                                $date = time();
                                delete_option("two_critical_blocked");
                                $critical_page["status"] = "success";
                                $critical_page["critical_date"] = $date;
                                $two_critical_pages[$page_id] = $critical_page;
                                $TwoSettings->update_setting("two_critical_pages", $two_critical_pages);
                                $regenerate_data = \TenWebWpTransients\OptimizerTransients::get("two_regenerate_critical_data");
                                if (is_array($regenerate_data) && !empty($regenerate_data)) {
                                    OptimizerUtils::regenerate_critical();
                                } else {
                                    \TenWebOptimizer\OptimizerAdmin::clear_cache(false, true);
                                    if($page_id !== "front_page"){
                                        OptimizerUtils::update_post($page_id);
                                    }else{
                                        OptimizerUtils::update_post();
                                    }
                                }
                            } else {
                                update_option("two_critical_blocked", true);
                                $critical_page["status"] = "error";
                                $two_critical_pages[$page_id] = $critical_page;
                                $TwoSettings->update_setting("two_critical_pages", $two_critical_pages);
                                $regenerate_data = \TenWebWpTransients\OptimizerTransients::get("two_regenerate_critical_data");
                                if (is_array($regenerate_data) && !empty($regenerate_data)) {
                                    OptimizerUtils::regenerate_critical();
                                }
                            }
                        }
                        if(has_action("two_page_optimized")){
                            do_action('two_page_optimized', $page_id);
                        }
                        if($page_id === "front_page" && has_action("bwg_hompage_optimized")){
                            do_action('bwg_hompage_optimized');
                        }
                    }
                }
                if(empty($file_content) && file_exists($file_path)){
                    unlink($file_path);
                }
            } catch (\Exception $exception) {
                update_option('two_critical_data_import_exception_'.time(), $exception->getMessage().' on '.$exception->getLine().' in '.$exception->getFile(), false);
            }
        }

        if ($firstImportOfCss) {
            if($css_import_no_rest){
                OptimizerUtils::update_connection_flow_progress("running", "css_import_no_rest");
            }
            update_option("two_triggerPostOptimizationTasks", "1" ,false);
            //OptimizerUtils::triggerPostOptimizationTasks();
        }
    }


}