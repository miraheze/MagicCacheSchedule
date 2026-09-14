<?php

declare( strict_types = 1 );

// phpcs:disable Generic.NamingConventions.UpperCaseConstantName.ClassConstantNotUpperCase
namespace MediaWiki\Extension\MagicCacheSchedule;

/**
 * A class containing constants representing the names of configuration variables,
 * to protect against typos.
 */
class ConfigNames {

	public const string MinimumExpiry = 'MagicCacheScheduleMinimumExpiry';

	public const string Timezone = 'MagicCacheScheduleTimezone';
}
