<?php
/**
 * Test cases: exam preparation access gating.
 *
 * Run: php tests/test-exam-access-gates.php
 *
 * These tests exercise the pure decision helpers and do not need WordPress.
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

if ( ! class_exists( 'CTA_Exam_Access', false ) ) {
	require_once $root . '/includes/class-cta-exam-access.php';
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

// --- Product type detection ---
cta_assert_same(
	'CE course is not exam prep',
	false,
	CTA_Exam_Access::is_exam_prep( (object) array( 'product_type' => 'ce' ) )
);

cta_assert_same(
	'Exam prep course is exam prep',
	true,
	CTA_Exam_Access::is_exam_prep( (object) array( 'product_type' => 'exam_prep' ) )
);

cta_assert_same(
	'Missing product_type defaults to CE',
	false,
	CTA_Exam_Access::is_exam_prep( (object) array() )
);

cta_assert_same(
	'String exam_prep is exam prep',
	true,
	CTA_Exam_Access::is_exam_prep( 'exam_prep' )
);

// --- Active access evaluator ---
cta_assert_same(
	'No record => no access',
	false,
	CTA_Exam_Access::evaluate_has_active_access( false, '2026-12-01 00:00:00', '2026-06-01 00:00:00' )
);

cta_assert_same(
	'Null expires_at => access remains open',
	true,
	CTA_Exam_Access::evaluate_has_active_access( true, null, '2026-06-01 00:00:00' )
);

cta_assert_same(
	'Future expires_at => active access',
	true,
	CTA_Exam_Access::evaluate_has_active_access( true, '2026-12-01 12:00:00', '2026-06-01 12:00:00' )
);

cta_assert_same(
	'Past expires_at => no access',
	false,
	CTA_Exam_Access::evaluate_has_active_access( true, '2026-01-01 00:00:00', '2026-06-01 00:00:00' )
);

cta_assert_same(
	'Expires exactly now => no access (must be strictly after)',
	false,
	CTA_Exam_Access::evaluate_has_active_access( true, '2026-06-01 12:00:00', '2026-06-01 12:00:00' )
);

// --- CE award / certificate flags ---
cta_assert_same(
	'Exam prep never awards CE',
	false,
	CTA_Exam_Access::awards_ce( (object) array( 'product_type' => 'exam_prep', 'awards_ce_hours' => 1 ) )
);

cta_assert_same(
	'Exam prep never has CE certificate',
	false,
	CTA_Exam_Access::has_ce_certificate( (object) array( 'product_type' => 'exam_prep', 'has_ce_certificate' => 1 ) )
);

cta_assert_same(
	'CE course awards CE by default',
	true,
	CTA_Exam_Access::awards_ce( (object) array( 'product_type' => 'ce' ) )
);

echo "\n";
if ( $failures > 0 ) {
	echo "FAILED: {$failures} assertion(s)\n";
	exit( 1 );
}

echo "All exam access gate tests passed.\n";
exit( 0 );
