# Referrer

Work out where a visitor came from, and strip the tracking noise off the URL
before you store it.

## What it does

The referrer tells you whether somebody arrived from Google, Facebook or a
link on someone's blog — but the raw header is a full URL with campaign
parameters hanging off it, and answering "was this a search?" means knowing
what several hundred search domains look like.

This reads the referrer, says which search engine or social platform it was,
and pulls the campaign parameters out separately so what you store is the
page rather than the tracking.

## Features

* Tell whether a visit came from a search engine, and which one
* Tell whether it came from social, and which platform
* Pull UTM and campaign parameters out as their own data
* Strip tracking parameters from a URL without eating its real ones
* Get the domain, or the root domain, without parsing it yourself
* Recognise a referrer from your own site, so internal navigation is not counted

## Installation

```bash
composer require arraypress/wp-referrer-utils
```

## Quick start

Record where an order came from, once, at checkout:

```php
$source = get_referrer_source();          // "google", "facebook", or a domain
$utm    = get_referrer_utm_params();      // campaign, medium, source

update_post_meta( $order_id, '_source', $source );
update_post_meta( $order_id, '_utm', $utm );
```

Cleaning a URL before it is stored or compared:

```php
use ArrayPress\ReferrerUtils\Tracking;

$clean = Tracking::strip( $url );
```

Only vendor-namespaced parameters are stripped — `utm_*`, `fbclid`, `gclid`
and their kind. A bare `s` or `v` is left alone, because those are WordPress
search and YouTube video ids, and a global strip list eats both.

## Requirements

* PHP 8.3 or later
* WordPress 7.1 or later

## License

GPL-2.0-or-later
