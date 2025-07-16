<?php
/**
 * Referrer Utility Class
 *
 * Provides utility functions for HTTP referrer detection, validation,
 * and analysis for traffic sources and security.
 *
 * @package ArrayPress\ReferrerUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\ReferrerUtils;

/**
 * Referrer Class
 *
 * Core operations for working with HTTP referrer headers.
 */
class Referrer {

	/**
	 * Common search engines and their query parameters.
	 * Updated for 2025 with AI search engines.
	 *
	 * @var array
	 */
	protected static array $search_engines = [
		// Traditional search engines
		'google'     => [
			'google.com',
			'google.co.uk',
			'google.ca',
			'google.de',
			'google.fr',
			'google.it',
			'google.es',
			'google.com.au',
			'google.co.jp',
			'google.co.in',
			'google.com.br',
			'google.com.mx',
			'google.nl',
			'google.com.tr',
			'google.com.ar',
			'google.pl',
			'google.com.sa',
			'google.ch',
			'google.be',
			'google.se'
		],
		'bing'       => [ 'bing.com', 'www.bing.com' ],
		'yahoo'      => [
			'yahoo.com',
			'search.yahoo.com',
			'yahoo.co.uk',
			'yahoo.co.jp',
			'yahoo.de',
			'yahoo.fr',
			'yahoo.it',
			'yahoo.es',
			'yahoo.com.au',
			'yahoo.com.br',
			'yahoo.com.mx',
			'yahoo.ca',
			'yahoo.in'
		],
		'duckduckgo' => [ 'duckduckgo.com', 'www.duckduckgo.com' ],
		'baidu'      => [ 'baidu.com', 'www.baidu.com' ],
		'yandex'     => [ 'yandex.com', 'yandex.ru', 'www.yandex.com', 'www.yandex.ru' ],
		'ask'        => [ 'ask.com', 'www.ask.com' ],
		'aol'        => [ 'aol.com', 'search.aol.com' ],
		'ecosia'     => [ 'ecosia.org', 'www.ecosia.org' ],
		'startpage'  => [ 'startpage.com', 'www.startpage.com' ],
		'searx'      => [ 'searx.org', 'www.searx.org' ],

		// AI search engines (2024-2025)
		'perplexity' => [ 'perplexity.ai', 'www.perplexity.ai' ],
		'you'        => [ 'you.com', 'www.you.com' ],
		'phind'      => [ 'phind.com', 'www.phind.com' ],
		'kagi'       => [ 'kagi.com', 'www.kagi.com' ],
		'searchgpt'  => [ 'chatgpt.com', 'www.chatgpt.com' ], // OpenAI's ChatGPT Search
		'andi'       => [ 'andisearch.com', 'www.andisearch.com' ],
		'deepseek'   => [ 'chat.deepseek.com', 'www.deepseek.com' ],
	];

	/**
	 * Search engine query parameters.
	 *
	 * @var array
	 */
	protected static array $search_params = [
		'q',     // Google, Bing, Yahoo, DuckDuckGo, Baidu, Yandex, Ask, AOL, Ecosia, Startpage, Searx
		'query', // Alternative parameter
		'p',     // Yahoo alternative
		'wd',    // Baidu alternative
		'text',  // Yandex alternative
	];

	/**
	 * Social media platforms with all known domains and short links.
	 * Updated for 2025 with comprehensive domain coverage.
	 *
	 * @var array
	 */
	protected static array $social_platforms = [
		'facebook'  => [
			'facebook.com',
			'www.facebook.com',
			'm.facebook.com',
			'l.facebook.com',
			'lm.facebook.com',
			'business.facebook.com',
			'free.facebook.com',
			'web.facebook.com',
			'mtouch.facebook.com',
			'mbasic.facebook.com',
			'apps.facebook.com',
			'mobile.facebook.com',
			'fb.me',
			'fb.com',
			'm.me'
		],
		'twitter'   => [
			'twitter.com',
			'www.twitter.com',
			't.co',
			'x.com',
			'www.x.com',
			'mobile.twitter.com',
			'tweetdeck.twitter.com'
		],
		'instagram' => [
			'instagram.com',
			'www.instagram.com',
			'l.instagram.com',
			'web.instagram.com',
			'touch.instagram.com',
			'mobile.instagram.com',
			'ig.me'
		],
		'linkedin'  => [
			'linkedin.com',
			'www.linkedin.com',
			'm.linkedin.com',
			'lnkd.in'
		],
		'pinterest' => [
			'pinterest.com',
			'www.pinterest.com',
			'pin.it'
		],
		'reddit'    => [
			'reddit.com',
			'www.reddit.com',
			'm.reddit.com',
			'old.reddit.com',
			'new.reddit.com',
			'redd.it'
		],
		'youtube'   => [
			'youtube.com',
			'www.youtube.com',
			'youtu.be',
			'm.youtube.com',
			'music.youtube.com',
			'gaming.youtube.com'
		],
		'tiktok'    => [
			'tiktok.com',
			'www.tiktok.com',
			'm.tiktok.com',
			'vm.tiktok.com'
		],
		'snapchat'  => [
			'snapchat.com',
			'www.snapchat.com',
			'story.snapchat.com',
			'snap.com'
		],
		'discord'   => [
			'discord.com',
			'www.discord.com',
			'discord.gg',
			'discordapp.com'
		],
		'telegram'  => [
			'telegram.org',
			'www.telegram.org',
			't.me',
			'telegram.me'
		],
		'whatsapp'  => [
			'whatsapp.com',
			'www.whatsapp.com',
			'wa.me',
			'web.whatsapp.com'
		],
		'mastodon'  => [
			'mastodon.social',
			'mastodon.online',
			'fosstodon.org',
			'mastodon.world',
			'mstdn.social'
		],
		'threads'   => [
			'threads.net',
			'www.threads.net'
		],
	];

	/**
	 * Get the current HTTP referrer.
	 *
	 * @return string|null The referrer URL or null if not available.
	 */
	public static function get(): ?string {
		if ( ! isset( $_SERVER['HTTP_REFERER'] ) ) {
			return null;
		}

		$referrer = wp_unslash( $_SERVER['HTTP_REFERER'] );
		$referrer = wp_strip_all_tags( $referrer );

		return ! empty( $referrer ) ? $referrer : null;
	}

	/**
	 * Check if a referrer URL is valid.
	 *
	 * @param string|null $referrer Optional referrer URL to check.
	 *
	 * @return bool True if valid URL.
	 */
	public static function is_valid( ?string $referrer = null ): bool {
		$referrer = $referrer ?? self::get();

		if ( ! $referrer ) {
			return false;
		}

		return filter_var( $referrer, FILTER_VALIDATE_URL ) !== false;
	}

	/**
	 * Get the domain from a referrer URL.
	 *
	 * @param string|null $referrer Optional referrer URL.
	 *
	 * @return string|null The domain or null if invalid.
	 */
	public static function get_domain( ?string $referrer = null ): ?string {
		$referrer = $referrer ?? self::get();

		if ( ! self::is_valid( $referrer ) ) {
			return null;
		}

		$parsed = parse_url( $referrer );

		return $parsed['host'] ?? null;
	}

	/**
	 * Get the root domain (without subdomains).
	 *
	 * @param string|null $referrer Optional referrer URL.
	 *
	 * @return string|null The root domain or null if invalid.
	 */
	public static function get_root_domain( ?string $referrer = null ): ?string {
		$domain = self::get_domain( $referrer );

		if ( ! $domain ) {
			return null;
		}

		// Remove www. prefix
		$domain = preg_replace( '/^www\./', '', $domain );

		// Get the last two parts of the domain (e.g., example.com from sub.example.com)
		$parts = explode( '.', $domain );
		if ( count( $parts ) >= 2 ) {
			return $parts[ count( $parts ) - 2 ] . '.' . $parts[ count( $parts ) - 1 ];
		}

		return $domain;
	}

	/**
	 * Check if referrer is from external domain.
	 *
	 * @param string|null $referrer     Optional referrer URL.
	 * @param string|null $current_site Optional current site domain.
	 *
	 * @return bool True if external referrer.
	 */
	public static function is_external( ?string $referrer = null, ?string $current_site = null ): bool {
		$referrer_domain = self::get_domain( $referrer );

		if ( ! $referrer_domain ) {
			return false;
		}

		$current_domain = $current_site ?? self::get_current_domain();

		return $referrer_domain !== $current_domain;
	}

	/**
	 * Check if referrer is from internal domain.
	 *
	 * @param string|null $referrer     Optional referrer URL.
	 * @param string|null $current_site Optional current site domain.
	 *
	 * @return bool True if internal referrer.
	 */
	public static function is_internal( ?string $referrer = null, ?string $current_site = null ): bool {
		return ! self::is_external( $referrer, $current_site );
	}

	/**
	 * Get search engine from referrer.
	 *
	 * @param string|null $referrer Optional referrer URL.
	 *
	 * @return string|null Search engine name or null if not found.
	 */
	public static function get_search_engine( ?string $referrer = null ): ?string {
		$domain = self::get_domain( $referrer );

		if ( ! $domain ) {
			return null;
		}

		foreach ( self::$search_engines as $engine => $domains ) {
			if ( in_array( $domain, $domains, true ) ) {
				return $engine;
			}
		}

		return null;
	}

	/**
	 * Get search terms from referrer.
	 *
	 * @param string|null $referrer Optional referrer URL.
	 *
	 * @return string|null Search terms or null if not found.
	 */
	public static function get_search_terms( ?string $referrer = null ): ?string {
		$referrer = $referrer ?? self::get();

		if ( ! self::is_valid( $referrer ) || ! self::get_search_engine( $referrer ) ) {
			return null;
		}

		$parsed = parse_url( $referrer );
		if ( ! isset( $parsed['query'] ) ) {
			return null;
		}

		parse_str( $parsed['query'], $params );

		foreach ( self::$search_params as $param ) {
			if ( isset( $params[ $param ] ) && ! empty( $params[ $param ] ) ) {
				return sanitize_text_field( $params[ $param ] );
			}
		}

		return null;
	}

	/**
	 * Check if referrer is from a search engine.
	 *
	 * @param string|null $referrer Optional referrer URL.
	 *
	 * @return bool True if from search engine.
	 */
	public static function is_search_engine( ?string $referrer = null ): bool {
		return self::get_search_engine( $referrer ) !== null;
	}

	/**
	 * Get social media platform from referrer.
	 *
	 * @param string|null $referrer Optional referrer URL.
	 *
	 * @return string|null Social platform name or null if not found.
	 */
	public static function get_social_platform( ?string $referrer = null ): ?string {
		$domain = self::get_domain( $referrer );

		if ( ! $domain ) {
			return null;
		}

		foreach ( self::$social_platforms as $platform => $domains ) {
			if ( in_array( $domain, $domains, true ) ) {
				return $platform;
			}
		}

		return null;
	}

	/**
	 * Check if referrer is from social media.
	 *
	 * @param string|null $referrer Optional referrer URL.
	 *
	 * @return bool True if from social media.
	 */
	public static function is_social( ?string $referrer = null ): bool {
		return self::get_social_platform( $referrer ) !== null;
	}

	/**
	 * Get UTM parameters from referrer.
	 *
	 * @param string|null $referrer Optional referrer URL.
	 *
	 * @return array UTM parameters array.
	 */
	public static function get_utm_parameters( ?string $referrer = null ): array {
		$referrer = $referrer ?? self::get();

		if ( ! self::is_valid( $referrer ) ) {
			return [];
		}

		$parsed = parse_url( $referrer );
		if ( ! isset( $parsed['query'] ) ) {
			return [];
		}

		parse_str( $parsed['query'], $params );

		return [
			'source'   => isset( $params['utm_source'] ) ? sanitize_text_field( $params['utm_source'] ) : null,
			'medium'   => isset( $params['utm_medium'] ) ? sanitize_text_field( $params['utm_medium'] ) : null,
			'campaign' => isset( $params['utm_campaign'] ) ? sanitize_text_field( $params['utm_campaign'] ) : null,
			'term'     => isset( $params['utm_term'] ) ? sanitize_text_field( $params['utm_term'] ) : null,
			'content'  => isset( $params['utm_content'] ) ? sanitize_text_field( $params['utm_content'] ) : null,
		];
	}

	/**
	 * Get campaign source from referrer.
	 *
	 * @param string|null $referrer Optional referrer URL.
	 *
	 * @return string|null Campaign source or null if not found.
	 */
	public static function get_campaign_source( ?string $referrer = null ): ?string {
		$utm_params = self::get_utm_parameters( $referrer );

		return $utm_params['source'];
	}

	/**
	 * Get traffic source type.
	 *
	 * @param string|null $referrer Optional referrer URL.
	 *
	 * @return string Traffic source type: 'search', 'social', 'direct', 'referral', 'campaign', or 'unknown'.
	 */
	public static function get_traffic_source( ?string $referrer = null ): string {
		$referrer = $referrer ?? self::get();

		if ( ! $referrer ) {
			return 'direct';
		}

		if ( ! self::is_valid( $referrer ) ) {
			return 'unknown';
		}

		// Check for UTM parameters first
		$utm_params = self::get_utm_parameters( $referrer );
		if ( $utm_params['source'] || $utm_params['medium'] || $utm_params['campaign'] ) {
			return 'campaign';
		}

		// Check for search engines
		if ( self::is_search_engine( $referrer ) ) {
			return 'search';
		}

		// Check for social media
		if ( self::is_social( $referrer ) ) {
			return 'social';
		}

		// Check if internal
		if ( self::is_internal( $referrer ) ) {
			return 'direct';
		}

		return 'referral';
	}

	/**
	 * Get comprehensive referrer information.
	 *
	 * @param string|null $referrer Optional referrer URL.
	 *
	 * @return array Array of referrer information.
	 */
	public static function get_referrer_info( ?string $referrer = null ): array {
		$referrer = $referrer ?? self::get();

		return [
			'url'             => $referrer,
			'is_valid'        => self::is_valid( $referrer ),
			'domain'          => self::get_domain( $referrer ),
			'root_domain'     => self::get_root_domain( $referrer ),
			'is_external'     => self::is_external( $referrer ),
			'search_engine'   => self::get_search_engine( $referrer ),
			'search_terms'    => self::get_search_terms( $referrer ),
			'social_platform' => self::get_social_platform( $referrer ),
			'utm_parameters'  => self::get_utm_parameters( $referrer ),
			'traffic_source'  => self::get_traffic_source( $referrer ),
		];
	}

	// ========================================
	// Options Methods for Conditional Logic
	// ========================================

	/**
	 * Get search engine options.
	 *
	 * @param bool        $as_value_label Optional. Return as value/label format. Default false.
	 * @param string|null $context        Optional. The context in which the options are being used.
	 *
	 * @return array Array of search engine options.
	 */
	public static function get_search_engine_options( bool $as_value_label = false, ?string $context = null ): array {
		$options = [
			'google'     => __( 'Google', 'arraypress' ),
			'bing'       => __( 'Bing', 'arraypress' ),
			'yahoo'      => __( 'Yahoo', 'arraypress' ),
			'duckduckgo' => __( 'DuckDuckGo', 'arraypress' ),
			'baidu'      => __( 'Baidu', 'arraypress' ),
			'yandex'     => __( 'Yandex', 'arraypress' ),
			'ask'        => __( 'Ask', 'arraypress' ),
			'aol'        => __( 'AOL', 'arraypress' ),
			'ecosia'     => __( 'Ecosia', 'arraypress' ),
			'startpage'  => __( 'Startpage', 'arraypress' ),
			'searx'      => __( 'Searx', 'arraypress' ),
			'perplexity' => __( 'Perplexity', 'arraypress' ),
			'you'        => __( 'You.com', 'arraypress' ),
			'phind'      => __( 'Phind', 'arraypress' ),
			'kagi'       => __( 'Kagi', 'arraypress' ),
			'searchgpt'  => __( 'SearchGPT', 'arraypress' ),
			'andi'       => __( 'Andi', 'arraypress' ),
			'deepseek'   => __( 'DeepSeek', 'arraypress' ),
		];

		/**
		 * Filters the search engine options.
		 *
		 * @param array       $options The search engine options.
		 * @param string|null $context The context in which the options are being used.
		 */
		$options = apply_filters( 'arraypress_referrer_search_engine_options', $options, $context );

		return $as_value_label ? self::to_value_label( $options ) : $options;
	}

	/**
	 * Get social platform options.
	 *
	 * @param bool        $as_value_label Optional. Return as value/label format. Default false.
	 * @param string|null $context        Optional. The context in which the options are being used.
	 *
	 * @return array Array of social platform options.
	 */
	public static function get_social_platform_options( bool $as_value_label = false, ?string $context = null ): array {
		$options = [
			'facebook'  => __( 'Facebook', 'arraypress' ),
			'twitter'   => __( 'Twitter/X', 'arraypress' ),
			'instagram' => __( 'Instagram', 'arraypress' ),
			'linkedin'  => __( 'LinkedIn', 'arraypress' ),
			'pinterest' => __( 'Pinterest', 'arraypress' ),
			'reddit'    => __( 'Reddit', 'arraypress' ),
			'youtube'   => __( 'YouTube', 'arraypress' ),
			'tiktok'    => __( 'TikTok', 'arraypress' ),
			'snapchat'  => __( 'Snapchat', 'arraypress' ),
			'discord'   => __( 'Discord', 'arraypress' ),
			'telegram'  => __( 'Telegram', 'arraypress' ),
			'whatsapp'  => __( 'WhatsApp', 'arraypress' ),
			'mastodon'  => __( 'Mastodon', 'arraypress' ),
			'threads'   => __( 'Threads', 'arraypress' ),
		];

		/**
		 * Filters the social platform options.
		 *
		 * @param array       $options The social platform options.
		 * @param string|null $context The context in which the options are being used.
		 */
		$options = apply_filters( 'arraypress_referrer_social_platform_options', $options, $context );

		return $as_value_label ? self::to_value_label( $options ) : $options;
	}

	/**
	 * Get traffic source options.
	 *
	 * @param bool        $as_value_label Optional. Return as value/label format. Default false.
	 * @param string|null $context        Optional. The context in which the options are being used.
	 *
	 * @return array Array of traffic source options.
	 */
	public static function get_traffic_source_options( bool $as_value_label = false, ?string $context = null ): array {
		$options = [
			'search'   => __( 'Search Engine', 'arraypress' ),
			'social'   => __( 'Social Media', 'arraypress' ),
			'direct'   => __( 'Direct', 'arraypress' ),
			'referral' => __( 'Referral', 'arraypress' ),
			'campaign' => __( 'Campaign', 'arraypress' ),
			'unknown'  => __( 'Unknown', 'arraypress' ),
		];

		/**
		 * Filters the traffic source options.
		 *
		 * @param array       $options The traffic source options.
		 * @param string|null $context The context in which the options are being used.
		 */
		$options = apply_filters( 'arraypress_referrer_traffic_source_options', $options, $context );

		return $as_value_label ? self::to_value_label( $options ) : $options;
	}

	/**
	 * Check if referrer matches specific criteria.
	 *
	 * @param string|array $criteria Search engine, social platform, or traffic source to match.
	 * @param string|null  $referrer Optional referrer URL.
	 *
	 * @return bool True if referrer matches criteria.
	 */
	public static function is_match( $criteria, ?string $referrer = null ): bool {
		if ( is_string( $criteria ) ) {
			$criteria = [ $criteria ];
		}

		if ( ! is_array( $criteria ) ) {
			return false;
		}

		$referrer = $referrer ?? self::get();

		foreach ( $criteria as $criterion ) {
			$criterion = strtolower( trim( $criterion ) );

			// Check search engines
			if ( self::get_search_engine( $referrer ) === $criterion ) {
				return true;
			}

			// Check social platforms
			if ( self::get_social_platform( $referrer ) === $criterion ) {
				return true;
			}

			// Check traffic sources
			if ( self::get_traffic_source( $referrer ) === $criterion ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Convert key/value array to value/label options format.
	 *
	 * @param array $options The options array in key => value format.
	 *
	 * @return array Options in value/label format.
	 */
	protected static function to_value_label( array $options ): array {
		$formatted = [];
		foreach ( $options as $key => $label ) {
			$formatted[] = [
				'value' => $key,
				'label' => $label,
			];
		}

		return $formatted;
	}

	/**
	 * Get current domain.
	 *
	 * @return string Current domain.
	 */
	protected static function get_current_domain(): string {
		$site_url = get_option( 'siteurl' );
		$parsed   = parse_url( $site_url );

		return $parsed['host'] ?? '';
	}

}