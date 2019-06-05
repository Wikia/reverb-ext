<?php
/**
 * Reverb
 * Notification
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Reverb\Notification;

use MWException;

class NotificationBundle extends ArrayObject {
	/**
	 * Main Constructor
	 *
	 * @param array   $notifications Array of Reverb\Notification\Notification objects.
	 * @param integer $flags         ArrayObject::STD_PROP_LIST | ArrayObject::ARRAY_AS_PROPS
	 * @param string  $iterator      Iterator class to use.
	 *
	 * @return void
	 */
	public function __construct(array $notifications = [], int $flags = 0, string $iterator = "ArrayIterator") {
		foreach ($notifications as $notification) {
			if (!($notification instanceof Notification)) {
				throw new MWException('Invalid item was attempted to be added to bundle.');
			}
		}
		parent::__construct($notifications, $flags, $iterator);
	}
}
