# WordPress Referrer Utils - HTTP Referrer Detection & Analysis

A lightweight WordPress library for detecting and analyzing HTTP referrers. Perfect for analytics, security, and understanding your traffic sources without external dependencies.

## Features

* 🎯 **Clean API**: WordPress-style snake_case methods with consistent interfaces
* 🔍 **Traffic Analysis**: Identify search engines, social media, and campaign sources
* 🔒 **Security Ready**: Validate referrers for CSRF protection and security checks
* 📊 **UTM Support**: Extract campaign parameters for marketing analysis
* 🔗 **Domain Operations**: Check internal vs external referrers
* 🌐 **Search Engine Detection**: Identify 10+ search engines and extract search terms
* 📱 **Social Media Detection**: Recognize 13+ social platforms
* 🛡️ **WordPress Native**: Uses WordPress sanitization and validation

## Requirements

* PHP 7.4 or later
* WordPress 5.0 or later

## Installation

```bash
composer require arraypress/wp-referrer-utils
```

## Basic Usage

### Get Referrer Information

```php
use ArrayPress\ReferrerUtils\Referrer;

// Get current referrer
$referrer = Referrer::get();
// Returns: "https://google.com/search?q=wordpress" or null

// Check if valid URL
if ( Referrer::is_valid( $referrer ) ) {
	// Valid referrer URL
}

// Get domain from referrer
$domain = Referrer::get_domain();
// Returns: "google.com"
```

### Traffic Source Analysis

```php
// Get traffic source type
$source = Referrer::get_traffic_source();
// Returns: 'search', 'social', 'direct', 'referral', 'campaign', or 'unknown'

// Check specific source types
if ( Referrer::is_search_engine() ) {
	// Visitor came from search engine
}

if ( Referrer::is_social() ) {
	// Visitor came from social media
}

if ( Referrer::is_external() ) {
	// Visitor came from external site
}
```

### Search Engine Detection

```php
// Get search engine name
$engine = Referrer::get_search_engine();
// Returns: "google", "bing", "yahoo", "duckduckgo", etc.

// Get search terms
$terms = Referrer::get_search_terms();
// Returns: "wordpress plugins" or null
```

### Social Media Detection

```php
// Get social platform
$platform = Referrer::get_social_platform();
// Returns: "facebook", "twitter", "instagram", "linkedin", etc.
```

### Campaign Tracking

```php
// Get UTM parameters
$utm = Referrer::get_utm_parameters();
/*
Returns:
[
    'source' => 'newsletter',
    'medium' => 'email', 
    'campaign' => 'summer-sale',
    'term' => 'discount',
    'content' => 'header-link'
]
*/
```

### Comprehensive Analysis

```php
// Get all referrer information at once
$info = Referrer::get_referrer_info();
/*
Returns:
[
    'url' => 'https://google.com/search?q=wordpress',
    'is_valid' => true,
    'domain' => 'google.com',
    'root_domain' => 'google.com',
    'is_external' => true,
    'search_engine' => 'google',
    'search_terms' => 'wordpress',
    'social_platform' => null,
    'utm_parameters' => [],
    'traffic_source' => 'search'
]
*/
```

## Common Use Cases

### Analytics Tracking

```php
function track_visitor_sources() {
	$info = Referrer::get_referrer_info();

	// Store analytics data
	$analytics = [
		'traffic_source'  => $info['traffic_source'],
		'search_engine'   => $info['search_engine'],
		'search_terms'    => $info['search_terms'],
		'social_platform' => $info['social_platform'],
		'utm_source'      => $info['utm_parameters']['source'],
		'timestamp'       => current_time( 'mysql' )
	];

	update_option( 'traffic_analytics', $analytics );
}
add_action( 'wp_head', 'track_visitor_sources' );
```

### CSRF Protection

```php
function check_form_referrer() {
	if ( ! Referrer::is_internal() ) {
		wp_die( 'Invalid form submission - referrer check failed.' );
	}
}

// Use in form processing
if ( $_POST['submit'] ) {
	check_form_referrer();
	// Process form...
}
```

### Content Personalization

```php
function personalize_content_by_source() {
	$source = Referrer::get_traffic_source();

	switch ( $source ) {
		case 'search':
			echo '<div class="welcome-search">Welcome from search!</div>';
			break;
		case 'social':
			$platform = Referrer::get_social_platform();
			echo "<div class='welcome-social'>Thanks for visiting from {$platform}!</div>";
			break;
		case 'campaign':
			$utm = Referrer::get_utm_parameters();
			echo "<div class='welcome-campaign'>Welcome {$utm['campaign']} visitor!</div>";
			break;
	}
}
add_action( 'wp_head', 'personalize_content_by_source' );
```

## Supported Platforms

### Search Engines (17+)
**Traditional Search Engines:**
- **Google** - All international domains (google.com, google.co.uk, etc.)
- **Bing** - Microsoft's search engine
- **Yahoo** - Yahoo Search with international variants
- **DuckDuckGo** - Privacy-focused search
- **Baidu** - Chinese search engine
- **Yandex** - Russian search engine
- **Ask** - Ask.com search
- **AOL** - AOL Search
- **Ecosia** - Environmental search engine
- **Startpage** - Privacy search engine

**AI Search Engines (2024-2025):**
- **Perplexity** - AI-powered answer engine with citations
- **You.com** - AI search with personalized results
- **Phind** - AI search engine for developers
- **Kagi** - Premium ad-free search with AI features
- **SearchGPT** - OpenAI's ChatGPT Search
- **Andi** - Conversational AI search assistant
- **DeepSeek** - AI-powered search and chat

### Social Media Platforms (13+)
- **Facebook** - Including mobile variants and short links (fb.me, m.me)
- **Twitter/X** - Including t.co short links and x.com rebrand
- **Instagram** - Meta's photo platform with ig.me short links
- **LinkedIn** - Professional networking with lnkd.in short links
- **Pinterest** - Visual discovery with pin.it short links
- **Reddit** - Social news aggregation with redd.it short links
- **YouTube** - Video platform and youtu.be short links
- **TikTok** - Short video platform with vm.tiktok.com
- **Snapchat** - Multimedia messaging
- **Discord** - Gaming and community chat with discord.gg
- **Telegram** - Messaging platform with t.me short links
- **WhatsApp** - Messaging app with wa.me short links
- **Mastodon** - Decentralized social network
- **Threads** - Meta's Twitter alternative

### Campaign Parameters
- **UTM Parameters** - utm_source, utm_medium, utm_campaign, utm_term, utm_content
- **Search Parameters** - q, query, p, wd, text (for search term extraction)

## Method Reference

### Core Methods
- `get()` - Get current HTTP referrer
- `is_valid( ?string $referrer )` - Check if referrer is valid URL
- `get_domain( ?string $referrer )` - Get domain from referrer
- `get_root_domain( ?string $referrer )` - Get root domain without subdomains

### Domain Operations
- `is_external( ?string $referrer, ?string $current_site )` - Check if external referrer
- `is_internal( ?string $referrer, ?string $current_site )` - Check if internal referrer

### Search Engine Detection
- `get_search_engine( ?string $referrer )` - Get search engine name
- `get_search_terms( ?string $referrer )` - Get search terms
- `is_search_engine( ?string $referrer )` - Check if from search engine

### Social Media Detection
- `get_social_platform( ?string $referrer )` - Get social platform name
- `is_social( ?string $referrer )` - Check if from social media

### Campaign Tracking
- `get_utm_parameters( ?string $referrer )` - Get UTM parameters array
- `get_campaign_source( ?string $referrer )` - Get campaign source

### Analysis
- `get_traffic_source( ?string $referrer )` - Get traffic source type
- `get_referrer_info( ?string $referrer )` - Get comprehensive referrer information

## WordPress Integration

- **Sanitized Input**: All referrer data is sanitized using `wp_strip_all_tags()` and `wp_unslash()`
- **Enhanced Security**: UTM parameters and search terms use `sanitize_text_field()` for additional protection
- **WordPress Native**: Uses `get_option('siteurl')` for site URL detection
- **Performance Optimized**: Efficient domain lookups with comprehensive coverage
- **Standards Compliant**: Follows WordPress coding standards and best practices

## Privacy Considerations

This library analyzes referrer headers that are automatically sent by browsers. It:
- **Does not store personal data** - only analyzes traffic patterns
- **Respects user privacy** - works with publicly available referrer information
- **GDPR Compliant** - when used for analytics, follows standard referrer analysis practices

## Requirements

- PHP 7.4+
- WordPress 5.0+

## Testing

The library has been thoroughly tested with:
- ✅ **100% test coverage** for core functionality
- ✅ **Real-world referrer patterns** from major platforms
- ✅ **Edge case handling** for malformed URLs and invalid input
- ✅ **Performance validation** with hundreds of URLs
- ✅ **WordPress integration** with proper sanitization
- ✅ **Modern AI search engines** and social media short links

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-2.0-or-later License.

## Support

- [Documentation](https://github.com/arraypress/wp-referrer-utils)
- [Issue Tracker](https://github.com/arraypress/wp-referrer-utils/issues)