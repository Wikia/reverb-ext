<?php
/**
 * Reverb
 * Special:Notification
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 **/

declare(strict_types=1);

namespace Reverb\Special;

use SpecialPage;
use MediaWiki\MediaWikiServices;

class SpecialNotifications extends SpecialPage {
	/**
	 * Main Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct('Notifications');

		$this->output = $this->getOutput();

		$this->icons = [
			"articleCheck.svg",
			"bell.svg",
			"edit.svg",
			"feedback.svg",
			"help.svg",
			"mention-failure.svg",
			"mention-success.svg",
			"message.svg",
			"revert.svg",
			"tray.svg",
			"user-speech-bubble.svg",
			"changes.svg",
			"edit-user-talk.svg",
			"global.svg",
			"link.svg",
			"mention-status-bundle.svg",
			"mention.svg",
			"notice.svg",
			"speechBubbles.svg",
			"user-rights.svg",
			"userTalk.svg"
		];

		$this->types = [
			"wiki_edit" => [
				"title" => "Wiki Edits",
				"icon"	=> ""
			],
			"edit_revert" => [
				"title" => "Edit Revert",
				"icon"	=> ""
			],
			"talk_page_message" => [
				"title" => "Talk Page Message",
				"icon"	=> ""
			],
			"profile_comments" => [
				"title" => "Profile Comments",
				"icon"	=> ""
			],
			"friendship_request" => [
				"title" => "Friendship Request",
				"icon"	=> ""
			],
			"new_wiki_claims" => [
				"title" => "New Wiki Claims",
				"icon"	=> ""
			],
			"wiki_tool_queues" => [
				"title" => "Wiki Tool Queues",
				"icon"	=> ""
			],
			"comment_reports" => [
				"title" => "Comment Reports",
				"icon"	=> ""
			],
			"page_link" => [
				"title" => "Page Link",
				"icon"	=> ""
			],
			"thanks" => [
				"title" => "Thanks",
				"icon"	=> ""
			],
			"achievements" => [
				"title" => "Achievements",
				"icon"	=> ""
			],
			"mention" => [
				"title" => "Mention",
				"icon"	=> ""
			],
			"email_from_another_user" => [
				"title" => "Email From Another User",
				"icon"	=> ""
			],
			"user_rights_change" => [
				"title" => "User Rights Change",
				"icon"	=> ""
			]
		];
	}

	/**
	 * Main Executor
	 *
	 * @param string|null $subpage Sub page passed in the URL.
	 *
	 * @return void [Outputs to screen]
	 */
	public function execute($subpage) {
		$twig = MediaWikiServices::getInstance()->getService( 'TwiggyService' );
		$template = $twig->load('@Reverb/notifications.twig');
		$this->output->addHtml($template->render(['types' => $this->types]));
	}

	/**
	 * Builds HTML for a notification row.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function notificationRow($data) {
		$header = $data['header'];
		$body = $data['body'];
		$lastread = "1 day ago";
		$read = $data['read'] ? "read" : "unread";
		$icon = $data['icon'];

		return "
            <div class=\"reverb-npn-row\">
                <div class=\"reverb-npnr-left\">
                    <img src=\"/extensions/Reverb/resources/icons/${icon}\" class=\"reverb-icon\" />
                </div>
                <div class=\"reverb-npnr-right\">
                    <div class=\"reverb-npnr-header\">${header}</div>
                    <div class=\"reverb-npnr-body\">${body}</div>
                    <div class=\"reverb-npnr-bottom\">
                        <span class=\"reverb-npnr-${read}\"></span>
                        ${lastread}
                    </div>
                </div>
            </div>
        ";
	}

	/**
	 * Hides special page from SpecialPages special page.
	 *
	 * @return boolean
	 */
	public function isListed() {
		return $this->getUser()->isLoggedIn();
	}

	/**
	 * Lets others determine that this special page is restricted.
	 *
	 * @return boolean
	 */
	public function isRestricted(): bool {
		return true;
	}

	/**
	 * Return the group name for this special page.
	 *
	 * @return string
	 */
	protected function getGroupName(): string {
		return 'user';
	}
}
