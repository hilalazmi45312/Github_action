/**
 * Criteria - Pickup Location.
 * 
 * @since 4.0.0
 * @global wp
 */
jQuery(function ($) {
	'use strict';

	/**
	 * Restriction rules.
	 * 
	 * @since 4.0.0
	 * @type object
	 */
	var PickupLocationCriteria = {
		init: function () {
			$(document).on('dey-init-pickup-location-restriction-rules', this.init_rules).trigger('dey-init-pickup-location-restriction-rules');
			// Add a rules group.
			$('.dey-pickup-location-options-wrapper').on('click', '.dey-add-restriction-rules-group', this.add_rule_group);
			// Remove the rules group.
			$('.dey-pickup-location-options-wrapper').on('click', '.dey-remove-restriction-rules-group', this.remove_rule_group);
			// Add a rule.
			$('.dey-pickup-location-options-wrapper').on('click', '.dey-add-restriction-rule', this.add_rule);
			// Remove the rule.
			$('.dey-pickup-location-options-wrapper').on('click', '.dey-remove-restriction-rule', this.remove_rule);
			// Toggle rules wrapper.
			$('.dey-pickup-location-options-wrapper').on('click', '.dey-restriction-rules-toggle', this.toggle_rules_wrapper);
			// Toggle the rule type.
			$('.dey-pickup-location-options-wrapper').on('change', '.dey-restriction-rule-type', this.toggle_rule_type);
			// Toggle the rule product type.
			$('.dey-pickup-location-options-wrapper').on('change', '.dey-restriction-rule-product-type', this.toggle_rule_product_type);
			// Toggle the rule order type.
			$('.dey-pickup-location-options-wrapper').on('change', '.dey-restriction-rule-order-type', this.toggle_rule_order_type);
		},

		/**
		 * Init rules.
		 * 
		 * @since 4.0.0
		 */
		init_rules: function () {
			// Pickup location filter rule types.
			$('.dey-pickup-location-options-wrapper').find('.dey-restriction-rule-type').each(function () {
				PickupLocationCriteria.handle_rule_type(this);
			});
		},

		/**
		 * Toggle rules wrapper.
		 * 
		 * @since 4.0.0 
		 * @param {event} event 
		 */
		toggle_rules_wrapper: function (event) {
			event.preventDefault();
			var rules_wrapper = $($(event.currentTarget)).closest('.dey-restriction-rules-wrapper');

			rules_wrapper.find('.dey-restriction-rules-content').toggle();
		},

		/**
		 * Toggle rule type.
		 * 
		 * @since 4.0.0
		 * @param {event} event 
		 */
		toggle_rule_type: function (event) {
			event.preventDefault();
			PickupLocationCriteria.handle_rule_type($(event.currentTarget));
		},

		/**
		 * Toggle rule product type.
		 * 
		 * @since 4.0.0
		 * @param {event} event 
		 */
		toggle_rule_product_type: function (event) {
			event.preventDefault();
			PickupLocationCriteria.handles_rule_product_type($(event.currentTarget));
		},

		/**
		 * Toggle rule order type.
		 * 
		 * @since 4.0.0
		 * @param {event} event 
		 */
		toggle_rule_order_type: function (event) {
			event.preventDefault();
			PickupLocationCriteria.handle_rule_order_type($(event.currentTarget));
		},

		/**
		 * Handles the rule type.
		 * 
		 * @since 4.0.0
		 * @param {object} $this 
		 */
		handle_rule_type: function ($this) {
			var wrapper = $($this).closest('.dey-restriction-rule-wrapper');

			wrapper.find('.dey-restriction-rule-field').hide();
			wrapper.find('.dey-restriction-rule-type-field').hide();
			switch ($($this).val()) {
				case '1': // Product.
					wrapper.find('.dey-restriction-rule-product-type-field').show();
					PickupLocationCriteria.handles_rule_product_type(wrapper.find('.dey-restriction-rule-product-type'));
					break;

				case '2': // User.
					wrapper.find('.dey-restriction-rule-user-type-field').show();
					wrapper.find('.dey-restriction-rule-country-field').show();
					break;

				case '3': // Order.
					wrapper.find('.dey-restriction-rule-order-type-field').show();
					PickupLocationCriteria.handle_rule_order_type(wrapper.find('.dey-restriction-rule-order-type'));
					break;

				case '4': // Shipping.
					wrapper.find('.dey-restriction-rule-shipping-type-field').show();
					wrapper.find('.dey-restriction-rule-shipping-method-field').show();
					break;
			}
		},

		/**
		 * Handles the rule product type.
		 * 
		 * @since 4.0.0
		 * @param {object} $this 
		 */
		handles_rule_product_type: function ($this) {
			var wrapper = $($this).closest('.dey-restriction-rule-wrapper');
			wrapper.find('.dey-restriction-rule-field').hide();

			switch ($($this).val()) {
				case '8':
				case '7':
					wrapper.find('.dey-restriction-rule-tags-field').show();
					break;

				case '6':
				case '5':
					wrapper.find('.dey-restriction-rule-product-types-field').show();
					break;

				case '4':
				case '3':
					wrapper.find('.dey-restriction-rule-categories-field').show();
					break;

				case '2':
				case '1':
					wrapper.find('.dey-restriction-rule-products-field').show();
					break;
			}
		},

		/**
		 * Handles the restriction rule order type.
		 * 
		 * @since 4.0.0
		 * @param {object} $this 
		 */
		handle_rule_order_type: function ( $this ) {
			var wrapper = $($this).closest('.dey-restriction-rule-wrapper');
			wrapper.find('.dey-restriction-rule-order-field').hide();

			switch ($($this).val()) {
				case '1':
				case '2':
				case '3':
				case '4':
					wrapper.find('.dey-restriction-rule-order-price-field').show();
					break;

				case '5':
				case '6':
					wrapper.find('.dey-restriction-rule-order-count-field').show();
					break;
			}
		},

		/**
		 * Add new rule group.
		 * 
		 * @since 4.0.0
		 * @param {event} event 
		 */
		add_rule_group: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
				group_template = wp.template('dey-restriction-rules-group'),
				or_template = wp.template('dey-restriction-rule-or-label'),
				wrapper = $($this).closest('.dey-restriction-rules-group-wrapper'),
				last_rules_wrapper = wrapper.find('.dey-restriction-rules-wrapper:last'),
				group_id = last_rules_wrapper.data('group_id') + 1 || 0;

			wrapper.find('.dey-restriction-rules-group-content').append(group_template({ group_id: group_id, rule_id: 0 }));

			if (group_id >= 1) {
				last_rules_wrapper.after(or_template());
			}

			$(document).trigger('dey-init-pickup-location-restriction-rules');
			$(document.body).trigger('dey-enhanced-init');
		},

		/**
		 * Remove rule group.
		 * 
		 * @since 4.0.0
		 * @param {event} event 
		 */
		remove_rule_group: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
				rules_wrapper = $($this).closest('.dey-restriction-rules-wrapper');

			if (rules_wrapper.next('p.dey-restriction-rules-or-label').length) {
				rules_wrapper.next('p.dey-restriction-rules-or-label').remove();
			} else {
				rules_wrapper.prev('p.dey-restriction-rules-or-label').remove();
			}

			rules_wrapper.remove();
		},

		/**
		 * Add new rule.
		 * 
		 * @since 4.0.0
		 * @param {event} event 
		 */
		add_rule: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
				rule_template = wp.template('dey-restriction-rule'),
				and_template = wp.template('dey-restriction-rule-and-label'),
				wrapper = $($this).closest('.dey-restriction-rules-wrapper'),
				last_rule_wrapper = wrapper.find('.dey-restriction-rule-wrapper:last'),
				rule_id = last_rule_wrapper.data('rule_id') + 1 || 0;

			wrapper.find('.dey-restriction-rules-content').append(rule_template({ group_id: wrapper.data('group_id'), rule_id: rule_id }));
			if (rule_id >= 1) {
				last_rule_wrapper.after(and_template());
			}

			$(document.body).trigger('dey-enhanced-init');
			$(document).trigger('dey-init-pickup-location-restriction-rules');

		},

		/**
		 * Remove the rule.
		 * 
		 * @since 4.0.0
		 * @param {event} event 
		 */
		remove_rule: function (event) {
			event.preventDefault();
			var $this = $(event.currentTarget),
				rules_wrapper = $($this).closest('.dey-restriction-rules-wrapper'),
				rule_wrapper = $($this).closest('.dey-restriction-rule-wrapper');

			if (1 === rules_wrapper.find('.dey-restriction-rule-wrapper').length) {
				if (rules_wrapper.next('p.dey-restriction-rules-or-label').length) {
					rules_wrapper.next('p.dey-restriction-rules-or-label').remove();
				} else {
					rules_wrapper.prev('p.dey-restriction-rules-or-label').remove();
				}

				rules_wrapper.remove();
			} else {
				if (rule_wrapper.next('p.dey-restriction-rules-and-label').length) {
					rule_wrapper.next('p.dey-restriction-rules-and-label').remove();
				} else {
					rule_wrapper.prev('p.dey-restriction-rules-and-label').remove();
				}

				rule_wrapper.remove();
			}
		},
	};

	/**
	 * Init the restriction rules.
	 */
	PickupLocationCriteria.init();
});
