<?php

namespace GtmEcommerceWooPro\Lib\Extension;

use GtmEcommerceWooPro\Lib\Util\MpClientUtil;
use GtmEcommerceWooPro\Lib\Util\WcOutputUtil;
use GtmEcommerceWooPro\Lib\Util\WcTransformerUtil;

abstract class AbstractExtension {

	const SUPPORTED_PLUGIN_NAME = '';

	const SUPPORTED_PLUGIN_VERSION = '';

	/**
	 * WcTransformerUtil
	 *
	 * @var WcTransformerUtil
	 */
	protected $wcTransformerUtil;

	/**
	 * WcOutputUtil
	 *
	 * @var WcOutputUtil
	 */
	protected $wcOutputUtil;

	/**
	 * MpClientUtil
	 *
	 * @var MpClientUtil
	 */
	protected $mpClientUtil;

	public function __construct ( WcTransformerUtil $wcTransformerUtil, WcOutputUtil $wcOutputUtil, MpClientUtil $mpClientUtil) {
		$this->wcTransformerUtil = $wcTransformerUtil;
		$this->wcOutputUtil = $wcOutputUtil;
		$this->mpClientUtil = $mpClientUtil;
	}

	public static function supports( $pluginName, $pluginVersion) {
		if (static::SUPPORTED_PLUGIN_NAME !== $pluginName) {
			return false;
		}

		if (0 > version_compare(static::SUPPORTED_PLUGIN_VERSION, $pluginVersion)) {
			return false;
		}

		return true;
	}

	public function getEventStrategies() {
		return [];
	}

	public function init() {
		return;
	}
}
