<?php

declare( strict_types=1 );

namespace Reverb\Fixer;

final class NotificationUserNoteAssetsUrlFixer {
	private const REGEX =
		'/(?:<img\s+[^>]*?\bsrc=["\'].*(?<extPath>\/extensions-ucp(?:\/v2)?(?:\/mw[0-9]{3})?))/m';

	public function __construct( private readonly string $domain ) {
	}

	public function fix( array $notification, $extensionAssetsPath ): array {
		if ( !isset( $notification['user_note'] ) ) {
			return $notification;
		}

		$notification['user_note'] = $this->replaceNotificationImgAssetUrl(
			$notification['user_note'],
			$extensionAssetsPath
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

	private function replaceNotificationImgAssetUrl( $userNote, $extensionAssetsPath ): array|string|null {
		return preg_replace_callback( self::REGEX, static function ( $matches ) use ( $extensionAssetsPath
		): string|array {
			return str_replace( $matches['extPath'], $extensionAssetsPath, $matches[0] );
		}, $userNote );
	}
}
