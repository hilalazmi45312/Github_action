document.addEventListener("DOMContentLoaded", function () {
	const usageRestrictionDiv = document.querySelector("#usage_restriction_coupon_data");

	if ( ! usageRestrictionDiv ) {
		return;
	}

	// Hide only '.smart-coupons-field' divs inside '#usage_restriction_coupon_data'
	const smartCouponFields = Array.from(usageRestrictionDiv.querySelectorAll(".smart-coupons-field"));
	smartCouponFields.forEach(div => div.style.display = "none");

	// Define restriction container as HTML
	const restrictionContainerHTML = `
		<div id="sc-restriction-container" style="margin-top: 1em; padding: 1em; border-top: 0.1em solid #ccc; background-color: #f9f9f9;">
			<div class="options_group" style="background-color: #f0fff0;">
				<p class="form-field">
					<label for="wc-sc-restrictions" style="padding-top: 0.55em;">Smart Coupon Restrictions</label>
					<select id="wc-sc-restrictions" style="padding: 0.3em; border: 0.1em solid #ccc; border-radius: 0.3em; margin:0 1em 1em 0; min-width: 10.625rem;"></select>
					<span id="wc-sc-add-restriction" class="button" title="Add restriction" style="margin-left: 0.625rem">Add</span>
				</p>
			</div>
			<div id="sc-displayed-restrictions" style="margin-top: 1em;"></div>
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

	function showRestriction(selectedValue) {
		selectedValue = CSS.escape(selectedValue); // Escape the selected value for CSS
		const divToShow = smartCouponFields.find(div => div.querySelector(`label[for='${selectedValue}']`));
		if (divToShow && !displayedRestrictions.contains(divToShow)) {
			divToShow.style.display = "block";
			displayedRestrictions.appendChild(divToShow);

			// Disable option in select list
			select.querySelector(`option[value='${selectedValue}']`).disabled = true;
		}
	}

	// Add event listener to "Add" button
	document.querySelector("#wc-sc-add-restriction").addEventListener("click", function () {
		const selectedValue = select.value;
		if (selectedValue) {
			showRestriction(selectedValue);
		}
	});

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


});
