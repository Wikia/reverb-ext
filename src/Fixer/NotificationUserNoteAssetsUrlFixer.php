<?php

declare( strict_types=1 );

namespace Reverb\Fixer;

final class NotificationUserNoteAssetsUrlFixer {
	public function __construct( private string $domain ) {
	}

	public function fix( array $notification ): array {
		if ( !isset( $notification['user_note'] ) ) {
			return $notification;
		}

		$notification['user_note'] = preg_replace(
			'/\/extensions-ucp(?!\/v2\/)\//',
			'/extensions-ucp/v2/',
			$notification['user_note']
		);

		// replace relative path with full urls, so notifications displayed outside of wiki context
		// (i.e. fandom.com page) work well
		$notification['user_note'] = preg_replace(
			'/\/extensions-ucp\//',
			$this->domain . '/extensions-ucp/',
			$notification['user_note']
		);

		return $notification;
	}
}
