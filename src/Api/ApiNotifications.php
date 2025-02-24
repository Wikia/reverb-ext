<?php
/**
 * Reverb
 * Notifications API
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace Reverb\Api;

use Exception;
use MediaWiki\Api\ApiBase;
use MediaWiki\Config\Config;
use MediaWiki\MainConfigNames;
use Reverb\Fixer\NotificationUserNoteAssetsUrlFixer;
use Reverb\Notification\NotificationService;
use Wikimedia\ParamValidator\ParamValidator;

class ApiNotifications extends ApiBase {
	private const DO_PARAM = 'do';
	private const PAGE_PARAM = 'page';
	private const ITEMS_PER_PAGE_PARAM = 'itemsPerPage';
	private const TYPE_PARAM = 'type';
	private const READ_PARAM = 'read';
	private const UNREAD_PARAM = 'unread';
	private const NOTIFICATION_ID_PARAM = 'notificationId';
	private const DISMISSED_AT_PARAM = 'dismissedAt';

	public function __construct(
		$query,
		$moduleName,
		private readonly Config $config,
		private readonly NotificationService $notificationService,
		private readonly NotificationUserNoteAssetsUrlFixer $notificationUserNoteAssetsUrlFixer
	) {
		parent::__construct( $query, $moduleName );
	}

	public function execute(): void {
		if ( !$this->getUser()->isRegistered() ) {
			$this->dieWithError( [ 'apierror-permissiondenied-generic' ] );
		}
		$params = $this->extractRequestParams();

		$response = match ( $params[self::DO_PARAM] ) {
			'getNotificationsForUser' => $this->getNotificationsForUser( $params ),
			'dismissNotification' => $this->dismissNotification(
				$params[self::NOTIFICATION_ID_PARAM],
				$params[self::DISMISSED_AT_PARAM]
			),
			'dismissAllNotifications' => $this->dismissAllNotifications(),
			default => $this->dieWithError( [ 'invaliddo', $params[self::DO_PARAM] ] )
		};

		foreach ( $response as $key => $value ) {
			$this->getResult()->addValue( null, $key, $value );
		}
	}

	public function getNotificationsForUser( array $params ): array {
		$filters = [];
		if ( $params[self::READ_PARAM] === 1 ) {
			$filters['read'] = 1;
		}
		if ( $params[self::UNREAD_PARAM] === 1 ) {
			$filters['unread'] = 1;
		}
		if ( !empty( $params[self::TYPE_PARAM] ) ) {
			$types = explode( ',', $params[self::TYPE_PARAM] );
			foreach ( $types as $key => $type ) {
				if ( !$this->isTypeConfigured( $type ) ) {
					unset( $types[$key] );
				}
			}
			if ( !empty( $types ) ) {
				$filters['type'] = implode( ',', $types );
			}
		}

		$bundle = $this->notificationService->getNotificationBundle(
			$this->getUser(),
			$filters,
			// Make sure this is from 1 to 100.
			max( min( $params[self::ITEMS_PER_PAGE_PARAM], 100 ), 1 ),
			// Make sure the page number is >= 0.
			max( 0, $params[self::PAGE_PARAM] )
		);

		$result = [ 'notifications' => [] ];
		if ( $bundle === null ) {
			return $result;
		}

		foreach ( $bundle->getNotifications() as $key => $notification ) {
			$result['notifications'][] = $this->notificationUserNoteAssetsUrlFixer->fix(
				$notification->toArray(),
				$this->config->get( MainConfigNames::ExtensionAssetsPath )
			);
		}
		$result['meta'] = [
			'unread' => $bundle->getUnreadCount(),
			'read' => $bundle->getReadCount(),
			'total_this_page' => $bundle->getTotalThisPage(),
			'total_all' => $bundle->getTotalAll(),
			'page' => $bundle->getPageNumber(),
			'items_per_page' => $bundle->getItemsPerPage(),
		];

		return $result;
	}

	private function isTypeConfigured( string $type ): bool {
		return isset( $this->config->get( 'ReverbNotifications' )[$type] );
	}

	public function dismissNotification( ?string $notificationId, ?int $dismissedAt ): array {
		if ( !$this->getRequest()->wasPosted() ) {
			$this->dieWithError( [ 'apierror-mustbeposted', __FUNCTION__ ] );
		}

		$timestamp = $dismissedAt ?? time();
		if ( !empty( $notificationId ) ) {
			try {
				$this->notificationService->dismissNotification( $this->getUser(), $notificationId, $timestamp );
				return [ 'success' => true ];
			} catch ( Exception ) {
			}
		}

		return [ 'success' => false ];
	}

	public function dismissAllNotifications(): array {
		if ( !$this->getRequest()->wasPosted() ) {
			$this->dieWithError( [ 'apierror-mustbeposted', __FUNCTION__ ] );
		}

		try {
			$this->notificationService->dismissAllNotifications( $this->getUser() );
			return [ 'success' => true ];
		} catch ( Exception ) {
			return [ 'success' => false ];
		}
	}

	/** @inheritDoc */
	public function getAllowedParams() {
		return [
			self::DO_PARAM => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			],
			self::PAGE_PARAM => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_DEFAULT => 0,
			],
			self::ITEMS_PER_PAGE_PARAM => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => true,
				ParamValidator::PARAM_DEFAULT => 50,
			],
			self::TYPE_PARAM => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => null,
			],
			self::READ_PARAM => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => null,
			],
			self::UNREAD_PARAM => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => null,
			],
			self::NOTIFICATION_ID_PARAM => [
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => null,
			],
			self::DISMISSED_AT_PARAM => [
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => null,
			],
		];
	}

	/** @inheritDoc */
	protected function getExamplesMessages() {
		return [
			'action=notifications&do=getNotificationsForUser&page=0&itemsPerPage=50' =>
				'apihelp-notifications-getNotificationsForUser-example',
			'action=notifications&do=dismissNotification&notificationId=1&dismissedAt=1562006555' =>
				'apihelp-notifications-dismissNotification-example',
			'action=notifications&do=dismissAllNotifications' =>
				'apihelp-notifications-dismissAllNotifications-example',
		];
	}

	/** @inheritDoc */
	public function getHelpUrls() {
		return '';
	}
}
