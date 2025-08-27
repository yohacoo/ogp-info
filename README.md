# ogp-info.php

Library for retrieving OGP information via HTTP/HTTPS.

## Install

```
composer require yohacoo/ogp-info
```

Or simply copy src/OgpInfo.php to your project.

## Usage

```
require_once('OgpInfo.php');

use Yohacoo\OgpInfo\OgpInfo;

OgpInfo::setCacheDir('/path/to/.ogp-cache');
$info = OgpInfo::retrieve('https://example.com/');

$title = $info->get('og:title');
$description = $info->get('og:description');
```

We strongly recommend setting a cache directory.
The default cache directory is .ogp-cache under the directory containing OgpInfo.php.

## API

### OgpInfo->get($key)

Get a retrieved value.

| Key | Description |
| --- | --- |
| og:xxx | og:xxx value in \<meta property="og:xxx" content="..."\> |
| fb:xxx | fb:xxx value in \<meta property="fb:xxx" content="..."\> |
| twitter:xxx | twitter:xxx value in \<meta name="twitter:xxx" content="..."\> |
| title | \<title\> tag text content |
| description | description value in \<meta name="description" content="..."\> |
| icon | href value in \<link rel="icon" href="..."\><br>Return the absolute URL if the href starts with '/'. |
| apple-touch-icon | href value in \<link rel="apple-touch-icon" href="..."\><br>Return the absolute URL if the href starts with '/'. |

If no value exists for the key, return an empty string ('').

### OgpInfo->getUrl()

Get URL for retrieving information.

### OgpInfo->getHttpStatus()

Get HTTP status code.

### OgpInfo->getTimestamp()

Get timestamp.
Returns the number of seconds since the epoch.

### OgpInfo->isExpired()

Check whether it has passed the TTL.

## Static API

### OgpInfo::setCacheDir($dir)

Set the cache directory.

### OgpInfo::retrieve($url)

Retrieve OGP information via HTTP.

### OgpInfo::setCacheTtl($ttl)

Set the cache TTL.

### OgpInfo::clearCache()

Delete old cache files.
