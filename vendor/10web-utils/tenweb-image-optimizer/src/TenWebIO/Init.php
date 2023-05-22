<?php

namespace TenWebIO;

use TenWebIO\Views\LogsView;
use \TenWebQueue\Exceptions\QueueException;

class Init
{
    private static $instance = null;

    private function __construct()
    {
        $this->initCLI();
        $this->initRest();
        $this->initViews();
        $this->initBulkCompressCron();
        // $this->initMediaAutoOptimization();
    }

    /**
     * @return Init|null
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            return new self();
        }

        return self::$instance;
    }

    /**
     * @return void
     */
    public static function deactivate()
    {
        wp_clear_scheduled_hook('tenwebio_compress_hook');
    }

    /**
     * @return bool
     */
    public function autoOptimizeBulk()
    {
        try {
            $compress_service = new CompressService();
            $compress_service->compressBulk();
        } catch (\Exception $e) {
            Logs::setLog("compress:cron:error", $e->getMessage());
        }

        return true;
    }

    /**
     * @param $meta
     * @param $id
     *
     * @return mixed
     * @throws QueueException
     */
    public function autoOptimize($meta, $id)
    {
        $compress_settings = new Settings();
        $options = $compress_settings->getSettings(true, 1, 1);
        if (empty($options["enable_auto_optimization"])) {
            return $meta;
        }
        $config = new Config();
        if ($config->getAutoOptimizeWithRest()) {
            $route = add_query_arg(array('rest_route' => '/tenwebio/v2/compress-one', 'c' => $id), get_home_url() . "/");
            wp_remote_post($route, array('method' => 'POST', 'sslverify' => false, 'timeout' => 0.1, 'body' => array(
                "id"             => $id,
                'tenwebio_nonce' => wp_create_nonce('tenwebio_rest')
            )));
        } else {
            $compress_service = new CompressService();
            $compress_service->compressOne($id);
        }

        return $meta;
    }

    /**
     * @return void
     */
    private function initCLI()
    {
        if (class_exists('\WP_CLI')) {
            \WP_CLI::add_command('10web-tb-optimized-images', array('\TenWebIO\CLI', 'readyToOptimizeImages'));
            \WP_CLI::add_command('10web-store-optimized-images-log', array('\TenWebIO\CLI', 'storeOptimizedImagesLog'));
            \WP_CLI::add_command('10web-store-last-optimized-log', array('\TenWebIO\CLI', 'storeLastOptimizationLog'));
            \WP_CLI::add_command('10web-converted-images', array('\TenWebIO\CLI', 'convertedImages'));
        }
    }

    /**
     * @return void
     */
    private function initRest()
    {
        if (class_exists("\WP_REST_Controller")) {
            add_action('rest_api_init', function () {
                $rest = new Rest();
                $rest->registerRoutes();
            });
        }
    }

    /**
     * @return void
     */
    private function initBulkCompressCron()
    {
        $compress_settings = new Settings();
        $options = $compress_settings->getSettings(false, 1, 1);
        add_action('tenwebio_compress_hook', array($this, 'autoOptimizeBulk'));
        if (!wp_next_scheduled('tenwebio_compress_hook') && isset($options["enable_auto_optimization"]) && $options["enable_auto_optimization"]) {
            wp_schedule_event(time(), 'daily', 'tenwebio_compress_hook');
        }
    }

    /**
     * @return void
     */
    private function initMediaAutoOptimization()
    {
        add_filter('wp_generate_attachment_metadata', array($this, 'autoOptimize'), 15, 2);
    }

    /**
     * @return void
     */
    private function initViews()
    {
        new LogsView();
    }
}