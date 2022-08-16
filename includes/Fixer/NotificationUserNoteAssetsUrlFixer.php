<?php

declare( strict_types=1 );

namespace Reverb\Fixer;

final class NotificationUserNoteAssetsUrlFixer
{
	public function fix( array $notification ): array {
		if ( !isset( $notification['user_note'] ) ) {
			return $notification;
		}

		$notification['user_note'] = preg_replace(
			'/\/extensions-ucp(?!\/v2\/)\//',
			'/extensions-ucp/v2/',
			$notification['user_note']
		);

		return $notification;
	}
}
