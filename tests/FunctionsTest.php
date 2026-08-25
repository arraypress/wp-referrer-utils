<?php
/**
 * Global helper tests.
 *
 * @package ArrayPress\ReferrerUtils
 */

declare( strict_types=1 );

namespace ArrayPress\ReferrerUtils\Tests;

use PHPUnit\Framework\TestCase;

/**
 * The five global helpers.
 *
 * They differ from the class they wrap in one way worth testing: several of
 * them promise a string where the method returns null, so the coalescing has
 * to actually be there.
 */
final class FunctionsTest extends TestCase {

	/**
	 * Clear the stubbed state.
	 */
	protected function setUp(): void {
		ru_reset_globals();
	}

	/**
	 * And again.
	 */
	protected function tearDown(): void {
		ru_reset_globals();
	}

	/**
	 * All five are declared.
	 */
	public function test_the_helpers_are_declared(): void {
		foreach (
			[
				'get_referrer_url',
				'get_referrer_source',
				'get_referrer_utm_params',
				'is_referrer_from_search',
				'get_referrer_search_terms',
			] as $function
		) {
			$this->assertTrue( function_exists( $function ), sprintf( '%s() was never declared.', $function ) );
		}
	}

	/**
	 * Each one forwards to the method it names.
	 */
	public function test_each_helper_forwards_correctly(): void {
		$url = 'https://www.google.com/search?q=felt+hats';

		// get_source(), not get_traffic_source(). The two sit next to each
		// other and answer different questions: this one names the referrer
		// ("google"), the other gives the category it falls into ("search").
		// A wrapper pointed at the wrong one still returns a plausible string.
		$this->assertSame( 'google', get_referrer_source( $url ) );
		$this->assertSame( 'search', \ArrayPress\ReferrerUtils\Referrer::get_traffic_source( $url ) );

		$this->assertTrue( is_referrer_from_search( $url ) );
		$this->assertSame( 'felt hats', get_referrer_search_terms( $url ) );
		$this->assertSame( 'news', get_referrer_utm_params( 'https://x.test/?utm_source=news' )['source'] );
	}

	/**
	 * The helpers that promise a string return one, not null.
	 *
	 * Their signatures say `: string`, so a null slipping through is a
	 * TypeError on a page that merely had no referrer -- which is most of
	 * them.
	 */
	public function test_the_string_helpers_never_return_null(): void {
		$this->assertSame( '', get_referrer_url() );
		$this->assertSame( '', get_referrer_search_terms( 'https://blog.test/' ) );
		$this->assertSame( 'direct', get_referrer_source() );
	}

	/**
	 * The url helper reads the request.
	 */
	public function test_the_url_helper_reads_the_request(): void {
		$_SERVER['HTTP_REFERER'] = 'https://example.test/a';

		$this->assertSame( 'https://example.test/a', get_referrer_url() );
	}

	/**
	 * The utm helper always gives the five keys.
	 */
	public function test_the_utm_helper_always_gives_the_shape(): void {
		$this->assertSame(
			[ 'source', 'medium', 'campaign', 'term', 'content' ],
			array_keys( get_referrer_utm_params( 'https://example.test/' ) )
		);
	}
}
