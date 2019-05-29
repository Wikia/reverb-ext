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

class ApiNotifications extends ApiBase {
	/**
	 * Is POST required?
	 *
	 * @return boolean
	 */
	public function execute() {
	}

	/**
	 * Is POST required?
	 *
	 * @return boolean
	 */
	public function mustBePosted() {
		return true;
	}

	/**
	 * Array of allowed parameters on the API request.
	 *
	 * @return array
	 */
	public function getAllowedParams() {
		return [
			/*'category' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			]*/
		];
	}

	/**
	 * Get the name of the required POST token.
	 *
	 * @return string
	 */
	public function needsToken() {
		return 'csrf';
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
