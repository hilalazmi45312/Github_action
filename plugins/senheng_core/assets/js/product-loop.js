/**
 * Senheng Core Product Display JavaScript
 * Handles color swatch interactions and image switching
 */

(function ($) {
	"use strict";

	class SenhengProductDisplay {
		constructor() {
			this.init();
		}

		init() {
			this.bindEvents();
			this.disableWoodMartVariationHover();
			this.initializeSwatches();
			this.initializeSCoinCentering();
		}

		bindEvents() {
			// Color swatch click handler - updated for new template
			$(document).on(
				"click",
				".swatches-overlay .color-swatch",
				this.handleSwatchClick.bind(this)
			);
			$(document).on(
				"click",
				".swatches-overlay .swatch-item",
				this.handleSwatchClick.bind(this)
			);

			// Product hover effects - only for products with custom variations
			$(document).on(
				"mouseenter",
				".wd-product.has-custom-variations",
				this.handleProductHover.bind(this)
			);
			$(document).on(
				"mouseleave",
				".wd-product.has-custom-variations",
				this.handleProductLeave.bind(this)
			);

			// Prevent event bubbling on swatch clicks
			$(document).on("click", ".swatches-overlay", function (e) {
				e.stopPropagation();
			});
		}

		disableWoodMartVariationHover() {
			// Disable WoodMart's variation hover for products with custom variations
			// Use capture phase to intercept before WoodMart handlers
			document.addEventListener(
				"mouseenter",
				function (e) {
					if (
						e.target &&
						e.target.closest &&
						e.target.closest(".wd-product.has-custom-variations")
					) {
						e.stopImmediatePropagation();
					}
				},
				true
			);

			document.addEventListener(
				"touchstart",
				function (e) {
					if (
						e.target &&
						e.target.closest &&
						e.target.closest(".wd-product.has-custom-variations")
					) {
						e.stopImmediatePropagation();
					}
				},
				true
			);

			document.addEventListener(
				"mousemove",
				function (e) {
					if (e.target.closest(".wd-product.has-custom-variations")) {
						e.stopImmediatePropagation();
					}
				},
				true
			);
		}

		initializeSwatches() {
			// Initialize both old and new swatch containers
			$(".senheng-color-swatches, .color-swatches").each(function () {
				const $swatches = $(this);

				// Clear any pre-selected swatches on page load
				$swatches
					.find(".swatch-item, .color-swatch")
					.removeClass("selected active");
			});
		}

		initializeSCoinCentering() {
			// Add fallback classes for browsers that don't support :has() selector
			// Only target product loop S-Coin badges, not widget badges
			const $scoinTexts = $(
				".senheng-product-card .scoin-badge .scoin-container .scoin-text, .wd-product .scoin-badge .scoin-container .scoin-text"
			);

			$scoinTexts.each(function () {
				const $text = $(this);
				const $number = $text.find(".scoin-number");

				if ($number.length) {
					const val = ($number.text() || "").trim();
					const isSingleDigit = $number.attr("data-single-digit") === "true";
					const isTripleDigit =
						$number.attr("data-triple-digit") === "true" ||
						(val.length >= 3 && val.indexOf(".") === -1);
					const isDecimalTwo =
						$number.attr("data-decimal-two-digit") === "true";
					const isDecimalThree =
						$number.attr("data-decimal-three-digit") === "true";

					$text.removeClass(
						"product-loop-single-digit product-loop-multi-digit product-loop-triple-digit product-loop-decimal-two-digit product-loop-decimal-three-digit"
					);

					if (isDecimalTwo) {
						$text.addClass("product-loop-decimal-two-digit");
					} else if (isDecimalThree) {
						$text.addClass("product-loop-decimal-three-digit");
					} else if (isSingleDigit) {
						$text.addClass("product-loop-single-digit");
					} else if (isTripleDigit) {
						$text.addClass("product-loop-triple-digit");
					} else {
						$text.addClass("product-loop-multi-digit");
					}
				}
			});
		}

		handleSwatchClick(e) {
			e.preventDefault();
			e.stopPropagation();

			const $clickedSwatch = $(e.currentTarget);
			const $swatchContainer = $clickedSwatch.closest(
				".senheng-color-swatches, .color-swatches"
			);
			const $product = $clickedSwatch.closest(
				".wd-product, .senheng-product-card"
			);
			const colorValue =
				$clickedSwatch.data("value") || $clickedSwatch.data("color");
			const imageUrl = $clickedSwatch.data("image");
			const variationId = $clickedSwatch.data("variation-id");

			// Remove selected class from siblings
			$swatchContainer
				.find(".swatch-item, .color-swatch")
				.removeClass("selected active");

			// Add selected class to clicked swatch
			$clickedSwatch.addClass("selected active selecting");

			// Remove selecting class after animation
			setTimeout(() => {
				$clickedSwatch.removeClass("selecting");
			}, 300);

			// Switch product image if available
			this.switchProductImage($product, imageUrl);

			// Update product URL if needed
			this.updateProductUrl($product, colorValue);
		}

		switchProductImage($product, imageUrl) {
			if (!imageUrl) {
				return;
			}

			const $imageWrap = $product.find(
				".wd-product-image-wrap, .product-image-link, .product-wrapper .product-element-top"
			);
			const $image = $imageWrap.find("img").first();

			if ($image.length) {
				const currentSrc = $image.attr("src");

				// Skip if same image
				if (currentSrc === imageUrl) {
					return;
				}

				// Direct image switching for better performance
				$image.attr("src", imageUrl);

				// Clear srcset to prevent browser from using different images
				const currentSrcset = $image.attr("srcset");
				if (currentSrcset) {
					$image.removeAttr("srcset");
				}

				// Also set the src property directly
				$image[0].src = imageUrl;

				// Handle image load error - let WooCommerce handle fallback
				$image.on("error", () => {
					// Remove the error handler to prevent infinite loops
					$image.off("error");
				});
			}
		}

		updateProductUrl($product, colorValue) {
			const $productLink = $product.find('a[href*="product"]').first();

			if ($productLink.length) {
				const currentHref = $productLink.attr("href");
				// Keep only the clean product URL without any parameters
				const cleanHref = currentHref.split("?")[0];

				$productLink.attr("href", cleanHref);
			}
		}

		handleProductHover(e) {
			const $product = $(e.currentTarget);
			const $overlay = $product.find(".swatches-overlay");

			// Ensure overlay is visible on hover (CSS handles this, but ensure no conflicts)
			$overlay.addClass("hover-active");
		}

		handleProductLeave(e) {
			const $product = $(e.currentTarget);
			const $overlay = $product.find(".swatches-overlay");

			// Remove hover state
			setTimeout(() => {
				if (!$product.is(":hover")) {
					$overlay.removeClass("hover-active");
				}
			}, 100);
		}
	}

	// AJAX handler for getting variation images
	function addVariationImageAjaxHandler() {
		// This would be handled by the ProductLoopController in PHP
		// The AJAX endpoint is registered in the controller
	}

	// Initialize when document is ready
	$(document).ready(function () {
		window.senhengProductDisplay = new SenhengProductDisplay();
	});

	// Re-initialize after AJAX content loads (for infinite scroll, etc.)
	$(document).on("yith_wcan_ajax_filtered", function () {
		if (window.senhengProductDisplay) {
			window.senhengProductDisplay.initializeSwatches();
			window.senhengProductDisplay.initializeSCoinCentering();
		}
	});

	// WoodMart specific events
	$(document).on("woodmart-images-loaded", function () {
		if (window.senhengProductDisplay) {
			window.senhengProductDisplay.initializeSwatches();
			window.senhengProductDisplay.initializeSCoinCentering();
		}
	});

	// Re-initialize S-Coin centering when new content is loaded
	$(document).on("DOMNodeInserted", function (e) {
		if (
			$(e.target).find(".scoin-badge").length > 0 &&
			window.senhengProductDisplay
		) {
			window.senhengProductDisplay.initializeSCoinCentering();
		}
	});
})(jQuery);
