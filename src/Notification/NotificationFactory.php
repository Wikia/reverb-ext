<?php

namespace Reverb\Notification;

use Config;
use Fandom\Includes\Util\UrlUtilityService;
use Fandom\WikiConfig\WikiVariablesDataService;
use MediaWiki\User\UserFactory;
use WikiDomain\WikiConfigDataService;

class NotificationFactory {
	public function __construct(
		private Config $config,
		private NotificationListService $notificationListService,
		private UrlUtilityService $urlUtilityService,
		private WikiConfigDataService $wikiConfigDataService,
		private WikiVariablesDataService $wikiVariablesDataService,
		private UserFactory $userFactory
	) {
	}

	/** @return Notification[] */
	public function fromResponse( array $response ): array {
		$dissmissals = [];
		foreach ( $response['data'] as $data ) {
			$dissmissals[(int)$data['relationships']['notification']['data']['id']] =
				(int)$data['attributes']['dismissed-at'];
		}
		$notifications = [];
		foreach ( $response['included'] as $data ) {
			$id = (int)$data['id'];
			$notifications[$id] = new Notification(
				$this->config,
				$this->notificationListService,
				$this->urlUtilityService,
				$this->wikiConfigDataService,
				$this->wikiVariablesDataService,
				$this->userFactory,
				$data['attributes']['type'] ?? '',
				$data['attributes']['message'] ?? '',
				$data['attributes']['url'] ?? '',
				$id,
				(int)( $data['attributes']['created-at'] ?? '' ),
				$dissmissals[$id],
				$data['attributes']['origin-id'] ?? '',
				$data['attributes']['agent-id'] ?? ''
			);
		}
		return $notifications;
	}

	public function forEmail( string $message, string $type, string $canonicalUrl ): Notification {
		return new Notification(
			$this->config,
			$this->notificationListService,
			$this->urlUtilityService,
			$this->wikiConfigDataService,
			$this->wikiVariablesDataService,
			$this->userFactory,
			$type,
			$message,
			$canonicalUrl
		);
	}
}
