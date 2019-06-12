<?php
/**
 * Reverb
 * NotificationBundle
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Reverb\Notification;

use ArrayObject;
use CentralIdLookup;
use Exception;
use Hydrawiki\Reverb\Client\V1\Exceptions\ApiResponseInvalid;
use MediaWiki\MediaWikiServices;
use MWException;
use Reverb\Identifier\Identifier;
use User;

class NotificationBundle extends ArrayObject {
	use \Reverb\Traits\UserContextTrait;

	/**
	 * Filters used to build this instance.
	 *
	 * @var array
	 */
	protected $filters = [];

	/**
	 * Items per page.
	 *
	 * @var integer
	 */
	protected $itemsPerPage = 50;

	/**
	 * Current page number.
	 *
	 * @var integer
	 */
	protected $pageNumber = 0;

	/**
	 * Main Constructor
	 *
	 * @param array   $notifications Array of Reverb\Notification\Notification objects.
	 * @param integer $flags         ArrayObject::STD_PROP_LIST | ArrayObject::ARRAY_AS_PROPS
	 * @param string  $iterator      Iterator class to use.
	 *
	 * @return void
	 */
	public function __construct(array $notifications = [], int $flags = 0, string $iterator = "ArrayIterator") {
		foreach ($notifications as $notification) {
			if (!($notification instanceof Notification)) {
				throw new MWException('Invalid item was attempted to be added to bundle.');
			}
		}
		parent::__construct($notifications, $flags, $iterator);
	}

	/**
	 * Get a bundle of notifications for an user with optional filters.
	 *
	 * @param User  $user         User object to use for look up.
	 * @param array $filters      [Optional] Filters for notifications.
	 * @param array $itemsPerPage [Optional] Number of items per page.
	 * @param array $pageNumber   [Optional] Page number to read.
	 *
	 * @return NotificationBundle|null Returns null if a bad user(No global account or robot account) is passed.
	 */
	public static function getBundleForUser(
		User $user,
		array $filters = [],
		int $itemsPerPage = 50,
		int $pageNumber = 0
	): ?NotificationBundle {
		if ($user->isBot()) {
			return null;
		}

		// Make sure this is from 1 to 100.
		$itemsPerPage = max(min($itemsPerPage, 100), 1);
		// Make sure the page number is >= 0.
		$pageNumber = max(0, $pageNumber);

		$lookup = CentralIdLookup::factory();
		$globalId = $lookup->centralIdFromLocalUser($user);

		// @TODO: The only filter right now is 'target-id'.
		// Later on we will need to implement this function to validate passed filters.
		// $filters = self::validateFilters($filters);

		if (!empty($globalId)) {
			$notifications = [];

			$client = MediaWikiServices::getInstance()->getService('ReverbApiClient');
			$userIdentifier = Identifier::factory(
				[
					'namespace' => 'hydra',
					'what' => 'user',
					'id' => $globalId
				]
			);

			try {
				$notificationResources = $client->notifications()->page(
					$itemsPerPage,
					$itemsPerPage * $pageNumber
				)->filter(
					array_merge(
						$filters,
						[
							'target-id' => 'hydra:user:' . $globalId
						]
					)
				)->all();
			} catch (ApiResponseInvalid $e) {
				// @TODO: Logging and error reporting.
				return null;
			} catch (Exception $e) {
				return null;
			}

			foreach ($notificationResources as $key => $resource) {
				$notification = new Notification($resource);
				$notifications[$notification->getId()] = $notification;
			}

			$bundle = new NotificationBundle($notifications);

			$bundle->filters = $filters;
			$bundle->itemsPerPage = $itemsPerPage;
			$bundle->pageNumber = $pageNumber;

			// Set user context on NotificationBundle.
			$bundle->setUser($user);
			return $bundle;
		}
		return null;
	}

	/**
	 * Get the next page of bundled notifications.
	 *
	 * @return NotificationBundle|null
	 */
	public function nextPage(): ?NotificationBundle {
		return self::getBundleForUser($this->getUser(), $this->filters, $this->itemsPerPage, $this->pageNumber + 1);
	}

	/**
	 * Get a set of JSON:API compatible links for consumers.
	 *
	 * @return array ['first' => '', 'prev' => '', 'next' => '', 'last' => '']
	 */
	public function getApiLinks(): array {
		// code...
	}

	/**
	 * Return the total notifications returned by the meta data in the response.
	 *
	 * @return integer
	 */
	public function getTotal(): int {
		// code...
	}
}
