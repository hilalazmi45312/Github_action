<?php

namespace GtmEcommerceWooPro\Lib\Extension\Multilingual\Polylang\EventStrategy;

trait LanguageStrategyTrait {

	protected function getCurrentLanguage() {
		return pll_current_language();
	}
}
