// Initialize Insider
window.InsiderObject = {
    isInitialized: false,
    userDataReady: false,
    initializationPromise: null,
    queue: [],
    oldQuantities: {},

    async init() {
        window.InsiderQueue = window.InsiderQueue || [];

        // Load Insider script
        const script = document.createElement('script');
        script.async = true;
        script.src = `//${insiderData.partnerName}.api.useinsider.com/ins.js?id=${insiderData.partnerId}`;

        // Create promise for script loading
        const scriptPromise = new Promise((resolve, reject) => {
            script.onload = resolve;
            script.onerror = reject;
        });

        document.head.appendChild(script);
        await scriptPromise;

        // Mark as initialized without waiting for user data
        this.isInitialized = true;

        // If user is logged in, initialize user data first
        if (insiderData.hasLoginCookie || insiderData.hasRegisterCookie) {
            if (insiderData.userData) {
                this.initializationPromise = this.initializeUser(insiderData.userData);
                await this.initializationPromise;

                // Process login/register events after user data is ready
                await this.handleLoginEvent();
                await this.handleRegisterEvent();
            }
        } else {
            // If no user-specific events, mark user data as ready to allow other events
            this.userDataReady = true;
        }

        // Initialize basic events that don't need user data
        this.bindEvents();
        await this.processQueuedEvents();
    },

    initializeUser(userData) {
        return new Promise((resolve) => {
            console.log('Starting user initialization...');

            window.InsiderQueue = window.InsiderQueue || [];
            window.InsiderQueue.push({
                type: 'user',
                value: {
                    email: insiderData.userData.email,
                    gdpr_optin: true,
                    name: insiderData.userData.name,
                    phone_number: insiderData.userData.phone_number,
                    username: '',
                    uuid: insiderData.userData.user_id,
                    age: '',
                    birthday: '',
                    gender: '',
                    surname: '',
                    email_optin: '',
                    sms_optin: '',
                    language: '',
                    city: '',
                    country: '',
                    whatsapp_optin: '',
                }
            });

            window.InsiderQueue.push({
                type: 'init',
            });

            // Wait a bit to ensure user data is processed
            setTimeout(() => {
                this.userDataReady = true;
                document.dispatchEvent(new Event('insiderUserReady'));
                console.log('User initialization completed');
                resolve();
            }, 5000); // Adjust timeout as needed : 5000ms (5s)
        });
    },

    bindEvents() {

        //initialize user for foolproof
        this.backupSendUserData();

        // Page type
        this.handlePageType();

        // Banner clicks
        this.initializeBannerTracking();

        // Add landing page tracking
        // this.initializeLandingPageTracking();

        // Add to cart event
        this.bindAjaxCartEvent();

        // Product share event
        this.bindProductShareEvent();

        // Wishlist events
        this.bindWishlistEvents();

        // Product Care event
        this.bindProductCareEvent();

        // Product view event
        this.bindProductViewEventVariation();

        // Installment click event
        this.bindInstallmentClick();

        //Payment method selection event
        this.bindPaymentInitiatedClick();

        //Handle source
        this.handleSource();
    },

    // Capture initial quantities from the page load
    captureInitialQuantities() {
        jQuery('input[type="number"].qty').each((_, input) => {
            const cartKey = jQuery(input).attr('name').match(/cart\[(.*?)\]\[qty\]/)[1]; // Extract cart key
            const initialQty = parseInt(jQuery(input).val(), 10) || 0; // Get the initial quantity
            this.oldQuantities[cartKey] = initialQty; // Store it
        });
    },

    async processQueuedEvents() {
        if (this.isInitialized && this.userDataReady) {
            while (this.queue.length > 0) {
                const event = this.queue.shift();
                await this.pushEvent(event.name, event.parameters);
                console.log('Processing queued event:', event);
            }
        }
    },

    async pushEvent(eventName, parameters) {
        // If user is logged in, wait for user initialization
        if (insiderData.userData) {
            if (!this.userDataReady) {
                console.log(`Queuing ${eventName} until user initialization completes`);
                this.queue.push({ name: eventName, parameters });
                return;
            }
        }

        window.InsiderQueue.push({
            type: 'custom_event',
            value: [{
                event_name: eventName,
                event_parameters: parameters
            }]
        });

        console.log(`📡 ${eventName} event pushed to InsiderQueue`, parameters);
    },

    /**
     * Handle login event if user data is ready
     */
    async handleLoginEvent() {
        if (!this.userDataReady) {
            console.log('Waiting for user data initialization...');
            await this.initializationPromise;
        }

        if (insiderData.hasLoginCookie && insiderData.userData) {
            this.pushEvent('login', {
                email: insiderData.userData.email,
                name: insiderData.userData.name,
                phoneNo: insiderData.userData.phone_number,
                user_id: insiderData.userData.user_id,
                p1_number_latest: insiderData.userData.p1_number_latest,
            });

            // Delete the cookie
            document.cookie = 'insider_login_event=; path=/; max-age=0';
            console.log('Login event processed for:', insiderData.userData.name);
        }
    },

    /**
     * Handle registration event if user data is ready
     */
    async handleRegisterEvent() {
        if (!this.userDataReady) {
            console.log('Waiting for user data initialization...');
            await this.initializationPromise;
        }

        if (insiderData.hasRegisterCookie && insiderData.userData) {
            this.pushEvent('registration_completed', {
                // channel: insiderData.channel,
                email: insiderData.userData.email,
                name: insiderData.userData.name,
                phoneNo: insiderData.userData.phone_number,
                user_id: insiderData.userData.user_id,
            });

            // Delete the cookie
            document.cookie = 'insider_register_event=; path=/; max-age=0';
            console.log('Registration event processed for:', insiderData.userData.name);

            // Directly push login event here instead of using handleLoginEvent
            await this.pushEvent('login', {
                email: insiderData.userData.email,
                name: insiderData.userData.name,
                phoneNo: insiderData.userData.phone_number,
                user_id: insiderData.userData.user_id,
                p1_number_latest: insiderData.userData.p1_number_latest,
            });
            console.log('Auto-login event processed after registration for:', insiderData.userData.name);
        }
    },

    /**
     * Track page type and category view
     */
    handlePageType() {
        // Push page type
        if (insiderData.pageType) {
            window.InsiderQueue.push({
                type: insiderData.pageType,
                ...(insiderData.categoryName && { value: [insiderData.categoryName] })
            });

            // Track category view if applicable
            if (insiderData.pageType === 'category' && insiderData.categoryName) {
                this.pushEvent('category_viewed', { custom: { category_name: insiderData.categoryName, channel: insiderData.channel } });
                // console.log('Insider category view:', insiderData.categoryName);
            }

            window.InsiderQueue.push({
                type: 'init',
            });

            console.log('Insider page view:', insiderData.pageType);
        }
    },

    /**
     * Handle search event
     */
    handleSearch(event) {
        const searchInput = event.target.querySelector("input[name='s']");
        const keyword = searchInput ? searchInput.value.trim() : "";
        const searched_url = event.target.action + '?' + new URLSearchParams(new FormData(event.target)).toString();

        this.pushEvent('searched', {
            channel: insiderData.channel,
            keyword: keyword,
            search_url: searched_url,
        });
        console.log('📡 Search pushed:', {
            channel: insiderData.channel,
            keyword: keyword,
            search_url: searched_url,
        });
    },

    /**
     * Handle banner click events
     */
    initializeBannerTracking() {

        jQuery(document).on('click', '[data-banner-id]', function (e) {
            e.preventDefault();

            var $banner = jQuery(this);
            var banner_name = $banner.attr('data-banner-id');
            var banner_url = '';
            if ($banner.is('img')) {
                banner_url = $banner.attr('src');
            } else {
                var $img = $banner.find('img').first();
                if ($img.length) {
                    banner_url = $img.attr('src');
                }
            }
            //set a cookie to indicate banner is clicked
            setCookie('insider_banner_clicked', banner_name, 1);

            function setCookie(name, value, days) {
                var expires = "";
                if (days) {
                    var d = new Date();
                    d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
                    expires = "; expires=" + d.toUTCString();
                }
                var secure = location.protocol === 'https:' ? '; Secure' : '';
                document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/; SameSite=Lax" + secure;
            }

            window.InsiderQueue.push({
                type: 'custom_event',
                value: [{
                    event_name: 'banner_clicked',
                    event_parameters: {
                        banner_name: banner_name,
                        banner_url: banner_url,
                        channel: (window.insiderData && insiderData.channel) ? insiderData.channel : ''
                    }
                }]
            });
            console.log('📡 Banner clicked event pushed:', {
                event_name: 'banner_clicked',
                event_parameters: {
                    banner_name: banner_name,
                    banner_url: banner_url,
                    channel: (window.insiderData && insiderData.channel) ? insiderData.channel : ''
                }
            });
        });
    },

    //Initialize All Cart Event AJAX
    bindAjaxCartEvent() {

        //This one for action insider Cart Page
        jQuery(document).on('change', 'input[type="number"].qty', (event) => {
            const cartKey = jQuery(event.target).attr('name').match(/cart\[(.*?)\]\[qty\]/)[1];
            const newQty = parseInt(jQuery(event.target).val(), 10) || 0;
            const oldQty = this.oldQuantities[cartKey] || 0;

            // Calculate the quantity change (whether it's an add or remove event)
            const qtyChange = newQty - oldQty;

            // Update the stored old quantity
            this.oldQuantities[cartKey] = newQty;

            console.log("CartKey:", cartKey, "oldQty:", oldQty, "newQty:", newQty, "qtyChange:", qtyChange);

            // Only trigger if there's a change in quantity
            if (qtyChange !== 0) {
                // If qtyChange is positive, it's an add-to-cart event
                if (qtyChange > 0) {
                    jQuery.post(ajaxurl, {
                        action: "get_wc_products",
                        cart_key: cartKey
                    }, (response) => {
                        if (response.success) {
                            this.handleAddToCartEvent(response.data, qtyChange); // Pass qtyChange dynamically
                        }
                    });
                }
                // If qtyChange is negative, it's a remove-from-cart event
                else if (qtyChange < 0) {
                    jQuery.post(ajaxurl, {
                        action: "get_wc_products",
                        cart_key: cartKey
                    }, (response) => {
                        if (response.success) {
                            this.handleRemoveFromCartEvent(response.data, Math.abs(qtyChange)); // Always pass positive qtyChange for removal
                        }
                    });
                }
            }
        });

        // Capture initial quantities once the page is ready
        jQuery(document).ready(() => {
            this.captureInitialQuantities();
        });

        jQuery(document).ajaxComplete((_, __, settings) => {
            // Add to Cart event
            if ((settings.url.indexOf("wc-ajax=add_to_cart") !== -1) || (typeof settings.data === "string" && settings.data.includes("action=woodmart_ajax_add_to_cart"))) {
                try {
                    const isStringData = typeof settings.data === 'string';
                    const formDataArray = isStringData ? settings.data.split('&') : [];
                    const getFromArray = function (key) {
                        var item = formDataArray.find(function (x) { return x.includes(key); });
                        if (!item) return null;
                        var parts = item.split('=');
                        return parts.length > 1 ? parts[1] : null;
                    };

                    let product_id = null;
                    let quantity = null;
                    let variation_id = null;

                    if (settings.url.indexOf("wc-ajax=add_to_cart") !== -1) {
                        if (isStringData) {
                            product_id = getFromArray('product_id') || getFromArray('add-to-cart');
                            quantity = parseInt(getFromArray('quantity'), 10) || null;
                            variation_id = getFromArray('variation_id') || null;
                        } else if (settings.data && typeof settings.data === 'object') {
                            if (typeof settings.data.get === 'function') {
                                product_id = settings.data.get('product_id') || settings.data.get('add-to-cart') || null;
                                var q1 = settings.data.get('quantity');
                                quantity = q1 ? parseInt(q1, 10) : null;
                                variation_id = settings.data.get('variation_id') || null;
                            } else {
                                product_id = settings.data['product_id'] || settings.data['add-to-cart'] || null;
                                var q2 = settings.data['quantity'];
                                quantity = q2 ? parseInt(q2, 10) : null;
                                variation_id = settings.data['variation_id'] || null;
                            }
                        }
                    }

                    if (!product_id && isStringData && settings.data.includes("action=woodmart_ajax_add_to_cart")) {
                        product_id = getFromArray('add-to-cart');
                        quantity = parseInt(getFromArray('quantity'), 10) || null;
                        variation_id = getFromArray('variation_id') || null;
                    }

                    if (product_id) {
                        jQuery.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'get_wc_products',
                                product_id: product_id,
                                variation_id: variation_id,
                            },
                            success: (response) => {
                                if (response.success && response.data) {
                                    this.handleAddToCartEvent(response.data, quantity);
                                }
                            },
                            error: (error) => {
                                console.error('Error fetching product data:', error);
                            }
                        });
                    }
                } catch (error) {
                    console.error("Error processing Add to Cart event:", error);
                }
            }

            // Remove from Cart event
            if ((settings.url.indexOf("wc-ajax=remove_from_cart") !== -1) || (settings.url.indexOf("remove_item=") !== -1 && settings.url.indexOf("cart") !== -1)) {
                try {
                    let removeItemKey = null;

                    // Handle wc-ajax=remove_from_cart
                    if (settings.url.indexOf("wc-ajax=remove_from_cart") !== -1) {
                        let formDataArray = settings.data.split('&');
                        removeItemKey = formDataArray.find(x => x.includes('cart_item_key'))?.split('=')[1];
                    }

                    // Handle ?remove_item=xxx in URL
                    if (!removeItemKey && settings.url.indexOf("remove_item=") !== -1) {
                        let urlParams = new URLSearchParams(settings.url.split('?')[1]);
                        removeItemKey = urlParams.get('remove_item');
                    }

                    if (removeItemKey) {
                        jQuery.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'get_wc_products',
                                remove_item: removeItemKey,
                            },
                            // xhrFields: { withCredentials: true },
                            success: (response) => {
                                if (response.success && response.data) {
                                    this.handleRemoveFromCartEvent(response.data);
                                }
                            },
                            error: (error) => {
                                console.error('Error fetching product data:', error);
                            }
                        });
                    }
                } catch (error) {
                    console.error("Error processing Remove from Cart event:", error);
                }
            }

            // Update Order Review event
            // if (settings.url.indexOf("wc-ajax=update_order_review") !== -1) {
            //     try {
            //         jQuery.ajax({
            //             url: ajaxurl,
            //             type: 'POST',
            //             data: {
            //                 action: 'get_wc_cart_data',
            //             },
            //             success: (response) => {
            //                 if (response.success && response.data) {
            //                     this.handleUpdateOrderReviewEvent(response.data.products);
            //                 }
            //             },
            //             error: (error) => {
            //                 console.error('Error fetching cart data:', error);
            //             }
            //         });
            //     } catch (error) {
            //         console.error("Error processing Update Order Review event:", error);
            //     }
            // }

            // // Update Shipping Method event
            // if (settings.url.indexOf("wc-ajax=update_shipping_method") !== -1) {
            //     try {
            //         jQuery.ajax({
            //             url: ajaxurl,
            //             type: 'POST',
            //             data: {
            //                 action: 'get_wc_cart_data',
            //             },
            //             success: (response) => {
            //                 if (response.success && response.data) {
            //                     this.handleUpdateShippingMethodEvent(response.data);
            //                 }
            //             },
            //             error: (error) => {
            //                 console.error('Error fetching cart data:', error);
            //             }
            //         });
            //     } catch (error) {
            //         console.error("Error processing Update Shipping Method event:", error);
            //     }
            // }

            // Search woodmart ajax search
            if (settings.url.indexOf("action=woodmart_ajax_search") !== -1) {
                try {
                    let formDataArray = settings.data ? settings.data.split('&') : [];
                    let keyword = '';
                    const urlParams = new URLSearchParams(settings.url.split('?')[1]);
                    keyword = urlParams.get('query') || '';
                    let searched_url = settings.url + (keyword ? '&s=' + keyword : '');
                    this.pushEvent('searched', {
                        channel: insiderData.channel,
                        keyword: keyword,
                        search_url: searched_url,
                        src: ''
                    });
                    console.log('📡 Search pushed:', {
                        channel: insiderData.channel,
                        keyword: keyword,
                        search_url: searched_url,
                        src: ''
                    });
                } catch (error) {
                    console.error("Error processing Search event:", error);
                }
            }

            //Coupon
            if (settings.url.indexOf("wc-ajax=apply_coupon") !== -1) {
                try {
                    // Extract coupon_code from formDataArray
                    let coupon_code = '';
                    if (settings.data) {
                        const formDataArray = settings.data.split('&');
                        const couponParam = formDataArray.find(x => x.startsWith('coupon_code='));
                        if (couponParam) {
                            coupon_code = decodeURIComponent(couponParam.split('=')[1]);
                        }
                    }

                    jQuery.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'check_coupon_validity',
                            coupon_code: coupon_code
                        },
                        success: (response) => {
                            if (response.success && response.data) {
                                console.log('Coupon applied:', response.data);
                                this.handleApplyCouponEvent(response.data);
                            }
                        },
                        error: (error) => {
                            console.error('Error fetching cart data:', error);
                        }
                    });
                } catch (error) {
                    console.error("Error processing Apply Coupon event:", error);
                }
            }

        });
    },

    /**
     * Handle Add to Cart event
     */
    handleAddToCartEvent(productData, quantity) {
        const productAddedCart = {
            type: 'add_to_cart',
            value: {
                id: productData.sku_id,
                name: productData.product_name,
                taxonomy: productData.category_name,
                unit_price: productData.product_price,
                unit_sale_price: productData.product_sale_price,
                url: productData.product_url,
                product_image_url: productData.product_image_url,
                quantity: quantity,
                custom: {
                    channel: productData.channel,
                    store_name: productData.store_name,
                    variant: productData.variant,
                    s_coin_cashback: productData.s_coin_cashback,
                    source: productData.source,
                }
            }
        };

        window.InsiderQueue = window.InsiderQueue || [];
        window.InsiderQueue.push({
            type: 'currency',
            value: productData.currency
        });
        window.InsiderQueue.push(productAddedCart);
        // window.InsiderQueue.push({
        //     type: 'init'
        // });

        console.log('📡 Add to Cart pushed:', productAddedCart);

        // Inventory event
        // jQuery(function($) {
        //     var tryPopup = function() {
        //         var instance = $('body').data('plugin_multiInventory');
        //         console.log('instance:', instance);
        //         if (instance && typeof instance.popupOpen === 'function') {
        //             instance.popupOpen();
        //             console.log('multiInventory plugin initialized');
        //         } else {
        //             console.warn('multiInventory plugin not yet initialized');
        //         }
        //     };

        //     // Wait a bit or hook to a custom event
        //     setTimeout(tryPopup, 1000);
        // });

    },

    /**
     * Handle Remove from Cart event
     */
    handleRemoveFromCartEvent(productData, quantity=0) {
        const productRemovedCart = {
            type: 'remove_from_cart',
            value: {
                id: productData.sku_id,
                name: productData.product_name,
                taxonomy: productData.category_name,
                unit_price: productData.product_price,
                unit_sale_price: productData.product_sale_price,
                url: productData.product_url,
                product_image_url: productData.product_image_url,
                quantity: quantity != 0 ? quantity : productData.quantity,
                custom: {
                    channel: productData.channel,
                    store_name: productData.store_name,
                    variant: productData.variant,
                    s_coin_cashback: productData.s_coin_cashback,
                    source: productData.source,
                }
            }
        };

        window.InsiderQueue = window.InsiderQueue || [];
        window.InsiderQueue.push({
            type: 'currency',
            value: productData.currency
        });
        window.InsiderQueue.push(productRemovedCart);
        window.InsiderQueue.push({
            type: 'init'
        });

        console.log('📡 Remove from Cart pushed:', productRemovedCart);
    },

    /**
     * Handle Update Order Review
     */
    handleUpdateOrderReviewEvent(productData) {
        // const checkoutData = productData;
        // checkoutData.forEach((product, index) => {
        //     setTimeout(() => {
        //         let eventData = {
        //             type: 'custom_event',
        //             value: [{
        //                 event_name: 'checkout_completed',
        //                 event_parameters: checkoutData[index]
        //             }]
        //         };
        //         window.InsiderQueue.push(eventData);
        //         console.log('📡 Update Order Review pushed:', checkoutData);
        //     }, index === 0 ? 5000 : index * 3000); // First delay 5s, others follow index * 3000
        // });
        // // window.InsiderQueue = window.InsiderQueue || [];
        // window.InsiderQueue.push({
        //     type: 'currency',
        //     value: "MYR"
        // });
        // window.InsiderQueue.push(checkoutData);
        // window.InsiderQueue.push({
        //     type: 'init'
        // });
    },

    /**
     * Handle Update Shipping Method
     */
    handleUpdateShippingMethodEvent(productData) {
        let cartDataFull = productData;
        let cartData = {
            type: 'cart',
            value: {
                total: cartDataFull.total,
                items: cartDataFull.products,
            }
        };
        // window.InsiderQueue = window.InsiderQueue || [];
        window.InsiderQueue.push({
            type: 'currency',
            value: "MYR"
        });
        window.InsiderQueue.push(cartData);
        window.InsiderQueue.push({
            type: 'init'
        });

        console.log('📡 Update Shipping Method pushed:', cartData);
    },

    /**
     * Bind Product Share Event
     */
    bindProductShareEvent() {
        jQuery(document).on('click', '.sh-share-icon', (e) => {
            e.preventDefault();

            const productContainer = jQuery('.custom-cart-actions');
            let product_id =
                productContainer.find("button[name='add-to-cart']").attr('value') ||
                jQuery('.wd-wishlist-btn a').data('product-id');
            //check if name='variation_id' exists use it as product_id
            const variation_id = productContainer.find("input[name='variation_id']").val();
            if (variation_id) {
                product_id = variation_id;
            }

            if (!product_id) return;

            if (product_id) {
                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_wc_products',
                        product_id: product_id,
                    },
                    success: (response) => {
                        if (response.success && response.data) {
                            this.handleProductSharedEvent(response.data);
                        }
                    },
                    error: (error) => {
                        console.error('Error fetching product data for share:', error);
                    }
                });
            }
        });
    },

    /**
     * Handle Product Shared Event
     */
    handleProductSharedEvent(productData, channel_shared) {
        const productSharedData = {
            type: 'custom_event',
            value: [{
                event_name: 'product_shared',
                event_parameters: {
                    channel: productData.channel,
                    pid: productData.sku_id,
                    name: productData.product_name,
                    product_image_url: productData.product_image_url,
                    s_coin_cashback: productData.s_coin_cashback,
                    store_name: productData.store_name,
                    taxonomy: productData.category_name,
                    unit_price: productData.product_price,
                    url: productData.product_url,
                    unit_sale_price: productData.product_sale_price,
                    variant: productData.variant,

                }
            }]
        };

        // window.InsiderQueue = window.InsiderQueue || [];
        window.InsiderQueue.push({
            type: 'currency',
            value: productData.currency
        });
        window.InsiderQueue.push(productSharedData);
        window.InsiderQueue.push({
            type: 'init'
        });

        console.log('📡 Product Shared pushed:', productSharedData);
    },

    bindWishlistEvents() {
        // Add to Wishlist
        jQuery(document).on('click', '.wd-wishlist-btn a', (e) => {
            const product_id = jQuery(e.currentTarget).attr('data-product-id');
            if (!product_id) {
                console.log("No product ID found for wishlist");
                return;
            }

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_wc_products',
                    product_id: product_id
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.handleWishlistAddEvent(response.data);
                    } else {
                        console.log("Error:", response.data?.message);
                    }
                },
                error: () => {
                    console.log("AJAX request failed while adding to wishlist");
                }
            });
        });

        // Remove from Wishlist
        jQuery(document).on('click', '.wd-wishlist-remove', (e) => {
            const product_id = jQuery(e.currentTarget).data('product-id');
            if (!product_id) {
                console.log("No product ID found for wishlist removal");
                return;
            }

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_wc_products',
                    product_id: product_id
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.handleWishlistRemoveEvent(response.data);
                    } else {
                        console.log("Error:", response.data?.message);
                    }
                },
                error: () => {
                    console.log("AJAX request failed while removing from wishlist");
                }
            });
        });
    },

    /**
     * Handle Wishlist Add Event
     */
    handleWishlistAddEvent(productData) {
        const productAddedWishList = {
            type: 'custom_event',
            value: [{
                event_name: 'product_added_to_wishlist',
                event_parameters: {
                    channel: productData.channel,
                    pid: productData.sku_id,
                    name: productData.product_name,
                    product_image_url: productData.product_image_url,
                    s_coin_cashback: productData.s_coin_cashback,
                    source: productData.source,
                    store_name: productData.store_name,
                    taxonomy: productData.category_name,
                    unit_price: productData.product_price,
                    url: productData.product_url,
                    unit_sale_price: productData.product_sale_price,
                    variant: productData.variant,
                }
            }]
        };

        // window.InsiderQueue = window.InsiderQueue || [];
        window.InsiderQueue.push({ type: 'currency', value: productData.currency });
        window.InsiderQueue.push(productAddedWishList);
        window.InsiderQueue.push({ type: 'init' });

        console.log('📡 Product Added to Wishlist:', productAddedWishList);
    },

    /**
     * Handle Wishlist Remove Event
     */
    handleWishlistRemoveEvent(productData) {
        const productRemovedWishList = {
            type: 'custom_event',
            value: [{
                event_name: 'product_removed_from_wishlist',
                event_parameters: {
                    channel: productData.channel,
                    pid: productData.sku_id,
                    name: productData.product_name,
                    product_image_url: productData.product_image_url,
                    s_coin_cashback: productData.s_coin_cashback,
                    source: productData.source,
                    store_name: productData.store_name,
                    taxonomy: productData.category_name,
                    unit_price: productData.product_price,
                    url: productData.product_url,
                    unit_sale_price: productData.product_sale_price,
                    variant: productData.variant
                }
            }]
        };

        // window.InsiderQueue = window.InsiderQueue || [];
        window.InsiderQueue.push({ type: 'currency', value: productData.currency });
        window.InsiderQueue.push(productRemovedWishList);
        window.InsiderQueue.push({ type: 'init' });

        console.log('📡 Product Removed from Wishlist:', productRemovedWishList);
    },

    /**
     * Bind Product Care Event
     */
    bindProductCareEvent() {
        const $ = jQuery;
        const productElem = $('.single-product-page');
        const productIdAttr = productElem.attr('id');
        if (!productIdAttr) return;
        const productId = productIdAttr.replace('product-', '');
        if (!productId) return;

        const state = {
            channel: '',
            product_id_ins: '',
            sku_id: '',
            product_name: '',
            rating: '',
            total_reviews: ''
        };

        // Fetch product details
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: { action: 'get_wc_products', product_id: productId },
            success: (res) => {
                if (res?.success && res.data) {
                    Object.assign(state, {
                        product_name: res.data.product_name,
                        product_id_ins: res.data.sku_id,
                        sku_id: res.data.product_id,
                        rating: res.data.average_rating,
                        total_reviews: res.data.total_reviews,
                        channel: res.data.channel
                    });
                } else {
                    console.log('Error:', res?.data?.message);
                }
            },
            error: () => {
                console.log('AJAX request failed while fetching product details');
            },
            complete: setupHandlers // always run after request
        });

        function setupHandlers() {
            const normalize = (txt) =>
                (txt || '')
                    .replace(/\(\d+\)/, '')
                    .trim()
                    .toLowerCase()
                    .replace(/\s+/g, '_');

            const $tabs = $('.wd-nav-tabs .wd-tabs-title');
            const firstTab = normalize($tabs.first().text());

            const send = (details_clicked) => {
                // console.log('Product Care Tab Clicked:', details_clicked);
                window.InsiderQueue.push({
                    type: 'custom_event',
                    value: [{
                        event_name: 'product_details_clicked',
                        event_parameters: {
                            "channel": state.channel,
                            "details_clicked": details_clicked,
                            "product_id": state.product_id_ins,
                            "sku_id": state.sku_id,
                            "product_name": state.product_name,
                            "rating": state.rating,
                            "review": state.total_reviews
                        }
                    }]
                });
                console.log('📡 Product Care Event Fired:', { details_clicked });
            }

            function update() {
                const currentTab = normalize($('.wd-nav-tabs .active .wd-tabs-title').text());
                $('.woocommerce-Tabs-panel').off('click');

                if (!/^(additional_information|specs|reviews|description)$/.test(currentTab)) return;

                if (currentTab !== firstTab) {
                    send(currentTab);
                } else {
                    $(`#tab-${currentTab}`).on('click', () => send(currentTab));
                }
            }

            $tabs.on('click', () => setTimeout(update, 100));
            update();
        }
    },

    bindProductViewEventVariation() {
        jQuery(($) => {
            const $form = $('form.variations_form');
            if (!$form.length) return;

            let manuallyTriggered = false;
            let lastVariationId = null;

            // --- Helper: find variation by color ---
            function findVariationByColor(colorSlug) {
                const variations = $form.data('product_variations') || [];
                return variations.find(v => {
                    const attrs = v.attributes || {};
                    return attrs['attribute_pa_color'] === colorSlug;
                });
            }

            // --- Helper: find variation by SKU ---
            function findVariationBySKU() {
                const variations = $form.data('product_variations') || [];
                return variations.find(v => v.sku && v.sku.length > 0);
            }

            // --- Auto-select default variation ---
            function autoSelectDefaultVariation() {
                let variations = $form.data('product_variations');
                if (!variations || !variations.length) {
                    console.log("No variations loaded.");
                    return;
                }

                // Find variation with SKU first, otherwise fallback to first variation
                let defaultVar = findVariationBySKU() || variations[0];

                console.log("Default variation selected:", defaultVar);

                // Auto-set its attributes
                $.each(defaultVar.attributes, function (name, value) {
                    console.log("Setting", name, "=", value);
                    const field = $('[name="' + name + '"]');
                    if (field.length) {
                        field.val(value).trigger('change');
                    } else {
                        console.log("Attribute field not found:", name);
                    }
                });
            }

            // --- Trigger Woo image swap manually when only color changes ---
            function updateImagesForColor(colorSlug) {
                const v = findVariationByColor(colorSlug);
                if (v && v.image && v.image.src) {
                    manuallyTriggered = true;
                    $form.trigger('found_variation', [v]);
                    // Reset immediately so the next event (Woo's own) isn't skipped
                    setTimeout(() => { manuallyTriggered = false; }, 0);
                }
            }

            // --- Listen to color changes ---
            $form.on('change', 'select[name^="attribute_pa_color"]', function () {
                const val = $(this).val();
                if (val) updateImagesForColor(val);
            });

            $form.on('click change', '.variations [data-attribute_name="attribute_pa_color"] input', function () {
                const val = $(this).val();
                if (val) updateImagesForColor(val);
            });

            // --- Handle found_variation once (AJAX logic) ---
            $form.on('found_variation', (event, variation) => {
                if (manuallyTriggered) return; // Skip the manual image-only trigger

                const variation_id = variation.variation_id;
                if (variation_id === lastVariationId) return; // Skip duplicates

                lastVariationId = variation_id;

                jQuery.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_wc_products',
                        product_id: variation_id,
                    },
                    success: (response) => {
                        if (response.success && response.data) {
                            this.handleProductViewEvent(response.data); // Handle the product view event
                        } else {
                            console.log("Error:", response.data?.message);
                        }
                    },
                    error: () => {
                        console.log("AJAX request failed while handling product view.");
                    }
                });
            });

            // --- Reset variation tracking ---
            $form.on('reset_data', () => {
                lastVariationId = null;
            });

            // Auto-select the default variation when the page loads
            autoSelectDefaultVariation();
        });
    },

    /**
     * Handle Product View Event
     */
    handleProductViewEvent(productData) {
        // Helper to read cookies
        function getCookieValue(name) {
            const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
            return match ? decodeURIComponent(match[2]) : '';
        }

        // Parse utm_info from cookie
        const utmRaw = getCookieValue('utm_info');
        const utmParts = utmRaw ? utmRaw.split(' - ') : [];

        const source_1 = utmParts[0] || '';
        const source_2 = utmParts[1] || '';
        const source_3 = utmParts[2] || '';

        const productViewed = {
            type: 'product',
            value: {
                id: productData.sku_id,
                name: productData.product_name,
                taxonomy: productData.category_name,
                unit_price: parseFloat(productData.product_price),
                unit_sale_price: parseFloat(productData.product_sale_price),
                url: productData.product_url,
                product_image_url: productData.product_image_url,
                stock: parseInt(productData.product_stock),
                custom: {
                    sku_id: productData.sku_id,
                    channel: productData.channel,
                    store_name: productData.store_name,
                    variant: productData.variant,
                    coin_cashback: productData.coin_cashback,
                    source_1: source_1.trim(),
                    source_2: source_2.trim(),
                    source_3: source_3.trim()
                }
            }
        };

        window.InsiderQueue = window.InsiderQueue || [];
        window.InsiderQueue.push(productViewed);
        window.InsiderQueue.push({ type: 'currency', value: productData.currency || 'MYR' });
        window.InsiderQueue.push({ type: 'init' });

        console.log('📡 Product Viewed pushed:', productViewed);

        // Unset source_product_clicked cookie after use
        if (document.cookie.indexOf('source_product_clicked') !== -1) {
            document.cookie = 'source_product_clicked=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        }
    },

    /**Bind Installment Click */
    bindInstallmentClick() {
        jQuery(document).on('click', '#installment-modal .bank-header', (e) => {
            e.preventDefault();

            const header = jQuery(e.currentTarget);
            const bankSection = header.closest('.bank-section');
            const bankName = header.find('.bank-name').text().trim();

            // check if already opened (using a data-flag)
            if (!bankSection.data('opened')) {
                console.log('Bank opened:', bankName);
                this.pushEvent('instalment_clicked', {
                    channel: insiderData.channel,
                    instalment_name: bankName
                });

                // mark as opened
                bankSection.data('opened', true);
            } else {
                console.log('Bank closed:', bankName);
                // clear flag so next open will trigger again
                bankSection.data('opened', false);
            }
        });
    },

    /**
     * Handle Voucher Viewed Event
     */
    handleApplyCouponEvent(couponData) {
        // Map pathname to a simple page name for source
        let sourcePage = '';
        const path = window.location.pathname || '';
        if (path.includes('/my-account')) {
            sourcePage = 'my-account-page';
        } else if (path.includes('/cart')) {
            sourcePage = 'cart-page';
        } else if (path.includes('/product')) {
            sourcePage = 'product-page';
        } else if (path.includes('/checkout')) {
            sourcePage = 'checkout-page';
        } else {
            sourcePage = path;
        }
        const couponApplied = {
            type: 'custom_event',
            value: [{
                event_name: 'voucher_viewed',
                event_parameters: {
                    voucher_name: couponData.name,
                    voucher_validity: couponData.expiry_date,
                    voucher_type: couponData.discount_type,
                    source: sourcePage
                }
            }]
        };

        window.InsiderQueue = window.InsiderQueue || [];
        window.InsiderQueue.push(couponApplied);

        console.log('📡 Coupon Applied pushed:', couponApplied);
    },

    /**Bind Payment Initiated Click */
    bindPaymentInitiatedClick() {
        // Trigger on first load for selected payment method
        let methodName = '';
        var checked = jQuery('input[name="ipay88_payment_type"]:checked');
        if (checked.length) {
            methodName = jQuery.trim(jQuery('label[for="' + checked.attr('id') + '"]').text());
            // console.log('Payment method selected (initial):', methodName);
        }

        let paymentTriggered = false;

        jQuery(document).on('change', 'input[name="ipay88_payment_type"]', (e) => {
            if (paymentTriggered) return; // Prevent multiple triggers

            const checked = jQuery('input[name="ipay88_payment_type"]:checked');
            if (!checked.length) return;

            const methodName = jQuery.trim(jQuery('label[for="' + checked.attr('id') + '"]').text());
            console.log('Payment method selected:', methodName);

            paymentTriggered = true; // Lock further triggers

            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_wc_cart_data',
                },
                success: (response) => {
                    if (response.success && response.data) {
                        this.handlePaymentInitiatedEvent(methodName, response.data);
                        paymentTriggered = false; // Reset for future changes if needed
                    } else {
                        console.log('Error:', response.data?.message);
                    }
                },
                error: () => {
                    console.log('AJAX request failed while removing from wishlist');
                }
            });
        });

    },

    /**Handle Payment Initiated Event */
    handlePaymentInitiatedEvent(methodName, productData) {
        for (let i = 0; i < productData.products.length; i++) {
            const product = productData.products[i];
            const delay = i === 0 ? 5000 : i * 3000;

            setTimeout(() => {
                window.InsiderQueue.push({
                    type: 'custom_event',
                    value: [{
                        event_name: 'payment_initiated',
                        event_parameters: {
                            payment_method: methodName,
                            pid: product.custom.sku_id,
                            name: product.name,
                            product_price: product.unit_sale_price,
                            quantity: product.quantity,
                            store_name: product.custom.store_name,
                            variant: product.custom.variant,
                            channel: product.custom.channel,
                        }
                    }]
                });
            }, delay);
        }
    },

    handleSource() {
        (function ($) {
            function setCookie(name, value, days) {
                var expires = "";
                if (days) {
                    var d = new Date();
                    d.setTime(d.getTime() + (days * 24 * 60 * 60 * 1000));
                    expires = "; expires=" + d.toUTCString();
                }
                var secure = location.protocol === 'https:' ? '; Secure' : '';
                document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/; SameSite=Lax" + secure;
            }

            function getCookie(name) {
                var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
                return match ? decodeURIComponent(match[2]) : null;
            }

            function detectGlobalPrevPage() {
                var $body = $('body');
                if ($body.hasClass('home')) {
                    // return 'homepage sections';
                } else if (getCookie('insider_banner_clicked')) {
                    return 'Banner : ' + getCookie('insider_banner_clicked');
                } else if ($body.hasClass('search')) {
                    return 'Search Engine';
                } else if ($body.hasClass('tax-product_cat') || $body.hasClass('category')) {
                    return 'Category Page';
                } else if ($body.hasClass('tax-product_brand')) {
                    return 'Store';
                } else {
                    return '';
                }
            }
            $(function () {
                var value = detectGlobalPrevPage();
                if (value) {
                    setCookie('globalPrevPage', value, 7);
                }
            });
        })(jQuery);
    },

    backupSendUserData() {
        if (!insiderData.userData || !insiderData.userData.email) {
            return;
        }
        window.InsiderQueue.push({
            type: 'user',
            value: {
                email: insiderData.userData.email,
                gdpr_optin: true,
                name: insiderData.userData.name,
                phone_number: insiderData.userData.phone_number,
                username: '',
                uuid: insiderData.userData.user_id,
                age: '',
                birthday: '',
                gender: '',
                surname: '',
                email_optin: '',
                sms_optin: '',
                language: '',
                city: '',
                country: '',
                whatsapp_optin: '',
            }
        });
    },
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.InsiderObject.init().catch(error => {
        console.error('Failed to initialize Insider:', error);
    });
    jQuery(".backButton").click(function () {
        console.log('test');
        window.history.back();
    });
});