<?php
namespace TenWebIO;

class Config
{
    private $debug_mode = 0;
    private $images_limit_for_restart = 20;
    private $auto_optimize_with_rest = 1;

    public function __construct()
    {
        $config = get_site_option(TENWEBIO_PREFIX . '_configs');
        if (!empty($config['debug_mode'])) {
            $this->debug_mode = $config['debug_mode'];
        } else {
            $this->debug_mode = 0;
        }

        if (!empty($config['auto_optimize_with_rest'])) {
            $this->auto_optimize_with_rest = $config['auto_optimize_with_rest'];
        } else {
            $this->auto_optimize_with_rest = 1;
        }

        if (!empty($config['images_limit_for_restart'])) {
            $this->images_limit_for_restart = (int)$config['images_limit_for_restart'];
        }
    }

    public function getDebugMode()
    {
        return $this->debug_mode;
    }

    public function getImagesLimitForRestart()
    {
        return $this->images_limit_for_restart;
    }

    public function getAutoOptimizeWithRest()
    {
        return $this->auto_optimize_with_rest;
    }

    public function save($data)
    {
        if (isset($data['debug_mode'])) {
            $this->debug_mode = (int)$data['debug_mode'];
        }
        if (isset($data['images_limit_for_restart'])) {
            $this->images_limit_for_restart = (int)$data['images_limit_for_restart'];
        }
        if (isset($data['auto_optimize_with_rest'])) {
            $this->auto_optimize_with_rest = (int)$data['auto_optimize_with_rest'];
        }
        update_site_option(TENWEBIO_PREFIX . '_configs', array(
            'debug_mode'               => $this->debug_mode,
            'images_limit_for_restart' => $this->images_limit_for_restart,
            'auto_optimize_with_rest'  => $this->auto_optimize_with_rest,
        ));
    }

}