<?php
/**
 * Reverb
 * NotificationBroadcast
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace Reverb\Notification;

use Exception;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\User;
use MWException;
use Psr\Log\LoggerInterface;
use Reverb\Identifier\IdentifierService;

class NotificationBroadcast {
	private readonly LoggerInterface $logger;

	public function __construct(
		private readonly IdentifierService $identifierService,
		private readonly NotificationEmail $notificationEmail,
		private readonly NotificationService $notificationService,
		private readonly array $attributes,
		private readonly string $origin,
		private readonly array $targetIds,
		private readonly array $targets,
		private readonly ?\MediaWiki\User\User $agent
	) {
		$this->logger = LoggerFactory::getInstance( __CLASS__ );
	}

	/**
	 * @deprecated (for removal in next upgrade) use NotificationBroadcastFactory instead
	 *
	 * Get a new instance for a broadcast to a single target for the system user.
	 *
	 * @param string $type Notification Type
	 * @param User|null $agent User that triggered the creation of the notification.
	 *                            Null to indicate the system user.  An user with no ID will automatically set null.
	 * @param User|array $targets User or Users that the notification is targeting.
	 * @param array $meta Meta data attributes such as 'url' and 'message' parameters.
	 *
	 * @return self|null
	 * @throws MWException
	 */
	public static function new( string $type, ?User $agent, $targets, array $meta ): ?self {
		if ( $targets instanceof User ) {
			$targets = [ $targets ];
		}

		return MediaWikiServices::getInstance()->getService( NotificationBroadcastFactory::class )
			->new( $type, $agent, $targets, $meta );
	}

	/**
	 * @deprecated (for removal in next upgrade) use NotificationBroadcastFactory instead
	 *
	 * Get a new instance for a broadcast to a single target.
	 *
	 * @param string $type Notification Type
	 * @param User $agent User that triggered the creation of the notification.
	 * @param User $target User that the notification is targeting.
	 * @param array $meta Meta data attributes such as 'url' and 'message' parameters for building language strings.
	 *
	 * @return self|null
	 * @throws MWException
	 */
	public static function newSingle( string $type, User $agent, User $target, array $meta ): ?self {
		return MediaWikiServices::getInstance()->getService( NotificationBroadcastFactory::class )
			->newSingle( $type, $agent, $target, $meta );
	}

	/**
	 * @deprecated (for removal in next upgrade) use NotificationBroadcastFactory instead
	 *
	 * Get a new instance for a broadcast to multiple targets.
	 *
	 * @param string $type Notification Type
	 * @param User $agent User that triggered the creation of the notification.
	 * @param array $targets Users that the notification is targeting.
	 * @param array $meta Meta data attributes such as 'url' and 'message' parameters for building language strings.
	 *
	 * @return self|null
	 * @throws MWException
	 */
	public static function newMulti( string $type, User $agent, array $targets, array $meta ): ?self {
		return MediaWikiServices::getInstance()->getService( NotificationBroadcastFactory::class )
			->newMulti( $type, $agent, $targets, $meta );
	}

	/**
	 * @deprecated (for removal in next upgrade) use NotificationBroadcastFactory instead
	 *
	 * Get a new instance for a broadcast to a single target for the system user.
	 *
	 * @param string $type Notification Type
	 * @param User $target User that the notification is targeting.
	 * @param array $meta Meta data attributes such as 'url' and 'message' parameters for building language strings.
	 *
	 * @return self|null
	 * @throws MWException
	 */
	public static function newSystemSingle( string $type, User $target, array $meta ): ?self {
		return MediaWikiServices::getInstance()->getService( NotificationBroadcastFactory::class )
			->newSystemSingle( $type, $target, $meta );
	}

	public function getTargets(): array {
		return $this->targets;
	}

	public function getAttributes(): array {
		return $this->attributes;
	}

	public function getAgent(): ?User {
		return $this->agent;
	}

	/**
	 * Transmit the broadcast.
	 *
	 * @return bool Operation Success
	 */
	public function transmit(): bool {
		$this->notificationEmail->send( $this );

		if ( empty( $this->targetIds ) ) {
			return true;
		}

		try {
			$this->notificationService->broadcastNotification(
				array_merge( $this->attributes, [
					'origin-id' => $this->origin,
					'agent-id' => $this->agent ?
						$this->identifierService->forUser( (string)$this->agent->getId() ) :
						null,
					'target-ids' => $this->targetIds,
				] )
			);
		} catch ( Exception $e ) {
			$this->logger->error( "Failed to broadcast notification. {$e->getMessage()}", [ 'exception' => $e ] );
			return false;
		}

		return true;
	}
}
