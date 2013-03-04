README
======
What is VDB\URI?
----------------
A set of URI classes and a URI parser, based on RFC 3986, inspired by java.net.URI.

With it, developers can parse, validate, normalize and compare URIs.
Once an URI is parsed, developers can use the URI object to get detailed information about the URI.

The parer is a validating parser, that can handle URLs, URNs, and any URI scheme, such as http, mailto, ftp, etc.

Installation
------------
The best way to install VDB\URI is with composer. Find it on http://packagist.org under the name `vdb/uri`.

Usage
-----
# Example

The first step is creating and URI object. In this example we will be using an HttpURI.
```php
use VDB\URI\HttpURI;

$uri = new HttpURI('http://user:pass@example.com/foo/..?bar#baz');
```
Alternatively, you could use a relative URI:
```php
$uri = new HttpURI('/foo/..?bar#baz', 'http://user:pass@example.com?ignored');
```
The we want to get the validated, normalized, recomposed string of the URI:
```php
$parsedUriString = $uri->recompose(); // http://user:pass@example.com/?bar#baz
```
As you can see, among other things, the path was normalized. This makes it easier to compare URIs.
Instead of calling `recompose()`, you could simple use the URI in a string context
because `__toString()` is implemented as an alias of `recompose()`.

# API

The basics:
* `__construct()`
* `__toString()`
* `recompose()`

Accessors
* `getFragment()`
* `getHost()`
* `getPassword()`
* `getPath()`
* `getPort()`
* `getQuery()`
* `getScheme()`
* `getUsername()`

# Subclassing GenericURI for specific schemes

Although the GenericURI class can be used to parse any URI, different URI schemes (http, https, etc.)
have different rules for what is a valid URI. For example: the HTTP scheme specification states that the path component
of a URL, if empty, should be set to '/'. The best way to implement this custom behavior is by subclassing GenericURI.

There are a few methods you can override in your subclass:

Scheme specific post processing:
* `doSchemeSpecificPostProcessing()`. In here you can do anything you like with the parsed component values

Validators, should throw `VDB\URI\UriSyntaxException` if the component value is invalid:
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

See the HttpURI class for an example implementation.

TODO
----
- [] implement `URI::compare(URI $uri)` public method
- [] implement percentage encoding normalization for username and pass and host
- [] implement validations for all components. Check java.net.URI for reference
- [] refactor: separate classes for parser and URI (value object with DI for parser (+ DI defaults))
- [] add more tests based on C uriparser tests from sourceforge
