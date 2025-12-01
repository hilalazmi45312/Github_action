<?php

class SrcConfigApi
{
    public static function getSrcApiInfo(): array
    {
        return [
            'SrcLoginEndpoint'         => 'https://example.com/api/login',
            'SrcDomain'                => $_SERVER["HTTP_HOST"] ?? '',
            'SrcQtyEndpoint'           => 'https://example.com/api/cart/qty',
            'SrcPriceEndpoint'         => 'https://example.com/api/cart/price',
            'ScrcQtyEndpoint'          => 'https://example.com/api/scrc/cart/qty',
            'ScrcPriceEndpoint'        => 'https://example.com/api/scrc/cart/price',
            'SrcAccountInfoEndpoint'   => 'https://example.com/api/account/info',
            'SrcMyAddressEndpoint'     => 'https://example.com/api/address/list',
            'SrcDeleteAddressEndpoint' => 'https://example.com/api/address/%d',
            'SrcUpdateAddressEndpoint' => 'https://example.com/api/address/update',
            'SrcAddAddressEndpoint'    => 'https://example.com/api/address/add',
            'SrcGetSrcAddressEndpoint' => 'https://example.com/api/address/src/%s',
            'SrcWishListGetEndpoint'   => 'https://example.com/api/wishlist?srcUserId={srcUserId}&pageSize={pageSize}&pageNo={pageNo}',
            'SrcWishListDeleteEndpoint' => 'https://example.com/api/wishlist/{itemId}?srcUserId={srcUserId}',
        ];
    }

    public static function getDomain(): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . '/';
    }
}

// -----------------------------
// SIMPLE LOGGER HELPERS (OPTIONAL)
// -----------------------------
function log_info(string $file, string $message, $context = null): void
{
    custom_log("[INFO][$file] $message " . ($context ? json_encode($context) : ''));
}

function log_error(string $file, string $message, $context = null): void
{
    custom_log("[ERROR][$file] $message " . ($context ? json_encode($context) : ''));
}

// -----------------------------
// HTTP HELPER USING cURL
// -----------------------------
function callApi(string $method, string $url, array $headers = [], ?string $body = null): string
{
    $ch = curl_init();

    $headerLines = [];
    foreach ($headers as $k => $v) {
        if ($v === '') continue;
        $headerLines[] = $k . ': ' . $v;
    }

    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headerLines,
        CURLOPT_FOLLOWLOCATION => true,
    ]);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    $response = curl_exec($ch);
    $err      = curl_error($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        throw new Exception("HTTP $method to $url failed: $err");
    }

    // Go code treats HTTP 4xx/5xx as "err" too, but also still parses body.
    // Here we just return the body and let each function inspect JSON like in Go.
    // If you want, you can handle $code here.

    return $response !== false ? $response : '';
}

function callGetApi(string $url, array $headers = []): string
{
    return callApi('GET', $url, $headers, null);
}

function callPostApi(string $url, array $headers = [], ?string $body = null): string
{
    return callApi('POST', $url, $headers, $body);
}

function callPutApi(string $url, array $headers = [], ?string $body = null): string
{
    return callApi('PUT', $url, $headers, $body);
}

function callDeleteApi(string $url, array $headers = []): string
{
    return callApi('DELETE', $url, $headers, null);
}

// -----------------------------
// HEADER BUILDER (SetSRCHeaders)
// -----------------------------
function SetSRCHeaders(bool $includeCookie, bool $includeAccessToken, array $info, string $contentType, string $cookies): array
{
    $headers = [];
    $headers['Accept']     = '*/*';
    $headers['Host']       = SrcConfigApi::getSrcApiInfo()['SrcDomain'];
    $headers['Connection'] = 'keep-alive';

    if ($contentType !== '') {
        $headers['Content-Type'] = $contentType;
    }

    if ($includeCookie && $cookies !== '') {
        $headers['Cookie'] = $cookies;
    }

    if ($includeAccessToken) {
        $accessToken = (string)($info['AccessToken'] ?? '');
        if ($accessToken !== '') {
            $headers['mo-token'] = $accessToken;
        }
    }
    dd($headers);
    return $headers;
}

// -----------------------------
// GetSRCCookies
// -----------------------------
function GetSRCCookies(array $info): string
{
    $filename = 'src_helper';
    $scrApiLoginUrl = SrcConfigApi::getSrcApiInfo()['SrcLoginEndpoint'];

    // Go code creates an empty multipart body and uses a buggy contentType literal.
    $payload = ''; // empty body
    $contentType = ''; // literally what the Go code does
    $cookies = '';

    try {
        $response = callPostApi(
            $scrApiLoginUrl,
            SetSRCHeaders(false, true, $info, $contentType, $cookies),
            $payload
        );
    } catch (Exception $e) {
        log_error($filename, 'Failed to call src api for login: ' . $e->getMessage(), $info);
        return '';
    }

    $respMap = json_decode($response, true);
    if (!is_array($respMap)) {
        return '';
    }

    if (empty($respMap['success'])) {
        log_error($filename, 'Failed to call src api for login (success=false)', ['resp' => $respMap, 'info' => $info]);
        return '';
    }

    $jwtToken = $respMap['data']['jwtToken'] ?? '';
    if ($jwtToken === '') {
        return '';
    }

    return 'draco_local=' . $jwtToken;
}

// -----------------------------
// GetSRCCookies2
// -----------------------------
function GetSRCCookies2(array $info, string $filename): array
{
    $scrApiLoginUrl = SrcConfigApi::getSrcApiInfo()['SrcLoginEndpoint'];

    $payload = '';
    $contentType = '';
    $cookies = '';

    try {
        $headers = SetSRCHeaders(false, true, $info, $contentType, $cookies);
        $response = callPostApi($scrApiLoginUrl, $headers, $payload);

        log_info($filename, "SRC API Login Request/Response", [
            'URL'      => $scrApiLoginUrl,
            'Headers'  => $headers,
            'Body'     => $payload,
            'Info'     => $info,
            'Response' => $response,
        ]);
    } catch (Exception $e) {
        log_error($filename, 'Failed to call src api for login: ' . $e->getMessage(), $info);
        return ['', ''];
    }

    $respMap = json_decode($response, true);
    if (!is_array($respMap)) {
        return ['', ''];
    }

    if (empty($respMap['success'])) {
        log_error($filename, 'Failed to call src api for login (success=false)', ['resp' => $respMap, 'info' => $info]);
        return ['', ''];
    }

    $jwtToken = $respMap['data']['jwtToken'] ?? '';
    $userId   = $respMap['data']['userId']   ?? '';

    return ['draco_local=' . $jwtToken, $userId];
}

// -----------------------------
// Cart helpers (return arrays instead of channel writes)
// -----------------------------
function SRCCartQty(array $info, string $cookies): array
{
    $filename = 'src_helper';
    $scrApiQtyUrl = SrcConfigApi::getSrcApiInfo()['SrcQtyEndpoint'];
    $contentType = '';

    try {
        $response = callGetApi($scrApiQtyUrl, SetSRCHeaders(true, false, $info, $contentType, $cookies));
    } catch (Exception $e) {
        log_error($filename, 'Failed to call src api(quantity): ' . $e->getMessage());
        return ['SrcQuantity' => 0];
    }

    $json = json_decode($response, true);
    $srcQuantity = $json['data']['quantity'] ?? 0;

    return ['SrcQuantity' => (int)$srcQuantity];
}

function SRCCartPrice(array $info, string $cookies): array
{
    $filename = 'src_helper';
    $scrApiPriceUrl = SrcConfigApi::getSrcApiInfo()['SrcPriceEndpoint'];
    $contentType = '';

    try {
        $response = callGetApi($scrApiPriceUrl, SetSRCHeaders(true, false, $info, $contentType, $cookies));
    } catch (Exception $e) {
        log_error($filename, 'Failed to call src api(price): ' . $e->getMessage());
        return ['SrcPrice' => 0.0];
    }

    $json = json_decode($response, true);
    $srcPriceCents = $json['data']['cartSummary']['summaryRealPrice'] ?? 0;
    $srcPrice = ((float)$srcPriceCents) / 100.0;

    return ['SrcPrice' => $srcPrice];
}

function SCRCCartQty(array $info, string $cookies): array
{
    $filename = 'src_helper';
    $scrcQtyApiURL = SrcConfigApi::getSrcApiInfo()['ScrcQtyEndpoint'];
    $contentType = '';

    try {
        $response = callGetApi($scrcQtyApiURL, SetSRCHeaders(true, false, $info, $contentType, $cookies));
    } catch (Exception $e) {
        log_error($filename, 'Failed to call scrc api(quantity): ' . $e->getMessage());
        return ['ScrcQuantity' => 0];
    }

    $json = json_decode($response, true);
    $scrcQuantity = $json['data']['quantity'] ?? 0;

    return ['ScrcQuantity' => (int)$scrcQuantity];
}

function SCRCCartPrice(array $info, string $cookies): array
{
    $filename = 'src_helper';
    $scrcPriceApiURL = SrcConfigApi::getSrcApiInfo()['ScrcPriceEndpoint'];
    $contentType = '';

    try {
        $response = callGetApi($scrcPriceApiURL, SetSRCHeaders(true, false, $info, $contentType, $cookies));
    } catch (Exception $e) {
        log_error($filename, 'Failed to call scrc api(price): ' . $e->getMessage());
        return ['ScrcPrice' => 0.0];
    }

    $json = json_decode($response, true);
    $scrcPriceCents = $json['data']['cartSummary']['summaryRealPrice'] ?? 0;
    $scrcPrice = ((float)$scrcPriceCents) / 100.0;

    return ['ScrcPrice' => $scrcPrice];
}

// -----------------------------
// Account Info
// -----------------------------
function SRCAccountInfo(array $info, string $cookies): array
{
    $filename = 'src_helper';
    $scrcAccInfoApiURL = SrcConfigApi::getSrcApiInfo()['SrcAccountInfoEndpoint'];
    $contentType = '';

    $header = SetSRCHeaders(true, false, $info, $contentType, $cookies);

    try {
        $response = callGetApi($scrcAccInfoApiURL, $header);
    } catch (Exception $e) {
        log_error($filename, 'Failed to call SCRC API (account info): ' . $e->getMessage(), [
            'url'     => $scrcAccInfoApiURL,
            'header'  => $header,
            'info'    => $info,
            'cookies' => $cookies,
        ]);
        throw $e;
    }

    $responseMap = json_decode($response, true);
    if (isset($responseMap['error']) && $responseMap['error'] === 'User not logged in') {
        log_error($filename, 'Unauthorized in SRCAccountInfo', [
            'url'     => $scrcAccInfoApiURL,
            'header'  => $header,
            'info'    => $info,
            'cookies' => $cookies,
        ]);
        throw new Exception('unauthorized, please check your session');
    }

    // In Go, AccountResponse.Data is a single account object.
    $accounts = [];
    if (!empty($responseMap['data']) && !empty($responseMap['data']['id'])) {
        $accounts[] = $responseMap['data'];
    }

    return $accounts; // [] of account info
}

// -----------------------------
// My Address
// -----------------------------
function SRCMyAddress(array $info, string $cookies): array
{
    $filename = 'src_helper';
    $scrcAddressInfoApiURL = SrcConfigApi::getSrcApiInfo()['SrcMyAddressEndpoint'];
    $contentType = '';

    $header = SetSRCHeaders(true, false, $info, $contentType, $cookies);

    try {
        $response = callGetApi($scrcAddressInfoApiURL, $header);
    } catch (Exception $e) {
        log_error($filename, 'Failed to call SCRC API (my address): ' . $e->getMessage(), [
            'url'     => $scrcAddressInfoApiURL,
            'header'  => $header,
            'info'    => $info,
            'cookies' => $cookies,
        ]);
        throw $e;
    }

    $responseMap = json_decode($response, true);
    if (!is_array($responseMap)) {
        log_error($filename, 'Failed to decode SRCMyAddress response', [
            'url'     => $scrcAddressInfoApiURL,
            'header'  => $header,
            'info'    => $info,
            'cookies' => $cookies,
        ]);
        throw new Exception('Invalid JSON from SRCMyAddress');
    }

    if (isset($responseMap['error']) && $responseMap['error'] === 'User not logged in') {
        log_error($filename, 'Unauthorized in SRCMyAddress', [
            'url'     => $scrcAddressInfoApiURL,
            'header'  => $header,
            'info'    => $info,
            'cookies' => $cookies,
        ]);
        throw new Exception('Unauthorized, please check your session');
    }

    // In Go, MyAddressResponse.Data is a list
    $addresses = [];
    if (!empty($responseMap['success']) && !empty($responseMap['data']) && is_array($responseMap['data'])) {
        $addresses = $responseMap['data'];
    }

    return $addresses; // [] of address objects
}

// -----------------------------
// DeleteSRCAddress
// -----------------------------
function DeleteSRCAddress(array $info, int $addressId, string $cookies): void
{
    $srcDeleteAddressApiURL = sprintf(SrcConfigApi::getSrcApiInfo()['SrcDeleteAddressEndpoint'], $addressId);
    $contentType = 'application/json';

    $headers = SetSRCHeaders(true, false, $info, $contentType, $cookies);

    try {
        $response = callDeleteApi($srcDeleteAddressApiURL, $headers);
    } catch (Exception $e) {
        // network/transport error
        throw $e;
    }

    $responseMap = json_decode($response, true);
    if (is_array($responseMap) && isset($responseMap['error']) && $responseMap['error'] === 'User not logged in') {
        throw new Exception('unauthorized, please check your session');
    }

    // Go code also treated certain HTTP status errors as unauthorized, but here
    // we only rely on the JSON body. Add your own HTTP code checks if needed.
}

// -----------------------------
// UpdateSRCAddress
// -----------------------------
function UpdateSRCAddress(array $info, array $address, string $cookies): void
{
    $srcUpdateAddressApiURL = SrcConfigApi::getSrcApiInfo()['SrcUpdateAddressEndpoint'];
    $contentType = 'application/json';

    $jsonData = json_encode($address);
    if ($jsonData === false) {
        throw new Exception('failed to marshal address');
    }

    $headers = SetSRCHeaders(true, false, $info, $contentType, $cookies);

    $response = callPutApi($srcUpdateAddressApiURL, $headers, $jsonData);

    $responseMap = json_decode($response, true);
    if (is_array($responseMap) && isset($responseMap['error']) && $responseMap['error'] === 'User not logged in') {
        throw new Exception('unauthorized, please check your session');
    }
}

// -----------------------------
// AddSRCAddress
// -----------------------------
function AddSRCAddress(array $info, array $address, string $cookies): void
{
    $srcAddAddressApiURL = SrcConfigApi::getSrcApiInfo()['SrcAddAddressEndpoint'];
    $contentType = 'application/json';

    $jsonData = json_encode($address);
    if ($jsonData === false) {
        throw new Exception('failed to marshal address');
    }

    $headers = SetSRCHeaders(true, false, $info, $contentType, $cookies);

    $response = callPostApi($srcAddAddressApiURL, $headers, $jsonData);

    $responseMap = json_decode($response, true);
    if (is_array($responseMap) && isset($responseMap['error']) && $responseMap['error'] === 'User not logged in') {
        throw new Exception('unauthorized, please check your session');
    }
}

// -----------------------------
// GetSRCAddressAPI
// -----------------------------
function GetSRCAddressAPI(array $info, string $id, string $cookies): array
{
    $filename = 'src_helper';

    $url = sprintf(SrcConfigApi::getSrcApiInfo()['SrcGetSrcAddressEndpoint'], $id);
    $contentType = '';

    // Go code forcibly empties cookies but still sets includeCookie=true
    $cookies = '';
    try {
        $response = callGetApi($url, SetSRCHeaders(true, false, $info, $contentType, $cookies));
    } catch (Exception $e) {
        log_error($filename, 'Failed to call SCRC API: ' . $e->getMessage());
        return [];
    }

    $srcAddressResponse = json_decode($response, true);
    if (!is_array($srcAddressResponse)) {
        log_error($filename, 'Failed to unmarshal response in GetSRCAddressAPI');
        return [];
    }

    if (!empty($srcAddressResponse['success']) && !empty($srcAddressResponse['data']) && is_array($srcAddressResponse['data'])) {
        return $srcAddressResponse['data']; // list of src addresses
    }

    return [];
}

// -----------------------------
// GetSRCToReview
// -----------------------------
function GetSRCToReview(string $cookieString): string
{
    $url = SrcConfigApi::$SRCDomain . 'api/trade/order/management/buyer/list?pageSize=10&pageNo=1&canComment=1';

    $headers = [
        'Accept'     => '*/*',
        'Connection' => 'keep-alive',
        'Cookie'     => $cookieString,
    ];

    return callGetApi($url, $headers);
}

// -----------------------------
// GetSRCPostReviewPageByOrderLineId
// -----------------------------
function GetSRCPostReviewPageByOrderLineId(string $OrderLineId, string $accessToken): string
{
    // accessToken is not used in the Go code either
    return SrcConfigApi::$SRCDomain . 'buyer/comments/create?orderLineIds=' . urlencode($OrderLineId) . '&';
}

// -----------------------------
// GetWishlist
// -----------------------------
function GetWishlist(array $info, string $cookies, string $srcUserId, string $filename): string
{
    $scrApiWishlistGetUrl = SrcConfigApi::getSrcApiInfo()['SrcWishListGetEndpoint'];
    $contentType = '';

    $pageSize = (string)($info['PageSize'] ?? '10');
    $pageNo   = (string)($info['PageNo']   ?? '1');

    $scrApiWishlistGetUrl = str_replace('{pageSize}', $pageSize, $scrApiWishlistGetUrl);
    $scrApiWishlistGetUrl = str_replace('{pageNo}', $pageNo, $scrApiWishlistGetUrl);
    $scrApiWishlistGetUrl = str_replace('{srcUserId}', $srcUserId, $scrApiWishlistGetUrl);

    $headers = SetSRCHeaders(true, false, $info, $contentType, $cookies);

    $res = callGetApi($scrApiWishlistGetUrl, $headers);

    log_info($filename, 'GetWishlist Request/Response', [
        'srcUserId' => $srcUserId,
        'URL'       => $scrApiWishlistGetUrl,
        'Headers'   => $headers,
        'Response'  => $res,
    ]);

    return $res;
}

// -----------------------------
// DeleteWishlist
// -----------------------------
function DeleteWishlist(array $info, string $cookies, string $srcUserId, string $filename): string
{
    $scrApiWishlistDeleteUrl = SrcConfigApi::getSrcApiInfo()['SrcWishListDeleteEndpoint'];
    $contentType = 'application/json';

    $itemId = (string)($info['ItemId'] ?? '');
    $scrApiWishlistDeleteUrl = str_replace('{itemId}', $itemId, $scrApiWishlistDeleteUrl);
    $scrApiWishlistDeleteUrl = str_replace('{srcUserId}', $srcUserId, $scrApiWishlistDeleteUrl);

    $jsonData = json_encode($info);
    if ($jsonData === false) {
        throw new Exception('failed to marshal info');
    }

    $payload = $jsonData;
    $headers = SetSRCHeaders(true, false, $info, $contentType, $cookies);

    $response = callPutApi($scrApiWishlistDeleteUrl, $headers, $payload);

    log_info($filename, 'DeleteWishlist Request/Response', [
        'srcUserId' => $srcUserId,
        'URL'       => $scrApiWishlistDeleteUrl,
        'Headers'   => $headers,
        'Body'      => $jsonData,
        'Response'  => $response,
    ]);

    $responseMap = json_decode($response, true);
    if (is_array($responseMap) && isset($responseMap['error']) && $responseMap['error'] === 'User not logged in') {
        log_error($filename, 'DeleteWishlist unauthorized', $responseMap);
        throw new Exception('unauthorized, please check your session');
    }

    return $response;
}
