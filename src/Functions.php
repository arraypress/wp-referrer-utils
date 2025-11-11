<?php
/**
 * Global Referrer Helper Functions
 *
 * Provides convenient global functions for common referrer operations.
 * These functions are wrappers around the ArrayPress\ReferrerUtils\Referrer class.
 *
 * Functions included:
 * - get_referrer() - Get the current HTTP referrer URL
 * - get_referrer_source() - Get the referrer source for analytics
 *
 * @package ArrayPress\ReferrerUtils
 * @since   1.0.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use ArrayPress\ReferrerUtils\Referrer;

if ( ! function_exists( 'get_referrer_url' ) ) {
	/**
	 * Get the current HTTP referrer URL.
	 *
	 * @return string The referrer URL or empty string if not available.
	 * @since 1.0.0
	 */
	function get_referrer_url(): string {
		return Referrer::get() ?? '';
	}
}

if ( ! function_exists( 'get_referrer_source' ) ) {
	/**
	 * Get the simplified referrer source (google, facebook, direct, referral, etc.).
	 *
	 * @param string|null $url Optional. URL to check instead of current referrer.
	 *
	 * @return string Traffic source identifier.
	 * @since 1.0.0
	 */
	function get_referrer_source( ?string $url = null ): string {
		return Referrer::get_source( $url );
	}
}

if ( ! function_exists( 'get_referrer_utm_params' ) ) {
	/**
	 * Get UTM parameters from a URL (current referrer or provided URL).
	 *
	 * @param string|null $url Optional. URL to parse instead of current referrer.
	 *
	 * @return array UTM parameters array (source, medium, campaign, term, content).
	 * @since 1.0.0
	 */
	function get_referrer_utm_params( ?string $url = null ): array {
		return Referrer::get_utm_parameters( $url );
	}
}

if ( ! function_exists( 'is_referrer_from_search' ) ) {
	/**
	 * Check if the referrer is from a search engine.
	 *
	 * @param string|null $url Optional. URL to check instead of current referrer.
	 *
	 * @return bool True if from search engine, false otherwise.
	 * @since 1.0.0
	 */
	function is_referrer_from_search( ?string $url = null ): bool {
		return Referrer::is_search_engine( $url );
	}
}

if ( ! function_exists( 'get_referrer_search_terms' ) ) {
	/**
	 * Get search terms from the referrer if it's from a search engine.
	 *
	 * @param string|null $url Optional. URL to check instead of current referrer.
	 *
	 * @return string Search terms or empty string if not available.
	 * @since 1.0.0
	 */
	function get_referrer_search_terms( ?string $url = null ): string {
		return Referrer::get_search_terms( $url ) ?? '';
	}
}