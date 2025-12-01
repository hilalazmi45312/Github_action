<?php
$ep_requests = new EP_Requests;
$ep_functions = new Eventprime_Basic_Functions;
$ep_functions->event_submission_enqueue_script();
$ep_functions->event_submission_enqueue_style();
$args = $atts;
$themepath = $ep_requests->eventprime_get_ep_theme('frontend-submission-form-tpl');
include $themepath;