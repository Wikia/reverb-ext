<?php
/**
 * Reverb
 * NotificationBroadcast
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Reverb\Notification;

use ArrayObject;
use CentralIdLookup;
use Exception;
use Hydrawiki\Reverb\Client\V1\Exceptions\ApiResponseInvalid;
use Hydrawiki\Reverb\Client\V1\Resources\NotificationBroadcast as NotificationBroadcastResource;
use MediaWiki\MediaWikiServices;
use MWException;
use Reverb\Identifier\Identifier;
use User;

class NotificationBroadcast {
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

	/**
	 * Function Documentation
	 *
	 * @return null
	 */
	public static function newSingle(string $type, User $agent, User $target): null {
		$client = MediaWikiServices::getInstance()->getService('ReverbApiClient');

		$lookup = CentralIdLookup::factory();
		$agentGlobalId = $lookup->centralIdFromLocalUser($agent);
		$targetGlobalId = $lookup->centralIdFromLocalUser($target);

		if (!$agentGlobalId || !$targetGlobalId) {
			
		}

		$user1 = $client->users()->find('hydra:user:1');

		$notification = new NotificationBroadcastResource(
			[
			    'type'        => 'example',
			    'message'     => 'Hello, World!',
			    'created-at'  => '2018-01-01 00:00:00',
			    'url'         => 'https://www.example.com',
			]
		);

		$notification->add('targets', $user1, $user2);

		$client->broadcasts()->create($notification);
	}
}
