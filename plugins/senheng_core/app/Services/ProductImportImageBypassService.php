<?php

class ProductImportImageBypassService
{
    public static function register()
    {
        add_filter('http_response', [self::class, 'bypassMd5ForImages'], 10, 3);
    }

    public static function bypassMd5ForImages($response, $parsed_args, $url)
    {
        if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $url)) {
            if (!is_wp_error($response) && isset($response['headers']['content-md5'])) {
                unset($response['headers']['content-md5']);
            }
        }
        return $response;
    }
} 