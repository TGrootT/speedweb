<?php
namespace TenWebOptimizer;

use JSMin\JSMin;
use MatthiasMullie\Minify\JS;

if (!defined('ABSPATH')) {
    exit;
}

class OptimizerScripts extends OptimizerBase
{

    const TWO_DELAYED_JS_ATTRIBUTE ='data-twodelayedjs';

    const TWO_NO_DELAYED_JS_ATTRIBUTE = 'two-no-delay';

    const EXCLUDED_TYPES              = [
        'application/ld+json',
        'text/template',
        'text/x-template',
        'text/html',
        'application/json',
        'text/totalpoll-config',
    ];

    const EXCLUSION_DATA = [
        'exclude_rev' => [
            'scripts' => [
                'jquery.min.js',
                'jquery.js',
                'revslide',
                'setREVStartSize',
            ],
            'regex_to_find' => '/<\/rs-module-wrap>|<\/rs-slides>|<\/rs-slide>|<\/rs-layer>|<!-- END REVOLUTION SLIDER -->|id="rev_slider/m'
        ],
        'exclude_owl' => [
            'scripts' => [
                'jquery.min.js',
                'jquery.js',
                // https://github.com/OwlCarousel2/OwlCarousel2/tree/develop/dist
                'owl.carousel.min.js',
                'owl.carousel.js',
                'owl.carousel2.thumbs.min.js' // this is for slide-anything plugin
            ],
            'regex_to_find' => '/owl-carousel/m'
        ],
        'exclude_slick' => [
            'scripts' => [
                'jquery.min.js',
                'jquery.js',
                // https://github.com/OwlCarousel2/OwlCarousel2/tree/develop/dist
                'slick.min.js',
                'slick.js',
            ],
            'regex_to_find' => '/slick-slide|data-slick=|slick-/m'
        ],
        'exclude_slider_by_10web' => [
            'scripts' => [
                'jquery.min.js',
                'jquery.js',
                '/slider-wd/js',
                'wds_frontend-js-extra',
                'wds_frontend-js-before',
                'wds_params',
                'wds_object',
            ],
            'regex_to_find' => '/id="wds_container/m'
        ],
        'exclude_elementor_scripts' => [
            'scripts' => [
                'jquery.min.js',
                'jquery.js',
                'elementor-webpack-runtime-js',
                'elementor-frontend-modules-js',
                'elementor-frontend-js',
                'elementor-frontend-js-before',
                'swiper.min.js',
                'pro-elements-handlers',
                'pro-preloaded-elements-handlers',
                'elementor-pro-frontend',
                'ElementorProFrontendConfig',
                'elementor-pro-webpack-runtime',
                'elementor-frontend-modules',
            ],
            'regex_to_find' => '/class="elementor-widget-container"/m'
        ],
        'exclude_photo_gallery_by_10web' => [
            'scripts' => [
                'jquery.min.js',
                'jquery.js',
                '/photo-gallery/js',
                '/photo-gallery-google-photos/js',
                'bwg_frontend',
                'bwg_objectsL10n',
            ],
            'regex_to_find' => '/id="bwg_container/m'
        ],
    ];

    private $scripts = array();

    private $move = array(
        'first' => array(),
        'last' => array(),
    );

    private $dontmove = array(
        'document.write',
        'html5.js',
        'show_ads.js',
        'google_ad',
        'histats.com/js',
        'statcounter.com/counter/counter.js',
        'ws.amazon.com/widgets',
        'media.fastclick.net',
        '/ads/',
        'comment-form-quicktags/quicktags.php',
        'edToolbar',
        'intensedebate.com',
        'scripts.chitika.net/',
        '_gaq.push',
        'jotform.com/',
        'admin-bar.min.js',
        'GoogleAnalyticsObject',
        'plupload.full.min.js',
        'syntaxhighlighter',
        'adsbygoogle',
        'gist.github.com',
        '_stq',
        'nonce',
        'post_id',
        'data-noptimize',
        'logHuman',
        'two-no-delay',
        'two_exclude_inline',
        'data-two_exclude'
    );
    private $domove = array(
        'gaJsHost',
        'load_cmc',
        'jd.gallery.transitions.js',
        'swfobject.embedSWF(',
        'tiny_mce.js',
        'tinyMCEPreInit.go',
    );
    private $domovelast = array(
        'addthis.com',
        '/afsonline/show_afs_search.js',
        'disqus.js',
        'networkedblogs.com/getnetworkwidget',
        'infolinks.com/js/',
        'jd.gallery.js.php',
        'jd.gallery.transitions.js',
        'swfobject.embedSWF(',
        'linkwithin.com/widget.js',
        'tiny_mce.js',
        'tinyMCEPreInit.go',
    );

    private $dontmoveExtended = array(
        'document.write',
        'google_ad',
        'edToolbar',
        'gtag',
        '_gaq.push',
        '_gaLt',
        'GoogleAnalyticsObject',
        'syntaxhighlighter',
        'adsbygoogle',
        'ci_cap_',
        '_stq',
        'nonce',
        'post_id',
        'LogHuman',
        'idcomments_acct',
        'ch_client',
        'sc_online_t',
        '_stq',
        'bannersnack_embed',
        'vtn_player_type',
        'ven_video_key',
        'ANS_customer_id',
        'tdBlock',
        'tdLocalCache',
        'wpRestNonce',
        '"url":',
        'lazyLoadOptions',
        'adthrive',
        'loadCSS',
        'google_tag_params',
        'clicky_custom',
        'clicky_site_ids',
        'NSLPopupCenter',
        '_paq',
        'gtm',
        'dataLayer',
        'RecaptchaLoad',
        'WPCOM_sharing_counts',
        'jetpack_remote_comment',
        'subscribe-field',
        'contextly',
        '_mmunch',
        'gt_request_uri',
        'doGTranslate',
        'docTitle',
        'bs_ajax_paginate_',
        'bs_deferred_loading_',
        'theChampRedirectionUrl',
        'theChampFBCommentUrl',
        'theChampTwitterRedirect',
        'theChampRegRedirectionUrl',
        'ESSB_CACHE_URL',
        'oneall_social_login_providers_',
        'betterads_screen_width',
        'woocommerce_wishlist_add_to_wishlist_url',
        'arf_conditional_logic',
        'heateorSsHorSharingShortUrl',
        'TL_Const',
        'bimber_front_microshare',
        'setAttribute("id"',
        'setAttribute( "id"',
        'TribeEventsPro',
        'peepsotimedata',
        'wphc_data',
        'hc_rand_id',
        'RBL_ADD',
        'AfsAnalyticsObject',
        '_thriveCurrentPost',
        'esc_login_url',
        'fwduvpMainPlaylist',
        'Bibblio.initRelatedContent',
        'showUFC()',
        '#iphorm-',
        '#fancy-',
        'ult-carousel-',
        'theChampLJAuthUrl',
        'f._fbq',
        'Insticator',
        'w2dc_js_objects',
        'cherry_ajax',
        'ad_block_',
        'elementorFrontendConfig',
        'zeen_',
        'disqusIdentifier',
        'currentAjaxUrl',
        'geodir_event_call_calendar_',
        'atatags-',
        'hbspt.forms.create',
        'function(c,h,i,m,p)',
        'dataTable({',
        'rankMath = {',
        '_atrk_opts',
        'quicklinkOptions',
        'ct_checkjs_',
        'WP_Statistics_http',
        'penci_block_',
        'omapi_localized',
        'omapi_data',
        'OptinMonsterApp',
        'tminusnow',
        'nfForms',
        'galleries.gallery_',
        'wcj_evt.prodID',
        'advads_tracking_ads',
        'advadsGATracking.postContext',
        'woopack_config',
        'ulp_content_id',
        'wp-cumulus/tagcloud.swf?r=',
        'ctSetCookie(\'ct_checkjs\'',
        'woof_really_curr_tax',
        'uLogin.customInit',
        'i18n_no_matching_variations_text',
        'alsp_map_markers_attrs',
        'var inc_opt =',
        'iworks_upprev',
        'yith_wcevti_tickets',
        'window.metrilo.ensure_cbuid',
        'metrilo.event',
        'wordpress_page_root',
        'wcct_info',
        'Springbot.product_id',
        'pysWooProductData',
        'dfd-heading',
        'owl=$("#',
        'penci_megamenu',
        'fts_security',
        'algoliaAutocomplete',
        'avia_framework_globals',
        'tabs.easyResponsiveTabs',
        'searchlocationHeader',
        'yithautocomplete',
        'data-parallax-speed',
        'currency_data=',
        'cedexisData',
        'function reenableButton',
        '#wpnbio-show',
        'e.Newsletter2GoTrackingObject',
        'var categories_',
        '"+nRemaining+"',
        'cartsguru_cart_token',
        'after_share_easyoptin',
        'location_data.push',
        'thirstyFunctions.isThirstyLink',
        'styles: \' #custom-menu-',
        'function svc_center_',
        '#svc_carousel2_container_',
        'advads.move',
        'elementid',
        'advads_has_ads',
        'wpseo_map_init',
        'mdf_current_page_url',
        'tptn_tracker',
        'dpsp_pin_button_data',
        'searchwp_live_search_params',
        'wpp_params',
        'top.location,thispage',
        'selection+pagelink',
        'ic_window_resolution',
        'PHP.wp_p_id',
        'ShopifyBuy.UI.onReady(client)',
        'orig_request_uri',
        'gie.widgets.load',
        'Adman.Flash',
        'PHP.wp_p_id',
        'window.broadstreetKeywords',
        'var productId =',
        'var flatsomeVars',
        'wc_product_block_data',
        'static.mailerlite.com',
        'amzn_assoc',
        '_bs_getParameterByName',
        '_stq.push',
        'h._remove',
        'var FlowFlowOpts',
        'var WCPFData =',
        'var _beeketing',
        'var _statcounter',
        'var actions =',
        'var current_url',
        'var object_name',
        'var the_ajax_script',
        'var wc_cart_fragments_params',
        'var woocommerce_params',
        'var wpml_cookies',
        'wc_add_to_cart_params',
        'window.broadstreetKeywords',
        'window.wc_ga_pro.available_gateways',
        'xa.prototype',
        'HOUZEZ_ajaxcalls_vars',
        'w2dc_maps_objects',
        'w2dc_controller_args_array',
        'w2dc_map_markers_attrs',
        'YT.Player',
        'WPFC.data',
        'function current_video_',
        'var videodiv',
        'var slider_wppasrotate',
        'wppas_ga',
        'var blockClass',
        'tarteaucitron',
        'pw_brand_product_list',
        'tminusCountDown',
        'pysWooSelectContentData',
        'wpvq_ans89733',
        '_isp_version',
        'price_range_data',
        'window.FeedbackCompanyWidgets',
        'woocs_current_currency',
        'woo_variation_swatches_options',
        'woocommerce_price_slider_params',
        'scriptParams',
        'form-adv-pagination',
        'borlabsCookiePrioritize',
        'urls_wpwidgetpolylang',
        'quickViewNonce',
        'frontendscripts_params',
        'nj-facebook-messenger',
        'var fb_mess_position',
        'init_particles_row_background_script',
        'setREVStartSize',
        'fl-node',
        'PPAccordion',
        'soliloquy_',
        'wprevpublicjs_script_vars',
        'DTGS_NONCE_FRONTEND',
        'et_animation_data',
        'archives-dropdown',
        'loftloaderCache',
        'SmartSliderSimple',
        'var nectarLove',
        'var incOpt',
        'RocketBrowserCompatibilityChecker',
        'RocketPreloadLinksConfig',
        'placementVersionId',
        'var useEdit',
        'var DTGS_NONCE_FRONTEND',
        'n2jQuery',
        'et_core_api_spam_recaptcha',
        'cnArgs',
        '__CF$cv$params',
        'trustbox_settings',
        'aepro',
        'cdn.jst.ai',
        'w2dc_fields_in_categories',
        'aepc_pixel',
        'avadaWooCommerceVars',
        'var isb',
        'fcaPcPost',
        'csrf_token',
        'icwp_wpsf_vars_lpantibot',
        'wpvViewHead',
        'ed_school_plugin',
        'aps_comp_',
        'guaven_woos',
        '__lm_redirect_to',
        '__wpdm_view_count',
        'bookacti.booking_system',
        'nfFrontEnd',
        'view_quote_cart_link',
        '__eae_decode_emails',
        'divioverlays_ajaxurl',
        'var _EPYT_',
        '#ins-heading-',
        '#ins-button-',
        'tve_frontend_options',
        'lb24.src',
        'amazon_Login_accessToken',
        'porto_infinite_scroll',
        '.adace-loader-',
        'adace_load_',
        'tagGroupsAccordiontaggroupscloudaccordion',
        'tagGroupsTabstaggroupscloudtabs',
        'jrRelatedWidgets',
    );

    private $delayed_js = array();
    public  $cdn_url = '';
    private $aggregate = FALSE;
    private $trycatch = FALSE;
    private $alreadyminified = FALSE;
    private $forcehead = TRUE;
    private $include_inline = FALSE;
    private $jscode = '';
    private $hashes = [];
    private $url = '';
    private $restofcontent = '';
    private $md5hash = '';
    private $whitelist = '';
    private $jsremovables = array();
    private $inject_min_late = '';
    private $minify_excluded = TRUE;
    private $two_defer_plugin_js = array();

    /**
     * @var bool
     */
    private $delay_js_execution = false;

    /**
     * @var OptimizerCacheStructure
     */
    private $cacheStructure;

    /**
     * OptimizerStyles constructor.
     *
     * @param string $content
     * @param OptimizerCacheStructure $cacheStructure
     */

    private $TwoSettings;
    /**
     * @var bool
     */
    private $delay_all_js_execution;


    public $two_js_list =array();

    public $two_js_list_excluded =array();

    public function __construct($content, $cacheStructure)
    {
        global $TwoSettings;
        $this->TwoSettings = $TwoSettings;
        parent::__construct($content);
        $this->cacheStructure = $cacheStructure;
    }

    // Reads the page and collects script tags.
    public function read( $options ) {
        $two_defer_plugin_js = $this->TwoSettings->get_settings("two_defer_plugin_js");
        if(isset($two_defer_plugin_js) && is_array($two_defer_plugin_js)){
            $this->two_defer_plugin_js = $two_defer_plugin_js;
        }
        $exclJSArr = array();

        // Determine whether we're doing JS-files aggregation or not.
        if ( isset($options['aggregate']) ) {
            $this->aggregate = $options['aggregate'];
        }
        // Returning true for "dontaggregate" turns off aggregation.
        // include inline?
        if ( $options['include_inline'] && $this->aggregate ) {
            $this->include_inline = TRUE;
        }
        $this->inject_min_late = TRUE;
        // Determine whether excluded files should be minified if not yet so.
        /*if ( !$options['minify_excluded'] && $options['aggregate'] ) {
            $this->minify_excluded = FALSE;
        }*/
        $this->minify_excluded = $options['minify_excluded'];

        if($this->aggregate){
            $this->minify_excluded = FALSE;
        }

        // get extra exclusions settings or filter.
        $excludeJS = $options['js_exclude'];
        if ( '' !== $excludeJS ) {
            if ( is_array($excludeJS) ) {
                if ( ($removeKeys = array_keys($excludeJS, 'remove')) !== FALSE ) {
                    foreach ( $removeKeys as $removeKey ) {
                        unset($excludeJS[$removeKey]);
                        $this->jsremovables[] = $removeKey;
                    }
                }
                $exclJSArr = array_keys($excludeJS);
            }
            else {
                $exclJSArr = array_filter(array_map('trim', explode(',', $excludeJS)));
            }
            $this->dontmove = array_merge($exclJSArr, $this->dontmove);
        }

        if ( $options['use_extended_exception_list_js'] ) {
            $this->dontmove = array_unique(array_merge($this->dontmoveExtended, $this->dontmove));
        }

        // Should we add delay execution
        if ( $options['delay_js_execution'] ) {
            $this->delay_js_execution = true;
        }

        // Should we add delay execution
        if ( $options['delay_all_js_execution'] ) {
            $this->delay_all_js_execution = true;

            if ( $options['disabled_delay_all_js_pages'] ) {
                $two_disabled_delay_all_js_pages = array_filter(
                    array_map('trim', explode(',', $options['disabled_delay_all_js_pages']))
                );

                if (!empty($two_disabled_delay_all_js_pages)) {
                    //check excluded pages
                    foreach ($two_disabled_delay_all_js_pages as $optimizerDisabledPage) {
                        if (preg_match('~' . $optimizerDisabledPage . '~', $_SERVER['REQUEST_URI'])) {
                            $this->delay_all_js_execution = false;
                        }
                    }
                }
            }
        }

        // delay js execution.
        $delayJSExecutionList = $options['delayed_js_execution_list'];
        if ( '' !== $delayJSExecutionList ) {
            $delayJSExecution = array_filter(array_map('trim', explode(',', $delayJSExecutionList)));
            $this->delayed_js = array_merge($delayJSExecution, $this->delayed_js);
        }


        // Should we add try-catch?
        if ( $options['trycatch'] ) {
            $this->trycatch = TRUE;
        }
        // force js in head?
        if ( $options['forcehead'] ) {
            $this->forcehead = TRUE;
        }
        else {
            $this->forcehead = FALSE;
        }
        // get cdn url.
        $this->cdn_url = $options['cdn_url'];
        // noptimize me.
        $this->content = $this->hide_noptimize($this->content);
        // Save IE hacks.
        $this->content = $this->hide_iehacks($this->content);
        // comments.
        $this->content = $this->hide_comments($this->content);
        // Get script files.

        if ( preg_match_all('#<script.*</script>#Usmi', $this->content, $matches) ) {


            if ($this->delay_all_js_execution) {
                $exclude_delay_js = $this->get_exclusion_list($options);

                foreach ( $matches[0] as $tag ) {
                    $delay_uid = uniqid('two_', false);
                    if (false !== strpos($tag, OptimizerScripts::TWO_NO_DELAYED_JS_ATTRIBUTE)) {
                        continue;
                    }

                    $excluded_script = false;
                    foreach ($exclude_delay_js as $type) {
                        if (false !== strpos($tag, $type)) {
                            $excluded_script = true;
                            break;
                        }
                    }

                    if($excluded_script && $this->TwoSettings->get_settings("two_load_excluded_js_via_worker") != 'on'){
                        continue;
                    }

                    preg_match('#<script[^>]*id=("|\')([^>]*)("|\')#Usmi', $tag, $source);
                    $script_id = "";
                    if(isset($source[2])){
                        $script_id = $source[2];
                    }
                    if (preg_match('#<script[^>]*src=("|\')([^>]*)("|\')#Usmi', $tag, $source)) {
                        if (isset($source[2])) {
                            $dealy_script_data = array(
                                'inline' => false,
                                'url' => $source[2],
                                'id' => $script_id,
                                'uid' => $delay_uid,
                            );

                            if($excluded_script){
                                $dealy_script_data["excluded_from_delay"] = true;
                                $this->two_js_list_excluded[] = $dealy_script_data;
                            }else{
                                $dealy_script_data["excluded_from_delay"] = false;
                                $this->two_js_list[] = $dealy_script_data;
                            }
                        }
                        $new_tag = str_replace(array("src", "<script") , array("data-two_delay_src", "<script data-two_delay_id=\"".$delay_uid."\""), $tag);


                    }else{
                        preg_match('#<script.*>(.*)</script>#Usmi', $tag, $code);
                        if (isset($code[1])) {
                            $inline_code = base64_encode($code[1]);
                            $dealy_script_data = array(
                                'inline' => true,
                                'code' => $inline_code,
                                'id' => $script_id,
                                'uid' => $delay_uid,
                            );

                            if($excluded_script){
                                $dealy_script_data["excluded_from_delay"] = true;
                                $this->two_js_list_excluded[] = $dealy_script_data;
                            }else{
                                $dealy_script_data["excluded_from_delay"] = false;
                                $this->two_js_list[] = $dealy_script_data;
                            }
                        }
                        $new_tag = str_replace(array("<script", $code[1]) , array("<script data-two_delay_src='inline' data-two_delay_id=\"".$delay_uid."\"", ""), $tag);
                    }


                    //$this->content = str_replace($tag , $new_tag, $this->content);
                    $pos = strpos($this->content, $tag);
                    if ($pos !== false) {
                        $this->content = substr_replace($this->content, $new_tag, $pos, strlen($tag));
                    }
                    $this->cacheStructure->addToTagsToReplace($tag, $new_tag);
                }

                return true;
            }

            foreach ( $matches[0] as $tag ) {
                // only consider script aggregation for types whitelisted in should_aggregate-function or if it is for delayed
                $should_aggregate = $this->should_aggregate($tag);
                if ( ! ($this->delay_js_execution && $this->isfordelay($tag, $this->delayed_js)) && !$should_aggregate ) {
                    $tag = '';
                    continue;
                }
                if ( preg_match('#<script[^>]*src=("|\')([^>]*)("|\')#Usmi', $tag, $source) ) {
                    // non-inline script.
                    if ( $this->isremovable($tag, $this->jsremovables) ) {
                        $this->content = str_replace($tag, '', $this->content);
                        $this->cacheStructure->addToTagsToReplace($tag, '');
                        continue;
                    }
                    $origTag = NULL;
                    $url = current(explode('?', $source[2], 2));
                    if ( $this->delay_js_execution && $this->isfordelay($url, $this->delayed_js) ) {
                        //$newTag = '<script type="text/javascript" '.self::TWO_DELAYED_JS_ATTRIBUTE.'="'.$url.'"></script>';
                        $newTag = str_replace("src=" , self::TWO_DELAYED_JS_ATTRIBUTE."=", $tag);
                        $this->content = str_replace($tag, $newTag, $this->content);
                        $this->cacheStructure->addToTagsToReplace($tag, $newTag);

                        continue;
                    }
                    $path = $this->getpath($url);
                    if ( FALSE !== $path && preg_match('#\.js$#', $path) && $this->ismergeable($tag) ) {
                        // ok to optimize, add to array.
                        $this->scripts[md5($path)] = $path;
                    }
                    else {
                        $origTag = $tag;
                        $newTag = $tag;
                        // non-mergeable script (excluded or dynamic or external).
                        if ( is_array($exclJSArr) ) {
                            if (OptimizerUtils::is_pagespeed_js_defer_enabled()) {
                                // should we add flags to disable async loading?
                                if(is_array($this->two_defer_plugin_js) && !empty($this->two_defer_plugin_js)){
                                    $this->dontmove = array_merge($this->dontmove, $this->two_defer_plugin_js);
                                    $exclJSArr = array_merge($exclJSArr, $this->two_defer_plugin_js);
                                }
                                foreach ( $exclJSArr as $exclTag  ) {
                                    if ( FALSE !== strpos($origTag, $exclTag) ) {
                                        $newTag = str_replace('<script ', '<script data-pagespeed-no-defer ', $newTag);
                                    }
                                }
                            } else {
                                //todo check this, there has to be a bug
                                // should we add flags to enable async loading?
                                if ( is_array($excludeJS) ) {
                                    foreach ($excludeJS as $exclTag => $exclFlags) {
                                        if (false !== strpos($origTag, $exclTag) && in_array($exclFlags, array('async', 'defer'))) {
                                            $newTag = str_replace('<script ', '<script ' . $exclFlags . ' ', $newTag);
                                        }
                                    }
                                }
                            }

                        }
                        // Should we minify the non-aggregated script?
                        // -> if aggregate is on and exclude minify is on
                        // -> if aggregate is off and the file is not in dontmove.
                        if ( $path && $this->minify_excluded ) {
                            $consider_minified_array = FALSE;
                            if ( (FALSE === $this->aggregate && str_replace($this->dontmove, '', $path) === $path) || (TRUE === $this->aggregate && (FALSE === $consider_minified_array || str_replace($consider_minified_array, '', $path) === $path)) ) {
                                $minified_url = $this->minify_single($path);
                                // replace orig URL with minified URL from cache if so.
                                if ( !empty($minified_url) ) {
                                    $newTag = str_replace($url, $minified_url, $newTag);
                                }
                            }
                        }
                        if ( $this->ismovable($newTag) ) {
                            // can be moved, flags and all.
                            if ( $this->movetolast($newTag) ) {
                                $this->move['last'][] = $newTag;
                            }
                            else {
                                $this->move['first'][] = $newTag;
                            }
                        }
                        else {
                            // cannot be moved, so if flag was added re-inject altered tag immediately.
                            if ( $origTag !== $newTag ) {
                                $this->content = str_replace($origTag, $newTag, $this->content);
                                $this->cacheStructure->addToTagsToReplace($origTag, $newTag);

                                $origTag = '';
                            }
                            // and forget about the $tag (not to be touched any more).
                            $tag = '';
                        }
                    }
                }
                else {
                    //this tag is for delay
                    if ( $this->delay_js_execution && $this->isfordelay($tag, $this->delayed_js) ) {
                        $type = OptimizerUtils::get_javascipt_type($tag);
                        preg_match('#<script.*>(.*)</script>#Usmi', $tag, $code);
                        if (isset($code[1])) {
                            $newTag = '<script '.self::TWO_DELAYED_JS_ATTRIBUTE.'="data:'.$type.';base64,'.base64_encode($code[1]).'"></script>';
                            $this->content = str_replace($tag, $newTag, $this->content);
                            $this->cacheStructure->addToTagsToReplace($tag, $newTag);

                            continue;
                        }

                    }
                    // Inline script.
                    if ( $this->isremovable($tag, $this->jsremovables) ) {
                        $this->content = str_replace($tag, '', $this->content);
                        $this->cacheStructure->addToTagsToReplace($tag, '');

                        continue;
                    }
                    // unhide comments, as javascript may be wrapped in comment-tags for old times' sake.
                    $tag = $this->restore_comments($tag);
                    if ( $this->include_inline && $this->ismergeable($tag)) {
                        preg_match('#<script.*>(.*)</script>#Usmi', $tag, $code);
                        $code = preg_replace('#.*<!\[CDATA\[(?:\s*\*/)?(.*)(?://|/\*)\s*?\]\]>.*#sm', '$1', $code[1]);
                        $code = preg_replace('/(?:^\\s*<!--\\s*|\\s*(?:\\/\\/)?\\s*-->\\s*$)/', '', $code);
                        $this->scripts[md5($code)] = 'INLINE;' . $code;
                    }
                    else {
                        // Can we move this?
                        $twoptimize_js_moveable = "";
                        //todo refactor this
                        if ( $this->ismovable($tag) || '' !== $twoptimize_js_moveable ) {
                            if ( $this->movetolast($tag) || 'last' === $twoptimize_js_moveable ) {
                                $this->move['last'][] = $tag;
                            }
                            else {
                                $this->move['first'][] = $tag;
                            }
                        }
                        else {
                            // We shouldn't touch this.
                            $tag = '';
                        }
                    }
                    // Re-hide comments to be able to do the removal based on tag from $this->content.
                    $tag = $this->hide_comments($tag);
                }
                //Remove the original script tag.
                $this->content = str_replace($tag, '', $this->content);
                $this->cacheStructure->addToTagsToReplace($tag, '');

            }
            return TRUE;
        }

        // No script files, great ;-)
        return FALSE;
    }

    /**
     * Determines wheter a certain `<script>` $tag should be aggregated or not.
     * We consider these as "aggregation-safe" currently:
     * - script tags without a `type` attribute
     * - script tags with these `type` attribute values: `text/javascript`, `text/ecmascript`, `application/javascript`,
     * - it is not delayed script
     * and `application/ecmascript`
     * Everything else should return false.
     *
     * @link https://developer.mozilla.org/en/docs/Web/HTML/Element/script#attr-type
     *
     * @param string $tag
     *
     * @return bool
     */
    public function should_aggregate( $tag ) {
        // We're only interested in the type attribute of the <script> tag itself, not any possible
        // inline code that might just contain the 'type=' string...
        if (false !== strpos($tag, OptimizerScripts::TWO_NO_DELAYED_JS_ATTRIBUTE)) {
            return false;
        }
        $tag_parts = array();
        preg_match('#<(script[^>]*)>#i', $tag, $tag_parts);
        $tag_without_contents = NULL;
        if ( !empty($tag_parts[1]) ) {
            $tag_without_contents = $tag_parts[1];
        }
        $has_type = (strpos($tag_without_contents, 'type') !== FALSE);
        $type_valid = FALSE;
        if ( $has_type ) {
            $type_valid = (bool) preg_match('/type\s*=\s*[\'"]?(?:text|application)\/(?:javascript|ecmascript)[\'"]?/i', $tag_without_contents);
        }
        $should_aggregate = FALSE;
        if ( !$has_type || $type_valid ) {
            $should_aggregate = TRUE;
        }

        return $should_aggregate;
    }

    //Joins and optimizes JS
    public function optimize() {
        $two_change_minify = $this->TwoSettings->get_settings("two_change_minify");
        if (!empty($this->scripts)) {
            foreach ( $this->scripts as $hash => $script ) {
                if ( preg_match('#^INLINE;#', $script) ) {
                    // Inline script
                    $script = preg_replace('#^INLINE;#', '', $script);
                    $script = rtrim($script, ";\n\t\r") . ';';
                    // Add try-catch?
                    if ( $this->trycatch ) {
                        $script = 'try{' . $script . '}catch(e){}';
                    }
                    $tmpscript = $this->js_snippetcacher($script, "", $hash);
                    if ( !empty($tmpscript) ) {
                        $script = $tmpscript;
                        $this->alreadyminified = TRUE;
                    }
                    $this->jscode .= "\n" . $script;
                    $this->hashes[] = $hash;
                }
                else {
                    // External script
                    if ( FALSE !== $script && file_exists($script) && is_readable($script) ) {
                        $scriptsrc = file_get_contents($script);
                        $scriptsrc = preg_replace('/\x{EF}\x{BB}\x{BF}/', '', $scriptsrc);
                        $scriptsrc = rtrim($scriptsrc, ";\n\t\r") . ';';
                        // Add try-catch?
                        if ( $this->trycatch ) {
                            $scriptsrc = 'try{' . $scriptsrc . '}catch(e){}';
                        }
                        $tmpscriptsrc = $this->js_snippetcacher($scriptsrc, "", $hash);

                        if ( !empty($tmpscriptsrc) ) {
                            $scriptsrc = $tmpscriptsrc;
                            $this->alreadyminified = TRUE;
                        }
                        else {
                            if ( $this->can_inject_late($script) ) {
                                $scriptsrc = self::build_injectlater_marker($script, md5($scriptsrc));
                            }
                        }
                        $this->jscode .= "\n" . $scriptsrc;
                        $this->hashes[] = $hash;
                    }/*else{
                    //Couldn't read JS. Maybe getpath isn't working?
                }*/
                }
            }
        }

        // Check for already-minified code
        $this->md5hash = md5($this->jscode);
        if ( TRUE !== $this->alreadyminified ) {
            if($two_change_minify ==FALSE || $two_change_minify=="JSMin"){
                $tmp_jscode = trim(JSMin::minify($this->jscode));
            }else{
                $minifier = new JS($this->jscode);
                $tmp_jscode = $minifier->minify();
            }
            if ( !empty($tmp_jscode) ) {
                $this->jscode = $tmp_jscode;
                unset($tmp_jscode);
            }
            $this->jscode = $this->inject_minified($this->jscode);

            return TRUE;
        }

        return TRUE;
    }

    // Caches the JS in uncompressed, deflated and gzipped form.
    public function cache() {
        $cache = new OptimizerCache(null, 'js');
        if (!empty($this->jscode)) {
            // Cache our code
            $cache->cache($this->jscode, 'text/javascript');
        }
        $this->url = TWO_CACHE_URL . $cache->getname();
        $this->url = $this->url_replace_cdn($this->url);
    }

    // Returns the content
    public function getcontent() {
        // Restore the full content
        if ( !empty($this->restofcontent) ) {
            $this->content .= $this->restofcontent;
            $this->restofcontent = '';
        }
        // Add the scripts taking forcehead/ deferred (default) into account
        if ( $this->forcehead ) {
            $replaceTag = array( '</head>', 'before' );
            $defer = '';
        }
        else {
            $replaceTag = array( '</body>', 'before' );
            $defer = 'defer ';
        }


        $bodyreplacementpayload = '<script type="text/javascript" ' . $defer . 'src="' . $this->url . '"></script>';
        $bodyreplacement = implode('', $this->move['first']);
        $bodyreplacement .= $bodyreplacementpayload;
        $bodyreplacement .= implode('', $this->move['last']);

        if ( strlen($this->jscode) > 0 ) {
            $this->content = OptimizerUtils::inject_in_html($this->content,$bodyreplacement, $replaceTag);
            $this->cacheStructure->addToTagsToAdd($bodyreplacement, $replaceTag);
        }
        // Restore comments.
        $this->content = $this->restore_comments($this->content);
        // Restore IE hacks.
        $this->content = $this->restore_iehacks($this->content);
        // Restore noptimize.
        $this->content = $this->restore_noptimize($this->content);

        if (!empty($this->two_js_list) && $this->delay_all_js_execution ) {
            $this->two_js_list[] = array(
                'code' => base64_encode('
               
                if (window.two_page_loaded) {
                    console.log("dispatching events");' .
                    'document.dispatchEvent(new Event("DOMContentLoaded"));' .
                    'window.dispatchEvent(new Event("load"));' .
                    'two_loading_events(two_event);}
                
                '),
                "inline" => true,
                "uid" =>  "two_dispatchEvent_script",
            );
        }
        $two_dispatchEvent_script= "<script data-two_delay_id=\"two_dispatchEvent_script\"></script>";
        //$this->content = OptimizerUtils::inject_in_html($this->content, $two_dispatchEvent_script, array( '</body>', 'after' ));
        $this->content = str_replace("</body>", $two_dispatchEvent_script."</body>", $this->content);
        $this->cacheStructure->addToTagsToAdd($two_dispatchEvent_script, $replaceTag);
        // Return the modified HTML.
        return $this->content;
    }

    // Checks against the white- and blacklists
    private function ismergeable( $tag ) {
        if ( !$this->aggregate ) {
            return FALSE;
        }
        if ( !empty($this->whitelist) ) {
            foreach ( $this->whitelist as $match ) {
                if ( FALSE !== strpos($tag, $match) ) {
                    return TRUE;
                }
            }

            // no match with whitelist
            return FALSE;
        }
        else {
            foreach ( $this->domove as $match ) {
                if ( FALSE !== strpos($tag, $match) ) {
                    // Matched something
                    return FALSE;
                }
            }
            if ( $this->movetolast($tag) ) {
                return FALSE;
            }
            foreach ( $this->dontmove as $match ) {
                if ( FALSE !== strpos($tag, $match) ) {
                    // Matched something
                    return FALSE;
                }
            }

            // If we're here it's safe to merge
            return TRUE;
        }
    }

    // Checks agains the blacklist
    private function ismovable( $tag ) {
        if ( TRUE !== $this->include_inline ) {
            return FALSE;
        }
        foreach ( $this->domove as $match ) {
            if ( FALSE !== strpos($tag, $match) ) {
                // Matched something
                return TRUE;
            }
        }
        if ( $this->movetolast($tag) ) {
            return TRUE;
        }
        foreach ( $this->dontmove as $match ) {
            if ( FALSE !== strpos($tag, $match) ) {
                // Matched something
                return FALSE;
            }
        }

        // If we're here it's safe to move
        return TRUE;
    }

    private function movetolast( $tag ) {
        foreach ( $this->domovelast as $match ) {
            if ( FALSE !== strpos($tag, $match) ) {
                // Matched, return true
                return TRUE;
            }
        }
        // Should be in 'first'
        return FALSE;
    }

    /**
     * Determines wheter a <script> $tag can be excluded from minification (as already minified) based on:
     * - inject_min_late being active
     * - filename ending in `min.js`
     * - filename matching `js/jquery/jquery.js` (wordpress core jquery, is minified)
     * - filename matching one passed in the consider minified filter
     *
     * @param string $jsPath
     *
     * @return bool
     */
    private function can_inject_late( $jsPath ) {
        $consider_minified_array = FALSE;
        if ( TRUE !== $this->inject_min_late ) {
            // late-inject turned off
            return FALSE;
        }
        else {
            if ( (FALSE === strpos($jsPath, 'min.js')) && (FALSE === strpos($jsPath, 'wp-includes/js/jquery/jquery.js')) && (str_replace($consider_minified_array, '', $jsPath) === $jsPath) ) {
                // file not minified based on filename & filter
                return FALSE;
            }
            else {
                // phew, all is safe, we can late-inject
                return TRUE;
            }
        }
    }

    /**
     * Minifies a single local js file and returns its (cached) url.
     *
     * @param string $filepath   Filepath.
     * @param bool   $cache_miss Optional. Force a cache miss. Default false.
     *
     * @return bool|string Url pointing to the minified js file or false.
     */
    public function minify_single( $filepath, $cache_miss = FALSE ) {
        $two_change_minify = $this->TwoSettings->get_settings("two_change_minify");
        $contents = $this->prepare_minify_single($filepath);
        if ( empty($contents) ) {
            return FALSE;
        }
        // Check cache.
        $name_prefix = "minified_".str_replace(".js", "", basename($filepath));
        $cache = new OptimizerCache(null, 'js', "all", $name_prefix);
        if($two_change_minify ==FALSE || $two_change_minify=="JSMin"){
            $contents = trim(JSMin::minify($contents));
        }else{
            $minifier = new JS($contents);
            $contents = $minifier->minify();
        }
        // Store in cache.
        $cache->cache($contents, 'text/javascript');
        $url = $this->build_minify_single_url($cache);

        return $url;
    }

    public function js_snippetcacher( $jsin, $jsfilename, $hash )
    {
        $two_change_minify = $this->TwoSettings->get_settings("two_change_minify");
        if($two_change_minify ==FALSE || $two_change_minify=="JSMin"){
            $tmp_jscode = trim( JSMin::minify( $jsin ) );
        }else{
            $minifier = new JS($jsin);
            $tmp_jscode = $minifier->minify();
        }
        if ( ! empty( $tmp_jscode ) ) {
            $scriptsrc = $tmp_jscode;
            unset( $tmp_jscode );
        } else {
            $scriptsrc = $jsin;
        }
        $last_char = substr( $scriptsrc, -1, 1 );
        if ( ';' !== $last_char && '}' !== $last_char ) {
            $scriptsrc .= ';';
        }
        return $scriptsrc;
    }

    private function get_exclusion_list($options){
        $exclude_delay_js = $options["exclude_delay_js"];
        $exclude_delay_js = array_merge(self::EXCLUDED_TYPES, $exclude_delay_js);

        foreach(self::EXCLUSION_DATA as $opt_name => $ex_data) {
            if(!$options[$opt_name]) {
                continue;
            }

            preg_match($ex_data['regex_to_find'], $this->content, $matches);
            if(empty($matches)) {
                continue;
            }

            $exclude_delay_js = array_merge($ex_data['scripts'], $exclude_delay_js);
        }

        return $exclude_delay_js;
    }
}
