<?php
/**
 * Reverb
 * UserContext Trait
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 */

namespace Reverb\Traits;

use User;

trait UserContextTrait {
	/**
	 * User context for this scope.
	 *
	 * @var User $user
	 */
	protected $user;

	/**
	 * Get the User context.
	 *
	 * @return User|null
	 */
	public function getUser(): ?User {
		return $this->user;
	}

	/**
	 * Get the User context.
	 *
	 * @param User|null $user User to set as context.
	 *
	 * @return void
	 */
	public function setUser(?User $user) {
		$this->user = $user;
	}
}
