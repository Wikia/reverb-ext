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

		$notification['user_note'] = $this->replaceNotificationImgAssetUrl($notification['user_note']);

		// replace relative path with full urls, so notifications displayed outside of wiki context
		// (i.e. fandom.com page) work well
		$notification['user_note'] = preg_replace(
			'/\/extensions-ucp\//',
			$this->domain . '/extensions-ucp/',
			$notification['user_note']
		);

		return $notification;
	}

	private function replaceNotificationImgAssetUrl($userNote): array|string|null
	{
		$regex = '/(<img\s+[^>]*?\bsrc=["\'].*?)((?!.*\/extensions-ucp\/mw139\/.*).*|^(?=.*\/extensions-ucp\/v2\/.*$).*|^(?=.*\/extensions-ucp\/[^\/]+\/.*$))/m';

		return preg_replace_callback($regex, function ($matches) {
			$match = $matches[0];

			if (str_contains($match, '/extensions-ucp/mw139/')) {
				return $match;
			} elseif (str_contains($match, '/extensions-ucp/v2/mw139/')) {
				return str_replace('/extensions-ucp/v2/mw139/', '/extensions-ucp/mw139/', $match);
			} elseif (str_contains($match, '/extensions-ucp/v2/')) {
				return str_replace('/extensions-ucp/v2/', '/extensions-ucp/mw139/', $match);
			} else {
				return str_replace('/extensions-ucp/', '/extensions-ucp/mw139/', $match);
			}
		}, $userNote);
	}
}
