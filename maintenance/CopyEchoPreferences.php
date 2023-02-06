<?php
/**
 * Reverb
 * Copy Echo Preferences Maintenance Script
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license MIT
 */

namespace Reverb\Maintenance;

require_once dirname( __DIR__, 3 ) . "/maintenance/Maintenance.php";

use Maintenance;

class CopyEchoPreferences extends Maintenance {
	/**
	 * Main Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
		$this->mDescription = "CopyEchoPreferences";

		$this->addOption( 'final', 'Actually perform the copy instead of testing.', false, false );
	}

	/**
	 * Perform preference copy.
	 *
	 * @return void
	 */
	public function execute() {
		// @TODO: Waiting on Sam's WIP merge request for ClaimWiki.
		$preferenceMap = [
			'echo-subscriptions-email-mention' => false,
			'echo-subscriptions-web-mention' => false,
			'echo-subscriptions-web-emailuser' => false,
			'echo-cross-wiki-notifications' => false,
			'echo-email-frequency' => 'reverb-email-frequency',
			'echo-subscriptions-email-article-linked' => 'reverb-user-interest-email-page-linked',
			'echo-subscriptions-email-edit-thank' => 'reverb-user-interest-email-thanks',
			'echo-subscriptions-email-edit-user-talk' => 'reverb-user-interest-email-talk-page-edit',
			'echo-subscriptions-email-profile-comment' => 'reverb-user-interest-email-profile-comment',
			'echo-subscriptions-email-profile-friendship' => 'reverb-user-interest-email-profile-friendship',
			'echo-subscriptions-email-profile-report' => 'reverb-user-moderation-email-profile-comment-report',
			'echo-subscriptions-email-reverted' => 'reverb-article-edit-email-revert',
			'echo-subscriptions-email-thank-you-edit' => 'reverb-article-edit-email-thanks',
			'echo-subscriptions-email-dynamicsettings-tools' => 'reverb-site-management-email-tools',
			'echo-subscriptions-email-user-rights' => 'reverb-user-account-email-groups-changed',
			'echo-subscriptions-email-user-rights-expiry-change' =>
				'reverb-user-account-email-groups-expiration-change',
			'echo-subscriptions-email-welcome' => 'reverb-user-interest-email-welcome',
			'echo-subscriptions-email-dynamicsettings-wiki-edit' => 'reverb-site-management-email-wiki-edit',
			'echo-subscriptions-web-article-linked' => 'reverb-user-interest-web-page-linked',
			'echo-subscriptions-web-edit-thank' => 'reverb-user-interest-web-thanks',
			'echo-subscriptions-web-edit-user-talk' => 'reverb-user-interest-web-talk-page-edit',
			'echo-subscriptions-web-profile-comment' => 'reverb-user-interest-web-profile-comment',
			'echo-subscriptions-web-profile-friendship' => 'reverb-user-interest-web-profile-friendship',
			'echo-subscriptions-web-profile-report' => 'reverb-user-moderation-web-profile-comment-report',
			'echo-subscriptions-web-reverted' => 'reverb-article-edit-web-revert',
			'echo-subscriptions-web-thank-you-edit' => 'reverb-article-edit-web-thanks',
			'echo-subscriptions-web-dynamicsettings-tools' => 'reverb-site-management-web-tools',
			'echo-subscriptions-web-user-rights' => 'reverb-user-account-web-groups-changed',
			'echo-subscriptions-web-user-rights-expiry-change' => 'reverb-user-account-web-groups-expiration-change',
			'echo-subscriptions-web-welcome' => 'reverb-user-interest-web-welcome ',
			'echo-subscriptions-web-dynamicsettings-wiki-edit' => 'reverb-site-management-web-wiki-edit',
			'enotifwatchlistpages' => 'reverb-article-edit-email-watch',
		];

		$db = wfGetDB( DB_MASTER );

		$results = $db->select( [ 'user_properties' ], [
				'*',
			], [
				"up_property LIKE 'echo-%' OR up_property = 'enotifwatchlistpages'",
			], __METHOD__ );

		while ( $row = $results->fetchRow() ) {
			if ( !isset( $preferenceMap[$row['up_property']] ) ) {
				$this->output( "Skipping unknown preference {$row['up_property']}\n" );
				continue;
			}

			if ( !$preferenceMap[$row['up_property']] ) {
				// Skipping a preference we do not care about.
				continue;
			}

			$insert = [
				'up_user' => $row['up_user'],
				'up_property' => $preferenceMap[$row['up_property']],
				'up_value' => $row['up_value'],
			];

			$success = false;
			if ( $this->hasOption( 'final' ) ) {
				$success = $db->upsert( 'user_properties', [
															   'up_user' => $row['up_user'],
														   ] + $insert, [ 'PRIMARY' ], $insert, __METHOD__ );
			}
			$this->output( "Insert " . json_encode( $insert ) . "... " . var_export( $success, true ) . "\n" );
		}
	}
}

$maintClass = 'Reverb\Maintenance\CopyEchoPreferences';
require_once RUN_MAINTENANCE_IF_MAIN;
