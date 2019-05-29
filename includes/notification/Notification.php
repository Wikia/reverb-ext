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

class Notification {
	/**
	 * Data container.
	 *
	 * @var array
	 */
	private $data = [];

	/**
	 * Main Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Get the type for this notification.
	 *
	 * @access public
	 * @return string	Notification Type
	 */
	public function getType(): string {
		return $this->data['type'];
	}

	/**
	 * Get the category for this notification.
	 *
	 * @access public
	 * @return string	Category
	 */
	public function getCategory(): string {
		return substr($this->getType(), 0, strpos($this->getType(), '-'));
	}

	/**
	 * Get the subcategory for this notification.
	 *
	 * @access public
	 * @return string	Subcategory
	 */
	public function getSubcategory(): string {
		return substr($this->getType(), 0, strpos($this->getType(), '-', strpos($this->getType()) + 1));
	}

	/**
	 * Return the URL for the notification icon.
	 *
	 * @access public
	 * @return string|null URL or null if missing.
	 */
	public function getNotificationIcon(): ?string {
		$mainConfig = MediaWikiServices::getInstance()->getMainConfig();
		$reverbIcons = $config->get('ReverbIcons');

		return $reverbIcons['notification'][$this->getType()] ?? null;
	}

	/**
	 * Return the URL for the category icon.
	 *
	 * @access public
	 * @return string|null URL or null if missing.
	 */
	public function getCategoryIcon(): ?string {
		$mainConfig = MediaWikiServices::getInstance()->getMainConfig();
		$reverbIcons = $config->get('ReverbIcons');

		return $reverbIcons['category'][$this->getCategory()] ?? null;
	}

	/**
	 * Return the URL for the subcategory icon.
	 *
	 * @access public
	 * @return string|null URL or null if missing.
	 */
	public function getSubcategoryIcon(): ?string {
		$mainConfig = MediaWikiServices::getInstance()->getMainConfig();
		$reverbIcons = $config->get('ReverbIcons');

		return $reverbIcons['subcategory'][$this->getSubcategory()] ?? null;
	}
}
