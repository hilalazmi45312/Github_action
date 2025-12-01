<?php

use XTS\WC_Wishlist\Wishlist;
use XTS\Modules\WC_Wishlist;

function get_wishlist($data = [])
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in'], 401);
    }

    $shData  = senhengallInfo();
    $user_id = $shData['user_id'];

    // pagination & filters from request: pageSize, pageNo, targetType (optional)
    $page_size  = isset($data['pageSize']) ? max(1, (int) $data['pageSize']) : 10;
    $page_no    = isset($data['pageNo'])   ? max(1, (int) $data['pageNo'])   : 1;
    $targetType = isset($data['targetType']) ? (int) $data['targetType'] : 1; // default 1, like your sample

    $wishlist = new Wishlist();
    $items    = $wishlist->get_all(); // whatever your class returns

    if (!is_array($items)) {
        $items = [];
    }

    $total = count($items);
    $page_count = $page_size > 0 ? (int) ceil($total / $page_size) : 1;

    // Slice for current page
    $offset      = ($page_no - 1) * $page_size;
    $paged_items = array_slice($items, $offset, $page_size);

    $response_items = [];

    if (!empty($paged_items)) {
        foreach ($paged_items as $item) {
            $product_id = isset($item['product_id']) ? (int) $item['product_id'] : 0;
            if ($product_id <= 0) {
                continue;
            }

            $product = wc_get_product($product_id);
            if (! $product) {
                continue;
            }

            $image_url = wp_get_attachment_image_url($product->get_image_id(), 'medium');
            $price_raw = wc_get_price_to_display($product);  // float
            $price_str = (string) (int) round($price_raw * 100); // if you want "134900" style (cents)

            $tenant_id = 1; // hardcode or derive as needed

            // Build something like: "1_119325_114800204251007_1"
            $unique_key = sprintf(
                '%d_%d_%d_%d',
                $tenant_id,
                $shData['idmapping'],
                $product_id,
                $targetType
            );

            $sku = $product->get_sku();
            // Match format "[114800205393779]"
            $skuId = $sku ? '[' . $sku . ']' : '[]';
            $status = $product->get_status();
            $status = ($status === 'publish') ? '1' : '0';

            // Category names (comma-separated)
            $categories = strip_tags(wc_get_product_category_list($product_id));

            $response_items[] = [
                'id'        => $product_id,
                'uniqueKey' => $unique_key,
                'tenantId'  => $tenant_id,
                'targetId'  => $product_id,
                'targetType' => $targetType,
                'targetSubType' => null,
                'userId'    => $shData['idmapping'],
                'extra'     => [
                    'mainImage'     => $image_url,
                    'lowPrice'      => $price_str,
                    'highPrice'     => $price_str,
                    'name'          => $product->get_name(),
                    'id'            => (string) $product_id,
                    'type'          => (string) $targetType,
                    'businessType'  => '1',
                    'skuId'         => $skuId,
                    'status'        => $status,
                ],
                'status'                 => $status,
                'createdAt'              => NULL,
                'updatedAt'              => NULL,
                'merchantScoinPercentage' => null,
                'platformScoinPercentage' => null,
                'categoryName'           => $categories,
                'shopName'               => get_bloginfo('name'),
                'seoCanonical'           => null,
                'channel'                => 'SRC',
                'discountValue'          => null,
                'scoinRedemption'        => null,
            ];
        }
    }

    echo wp_json_encode([
        'success' => true,
        'data'    => [
            'total'     => $total,
            'data'      => $response_items,
            'pageCount' => $page_count,
            'pageSize'  => $page_size,
            'empty'     => ($total === 0),
        ],
    ]);
}

function add_wishlist($data = [])
{
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in'], 401);
    }

    $shData  = senhengallInfo();
    $user_id = $shData['user_id'];

    // Extract the parameters from the request
    $page_size  = isset($data['pageSize']) ? max(1, (int) $data['pageSize']) : 10;
    $page_no    = isset($data['pageNo'])   ? max(1, (int) $data['pageNo'])   : 1;
    $targetType = isset($data['targetType']) ? (int) $data['targetType'] : 1;
    $userId = isset($data['userId']) ? (int) $data['userId'] : $user_id;
    $targetId = isset($data['targetId']) ? (int) $data['targetId'] : null;
    $tenantId = isset($data['tenantId']) ? (int) $data['tenantId'] : 1;

    // Check if product_id or product_ids are provided
    $product_ids = [];

    if (isset($targetId)) {
        $product_ids[] = (int) $targetId;
    }

    $product_ids = array_unique(array_filter($product_ids));

    if (empty($product_ids)) {
        echo wp_json_encode([
            'success' => false,
            'message' => 'No valid product IDs provided',
        ]);
        return;
    }

    $wishlist = new Wishlist();

    $added = 0;

    foreach ($product_ids as $product_id) {
        $product_id = apply_filters(
            'wpml_object_id',
            $product_id,
            'product',
            true,
            apply_filters('wpml_default_language', null)
        );

        if ($wishlist->add($product_id, '')) {
            $added++;
        }
    }

    $wishlist->update_count_cookie();

    echo wp_json_encode([
        'success' => true,
        'data'    => true,
    ]);
}


function delete_wishlist($data = [])
{
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in'], 401);
    }

    $shData  = senhengallInfo();
    $user_id = $shData['user_id'];

    // Extract the parameters from the request
    $page_size  = isset($data['pageSize']) ? max(1, (int) $data['pageSize']) : 10;
    $page_no    = isset($data['pageNo'])   ? max(1, (int) $data['pageNo'])   : 1;
    $targetType = isset($data['targetType']) ? (int) $data['targetType'] : 1;
    $userId = isset($data['userId']) ? (int) $data['userId'] : $user_id;
    $targetId = isset($data['targetId']) ? (int) $data['targetId'] : null;
    $tenantId = isset($data['tenantId']) ? (int) $data['tenantId'] : 1;

    // Check if product_id or product_ids are provided
    $product_ids = [];

    if (isset($targetId)) {
        $product_ids[] = (int) $targetId;
    }

    // if (isset($data['product_ids']) && is_array($data['product_ids'])) {
    //     foreach ($data['product_ids'] as $pid) {
    //         $pid = (int) $pid;
    //         if ($pid > 0) {
    //             $product_ids[] = $pid;
    //         }
    //     }
    // }

    $product_ids = array_unique(array_filter($product_ids));

    // Return error if no valid product IDs are provided
    if (empty($product_ids)) {
        echo wp_json_encode([
            'success' => false,
            'data' => false,
        ]);
        return;
    }

    // Initialize wishlist object
    $wishlist = new Wishlist();
    $wc_wishlist = new WC_Wishlist();

    $deleted = 0;

    // Loop through the product IDs and delete them
    foreach ($product_ids as $product_id) {
        $wc_wishlist->remove_product_from_wishlist($wishlist, $product_id, '');
        $deleted++;
    }

    // Update the count in the wishlist
    $wishlist->update_count_cookie();

    // Return success response with the count of deleted items
    echo wp_json_encode([
        'success' => true,
        'data'    => true,
    ]);
}
