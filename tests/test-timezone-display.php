<?php
/**
 * Test cases: CTA display timezone helpers (Pacific by default).
 *
 * Run: php tests/test-timezone-display.php
 *
 * @package CTA_LMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

$failures = 0;
$cta_test_timezone_option = 'America/Los_Angeles';

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * @param string $key Option key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	function get_option( $key, $default = false ) {
		global $cta_test_timezone_option;
		if ( 'cta_timezone' === $key ) {
			return $cta_test_timezone_option;
		}
		return $default;
	}
}

if ( ! function_exists( 'wp_timezone' ) ) {
	/**
	 * @return DateTimeZone
	 */
	function wp_timezone() {
		return new DateTimeZone( 'UTC' );
	}
}

if ( ! function_exists( 'wp_date' ) ) {
	/**
	 * Minimal wp_date stub.
	 *
	 * @param string            $format Format.
	 * @param int|null          $timestamp Timestamp.
	 * @param DateTimeZone|null $timezone Timezone.
	 * @return string
	 */
	function wp_date( $format, $timestamp = null, $timezone = null ) {
		if ( null === $timestamp ) {
			$timestamp = time();
		}
		$tz = $timezone instanceof DateTimeZone ? $timezone : new DateTimeZone( 'UTC' );
		$dt = ( new DateTimeImmutable( '@' . (int) $timestamp ) )->setTimezone( $tz );
		return $dt->format( $format );
	}
}

require_once dirname( __DIR__ ) . '/includes/cta-timezone.php';

/**
 * Assert helper.
 *
 * @param string $label Case label.
 * @param mixed  $expected Expected value.
 * @param mixed  $actual Actual value.
 */
function cta_tz_assert_same( $label, $expected, $actual ) {
	global $failures;

	if ( $expected === $actual ) {
		echo "[PASS] {$label}\n";
		return;
	}

	++$failures;
	echo "[FAIL] {$label}\n";
	echo '  expected: ' . var_export( $expected, true ) . "\n";
	echo '  actual:   ' . var_export( $actual, true ) . "\n";
}

/**
 * Assert haystack does not contain needle.
 *
 * @param string $label Case label.
 * @param string $needle Forbidden substring.
 * @param string $haystack Value.
 */
function cta_tz_assert_not_contains( $label, $needle, $haystack ) {
	global $failures;

	if ( false === strpos( (string) $haystack, (string) $needle ) ) {
		echo "[PASS] {$label}\n";
		return;
	}

	++$failures;
	echo "[FAIL] {$label}\n";
	echo "  must not contain: {$needle}\n";
	echo '  actual:   ' . var_export( $haystack, true ) . "\n";
}

cta_tz_assert_same(
	'Default timezone is America/Los_Angeles',
	'America/Los_Angeles',
	cta_lms_get_timezone_string()
);

$dt = cta_lms_session_datetime( '2026-01-15', '10:00:00' );
cta_tz_assert_same(
	'Session datetime uses Pacific wall clock (winter PST offset -08:00)',
	'-08:00',
	$dt ? $dt->format( 'P' ) : null
);

$summer = cta_lms_session_datetime( '2026-07-15', '10:00:00' );
cta_tz_assert_same(
	'Session datetime uses Pacific daylight (summer PDT offset -07:00)',
	'-07:00',
	$summer ? $summer->format( 'P' ) : null
);

$formatted = cta_lms_format_session_time( '2026-01-15', '10:00:00', 'g:i A T' );
cta_tz_assert_same(
	'Session time formats as 10:00 AM PST',
	'10:00 AM PST',
	$formatted
);

$formatted_summer = cta_lms_format_session_time( '2026-07-15', '14:30:00', 'g:i A T' );
cta_tz_assert_same(
	'Session time formats as 2:30 PM PDT',
	'2:30 PM PDT',
	$formatted_summer
);

cta_tz_assert_not_contains(
	'Session time never shows GMT+0000 (winter)',
	'GMT',
	$formatted
);

cta_tz_assert_not_contains(
	'Session time never shows GMT+0000 (summer)',
	'GMT',
	$formatted_summer
);

// MySQL datetime stored in WP site TZ (UTC stub) should convert to Pacific when displayed.
$local = cta_lms_format_local_date( '2026-01-15 18:00:00', 'g:i A T' );
cta_tz_assert_same(
	'UTC-stored mysql datetime displays as 10:00 AM PST',
	'10:00 AM PST',
	$local
);

$iso = $dt ? $dt->format( 'c' ) : '';
cta_tz_assert_same(
	'ISO cancel attribute includes Pacific offset',
	true,
	( false !== strpos( $iso, '2026-01-15T10:00:00-08:00' ) )
);

$full = cta_lms_format_session_datetime( '2026-01-15', '10:00:00' );
cta_tz_assert_not_contains(
	'Full datetime label never shows GMT',
	'GMT',
	$full
);

global $cta_test_timezone_option;
$cta_test_timezone_option = 'America/New_York';
cta_tz_assert_same(
	'Configurable timezone option is honored',
	'America/New_York',
	cta_lms_get_timezone_string()
);

$ny = cta_lms_format_session_time( '2026-01-15', '10:00:00', 'g:i A T' );
cta_tz_assert_same(
	'New York session time formats as 10:00 AM EST',
	'10:00 AM EST',
	$ny
);

echo "\n";
if ( $failures > 0 ) {
	echo "FAILED: {$failures} assertion(s)\n";
	exit( 1 );
}

echo "All timezone display tests passed.\n";
exit( 0 );
