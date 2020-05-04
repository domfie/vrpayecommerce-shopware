<?php

class VersionTracker
{
    private static $versionTrackerUrl = 'http://api.dbserver.payreto.eu/v1/tracker';

    private static function getVersionTrackerUrl()
    {
        return self::$versionTrackerUrl;
    }

    /**
    * provide response data from version tracker API
    *
    * @param array $data
    * @param string $url
    * @return array
    */
    private static function getResponseData($data, $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            return false;
        }
        curl_close($ch);
        return json_decode($response, true);
    }

    /**
    * provide params that required by version tracker API
    *
    * @param array $versionData
    * @return array
    */
    private static function getVersionTrackerParameter($versionData)
    {
        $versionData['hash'] =
            md5($versionData['shop_version'].
            $versionData['plugin_version'].
            $versionData['client']);

        return http_build_query(array_filter($versionData), '', '&');
    }

    /**
    * send params to version tracker API
    *
    * @param array $versionData
    * @return array
    */
    public static function sendVersionTracker($versionData)
    {
        $postData = self::getVersionTrackerParameter($versionData);
        $url = self::getVersionTrackerUrl();
        return self::getResponseData($postData, $url);
    }
}
