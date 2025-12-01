<?php
defined( 'ABSPATH' ) || exit;
$ep_requests = new EP_Requests;
$themepath = $ep_requests->eventprime_get_ep_theme('gdpr-badge-tpl');
include $themepath;
