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

use CentralIdLookup;
use Hydrawiki\Reverb\Client\V1\Resources\NotificationBroadcast as NotificationBroadcastResource;
use MediaWiki\MediaWikiServices;
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
	}

	/**
	 * Get a new instance for a broadcast to a single target.
	 *
	 * @param string $type         Notification Type
	 * @param User   $agent        User that triggerred the creation of the notification.
	 * @param User   $target       User that the notification is targetting.
	 * @param string $canonicalUrl Definitive canonical URL for this notification.
	 * @param array  $parameters   Mixed parameters for building language strings.
	 *
	 * @return null
	 */
	public static function newSingle(
		string $type,
		User $agent,
		User $target,
		string $canonicalUrl,
		array $parameters
	): ?self {
		$client = MediaWikiServices::getInstance()->getService('ReverbApiClient');

		$lookup = CentralIdLookup::factory();
		$agentGlobalId = $lookup->centralIdFromLocalUser($agent);
		$targetGlobalId = $lookup->centralIdFromLocalUser($target);

		if (!$agentGlobalId || !$targetGlobalId) {
			return null;
		}

		$agentIdentifier = Identifier::newUser($agentGlobalId);
		$targetIdentifier = Identifier::newUser($targetGlobalId);
		$originIdentifier = Identifier::newLocalSite();

		$notification = new NotificationBroadcastResource(
			[
				'type'       => $type,
				'message'    => $parameters,
				'url'        => $canonicalUrl,
				'origin-id'  => (string)$originIdentifier,
				'agent-id'   => (string)$agentIdentifier,
				'target-ids' => [(string)$targetIdentifier]
			]
		);
		var_dump($notification);
		$client->notification_broadcasts()->create($notification);
	}
}
