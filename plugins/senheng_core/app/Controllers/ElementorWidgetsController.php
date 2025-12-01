<?php

class ElementorWidgetsController
{
    public static function init()
    {
        // Initialize AJAX handlers early - always register regardless of Elementor status
        require_once(__DIR__ . '/../Widgets/ProductExtrasWidget.php');
        \Senheng\Widgets\ProductExtrasWidget::init_ajax_handlers();

        // Check if Elementor is installed and activated for widget registration
        if (!did_action('elementor/loaded')) {
            return;
        }

        // Add Widget categories
        add_action('elementor/elements/categories_registered', [self::class, 'addWidgetCategories']);

        // Register widgetsfv
        add_action('elementor/widgets/register', [self::class, 'registerWidgets']);

        // Enqueue assets
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);
        add_action('elementor/frontend/after_enqueue_scripts', [self::class, 'enqueueElementorAssets']);
    }

    public static function addWidgetCategories($elements_manager)
    {
        $elements_manager->add_category(
            'senheng',
            [
                'title' => esc_html__('Senheng', 'senheng_core'),
                'icon' => 'fa fa-plug',
            ]
        );
    }

    public static function registerWidgets($widgets_manager)
    {
        // Include widget files
        require_once(__DIR__ . '/../Widgets/ProductPriceWidget.php');
        require_once(__DIR__ . '/../Widgets/ProductVariationWidget.php');
        require_once(__DIR__ . '/../Widgets/SCoinLabelWidget.php');
        require_once(__DIR__ . '/../Widgets/BrandDisplayWidget.php');
        require_once(__DIR__ . '/../Widgets/ProductExtrasWidget.php');
        require_once(__DIR__ . '/../Widgets/TradeInWidget.php');

        // Register widgets
        $widgets_manager->register(new \Senheng\Widgets\ProductPriceWidget());
        $widgets_manager->register(new \Senheng\Widgets\ProductVariationWidget());
        $widgets_manager->register(new \Senheng\Widgets\SCoinLabelWidget());
        $widgets_manager->register(new \Senheng\Widgets\BrandDisplayWidget());
        $widgets_manager->register(new \Senheng\Widgets\ProductExtrasWidget());
        $widgets_manager->register(new \Senheng\Widgets\TradeInWidget());
    }

    public static function enqueueAssets()
    {
        // Always enqueue brand widget assets
        wp_enqueue_style(
            'sh-brand-display-widget-css',
            SENHENG_CORE_ASSETS_URL . 'css/elementor-widget/frontend/brand-display-widget.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'sh-brand-display-widget-js',
            SENHENG_CORE_ASSETS_URL . 'js/elementor-widget/frontend/brand-display-widget.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Always enqueue Trade In Widget assets (needed for Elementor preview)
        wp_enqueue_style(
            'sh-trade-in-widget-css',
            SENHENG_CORE_ASSETS_URL . 'css/elementor-widget/frontend/trade-in-widget.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'sh-trade-in-widget-js',
            SENHENG_CORE_ASSETS_URL . 'js/elementor-widget/frontend/trade-in-widget.js',
            array('jquery'),
            '1.0.0',
            ['in_footer' => true, 'strategy' => 'defer']
        );

        if (!is_product()) {
            return;
        }

        // Enqueue Product Price Widget assets
        wp_enqueue_style(
            'sh-product-price-widget-css',
            SENHENG_CORE_ASSETS_URL . 'css/elementor-widget/frontend/product-price-widget.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'sh-product-price-widget-js',
            SENHENG_CORE_ASSETS_URL . 'js/elementor-widget/frontend/product-price-widget.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Enqueue Product Variation Widget assets
        wp_enqueue_style(
            'sh-product-variation-widget-css',
            SENHENG_CORE_ASSETS_URL . 'css/elementor-widget/frontend/product-variation-widget.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'sh-product-variation-widget-js',
            SENHENG_CORE_ASSETS_URL . 'js/elementor-widget/frontend/product-variation-widget.js',
            array('jquery', 'wc-add-to-cart-variation'),
            '1.0.0',
            ['in_footer' => true, 'strategy' => 'defer']
        );

		// Enqueue S-Coin Label Widget assets
		wp_enqueue_style(
			'sh-s-coin-label-widget-css',
			SENHENG_CORE_ASSETS_URL . 'css/elementor-widget/frontend/s-coin-label-widget.css',
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'sh-s-coin-label-widget-js',
			SENHENG_CORE_ASSETS_URL . 'js/elementor-widget/frontend/s-coin-label-widget.js',
			array('jquery'),
			'1.0.0',
			['in_footer' => true, 'strategy' => 'defer']
		);

		// Enqueue Product Extras Widget assets
		wp_enqueue_style(
			'sh-product-extras-widget-css',
			SENHENG_CORE_ASSETS_URL . 'css/elementor-widget/frontend/product-extras-widget.css',
			array(),
			'1.0.0'
		);

		wp_enqueue_script(
			'sh-product-extras-widget-js',
			SENHENG_CORE_ASSETS_URL . 'js/elementor-widget/frontend/product-extras-widget.js',
			array('jquery'),
			'1.0.0',
			['in_footer' => true, 'strategy' => 'defer']
		);

    }

    public static function enqueueElementorAssets()
    {
        // Enqueue common editor assets
        // wp_enqueue_style(
        //     'sh-elementor-widgets-editor-css',
        //     SENHENG_CORE_ASSETS_URL . 'css/elementor-widget/editor/elementor-editor.css',
        //     array(),
        //     '1.0.0'
        // );

        wp_enqueue_script(
            'sh-elementor-widgets-editor-js',
            SENHENG_CORE_ASSETS_URL . 'js/elementor-widget/editor/elementor-editor.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Enqueue Product Price Widget editor assets
        wp_enqueue_style(
            'sh-product-price-widget-editor-css',
            SENHENG_CORE_ASSETS_URL . 'css/elementor-widget/editor/product-price-widget-editor.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'sh-product-price-widget-editor-js',
            SENHENG_CORE_ASSETS_URL . 'js/elementor-widget/editor/product-price-widget-editor.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Enqueue Product Variation Widget editor assets
        wp_enqueue_style(
            'sh-product-variation-widget-editor-css',
            SENHENG_CORE_ASSETS_URL . 'css/elementor-widget/editor/product-variation-widget-editor.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'sh-product-variation-widget-editor-js',
            SENHENG_CORE_ASSETS_URL . 'js/elementor-widget/editor/product-variation-widget-editor.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Enqueue S-Coin Label Widget editor assets
        wp_enqueue_style(
            'sh-s-coin-label-widget-editor-css',
            SENHENG_CORE_ASSETS_URL . 'css/elementor-widget/editor/s-coin-label-widget-editor.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'sh-s-coin-label-widget-editor-js',
            SENHENG_CORE_ASSETS_URL . 'js/elementor-widget/editor/s-coin-label-widget-editor.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Enqueue Brand Display Widget editor assets
        wp_enqueue_style(
            'sh-brand-display-widget-editor-css',
            SENHENG_CORE_ASSETS_URL . 'css/elementor-widget/editor/brand-display-widget-editor.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'sh-brand-display-widget-editor-js',
            SENHENG_CORE_ASSETS_URL . 'js/elementor-widget/editor/brand-display-widget-editor.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Enqueue Product Extras Widget editor assets
        wp_enqueue_style(
            'sh-product-extras-widget-editor-css',
            SENHENG_CORE_ASSETS_URL . 'css/elementor-widget/editor/product-extras-widget-editor.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'sh-product-extras-widget-editor-js',
            SENHENG_CORE_ASSETS_URL . 'js/elementor-widget/editor/product-extras-widget-editor.js',
            array('jquery'),
            '1.0.0',
            true
        );
    }
}
