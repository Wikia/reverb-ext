<?php
/**
 * Reverb
 * NotificationBundle
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace Reverb\Notification;

class NotificationBundle {
	/** @param Notification[] $notifications */
	public function __construct(
		private readonly array $notifications,
		private readonly int $itemsPerPage,
		private readonly int $pageNumber,
		private readonly int $totalThisPage,
		private readonly int $unreadCount,
		private readonly int $readCount
	) {
	}

	public function getNotifications(): array {
		return $this->notifications;
	}

	public function getTotalAll(): int {
		return $this->unreadCount + $this->readCount;
	}

	public function getTotalThisPage(): int {
		return $this->totalThisPage;
	}

	public function getUnreadCount(): int {
		return $this->unreadCount;
	}

	public function getReadCount(): int {
		return $this->readCount;
	}

	public function getPageNumber(): int {
		return $this->pageNumber;
	}

	public function getItemsPerPage(): int {
		return $this->itemsPerPage;
	}
}
