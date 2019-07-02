<?php
/**
 * Reverb
 * Notifications API
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Reverb\Api;

use ApiBase;
use CentralIdLookup;
use Hydrawiki\Reverb\Client\V1\Resources\NotificationDismissals as NotificationDismissalsResource;
use MediaWiki\MediaWikiServices;
use Reverb\Identifier\Identifier;
use Reverb\Notification\Notification;
use Reverb\Notification\NotificationBroadcast;
use Reverb\Notification\NotificationBundle;

class ApiNotifications extends ApiBase {
	/**
	 * Main API entry point.
	 *
	 * @return void
	 */
	public function execute() {
		$this->params = $this->extractRequestParams();

		if (!$this->getUser()->isLoggedIn()) {
			$this->dieWithError(['apierror-permissiondenied-generic']);
		}

		switch ($this->params['do']) {
			case 'getNotificationsForUser':
				$response = $this->getNotificationsForUser();
				break;
			case 'dismissNotification':
				$response = $this->dismissNotification();
				break;
			case 'dismissAllNotifications':
				$response = $this->dismissAllNotifications();
				break;
			default:
				$this->dieUsageMsg(['invaliddo', $this->params['do']]);
				break;
		}

		foreach ($response as $key => $value) {
			$this->getResult()->addValue(null, $key, $value);
		}
	}

	/**
	 * Get notifications for the current user.
	 *
	 * @return array
	 */
	public function getNotificationsForUser(): array {
		$return = [
			'notifications' => []
		];

		$filters = [];
		if ($this->params['read'] === 1) {
			$filters['read'] = 1;
		}
		if ($this->params['unread'] === 1) {
			$filters['unread'] = 1;
		}
		if (!empty($this->params['type'])) {
			$types = explode(',', $this->params['type']);
			foreach ($types as $key => $type) {
				if (!NotificationBroadcast::isTypeConfigured($type)) {
					unset($types[$key]);
				}
			}
			if (!empty($types)) {
				$filters['type'] = implode(',', $types);
			}
		}

		$bundle = NotificationBundle::getBundleForUser(
			$this->getUser(),
			$filters,
			$this->params['itemsPerPage'],
			$this->params['page']
		);

		if ($bundle !== null) {
			foreach ($bundle as $key => $notification) {
				$return['notifications'][] = $notification->toArray();
			}
			$return['meta'] = [
				'unread' => $bundle->getUnreadCount(),
				'read' => $bundle->getReadCount(),
				'total_this_page' => $bundle->getTotalThisPage(),
				'total_all' => $bundle->getTotalAll(),
				'page' => $bundle->getPageNumber(),
				'items_per_page' => $bundle->getItemsPerPage()
			];
		}

		return $return;
	}

	/**
	 * Dismiss a notification based on ID.
	 *
	 * @return array
	 */
	public function dismissNotification(): array {
		if (!$this->getRequest()->wasPosted()) {
			$this->dieWithError(['apierror-mustbeposted', __FUNCTION__]);
		}

		$success = false;

		$id = $this->params['notificationId'];
		$timestamp = $this->params['dismissedAt'];
		if ($timestamp === null) {
			$timestamp = time();
		}

		if (!empty($id)) {
			$success = Notification::dismissNotification($this->getUser(), (string)$id, $timestamp);
		}

		return [
			'success' => $success
		];
	}

	/**
	 * Dismiss a notification based on ID.
	 *
	 * @return array
	 */
	public function dismissAllNotifications(): array {
		if (!$this->getRequest()->wasPosted()) {
			$this->dieWithError(['apierror-mustbeposted', __FUNCTION__]);
		}

		$success = false;

		$lookup = CentralIdLookup::factory();
		$globalId = $lookup->centralIdFromLocalUser($this->getUser());
		$userIdentifier = Identifier::newUser($globalId);
		$dismiss = new NotificationDismissalsResource(
			[
				'target-id' => (string)$userIdentifier
			]
		);

		try {
			$client = MediaWikiServices::getInstance()->getService('ReverbApiClient');
			$response = $client->notification_dismissals()->create($dismiss);
			$success = true;
		} catch (ApiRequestUnsuccessful $e) {
			wfLogWarning('Invalid API response from the service: ' . $e->getMessage());
		} catch (Exception $e) {
			wfLogWarning('General exception encountered when communicating with the service: ' . $e->getMessage());
		}

		return [
			'success' => $success
		];
	}

	/**
	 * Array of allowed parameters on the API request.
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return [
			'do' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			],
			'page' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_DFLT => 0
			],
			'itemsPerPage' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_DFLT => 50
			],
			'type' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false,
				ApiBase::PARAM_DFLT => null
			],
			'read' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false,
				ApiBase::PARAM_DFLT => null
			],
			'unread' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false,
				ApiBase::PARAM_DFLT => null
			],
			'notificationId' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false,
				ApiBase::PARAM_DFLT => null
			],
			'dismissedAt' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false,
				ApiBase::PARAM_DFLT => null
			]
		];
	}

	/**
	 * Get example URL parameters and help message key.
	 *
	 * @return string
	 */
	protected function getExamplesMessages() {
		return [
			'action=notifications&do=getNotificationsForUser&page=0&itemsPerPage=50'
				=> 'apihelp-notifications-getNotificationsForUser-example',
			'action=notifications&do=dismissNotification&notificationId=1&dismissedAt=1562006555'
				=> 'apihelp-notifications-dismissNotification-example',
			'action=notifications&do=dismissAllNotifications'
				=> 'apihelp-notifications-dismissAllNotifications-example'
		];
	}

	/**
	 * Destination URL of help information for this API.
	 *
	 * @return string
	 */
	public function getHelpUrls() {
		return '';
	}
}
