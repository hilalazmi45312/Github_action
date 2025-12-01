<?php

class OneSyncController
{
    public static function one_sync_dynamic_script()
    {
        if (!is_product()) return;

        global $product;
        if (!$product || !is_a($product, 'WC_Product')) return;

        $cpn = $product->get_sku() ?: '';
        // if (!preg_match('/^MS-MIC-?/i', $cpn)) {
        //     return; // Only run for MS-MIC prefixed products
        // }

        $pn          = preg_replace('/^MS-(?:MIC|ESD)-?/i', '', $cpn);
        $brand_terms = get_the_terms($product->get_id(), 'product_brand');
        $brand_name  = (!empty($brand_terms) && !is_wp_error($brand_terms)) ? $brand_terms[0]->name : '';

        // Early exit for simple products with no valid PN
        if ($product->is_type('simple') && empty($pn)) {
            return;
        }
?>
        <style>
            @media (max-width:680px) {

                #ccs-feature-icons,
                #ccs-logos {
                    display: none !important
                }
            }

            /* Smart description replacement - real hide/show */
            #tab-description {
                position: relative;
                transition: all 0.3s ease;
            }

            #tab-description> :not(#ccs-inline-content) {
                transition: opacity 0.2s ease;
            }

            #tab-description.onews-active> :not(#ccs-inline-content) {
                display: none !important;
            }

            #tab-description #ccs-inline-content {
                display: none;
            }

            #tab-description.onews-active #ccs-inline-content {
                display: block !important;
            }

            #ccs-feature-icons,
            #ccs-logos {
                margin: 20px 0;
            }
        </style>

        <!-- 1WorldSync Containers -->
        <div id="ccs-feature-icons"></div>
        <div id="ccs-logos"></div>
        <div id="ccs-inline-content"></div>

        <script type="text/javascript">
            (function() {
                const SKEY = "61e4fd55";
                const ZONEID = "2f75f80ad4";
                const CCID = "645e91b6-db45-48a4-98a4-89f52732864c";

                function remove1WSScript() {
                    const s = document.querySelector('script[src*="cdn.cs.1worldsync.com/jsc/h1ws.js"]');
                    if (s && s.parentNode) s.parentNode.removeChild(s);
                }

                function clear1WSContainers() {
                    ['ccs-feature-icons', 'ccs-logos', 'ccs-inline-content'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.innerHTML = '';
                    });
                }

                window.__clear1WS = clear1WSContainers;

                window.__load1WSFor = function({
                    cpn,
                    mf,
                    pn
                }) {
                    if (!cpn || !pn) {
                        clear1WSContainers();
                        if (window.__update1WSVisibility) setTimeout(window.__update1WSVisibility, 100);
                        return;
                    }

                    window.ccs_cc_args = window.ccs_cc_args || [];
                    window.ccs_cc_args.length = 0;

                    window.ccs_cc_args.push(["cpn", cpn]);
                    window.ccs_cc_args.push(["mf", mf]);
                    window.ccs_cc_args.push(["pn", pn]);
                    window.ccs_cc_args.push(["upcean", "EAN"]);
                    window.ccs_cc_args.push(["ccid", CCID]);
                    window.ccs_cc_args.push(["lang", "EN"]);
                    window.ccs_cc_args.push(["market", "MY"]);
                    window.ccs_cc_args.push(["_SKey", SKEY]);
                    window.ccs_cc_args.push(["_ZoneId", ZONEID]);

                    clear1WSContainers();
                    remove1WSScript();

                    const sc = document.createElement("script");
                    sc.async = true;
                    sc.src = "https://cdn.cs.1worldsync.com/jsc/h1ws.js";
                    sc.onload = () => {
                        if (window.__update1WSVisibility) setTimeout(window.__update1WSVisibility, 300);
                    };
                    document.head.appendChild(sc);
                };
            })();

            /* Smart Description Toggle (same logic as FlixMedia) */
            document.addEventListener('DOMContentLoaded', function() {
                const inlineDiv = document.getElementById('ccs-inline-content');
                const descTab = document.getElementById('tab-description');
                if (!inlineDiv || !descTab) return;

                if (!descTab.contains(inlineDiv)) {
                    descTab.appendChild(inlineDiv);
                }

                window.__update1WSVisibility = function() {
                    const hasContent = inlineDiv.children.length > 0 || inlineDiv.innerHTML.trim() !== '';
                    descTab.classList.toggle('onews-active', hasContent);
                };

                new MutationObserver(window.__update1WSVisibility)
                    .observe(inlineDiv, {
                        childList: true,
                        subtree: true,
                        characterData: true
                    });

                window.__update1WSVisibility();
            });

            /* Position feature icons & logos after gallery */
            document.addEventListener('DOMContentLoaded', function() {
                const gallery = document.querySelector('.woocommerce-product-gallery');
                const icons = document.getElementById('ccs-feature-icons');
                const logos = document.getElementById('ccs-logos');

                if (gallery && icons && logos) {
                    gallery.after(icons, logos);
                }
            });

            /* Initial Load - only if base product has valid CPN/PN */
            <?php
            $has_base = !empty($cpn) && !empty($pn);
            if ($has_base):
            ?>
                document.addEventListener('DOMContentLoaded', function() {
                    window.__load1WSFor({
                        cpn: <?php echo wp_json_encode($cpn); ?>,
                        mf: <?php echo wp_json_encode($brand_name); ?>,
                        pn: <?php echo wp_json_encode($pn); ?>
                    });
                });
            <?php endif; ?>

            /* Variation Handler */
            jQuery(function($) {
                const $form = $('form.variations_form');
                if (!$form.length) return;

                let lastSku = null;
                const baseCpn = <?php echo wp_json_encode($cpn); ?>;
                const basePn = <?php echo wp_json_encode($pn); ?>;
                const baseMf = <?php echo wp_json_encode($brand_name); ?>;

                $form.on('found_variation', function(e, variation) {
                    const sku = variation.sku || '';
                    if (!sku || !sku.match(/^MS-(?:MIC|ESD)-?/i)) {
                        if (window.__clear1WS) window.__clear1WS();
                        lastSku = null;
                        return;
                    }

                    const pn = sku.replace(/^MS-(?:MIC|ESD)-?/i, '');

                    if (lastSku === sku) return;
                    lastSku = sku;

                    window.__load1WSFor({
                        cpn: sku,
                        mf: baseMf,
                        pn: pn
                    });
                });

                $form.on('reset_data', function() {
                    lastSku = null;
                    if (baseCpn && basePn) {
                        window.__load1WSFor({
                            cpn: baseCpn,
                            mf: baseMf,
                            pn: basePn
                        });
                    } else {
                        if (window.__clear1WS) window.__clear1WS();
                    }
                });
            });
        </script>
<?php
    }

    public static function add_1ws_field()
    {
        /**
         * Add CPN and PN fields for simple + variable products
         */
        add_action('woocommerce_product_options_general_product_data', function () {
            echo '<div class="options_group">';
            woocommerce_wp_text_input([
                'id' => 'pn',
                'label' => __('Part Number (PN)', 'woocommerce'),
                'desc_tip' => true,
                'description' => __('Enter the manufacturer or internal part number.', 'woocommerce'),
            ]);
            echo '</div>';
        });

        /**
         * Save fields for simple products
         */
        add_action('woocommerce_process_product_meta', function ($post_id) {
            $pn  = isset($_POST['pn'])  ? sanitize_text_field($_POST['pn'])  : '';
            update_post_meta($post_id, 'pn', $pn);
        });


        /**
         * Add fields for each variation
         */
        add_action('woocommerce_product_after_variable_attributes', function ($loop, $variation_data, $variation) {
            woocommerce_wp_text_input([
                'id'    => "pn_$loop",
                'name'  => "pn[$loop]",
                'value' => get_post_meta($variation->ID, 'pn', true),
                'label' => __('Part Number (PN)', 'woocommerce'),
                'desc_tip' => true,
                'description' => __('Enter the internal or manufacturer part number.', 'woocommerce'),
            ]);
        }, 30, 3);


        /**
         * Save variation data
         */
        add_action('woocommerce_save_product_variation', function ($variation_id, $i) {
            if (isset($_POST['pn'][$i])) {
                update_post_meta($variation_id, 'pn', sanitize_text_field($_POST['pn'][$i]));
            }
        }, 10, 2);

        /**
         * Make CPN and PN available in the variation data on the frontend
         */
        add_filter('woocommerce_available_variation', function ($data, $product, $variation) {
            $data['pn']  = get_post_meta($variation->get_id(), 'pn', true);
            return $data;
        }, 10, 3);
    }
}
