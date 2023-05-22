<?php

namespace TenWebIO;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\S3\S3UriParser;


class AwsService
{
    private $compress_settings;
    private $credentials;

    public function __construct()
    {
        $this->compress_settings = new Settings();
        $this->credentials = $this->compress_settings->getAwsCredentials(false, 1, 1);
    }

    /**
     * @param $aws_url
     * @param $destination
     * @param $retry
     *
     * @return bool
     */
    public function download($aws_url, $destination, $retry = 1)
    {
        if ($retry > 2) {
            return false;
        }
        $credentials = $this->credentials;
        try {
            $s3_url_parser = new S3UriParser();
            $parsed_url = $s3_url_parser->parse($aws_url);
            $s3client = new S3Client(array(
                'version'     => 'latest',
                'region'      => $parsed_url['region'],
                'credentials' => [
                    'key'    => $credentials['AccessKeyId'],
                    'secret' => $credentials['SecretAccessKey'],
                    'token'  => $credentials['SessionToken']
                ],
            ));

            if (!empty($parsed_url['key'])) {
                $s3client->getObject(array(
                    'Bucket' => $parsed_url['bucket'],
                    'Key'    => $parsed_url['key'],
                    'SaveAs' => $destination,
                ));
                Logs::setLog("s3:download:" . $aws_url . ":log", ' Saved as:' . $destination);

                return true;
            }
        } catch (S3Exception $e) {
            if ($e->getAwsErrorCode() == 'InvalidAccessKeyId' || $e->getAwsErrorCode() == 'ExpiredToken') {
                $this->credentials = $this->compress_settings->getAwsCredentials(true, 1, 1);
                $this->download($aws_url, $destination, $retry + 1);
                Logs::setLog("s3:download:" . $aws_url . ":log", 'Download from s3 retried.');
            }
            Logs::setLog("s3:download:" . $aws_url . ":s3exception", array('message' => $e->getMessage(), 'error_code' => $e->getAwsErrorCode()));
        } catch (\Exception $e) {
            Logs::setLog("s3:download:" . $aws_url . ":exception", $e->getMessage());
        }

        return false;
    }
}
