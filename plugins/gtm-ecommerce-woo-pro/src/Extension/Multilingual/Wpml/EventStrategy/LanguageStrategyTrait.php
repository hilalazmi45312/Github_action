<?php

namespace GtmEcommerceWooPro\Lib\Extension\Multilingual\Wpml\EventStrategy;

trait LanguageStrategyTrait {

	protected function getCurrentLanguage() {
		/**
		 * Current language.
		 *
		 * @var string|null $lang
		 * @since 1.0.0
		 */
		return apply_filters('wpml_current_language', null);
	}
}
