<?php

namespace Reverb\Notification;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\User\User;
use Psr\Log\LoggerInterface;
use Reverb\Identifier\IdentifierService;

class NotificationService {
	private readonly LoggerInterface $logger;

	public function __construct(
		private readonly IdentifierService $identifierService,
		private readonly NotificationClient $notificationClient,
		private readonly NotificationFactory $notificationFactory
	) {
		$this->logger = LoggerFactory::getInstance( __CLASS__ );
	}

	public function broadcastNotification( array $attributes ): void {
		$this->notificationClient->broadcastNotification( $attributes );
	}

	private function validateNotificationBundleFilters( array $filters ): array {
		$validFilters = [
			'read' => 'intval',
			'unread' => 'intval',
			'type' => 'strval',
		];

		$filters = array_intersect_key( $filters, $validFilters );

		foreach ( $filters as $key => $filter ) {
			$filters[$key] = $validFilters[$key]( $filter );
		}

		return $filters;
	}

	/**
	 * @param User $user
	 * @param array $filters [Optional] Filters for notifications.
	 *                            [
	 *                            'read' => 1, // 1 only
	 *                            'unread' => 1, // 1 only
	 *                            'type' => article-edit-revert // Accepts comma separated notification types.
	 *                            ]
	 * @param int $itemsPerPage
	 * @param int $pageNumber
	 * @return NotificationBundle|null
	 */
	public function getNotificationBundle(
		User $user,
		array $filters,
		int $itemsPerPage,
		int $pageNumber
	): ?NotificationBundle {
		if ( $user->isBot() || empty( $user->getId() ) ) {
			return null;
		}

		$resourceId = $this->identifierService->forUser( $user->getId() );
		$filters = $this->validateNotificationBundleFilters( $filters );
		try {
			$response = $this->notificationClient->getNotificationBundle(
				$resourceId,
				$filters,
				$itemsPerPage,
				$pageNumber
			);
			$notifications = $this->notificationFactory->fromResponse( $response );
			return new NotificationBundle(
				$notifications,
				$itemsPerPage,
				$pageNumber,
				count( $notifications ),
				(int)( $response['meta']['unread-count'] ?? 0 ),
				(int)( $response['meta']['read-count'] ?? 0 ),
			);
		} catch ( Exception $e ) {
			wfLogWarning( 'General exception encountered when communicating with the service: ' . $e->getMessage() );
			return null;
		}
	}

	public function dismissAllNotifications( User $user ): void {
		$userIdentifier = $this->identifierService->forUser( (string)$user->getId() );

		try {
			$this->notificationClient->dismissAllNotifications( $userIdentifier );
		} catch ( GuzzleException $e ) {
			$this->logger->warning( "Failed to call Reverb service. {$e->getMessage()}", [ 'exception' => $e ] );
			throw $e;
		}
	}

	public function dismissNotification( User $user, string $id, int $timestamp ): void {
		$userIdentifier = $this->identifierService->forUser( (string)$user->getId() );

		try {
			$this->notificationClient->dismissNotification( "$userIdentifier:$id", $timestamp );
		} catch ( GuzzleException $e ) {
			$this->logger->warning( "Failed to call Reverb service. {$e->getMessage()}", [ 'exception' => $e ] );
			throw $e;
		}
	}
}
