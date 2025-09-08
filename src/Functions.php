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

if ( ! function_exists( 'get_referrer' ) ) {
	/**
	 * Get the current HTTP referrer URL.
	 *
	 * @return string The referrer URL or empty string if not available.
	 * @since 1.0.0
	 */
	function get_referrer(): string {
		return Referrer::get() ?? '';
	}
}

if ( ! function_exists( 'get_referrer_source' ) ) {
	/**
	 * Get the referrer source for analytics.
	 *
	 * Returns specific platform names (google, facebook, etc.) or
	 * generic types (direct, internal, referral).
	 *
	 * @param string|null $url Optional referrer URL to analyze.
	 *
	 * @return string Traffic source identifier.
	 * @since 1.0.0
	 */
	function get_referrer_source( ?string $url = null ): string {
		return Referrer::get_source( $url );
	}
}