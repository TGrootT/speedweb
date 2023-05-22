<?php

namespace TenWebIO;

use TenWebIO\Exceptions\IOException;

class Compress
{
    private $queue_type;
    private $url;
    private $post_id;
    private $thumb_size;

    private $destination;
    private $absolute_path;

    private $compress_response = array();
    private $compress_settings;

    /**
     * @param        $queue_name
     * @param        $url
     * @param int    $post_id
     * @param string $thumb_size
     */
    public function __construct($queue_name, $url, $post_id = 0, $thumb_size = 'full')
    {
        $this->url = $url;
        $this->post_id = (int)$post_id;
        $this->thumb_size = $thumb_size;

        $data = Utils::getAttachmentData($url);
        $this->destination = $data['destination'];
        $this->absolute_path = $data['absolute_path'];

        $this->compress_settings = new Settings();

        $this->queue_type = Utils::getQueueTypeByName($queue_name);
        update_site_option(TENWEBIO_PREFIX . '_compress_images_counter_' . $this->queue_type, get_site_option(TENWEBIO_PREFIX . '_compress_images_counter_' . $this->queue_type, 0) + 1);
    }

    /**
     *
     * @return void
     * @throws IOException
     */
    public function compress()
    {
        $settings = $this->compress_settings->getSettings(false, 1, 1);
        Logs::setLog("compress:settings:log", $settings);

        $this->compressRequest();
        if (!$this->compress_response) {
            throw new IOException('Error during compress request');
        }
        $compress_response = $this->compress_response;
        if (!empty($compress_response['aws_url'])) {
            if (!empty($settings['keep_originals'])) {
                $this->keepOriginals();
            }
        }
        if (!$this->download((int)$settings['convert_webp'])) {
            throw new IOException('Error during download from s3');
        }
        if (!$this->checkHashes()) {
            throw new IOException('Not matching image hashes.');
        }
        $this->saveLastCompress();
    }

    /**
     * @return void
     * @throws IOException
     */
    public function compressRequest()
    {
        $action = strpos($this->queue_type, 'custom_') !== false ? Api::API_COMPRESS_CUSTOM_ACTION : Api::API_COMPRESS_ACTION;
        $page_id = Utils::getCustomQueueIdByType($this->queue_type);
        $api_instance = new Api($action);
        $this->compress_response = $api_instance->apiRequest('POST', array(
            "wp_options"        => array(
                "attachment_id" => $this->post_id,
                "thumb_size"    => $this->thumb_size,
                "page_id"       => $page_id
            ),
            "url"               => $this->url,
            "total"             => get_site_option(TENWEBIO_PREFIX . '_compress_images_count_' . $this->queue_type, 0),
            "counter"           => get_site_option(TENWEBIO_PREFIX . '_compress_images_counter_' . $this->queue_type, 0),
            "only_convert_webp" => get_site_option(TENWEBIO_PREFIX . '_custom_compress_only_convert_webp_' . $page_id),
        ));
        if ($api_instance->getResponseStatusCode() === 400) {
            throw new IOException('finish_queue');
        }
    }

    /**
     * @return void
     */
    public function keepOriginals()
    {
        $helper = new Backup();
        $helper->backupBeforeReplace($this->absolute_path, $this->destination);
        Logs::setLog("compress:backup:" . $this->url . ":log", 'finished');
    }

    /**
     *
     * @param int $with_webp
     *
     * @return bool
     * @throws IOException
     */
    public function download($with_webp = 1)
    {
        $compress_response = $this->compress_response;
        if ((int)$compress_response['final_size'] > (int)$compress_response['orig_size']) {
            return true;
        }
        $aws_webp = $aws = true;
        $s3 = new AwsService();

        if (!empty($compress_response['aws_webp_url']) && $with_webp) {
            $aws_webp = $s3->download($compress_response['aws_webp_url'], $this->destination . '/' . basename($this->url) . '.webp');
            Utils::storeWebPLog($this->url . '.webp');
        }

        if (!empty($compress_response['aws_url'])) {
            $aws = $s3->download($compress_response['aws_url'], $this->destination . '/' . basename($compress_response['aws_url']));
        } else {
            Logs::setLog("compress:download:" . $this->url . ":error", 'Empty aws_url in compress response.');

            return false;
        }

        return ($aws_webp && $aws);
    }

    /**
     *
     * @return void
     */
    public function saveLastCompress()
    {
        $compress_images_counter = get_site_option(TENWEBIO_PREFIX . '_compress_images_counter_' . $this->queue_type);
        $last_force = $compress_images_counter == 1;
        $last_compress = new LastCompress($last_force);
        $response = $this->compress_response;
        $orig_size = !empty($response['orig_size']) ? $response['orig_size'] : 0;
        $size = !empty($response['final_size']) ? $response['final_size'] : 0;
        $last_compress->update($size, $orig_size);

        Logs::setLog("compress:last:" . $this->url . ":log", array('orig_size' => $orig_size, 'final_size' => $size));
    }


    /**
     * @return bool
     */
    private function checkHashes()
    {
        $compress_response = $this->compress_response;
        $downloaded_file = $this->destination . '/' . basename($compress_response['aws_url']);
        $downloaded_file_hash = hash_file("sha256", $downloaded_file);
        if ($compress_response['image_hash'] !== $downloaded_file_hash) {
            unlink($downloaded_file);

            return false;
        }
        rename($downloaded_file, $this->destination . '/' . basename($this->url));

        return true;
    }

}
