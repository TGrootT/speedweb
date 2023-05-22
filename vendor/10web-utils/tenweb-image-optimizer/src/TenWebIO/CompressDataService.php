<?php

namespace TenWebIO;

class CompressDataService
{
    private $compress_settings;

    public function __construct()
    {
        $this->compress_settings = new Settings();
    }

    /**
     * @param bool  $force
     * @param bool  $force_stat
     * @param array $first_in_queue
     *
     * @return Attachments
     */
    public function getImagesReadyForOptimize($force = false, $force_stat = false, $first_in_queue = array())
    {
        $stat = $this->compress_settings->getStat($force_stat, 0, 1);
        $settings = $this->compress_settings->getSettings(false, 1, 1);

        $exclude_ids = array();
        $exclude_thumb_ids = array();
        $exclude_other = array();
        if (!$force) {
            if (!empty($stat['full_ids'])) {
                $exclude_ids = $stat['full_ids'];
            }
            if (!empty($stat['thumb_ids'])) {
                $exclude_thumb_ids = $stat['thumb_ids'];
            }
            if (!empty($stat['other'])) {
                $exclude_other = $stat['other'];
            }
        }
        $other_directories = !empty($settings['other_directories']) ? $settings['other_directories'] : array();

        $attachments = new Attachments();
        $attachments->setExcludedIds($exclude_ids);
        $attachments->setExcludedThumbIds($exclude_thumb_ids);
        $attachments->setOtherDirectories($other_directories);
        $attachments->setExcludedOtherPaths($exclude_other);
        $attachments->setFirstInQueue($first_in_queue);

        return $attachments;
    }

    /**
     * @param $post_id
     * @param $force_stat
     *
     * @return Attachments
     */
    public function getImageReadyForOptimize($post_id, $force_stat = false)
    {
        $stat = $this->compress_settings->getStat($force_stat, 1, 1);
        $exclude_ids = !empty($stat['full_ids']) ? $stat['full_ids'] : array();
        $exclude_thumb_ids = !empty($stat['thumb_ids']) ? $stat['thumb_ids'] : array();

        $attachments = new Attachments();
        $attachments->setExcludedIds($exclude_ids);
        $attachments->setExcludedThumbIds($exclude_thumb_ids);
        $attachments->setFilteredIds(array($post_id));

        return $attachments;
    }

    /**
     * @return array|array[]
     */
    public function getCompressResults()
    {
        if (class_exists('\TenWebWpTransients\OptimizerTransients')) {
            $data = \TenWebWpTransients\OptimizerTransients::get('two_images_count');
        }
        if (empty($data)) {
            $not_compressed = $this->getNotCompressedNumbers();
            $compressed = $this->getCompressedNumbers();

            $data = $not_compressed + $compressed;
            if (class_exists('\TenWebWpTransients\OptimizerTransients')) {
                \TenWebWpTransients\OptimizerTransients::set('two_images_count', $data, 86400);
            }
        }

        return $data;
    }

    /**
     * @param bool $force_stat
     *
     * @return array
     */
    public function getNotCompressedNumbers($force_stat = false)
    {
        $not_optimized = $this->getImagesReadyForOptimize(false, $force_stat)->getDataSeparate();
        $last_optimized = new LastCompress();
        $size = $last_optimized->getImageOrigSize() - $last_optimized->getImageSize();
        $percent = $last_optimized->getImageOrigSize() ? ($size / $last_optimized->getImageOrigSize()) * 100 : 0;

        return array(
            'not_optimized'  => array(
                'full'       => count($not_optimized['attachments_full']),
                'thumbs'     => count($not_optimized['attachments_meta']),
                'other'      => count($not_optimized['attachments_other']),
                'total_size' => $not_optimized['total_size']
            ),
            'last_optimized' => array(
                'size'    => $size,
                'percent' => $percent
            )
        );
    }

    /**
     * @param $skip_local_data
     *
     * @return array
     */
    public function getCompressedNumbers($skip_local_data = 1)
    {
        $data = array();
        $api_instance = new Api(Api::API_GET_STAT);

        $response_data = $api_instance->apiRequest('GET', array(), array(
            'skip_local_data' => $skip_local_data,
        ));
        if (is_array($response_data)) {
            $data = $response_data;
        }

        return $data;
    }
}