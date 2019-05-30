<?php
/**
 * Reverb
 * SiteIdentifier
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Reverb\Identifier;

class SiteIdentifier extends Identifier {
	/**
	 * What am I?(Type such as site, user.)
	 *
	 * @var string
	 */
	private $what = 'site';

	/**
	 * Did this notification originate from this place?
	 *
	 * @return boolean
	 */
	public function isLocal() {
		$mainConfig = MediaWikiServices::getInstance()->getMainConfig();
		return $this->whereIsHome() === $this->getConfiguredNamespace() && $this->whoAmI() === wfWikiID();
	}
}
