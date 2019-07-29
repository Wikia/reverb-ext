<?php
/**
 * Reverb
 * Service Wiring for Twiggy
 *
 * @package Reverb
 * @author  Samuel Hilson
 * @license GPL-2.0-or-later
 **/

namespace Reverb;

use MediaWiki\MediaWikiServices;
use Twiggy\TwiggyService;

class TwiggyWiring {
	/**
	 * Initialize Twiggy
	 *
	 * @return TwiggyService
	 */
	public static function init() {
		$twig = MediaWikiServices::getInstance()->getService('TwiggyService');
		$twig->setTemplateLocation('Reverb', __DIR__ . '/../resources/templates');

		return $twig;
	}
}
