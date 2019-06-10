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
			$this->dieUsageMsg(['apierror-permissiondenied-generic']);
		}

		switch ($this->params['do']) {
			case 'getNotificationsForUser':
				$response = $this->getNotificationsForUser();
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

		$bundle = NotificationBundle::getBundleForUser($this->getUser());

		if ($bundle !== null) {
			foreach ($bundle as $key => $notification) {
				$return['notifications'][] = $notification->toArray();
			}
		}

		return $return;
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
			'action=notifications&token=123ABC' => 'apihelp-notifications-example',
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
