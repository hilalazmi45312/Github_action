<?php

class FlixmediaController
{

    public static function flixmedia_dynamic_script()
    {

        // Elementor editor or preview – do not load Flix
        if (
            (did_action('elementor/loaded') && \Elementor\Plugin::$instance->editor->is_edit_mode()) ||
            isset($_GET['elementor-preview'])
        ) {
            return;
        }

        if (! is_product()) return;

        global $product;
        if (! $product || ! is_a($product, 'WC_Product')) return;

        $product_id = $product->get_id();
        $base_sku   = $product->get_sku();
        $base_ean   = get_post_meta($product_id, '_global_unique_id', true);
        $base_mpn   = get_post_meta($product_id, 'mpn', true);

        $brand_terms = get_the_terms($product_id, 'product_brand');
        $brand_name  = (! empty($brand_terms) && ! is_wp_error($brand_terms)) ? $brand_terms[0]->name : '';

        $distributor_id = '7158'; // Senheng Distributor ID
        $WebChannel = getChannelWeb();

        if ($WebChannel === 'SenQ') {
            $distributor_id = '9248';
        }
        // Only skip simple products with zero identifiers
        if ($product->is_type('simple') && empty($base_ean) && empty($base_mpn)) {
            return;
        }
?>
        <style>
            @media (max-width:680px) {
                /* #flix-minisite {
                    display: none !important
                }

                #flix_hotspots .flix_hs {
                    display: none !important
                } */
            }

            /* .additional_information_tab {
                display: none !important;
            }

            .reviews_tab {
                display: none !important;
            } */

            .woocommerce-product-gallery--with-images {
                position: relative
            }

            #flix-minisite {
                margin-left: -6px;
                display: none !important;
            }

            /* .woocommerce-product-gallery--with-images #flix_hotspots {
                position: absolute;
                top: 50%;
                left: 0;
                transform: translateY(-50%);
                width: 100%;
                display: flex !important;
                justify-content: space-between;
                align-items: center;
                padding-inline: clamp(12px, 6%, 40px);
                pointer-events: none;
                visibility: visible !important;
            } */

            #flix_hotspots .flix_hs {
                position: static !important;
                top: auto !important;
                left: auto !important;
                margin: 0 !important;
                pointer-events: auto
            }

            .share-bubble {
                margin-top: 0 !important
            }

            /* ────── SMART DESCRIPTION SWITCH (real hide/show) ────── */
            #tab-description {
                position: relative;
                transition: all 0.3s ease;
            }

            /* Original content (everything except #flix-inpage) */
            #tab-description> :not(#flix-inpage) {
                transition: opacity 0.2s ease;
            }

            /* When FlixMedia is active → completely hide original content */
            #tab-description.flix-active> :not(#flix-inpage) {
                display: none !important;
            }

            /* FlixMedia container → hidden by default */
            #tab-description #flix-inpage {
                display: none;
            }

            /* Show FlixMedia when active */
            #tab-description.flix-active #flix-inpage {
                display: block !important;
                /* or flex if your theme uses flex */
            }

            /* Hide other tabs when Flix is active */
            #tab-description.flix-active~.additional_information_tab,
            #tab-description.flix-active~.reviews_tab {
                display: none !important;
            }
        </style>

        <div id="flix-minisite"></div>
        <div id="flix-inpage"></div>

        <script type="text/javascript">
            (function() {
                /* ========= CORE LOADER ========= */
                function removeFlixScript() {
                    const s = document.querySelector('script[src="https://media.flixfacts.com/js/loader.js"]');
                    if (s && s.parentNode) s.parentNode.removeChild(s);
                }

                function clearContainers() {
                    ['flix-minisite', 'flix-inpage', 'flix_hotspots'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.innerHTML = '';
                    });
                    const stray = document.querySelector('body > #flix_hotspots');
                    if (stray) stray.remove();
                }
                window.__clearFlix = clearContainers;

                window.__loadFlixFor = function(o) {
                    if (!o.ean && !o.mpn && !o.sku) {
                        clearContainers();
                        if (window.__updateFlixVisibility) setTimeout(window.__updateFlixVisibility, 100);
                        return;
                    }
                    clearContainers();
                    removeFlixScript();
                    const s = document.createElement('script');
                    s.async = true;
                    s.src = 'https://media.flixfacts.com/js/loader.js';
                    s.setAttribute('data-flix-distributor', <?php echo wp_json_encode($distributor_id); ?>);
                    s.setAttribute('data-flix-language', 'b3');
                    s.setAttribute('data-flix-button', 'flix-minisite');
                    s.setAttribute('data-flix-inpage', 'flix-inpage');
                    s.setAttribute('data-flix-fallback-language', 'b3');
                    if (o.brand) s.setAttribute('data-flix-brand', o.brand);
                    if (o.ean) s.setAttribute('data-flix-ean', o.ean);
                    if (o.mpn) s.setAttribute('data-flix-mpn', o.mpn);
                    if (o.sku) s.setAttribute('data-flix-sku', o.sku);
                    s.onload = () => {
                        if (window.__updateFlixVisibility) setTimeout(window.__updateFlixVisibility, 300);
                    };
                    document.head.appendChild(s);
                };
            })();

            /* ========= SMART DESCRIPTION TOGGLE ========= */
            document.addEventListener('DOMContentLoaded', function() {
                const inpage = document.getElementById('flix-inpage');
                const descTab = document.getElementById('tab-description');
                if (!inpage || !descTab) return;

                // place container once
                if (!descTab.contains(inpage)) descTab.appendChild(inpage);

                window.__updateFlixVisibility = function() {
                    const inpage = document.getElementById('flix-inpage');
                    if (!inpage) return;

                    // Check if it contains a fallback script (indicates no match / no real content)
                    const hasFallbackScript = inpage.querySelector('script[src*="media.flix"][src*="service.js"]') !== null ||
                        inpage.querySelector('script[type="text/javascript"][src*="modular/js/minify"]') !== null;

                    // Alternative broader check: any <script type="text/javascript"> inside inpage
                    // const hasFallbackScript = inpage.querySelector('script[type="text/javascript"]') !== null;

                    const hasContent = !hasFallbackScript &&
                        (inpage.children.length > 0 || inpage.innerHTML.trim() !== '');

                    const descTab = document.getElementById('tab-description');
                    if (descTab) {
                        descTab.classList.toggle('flix-active', hasContent);
                    }

                    // Hide/show additional info and reviews tabs
                    const addInfoTab = document.querySelector('.additional_information_tab');
                    const reviewsTab = document.querySelector('.reviews_tab');

                    if (hasContent) {
                        if (addInfoTab) addInfoTab.style.display = 'none';
                        if (reviewsTab) reviewsTab.style.display = 'none';
                    } else {
                        if (addInfoTab) addInfoTab.style.display = '';
                        if (reviewsTab) reviewsTab.style.display = '';
                    }
                };

                new MutationObserver(window.__updateFlixVisibility)
                    .observe(inpage, {
                        childList: true,
                        subtree: true,
                        characterData: true
                    });

                window.__updateFlixVisibility(); // initial check
            });

            /* ========= INITIAL LOAD – ONLY if we actually have identifiers ========= */
            <?php
            // We output the call ONLY when needed – and we do it with proper JSON encoding
            $has_base_data = ! empty($base_ean) || ! empty($base_mpn) || ! empty($base_sku);
            if ($has_base_data) :
            ?>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof window.__loadFlixFor === 'function') {
                        window.__loadFlixFor({
                            ean: <?php echo wp_json_encode($base_ean); ?>,
                            mpn: <?php echo wp_json_encode($base_mpn); ?>,
                            sku: <?php echo wp_json_encode($base_sku); ?>,
                            brand: <?php echo wp_json_encode($brand_name); ?>
                        });
                    }
                });
            <?php
            endif;
            ?>

            /* ========= VARIATION HANDLER ========= */
            jQuery(function($) {
                const parentHasData = <?php echo wp_json_encode(
                                            ! empty($base_ean) || ! empty($base_mpn) || ! empty($base_sku)
                                        ); ?>;
                const $form = $('form.variations_form');
                if (!$form.length) return;

                let lastSku = null;
                const base = {
                    ean: <?php echo wp_json_encode($base_ean); ?>,
                    mpn: <?php echo wp_json_encode($base_mpn); ?>,
                    sku: <?php echo wp_json_encode($base_sku); ?>,
                    brand: <?php echo wp_json_encode($brand_name); ?>
                };

                // $form.on('found_variation', function(e, variation) {
                //     const data = {
                //         ean: variation._global_unique_id || variation.ean || '',
                //         mpn: variation.mpn || '',
                //         sku: variation.sku || '',
                //         brand: base.brand
                //     };
                //     if (!data.ean && !data.mpn && !data.sku) {
                //         if (window.__clearFlix) window.__clearFlix();
                //         lastSku = null;
                //         return;
                //     }
                //     if (lastSku === data.sku) return;
                //     lastSku = data.sku;
                //     window.__loadFlixFor(data);
                // });

                $form.on('found_variation', function(e, variation) {
                    if (parentHasData) {
                        console.log('Parent has data, skipping variation load.');
                        return;
                    }

                    const data = {
                        ean: variation._global_unique_id || variation.ean || '',
                        mpn: variation.mpn || '',
                        sku: variation.sku || '',
                        brand: base.brand
                    };

                    if (!data.ean && !data.mpn && !data.sku) {
                        if (window.__clearFlix) window.__clearFlix();
                        return;
                    }

                    window.__loadFlixFor(data);
                });

                $form.on('reset_data', function() {
                    lastSku = null;
                    if (base.ean || base.mpn || base.sku) {
                        window.__loadFlixFor(base);
                    } else {
                        if (window.__clearFlix) window.__clearFlix();
                    }
                });
            });

            /* ========= POSITION FIXER ========= */
            document.addEventListener('DOMContentLoaded', function() {
                const title = document.querySelector('.product_title.entry-title');
                const gallery = document.querySelector('.woocommerce-product-gallery');
                if (!title && !gallery) return;

                new MutationObserver(function() {
                    const btn = document.querySelector('#flix-minisite > *:first-child');
                    const hs = document.getElementById('flix_hotspots');
                    if (btn && title && title.nextElementSibling !== btn.parentElement) {
                        title.after(btn.parentElement || btn);
                    }
                    if (hs && gallery && !gallery.contains(hs)) {
                        gallery.appendChild(hs);
                    }
                }).observe(document.body, {
                    childList: true,
                    subtree: true
                });
            });
        </script>
<?php
    }

    public static function add_mpn_field()
    {
        /**
         * SIMPLE PRODUCT – Inventory tab, right after SKU
         */
        add_action('woocommerce_product_options_sku', function () {
            // DO NOT open a new options_group here – we're already inside one
            woocommerce_wp_text_input([
                'id'          => 'mpn',
                'label'       => __('MPN', 'woocommerce'),
                'desc_tip'    => true,
                'description' => __('Enter the manufacturer or internal part number.', 'woocommerce'),
                'class'       => 'short', // same width style as SKU
            ]);
        });

        /**
         * Save field for simple products
         */
        add_action('woocommerce_process_product_meta', function ($post_id) {
            $mpn = isset($_POST['mpn']) ? sanitize_text_field($_POST['mpn']) : '';
            update_post_meta($post_id, 'mpn', $mpn);
        });


        /**
         * VARIATIONS – keep your existing code, or hook into a variation inventory action
         */
        add_action('woocommerce_variation_options_pricing', function ($loop, $variation_data, $variation) {
            woocommerce_wp_text_input([
                'id'          => "mpn_$loop",
                'name'        => "mpn[$loop]",
                'value'       => get_post_meta($variation->ID, 'mpn', true),
                'label'       => __('MPN', 'woocommerce'),
                'desc_tip'    => true,
                'description' => __('Enter the internal or manufacturer part number.', 'woocommerce'),
                'wrapper_class' => 'form-row form-row-full',
            ]);
        }, 30, 3);

        add_action('woocommerce_save_product_variation', function ($variation_id, $i) {
            if (isset($_POST['mpn'][$i])) {
                update_post_meta($variation_id, 'mpn', sanitize_text_field($_POST['mpn'][$i]));
            }
        }, 10, 2);

        add_filter('woocommerce_available_variation', function ($data, $product, $variation) {
            $data['mpn'] = get_post_meta($variation->get_id(), 'mpn', true);
            return $data;
        }, 10, 3);
    }
}
