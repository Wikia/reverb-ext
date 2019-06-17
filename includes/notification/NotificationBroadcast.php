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
use Hydrawiki\Reverb\Client\V1\Exceptions\ApiRequestUnsuccessful;
use MediaWiki\MediaWikiServices;
use MWException;
use Reverb\Identifier\Identifier;
use Reverb\Identifier\SiteIdentifier;
use Reverb\Identifier\UserIdentifier;
use User;

class NotificationBroadcast {
	/**
	 * SiteIdentifier Origin
	 *
	 * @var SiteIdentifier
	 */
	private $origin = null;

	/**
	 * UserIdentifier Agent
	 *
	 * @var UserIdentifier
	 */
	private $agent = null;

	/**
	 * UserIdentifier Targets
	 *
	 * @var array
	 */
	private $targets = [];

	/**
	 * Meta attributes part of the notification.
	 *
	 * @var array
	 */
	protected $attributes = [
		'type'        => null,
		'message'     => null,
		'created-at'  => null,
		'url'         => null
	];

	/**
	 * Reverb Client API Library
	 *
	 * @var Hydrawiki\Reverb\Client\V1\Client
	 */
	private $client = null;

	/**
	 * The last error encountered talking to the client library or service.
	 *
	 * @var string|null
	 */
	private $lastError = null;

	/**
	 * Main Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->client = MediaWikiServices::getInstance()->getService('ReverbApiClient');
	}

	/**
	 * Get a new instance for a broadcast to a single target.
	 *
	 * @param string $type   Notification Type
	 * @param User   $agent  User that triggerred the creation of the notification.
	 * @param User   $target User that the notification is targetting.
	 * @param array  $meta   Meta data attributes such as 'url' and 'message' parameters for building language strings.
	 *
	 * @return null
	 */
	public static function newSingle(
		string $type,
		User $agent,
		User $target,
		array $meta
	): ?self {
		if (!self::isTypeConfigured($type)) {
			throw new MWException('The notification type passed is not defined.');
		}

		$broadcast = new self();

		$lookup = CentralIdLookup::factory();
		$agentGlobalId = $lookup->centralIdFromLocalUser($agent);
		$targetGlobalId = $lookup->centralIdFromLocalUser($target);

		if (!$agentGlobalId || !$targetGlobalId) {
			return null;
		}

		if (empty($type) || !isset($meta['url']) || empty($meta['url'])) {
			throw new MWException('No type or canonical URL passed for broadcast.');
		}

		$broadcast->setAgent(Identifier::newUser($agentGlobalId));
		$broadcast->addTarget(Identifier::newUser($targetGlobalId));
		$broadcast->setOrigin(Identifier::newLocalSite());

		$broadcast->setAttributes(
			[
				'type' => $type,
				'url' => $meta['url'],
				'message' => json_encode($meta['message'])
			]
		);

		return $broadcast;
	}

	/**
	 * Is this a valid configured notification type?
	 *
	 * @param string $type Notification Type
	 *
	 * @return boolean
	 */
	private static function isTypeConfigured(string $type): bool {
		$mainConfig = MediaWikiServices::getInstance()->getMainConfig();
		$types = $mainConfig->get('ReverbNotifications');
		return isset($types[$type]);
	}

	/**
	 * Set the identifier for the origin(namespace) that originated this notification.
	 *
	 * @param SiteIdentifier $origin Origin Namespace
	 *
	 * @return void
	 */
	public function setOrigin(SiteIdentifier $origin) {
		$this->origin = $origin;
	}

	/**
	 * Set the identifier for the agent that created this notification.
	 *
	 * @param UserIdentifier $agent Notification Creator
	 *
	 * @return void
	 */
	public function setAgent(UserIdentifier $agent) {
		$this->agent = $agent;
	}

	/**
	 * Overwrite all targets.
	 *
	 * @param array $targets Notification Destinations
	 *
	 * @return void
	 */
	public function setTargets(array $targets) {
		foreach ($targets as $target) {
			if (!($target instanceof UserIdentifier)) {
				throw new MWException('Specified target is not an UserIdentifier.');
			}
		}
		$this->targets = $targets;
	}

	/**
	 * Set meta attributes for this broadcast.
	 *
	 * @param array $attributes Meta Attributes
	 *
	 * @return void
	 */
	public function setAttributes(array $attributes) {
		$attributes = array_intersect_key($attributes, $this->attributes);
		$this->attributes = array_merge($this->attributes, $attributes);
	}

	/**
	 * Add a new target to the existing targets.
	 *
	 * @param UserIdentifier $target Notification Destination
	 *
	 * @return void
	 */
	public function addTarget(UserIdentifier $target) {
		$this->targets[] = $target;
	}

	/**
	 * Transmit the broadcast.
	 *
	 * @return boolean Operation Success
	 */
	public function transmit(): bool {
		try {
			$notification = new NotificationBroadcastResource(
				array_merge(
					$this->attributes,
					[
						'origin-id'  => (string)$this->origin,
						'agent-id'   => (string)$this->agent,
						'target-ids' => array_map('strval', $this->targets)
					]
				)
			);

			$this->client->notification_broadcasts()->create($notification);
		} catch (ApiRequestUnsuccessful $e) {
			$this->lastError = $e->getMessage();
			return false;
		}
		return true;
	}

	/**
	 * Return the last error encountered.
	 *
	 * @return string|null Null if none has been set.
	 */
	public function getLastError(): ?string {
		return $this->lastError;
	}
}
