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

use MediaWiki\MediaWikiServices;
use MWException;
use Hydrawiki\Reverb\Client\V1\Resources\Notification as NotificationResource;
use Reverb\Identifier\Identifier;
use Reverb\Identifier\InvalidIdentifierException;

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
	public function getID(): string {
		return $this->resource->getId();
	}

	/**
	 * Get the type for this notification.
	 *
	 * @return string Notification Type
	 */
	public function getType(): string {
		return $this->resource->getType();
	}

	/**
	 * Get the type for this notification.
	 *
	 * @param string $type Notification Type
	 *
	 * @return void
	 */
	public function setType(string $type) {
		$this->resource->setType($type);
	}

	/**
	 * Get the origin SiteIdentifier.
	 *
	 * @return SiteIdentifier|null
	 */
	public function getOrigin(): ?SiteIdentifier {
		if ($this->originIdCache === null) {
			try {
				$this->originIdCache = Identifier::factory($this->data['origin_id']);
			} catch (InvalidIdentifierException $e) {
				$this->originIdCache = null;
			}
		}
		return $this->originIdCache;
	}

	/**
	 * Get the agent UserIdentifier.
	 *
	 * @return UserIdentifier|null
	 */
	public function getAgent(): ?UserIdentifier {
		if ($this->agentIdCache === null) {
			try {
				$this->agentIdCache = Identifier::factory($this->data['agent_id']);
			} catch (InvalidIdentifierException $e) {
				$this->agentIdCache = null;
			}
		}
		return $this->agentIdCache;
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
		return substr($this->getType(), 0, strpos($this->getType(), '-', strpos($this->getType()) + 1));
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
}
