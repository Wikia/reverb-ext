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
use Hydrawiki\Reverb\Client\V1\Exceptions\ApiRequestUnsuccessful;
use Hydrawiki\Reverb\Client\V1\Resources\NotificationBroadcast as NotificationBroadcastResource;
use MediaWiki\MediaWikiServices;
use MWException;
use Reverb\Identifier\Identifier;
use Reverb\Identifier\SiteIdentifier;
use Reverb\Identifier\UserIdentifier;
use Reverb\Traits\NotificationListTrait;
use User;

class NotificationBroadcast {
	use NotificationListTrait;

	/**
	 * SiteIdentifier Origin
	 *
	 * @var SiteIdentifier
	 */
	private $origin = null;

	/**
	 * User Agent
	 *
	 * @var User
	 */
	private $agent = null;

	/**
	 * UserIdentifier Agent
	 *
	 * @var UserIdentifier
	 */
	private $agentId = null;

	/**
	 * User Targets
	 *
	 * @var array User
	 */
	private $targets = [];

	/**
	 * UserIdentifier Targets
	 *
	 * @var array
	 */
	private $targetIds = [];

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
	 * @param User   $agent  User that triggered the creation of the notification.
	 * @param User   $target User that the notification is targeting.
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
		return self::newMulti($type, $agent, [$target], $meta);
	}

	/**
	 * Get a new instance for a broadcast to a single target.
	 *
	 * @param string $type    Notification Type
	 * @param User   $agent   User that triggered the creation of the notification.
	 * @param array  $targets User that the notification is targeting.
	 * @param array  $meta    Meta data attributes such as 'url' and 'message' parameters for building language strings.
	 *
	 * @return null
	 */
	public static function newMulti(
		string $type,
		User $agent,
		array $targets,
		array $meta
	): ?self {
		if (!self::isTypeConfigured($type)) {
			throw new MWException('The notification type passed is not defined.');
		}

		if (!isset($meta['url']) || empty($meta['url'])) {
			throw new MWException('No canonical URL passed for broadcast.');
		}

		$broadcast = new self();

		$broadcast->setAttributes(
			[
				'type' => $type,
				'url' => $meta['url'],
				'message' => json_encode($meta['message'])
			]
		);

		// These need to come after setAttributes().
		$broadcast->setAgent($agent);
		$broadcast->setTargets($targets);
		$broadcast->setOrigin(Identifier::newLocalSite());

		if (!$broadcast->getAgent() || empty($broadcast->getTargets())) {
			return null;
		}

		return $broadcast;
	}

	/**
	 * Is this a valid configured notification type?
	 *
	 * @param string $type Notification Type
	 *
	 * @return boolean
	 */
	public static function isTypeConfigured(string $type): bool {
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
	 * @param User $agent Notification Creator
	 *
	 * @return boolean Success
	 */
	public function setAgent(User $agent): bool {
		if (!($agent instanceof User)) {
			throw new MWException('Invalid agent passed.');
		}

		$lookup = CentralIdLookup::factory();
		$agentGlobalId = $lookup->centralIdFromLocalUser($agent);

		if (empty($agentGlobalId)) {
			return false;
		}

		$this->agent = $agent;
		$this->agentId = Identifier::newUser($agentGlobalId);
		return true;
	}

	/**
	 * Return all the agent for this broadcast.
	 *
	 * @return User|null
	 */
	public function getAgent(): ?User {
		return $this->agent;
	}

	/**
	 * Overwrite all targets.
	 *
	 * @param array $targets Array of User
	 *
	 * @return void
	 */
	public function setTargets(array $targets) {
		$lookup = CentralIdLookup::factory();

		$targetIdentifiers = [];
		foreach ($targets as $key => $target) {
			if (!($target instanceof User)) {
				throw new MWException('Invalid target passed.');
			}

			$targetGlobalId = $lookup->centralIdFromLocalUser($target);
			if (!$targetGlobalId) {
				unset($targets[$key]);
				continue;
			}
			$targetShouldBeNotified = $this->shouldNotify($target, $this->attributes['type'], 'web');
			if ($targetShouldBeNotified) {
				$targetIdentifiers[] = Identifier::newUser($targetGlobalId);
			}
		}

		$this->targets = $targets;
		$this->targetIds = $targetIdentifiers;
	}

	/**
	 * Return all targets for this broadcast.
	 *
	 * @return array
	 */
	public function getTargets(): array {
		return $this->targets;
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
	 * Get meta attributes for this broadcast.
	 *
	 * @return array $attributes Meta Attributes
	 */
	public function getAttributes(): array {
		return $this->attributes;
	}

	/**
	 * Transmit the broadcast.
	 *
	 * @return boolean Operation Success
	 */
	public function transmit(): bool {
		$email = NotificationEmail::newFromBroadcast($this);
		$email->send();

		if (empty($this->targetIds)) {
			return true;
		}

		try {
			$notification = new NotificationBroadcastResource(
				array_merge(
					$this->attributes,
					[
						'origin-id'  => (string)$this->origin,
						'agent-id'   => (string)$this->agentId,
						'target-ids' => array_map('strval', $this->targetIds)
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
