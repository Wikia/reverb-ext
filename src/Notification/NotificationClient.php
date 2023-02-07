<?php

namespace Reverb\Notification;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class NotificationClient {
	public function __construct(
		private Client $httpClient,
		private string $serviceUrl,
		private array $headers
	) {
	}

	public function broadcastNotification( array $attributes ): void {
		$data = [
			'data' => [
				'type' => 'notification-broadcasts',
				'attributes' => $attributes,
			],
		];

		$this->httpClient->post(
			"$this->serviceUrl/notification-broadcasts",
			[
				RequestOptions::BODY => json_encode( $data ),
				RequestOptions::HEADERS => $this->headers,
			]
		);
	}

	public function getNotificationBundle(
		string $resourceId,
		array $filters,
		int $itemsPerPage,
		int $pageNumber
	): array {
		$query = http_build_query( [
			'page' => [
				'limit' => $itemsPerPage,
				'offset' => $itemsPerPage * $pageNumber,
			],
			'filter' => array_merge( $filters, [ 'target-id' => $resourceId ] )
		] );
		$response = $this->httpClient->get(
			"$this->serviceUrl/notification-targets",
			[
				RequestOptions::QUERY => $query,
				RequestOptions::HEADERS => $this->headers,
			]
		);
		return json_decode( (string)$response->getBody(), true );
	}

	public function dismissAllNotifications( string $resourceId ): void {
		$data = [
			'data' => [
				'type' => 'notification-dismissals',
				'attributes' => [
					'target-id' => $resourceId,
				],
			],
		];

		$this->httpClient->post(
			"$this->serviceUrl/notification-dismissals",
			[
				RequestOptions::BODY => json_encode( $data ),
				RequestOptions::HEADERS => $this->headers,
			]
		);
	}

	public function dismissNotification( string $resourceId, int $timestamp ): void {
		$data = [
			'data' => [
				'type' => 'notification-targets',
				'id' => $resourceId,
				'attributes' => [
					'created-at' => null,
					'dismissed-at' => $timestamp,
					'target-id' => $resourceId,
				],
			],
		];

		$this->httpClient->patch(
			"$this->serviceUrl/notification-targets/$resourceId",
			[
				RequestOptions::BODY => json_encode( $data ),
				RequestOptions::HEADERS => $this->headers,
			]
		);
	}
}
