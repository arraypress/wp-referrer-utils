<?php
/**
 * PHPUnit bootstrap.
 *
 * @package ArrayPress\ReferrerUtils
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

/**
 * Stubbed options, and the filter registry.
 *
 * @var array<string, mixed>
 */
$GLOBALS['ru_options'] = [ 'siteurl' => 'https://example.test' ];

/**
 * @var array<string, array<int, callable>>
 */
$GLOBALS['ru_filters'] = [];

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Read a stubbed option.
	 *
	 * @param string $name    Option name.
	 * @param mixed  $default Fallback.
	 *
	 * @return mixed
	 */
	function get_option( string $name, $default = false ) {
		return $GLOBALS['ru_options'][ $name ] ?? $default;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	/**
	 * Register a filter callback.
	 *
	 * @param string   $hook     Hook name.
	 * @param callable $callback Callback.
	 *
	 * @return bool
	 */
	function add_filter( string $hook, callable $callback ): bool {
		$GLOBALS['ru_filters'][ $hook ][] = $callback;

		return true;
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Run a value through the callbacks registered for a hook.
	 *
	 * @param string $hook    Hook name.
	 * @param mixed  $value   Value.
	 * @param mixed  ...$args Further arguments.
	 *
	 * @return mixed
	 */
	function apply_filters( string $hook, $value, ...$args ) {
		foreach ( $GLOBALS['ru_filters'][ $hook ] ?? [] as $callback ) {
			$value = $callback( $value, ...$args );
		}

		return $value;
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Translation stub.
	 *
	 * @param string $text   Text.
	 * @param string $domain Text domain.
	 *
	 * @return string
	 */
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Core's tag stripper.
	 *
	 * @param string $value Value.
	 *
	 * @return string
	 */
	function wp_strip_all_tags( string $value ): string {
		$value = (string) preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $value );

		return trim( (string) strip_tags( $value ) );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Core strips the slashes it added to the superglobals on load.
	 *
	 * @param mixed $value Value.
	 *
	 * @return mixed
	 */
	function wp_unslash( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Core's single-line text sanitizer, near enough.
	 *
	 * @param string $value Value.
	 *
	 * @return string
	 */
	function sanitize_text_field( string $value ): string {
		$value = wp_strip_all_tags( $value );

		return trim( (string) preg_replace( '/[\r\n\t ]+/', ' ', $value ) );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * Core's URL sanitizer, near enough for a referrer.
	 *
	 * Keeps only http and https, strips what has no place in a URL, and
	 * leaves an already-clean URL alone.
	 *
	 * @param string $url URL.
	 *
	 * @return string
	 */
	function esc_url_raw( string $url ): string {
		$url = trim( wp_strip_all_tags( $url ) );
		$url = (string) preg_replace( '|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'\'()\\[\\]\\\\x80-\\xff]|i', '', $url );

		if ( '' === $url ) {
			return '';
		}

		return preg_match( '#^https?://#i', $url ) ? $url : '';
	}
}

/**
 * Forget the stubbed state between tests.
 *
 * @return void
 */
function ru_reset_globals(): void {
	$GLOBALS['ru_options'] = [ 'siteurl' => 'https://example.test' ];
	$GLOBALS['ru_filters'] = [];

	unset( $_SERVER['HTTP_REFERER'] );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/*
 * And src/Functions.php again: it is a Composer `files` entry, so it already
 * ran when PHPUnit loaded the autoloader -- before ABSPATH was defined, so it
 * returned without declaring anything. `require`, not `require_once`, because
 * Composer already included this exact path.
 */
require dirname( __DIR__ ) . '/src/Functions.php';
