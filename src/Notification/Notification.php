<?php
/**
 * Reverb
 * Notification
 *
 * @package Reverb
 * @author  Alexia E. Smith
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace Reverb\Notification;

use Fandom\Includes\Util\UrlUtilityService;
use Fandom\WikiConfig\WikiVariablesDataService;
use Fandom\WikiDomain\WikiConfigData;
use Fandom\WikiDomain\WikiConfigDataService;
use MediaWiki\Config\Config;
use MediaWiki\User\UserFactory;
use Message;

class Notification {
	// Cache of loaded wikis.
	private static array $wikiCache = [];

	public function __construct(
		private readonly Config $config,
		private readonly NotificationListService $notificationListService,
		private readonly UrlUtilityService $urlUtilityService,
		private readonly WikiConfigDataService $wikiConfigDataService,
		private readonly WikiVariablesDataService $wikiVariablesDataService,
		private readonly UserFactory $userFactory,
		private readonly string $type,
		private readonly string $message,
		private readonly string $canonicalUrl,
		private readonly int $id = 0,
		private readonly int $createdAt = 0,
		private readonly int $dismissedAt = 0,
		private readonly ?string $originId = null,
		private readonly ?string $agentId = null
	) {
	}

	public function getId(): int {
		return $this->id;
	}

	public function getType(): string {
		return $this->type;
	}

	public function getCreatedAt(): int {
		return $this->createdAt;
	}

	public function getDismissedAt(): int {
		return $this->dismissedAt;
	}

	public function getCanonicalUrl(): string {
		return $this->canonicalUrl;
	}

	public function getOriginId(): ?string {
		return $this->originId;
	}

	public function getAgentId(): ?string {
		return $this->agentId;
	}

	/**
	 * Get the header for this notification.
	 *
	 * @param bool $long Use the short or long version of the header.
	 *
	 * @return Message
	 */
	public function getHeader( bool $long = false ): Message {
		$parameters = $this->getMessageParameters();
		unset( $parameters['user_note'] );

		// Pad parameters that start at not 1.
		// This fixes issues with legacy Echo language strings missing parameters at the beginning of the string.
		if ( count( $parameters ) > 0 ) {
			$max = max( array_keys( $parameters ) );
			for ( $i = 1; $i < $max; $i++ ) {
				if ( !isset( $parameters[$i] ) ) {
					$parameters[$i] = null;
				}
			}
			ksort( $parameters );
		}

		return wfMessage( ( $long ? 'long' : 'short' ) . '-header-' . $this->type )->params( $parameters );
	}

	/**
	 * If there is an available user note get it from the parameters.
	 *
	 * @return string|null Defined user note or null.
	 */
	public function getUserNote(): ?string {
		return $this->getMessageParameters()['user_note'] ?? null;
	}

	/**
	 * Do any clean up and representation changes on message parameters then return them.
	 *
	 * @return array
	 */
	protected function getMessageParameters(): array {
		$json = json_decode( $this->message, true );

		$parameters = [];
		foreach ( $json as $parameter ) {
			$parameters[$parameter[0]] = $parameter[1];
		}
		ksort( $parameters, SORT_NATURAL );

		return $parameters;
	}

	/**
	 * Return a Wiki object that has wiki information.
	 */
	public function getOrigin(): ?WikiConfigData {
		if ( $this->originId === null ) {
			return null;
		}

		if ( isset( self::$wikiCache[$this->originId] ) ) {
			return self::$wikiCache[$this->originId];
		}

		$wiki = $this->getWikiInformation( $this->originId );
		self::$wikiCache[$this->originId] = $wiki;

		return $wiki;
	}

	/**
	 * Get wiki information based on the provided site identifier.($dsSiteKey or $cityId)
	 * This function is copied from Extension:Cheevos.  We should migrate this into another extension.
	 */
	private function getWikiInformation( string $siteKey ): ?WikiConfigData {
		if ( strlen( $siteKey ) !== 32 ) {

			return $this->wikiConfigDataService->getWikiDataById( (int)$siteKey );
		}

		// Handle legacy $dsSiteKey MD5 hash.
		$variableId = (int)$this->wikiVariablesDataService->getVarIdByName( 'dsSiteKey' );
		if ( !$variableId ) {
			return null;
		}
		$listOfWikisWithVar = $this->wikiVariablesDataService->getListOfWikisWithVar(
			$variableId,
			'=',
			$siteKey,
			'$',
			0,
			1
		);
		if ( $listOfWikisWithVar['total_count'] === 1 ) {
			$cityId = key( $listOfWikisWithVar['result'] );
			return $this->wikiConfigDataService->getWikiDataById( (int)$cityId );
		}

		return null;
	}

	/**
	 * Get an URL to the origin.
	 */
	public function getOriginUrl(): ?string {
		$origin = $this->getOrigin();
		return $origin === null ? null : $this->urlUtilityService->forceHttps( $origin->getWikiUrl() );
	}

	public function getAgentUrl(): ?string {
		if ( $this->agentId === null ) {
			return null;
		}
		return $this->userFactory->newFromId( (int)$this->agentId )->getUserPage()->getFullURL();
	}

	public function getCategory(): string {
		return $this->notificationListService->getCategoryFromType( $this->type );
	}

	public function getSubcategory(): string {
		return $this->notificationListService->getSubCategoryFromType( $this->type );
	}

	/**
	 * Return the URL for the notification icon.
	 *
	 * @return string|null URL or null if missing.
	 */
	public function getNotificationIcon(): ?string {
		return $this->getIconsConfig( 'notification' )[$this->type] ?? null;
	}

	/**
	 * Return the URL for the category icon.
	 *
	 * @return string|null URL or null if missing.
	 */
	public function getCategoryIcon(): ?string {
		return $this->getIconsConfig( 'category' )[$this->getCategory()] ?? null;
	}

	/**
	 * Return the URL for the subcategory icon.
	 *
	 * @return string|null URL or null if missing.
	 */
	public function getSubcategoryIcon(): ?string {
		return $this->getIconsConfig( 'subcategory' )[$this->getSubcategory()] ?? null;
	}

	/**
	 * Get icon configuration.
	 *
	 * @param string $type Icon Type, one of: 'notification', 'category', 'subcategory'
	 *
	 * @return array Array containing key of type name to the URL location for it.
	 */
	private function getIconsConfig( string $type ): array {
		return $this->config->get( 'ReverbIcons' )[$type];
	}

	/**
	 * Get the importance of this notification to assist with sorting.
	 *
	 * @return int Importance
	 */
	public function getImportance(): int {
		return (int)$this->config->get( 'ReverbNotifications' )[ $this->type ][ 'importance' ];
	}

	/**
	 * Return a visual grouping of notifications based on user preferences grouping.
	 *
	 * @return string
	 */
	public function getVisualGroup(): string {
		return $this->notificationListService->replaceTypeWithUsePreference( $this->type );
	}

	/**
	 * Get an array representation of this object suitable for APIs or otherwise.
	 *
	 * @return array
	 */
	public function toArray(): array {
		$wiki = $this->getOrigin();

		return [
			'icons' => [
				'notification' => $this->getNotificationIcon(),
				'category' => $this->getCategoryIcon(),
				'subcategory' => $this->getSubcategoryIcon(),
			],
			'category' => $this->getCategory(),
			'subcategory' => $this->getSubcategory(),
			'grouping' => $this->getVisualGroup(),
			'id' => $this->id,
			'type' => $this->type,
			'header_short' => $this->getHeader(),
			'header_long' => $this->getHeader( true ),
			'user_note' => $this->getUserNote(),
			'created_at' => $this->createdAt,
			'dismissed_at' => $this->dismissedAt,
			'origin_url' => $this->getOriginUrl(),
			'site_key' => $wiki?->getWikiId(),
			'site_name' => $wiki === null ? null :
				sprintf( '%s (%s)', $wiki->getTitle(), mb_strtoupper( $wiki->getLangCode(), 'UTF-8' ) ),
			'agent_url' => $this->getAgentUrl(),
			'canonical_url' => $this->canonicalUrl,
			'importance' => $this->getImportance(),
		];
	}
}
