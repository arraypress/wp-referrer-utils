<?php
/**
 * Referrer parsing tests.
 *
 * @package ArrayPress\ReferrerUtils
 */

declare( strict_types=1 );

namespace ArrayPress\ReferrerUtils\Tests;

use ArrayPress\ReferrerUtils\Referrer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * What this library does is classify a URL somebody else sent.
 *
 * The classification is table-driven -- a list of domains per search engine,
 * another per social platform -- and a table lookup that quietly misses is the
 * failure mode worth guarding. Nothing errors. The visit is simply counted as
 * a plain referral, and the only way to notice is to look at a report and
 * wonder why no traffic ever comes from Google.
 */
final class ReferrerTest extends TestCase {

	/**
	 * Clear the stubbed options, filters and superglobal.
	 */
	protected function setUp(): void {
		ru_reset_globals();
	}

	/**
	 * And again, so nothing leaks.
	 */
	protected function tearDown(): void {
		ru_reset_globals();
	}

	/**
	 * A search engine is recognised whether or not the host carries `www.`.
	 *
	 * This is what the library got wrong. Referrers arrive as
	 * `www.google.com`; the table lists `google.com`; the comparison was
	 * exact, so Google -- twenty-one domains of it -- was never recognised at
	 * all and every search visit was filed as a referral. A few entries did
	 * carry their `www.` form, which is why the tables looked right and some
	 * platforms worked.
	 *
	 * @param string $url    The referrer.
	 * @param string $engine The engine it should be attributed to.
	 */
	#[DataProvider( 'searchEngineProvider' )]
	public function test_a_search_engine_is_recognised_with_or_without_www( string $url, string $engine ): void {
		$this->assertSame( $engine, Referrer::get_search_engine( $url ) );
		$this->assertTrue( Referrer::is_search_engine( $url ) );
		$this->assertSame( 'search', Referrer::get_traffic_source( $url ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function searchEngineProvider(): array {
		return [
			'google with www'    => [ 'https://www.google.com/search?q=hats', 'google' ],
			'google without'     => [ 'https://google.com/search?q=hats', 'google' ],
			'a country google'   => [ 'https://www.google.co.uk/search?q=hats', 'google' ],
			'bing'               => [ 'https://www.bing.com/search?q=hats', 'bing' ],
			'duckduckgo'         => [ 'https://duckduckgo.com/?q=hats', 'duckduckgo' ],
		];
	}

	/**
	 * A social platform is recognised the same way.
	 */
	public function test_a_social_platform_is_recognised_with_or_without_www(): void {
		$this->assertSame( 'facebook', Referrer::get_social_platform( 'https://www.facebook.com/x' ) );
		$this->assertSame( 'facebook', Referrer::get_social_platform( 'https://facebook.com/x' ) );
		$this->assertTrue( Referrer::is_social( 'https://facebook.com/x' ) );
		$this->assertSame( 'social', Referrer::get_traffic_source( 'https://facebook.com/x' ) );
	}

	/**
	 * Something that is neither is neither.
	 */
	public function test_an_ordinary_site_is_a_plain_referral(): void {
		$this->assertNull( Referrer::get_search_engine( 'https://blog.test/post' ) );
		$this->assertNull( Referrer::get_social_platform( 'https://blog.test/post' ) );
		$this->assertFalse( Referrer::is_search_engine( 'https://blog.test/post' ) );
		$this->assertFalse( Referrer::is_social( 'https://blog.test/post' ) );
		$this->assertSame( 'referral', Referrer::get_traffic_source( 'https://blog.test/post' ) );
	}

	/**
	 * No referrer at all is direct traffic.
	 */
	public function test_no_referrer_is_direct(): void {
		$this->assertNull( Referrer::get() );
		$this->assertSame( 'direct', Referrer::get_traffic_source() );
		$this->assertFalse( Referrer::is_valid() );
		$this->assertNull( Referrer::get_domain() );
	}

	/**
	 * Search terms are pulled out of the query.
	 */
	public function test_search_terms_are_extracted(): void {
		$this->assertSame( 'felt hats', Referrer::get_search_terms( 'https://www.google.com/search?q=felt+hats' ) );
		$this->assertSame( 'felt hats', Referrer::get_search_terms( 'https://www.bing.com/search?q=felt%20hats' ) );
	}

	/**
	 * A site that is not a search engine has no search terms, whatever its
	 * query string says.
	 */
	public function test_a_non_search_engine_has_no_search_terms(): void {
		$this->assertNull( Referrer::get_search_terms( 'https://blog.test/?q=hats' ) );
	}

	/**
	 * The root domain drops subdomains without eating the registered name.
	 *
	 * Taking the last two labels gives "co.uk" for every site in Britain,
	 * which groups the entire country under one referrer.
	 *
	 * @param string $url    The referrer.
	 * @param string $expect The registered domain.
	 */
	#[DataProvider( 'rootDomainProvider' )]
	public function test_the_root_domain_survives_a_compound_suffix( string $url, string $expect ): void {
		$this->assertSame( $expect, Referrer::get_root_domain( $url ) );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function rootDomainProvider(): array {
		return [
			'a subdomain'         => [ 'https://sub.example.com/x', 'example.com' ],
			'www'                 => [ 'https://www.example.com/x', 'example.com' ],
			'already bare'        => [ 'https://example.com/x', 'example.com' ],
			'a British domain'    => [ 'https://www.example.co.uk/x', 'example.co.uk' ],
			'and a subdomain too' => [ 'https://news.bbc.co.uk/x', 'bbc.co.uk' ],
			'an Australian one'   => [ 'https://a.b.example.com.au/x', 'example.com.au' ],
			'a single label'      => [ 'http://localhost/x', 'localhost' ],
		];
	}

	/**
	 * The host comes back as sent, subdomain and all.
	 */
	public function test_the_domain_is_the_host_as_sent(): void {
		$this->assertSame( 'news.bbc.co.uk', Referrer::get_domain( 'https://news.bbc.co.uk/story' ) );
		$this->assertNull( Referrer::get_domain( 'not a url' ) );
	}

	/**
	 * Only something that is actually a URL is valid.
	 *
	 * The value arrives in a request header, so this is the gate everything
	 * else sits behind.
	 *
	 * @param string $value What arrived.
	 * @param bool   $valid Whether it should be accepted.
	 */
	#[DataProvider( 'validityProvider' )]
	public function test_only_a_url_is_valid( string $value, bool $valid ): void {
		$this->assertSame( $valid, Referrer::is_valid( $value ) );
	}

	/**
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public static function validityProvider(): array {
		return [
			'an https url'    => [ 'https://example.test/a', true ],
			'an http url'     => [ 'http://example.test/a', true ],
			'prose'           => [ 'not a url', false ],
			'empty'           => [ '', false ],
			'a bare host'     => [ 'example.test', false ],
			'a javascript'    => [ 'javascript:alert(1)', false ],
		];
	}

	/**
	 * The referrer is read from the request, unslashed and stripped.
	 */
	public function test_the_referrer_is_read_from_the_request(): void {
		$_SERVER['HTTP_REFERER'] = 'https://example.test/a?b=1';

		$this->assertSame( 'https://example.test/a?b=1', Referrer::get() );
	}

	/**
	 * Slashes core added are taken back off.
	 *
	 * Asserted on the apostrophe rather than a quote mark, because
	 * esc_url_raw() strips double quotes outright and the test would pass
	 * whether anything unslashed the value or not.
	 */
	public function test_the_referrer_is_unslashed(): void {
		$_SERVER['HTTP_REFERER'] = "https://example.test/it\\'s";

		$referrer = (string) Referrer::get();

		$this->assertStringNotContainsString( '\\', $referrer, 'The slashes core added are still on.' );
		$this->assertSame( "https://example.test/it's", $referrer );
	}

	/**
	 * Markup in the header does not survive.
	 */
	public function test_markup_in_the_referrer_is_stripped(): void {
		$_SERVER['HTTP_REFERER'] = 'https://example.test/<script>alert(1)</script>';

		$referrer = (string) Referrer::get();

		$this->assertStringNotContainsString( '<script', $referrer );
	}

	/**
	 * A blank header reads as no referrer at all.
	 */
	public function test_a_blank_referrer_is_no_referrer(): void {
		$_SERVER['HTTP_REFERER'] = '';

		$this->assertNull( Referrer::get() );
	}

	/**
	 * Whether a referrer is off-site is judged against the site's own domain.
	 */
	public function test_external_is_judged_against_the_site_domain(): void {
		$this->assertTrue( Referrer::is_external( 'https://other.test/x' ) );
		$this->assertFalse( Referrer::is_external( 'https://example.test/x' ) );

		$this->assertTrue( Referrer::is_internal( 'https://example.test/x' ) );
		$this->assertFalse( Referrer::is_internal( 'https://other.test/x' ) );
	}

	/**
	 * The site to compare against can be given.
	 */
	public function test_the_site_to_compare_against_can_be_given(): void {
		$this->assertFalse( Referrer::is_external( 'https://other.test/x', 'other.test' ) );
		$this->assertTrue( Referrer::is_external( 'https://other.test/x', 'example.test' ) );
	}

	/**
	 * No referrer is not external.
	 *
	 * Nothing to compare, so nothing to claim. Answering true would file every
	 * direct visit as an external referral.
	 */
	public function test_no_referrer_is_not_external(): void {
		$this->assertFalse( Referrer::is_external() );
	}

	/**
	 * UTM parameters are read off the query string.
	 */
	public function test_utm_parameters_are_read(): void {
		$url = 'https://example.test/?utm_source=news&utm_medium=email&utm_campaign=spring&utm_term=hats&utm_content=top';

		$this->assertSame(
			[
				'source'   => 'news',
				'medium'   => 'email',
				'campaign' => 'spring',
				'term'     => 'hats',
				'content'  => 'top',
			],
			Referrer::get_utm_parameters( $url )
		);

		$this->assertSame( 'news', Referrer::get_utm_parameter( 'source', $url ) );
		$this->assertNull( Referrer::get_utm_parameter( 'nonsense', $url ) );
	}

	/**
	 * A URL with no UTM parameters gives the shape with nothing in it.
	 *
	 * The keys are always there, so reporting code does not have to check
	 * before reading.
	 */
	public function test_a_url_with_no_utm_still_gives_the_shape(): void {
		$this->assertSame(
			[
				'source'   => null,
				'medium'   => null,
				'campaign' => null,
				'term'     => null,
				'content'  => null,
			],
			Referrer::get_utm_parameters( 'https://example.test/' )
		);
	}

	/**
	 * An invalid referrer gives the shape too, not an empty array.
	 *
	 * The branch that matters most, because it is the one every direct visit
	 * takes: get_campaign_source() and get_traffic_source() both read
	 * $params['source'] straight out, so an empty array is an undefined key
	 * warning on the commonest path through the library.
	 *
	 * @param string|null $referrer What arrived, if anything.
	 */
	#[DataProvider( 'noUtmProvider' )]
	public function test_anything_without_utm_still_gives_the_shape( ?string $referrer ): void {
		$this->assertSame(
			[ 'source', 'medium', 'campaign', 'term', 'content' ],
			array_keys( Referrer::get_utm_parameters( $referrer ) )
		);

		// And the two readers of it come back clean rather than warning.
		$this->assertNull( Referrer::get_campaign_source( $referrer ) );
		$this->assertIsString( Referrer::get_traffic_source( $referrer ) );
	}

	/**
	 * @return array<string, array{0: string|null}>
	 */
	public static function noUtmProvider(): array {
		return [
			'no referrer at all' => [ null ],
			'not a url'          => [ 'not a url' ],
			'empty'              => [ '' ],
			'a url with no query'=> [ 'https://example.test/' ],
			'a query, no utm'    => [ 'https://example.test/?a=1' ],
		];
	}

	/**
	 * A campaign source outranks the referring domain.
	 */
	public function test_a_tagged_campaign_is_the_source(): void {
		$this->assertSame( 'campaign', Referrer::get_traffic_source( 'https://blog.test/?utm_source=newsletter' ) );
	}

	/**
	 * The summary carries every part in one call.
	 */
	public function test_the_summary_carries_every_part(): void {
		$info = Referrer::get_referrer_info( 'https://www.google.com/search?q=hats' );

		$this->assertSame( 'www.google.com', $info['domain'] );
		$this->assertSame( 'google.com', $info['root_domain'] );
		$this->assertSame( 'google', $info['search_engine'] );
		$this->assertSame( 'search', $info['traffic_source'] );
		$this->assertSame( 'hats', $info['search_terms'] );
		$this->assertNull( $info['social_platform'] );
	}

	/**
	 * The option lists come in both shapes and are not empty.
	 *
	 * @param string $method The getter.
	 */
	#[DataProvider( 'optionProvider' )]
	public function test_an_option_list_comes_in_both_shapes( string $method ): void {
		$map = Referrer::$method();

		$this->assertNotEmpty( $map );

		$options = Referrer::$method( true );

		$this->assertCount( count( $map ), $options );
		$this->assertSame( [ 'value', 'label' ], array_keys( $options[0] ) );
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function optionProvider(): array {
		return [
			'search engines'  => [ 'get_search_engine_options' ],
			'social networks' => [ 'get_social_platform_options' ],
			'traffic sources' => [ 'get_traffic_source_options' ],
			'utm parameters'  => [ 'get_utm_parameter_options' ],
		];
	}

	/**
	 * Every search engine and platform in the tables can be selected.
	 *
	 * The dropdown and the classifier read the same tables, so a key in one
	 * and not the other gives an option nothing ever matches.
	 */
	public function test_every_listed_engine_and_platform_is_selectable(): void {
		$this->assertArrayHasKey( 'google', Referrer::get_search_engine_options() );
		$this->assertArrayHasKey( 'bing', Referrer::get_search_engine_options() );
		$this->assertArrayHasKey( 'facebook', Referrer::get_social_platform_options() );
	}
}
