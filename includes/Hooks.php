<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\MagicCacheSchedule;

use MediaWiki\Parser\Hook\ParserFirstCallInitHook;

class Hooks implements ParserFirstCallInitHook {

	public function __construct(
		private readonly CacheScheduler $cacheScheduler,
	) {
	}

	/** @inheritDoc */
	public function onParserFirstCallInit( $parser ) {
		$parser->setFunctionHook( 'cachedaily', [ $this->cacheScheduler, 'cacheDaily' ] );
		$parser->setFunctionHook( 'cacheuntil', [ $this->cacheScheduler, 'cacheUntil' ] );
		$parser->setFunctionHook( 'cacheinterval', [ $this->cacheScheduler, 'cacheInterval' ] );
	}
}
