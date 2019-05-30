<?php
/**
 * Reverb
 * Identifier
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Reverb\Identifier;

use MediaWikiServices;

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
	protected $id = '';

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
	 * @param string $identifier String based identifier with namespace, what, and ID.
	 *
	 * @return Identifier One of the Identifier children.
	 *
	 * @throws InvalidIdentifierException
	 */
	public static function factory(string $identifier): Identifier {
		$identifier = self::splitIdentifier($identifier);
		if ($identifier === null || !isset(self::$whatClassMap[$identifier['what']])) {
			throw new InvalidIdentifierException();
		}

		return new self::$whatClassMap[$identifier['what']]($identifier['namespace'], $identifier['id']);
	}

	/**
	 * Split an identifier with all three parts into an array.
	 *
	 * @param string $identifier Raw parameter with unknown validity.
	 *
	 * @return array|null Array with ['namespace' => 'hydra', 'what' => 'hydra', 'id' => 'hydra'] or null if invalid.
	 */
	private static function splitIdentifier(string $identifier): ?array {
		$regex = '#^([a-z]{1,64})/([a-z]{1,64}):([\w]+)$#';
		$matches = [];
		if (preg_match($regex, $identifier, $matches) > 0) {
			return [
				'namespace' => $matches[1],
				'what' => $matches[2],
				'id' => $matches[3]
			];
		}
	}

	/**
	 * Main Constructor
	 *
	 * @param string $namespace Namespace
	 * @param string $id        Unique ID
	 *
	 * @return void
	 */
	private function __construct(string $namespace, string $id) {
		$this->namespace = $namespace;
		$this->id = $id;
	}

	/**
	 * Return a concatenated version of the identifier.
	 *
	 * @return string
	 */
	public function __toString(): string {
		return $this->whereIsHome() . "/" . $this->whatAmI() . ':' . $this->whoAmI();
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
		return $this->id;
	}

	/**
	 * Did this notification originate from this place?
	 *
	 * @return boolean
	 */
	public function isLocal(): bool {
		return $this->namespace === $this->getConfiguredNamespace();
	}

	/**
	 * Get the namespace from $wgReverbNamespace.
	 *
	 * @return string
	 */
	public function getConfiguredNamespace(): string {
		$mainConfig = MediaWikiServices::getInstance()->getMainConfig();
		return $config->get('ReverbNamespace');
	}
}
