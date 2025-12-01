<?php
/**
 * Fail-safe filtering class for WooCommerce Advanced Bulk Edit
 *
 * This class provides a secondary filtering mechanism to validate
 * that all filters have been properly applied to the result set.
 */
class W3ExABulkEdit_FailsafeFilter {

    private $filters = [];
    private $mode = 'AND'; // Default to AND mode for all filters
    private $debug = false;
    private $discrepancies = [];
    private $allProductsById = []; // Store all products for parent lookups

    /**
     * Constructor
     * @param bool $debug Enable debug logging
     */
    public function __construct($debug = false) {
        $this->debug = $debug;
    }

    /**
     * Apply all filters to the result set
     * @param array $products Array of product objects
     * @param array $filterParams All filter parameters from loadProducts
     * @return array Filtered products
     */
    public function applyFilters($products, $filterParams) {
        // Parse and store filters for processing
        $this->parseFilters($filterParams);

        // Index all products by ID for parent lookups
        $this->allProductsById = [];
        foreach ($products as $product) {
            $this->allProductsById[$product->ID] = $product;
        }

        // Apply each filter in sequence
        $filteredProducts = [];
        foreach ($products as $product) {
            if ($this->matchesAllFilters($product)) {
                $filteredProducts[] = $product;
            }
        }

        // Include parent products for matched variations
        $filteredProducts = $this->includeParentProducts($filteredProducts, $products);

        // Log any discrepancies
        if ($this->debug && count($products) !== count($filteredProducts)) {
            $this->logDiscrepancy($products, $filteredProducts);
        }

        return $filteredProducts;
    }

    /**
     * Include parent products for matched variations
     * @param array $filteredProducts Products that passed filters
     * @param array $allProducts Original full product list
     * @return array Products with parent products included
     */
    private function includeParentProducts($filteredProducts, $allProducts) {
        // Check if feature is enabled
        $include_parents = true; // Default

        // Check config loader if available
        if (class_exists('W3ExABulkEdit_ConfigLoader')) {
            $include_parents = W3ExABulkEdit_ConfigLoader::should_include_parents();
        } elseif (defined('WCABE_FAILSAFE_INCLUDE_PARENTS')) {
            // Fallback to constant for backward compatibility
            $include_parents = WCABE_FAILSAFE_INCLUDE_PARENTS;
        }

        if (!$include_parents) {
            return $filteredProducts;
        }

        $productsById = [];
        $filteredById = [];
        $parentIdsToAdd = [];

        // Index all products by ID for quick lookup
        foreach ($allProducts as $product) {
            $productsById[$product->ID] = $product;
        }

        // Index filtered products and find parent IDs that need to be added
        foreach ($filteredProducts as $product) {
            $filteredById[$product->ID] = true;

            // If this is a variation and its parent isn't in the filtered list
            if (!empty($product->post_parent) && $product->post_type === 'product_variation') {
                if (!isset($filteredById[$product->post_parent])) {
                    $parentIdsToAdd[$product->post_parent] = true;
                }
            }
        }

        // Build final product list with proper ordering
        $finalProducts = [];
        $processedIds = [];

        // Process products maintaining parent-before-variation order
        foreach ($filteredProducts as $product) {
            // Skip if already processed
            if (isset($processedIds[$product->ID])) {
                continue;
            }

            // If this is a variation whose parent needs to be added
            if (!empty($product->post_parent) &&
                $product->post_type === 'product_variation' &&
                isset($parentIdsToAdd[$product->post_parent]) &&
                !isset($processedIds[$product->post_parent])) {

                // Add parent first
                if (isset($productsById[$product->post_parent])) {
                    $finalProducts[] = $productsById[$product->post_parent];
                    $processedIds[$product->post_parent] = true;
                }
            }

            // Add the current product
            $finalProducts[] = $product;
            $processedIds[$product->ID] = true;

            // If this is a parent product, add any of its variations that were in filtered list
            if ($product->post_type !== 'product_variation') {
                foreach ($filteredProducts as $possibleChild) {
                    if ($possibleChild->post_parent == $product->ID &&
                        !isset($processedIds[$possibleChild->ID])) {
                        $finalProducts[] = $possibleChild;
                        $processedIds[$possibleChild->ID] = true;
                    }
                }
            }
        }

        return $finalProducts;
    }

    /**
     * Parse filter parameters into internal structure
     */
    private function parseFilters($filterParams) {
        $this->filters = [];

        // Title filter
        if (!empty($filterParams['title'])) {
            $this->filters[] = [
                'type' => 'title',
                'params' => $filterParams['title']
            ];
        }

        // SKU filter
        if (!empty($filterParams['sku'])) {
            $this->filters[] = [
                'type' => 'sku',
                'params' => $filterParams['sku']
            ];
        }

        // Category filter
        if (!empty($filterParams['categories']) && is_array($filterParams['categories'])) {
            $this->filters[] = [
                'type' => 'category',
                'params' => $filterParams['categories'],
                'mode' => $filterParams['category_or'] ? 'OR' : 'AND'
            ];
        }

        // Price filter
        if (!empty($filterParams['price'])) {
            $this->filters[] = [
                'type' => 'price',
                'params' => $filterParams['price']
            ];
        }

        // Sale filter
        if (!empty($filterParams['sale'])) {
            $this->filters[] = [
                'type' => 'sale',
                'params' => $filterParams['sale']
            ];
        }

        // Stock filter
        if (!empty($filterParams['stock'])) {
            $this->filters[] = [
                'type' => 'stock',
                'params' => $filterParams['stock']
            ];
        }

        // Description filter
        if (!empty($filterParams['description'])) {
            $this->filters[] = [
                'type' => 'description',
                'params' => $filterParams['description']
            ];
        }

        // Short description filter
        if (!empty($filterParams['short_description'])) {
            $this->filters[] = [
                'type' => 'short_description',
                'params' => $filterParams['short_description']
            ];
        }

        // Tags filter
        if (!empty($filterParams['tags']) && is_array($filterParams['tags'])) {
            $this->filters[] = [
                'type' => 'tags',
                'params' => $filterParams['tags']
            ];
        }

        // Custom field filter (from customparam)
//        if (!empty($filterParams['custom']) && is_array($filterParams['custom'])) {
//            $this->filters[] = [
//                'type' => 'custom_field',
//                'params' => $filterParams['custom']
//            ];
//        }

        // GUID filter
        if (!empty($filterParams['guid'])) {
            $this->filters[] = [
                'type' => 'guid',
                'params' => $filterParams['guid']
            ];
        }

        // Custom search filter (handle attributes and other custom searches)
        if (!empty($filterParams['custom_search']) && is_array($filterParams['custom_search'])) {
            $attributes = [];
            $customFields = [];
            $otherFilters = [];
            $regularPriceFilter = null;
            $salePriceFilter = null;
            $shippingClassFilter = null;

            foreach ($filterParams['custom_search'] as $custitem) {
                if (isset($custitem['type'])) {
                    // Handle price filters from custom_search
                    if (isset($custitem['id'])) {
                        if ($custitem['id'] === '_regular_price' && isset($custitem['title']) && isset($custitem['value'])) {
                            // Extract regular price filter
                            $regularPriceFilter = [
                                'title' => $custitem['title'],
                                'value' => $custitem['value']
                            ];
                            continue;
                        } elseif ($custitem['id'] === '_sale_price' && isset($custitem['title']) && isset($custitem['value'])) {
                            // Extract sale price filter
                            $salePriceFilter = [
                                'title' => $custitem['title'],
                                'value' => $custitem['value']
                            ];
                            continue;
                        } elseif ($custitem['id'] === 'product_shipping_class' && isset($custitem['array'])) {
                            // Extract shipping class filter
                            $shippingClassFilter = $custitem['array'];
                            continue;
                        }
                    }

                    if ($custitem['type'] === 'attribute' && isset($custitem['title']['attr']) && isset($custitem['title']['value'])) {
                        // Extract attribute filters
                        $attributes[$custitem['title']['attr']] = $custitem['title']['value'];
                    } elseif ($custitem['type'] === 'custom' || $custitem['type'] === 'customh') {
                        // Handle other custom field types (but skip shipping class as it's already handled)
                        if (!isset($custitem['id']) || $custitem['id'] !== 'product_shipping_class') {
                            $customFields[] = $custitem;
                        }
                    } else {
                        // Handle other types (date, etc.)
                        $otherFilters[] = $custitem;
                    }
                }
            }

            // Add regular price filter if found in custom_search
            if ($regularPriceFilter !== null && empty($filterParams['price'])) {
                $this->filters[] = [
                    'type' => 'price',
                    'params' => $regularPriceFilter
                ];
            }

            // Add sale price filter if found in custom_search
            if ($salePriceFilter !== null && empty($filterParams['sale'])) {
                $this->filters[] = [
                    'type' => 'sale_price',
                    'params' => $salePriceFilter
                ];
            }

            // Add attribute filter if any
            if (!empty($attributes)) {
                $this->filters[] = [
                    'type' => 'attributes',
                    'params' => $attributes
                ];
            }

            // Add shipping class filter if any
            if (!empty($shippingClassFilter)) {
                $this->filters[] = [
                    'type' => 'shipping_class',
                    'params' => $shippingClassFilter
                ];
            }

            // Add remaining custom search filters if any
            if (!empty($customFields) || !empty($otherFilters)) {
                $this->filters[] = [
                    'type' => 'custom_search',
                    'params' => array_merge($customFields, $otherFilters)
                ];
            }
        }
    }

    /**
     * Check if product matches all filters (AND mode)
     */
    private function matchesAllFilters($product) {
        foreach ($this->filters as $filter) {
            if (!$this->applyFilter($product, $filter)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Apply individual filter to product
     */
    private function applyFilter($product, $filter) {
        switch ($filter['type']) {
            case 'title':
                return $this->filterByTitle($product, $filter['params']);
            case 'sku':
                return $this->filterBySku($product, $filter['params']);
            case 'category':
                return $this->filterByCategory($product, $filter['params'], $filter['mode'] ?? 'AND');
            case 'price':
                return $this->filterByPrice($product, $filter['params']);
            case 'sale':
                return $this->filterBySalePrice($product, $filter['params']);
            case 'sale_price':
                return $this->filterBySalePriceNumeric($product, $filter['params']);
            case 'stock':
                return $this->filterByStock($product, $filter['params']);
            case 'description':
                return $this->filterByDescription($product, $filter['params']);
            case 'short_description':
                return $this->filterByShortDescription($product, $filter['params']);
            case 'tags':
                return $this->filterByTags($product, $filter['params']);
            case 'attributes':
                return $this->filterByAttributes($product, $filter['params']);
            case 'custom_field':
                return $this->filterByCustomField($product, $filter['params']);
            case 'guid':
                return $this->filterByGuid($product, $filter['params']);
            case 'shipping_class':
                return $this->filterByShippingClass($product, $filter['params']);
            case 'custom_search':
                return $this->filterByCustomSearch($product, $filter['params']);
        }
        return true; // Unknown filter type, pass through
    }

    /**
     * Filter by product title
     */
    private function filterByTitle($product, $params) {
        if (empty($params) || !isset($product->post_title)) {
            return true;
        }

        $title = strtolower($product->post_title);

        // Handle array params - use first element as search term
        if (is_array($params)) {
            if (empty($params)) {
                return true;
            }
            $search = strtolower(reset($params));
        } else {
            $search = strtolower($params);
        }

        return strpos($title, $search) !== false;
    }

    /**
     * Filter by SKU with CSV support
     */
    private function filterBySku($product, $params) {
        if (empty($params)) {
            return true;
        }

        // If product doesn't have SKU field, handle based on operation
        if (!isset($product->_sku)) {
            $operation = $params['value'] ?? 'con';
            // For "not contains" operation, products without SKU pass
            // For all other operations, products without SKU fail
            return $operation === 'notcon';
        }

        $sku = $product->_sku;
        $operation = $params['value'] ?? 'con';
        $searchValue = $params['title'] ?? '';

        // Handle CSV list
        if (strpos($searchValue, ',') !== false) {
            $skuList = array_map('trim', explode(',', $searchValue));
            return $this->matchesSkuList($sku, $skuList, $operation);
        }

        // Single SKU matching
        switch ($operation) {
            case 'con':
                return strpos($sku, $searchValue) !== false;
            case 'isexactly':
                return $sku === $searchValue;
            case 'notcon':
                return strpos($sku, $searchValue) === false;
            case 'start':
                return strpos($sku, $searchValue) === 0;
            case 'end':
                return substr($sku, -strlen($searchValue)) === $searchValue;
        }
        return true;
    }

    /**
     * Helper for SKU list matching
     */
    private function matchesSkuList($sku, $skuList, $operation) {
        switch ($operation) {
            case 'con':
                foreach ($skuList as $search) {
                    if (strpos($sku, $search) !== false) {
                        return true;
                    }
                }
                return false;

            case 'isexactly':
                return in_array($sku, $skuList);

            case 'notcon':
                foreach ($skuList as $search) {
                    if (strpos($sku, $search) !== false) {
                        return false;
                    }
                }
                return true;

            case 'start':
                foreach ($skuList as $search) {
                    if (strpos($sku, $search) === 0) {
                        return true;
                    }
                }
                return false;

            case 'end':
                foreach ($skuList as $search) {
                    if (substr($sku, -strlen($search)) === $search) {
                        return true;
                    }
                }
                return false;
        }
        return true;
    }

    /**
     * Filter by category
     */
    private function filterByCategory($product, $params, $mode = 'AND') {
        if (empty($params)) {
            return true;
        }

        // Check if this is a variation and get parent's categories
        $productToCheck = $product;
        if (isset($product->post_type) && $product->post_type === 'product_variation') {
            // For variations, we need to check the parent product's categories
            if (!empty($product->post_parent) && isset($this->allProductsById[$product->post_parent])) {
                $productToCheck = $this->allProductsById[$product->post_parent];
            } else {
                // If we can't find the parent, treat as no categories
                // This handles the 'none' case for orphaned variations
                if (in_array('none', $params)) {
                    return true;
                }
                return false;
            }
        }

        // Handle 'none' (uncategorized)
        if (in_array('none', $params)) {
            return !isset($productToCheck->product_cat_ids) ||
                   empty($productToCheck->product_cat_ids);
        }

        if (!isset($productToCheck->product_cat_ids)) {
            return false;
        }

        $productCats = explode(',', $productToCheck->product_cat_ids);

        // Check if any required category matches
        foreach ($params as $requiredCat) {
            if (in_array($requiredCat, $productCats)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter by price range
     */
    private function filterByPrice($product, $params) {
        if (empty($params)) {
            return true;
        }

        $price = isset($product->_regular_price) ?
                 floatval($product->_regular_price) : 0;

        $operation = $params['value'] ?? '';
        $compareValue = floatval($params['title'] ?? 0);

        switch ($operation) {
            case 'more':
                return $price > $compareValue;
            case 'less':
                return $price < $compareValue;
            case 'equal':
                return $price == $compareValue;
            case 'moree':
                return $price >= $compareValue;
            case 'lesse':
                return $price <= $compareValue;
            case 'compexp':
                return $this->evaluateComplexExpression($price, $params['title']);
        }

        return true;
    }

    /**
     * Evaluate complex price expressions (e.g., "10-50" for range)
     */
    private function evaluateComplexExpression($price, $expression) {
        // Handle range expressions like "10-50"
        if (strpos($expression, '-') !== false) {
            $parts = explode('-', $expression);
            if (count($parts) == 2) {
                $min = floatval($parts[0]);
                $max = floatval($parts[1]);
                return $price >= $min && $price <= $max;
            }
        }
        return true;
    }

    /**
     * Filter by sale price
     */
    private function filterBySalePrice($product, $params) {
        if (empty($params)) {
            return true;
        }

        // Check if product is on sale
        if ($params === 'yes') {
            return isset($product->_sale_price) &&
                   !empty($product->_sale_price) &&
                   $product->_sale_price !== '';
        } else if ($params === 'no') {
            return !isset($product->_sale_price) ||
                   empty($product->_sale_price) ||
                   $product->_sale_price === '';
        }

        return true;
    }

    /**
     * Filter by sale price with numeric comparisons
     */
    private function filterBySalePriceNumeric($product, $params) {
        if (empty($params)) {
            return true;
        }

        $salePrice = isset($product->_sale_price) ?
                    floatval($product->_sale_price) : 0;

        // If no sale price, treat as 0 for comparison
        if (empty($product->_sale_price) || $product->_sale_price === '') {
            $salePrice = 0;
        }

        $operation = $params['value'] ?? '';
        $compareValue = floatval($params['title'] ?? 0);

        switch ($operation) {
            case 'more':
                return $salePrice > $compareValue;
            case 'less':
                return $salePrice < $compareValue;
            case 'equal':
                return $salePrice == $compareValue;
            case 'moree':
                return $salePrice >= $compareValue;
            case 'lesse':
                return $salePrice <= $compareValue;
            case 'compexp':
                return $this->evaluateComplexExpression($salePrice, $params['title']);
        }

        return true;
    }

    /**
     * Filter by stock status/quantity
     */
    private function filterByStock($product, $params) {
        if (empty($params)) {
            return true;
        }

        // Stock status filter
        if (isset($params['status'])) {
            $stockStatus = $product->_stock_status ?? 'instock';
            if ($params['status'] !== $stockStatus) {
                return false;
            }
        }

        // Stock quantity filter
        if (isset($params['quantity'])) {
            $stock = isset($product->_stock) ? intval($product->_stock) : 0;
            $operation = $params['quantity']['operation'] ?? 'equal';
            $value = intval($params['quantity']['value'] ?? 0);

            switch ($operation) {
                case 'more':
                    return $stock > $value;
                case 'less':
                    return $stock < $value;
                case 'equal':
                    return $stock == $value;
                case 'moree':
                    return $stock >= $value;
                case 'lesse':
                    return $stock <= $value;
            }
        }

        return true;
    }

    /**
     * Filter by product description
     */
    private function filterByDescription($product, $params) {
        if (empty($params) || !isset($product->post_content)) {
            return true;
        }

        $description = strtolower($product->post_content);

        // Handle array params - use first element as search term
        if (is_array($params)) {
            if (empty($params)) {
                return true;
            }
            $search = strtolower(reset($params));
        } else {
            $search = strtolower($params);
        }

        return strpos($description, $search) !== false;
    }

    /**
     * Filter by short description
     */
    private function filterByShortDescription($product, $params) {
        if (empty($params) || !isset($product->post_excerpt)) {
            return true;
        }

        $shortDesc = strtolower($product->post_excerpt);

        // Handle array params - use first element as search term
        if (is_array($params)) {
            if (empty($params)) {
                return true;
            }
            $search = strtolower(reset($params));
        } else {
            $search = strtolower($params);
        }

        return strpos($shortDesc, $search) !== false;
    }

    /**
     * Filter by shipping class
     */
    private function filterByShippingClass($product, $params) {
        if (empty($params)) {
            return true;
        }

        // Check if this is a variation and needs to check parent's shipping class
        $productToCheck = $product;

        if (isset($product->post_type) && $product->post_type === 'product_variation') {
            // Check if variation should inherit from parent
            $shippingClassValue = isset($product->product_shipping_class_ids) ?
                                 trim($product->product_shipping_class_ids) : '';

            // Empty value, '0', or 0 means inherit from parent ("Same as parent")
            if ($shippingClassValue === '' || $shippingClassValue === '0' || $shippingClassValue == 0) {
                // Inherit from parent product
                if (!empty($product->post_parent) && isset($this->allProductsById[$product->post_parent])) {
                    $productToCheck = $this->allProductsById[$product->post_parent];
                } else {
                    // Can't find parent, no shipping class
                    return false;
                }
            }
            // Otherwise use variation's own shipping class
        }

        // Now check if the product has the required shipping class
        if (!isset($productToCheck->product_shipping_class_ids)) {
            return false;
        }

        // Convert shipping class IDs to array
        $productShippingClasses = explode(',', $productToCheck->product_shipping_class_ids);

        // Check if any of the required shipping classes match
        foreach ($params as $requiredShippingClass) {
            if (in_array($requiredShippingClass, $productShippingClasses)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter by product tags
     */
    private function filterByTags($product, $params) {
        if (empty($params)) {
            return true;
        }

        if (!isset($product->product_tag_ids)) {
            return false;
        }

        $productTags = explode(',', $product->product_tag_ids);

        // Check if any required tag matches
        foreach ($params as $requiredTag) {
            if (in_array($requiredTag, $productTags)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Filter by product attributes
     */
    private function filterByAttributes($product, $params) {
        if (empty($params)) {
            return true;
        }

        // Check attribute format: attribute_pa_{attr_name} = value
        foreach ($params as $attrName => $attrValue) {
            $attrKey = 'attribute_pa_' . $attrName;

            // Check if product/variation has this attribute
            if (isset($product->$attrKey)) {
                // Direct match for variations
                if ($product->$attrKey != $attrValue && !empty($product->$attrKey)) {
                    return false;
                }
            } else {
                // Check if this is a product (not variation) and has attribute_pa_ids
                // This would require checking if the term_id is in the attribute_pa_ids
                // For now, if attribute is not found on the product, we filter it out
                // unless the attribute value is empty (meaning any value is acceptable)
                if (!empty($attrValue)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Filter by custom field
     */
    private function filterByCustomField($product, $params) {
        if (empty($params)) {
            return true;
        }

        foreach ($params as $fieldName => $fieldValue) {
            $metaKey = '_' . $fieldName;

            if (isset($product->$metaKey)) {
                if ($product->$metaKey != $fieldValue) {
                    return false;
                }
            } elseif (isset($product->$fieldName)) {
                if ($product->$fieldName != $fieldValue) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Filter by GUID
     */
    private function filterByGuid($product, $params) {
        if (empty($params) || !isset($product->guid)) {
            return true;
        }

        return $product->guid === $params;
    }

    /**
     * Filter by custom search parameters
     */
    private function filterByCustomSearch($product, $params) {
        if (empty($params)) {
            return true;
        }

        foreach ($params as $custitem) {
            if (!isset($custitem['type'])) {
                continue;
            }

            $fieldId = $custitem['id'] ?? null;
            $fieldType = $custitem['type'];
            $operation = $custitem['value'] ?? null;
            $searchValue = $custitem['title'] ?? null;

            if ($fieldId === null) {
                continue;
            }

            if ($fieldType === 'integer' || $fieldType === 'decimal' || $fieldType === 'decimal3') {
                if (!$this->filterNumericField($product, $fieldId, $operation, $searchValue)) {
                    return false;
                }
            } elseif ($fieldType === 'text' || $fieldType === 'textarea') {
                if (!$this->filterTextField($product, $fieldId, $operation, $searchValue)) {
                    return false;
                }
            } elseif ($fieldType === 'checkbox') {
                if (!$this->filterCheckboxField($product, $fieldId, $searchValue)) {
                    return false;
                }
            } elseif ($fieldType === 'customh') {
                $termIds = $custitem['array'] ?? null;
                if (!$this->filterCustomHierarchicalTaxonomy($product, $fieldId, $termIds)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function filterNumericField($product, $fieldId, $operation, $searchValue) {
        $productValue = null;

        if (isset($product->$fieldId)) {
            $productValue = floatval($product->$fieldId);
        } elseif (isset($product->{'_' . $fieldId})) {
            $productValue = floatval($product->{'_' . $fieldId});
        } else {
            return false;
        }

        if (!is_numeric($searchValue) && $operation !== 'compexp') {
            return true;
        }

        switch ($operation) {
            case 'more':
                return $productValue > floatval($searchValue);
            case 'less':
                return $productValue < floatval($searchValue);
            case 'equal':
                return $productValue == floatval($searchValue);
            case 'moree':
                return $productValue >= floatval($searchValue);
            case 'lesse':
                return $productValue <= floatval($searchValue);
            case 'compexp':
                return $this->evaluateComplexExpression($productValue, $searchValue);
        }

        return true;
    }

    private function filterTextField($product, $fieldId, $operation, $searchValue) {
        $productValue = null;

        if (isset($product->$fieldId)) {
            $productValue = $product->$fieldId;
        } elseif (isset($product->{'_' . $fieldId})) {
            $productValue = $product->{'_' . $fieldId};
        }

        if ($productValue === null) {
            return $operation === 'notcon';
        }

        $productValue = strtolower($productValue);
        $searchValue = strtolower($searchValue);

        switch ($operation) {
            case 'con':
                return strpos($productValue, $searchValue) !== false;
            case 'notcon':
                return strpos($productValue, $searchValue) === false;
            case 'isexactly':
                return $productValue === $searchValue;
            case 'start':
                return strpos($productValue, $searchValue) === 0;
            case 'end':
                return substr($productValue, -strlen($searchValue)) === $searchValue;
        }

        return true;
    }

    private function filterCheckboxField($product, $fieldId, $searchValue) {
        $productValue = null;

        if (isset($product->$fieldId)) {
            $productValue = $product->$fieldId;
        } elseif (isset($product->{'_' . $fieldId})) {
            $productValue = $product->{'_' . $fieldId};
        }

        if ($productValue === null) {
            $productValue = 'no';
        }

        $expectedValue = ($searchValue === 'yes' || $searchValue === true || $searchValue === '1') ? 'yes' : 'no';
        $actualValue = ($productValue === 'yes' || $productValue === true || $productValue === '1') ? 'yes' : 'no';

        return $actualValue === $expectedValue;
    }

    private function filterCustomHierarchicalTaxonomy($product, $taxonomyName, $termIds) {
        if (empty($termIds) || !is_array($termIds)) {
            return true;
        }

        $productToCheck = $product;
        if (isset($product->post_type) && $product->post_type === 'product_variation') {
            if (!empty($product->post_parent) && isset($this->allProductsById[$product->post_parent])) {
                $productToCheck = $this->allProductsById[$product->post_parent];
            } else {
                return false;
            }
        }

        $taxonomyField = $taxonomyName . '_ids';
        $productTermIds = $productToCheck->$taxonomyField ?? [];

        if (empty($productTermIds)) {
            return false;
        }

        if (!is_array($productTermIds)) {
            $productTermIds = [$productTermIds];
        }

        foreach ($termIds as $searchTermId) {
            if (in_array($searchTermId, $productTermIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log discrepancies for debugging
     */
    private function logDiscrepancy($original, $filtered) {
        $this->discrepancies[] = [
            'time' => current_time('mysql'),
            'original_count' => count($original),
            'filtered_count' => count($filtered),
            'filters_applied' => $this->filters
        ];

        if ($this->debug) {
            error_log('WCABE Failsafe Filter Discrepancy: ' . json_encode(end($this->discrepancies)));
        }
    }

    /**
     * Get discrepancies for analysis
     */
    public function getDiscrepancies() {
        return $this->discrepancies;
    }
}
