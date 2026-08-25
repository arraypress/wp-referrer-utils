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

/*
 * The query-string helpers below are copied verbatim from WordPress core
 * (wp-includes/functions.php, formatting.php, http.php) rather than
 * reimplemented. Tracking::strip() delegates to remove_query_arg(), and its
 * behaviour on edge cases — repeated keys, array syntax, encoded names — is
 * core's behaviour. A hand-written approximation would test the approximation.
 */
if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( ...$args ) {
		if ( is_array( $args[0] ) ) {
			if ( count( $args ) < 2 || false === $args[1] ) {
				$uri = $_SERVER['REQUEST_URI'];
			} else {
				$uri = $args[1];
			}
		} else {
			if ( count( $args ) < 3 || false === $args[2] ) {
				$uri = $_SERVER['REQUEST_URI'];
			} else {
				$uri = $args[2];
			}
		}

		$frag = strstr( $uri, '#' );
		if ( $frag ) {
			$uri = substr( $uri, 0, -strlen( $frag ) );
		} else {
			$frag = '';
		}

		if ( 0 === stripos( $uri, 'http://' ) ) {
			$protocol = 'http://';
			$uri      = substr( $uri, 7 );
		} elseif ( 0 === stripos( $uri, 'https://' ) ) {
			$protocol = 'https://';
			$uri      = substr( $uri, 8 );
		} else {
			$protocol = '';
		}

		if ( str_contains( $uri, '?' ) ) {
			list( $base, $query ) = explode( '?', $uri, 2 );
			$base                .= '?';
		} elseif ( $protocol || ! str_contains( $uri, '=' ) ) {
			$base  = $uri . '?';
			$query = '';
		} else {
			$base  = '';
			$query = $uri;
		}

		wp_parse_str( $query, $qs );
		$qs = urlencode_deep( $qs ); // This re-URL-encodes things that were already in the query string.
		if ( is_array( $args[0] ) ) {
			foreach ( $args[0] as $k => $v ) {
				$qs[ $k ] = $v;
			}
		} else {
			$qs[ $args[0] ] = $args[1];
		}

		foreach ( $qs as $k => $v ) {
			if ( false === $v ) {
				unset( $qs[ $k ] );
			}
		}

		$ret = build_query( $qs );
		$ret = trim( $ret, '?' );
		$ret = preg_replace( '#=(&|$)#', '$1', $ret );
		$ret = $protocol . $base . $ret . $frag;
		$ret = rtrim( $ret, '?' );
		$ret = str_replace( '?#', '#', $ret );
		return $ret;
	}
}

if ( ! function_exists( 'remove_query_arg' ) ) {
	function remove_query_arg( $key, $query = false ) {
		if ( is_array( $key ) ) { // Removing multiple keys.
			foreach ( $key as $k ) {
				$query = add_query_arg( $k, false, $query );
			}
			return $query;
		}
		return add_query_arg( $key, false, $query );
	}
}

if ( ! function_exists( 'build_query' ) ) {
	function build_query( $data ) {
		return _http_build_query( $data, null, '&', '', false );
	}
}

if ( ! function_exists( '_http_build_query' ) ) {
	function _http_build_query( $data, $prefix = null, $sep = null, $key = '', $urlencode = true ) {
		$ret = array();

		foreach ( (array) $data as $k => $v ) {
			if ( $urlencode ) {
				$k = urlencode( $k );
			}

			if ( is_int( $k ) && null !== $prefix ) {
				$k = $prefix . $k;
			}

			if ( ! empty( $key ) ) {
				$k = $key . '%5B' . $k . '%5D';
			}

			if ( null === $v ) {
				continue;
			} elseif ( false === $v ) {
				$v = '0';
			}

			if ( is_array( $v ) || is_object( $v ) ) {
				array_push( $ret, _http_build_query( $v, '', $sep, $k, $urlencode ) );
			} elseif ( $urlencode ) {
				array_push( $ret, $k . '=' . urlencode( $v ) );
			} else {
				array_push( $ret, $k . '=' . $v );
			}
		}

		if ( null === $sep ) {
			$sep = ini_get( 'arg_separator.output' );
		}

		return implode( $sep, $ret );
	}
}

if ( ! function_exists( 'wp_parse_str' ) ) {
	function wp_parse_str( $input_string, &$result ) {
		parse_str( (string) $input_string, $result );

		/**
		 * Filters the array of variables derived from a parsed string.
		 *
		 * @since 2.2.1
		 *
		 * @param array $result The array populated with variables.
		 */
		$result = apply_filters( 'wp_parse_str', $result );
	}
}

if ( ! function_exists( 'urlencode_deep' ) ) {
	function urlencode_deep( $value ) {
		return map_deep( $value, 'urlencode' );
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		$to_unset = array();
		$url      = (string) $url;

		if ( str_starts_with( $url, '//' ) ) {
			$to_unset[] = 'scheme';
			$url        = 'placeholder:' . $url;
		} elseif ( str_starts_with( $url, '/' ) ) {
			$to_unset[] = 'scheme';
			$to_unset[] = 'host';
			$url        = 'placeholder://placeholder' . $url;
		}

		$parts = parse_url( $url );

		if ( false === $parts ) {
			// Parsing failure.
			return $parts;
		}

		// Remove the placeholder values.
		foreach ( $to_unset as $key ) {
			unset( $parts[ $key ] );
		}

		return _get_component_from_parsed_url_array( $parts, $component );
	}
}

if ( ! function_exists( '_get_component_from_parsed_url_array' ) ) {
	function _get_component_from_parsed_url_array( $url_parts, $component = -1 ) {
		if ( -1 === $component ) {
			return $url_parts;
		}

		$key = _wp_translate_php_url_constant_to_key( $component );
		if ( false !== $key && is_array( $url_parts ) && isset( $url_parts[ $key ] ) ) {
			return $url_parts[ $key ];
		} else {
			return null;
		}
	}
}


if ( ! function_exists( '_wp_translate_php_url_constant_to_key' ) ) {
	function _wp_translate_php_url_constant_to_key( $constant ) {
		$translation = array(
			PHP_URL_SCHEME   => 'scheme',
			PHP_URL_HOST     => 'host',
			PHP_URL_PORT     => 'port',
			PHP_URL_USER     => 'user',
			PHP_URL_PASS     => 'pass',
			PHP_URL_PATH     => 'path',
			PHP_URL_QUERY    => 'query',
			PHP_URL_FRAGMENT => 'fragment',
		);

		return $translation[ $constant ] ?? false;
	}
}

if ( ! function_exists( 'map_deep' ) ) {
	function map_deep( $value, $callback ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $index => $item ) {
				$value[ $index ] = map_deep( $item, $callback );
			}
		} elseif ( is_object( $value ) ) {
			$object_vars = get_object_vars( $value );
			foreach ( $object_vars as $property_name => $property_value ) {
				$value->$property_name = map_deep( $property_value, $callback );
			}
		} else {
			$value = call_user_func( $callback, $value );
		}

		return $value;
	}
}
