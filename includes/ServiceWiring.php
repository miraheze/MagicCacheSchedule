<?php

declare( strict_types = 1 );

use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\ScheduledCacheExpiry\CacheScheduler;
use MediaWiki\MediaWikiServices;

return [
	'ScheduledCacheExpiry.CacheScheduler' => static function ( MediaWikiServices $services ): CacheScheduler {
		return new CacheScheduler(
			new ServiceOptions(
				CacheScheduler::CONSTRUCTOR_OPTIONS,
				$services->getMainConfig()
			)
		);
	},
];
