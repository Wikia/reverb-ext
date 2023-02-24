<?php
/**
 * Reverb
 * ReverbHookHandler
 * Includes MIT licensed code from Extension:Echo.
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license MIT
 */

declare( strict_types=1 );

namespace Reverb;

use Config;
use Fandom\FandomDesktop\PageHeaderActions;
use MediaWiki\Hook\AbortTalkPageEmailNotificationHook;
use MediaWiki\Hook\BeforeInitializeHook;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\GetNewMessagesAlertHook;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use RequestContext;
use Reverb\Notification\NotificationListService;
use SpecialPage;
use Title;

class ReverbHookHandler implements
	BeforePageDisplayHook,
	BeforeInitializeHook,
	GetNewMessagesAlertHook,
	GetPreferencesHook,
	AbortTalkPageEmailNotificationHook
{
	public function __construct( private Config $config, private NotificationListService $notificationListService ) {
	}

	/** @inheritDoc */
	public function onBeforePageDisplay( $out, $skin ): void {
		if ( !$out->getTitle()->isSpecial( 'Notifications' ) || $out->getUser()->isAnon() ) {
			return;
		}

		if ( $out->getSkin()->getSkinName() !== 'fandomdesktop' ) {
			$out->addModuleStyles( [ 'ext.reverb.notifications.styles', 'ext.hydraCore.font-awesome.styles' ] );
		}

		$out->addModules( 'ext.reverb.notifications.scripts' );
	}

	/** @inheritDoc */
	public function onBeforeInitialize( $title, $unused, $output, $user, $request, $mediaWiki ) {
		if ( !$this->config->get( 'EnableHydraFeatures' ) ||
			!$title->equals( SpecialPage::getTitleFor( 'Preferences' ) ) ) {
			return;
		}

		$output->addModules( 'ext.reverb.preferences' );
	}

	/**
	 * We're using the GetNewMessagesAlert hook instead of the
	 * ArticleEditUpdateNewTalk hook since we still want the user_newtalk data
	 * to be updated and availble to client-side tools and the API.
	 *
	 * @inheritDoc
	 */
	public function onGetNewMessagesAlert( &$newMessagesAlert, $newtalks, $user, $out ) {
		return !$this->config->get( 'EnableHydraFeatures' );
	}

	/** @inheritDoc */
	public function onGetPreferences( $user, &$preferences ) {
		if ( !$this->config->get( 'EnableHydraFeatures' ) ) {
			return;
		}

		$preferences[ 'reverb-email-frequency' ] = [
			'type' => 'radio',
			'help-message' => 'reverb-pref-email-options-toggle-help',
			'section' => 'reverb/reverb-email-options-toggle',
			'options' => [
				wfMessage( 'reverb-pref-email-frequency-immediately' )->plain() => 1,
				wfMessage( 'reverb-pref-email-frequency-never' )->plain() => 0,
			],
		];

		$preferences[ NotificationListService::getPreferenceKey( 'user-interest-email-user', 'web' ) ] = [
			'type' => 'toggle',
			'label-message' => 'user-interest-email-user',
			'section' => 'reverb/email-user-notification',
		];

		// Setup Check Matrix columns
		$columns = [];
		$reverbNotifiers = $this->config->get( 'ReverbNotifiers' );
		foreach ( $reverbNotifiers as $notifierType => $notifierData ) {
			$formatMessage = wfMessage( 'reverb-pref-' . $notifierType )->escaped();
			$columns[ $formatMessage ] = $notifierType;
		}

		$notifications = $this->notificationListService->organizeNotificationList(
			$user,
			$this->config->get( 'ReverbNotifications' )
		);

		foreach ( $notifications as $group => $notificationType ) {
			$rows = [];
			$tooltips = [];

			foreach ( $notificationType as $key => $notification ) {
				$notificationTitle = wfMessage( 'reverb-pref-title-' . $key )->numParams( 1 )->escaped();
				$rows[ $notificationTitle ] = $notification[ 'name' ];
				$hasTooltip = !wfMessage( 'reverb-pref-tooltip-' . $key )->inContentLanguage()->isBlank();
				if ( $hasTooltip ) {
					$tooltips[ $notificationTitle ] = wfMessage( 'reverb-pref-tooltip-' . $key )->text();
				}
			}

			$preferences[ 'reverb-' . $group ] = [
				'class' => 'HTMLCheckMatrix',
				'section' => 'reverb/reverb-' . $group,
				'rows' => $rows,
				'columns' => $columns,
				'prefix' => 'reverb-' . $group . '-',
				'tooltips' => $tooltips,
			];
		}
		foreach ( $preferences as $index => $preference ) {
			if ( isset( $preference[ 'section' ] ) && $preference[ 'section' ] === 'personal/email' ) {
				$preferences[ $index ][ 'section' ] = 'reverb/reverb-email-options';
			}

			// Reverb supercedes Fandom email preferences, so don't show them in Special:Preferences
			// Note: this depends on Reverb being loaded after fandom extensions
			if ( isset( $preference[ 'section' ] ) && strpos( $preference[ 'section' ], 'emailv2/' ) === 0 ) {
				$preferences[ $index ][ 'type' ] = 'hidden';
				$preferences[ $index ][ 'section' ] = 'reverb/reverb-email-options';
			}
		}
	}

	/**
	 * Abort all talk page emails since that is handled by Reverb now.
	 * @inheritDoc
	 */
	public function onAbortTalkPageEmailNotification( $targetUser, $title ) {
		return !$this->config->get( 'EnableHydraFeatures' );
	}

	public function onPageHeaderActionButtonShouldDisplay( Title $title, bool &$shouldDisplay ) {
		if ( $title->isSpecial( 'Notifications' ) ) {
			$shouldDisplay = true;
		}
	}

	public function onBeforePrepareActionButtons( PageHeaderActions $actionButton, &$contentActions ) {
		$context = RequestContext::getMain();
		$skinName = $context->getSkin()->getSkinName();
		$title = $context->getTitle();

		if ( $skinName !== 'fandomdesktop' || !$title->isSpecial( 'Notifications' ) ) {
			return;
		}

		$enableHydraFeatures = $this->config->get( 'EnableHydraFeatures' );

		$actionButton->setCustomAction(
			[
				'text' => wfMessage( 'preferences' )->text(),
				'href' => SpecialPage::getTitleFor(
					'Preferences',
					false,
					$enableHydraFeatures ? 'mw-prefsection-reverb' : 'mw-prefsection-emailv2'
				)->getFullURL(),
				'id' => 'ca-preferences-notifications',
				'data-tracking' => 'ca-preferences-notifications',
				'icon' => 'wds-icons-gear-small',
			]
		);
	}
}
