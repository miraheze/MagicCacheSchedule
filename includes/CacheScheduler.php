<?php

declare( strict_types = 1 );

namespace MediaWiki\Extension\MagicCacheSchedule;

use DateTime;
use DateTimeZone;
use Exception;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Parser\Parser;
use function array_filter;
use function array_map;
use function explode;
use function max;
use function preg_match;

class CacheScheduler {

	public const array CONSTRUCTOR_OPTIONS = [
		ConfigNames::MinimumExpiry,
		ConfigNames::Timezone,
	];

	private const int SECONDS_PER_DAY = 86400;

	public function __construct(
		private readonly ServiceOptions $options,
	) {
		$this->options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
	}

	/**
	 * {{#cachedaily}}
	 * The page becomes eligible for regeneration 24 hours after it was
	 * last parsed, not at a fixed clock time. Use cacheuntil for that.
	 */
	public function cacheDaily( Parser $parser ): string {
		$parser->getOutput()->updateCacheExpiry( $this->withMinimum( self::SECONDS_PER_DAY ), 'cachedaily' );
		return '';
	}

	/**
	 * {{#cacheuntil:03:00}}
	 * {{#cacheuntil:03:00,15:00}}
	 * Invalidates at the next occurrence of one of the given times of
	 * day, in 24 hour HH:MM format. More than one time just means the
	 * page refreshes more than once a day, at each one.
	 */
	public function cacheUntil( Parser $parser, string $times = '' ): string {
		$times = array_filter( array_map( 'trim', explode( ',', $times ) ) );
		if ( !$times ) {
			return '';
		}

		$seconds = $this->secondsUntilNextTime( $times );
		if ( $seconds !== null ) {
			$parser->getOutput()->updateCacheExpiry( $this->withMinimum( $seconds ), 'cacheuntil' );
		}

		return '';
	}

	/**
	 * {{#cacheinterval:3600}}
	 * Raw control for when a fixed number of seconds is easier to reason
	 * about than a clock time, for example refreshing every 15 minutes.
	 */
	public function cacheInterval( Parser $parser, string $seconds = '' ): string {
		$seconds = (int)$seconds;
		if ( $seconds > 0 ) {
			$parser->getOutput()->updateCacheExpiry( $this->withMinimum( $seconds ), 'cacheinterval' );
		}

		return '';
	}

	/**
	 * Works out how many seconds remain until the nearest of the given
	 * times of day, rolling over to tomorrow for any time already passed.
	 *
	 * @param string[] $times HH:MM strings
	 * @return int|null seconds until the soonest valid time, or null if
	 *   none of the entries parsed as a real time
	 */
	private function secondsUntilNextTime( array $times ): ?int {
		$now = new DateTime( 'now', $this->getTimezone() );
		$soonest = null;
		foreach ( $times as $time ) {
			if ( !preg_match( '/^([01]?\d|2[0-3]):([0-5]\d)$/', $time, $m ) ) {
				continue;
			}

			$candidate = clone $now;
			$candidate->setTime( (int)$m[1], (int)$m[2], 0 );

			if ( $candidate <= $now ) {
				$candidate->modify( '+1 day' );
			}

			$diff = $candidate->getTimestamp() - $now->getTimestamp();
			if ( $soonest === null || $diff < $soonest ) {
				$soonest = $diff;
			}
		}

		return $soonest;
	}

	private function getTimezone(): DateTimeZone {
		$tzName = $this->options->get( ConfigNames::Timezone );
		try {
			return new DateTimeZone( $tzName );
		} catch ( Exception ) {
			return new DateTimeZone( 'UTC' );
		}
	}

	/**
	 * Clamps a computed expiry to the configured floor, so a page can't
	 * set cacheinterval to something tiny and keep forcing reparses.
	 */
	private function withMinimum( int $seconds ): int {
		return max( $seconds, $this->options->get( ConfigNames::MinimumExpiry ) );
	}
}
