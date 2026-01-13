<?php

namespace GtmEcommerceWooPro\Lib\EventStrategy;

use GtmEcommerceWoo\Lib\EventStrategy\AbstractEventStrategy;
use GtmEcommerceWooPro\Lib\Util\MpClientUtil;
use GtmEcommerceWooPro\Lib\Util\WcOutputUtil;

abstract class AbstractServerEventStrategy extends AbstractEventStrategy {

	/**
	 * WcOutputUtil
	 *
	 * @var WcOutputUtil
	 */
	protected $wcOutput;

	/**
	 * MpClientUtil
	 *
	 * @var MpClientUtil
	 */
	protected $mpClient;

	public function __construct( $wcTransformer, $wcOutput, $mpClient ) {
		$this->mpClient = $mpClient;
		parent::__construct($wcTransformer, $wcOutput);
	}
}
