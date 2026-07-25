<?php
/**
 * Encoding / mojibake repair checks (run without WordPress bootstrap).
 *
 * @package CTA_LMS
 */

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

$root = dirname( __DIR__ );
require_once $root . '/includes/cta-encoding.php';

$failures = 0;

function cta_assert( $cond, $msg ) {
	global $failures;
	if ( ! $cond ) {
		echo "FAIL: {$msg}\n";
		$failures++;
		return;
	}
	echo "OK: {$msg}\n";
}

// Em dash mojibake (UTF-8 of â€”).
$em_moji = "\xC3\xA2\xE2\x82\xAC\xE2\x80\x9D";
cta_assert( cta_lms_fix_mojibake( 'Demo mode ' . $em_moji . ' test' ) === "Demo mode \xE2\x80\x94 test", 'em-dash mojibake repaired' );

// Apostrophe mojibake (UTF-8 of â€™).
$rsq_moji = "\xC3\xA2\xE2\x82\xAC\xE2\x84\xA2";
cta_assert( cta_lms_fix_mojibake( 'Stripe' . $rsq_moji . 's' ) === "Stripe\xE2\x80\x99s", 'right-single-quote mojibake repaired' );

// Truncated ASCII-quote form â€"
$trunc = "\xC3\xA2\xE2\x82\xAC\"";
cta_assert( cta_lms_fix_mojibake( 'x ' . $trunc . ' y' ) === "x \xE2\x80\x94 y", 'truncated â€" form repaired' );

// Already-correct UTF-8 must pass through unchanged.
$ok = "Group Supervision \xE2\x80\x94 \$260/month";
cta_assert( cta_lms_sanitize_utf8_text( $ok ) === $ok, 'valid UTF-8 punctuation preserved' );

// main.js subscription modal must use HTML entities (ASCII-safe in HTML builders).
$main = file_get_contents( $root . '/assets/js/main.js' );
cta_assert( false !== strpos( $main, 'Demo mode &mdash; turn off Testing Mode' ), 'subscription modal footer uses &mdash;' );
cta_assert( false !== strpos( $main, 'Stripe&rsquo;s Customer Portal' ), 'subscription modal help uses &rsquo;' );
cta_assert( false === strpos( $main, 'Demo mode \\u2014 turn off' ), 'subscription modal no longer uses \\u2014 in HTML footer' );
cta_assert( false === strpos( $main, "value=\"\xE2\x80\xA2" ), 'demo CVC field no longer uses literal bullet UTF-8' );

// No baked-in mojibake in plugin sources (exclude this test + scan helpers).
$skip = array( '_scan_encoding', 'test-encoding-mojibake' );
$exts = array( 'php', 'js', 'html' );
$moji_needle = "\xC3\xA2\xE2\x82\xAC";
$iterator   = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root ) );
$found      = array();
foreach ( $iterator as $file ) {
	if ( ! $file->isFile() ) {
		continue;
	}
	$path = $file->getPathname();
	$base = $file->getBasename();
	foreach ( $skip as $s ) {
		if ( false !== strpos( $base, $s ) ) {
			continue 2;
		}
	}
	if ( false !== strpos( $path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR ) ) {
		continue;
	}
	$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
	if ( ! in_array( $ext, $exts, true ) ) {
		continue;
	}
	$blob = file_get_contents( $path );
	if ( false !== strpos( $blob, $moji_needle ) ) {
		$found[] = str_replace( $root . DIRECTORY_SEPARATOR, '', $path );
	}
}
cta_assert( 0 === count( $found ), 'no mojibake sequences in source (found: ' . implode( ', ', $found ) . ')' );

echo $failures ? "\n{$failures} failure(s)\n" : "\nAll encoding checks passed.\n";
exit( $failures ? 1 : 0 );
