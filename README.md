[![Build Status](https://travis-ci.org/mvdbos/vdb-uri.svg?branch=master)](https://travis-ci.org/mvdbos/vdb-uri)

README
======
What is VDB\URI?
----------------
A set of URI classes and a URI parser, based on [RFC 3986](https://www.ietf.org/rfc/rfc3986.txt), inspired by java.net.URI.

With it, developers can parse, validate, normalize and compare URIs.
Once an URI is parsed, developers can use the URI object to get detailed information about the URI.

The parser is a validating parser, that can handle URLs, URNs, and any URI scheme, such as http, mailto, ftp, etc.

It is easy to create your own URI classes for specific URI schemes by extending the Uri, or if you are feeling adventurous, implementing the URI interface.

Installation
------------
The easiest way to install VDB\URI is by downloading [vdb-uri.phar](https://github.com/matthijsvandenbos/vdb-uri/raw/master/bin/vdb-uri.phar).
Then you only have to require the Phar file to begin using VDB\URI: `require_once '/path/to/vdb-uri.phar';`

A more flexible way to install VDB\URI is with [composer](http://getcomposer.org/). That way you can keep control over which version you use. Find it on http://packagist.org under the name `vdb/uri`.

Usage
-----
## Example

The first step is creating and URI object. In this example we will be using an the generic Uri class.
```php
use VDB\Uri\Uri;

$uri = new Uri('http://user:pass@example.com/foo/..?bar#baz');
```
Alternatively, you could use a relative reference with a base URI to resolve it against:
```php
$uri = new Uri('/foo/..?bar#baz', 'http://user:pass@example.com?ignored');
```
Or, if no base URI is known, only supply the relative reference:
```php
$relativeUri = new Uri('/foo/..?bar#baz');
```
Then we can get the validated, recomposed string of the URI:
```php
$parsedUriString = $uri->toString(); // 'http://user:pass@example.com/?bar#baz'
$parsedRelativeUriString = $relativeUri->toString(); // '/foo/..?bar#baz'
```
Or access its separate components with accessors:
```php
$query = $uri->getQuery(); // 'bar'
```
As an alternative to calling `toString()`, you could simple use the URI in a string context
because `__toString()` is implemented as an alias of `toString()`.

> Note that normalization doesn't happen automatically, you have to call `normalize()` for that.
Normalization includes the following:
 - dot segements in the path component,
 - the port if it matches the default port for the scheme,
 - percent encoding and character case where applicable according to RFC 3986.
The only exception to this: when constructing a relative reference WITH a base URI, the path dot segments get normalized automatically as part of resolving the relative reference against the absolute base URI.

## API

The basics:
* `__construct($reference, $baseUri = null)`
* `__toString()`
* `toString()`

Normalization
* `normalize()`

Comparison
* `equals(URI $uri, $normalized)

Accessors
* `getFragment()`
* `getHost()`
* `getPassword()`
* `getPath()`
* `getPort()`
* `getQuery()`
* `getScheme()`
* `getUsername()`

## Subclassing Uri for specific schemes

Although the Uri class can be used to parse any URI, different URI schemes (http, https, etc.)
have different rules for what is a valid URI. For example: the HTTP scheme specification states that the path component
of a URL, if empty, should be set to '/'. The best way to implement this custom behavior is by subclassing Uri.

There are a few methods you can override in your subclass:

Scheme specific post processing:
* `doSchemeSpecificPostProcessing()`. In here you can do anything you like with the parsed component values

Validators. Should throw `VDB\URI\UriSyntaxException` if the component value is invalid:
* `validateAuthority()`
* `validateFragment()`
* `validateHost()`
* `validateOriginalUrlString()`
* `validatePassword()`
* `validatePath()`
* `validatePort()`
* `validateQuery()`
* `validateScheme()`
* `validateUserInfo()`
* `validateUsername()`

See the Http class for an example implementation.

## Usage tips
If you want to use type hinting (you should) on VDB\Uri classes in your application, you should use the UriInterface for that instead of the Uri class.
That way, your application will support switching out different implementations of URI classes without any refactoring.      

TODO
----
For a list of todo's check the issues in milestone feature-complete [here](https://github.com/matthijsvandenbos/vdb-uri/issues?direction=asc&milestone=1&page=1&sort=created&state=open).
