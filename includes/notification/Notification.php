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

use CentralIdLookup;
use DynamicSettings\Wiki;
use Exception;
use Hydrawiki\Reverb\Client\V1\Exceptions\ApiRequestUnsuccessful as ApiRequestUnsuccessful;
use Hydrawiki\Reverb\Client\V1\Resources\Notification as NotificationResource;
use Hydrawiki\Reverb\Client\V1\Resources\NotificationTarget as NotificationTargetResource;
use MediaWiki\MediaWikiServices;
use Message;
use MWException;
use Reverb\Identifier\Identifier;
use Reverb\Identifier\InvalidIdentifierException;
use Reverb\Identifier\SiteIdentifier;
use Reverb\Identifier\UserIdentifier;
use Title;
use User;

class Notification {
	use \Reverb\Traits\UserContextTrait;

	/**
	 * Data Resource
	 *
	 * @var Hydrawiki\Reverb\Client\V1\Resources\Notification
	 */
	private $resource = null;

	/**
	 * Cached Origin SiteIdentifier
	 *
	 * @var SiteIdentifier|null
	 */
	private $originIdCache = null;

	/**
	 * Cached Agent UserIdentifier
	 *
	 * @var SiteIdentifier|null
	 */
	private $agentIdCache = null;

	/**
	 * Dismissed Timestamp
	 *
	 * @var integer
	 */
	private $dismissedAt = 0;

	/**
	 * Main Constructor
	 *
	 * @param NotificationResource $resource Already known notification resource.
	 *
	 * @return void
	 */
	public function __construct(NotificationResource $resource) {
		$this->resource = $resource;
	}

	/**
	 * Get the unique ID for this notification used by the service.
	 *
	 * @return integer ID
	 */
	public function getId(): int {
		// The resource can return null if nothing is initialized.
		return intval($this->resource->id());
	}

	/**
	 * Get the type for this notification.
	 *
	 * @return string Notification Type
	 */
	public function getType(): string {
		// This will return the 'type' key off the $attributes array member on the object
		// and not the 'type' resource string member.
		return $this->resource->type;
	}

	/**
	 * Set the type for this notification.
	 *
	 * @param string $type Notification Type
	 *
	 * @return void
	 */
	public function setType(string $type) {
		$this->resource->setAttributes(['type' => $type]);
	}

	/**
	 * Get the header for this notification.
	 *
	 * @param boolean $long Use the short or long version of the header.
	 *
	 * @return string Message
	 */
	public function getHeader(bool $long = false): Message {
		$parameters = $this->getMessageParameters();
		unset($parameters['user_note']);

		// Pad parameters that start at not 1.
		// This fixes issues with legacy Echo language strings missing parameters at the beginning of the string.
		if (count($parameters) > 0) {
			$max = max(array_keys($parameters));
			for ($i = 1; $i < $max; $i++) {
				if (!isset($parameters[$i])) {
					$parameters[$i] = null;
				}
			}
			ksort($parameters);
		}

		return wfMessage(
			($long ? 'long' : 'short') . '-header-' . $this->getType()
		)->params($parameters);
	}

	/**
	 * If there is an available user note get it from the parameters.
	 *
	 * @return string|null Defined user note or null.
	 */
	protected function getUserNote(): ?string {
		$parameters = $this->getMessageParameters();
		return $parameters['user_note'] ?? null;
	}

	/**
	 * Do any clean up and representation changes on message parameters then return them.
	 *
	 * @return array
	 */
	protected function getMessageParameters(): array {
		$json = (array)json_decode($this->resource->message);

		$parameters = [];
		foreach ($json as $parameter) {
			$parameters[$parameter[0]] = $parameter[1];
		}
		ksort($parameters, SORT_NATURAL);

		return $parameters;
	}

	/**
	 * Get the created date for this notification.
	 *
	 * @return integer Creation Date
	 */
	public function getCreatedAt(): int {
		return $this->resource->created_at;
	}

	/**
	 * Is this notification dismissed?
	 *
	 * @return boolean Is dismissed
	 */
	public function isDismissed(): bool {
		return boolval($this->resource->dismissed_at);
	}

	/**
	 * Get the dismissed timestamp for this notification.
	 *
	 * @return integer Dismissed Timestamp
	 */
	public function getDismissedAt(): int {
		return intval($this->dismissedAt);
	}

	/**
	 * Set the dismissed timestamp for this notification.
	 *
	 * @param integer $dismissedAt Dismissed Timestamp
	 *
	 * @return void
	 */
	public function setDismissedAt(int $dismissedAt) {
		$this->dismissedAt = $dismissedAt;
	}

	/**
	 * Get the canonical URL destination for this notification.
	 *
	 * @return string URL(May be empty)
	 */
	public function getCanonicalUrl(): string {
		return (string)$this->resource->url;
	}

	/**
	 * Get the origin SiteIdentifier.
	 *
	 * @return SiteIdentifier|null
	 */
	public function getOriginId(): ?SiteIdentifier {
		if ($this->originIdCache === null) {
			try {
				$this->originIdCache = Identifier::factory($this->resource->origin_id);
			} catch (InvalidIdentifierException $e) {
				$this->originIdCache = null;
			}
		}
		return $this->originIdCache;
	}

	/**
	 * Return a Wiki object that has wiki information.
	 *
	 * @return Wiki|null
	 */
	public function getOrigin(): ?Wiki {
		$id = $this->getOriginId();
		if ($id !== null) {
			if ($id->whoAmI() === 'master') {
				$wiki = Wiki::getFakeMainWiki();
			} else {
				$wiki = Wiki::loadFromHash($id->whoAmI());
			}
			if (!empty($wiki)) {
				return $wiki;
			}
		}
		return null;
	}

	/**
	 * Get an URL to the origin.
	 *
	 * @return string|null
	 */
	public function getOriginUrl(): ?string {
		$origin = $this->getOrigin();
		if ($origin !== null) {
			return wfExpandUrl('//' . $origin->getDomains()->getDomain(), PROTO_HTTPS);
		}
		return null;
	}

	/**
	 * Get the agent UserIdentifier.
	 *
	 * @return UserIdentifier|null
	 */
	public function getAgentId(): ?UserIdentifier {
		if ($this->agentIdCache === null) {
			try {
				$this->agentIdCache = Identifier::factory($this->resource->agent_id);
			} catch (InvalidIdentifierException $e) {
				$this->agentIdCache = null;
			}
		}
		return $this->agentIdCache;
	}

	/**
	 * Return an User object of the agent that created this notification.
	 *
	 * @return Wiki|null
	 */
	public function getAgent(): ?User {
		$id = $this->getAgentId();
		if ($id !== null) {
			$lookup = CentralIdLookup::factory();
			$user = $lookup->localUserFromCentralId($id->whoAmI());
			if ($user !== null) {
				return $user;
			}
		}
		return null;
	}

	/**
	 * Get an URL to the agent.
	 *
	 * @return string|null
	 */
	public function getAgentUrl(): ?string {
		$agent = $this->getAgent();
		if ($agent !== null) {
			return Title::newFromText($agent->getName(), NS_USER)->getCanonicalURL();
		}
		return null;
	}

	/**
	 * Get the category for this notification.
	 *
	 * @return string Category
	 */
	public function getCategory(): string {
		return substr($this->getType(), 0, strpos($this->getType(), '-'));
	}

	/**
	 * Get the subcategory for this notification.
	 *
	 * @return string Subcategory
	 */
	public function getSubcategory(): string {
		return substr($this->getType(), 0, strpos($this->getType(), '-', strpos($this->getType(), '-') + 1));
	}

	/**
	 * Return the URL for the notification icon.
	 *
	 * @return string|null URL or null if missing.
	 */
	public function getNotificationIcon(): ?string {
		$icons = $this->getIconsConfig('notification');

		return $icons['notification'][$this->getType()] ?? null;
	}

	/**
	 * Return the URL for the category icon.
	 *
	 * @return string|null URL or null if missing.
	 */
	public function getCategoryIcon(): ?string {
		$icons = $this->getIconsConfig('category');

		return $icons[$this->getCategory()] ?? null;
	}

	/**
	 * Return the URL for the subcategory icon.
	 *
	 * @return string|null URL or null if missing.
	 */
	public function getSubcategoryIcon(): ?string {
		$icons = $this->getIconsConfig('subcategory');

		return $icons[$this->getSubcategory()] ?? null;
	}

	/**
	 * Get icon configuration.
	 *
	 * @param string $type Icon Type, one of: 'notification', 'category', 'subcategory'
	 *
	 * @return array Array containing key of type name to the URL location for it.
	 * @throws MWException
	 */
	private function getIconsConfig(string $type = 'notification'): array {
		$mainConfig = MediaWikiServices::getInstance()->getMainConfig();
		$reverbIcons = $mainConfig->get('ReverbIcons');

		if (!isset($reverbIcons[$type])) {
			throw new MWException("The request icon type '{$type}' is missing from the \$wgReverbIcons configuration.");
		}

		return $reverbIcons[$type];
	}

	/**
	 * Get the importance of this notification to assist with sorting.
	 *
	 * @return integer Importance
	 */
	public function getImportance(): int {
		$mainConfig = MediaWikiServices::getInstance()->getMainConfig();
		$reverbNotifications = $mainConfig->get('ReverbNotifications');
		return intval($reverbNotifications[$this->getType()]['importance']);
	}

	/**
	 * Get an array representation of this object suitable for APIs or otherwise.
	 *
	 * @return array
	 */
	public function toArray(): array {
		return [
			'icons' => [
				'notification' => $this->getNotificationIcon(),
				'category' => $this->getCategoryIcon(),
				'subcategory' => $this->getSubcategoryIcon()
			],
			'category' => $this->getCategory(),
			'subcategory' => $this->getSubcategory(),
			'id' => $this->getId(),
			'type' => $this->getType(),
			'header_short' => $this->getHeader(),
			'header_long' => $this->getHeader(true),
			'user_note' => $this->getUserNote(),
			'created_at' => $this->getCreatedAt(),
			'dismissed_at' => $this->getDismissedAt(),
			'origin_url' => $this->getOriginUrl(),
			'agent_url' => $this->getAgentUrl(),
			'canonical_url' => $this->getCanonicalUrl(),
			'importance' => $this->getImportance()
		];
	}

	/**
	 * Dismiss a notification based on the original resource ID.
	 *
	 * @param User   $user Target user that the notification originally targetted.
	 * @param string $id   The ID from the original resource.
	 *
	 * @return null
	 */
	public static function dismissNotification(User $user, string $id): bool {
		$lookup = CentralIdLookup::factory();
		$globalId = $lookup->centralIdFromLocalUser($user);
		$userIdentifier = Identifier::newUser($globalId);
		if (!$userIdentifier) {
			return false;
		}

		$target = new NotificationTargetResource(
			[
				'target-id' => (string)$userIdentifier,
				'dismissed-at' => time()
			]
		);
		$target->setId((string)$userIdentifier . ':' . $id);

		try {
			$client = MediaWikiServices::getInstance()->getService('ReverbApiClient');
			$response = $client->update($target);
			return true;
		} catch (ApiRequestUnsuccessful $e) {
			wfLogWarning('Invalid API response from the service: ' . $e->getMessage());
		} catch (Exception $e) {
			wfLogWarning('General exception encountered when communicating with the service: ' . $e->getMessage());
		}
		return false;
	}
}
