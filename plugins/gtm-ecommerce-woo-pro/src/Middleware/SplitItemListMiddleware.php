<?php

namespace GtmEcommerceWooPro\Lib\Middleware;

use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;
use GtmEcommerceWoo\Lib\Util\WcOutputUtil;
use GtmEcommerceWooPro\Lib\Type\EventType;

class SplitItemListMiddleware extends AbstractEventMiddleware {

	const MAX_ITEMS_LIMIT = 25;

	protected $supportedEvents = [
		EventType::VIEW_ITEM_LIST
	];

	private $wcOutputUtil;

	public function __construct( WcOutputUtil $wcOutputUtil) {
		$this->wcOutputUtil = $wcOutputUtil;
	}

	protected function apply( Event $event) {
		if (self::MAX_ITEMS_LIMIT >= count($event->items)) {
			return $event;
		}

		$itemChunks = array_chunk($event->items, self::MAX_ITEMS_LIMIT);
		$events = [];

		foreach ($itemChunks as $itemChunk) {
			$events[] = ( new Event(EventType::VIEW_ITEM_LIST) )
				->setItems($itemChunk);
		}

		foreach (array_slice($events, 0, count($events) -1 ) as $e) {
			$this->wcOutputUtil->dataLayerPush($e);
		}

		$event->items = end($events)->items;

		return $event;
	}
}
