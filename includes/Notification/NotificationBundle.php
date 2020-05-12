<?php
/**
 * Reverb
 * NotificationBundle
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 */

declare(strict_types=1);

namespace Reverb\Notification;

use ArrayObject;
use Exception;
use Hydrawiki\Reverb\Client\V1\Exceptions\ApiResponseInvalid;
use MediaWiki\MediaWikiServices;
use MWException;
use Reverb\Identifier\Identifier;
use Reverb\UserIdHelper;
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
	 * Total notifications in this bundle.
	 *
	 * @var integer
	 */
	protected $totalThisPage = 0;

	/**
	 * Total notifications in this bundle.
	 *
	 * @var integer
	 */
	protected $totalAll = 0;

	/**
	 * Number of unread notifications in this bundle.
	 *
	 * @var integer
	 */
	protected $unreadCount = 0;

	/**
	 * Number of read notifications in this bundle.
	 *
	 * @var integer
	 */
	protected $readCount = 0;

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
	 *                            [
	 *                            'read' => 1, // 1 only
	 *                            'unread' => 1, // 1 only
	 *                            'type' => article-edit-revert // Accepts comma separated notification types.
	 *                            ]
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

		$serviceUserId = UserIdHelper::getUserIdForService($user);

		$filters = self::validateFilters($filters);

		if (!empty($serviceUserId)) {
			$notifications = [];

			$client = MediaWikiServices::getInstance()->getService('ReverbApiClient');
			$userIdentifier = Identifier::newUser($serviceUserId);

			try {
				$notificationTargetResources = $client->notification_targets()->page(
					$itemsPerPage,
					$itemsPerPage * $pageNumber
				)->filter(
					array_merge(
						$filters,
						[
							'target-id' => 'hydra:user:' . $serviceUserId
						]
					)
				)->all();
			} catch (ApiResponseInvalid $e) {
				wfLogWarning('Invalid API response from the service: ' . $e->getMessage());
				return null;
			} catch (Exception $e) {
				wfLogWarning('General exception encountered when communicating with the service: ' . $e->getMessage());
				return null;
			}

			foreach ($notificationTargetResources as $key => $resource) {
				$notification = new Notification($resource->notification());
				$notification->setDismissedAt(intval($resource->dismissed_at));
				$notification->setUser($user);
				$notifications[$notification->getId()] = $notification;
			}

			$meta = $notificationTargetResources->meta();

			$bundle = new NotificationBundle($notifications);

			$bundle->filters = $filters;
			$bundle->itemsPerPage = $itemsPerPage;
			$bundle->pageNumber = $pageNumber;
			$bundle->unread = intval($meta['unread-count'] ?? 0);
			$bundle->read = intval($meta['read-count'] ?? 0);
			$bundle->totalThisPage = count($notifications);
			$bundle->totalAll = $bundle->unread + $bundle->read;

			// Set user context on NotificationBundle.
			$bundle->setUser($user);
			return $bundle;
		}
		return null;
	}

	/**
	 * Remove any filters that may be invalid.
	 *
	 * @param array $filters Unchecked Filters
	 *
	 * @return array Filters with anything invalid removed.
	 */
	public static function validateFilters($filters): array {
		$validFilters = [
			'read' => 'intval',
			'unread' => 'intval',
			'type' => 'strval'
		];

		$filters = array_intersect_key($filters, $validFilters);

		foreach ($filters as $key => $filter) {
			$filters[$key] = $validFilters[$key]($filter);
		}
		return $filters;
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
	 * Return the total notifications collected.
	 *
	 * @return integer
	 */
	public function getTotalThisPage(): int {
		return $this->totalThisPage;
	}

	/**
	 * Return the total notifications available from the service.
	 *
	 * @return integer
	 */
	public function getTotalAll(): int {
		return $this->totalAll;
	}

	/**
	 * Return the unread count from the meta data.
	 *
	 * @return integer
	 */
	public function getUnreadCount(): int {
		return $this->unread;
	}

	/**
	 * Return the unread count from the meta data.
	 *
	 * @return integer
	 */
	public function getReadCount(): int {
		return $this->read;
	}

	/**
	 * Return the calculated page number.
	 *
	 * @return integer
	 */
	public function getPageNumber(): int {
		return $this->pageNumber;
	}

	/**
	 * Return the requested items per page for reference.
	 *
	 * @return integer
	 */
	public function getItemsPerPage(): int {
		return $this->itemsPerPage;
	}
}
