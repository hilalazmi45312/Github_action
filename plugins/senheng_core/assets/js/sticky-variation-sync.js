/*
 * Senheng Core: Sticky Variation Sync
 *
 * Overrides WoodMart sticky add-to-cart click behavior and keeps the
 * sticky footer UI in sync with Senheng’s Product Variation widget.
 *
 * Behavior:
 * - When a valid variation is selected, sticky button (wd-enabled) triggers
 *   the widget form’s add-to-cart/buy-now.
 * - When not enabled (Select options), clicking the sticky button scrolls
 *   to the `.sh-variation-form` wrapper (preferred) or the widget form.
 * - Updates sticky price, out-of-stock message, and quantity synchronization.
 */
(function ($) {
	"use strict";

	var initialized = false;
	var lastVariation = null;

	function getStickyElements() {
		var $stickyBtn = $(".wd-sticky-btn");
		var $stickyPrice = $stickyBtn.find(".price");
		var $stickyAddBtn = $(".wd-sticky-add-to-cart");
		var $stickyCart = $(".wd-sticky-btn-cart");

		return {
			$stickyBtn: $stickyBtn,
			$stickyPrice: $stickyPrice,
			initialStickyPriceHtml: $stickyPrice.length ? $stickyPrice.html() : "",
			$stickyAddBtn: $stickyAddBtn,
			initialStickyBtnText: $stickyAddBtn.length ? $stickyAddBtn.text() : "",
			$stickyCart: $stickyCart,
		};
	}

	function findWidgetVariationsForm() {
		// Prefer Senheng widget forms, then any variations form not inside summary.
		var $form = $(
			".product-variation-widget .variations_form, .sh-variation-form .variations_form"
		).first();
		if ($form.length) return $form;

		// Fallback: any variations form present on page (if widget replaced default).
		var $any = $("form.variations_form").first();
		return $any.length ? $any : $();
	}

	// Helper to check if a variation is out of stock
	function isOOS(variation) {
		if (!variation) return false;
		if (variation.is_in_stock === false) return true;
		if (variation.is_purchasable === false) return true;
		var m = variation.managing_stock;
		var sq = variation.stock_quantity;
		var mx = variation.max_qty;
		if (m && typeof sq !== "undefined" && sq !== null && parseInt(sq, 10) <= 0)
			return true;
		if (typeof mx === "number" && mx <= 0) return true;
		return false;
	}

	// Helper to check if ALL variations are out of stock
	function allVariationsOutOfStock($form) {
		var raw =
			$form.attr("data-product_variations") || $form.data("product_variations");
		var list = [];
		if (raw) {
			try {
				list = typeof raw === "string" ? JSON.parse(raw) : raw;
			} catch (e) {
				list = [];
			}
		}
		if (!list || !list.length) return false;
		var anyInStock = false;
		for (var i = 0; i < list.length; i++) {
			var v = list[i];
			if (!isOOS(v)) {
				anyInStock = true;
				break;
			}
		}
		return !anyInStock;
	}

	function applyStickyOOS(ctx) {
		var oosText =
			window.woodmart_settings &&
			window.woodmart_settings.texts &&
			window.woodmart_settings.texts.out_of_stock
				? window.woodmart_settings.texts.out_of_stock
				: "Out of stock";

		if (ctx.$stickyAddBtn.length) {
			ctx.$stickyAddBtn
				.parent()
				.find("> .wd-sticky-stock.stock.out-of-stock.wd-style-default")
				.remove();
			ctx.$stickyAddBtn.text(oosText);
			ctx.$stickyAddBtn.removeClass("wd-enabled");
			ctx.$stickyAddBtn.addClass("disabled");
			ctx.$stickyAddBtn.show();
		}
		if (ctx.$stickyCart.length) {
			ctx.$stickyCart
				.find(".wd-sticky-stock.stock.out-of-stock.wd-style-default")
				.remove();
		}
	}

	function updateStickyUIOnFoundVariation(variation, ctx) {
		var $form = findWidgetVariationsForm();

		// Check if all variations are out of stock globally
		if (
			$form.length &&
			($form.data("sh_all_oos") || allVariationsOutOfStock($form))
		) {
			$form.data("sh_all_oos", true);
			applyStickyOOS(ctx);
			return;
		}

		if ($form.length && variation && variation.attributes) {
			var currentAttrs = {};
			$form.find(".variations select").each(function () {
				var n = $(this).attr("name");
				var v = $(this).val();
				if (n) currentAttrs[n] = v;
			});
			var mismatch = false;
			for (var a in currentAttrs) {
				var cur = currentAttrs[a];
				if (cur && cur !== "" && cur !== "0") {
					if (variation.attributes[a] !== cur) {
						mismatch = true;
						break;
					}
				}
			}
			if (mismatch) {
				if (ctx.$stickyCart.length) {
					ctx.$stickyCart
						.find(".wd-sticky-stock.stock.out-of-stock.wd-style-default")
						.remove();
				}
				if (ctx.$stickyAddBtn.length) {
					ctx.$stickyAddBtn
						.parent()
						.find("> .wd-sticky-stock.stock.out-of-stock.wd-style-default")
						.remove();
				}
				ctx.$stickyAddBtn.removeClass("wd-enabled disabled");
				var selectText0 =
					(window.woodmart_settings &&
						window.woodmart_settings.texts &&
						window.woodmart_settings.texts.select_options) ||
					ctx.initialStickyBtnText ||
					"Select options";
				ctx.$stickyAddBtn.text(selectText0).show();
				return;
			}
		}

		// Update sticky price.
		if (ctx.$stickyPrice.length && variation && variation.price_html) {
			ctx.$stickyPrice.html(variation.price_html);
		}

		// Helper: determine trade-in and deposit selection status
		function getTradeDepositStatus($form) {
			var $tradeScope = $form.find('.wd-swatches-product[data-id*="trade_in"]');
			if (!$tradeScope.length) {
				$tradeScope = jQuery('.wd-swatches-product[data-id*="trade_in"]');
			}
			var tradeRequired = $tradeScope.length > 0;
			var tradeSelected =
				$tradeScope.find(".wd-swatch.selected").length > 0 ||
				$form.find('input[name="trade_in"]').length > 0;

			var $depositSwatchScope = $form.find(
				'.wd-swatches-product[data-id*="awcdp_deposit_option"]'
			);
			if (!$depositSwatchScope.length) {
				$depositSwatchScope = jQuery(
					'.wd-swatches-product[data-id*="awcdp_deposit_option"]'
				);
			}
			var depositSwatchSelected =
				$depositSwatchScope.find(".wd-swatch.selected").length > 0 ||
				$form.find('input[name="awcdp_deposit_option"]').length > 0;

			var $depositRadio = $form.find('input[name="deposit_option"]');
			var depositRadioPresent = $depositRadio.length > 0;
			var depositRadioSelected =
				depositRadioPresent && $depositRadio.filter(":checked").length > 0;

			var $tradeWrapper = jQuery(".sh-trade-in-wrapper");
			var depositEnabled =
				$tradeWrapper.length &&
				($tradeWrapper.data("deposit-enabled") === "yes" ||
					$tradeWrapper.attr("data-deposit-enabled") === "yes");
			var depositRequired =
				depositEnabled &&
				($depositSwatchScope.length > 0 || depositRadioPresent);
			var depositSelected = depositSwatchSelected || depositRadioSelected;

			return {
				tradeRequired: tradeRequired,
				tradeSelected: tradeSelected,
				depositRequired: depositRequired,
				depositSelected: depositSelected,
			};
		}

		// Enable sticky add-to-cart with proper text only when purchasable, in stock, and extra requirements met.
		if (ctx.$stickyAddBtn.length) {
			var summaryBtnText =
				$(".summary-inner .single_add_to_cart_button").text() || "";
			var baseEnabled = !!(
				variation &&
				variation.is_purchasable !== false &&
				variation.is_in_stock !== false
			);

			var $form = findWidgetVariationsForm();
			var req = getTradeDepositStatus($form);
			var requirementsOk =
				(!req.tradeRequired || req.tradeSelected) &&
				(!req.depositRequired || req.depositSelected);
			var shouldEnable = baseEnabled && requirementsOk;

			if (shouldEnable) {
				// Remove any out-of-stock message and show the button.
				if (ctx.$stickyCart.length) {
					ctx.$stickyCart
						.find(".wd-sticky-stock.stock.out-of-stock.wd-style-default")
						.remove();
				}
				if (ctx.$stickyAddBtn.length) {
					ctx.$stickyAddBtn
						.parent()
						.find("> .wd-sticky-stock.stock.out-of-stock.wd-style-default")
						.remove();
				}
				ctx.$stickyAddBtn.show();
				ctx.$stickyAddBtn.removeClass("disabled");
				ctx.$stickyAddBtn.text(
					summaryBtnText ||
						(window.woodmart_settings &&
							window.woodmart_settings.texts &&
							window.woodmart_settings.texts.add_to_cart) ||
						"Add to cart"
				);
				ctx.$stickyAddBtn.addClass("wd-enabled");
			} else if (baseEnabled) {
				// Requirements not met: keep button visible but not enabled, prompt to select options.
				if (ctx.$stickyCart.length) {
					ctx.$stickyCart
						.find(".wd-sticky-stock.stock.out-of-stock.wd-style-default")
						.remove();
				}
				if (ctx.$stickyAddBtn.length) {
					ctx.$stickyAddBtn
						.parent()
						.find("> .wd-sticky-stock.stock.out-of-stock.wd-style-default")
						.remove();
				}
				ctx.$stickyAddBtn.removeClass("wd-enabled disabled");
				var selectText =
					(window.woodmart_settings &&
						window.woodmart_settings.texts &&
						window.woodmart_settings.texts.select_options) ||
					ctx.initialStickyBtnText ||
					"Select options";
				ctx.$stickyAddBtn.text(selectText).show();
			} else {
				// Show default WooCommerce out-of-stock label positioned below sticky add-to-cart button
				var oosText =
					window.woodmart_settings &&
					window.woodmart_settings.texts &&
					window.woodmart_settings.texts.out_of_stock
						? window.woodmart_settings.texts.out_of_stock
						: "Out of stock";

				if (ctx.$stickyAddBtn.length) {
					ctx.$stickyAddBtn
						.parent()
						.find("> .wd-sticky-stock.stock.out-of-stock.wd-style-default")
						.remove();
					ctx.$stickyAddBtn.text(oosText);
					ctx.$stickyAddBtn.removeClass("wd-enabled");
					ctx.$stickyAddBtn.addClass("disabled");
					ctx.$stickyAddBtn.show();
				}
			}
		}
	}

	function resetStickyUI(ctx) {
		var $form = findWidgetVariationsForm();
		if (
			$form.length &&
			($form.data("sh_all_oos") || allVariationsOutOfStock($form))
		) {
			$form.data("sh_all_oos", true);
			applyStickyOOS(ctx);
			return;
		}

		if (ctx.$stickyPrice.length && ctx.initialStickyPriceHtml) {
			ctx.$stickyPrice.html(ctx.initialStickyPriceHtml);
		}
		if (ctx.$stickyAddBtn.length) {
			ctx.$stickyAddBtn.text(ctx.initialStickyBtnText);
			ctx.$stickyAddBtn.removeClass("wd-enabled");
			ctx.$stickyAddBtn.removeClass("disabled");
			ctx.$stickyAddBtn.show();
		}
		if (ctx.$stickyAddBtn && ctx.$stickyAddBtn.length) {
			ctx.$stickyAddBtn
				.parent()
				.find("> .wd-sticky-stock.stock.out-of-stock.wd-style-default")
				.remove();
		}
	}

	function syncQuantityBetweenStickyAndWidget() {
		var $widgetForm = findWidgetVariationsForm();
		var $widgetQty = $widgetForm.length ? $widgetForm.find(".qty") : $();
		var $stickyQty = $(".wd-sticky-btn-cart .qty");

		if ($widgetQty.length && $stickyQty.length) {
			$stickyQty
				.off("change.senhengSync")
				.on("change.senhengSync", function () {
					$widgetQty.val($(this).val()).trigger("change");
				});
			$widgetQty
				.off("change.senhengSync")
				.on("change.senhengSync", function () {
					$stickyQty.val($(this).val());
				});
		}
	}

	// Remove compare button from sticky footer and keep it removed on dynamic updates.
	function removeStickyCompareButton() {
		try {
			var $scope = $(".wd-sticky-btn");
			if (!$scope.length) return;

			// Remove known compare wrappers and anchors within sticky footer.
			$scope.find(".wd-compare-btn").remove();
			$scope
				.find(
					"a.product-compare-button.wd-action-btn.wd-style-icon.wd-compare-icon"
				)
				.remove();
			// Clean up any tooltips tied to compare button.
			$scope
				.find(".wd-tooltip.wd-compare-icon, .wd-tooltip-inited.wd-compare-icon")
				.remove();
			// Ensure no delegated clicks linger for the sticky compare button.
			$(document).off("click", ".wd-sticky-btn .wd-compare-btn a");
		} catch (err) {
			// silent
		}
	}

	function init() {
		if (initialized) return;
		initialized = true;

		// Grab sticky elements and initial state snapshot.
		var ctx = getStickyElements();

		// Check initial OOS
		var $widgetForm = findWidgetVariationsForm();
		if ($widgetForm.length) {
			if (
				$widgetForm.data("sh_all_oos") ||
				allVariationsOutOfStock($widgetForm)
			) {
				$widgetForm.data("sh_all_oos", true);
				applyStickyOOS(ctx);
			}
		}

		$(document).on("sh_all_variations_out_of_stock", function () {
			applyStickyOOS(ctx);
		});

		// Listen to variation changes on the widget’s form.
		var $widgetForm = findWidgetVariationsForm();
		if ($widgetForm.length) {
			$widgetForm.on("found_variation.senhengSticky", function (e, variation) {
				lastVariation = variation;
				updateStickyUIOnFoundVariation(variation, ctx);
			});

			$widgetForm.on("reset_data.senhengSticky", function () {
				resetStickyUI(ctx);
				lastVariation = null;
			});

			// Handle widget-synced events for dropdown-driven changes.
			$widgetForm.on(
				"variation_data_synced.senhengSticky",
				function (e, variation) {
					lastVariation = variation;
					updateStickyUIOnFoundVariation(variation, ctx);
				}
			);

			$widgetForm.on("variation_reset_synced.senhengSticky", function () {
				resetStickyUI(ctx);
				lastVariation = null;
			});
		}

		// Re-evaluate sticky UI when trade-in or deposit selections change
		$(document).on(
			"click.senhengStickyReq",
			'.wd-swatches-product[data-id*="trade_in"] .wd-swatch, .wd-swatches-product[data-id*="awcdp_deposit_option"] .wd-swatch',
			function () {
				if (lastVariation) {
					updateStickyUIOnFoundVariation(lastVariation, ctx);
				}
			}
		);
		$(document).on(
			"change.senhengStickyReq",
			'input[name="deposit_option"]',
			function () {
				if (lastVariation) {
					updateStickyUIOnFoundVariation(lastVariation, ctx);
				}
			}
		);

		// Robustly detach WoodMart’s click handlers and install capture-phase interceptors to prevent auto-scroll.
		function detachThemeStickyClickHandlers() {
			try {
				$(".wd-sticky-add-to-cart, .wd-sticky-btn-cart > .wd-buy-now-btn").off(
					"click"
				);
				$(document).off("click", ".wd-sticky-add-to-cart");
				$(document).off("click", ".wd-sticky-btn-cart > .wd-buy-now-btn");
				if (window.woodmartThemeModule && woodmartThemeModule.$body) {
					woodmartThemeModule.$body.off("click", ".wd-sticky-add-to-cart");
					woodmartThemeModule.$body.off(
						"click",
						".wd-sticky-btn-cart > .wd-buy-now-btn"
					);
				}
			} catch (err) {
				// silent
			}
		}

		function installStickyClickInterceptors() {
			// Use capture-phase to block theme handlers reliably, then run our logic.
			document.addEventListener(
				"click",
				function (ev) {
					var target = ev.target;
					if (!target) return;
					var clickable = target.closest(
						".wd-sticky-add-to-cart, .wd-sticky-btn-cart > .wd-buy-now-btn"
					);
					if (!clickable) return;

					var $el = $(clickable);
					var $form = findWidgetVariationsForm();
					if (!$form.length) return;

					var isBuyNowClick = $el.hasClass("wd-buy-now-btn");
					var isStickyEnabled = $el.hasClass("wd-enabled");
					var $addToCartBtn = $form.find(".single_add_to_cart_button");
					var $buyNowBtn = $form.find(".wd-buy-now-btn");

					// Always prevent default and stop propagation to block theme’s auto-scroll.
					ev.preventDefault();
					ev.stopImmediatePropagation();

					if (
						isStickyEnabled &&
						$addToCartBtn.length &&
						!$addToCartBtn.hasClass("disabled")
					) {
						// Loading state until AJAX completes.
						$el
							.addClass("loading")
							.prop("disabled", true)
							.attr("aria-disabled", "true");
						var clearLoading = function () {
							$el
								.removeClass("loading")
								.prop("disabled", false)
								.attr("aria-disabled", "false");
						};
						$(document.body).one(
							"added_to_cart.senhengStickyLoading",
							clearLoading
						);
						$(document.body).one(
							"wc_add_to_cart_error.senhengStickyLoading",
							clearLoading
						);
						$(document.body).one(
							"not_added_to_cart.senhengStickyLoading",
							clearLoading
						);

						if (isBuyNowClick && $buyNowBtn.length) {
							$buyNowBtn.trigger("click");
						} else {
							$addToCartBtn.trigger("click");
						}
					} else {
						// Scroll to Senheng variation form wrapper.
						var $targetWrapper = $form.closest(".sh-variation-form");
						var $scrollTarget = $targetWrapper.length ? $targetWrapper : $form;
						var headerHeight =
							$(".whb-header .whb-row.whb-sticky-row").length > 0
								? $(".whb-header .whb-main-header").outerHeight()
								: 0;
						var $stickyHeader = $(".whb-sticky-header");
						var stickyHeaderHeight = $stickyHeader.length
							? $stickyHeader.outerHeight()
							: headerHeight;
						var offsetCfg =
							(window.woodmart_settings &&
								window.woodmart_settings.sticky_add_to_cart_offset) ||
							0;
						var scrollTo =
							$scrollTarget.offset().top - stickyHeaderHeight - offsetCfg;
						$("html, body").animate({ scrollTop: scrollTo }, 800);
					}
				},
				true
			);
		}

		// Detach theme handlers and install interceptors with retries to cover script load order.
		detachThemeStickyClickHandlers();
		setTimeout(detachThemeStickyClickHandlers, 0);
		installStickyClickInterceptors();

		// Keep quantities in sync to avoid mismatches.
		syncQuantityBetweenStickyAndWidget();
		// Remove compare button immediately.
		removeStickyCompareButton();

		// Re-run on ajax content loads or DOM mutations.
		$(document).ajaxComplete(function () {
			syncQuantityBetweenStickyAndWidget();
			removeStickyCompareButton();
		});

		$(document).on("DOMNodeInserted", function (e) {
			if (
				$(e.target).find(".wd-sticky-btn").length ||
				$(e.target).find(".variations_form").length
			) {
				syncQuantityBetweenStickyAndWidget();
				removeStickyCompareButton();
			}
		});
	}

	$(document).ready(function () {
		// Only run on single product pages.
		if ($("body").hasClass("single-product")) {
			init();
		}
	});
})(jQuery);
