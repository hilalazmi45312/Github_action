<?php

namespace GtmEcommerceWooPro\Lib\Middleware;

use GtmEcommerceWoo\Lib\GaEcommerceEntity\Event;

abstract class AbstractEventMiddleware {

	protected $supportedEvents = [];

	abstract protected function apply( Event $event);

	public function __invoke( Event $event) {
		if (false === in_array($event->name, $this->supportedEvents, true)) {
			return $event;
		}

		return $this->apply($event);
	}
}
