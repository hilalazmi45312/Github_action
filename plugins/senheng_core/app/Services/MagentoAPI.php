<?php

class MagentoAPI
{
    private $config;
    private $miniOrangeConfig;

    public function __construct()
    {
        $this->config = woo_authorization_salt();
        $this->miniOrangeConfig = mini_orange_authorization_salt();
    }

    public function checkPhone($phone, $type = 'CONTACT')
    {
        $body = [
            'type' => $type,
        ];
        switch ($type) {
            case 'ICNO':
                $body['icno'] = $phone;
                break;
            case 'EMAIL':
                $body['email'] = $phone;
                break;
            default:
                $body['contact'] = $phone;
                break;
        }

        $response = wp_remote_post(
            $this->config['api_url'] . '/magento_check',
            [
                'method'    => 'POST',
                'headers'   => [
                    'Customer-Key' => $this->config['customer_key'],
                    'Timestamp'    => $this->config['timestamp'],
                    'Authorization' => $this->config['hash'],
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept'       => 'application/x-www-form-urlencoded',
                ],
                'body'      => http_build_query($body),
                'timeout'   => 15,
            ]
        );

        if (is_wp_error($response)) {
            return [
                'flag' => 0,
                'message' => $response->get_error_message(),
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['Body'][0]['FLAG'])) {
            $flag = (int) $body['Body'][0]['FLAG'];
            $message = $body['Body'][0]['MESSAGE'] ?? '';
            $code = $body['Body'][0]['CODE'] ?? '';
            $status = $body['Body'][0]['STATUS'] ?? '';
            $dialogInfo = $body['Body'][0]['DIALOG_INFO'] ?? [];
            $isDialog = $body['Body'][0]['IS_DIALOG'] ?? false;

            return [
                'flag' => $flag,
                'message' => $message,
                'code' => $code,
                'status' => $status,
                'is_dialog' => $isDialog,
                'dialog_info' => $dialogInfo,
            ];
        } else {
            return [
                'flag' => 0,
                'message' => json_encode($body)
            ];
        }
    }

    public function requestOtp($phone, $type = 'CONTACT')
    {
        $body = [
            'phone' => $phone,
            'version' => '5.1.1',
        ];

        switch ($type) {
            case 'REGISTER':
                $type = 'REGISTER';
                break;
            case 'ICNO':
                $type = 'IC';
                break;
            case 'EMAIL':
                $type = 'EMAIL';
                break;
            default:
                $type = 'PHONE';
                break;
        }

        $body['type'] = $type;

        $response = wp_remote_post(
            $this->config['api_url'] . '/magento_otp_sms',
            [
                'method'    => 'POST',
                'headers'   => [
                    'Customer-Key' => $this->config['customer_key'],
                    'Timestamp'    => $this->config['timestamp'],
                    'Authorization' => $this->config['hash'],
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept'       => 'application/x-www-form-urlencoded',
                ],
                'body'      => http_build_query($body),
                'timeout'   => 30,
            ]
        );

        if (is_wp_error($response)) {
            return [
                'flag' => 0,
                'message' => $response->get_error_message(),
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['Body']['FLAG'])) {
            $flag = (int) $body['Body']['FLAG'];
            $message = $body['Body']['MESSAGE'] ?? '';
            $code = $body['Body']['CODE'] ?? '';
            $status = $body['Body']['STATUS'] ?? '';
            $dialogInfo = $body['Body']['DIALOG_INFO'] ?? [];
            $isDialog = $body['Body']['IS_DIALOG'] ?? false;
            $txId = $body['Body']['MESSAGE_RESULT']['txId'] ?? '';
            $maskedPhone = $body['Body']['PHONE'] ?? '';

            return [
                'flag' => $flag,
                'message' => $message,
                'code' => $code,
                'status' => $status,
                'is_dialog' => $isDialog,
                'dialog_info' => $dialogInfo,
                'tx_id' => $txId,
                'masked_phone' => $maskedPhone
            ];
        } else {
            return [
                'flag' => 0,
                'message' => json_encode($body)
            ];
        }
    }

    public function validateOtp($txId, $phone, $otp)
    {
        $response = wp_remote_post(
            $this->miniOrangeConfig['api_url'] . '/api/auth/validate',
            [
                'method'    => 'POST',
                'headers'   => [
                    'Customer-Key' => $this->miniOrangeConfig['customer_key'],
                    'Timestamp'    => $this->miniOrangeConfig['timestamp'],
                    'Authorization' => $this->miniOrangeConfig['hash'],
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'body'      => json_encode([
                    'txId' => $txId,
                    'customerKey' => $this->miniOrangeConfig['customer_key'],
                    'username' => $phone,
                    'authType' => 'SMS',
                    'token' => $otp,
                ]),
                'timeout'   => 30,
            ]
        );

        if (is_wp_error($response)) {
            return [
                'flag' => 0,
                'message' => $response->get_error_message(),
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        return $body;
    }

    public function registerUser($data)
    {
        $response = wp_remote_post(
            $this->config['api_url'] . '/magento_register',
            [
                'method'    => 'POST',
                'headers'   => [
                    'Customer-Key' => $this->config['customer_key'],
                    'Timestamp'    => $this->config['timestamp'],
                    'Authorization' => $this->config['hash'],
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept'       => 'application/x-www-form-urlencoded',
                ],
                'body'      => http_build_query([
                    'name' => $data['full_name'],
                    'email' => $data['email'],
                    'contact' => $data['phone'],
                    'password' => $data['password'],
                    'version' => '5.1.1',
                    'cardtype' => 'eBSC',
                    'icno' => $data['ic_number'],
                    'type' => '',
                ]),
                'timeout'   => 15,
            ]
        );

        //check if status code return like bad gateway or something
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code >= 400) {
            return [
                'flag' => 0,
                'message' => wp_remote_retrieve_body($response),
            ];
        }

        if (is_wp_error($response)) {
            return [
                'flag' => 0,
                'message' => $response->get_error_message(),
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $item = $body['Body'][0] ?? $body['Body'] ?? [];

        if (empty($item)) {
            return [
                'flag' => 0,
                'message' => 'Unexpected response format',
            ];
        }

        if ($item['FLAG'] === 2 || $item['STATUS'] === 'ERROR' || $item['STATUS'] === 'FAILED') {
            return [
                'flag' => $item['FLAG'],
                'message' => $item['MESSAGE'],
                'code' => $item['CODE'],
                'status' => $item['STATUS'],
                'is_dialog' => $item['IS_DIALOG'],
                'dialog_info' => $item['DIALOG_INFO'],
            ];
        }

        return [
            // Top-level response
            'code' => $body['code'],

            // From BODY object
            'cust_allp1no'     => implode(',', $item['BODY']['CUST_ALLP1NO']),
            'cust_all_cardtype' => implode(',', $item['BODY']['CUST_ALL_CARDTYPE']),
            'cust_cardtype'    => $item['BODY']['CUST_CARDTYPE'],
            'cust_contact'     => $item['BODY']['CUST_CONTACT'],
            'cust_contact_key' => $item['BODY']['CUST_CONTACT_KEY'],
            'cust_country'     => $item['BODY']['CUST_COUNTRY'],
            'cust_email'       => $item['BODY']['CUST_EMAIL'],
            'cust_icno'        => $item['BODY']['CUST_ICNO'],
            'cust_id'          => $item['BODY']['CUST_ID'],
            'cust_idmapping'   => $item['BODY']['CUST_IDMAPPING'],
            'cust_name'        => $item['BODY']['CUST_NAME'],
            'cust_p1no'        => $item['BODY']['CUST_P1NO'],

            // From Body root level
            'code_internal' => $item['CODE'],
            'flag'          => $item['FLAG'],
            'idmapping'     => $item['IDMAPPING'],
            'idsso'         => $item['IDSSO'],
            'is_pin'        => $item['IS_PIN'],
            'message'       => $item['MESSAGE'],
            'status'        => 'SUCCESS', // Assuming status is always 'SUCCESS' in this context
            'tnc_content'   => $item['TNC_CONTENT'],

            // From MESSAGE_INFO
            'access_token'  => $item['MESSAGE_INFO']['access_token'],
            'expires_in'    => $item['MESSAGE_INFO']['expires_in'],
            'refresh_token' => $item['MESSAGE_INFO']['refresh_token'],
            'token_type'    => $item['MESSAGE_INFO']['token_type'],
            'auth_status'   => $item['MESSAGE_INFO']['status'],
        ];
    }

    public function login($txId, $phone, $otp, $type)
    {
        switch ($type) {
            case 'ICNO':
                $type = 'IC';
                break;
            case 'EMAIL':
                $type = 'EMAIL';
                break;
            default:
                $type = 'PHONE';
                break;
        }

        $body = [
            'type' => $type,
            'email' => $phone,
            'password' => $otp,
        ];

        $response = wp_remote_post(
            $this->config['api_url'] . '/magento_login',
            [
                'method'    => 'POST',
                'headers'   => [
                    'Customer-Key' => $this->config['customer_key'],
                    'Timestamp'    => $this->config['timestamp'],
                    'Authorization' => $this->config['hash'],
                ],
                'body'      => $body,
                'timeout'   => 30,
            ]
        );

        //check if status code return like bad gateway or something
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code >= 400) {
            return [
                'flag' => 0,
                'message' => wp_remote_retrieve_body($response),
            ];
        }

        if (is_wp_error($response)) {
            return [
                'flag' => 0,
                'message' => $response->get_error_message(),
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $item = $body['Body'][0] ?? $body['Body'] ?? [];

        if (empty($item)) {
            return [
                'flag' => 0,
                'message' => 'Unexpected response format',
            ];
        }

        if ($item['FLAG'] === 2 || $item['STATUS'] === 'ERROR' || $item['STATUS'] === 'FAILED') {
            return [
                'flag' => $item['FLAG'],
                'message' => $item['MESSAGE'],
                'code' => $item['CODE'],
                'status' => $item['STATUS'],
                'is_dialog' => $item['IS_DIALOG'],
                'dialog_info' => $item['DIALOG_INFO'],
            ];
        }

        return [
            // Top-level response
            'code' => $body['code'],

            // From BODY object
            'cust_allp1no'     => implode(',', $item['BODY']['CUST_ALLP1NO']),
            'cust_all_cardtype' => implode(',', $item['BODY']['CUST_ALL_CARDTYPE']),
            'cust_cardtype'    => $item['BODY']['CUST_CARDTYPE'],
            'cust_contact'     => $item['BODY']['CUST_CONTACT'],
            'cust_contact_key' => $item['BODY']['CUST_CONTACT_KEY'],
            'cust_country'     => $item['BODY']['CUST_COUNTRY'],
            'cust_email'       => $item['BODY']['CUST_EMAIL'],
            'cust_icno'        => $item['BODY']['CUST_ICNO'],
            'cust_id'          => $item['BODY']['CUST_ID'],
            'cust_idmapping'   => $item['BODY']['CUST_IDMAPPING'],
            'cust_name'        => $item['BODY']['CUST_NAME'],
            'cust_p1no'        => $item['BODY']['CUST_P1NO'],

            // From Body root level
            'code_internal' => $item['CODE'],
            'flag'          => $item['FLAG'],
            'idmapping'     => isset($item['IDMAPPING']) ? $item['IDMAPPING'] : $item['BODY']['CUST_IDMAPPING'],
            'idsso'         => $item['IDSSO'],
            'is_pin'        => $item['IS_PIN'],
            'message'       => $item['MESSAGE'],
            'status'        => 'SUCCESS', // Assuming status is always 'SUCCESS' in this context
            'tnc_content'   => $item['TNC_CONTENT'],

            // From MESSAGE_INFO
            'access_token'  => $item['MESSAGE_INFO']['access_token'],
            'expires_in'    => $item['MESSAGE_INFO']['expires_in'],
            'refresh_token' => $item['MESSAGE_INFO']['refresh_token'],
            'token_type'    => $item['MESSAGE_INFO']['token_type'],
            'auth_status'   => $item['MESSAGE_INFO']['status'],
        ];
    }

    public function socialLogin($arr_data)
    {
        $body = [
            'email' => $arr_data['email'],
            'type' => 'social',
            'socialid' => $arr_data['uid'],
            'socialtoken' => $arr_data['access_token'],
            'name' => $arr_data['name'],
            'socialProviderId' => $arr_data['provider'],
        ];

        $response = wp_remote_post(
            $this->config['api_url'] . '/magento_login_social',
            [
                'method'    => 'POST',
                'headers'   => [
                    'Customer-Key' => $this->config['customer_key'],
                    'Timestamp'    => $this->config['timestamp'],
                    'Authorization' => $this->config['hash'],
                ],
                'body'      => $body,
                'timeout'   => 30,
            ]
        );

        //check if status code return like bad gateway or something
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code >= 400) {
            return [
                'flag' => 0,
                'message' => wp_remote_retrieve_body($response),
            ];
        }

        if (is_wp_error($response)) {
            return [
                'flag' => 0,
                'message' => $response->get_error_message(),
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $item = $body['Body'][0] ?? $body['Body'] ?? [];

        if (empty($item)) {
            return [
                'flag' => 0,
                'message' => 'Unexpected response format',
            ];
        }

        if ($item['FLAG'] === 2 || $item['STATUS'] === 'ERROR' || $item['STATUS'] === 'FAILED') {
            return [
                'flag' => $item['FLAG'],
                'message' => $item['MESSAGE'],
                'code' => $item['CODE'],
                'status' => $item['STATUS'],
                'is_dialog' => $item['IS_DIALOG'],
                'dialog_info' => $item['DIALOG_INFO'],
            ];
        }

        return [
            // Top-level response
            'code' => $body['code'],

            // From BODY object
            'cust_allp1no'     => implode(',', $item['BODY']['CUST_ALLP1NO']),
            'cust_all_cardtype' => implode(',', $item['BODY']['CUST_ALL_CARDTYPE']),
            'cust_cardtype'    => $item['BODY']['CUST_CARDTYPE'],
            'cust_contact'     => $item['BODY']['CUST_CONTACT'],
            'cust_contact_key' => $item['BODY']['CUST_CONTACT_KEY'],
            'cust_country'     => $item['BODY']['CUST_COUNTRY'],
            'cust_email'       => $item['BODY']['CUST_EMAIL'],
            'cust_icno'        => $item['BODY']['CUST_ICNO'],
            'cust_id'          => $item['BODY']['CUST_ID'],
            'cust_idmapping'   => $item['BODY']['CUST_IDMAPPING'],
            'cust_name'        => $item['BODY']['CUST_NAME'],
            'cust_p1no'        => $item['BODY']['CUST_P1NO'],

            // From Body root level
            'code_internal' => $item['CODE'],
            'flag'          => $item['FLAG'],
            'idmapping'     => isset($item['IDMAPPING']) ? $item['IDMAPPING'] : $item['BODY']['CUST_IDMAPPING'],
            'idsso'         => $item['IDSSO'],
            'is_pin'        => $item['IS_PIN'],
            'message'       => $item['MESSAGE'],
            'status'        => 'SUCCESS', // Assuming status is always 'SUCCESS' in this context
            'tnc_content'   => $item['TNC_CONTENT'],

            // From MESSAGE_INFO
            'access_token'  => $item['MESSAGE_INFO']['access_token'],
            'expires_in'    => $item['MESSAGE_INFO']['expires_in'],
            'refresh_token' => $item['MESSAGE_INFO']['refresh_token'],
            'token_type'    => $item['MESSAGE_INFO']['token_type'],
            'auth_status'   => $item['MESSAGE_INFO']['status'],
        ];
    }

    public function getAllCardInfo($idsso, $email)
    {
        $response = wp_remote_post(
            $this->config['api_url'] . '/magento/getallcardinfo',
            [
                'method'    => 'POST',
                'headers'   => [
                    'Customer-Key' => $this->config['customer_key'],
                    'Timestamp'    => $this->config['timestamp'],
                    'Authorization' => $this->config['hash'],
                ],
                'body'      => [
                    'mousername' => $idsso,
                    'email' => $email,
                ],
                'timeout'   => 30,
            ]
        );

        if (is_wp_error($response)) {
            return [
                'flag' => 0,
                'message' => $response->get_error_message(),
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['CARD_INFO'])) {
            return [
                'flag' => isset($body['FLAG']) ? (int)$body['FLAG'] : 0,
                'message' => $body['MESSAGE'] ?? '',
                'code' => $body['CODE'] ?? '',
                'status' => $body['STATUS'] ?? '',
                'contact_key' => $body['CONTACT_KEY'] ?? '',
                'card_info' => $body['CARD_INFO'],
            ];
        } else {
            return [
                'flag' => 0,
                'message' => json_encode($body)
            ];
        }
    }

    public function sso_get_user_info($token)
    {
        $response = wp_remote_get(
            $this->miniOrangeConfig['api_url'] . '/rest/oauth/getuserinfo',
            [
            'headers'   => [
                'Authorization' => 'Bearer ' . $token,
            ],
            'timeout'   => 30,
            ]
        );

        if (is_wp_error($response)) {
            return [
                'flag' => 0,
                'message' => $response->get_error_message(),
            ];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (
            isset($body['sub']) &&
            isset($body['firstname']) &&
            isset($body['phone']) &&
            isset($body['groups']) &&
            isset($body['email']) &&
            isset($body['username'])
        ) {
            return [
                'flag' => 1,
                'message' => 'Success',
                'user_info' => [
                    'sub' => $body['sub'],
                    'firstname' => $body['firstname'],
                    'phone' => $body['phone'],
                    'groups' => $body['groups'],
                    'email' => $body['email'],
                    'username' => $body['username'],
                ]
            ];
        } else {
            return [
                'flag' => 0,
                'message' => json_encode($body)
            ];
        }
    }
}
