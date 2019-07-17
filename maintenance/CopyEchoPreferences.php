<?php
/**
 * Reverb
 * Copy Echo Preferences Maintenance Script
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license MIT
 **/

require_once dirname(__DIR__, 3) . "/maintenance/Maintenance.php";

namespace Reverb\Maintenance;

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

		$this->addOption('final', 'Actually perform the copy instead of testing.', false, false);
	}

	/**
	 * Perform preference copy.
	 *
	 * @return void
	 */
	public function execute() {
		$preferenceMap = [
			'echo-cross-wiki-notifications' => false,
			'' => 'reverb-article-edit-email-revert',
			'' => 'reverb-article-edit-email-thanks',
			'' => 'reverb-article-edit-web-revert',
			'' => 'reverb-article-edit-web-thanks',
			'echo-email-frequency' => 'reverb-email-frequency',
			'' => 'reverb-site-management-email-tools',
			'' => 'reverb-site-management-email-wiki-edit',
			'' => 'reverb-site-management-web-tools',
			'' => 'reverb-site-management-web-wiki-edit',
			'' => 'reverb-user-account-email-groups-changed',
			'' => 'reverb-user-account-email-groups-expiration-change',
			'' => 'reverb-user-account-web-groups-changed',
			'' => 'reverb-user-account-web-groups-expiration-change',
			'' => 'reverb-user-interest-email-page-linked',
			'' => 'reverb-user-interest-email-profile-comment',
			'' => 'reverb-user-interest-email-profile-friendship',
			'' => 'reverb-user-interest-email-talk-page-edit',
			'' => 'reverb-user-interest-email-thanks',
			'' => 'reverb-user-interest-email-welcome',
			'' => 'reverb-user-interest-web-page-linked',
			'' => 'reverb-user-interest-web-profile-comment',
			'' => 'reverb-user-interest-web-profile-friendship',
			'' => 'reverb-user-interest-web-talk-page-edit',
			'' => 'reverb-user-interest-web-thanks',
			'' => 'reverb-user-interest-web-welcome ',
		];

		$results = $db->select(
			['user_properties'],
			[
				'*'
			],
			[
				"up_property LIKE 'echo-%'"
			],
			__METHOD__
		);

		while ($row = $results->fetchRow()) {
			if (!isset($preferenceMap[$row['up_property']])) {
				$this->output("Skipping unknown preference {$row['up_property']}");
				continue;
			}

			if (!$preferenceMap[$row['up_property']]) {
				// Skipping a preference we do not care about.
				continue;
			}

			$success = false;

			if ($this->hasOption('final')) {
				$success = $db->update(
					'wiki_sites',
					$row,
					['md5_key' => $md5Key],
					__METHOD__
				);
			}
			$this->output("Updating {$md5Key}... " . var_export($success, true) . "\n");
		}
	}
}

$maintClass = "CopyEchoPreferences";
require_once RUN_MAINTENANCE_IF_MAIN;
