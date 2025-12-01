<?php
namespace PublishPress\Permissions\Statuses\UI;

class StatusHelper
{
    public static function getUrlProperties(&$url, &$referer, &$redirect)
    {
        $url = apply_filters( 'presspermit_permits_base_url', 'admin.php' );

        if (PWP::empty_REQUEST() && PWP::SERVER_url('REQUEST_URI')) {
            $referer = '<input type="hidden" name="wp_http_referer" value="' . esc_attr(stripslashes(esc_url_raw(PWP::SERVER_url('REQUEST_URI')))) . '" />';
       
        } elseif ($wp_http_referer = PWP::REQUEST_url('wp_http_referer')) {
            $redirect = esc_url_raw(remove_query_arg(['wp_http_referer', 'updated', 'delete_count'], stripslashes(esc_url_raw($wp_http_referer))));
            $referer = '<input type="hidden" name="wp_http_referer" value="' . esc_attr($redirect) . '" />';
        } else {
            $redirect = "$url?page=presspermit-statuses";
            $referer = '';
        }
    }
}
