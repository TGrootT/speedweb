<?php
namespace TenWebIO;

class Utils
{
    /**
     * @return array
     */
    public static function getAttachmentData($url)
    {
        $no_schema_url = str_replace(array('https://', 'http://', 'www.'), '', $url);
        $no_schema_site_url = str_replace(array('https://', 'http://', 'www.'), '', site_url());

        $absolute_path = rtrim(self::strReplaceFirstOccurrence($no_schema_site_url, ABSPATH, $no_schema_url), '/');
        $absolute_url = rtrim(self::strReplaceFirstOccurrence(ABSPATH, site_url(), $url), '/');

        $base_name = rtrim(pathinfo($url, PATHINFO_BASENAME), '/');
        $destination = rtrim(pathinfo($absolute_path, PATHINFO_DIRNAME), '/');
        $extension = pathinfo($url, PATHINFO_EXTENSION);

        return [
            'base_name'     => $base_name,
            'destination'   => $destination,
            'extension'     => $extension,
            'absolute_url'  => $absolute_url,
            'absolute_path' => $absolute_path,
        ];
    }

    /**
     * @param $dir
     *
     * @return array
     */
    public static function getFilesFromDir($dir)
    {
        $result = array();
        foreach (scandir($dir) as $value) {
            if (!in_array($value, array(".", "..", ".original"))) {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $value)) {
                    $result = array_merge($result, self::getFilesFromDir($dir . DIRECTORY_SEPARATOR . $value));
                } else {
                    $result[] = $dir . DIRECTORY_SEPARATOR . $value;
                }
            }
        }

        return $result;
    }

    public static function removeDir($dir)
    {
        if (is_dir($dir)) {
            foreach (scandir($dir) as $value) {
                if (!in_array($value, array(".", "..", ".original"))) {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $value))
                        rrmdir($dir . DIRECTORY_SEPARATOR . $value);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $value);
                }
            }
            rmdir($dir);
        }
    }

    /**
     * @param      $type
     * @param bool $delete_all
     *
     * @return void
     */
    public static function deleteQueueTransients($type, $delete_all = true)
    {
        $page_id = Utils::getCustomQueueIdByType($type);
        delete_site_option(TENWEBIO_PREFIX . '_compress_images_count_' . $type);
        delete_site_option(TENWEBIO_PREFIX . '_compress_images_counter_' . $type);
        if ($delete_all) {
            delete_site_option(TENWEBIO_PREFIX . '_custom_compress_only_convert_webp_' . $page_id);
        }
    }

    /**
     * @return void
     */
    public static function clearSpeedCache()
    {
        if (class_exists('\OptimizerAdmin')) {
            \OptimizerAdmin::clear_cache(false, true);
            wp_remote_get(get_site_url(), array('method' => 'GET', 'sslverify' => false, 'timeout' => 0.1));
        }
    }

    /**
     * @param $type
     *
     * @return void
     */
    public static function finishQueue($type)
    {
        $queue_dir = Utils::getQueueDir($type);
        if (is_dir($queue_dir)) {
            Utils::removeDir($queue_dir);
        }
        Utils::deleteQueueTransients($type);
        Settings::purgeCompressSettings();
        Utils::clearSpeedCache();
    }

    /**
     * @param $queue_name
     *
     * @return array|string|string[]
     */
    public static function getQueueTypeByName($queue_name)
    {
        return str_replace(CompressService::QUEUE_NAME . '_', '', $queue_name);
    }

    /**
     * @param $queue_type
     *
     * @return int
     */
    public static function getCustomQueueIdByType($queue_type)
    {
        return str_replace('custom_', '', $queue_type);
    }

    /**
     * @param $queue_type
     *
     * @return string
     */
    public static function getQueueDir($queue_type)
    {
        $upload_dir = wp_get_upload_dir();
        $queue_dir = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $queue_type;
        if (!is_dir($queue_dir)) {
            mkdir($queue_dir);
        }

        return $queue_dir;
    }

    /**
     * @param $url
     *
     * @return void
     */
    public static function storeWebPLog($url)
    {
        $data = get_option(TENWEBIO_PREFIX . '_webp_converted', array());
        $attachment_data = self::getAttachmentData($url);
        $data[] = $attachment_data['absolute_path'];
        update_option(TENWEBIO_PREFIX . '_webp_converted', $data);
    }

    /**
     * @return int
     */
    public static function deleteWebPImages()
    {
        $data = get_option(TENWEBIO_PREFIX . '_webp_converted', array());
        $deleted_count = 0;
        if (!empty($data)) {
            foreach ($data as $image) {
                $extension = pathinfo($image, PATHINFO_EXTENSION);
                if (file_exists($image) && strtolower($extension) === 'webp') {
                    unlink($image);
                    $deleted_count++;
                }
            }
        }
        delete_option(TENWEBIO_PREFIX . '_webp_converted');

        return $deleted_count;
    }

    /**
     * @param $search
     * @param $replacement
     * @param $src
     *
     * @return array|mixed|string|string[]
     */
    public static function strReplaceFirstOccurrence($search, $replacement, $src)
    {
        return (false !== ($pos = strpos($src, $search))) ? substr_replace($src, $replacement, $pos, strlen($search)) : $src;
    }

}
