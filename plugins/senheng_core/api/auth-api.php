<?php

function auth_login($data)
{
    $headers = array_change_key_case($data['_headers'], CASE_LOWER);
    $headerToken = $headers['mo-token'] ?? '';
    if (empty($headerToken)) {
        wp_send_json_error(['message' => 'Missing token'], 400);
    }
    $magento_api = new MagentoAPI();
    $user_data = $magento_api->sso_get_user_info($headerToken);

    if (!isset($user_data['flag']) || $user_data['flag'] !== 1) {
        wp_send_json_error(['message' => 'Invalid token'], 401);
    }

    $email = $user_data['user_info']['email'] ?? '';
    $user  = get_user_by('email', $email);

    if (!$user) {
        wp_send_json_error(['message' => 'User not found'], 404);
    }

    /** -----------------------------------------------------
     * 1️⃣ Login user inside WordPress
     * ----------------------------------------------------- */
    wp_set_current_user($user->ID);
    // wp_set_auth_cookie($user->ID, true); // sets real cookies in browser, safe to keep
    $idmapping = get_user_meta($user->ID, 'idmapping', true);

    /** -----------------------------------------------------
     * 2️⃣ Generate login cookie manually (JWT-style)
     * ----------------------------------------------------- */
    $expiration = time() + DAY_IN_SECONDS;
    $scheme     = 'logged_in';

    $cookie_name  = 'wordpress_logged_in_' . COOKIEHASH;
    $cookie_value = wp_generate_auth_cookie($user->ID, $expiration, $scheme);

    /** -----------------------------------------------------
     * 3️⃣ Return cookies as JSON to be used in next API calls
     * ----------------------------------------------------- */
    wp_send_json([
        "data" => [
            "tenantId"       => null,
            "tenantIdLong"   => null,
            "extra"          => null,
            "userId"         => $idmapping,
            "token"          => $headerToken,
            "expireTime"     => $expiration,
            "firstTimeLogin" => false,
            "jwtToken"       => $cookie_name . '=' . $cookie_value,
        ],
        "success" => true
    ]);
}

function auth_current_user($data)
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in'], 401);
    }
    $shData = senhengallInfo();

    wp_send_json([
        "data" => [
            "id" => $shData['idmapping'],
            "tenantId" => null,
            "username" => null,
            "nickname" => $shData['cust_name'],
            "avatar" => null,
            "mobile" => $shData['cust_contact'],
            "email" => $shData['cust_email'],
            "pwdExpireAt" => null,
            "passwordExist" => false,
            "enabled" => true,
            "locked" => false,
            "channel" => null,
            "channelType" => null,
            "source" => null,
            "sourceType" => null,
            "tag" => null,
            "extra" => null,
            "userDetail" => null,
            "createdAt" => null,
            "updatedAt" => null,
            "lastLoginAt" => null,
            "pk" => null,
            "senhengId" => null,
            "cardNo" => null,
            "firstTimeLogin" => false,
            "cardType" => $shData['cust_cardtype'],
            "idSso" => $shData['idsso'],
            "icNo" => $shData['cust_icno'],
            "totalSCoin" => null,
            "validFrom" => null,
            "validTo" => null,
            "p1No" => null,
            "deviceInfo" => [
                "advertiseId" => null,
                "appsflyerUid" => null,
                "detailInfoId" => null,
                "platform" => null,
            ],
            "deliveryEmail" => null,
            "recentPaymentMethodId" => null,
            "unbxdId" => null,
            "affiliations" => [],
            "scoinAccNo" => null,
            "scoinCardNo" => null,
            "scoinContact" => null,
            "scoinCustNo" => null,
            "scoinEmail" => null,
            "scoinIcNo" => null,
            "scoinId" => null,
            "scoinPoint" => null,
            "scoinValidFrom" => null,
            "scoinValidTo" => null
        ],
        "success" => true
    ]);
}
