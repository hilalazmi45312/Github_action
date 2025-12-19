document.addEventListener("DOMContentLoaded", function () {
	const usageRestrictionDiv = document.querySelector("#usage_restriction_coupon_data");

	if ( ! usageRestrictionDiv ) {
		return;
	}

	function isWC980OrGreater(version) {
		if (!version) return false;
		const parts = version.split('.').map(Number);
		return (parts[0] > 9 || (parts[0] === 9 && parts[1] > 8));
	}

	// Hide only '.smart-coupons-field' divs inside '#usage_restriction_coupon_data'
	const smartCouponFields = Array.from(usageRestrictionDiv.querySelectorAll(".smart-coupons-field"));
	smartCouponFields.forEach(div => div.style.display = "none");

	// Conditionally add "And" line based on WC version
	const showAnd = isWC980OrGreater(scSmartCouponsData.strings.wc_version)  ? `<div class="hr-section hr-section-coupon_restrictions">And</div>` : '';
	
	// Define restriction container as HTML
	const restrictionContainerHTML = `
		<div id="sc-restriction-container" style="margin-top: 1em; background-color: #f0fff0;">
			<div class="options_group" style="background-color: #f0fff0;">
				${showAnd}
				<p class="form-field">
					<label for="wc-sc-restrictions" style="padding-top: 0.55em;">${scSmartCouponsData.strings.placeholder}</label>
					<select id="wc-sc-restrictions" style="padding: 0.3em; border: 0.1em solid #ccc; border-radius: 0.3em; margin:0 1em 1em 0; min-width: 10.625rem;"></select>
					<span id="wc-sc-add-restriction" class="button" title="Add restriction" style="margin-left: 0.625rem; margin-top: 0.25em;">Add</span>
				</p>
			</div>
			<div id="sc-displayed-restrictions"></div>
		</div>
	`;

	// Insert restriction container inside usage restriction div
	usageRestrictionDiv.insertAdjacentHTML("beforeend", restrictionContainerHTML);

	const select = document.querySelector("#wc-sc-restrictions");
	const displayedRestrictions = document.querySelector("#sc-displayed-restrictions");

	if (window.jQuery && jQuery.fn.selectWoo) {
		jQuery(select).selectWoo();
	}

	// Populate the select dropdown with options
	smartCouponFields.forEach(div => {
		const label = div.querySelector("label");
		if (label) {
			const option = document.createElement("option");
			option.value = label.getAttribute("for");
			option.textContent = label.textContent;
			select.appendChild(option);
		}
	});

	// Check pre-filled fields and show them.
	smartCouponFields.forEach(div => {
		const validInputs = Array.from(
			div.querySelectorAll("input:not([type='radio']), select, textarea")
		);

		const hasNonEmptyValue = validInputs.some(el => {
			if (el.type === "checkbox") return el.checked;
			return el.value && el.value.trim() !== "";
		});

		if (hasNonEmptyValue) {
			const label = div.querySelector("label");
			if (label) {
				showRestriction(label.getAttribute("for"));
			}
		}
	});

	function showRestriction(selectedValue) {
		selectedValue = CSS.escape(selectedValue); // Escape the selected value for CSS.
		const divToShow = smartCouponFields.find(div => div.querySelector(`label[for='${selectedValue}']`));
		if (divToShow && !displayedRestrictions.contains(divToShow)) {
			divToShow.style.display = "block";
			displayedRestrictions.appendChild(divToShow);

			// Disable option in select list
			const optionToDisable = select.querySelector(`option[value='${selectedValue}']`);
			if (optionToDisable) {
				optionToDisable.disabled = true;

				if (window.jQuery && (jQuery.fn.selectWoo || jQuery.fn.select2)) {
					jQuery(select).selectWoo ? jQuery(select).selectWoo() : jQuery(select).select2();
				}
			}
		}
	}

	// Add event listener to "Add" button
	document.querySelector("#wc-sc-add-restriction").addEventListener("click", function () {
		const selectedValue = select.value;
		if (selectedValue) {
			showRestriction(selectedValue);
		}
	});


});
