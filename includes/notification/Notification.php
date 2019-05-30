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

use MediaWikiServices;
use MWException;
use Reverb\Identifier\Identifier;
use Reverb\Identifier\InvalidIdentifierException;

class Notification {
	/**
	 * Data container.
	 *
	 * @var array
	 */
	private $data = [
		'id' => '',
		'origin_id' => '',
		'agent_id' => '',
		'type' => '',
		'message_data' => '',
		'created_at' => 0,
		'url' => ''
	];

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
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Get the type for this notification.
	 *
	 * @return string Notification Type
	 */
	public function getType(): string {
		return $this->data['type'];
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
				$this->agentIdCache = Identifier::factory($this->data['origin_id']);
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
		$reverbIcons = $config->get('ReverbIcons');

		if (!isset($reverbIcons[$type])) {
			throw new MWException("The request icon type '{$type}' is missing from the \$wgReverbIcons configuration.");
		}

		return $reverbIcons[$type];
	}
}
