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
use Hydrawiki\Reverb\Client\V1\Resources\Notification as NotificationResource;
use MediaWiki\MediaWikiServices;
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
	 * @return string ID
	 */
	public function getId(): string {
		return $this->resource->id();
	}

	/**
	 * Get the type for this notification.
	 *
	 * @return string Notification Type
	 */
	public function getType(): string {
		return $this->resource->type;
	}

	/**
	 * Get the type for this notification.
	 *
	 * @param string $type Notification Type
	 *
	 * @return void
	 */
	public function setType(string $type) {
		$this->resource->setId($type);
	}

	/**
	 * Get the message for this notification.
	 *
	 * @return string Message
	 */
	public function getMessage(): string {
		return $this->resource->message;
	}

	/**
	 * Get the created date for this notification.
	 *
	 * @return int Creation Date
	 */
	public function getCreatedAt(): int {
		return $this->resource->created_at;
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
			return wfExpandUrl('//'.$origin->getDomains()->getDomain(), PROTO_HTTPS);
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
	 * Function Documentation
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
			'message' => $this->getMessage(),
			'created_at' => $this->getCreatedAt(),
			'origin_url' => $this->getOriginUrl(),
			'agent_url' => $this->getAgentUrl()
		];
	}
}
