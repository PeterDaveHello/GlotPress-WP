<?php
/**
 * Functions, which make work with strings easier
 */

function gp_startswith( $haystack, $needle ) {
	return 0 === strpos( $haystack, $needle );
}

function gp_endswith( $haystack, $needle ) {
	return $needle === substr( $haystack, -strlen( $needle ));
}

function gp_in( $needle, $haystack ) {
	return false !== strpos( $haystack, $needle );
}

if ( function_exists('mb_strtolower') ) {
	function gp_strtolower( $str ) {
		return mb_strtolower( $str );
	}
} else {
	function gp_strtolower( $str ) {
		return strtolower( $str );
	}
}

if ( function_exists('mb_strlen') ) {
	function gp_strlen( $str ) {
		return mb_strlen( $str );
	}
} else {
	function gp_strlen( $str ) {
		return preg_match_all("/.{1}/us", $str, $dummy);
	}
}

if ( function_exists('mb_stripos') ) {
	function gp_stripos( $haystack, $needle ) {
		return mb_stripos( $haystack, $needle );
	}
} else {
	function gp_stripos( $haystack, $needle ) {
		return stripos( $haystack, $needle );
	}
}

if ( function_exists('mb_substr') ) {
	function gp_substr( $str, $start, $length ) {
		return mb_substr( $str, $start, $length );
	}
} else {
	function gp_substr( $str, $start, $length ) {
		return substr( $str, $start, $length );
	}
}

function gp_sanitize_for_url( $name ) {
	$name = trim( $name );
	$name = gp_strtolower( $name );
	$name = preg_replace( '/&.+?;/', '', $name ); // kill entities
	$name = str_replace( '.', '-', $name );
	$name = preg_replace('|[#$%&~/.\-;:=,?@\[\]+]|', '', $name);
	$name = preg_replace( '/\s+/', '-', $name );
	$name = preg_replace( '|-+|', '-', $name );
	$name = trim($name, '-');
	return $name;
}

/**
 * Escaping for HTML attributes.
 *
 * Similar to esc_attr(), but double encode entities.
 *
 * @since 1.0.0
 *
 * @param string $text The text prior to being escaped.
 * @return string The text after it has been escaped.
 */
function gp_esc_attr_with_entities( $text ) {
	$safe_text = wp_check_invalid_utf8( $text );
	$safe_text = htmlspecialchars( $safe_text, ENT_QUOTES, false, true );

	/**
	 * Filter a string cleaned and escaped for output in an HTML attribute.
	 *
	 * Text passed to gp_esc_attr_with_entities() is stripped of invalid or
	 * special characters before output. Unlike esc_attr() it double encodes
	 * entities.
	 *
	 * @since 1.0.0
	 *
	 * @param string $safe_text The text after it has been escaped.
	 * @param string $text      The text prior to being escaped.
	 */
	return apply_filters( 'gp_attribute_escape', $safe_text, $text );
}

/**
 * Escapes translations for HTML blocks.
 *
 * Similar to esc_html(), but double encode entities.
 *
 * @since 1.0.0
 *
 * @param string $text The text prior to being escaped.
 * @return string The text after it has been escaped.
 */
function esc_translation( $text ) {
	$safe_text = wp_check_invalid_utf8( $text );
	return htmlspecialchars( $safe_text, ENT_NOQUOTES, false, true );
}

function gp_string_similarity( $str1, $str2 ) {

	$length1 = gp_strlen( $str1 );
	$length2 = gp_strlen( $str2 );

	$len = min( $length1, $length2);
	if ( $len > 5000 ) {
		//Arbitrary limit on character length for speed purpose.
		$distance = $len;
	} else {
		$distance = gp_levenshtein( $str1, $str2, $length1, $length2 );
	}

	$similarity = 1 - ( $distance * 0.9 / $len );

	return $similarity;
}

/*
	PHP native implementation of levensthein is limited to 255 bytes, so let's extend that
	Source: https://github.com/wikimedia/mediawiki-extensions-Translate/blob/master/ttmserver/TTMServer.php#L90

*/
function gp_levenshtein( $str1, $str2, $length1, $length2 ) {

	if ( $length1 == 0 ) {
		return $length2;
	}

	if ( $length2 == 0 ) {
		return $length1;
	}

	if ( $str1 === $str2 ) {
		return 0;
	}

	$bytelength1 = strlen( $str1 );
	$bytelength2 = strlen( $str2 );

	if ( $bytelength1 === $length1 && $bytelength1 <= 255
	     && $bytelength2 === $length2 && $bytelength2 <= 255 ) {
		return levenshtein( $str1, $str2 );
	}

	$prevRow = range( 0, $length2 );
	for ( $i = 0; $i < $length1; $i++ ) {
		$currentRow = array();
		$currentRow[0] = $i + 1;
		$c1 = gp_substr( $str1, $i, 1 );
		for ( $j = 0; $j < $length2; $j++ ) {
			$c2 = gp_substr( $str2, $j, 1 );
			$insertions = $prevRow[$j + 1] + 1;
			$deletions = $currentRow[$j] + 1;
			$substitutions = $prevRow[$j] + ( ( $c1 != $c2 ) ? 1 : 0 );
			$currentRow[] = min( $insertions, $deletions, $substitutions );
		}
		$prevRow = $currentRow;
	}

	return $prevRow[$length2];
}
