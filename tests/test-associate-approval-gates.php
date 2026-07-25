<?php
/**
 * Test cases: associate approval vs plan access gates.
 *
 * Run: php tests/test-associate-approval-gates.php
 *
 * These tests exercise the pure decision helpers and do not need WordPress.
 *
 * @package CTA_LMS
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

$root = dirname( __DIR__ );

// Minimal stubs so the class file can load outside WordPress.
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

// --- Approval eligibility (vetting is independent of plan) ---
cta_assert_same(
	'Can approve associate without plan (vetting only)',
	true,
	CTA_Associate_Access::evaluate_can_approve( true, false )
);

cta_assert_same(
	'Can approve associate with plan',
	true,
	CTA_Associate_Access::evaluate_can_approve( true, false )
);

cta_assert_same(
	'Cannot approve non-associate',
	false,
	CTA_Associate_Access::evaluate_can_approve( false, false )
);

cta_assert_same(
	'Cannot re-approve already approved associate',
	false,
	CTA_Associate_Access::evaluate_can_approve( true, true )
);

// --- Feature access (dashboard / booking) ---
cta_assert_same(
	'Approved + no plan + active => NO access',
	false,
	CTA_Associate_Access::evaluate_feature_access( true, false, true )
);

cta_assert_same(
	'Approved + plan + inactive => NO access',
	false,
	CTA_Associate_Access::evaluate_feature_access( true, true, false )
);

cta_assert_same(
	'Pending + plan + active => NO access',
	false,
	CTA_Associate_Access::evaluate_feature_access( false, true, true )
);

cta_assert_same(
	'Approved + purchased/assigned plan + active => access granted',
	true,
	CTA_Associate_Access::evaluate_feature_access( true, true, true )
);

cta_assert_same(
	'No approval + no plan + inactive => NO access',
	false,
	CTA_Associate_Access::evaluate_feature_access( false, false, false )
);

echo "\n";
if ( $failures > 0 ) {
	echo "FAILED: {$failures} assertion(s)\n";
	exit( 1 );
}

echo "All associate approval gate tests passed.\n";
exit( 0 );
