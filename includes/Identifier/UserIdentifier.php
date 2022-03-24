<?php
/**
 * Reverb
 * UserIdentifier
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace Reverb\Identifier;

class UserIdentifier extends Identifier {
	/**
	 * What am I?(Type such as site, user.)
	 *
	 * @var string
	 */
	protected $what = 'user';
}
