<?php

namespace Reverb\Notification;

use Config;
use MWException;
use Reverb\Identifier\IdentifierService;
use User;

class NotificationBroadcastFactory {
	public function __construct(
		private Config $config,
		private NotificationListService $notificationListService,
		private IdentifierService $identifierService,
		private NotificationEmail $notificationEmail,
		private NotificationService $notificationService
	) {
	}

	/**
	 * @param string $type
	 * @param ?User $agent
	 * @param User[] $targets
	 * @param array $meta
	 */
	public function new( string $type, ?User $agent, array $targets, array $meta ): ?NotificationBroadcast {
		if ( $agent !== null && $agent->isRegistered() ) {
			return $this->newMulti( $type, $agent, $targets, $meta );
		}

		return $this->newSystemMulti( $type, $targets, $meta );
	}

	public function newSingle( string $type, User $agent, User $target, array $meta ): ?NotificationBroadcast {
		return $this->newMulti( $type, $agent, [ $target ], $meta );
	}

	public function newSystemSingle( string $type, User $target, array $meta ): ?NotificationBroadcast {
		return $this->newSystemMulti( $type, [ $target ], $meta );
	}

	/**
	 * @param string $type
	 * @param User $agent
	 * @param User[] $targets
	 * @param array $meta
	 */
	public function newMulti( string $type, User $agent, array $targets, array $meta ): ?NotificationBroadcast {
		if ( $agent->isAnon() ) {
			return null;
		}

		return $this->create( $type, $targets, $meta, $agent );
	}

	private function newSystemMulti( string $type, array $targets, array $meta ): ?NotificationBroadcast {
		return $this->create( $type, $targets, $meta );
	}

	private function create( string $type, array $targets, array $meta, ?User $agent = null ): ?NotificationBroadcast {
		if ( !$this->isTypeConfigured( $type ) ) {
			throw new MWException( 'The notification type passed is not defined.' );
		}

		if ( empty( $meta['url'] ) ) {
			throw new MWException( 'No canonical URL passed for broadcast.' );
		}

		[ $sanitizedTargetIds, $sanitizedTargets ] = $this->sanitizeTargets( $targets, $type );
		if ( empty( $sanitizedTargets ) ) {
			return null;
		}

		return new NotificationBroadcast(
			$this->identifierService,
			$this->notificationEmail,
			$this->notificationService,
			[
				'type' => $type,
				'url' => $meta['url'],
				'message' => json_encode( $meta['message'] ),
				'created-at' => null,
			],
			$this->identifierService->forLocalSite(),
			$sanitizedTargetIds,
			$sanitizedTargets,
			$agent
		);
	}

	/**
	 * @param User[] $targets
	 * @param string $type
	 * @return array[] [ $targetIdentifiers, $validTargets ]
	 * $targetIdentifiers - identifiers of users that should be notified
	 * $validTargets - all registered users
	 * This values does NOT have to match (eg. there can be more $validTargets than $targetIdentifiers)
	 *
	 * @throws MWException
	 */
	private function sanitizeTargets( array $targets, string $type ): array {
		$targetIdentifiers = [];
		$validTargets = [];
		foreach ( $targets as $target ) {
			if ( !( $target instanceof User ) ) {
				throw new MWException( 'Invalid target passed.' );
			}

			if ( $target->isAnon() ) {
				continue;
			}
			$validTargets[] = $target;

			if ( $this->notificationListService->shouldNotify( $target, $type, 'web' ) ) {
				$targetIdentifiers[] = $this->identifierService->forUser( (string)$target->getId() );
			}
		}

		return [ $targetIdentifiers, $validTargets ];
	}

	/**
	 * Is this a valid configured notification type?
	 *
	 * @param string $type Notification Type
	 *
	 * @return bool
	 */
	private function isTypeConfigured( string $type ): bool {
		return isset( $this->config->get( 'ReverbNotifications' )[$type] );
	}
}
