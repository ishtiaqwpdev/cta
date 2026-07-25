<?php
/**
 * Test cases: supervision booking authorization gates.
 *
 * Run: php tests/test-supervision-booking-gates.php
 *
 * Pure helpers — no WordPress / live DB required.
 *
 * @package CTA_LMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

$root = dirname( __DIR__ );

if ( ! function_exists( 'absint' ) ) {
	/**
	 * @param mixed $value Value.
	 * @return int
	 */
	function absint( $value ) {
		return abs( (int) $value );
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * @param string $text Text.
	 * @return string
	 */
	function __( $text ) {
		return $text;
	}
}

if ( ! class_exists( 'CTA_Associate_Access', false ) ) {
	require_once $root . '/includes/class-cta-associate-access.php';
}

if ( ! class_exists( 'CTA_Supervision', false ) ) {
	require_once $root . '/public/class-cta-supervision.php';
}

$failures = 0;

/**
 * Assert helper.
 *
 * @param string $label Case label.
 * @param mixed  $expected Expected value.
 * @param mixed  $actual Actual value.
 */
function cta_assert_same( $label, $expected, $actual ) {
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

// --- Scenarios 1–4: access gate (same helper used by ajax_book_session) ---
cta_assert_same(
	'1. Pending (not approved) cannot book',
	false,
	CTA_Associate_Access::evaluate_feature_access( false, true, true )
);

cta_assert_same(
	'2a. Rejected/not approved cannot book even with plan+active',
	false,
	CTA_Associate_Access::evaluate_feature_access( false, true, true )
);

cta_assert_same(
	'2b. Suspended/inactive plan status cannot book',
	false,
	CTA_Associate_Access::evaluate_feature_access( true, true, false )
);

cta_assert_same(
	'3. Approved without plan cannot book',
	false,
	CTA_Associate_Access::evaluate_feature_access( true, false, true )
);

cta_assert_same(
	'4. Non-associate path: no feature access without all three flags',
	false,
	CTA_Associate_Access::evaluate_feature_access( false, false, false )
);

cta_assert_same(
	'Authorized associate can book',
	true,
	CTA_Associate_Access::evaluate_feature_access( true, true, true )
);

// --- Scenario 5: group capacity (8) ---
cta_assert_same(
	'5a. Group capacity constant is 8',
	8,
	CTA_Supervision::GROUP_SEATS_MAX
);

cta_assert_same(
	'5b. 8/8 group seats => no open seat (9th blocked)',
	false,
	CTA_Supervision::evaluate_has_open_seat( 8, CTA_Supervision::GROUP_SEATS_MAX )
);

cta_assert_same(
	'5c. 7/8 group seats => open',
	true,
	CTA_Supervision::evaluate_has_open_seat( 7, CTA_Supervision::GROUP_SEATS_MAX )
);

cta_assert_same(
	'5d. Capacity helper for group',
	8,
	CTA_Supervision::get_capacity_for_type( 'group' )
);

// --- Scenario 7: individual capacity (1) ---
cta_assert_same(
	'7a. Individual capacity is 1',
	1,
	CTA_Supervision::get_capacity_for_type( 'individual' )
);

cta_assert_same(
	'7b. 1/1 individual => second booker blocked',
	false,
	CTA_Supervision::evaluate_has_open_seat( 1, 1 )
);

cta_assert_same(
	'7c. 0/1 individual => open',
	true,
	CTA_Supervision::evaluate_has_open_seat( 0, 1 )
);

// --- Scenario 6: duplicate / overlap ---
cta_assert_same(
	'6a. Same start times overlap',
	true,
	CTA_Supervision::sessions_overlap( '2026-08-01', '10:00:00', 120, '2026-08-01', '10:00:00', 60 )
);

cta_assert_same(
	'6b. Group 10:00–12:00 overlaps individual 11:00–12:00',
	true,
	CTA_Supervision::sessions_overlap( '2026-08-01', '10:00:00', 120, '2026-08-01', '11:00:00', 60 )
);

cta_assert_same(
	'6c. Back-to-back 10:00–12:00 and 12:00–13:00 do not overlap',
	false,
	CTA_Supervision::sessions_overlap( '2026-08-01', '10:00:00', 120, '2026-08-01', '12:00:00', 60 )
);

cta_assert_same(
	'6d. Different days never overlap',
	false,
	CTA_Supervision::sessions_overlap( '2026-08-01', '10:00:00', 120, '2026-08-02', '10:00:00', 120 )
);

// --- Scenario 8: meeting links ---
cta_assert_same(
	'8a. Meeting link hidden without approval/access',
	false,
	CTA_Supervision::evaluate_can_join_meeting( true, true, false )
);

cta_assert_same(
	'8b. Meeting link hidden without own confirmed booking',
	false,
	CTA_Supervision::evaluate_can_join_meeting( true, false, true )
);

cta_assert_same(
	'8c. Meeting link hidden when URL missing',
	false,
	CTA_Supervision::evaluate_can_join_meeting( false, true, true )
);

cta_assert_same(
	'8d. Meeting link visible only when approved+booked+URL',
	true,
	CTA_Supervision::evaluate_can_join_meeting( true, true, true )
);

echo "\n";
if ( $failures > 0 ) {
	echo "FAILED: {$failures} assertion(s)\n";
	exit( 1 );
}

echo "All supervision booking gate tests passed.\n";
exit( 0 );
