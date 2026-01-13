(function($) {
    const originalParams = { ...params };
	function getPresets() {
		return $.ajax({
			url: 'https://api.tagconcierge.com/v2/presets?container=server&filter=' + originalParams.filter + '&server=true&uuid=' + originalParams.uuid
		});
	}

	jQuery(function($) {
		var presetTemplateHtml = $("#gtm-ecommerce-woo-server-preset-tmpl").html();
		var $presetsGrid = $("#gtm-ecommerce-woo-server-presets-grid");
		getPresets()
			.then(function (res) {
				$("#gtm-ecommerce-woo-server-presets-loader").css("display", "none");
				var locked = false;
				res.map(function (preset) {
					var $preset = $(presetTemplateHtml);
					$(".name", $preset).text(preset.name);
					$(".description", $preset).text(preset.description);
					if (preset.locked !== true) {
						$(".download", $preset).attr("data-id", preset.id);
					} else {
						locked = true;
						$(".download", $preset).attr("disabled", "disabled");
						$(".download", $preset).attr("title", "Requires PRO version, upgrade below.");
					}
					$(".events-count", $preset).text((preset.events || []).length);
					$(".events-list", $preset).pointer({ content: "<p>- " + (preset.events || []).join("<br />- ") + "</p>" });

					$(".version", $preset).text(preset.version || "N/A");
					// $(".changelog", $preset).pointer({ content: "<p>- " + (preset.changelog || []).join("<br />- ") + "</p>" });
					if (true === preset.latest) {
						$preset.css({'border-color': '#06932d', 'border-width': '3px'});
						$('.name', $preset).append('<span style="float: right;color: #06932d;">recently added <span class="dashicons dashicons-admin-post"></span></span>');
					}
					$presetsGrid.append($preset);
				});
				// if something is locked then we show the button
				if (locked === true) {
					$("#gtm-ecommerce-woo-server-presets-upgrade").css("display", "block");
				}
			})
			.then(function() {
				$(".events-list", $presetsGrid).click(function(ev) {
					$(ev.currentTarget).pointer("open");
				});
				$(".download", $presetsGrid).click(function(ev) {
					ev.preventDefault();
					var preset = $(ev.currentTarget).attr("data-id");
					window.location = ajaxurl + '?action=gtm_ecommerce_woo_post_preset&preset=' + encodeURIComponent(preset);
				});
			})
	});

	jQuery(function($) {
		$("[data-id].download").click(function(ev) {
			ev.preventDefault();
			var preset = $(ev.currentTarget).attr("data-id");
			window.location = ajaxurl + '?action=gtm_ecommerce_woo_post_preset&preset=' + encodeURIComponent(preset);
		});
	});

})(jQuery);
