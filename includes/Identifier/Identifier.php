<?php
/**
 * Reverb
 * Identifier
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace Reverb\Identifier;

use DynamicSettings\Environment;
use MediaWiki\MediaWikiServices;

abstract class Identifier {
	/**
	 * Namespace
	 *
	 * @var string
	 */
	protected $namespace = '';

	/**
	 * What am I?(Type such as site, user.)
	 *
	 * @var string
	 */
	protected $what = '';

	/**
	 * Unique ID
	 *
	 * @var string
	 */
	protected $who = '';

	/**
	 * What/Type to class mapping.
	 *
	 * @var array
	 */
	private static $whatClassMap = [
		'site' => 'Reverb\Identifier\SiteIdentifier',
		'user' => 'Reverb\Identifier\UserIdentifier'
	];

	/**
	 * Get a new instance based on the identifier given.
	 *
	 * @param string|array $identifier String or array based identifier with namespace, what, and who.
	 *
	 * @return Identifier One of the Identifier children.
	 *
	 * @throws InvalidIdentifierException
	 */
	public static function factory($identifier): Identifier {
		if (!is_array($identifier)) {
			$idPieces = self::splitIdentifier($identifier);
		} else {
			$idPieces = $identifier;
		}
		if ($idPieces === null || !isset(self::$whatClassMap[$idPieces['what']])) {
			throw new InvalidIdentifierException();
		}

		return new self::$whatClassMap[$idPieces['what']]((string)$idPieces['namespace'], (string)$idPieces['who']);
	}

	/**
	 * Return a factory response for an user identifier.
	 *
	 * @param mixed $who Unique identifier of the item.
	 *
	 * @return UserIdentifier
	 */
	public static function newUser($who): UserIdentifier {
		$who = strval($who);
		return self::factory(['namespace' => self::getConfiguredNamespace(), 'what' => 'user', 'who' => $who]);
	}

	/**
	 * Return a factory response for a site identifier.
	 *
	 * @param mixed $who Unique identifier of the item.
	 *
	 * @return SiteIdentifier
	 */
	public static function newSite($who): SiteIdentifier {
		$who = strval($who);
		return self::factory(['namespace' => self::getConfiguredNamespace(), 'what' => 'site', 'who' => $who]);
	}

	/**
	 * Return a factory response for a local site identifier.
	 *
	 * @return SiteIdentifier
	 */
	public static function newLocalSite(): SiteIdentifier {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$siteKey = $config->get('CityId');
		if (empty($siteKey)) {
			throw new MWException('CityId could not be detected.');
		}
		return self::factory(['namespace' => self::getConfiguredNamespace(), 'what' => 'site', 'who' => $siteKey]);
	}

	/**
	 * Split an identifier with all three parts into an array.
	 *
	 * @param string $identifier Raw parameter with unknown validity.
	 *
	 * @return array|null Array with ['namespace' => 'hydra', 'what' => 'hydra', 'id' => 'hydra'] or null if invalid.
	 */
	private static function splitIdentifier(string $identifier): ?array {
		$regex = '#^([a-z]{1,64}):([a-z]{1,64}):([\w]{1,64})$#';
		$matches = [];
		if (preg_match($regex, $identifier, $matches) > 0) {
			return [
				'namespace' => $matches[1],
				'what' => $matches[2],
				'who' => $matches[3]
			];
		}
		return null;
	}

	/**
	 * Main Constructor
	 *
	 * @param string $namespace Namespace
	 * @param string $who       Unique ID
	 *
	 * @return void
	 */
	private function __construct(string $namespace, string $who) {
		$this->namespace = $namespace;
		$this->who = $who;
	}

	/**
	 * Return a concatenated version of the identifier.
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->whereIsHome() . ":" . $this->whatAmI() . ':' . $this->whoAmI();
	}

	/**
	 * Get the namespace for this identifier.
	 *
	 * @return string
	 */
	public function whereIsHome(): string {
		return $this->namespace;
	}

	/**
	 * Return what kind of identifier this is such as 'user' or 'site'.
	 *
	 * @return string What kind of identifier am I?
	 */
	public function whatAmI(): string {
		return $this->what;
	}

	/**
	 * Get the unique ID for this identifier.
	 *
	 * @return string
	 */
	public function whoAmI(): string {
		return $this->who;
	}

	/**
	 * Did this notification originate from this place?
	 *
	 * @return boolean
	 */
	public function isLocal(): bool {
		return $this->namespace === self::getConfiguredNamespace();
	}

	/**
	 * Get the namespace from $wgReverbNamespace.
	 *
	 * @return string
	 */
	public static function getConfiguredNamespace(): string {
		$mainConfig = MediaWikiServices::getInstance()->getMainConfig();
		return $mainConfig->get('ReverbNamespace');
	}
}
