<?php

namespace SkyVerge\WooCommerce\Local_Pickup_Plus\Helpers;

use DateTime;

class DateTimeComparison
{
	/**
	 * Compares two datetime objects, with an optional grace period.
	 *
	 * @since 2.11.6
	 *
	 * @param DateTime $dateToCheck
	 * @param DateTime $comparisonDate
	 * @param int $gracePeriodInSeconds
	 * @return bool
	 */
	public static function isGreaterThanOrEqualTo(DateTime $dateToCheck, DateTime $comparisonDate, int $gracePeriodInSeconds = 0) : bool
	{
		if ($dateToCheck >= $comparisonDate) {
			return true;
		}

		// if no grace period is allowed, bail now
		if (! $gracePeriodInSeconds) {
			return false;
		}

		$differenceInSeconds = abs($dateToCheck->getTimestamp() - $comparisonDate->getTimestamp());

		return $gracePeriodInSeconds >= $differenceInSeconds;
	}
}
