<?php
namespace VDB\Uri;

use ErrorException;
use VDB\Uri\Exception\UriSyntaxException;
use VDB\Uri\UriInterface;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 *
 * RFC 3986
 *
 */
class Uri implements UriInterface
{
    public static $defaultPorts = array();

    /** @var string */
    const UNRESERVED = '0-9a-zA-Z-._~';

    /** @var string */
    const GEN_DELIMS = ':/?#[]@';

    /** @var string */
    const SUB_DELIMS = "!$&'()*+,;=";

    /** @var string GEN_DELIMS + SUB_DELIMS */
    const RESERVED = ":/?#[]@!$&'()*+,;=";

    /** @var string UNRESERVED + SUB_DELIMS + ":" + "@" */
    const PCHAR = "0-9a-zA-Z-._~!$&()*+,;=:@";

    /** @var string PCHAR + "?" + "/" */
    const QUERY_OR_FRAGMENT = "0-9a-zA-Z-._~!$&'()*+,;=:@?/";

    private $uri;

    private $baseUri;

    private $remaining;

    private $composedURI;

    protected $authority;

    protected $userInfo;

    protected $scheme;

    protected $host;

    protected $port;

    protected $path;

    protected $query;

    protected $fragment;

    protected $username;

    protected $password;

    /**
     * @param string $uri
     * @param null|string $baseUri
     *
     * @throws UriSyntaxException
     */
    public function __construct($uri, $baseUri = null)
    {
        $this->uri = trim($uri);
        $this->remaining = $this->uri;

        // this handles both absolute and relative references
        $this->parseUriReference();

        // if the reference is relative AND there is a base UriInterface, resolve the relative reference against the base UriInterface
        if (!$this->hasScheme() && null !== $baseUri) {
            try {
                $this->baseUri = new static($baseUri);
                // The base UriInterface has to be absolute, if not, it makes no sense to resolve against it.
                if (!$this->baseUri->hasScheme()) {
                    throw new UriSyntaxException("The base Uri has to be absolute");
                }
            } catch (UriSyntaxException $e) {
                throw new UriSyntaxException("Invalid base Uri: " . $e->getMessage());
            }
            $this->resolveRelativeReference();
        }
    }

    /**
     * Recomposes the components of this Uri as a string.
     *
     * A string equivalent to the original input string, or to the
     * string computed from the original string, as appropriate, is
     * returned.  This can be influence bij normalization, reference resolution,
     * and so a string is constructed from this Uri's components according to
     * the rules specified in RFC 3986 paragraph 5.3
     *
     * @return string The string form of this Uri
     *
     * From RFC 3986 paragraph 5.3:
     *
     * result = ""
     *
     * if defined(scheme) then
     * append scheme to result;
     * append ":" to result;
     * endif;
     *
     * if defined(authority) then
     * append "//" to result;
     * append authority to result;
     * endif;
     *
     * append path to result;
     *
     * if defined(query) then
     * append "?" to result;
     * append query to result;
     * endif;
     *
     * if defined(fragment) then
     * append "#" to result;
     * append fragment to result;
     * endif;
     *
     * return result;
     */
    public function toString()
    {
        if (null === $this->composedURI) {
            $this->composedURI = '';

            if (null !== $this->scheme) {
                $this->composedURI .= $this->scheme;
                $this->composedURI .= ':';
            }

            if (null !== $this->host) {
                $this->composedURI .= '//';
                if (null !== $this->username) {
                    $this->composedURI .= $this->username;
                    if (null !== $this->password) {
                        $this->composedURI .= ':';
                        $this->composedURI .= $this->password;
                    }
                    $this->composedURI .= '@';
                }
                $this->composedURI .= $this->host;
            }

            $this->composedURI .= $this->path;

            if (null !== $this->query) {
                $this->composedURI .= '?';
                $this->composedURI .= $this->query;
            }

            if (null !== $this->fragment) {
                $this->composedURI .= '#';
                $this->composedURI .= $this->fragment;
            }
        }
        return $this->composedURI;
    }

    /**
     * Test two URIs for equality. Will return true if and only if all Uri components are identical.
     * Note: will use the normalized versions of the Uri's to compare
     *
     * @param UriInterface $that
     * @param bool $normalized whether the comparison will be done on normalized versions of the URIs.
     *                         This does not alter the arguments.
     * @return bool
     */
    public function equals(UriInterface $that, $normalized = false)
    {
        $thisClone = $this;
        $thatClone = $that;

        if (false !== $normalized) {
            $thisClone = clone $this;
            $thatClone = clone $that;
            $thisClone->normalize();
            $thatClone->normalize();
        }

        if ($thisClone->getScheme() !== $thatClone->getScheme()) {
            return false;
        } else {
            if ($thisClone->getUsername() !== $thatClone->getUsername()) {
                return false;
            } else {
                if ($thisClone->getPassword() !== $thatClone->getPassword()) {
                    return false;
                } else {
                    if ($thisClone->getHost() !== $thatClone->getHost()) {
                        return false;
                    } else {
                        if ($thisClone->getPort() !== $thatClone->getPort()) {
                            return false;
                        } else {
                            if ($thisClone->getPath() !== $thatClone->getPath()) {
                                return false;
                            } else {
                                if ($thisClone->getQuery() !== $thatClone->getQuery()) {
                                    return false;
                                } else {
                                    if ($thisClone->getFragment() !== $thatClone->getFragment()) {
                                        return false;
                                    } else {
                                        return true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Alias of Uri::toString()
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @return null|string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return null|string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return null|string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return int|null
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return null|string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return null|string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @return null|string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return null|string
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Normalization includes the following:
     *  - dot segements in the path component,
     *  - the port if it matches the default port for the scheme,
     *  - percent encoding and character case where applicable to components, according to RFC 3986.
     *
     * @return $this|UriInterface
     */
    public function normalize()
    {
        $this->normalizeSchemeCase();
        $this->normalizeUsernamePercentageEncoding();
        $this->normalizePasswordPercentageEncoding();
        $this->normalizeHostCase();
        $this->normalizePort();
        $this->normalizePathPercentageEncoding();
        $this->normalizeDotSegments();
        $this->normalizeQueryPercentageEncoding();
        $this->normalizeFragmentPercentageEncoding();

        return $this;
    }

    /**
     * Override this in your subclass to do any scheme specific postprocessing
     */
    protected function doSchemeSpecificPostProcessing()
    {
    }

    protected function validateAuthority()
    {
    }

    protected function validateFragment()
    {
    }

    protected function validateOriginalUrlString()
    {
    }

    protected function validatePassword()
    {
    }

    /**
     * @throws Exception\UriSyntaxException
     */
    protected function validatePath()
    {
        if (null === $this->authority) {
            if ('//' === substr($this->path, 0, 2)) {
                throw new UriSyntaxException(
                    "Invalid path: '" . $this->path . "'. Can't begin with '//' if no authority was found"
                );
            }
        } else {
            if (!empty($this->path) && '/' !== substr($this->path, 0, 1)) {
                throw new UriSyntaxException("Invalid path: '" . $this->path);
            }
        }
    }

    protected function validateQuery()
    {
    }

    /**
     * @throws Exception\UriSyntaxException
     */
    protected function validateScheme()
    {
        $schemeValidated = preg_match('/^[a-z]{1}[a-z0-9\+\-\.]*$/i', $this->scheme);
        if ($schemeValidated === 0 || $schemeValidated === false) {
            throw new UriSyntaxException('Invalid scheme: ' . $this->scheme);
        }
    }

    protected function validateUserInfo()
    {
    }

    protected function validateUsername()
    {
    }

    protected function validateHost()
    {
    }

    /**
     * @throws Exception\UriSyntaxException
     */
    protected function validatePort()
    {
        if (empty($this->port)) {
            throw new UriSyntaxException("Port must not be empty");
        }
        if (!preg_match('/^[0-9]+$/', $this->port)) {
            throw new UriSyntaxException("Port must be numeric: '" . $this->port . "'");
        }
    }

    protected function normalizePasswordPercentageEncoding()
    {
    }

    protected function normalizePathPercentageEncoding()
    {
        if (null !== $this->path) {
            $regex = '/[^' . preg_quote(self::PCHAR, '/') . ']/';
            $segments = explode('/', $this->path);
            foreach ($segments as &$segment) {
                $chars = str_split(urldecode($segment));
                for ($i = 0; $i < count($chars); $i++) {
                    if (preg_match($regex, $chars[$i])) {
                        array_splice($chars, $i, 1, rawurlencode($chars[$i]));
                    }
                }
                $segment = implode('', $chars);
            }
            $this->path = implode('/', $segments);
        }
    }

    protected function normalizeQueryPercentageEncoding()
    {
        $this->query = $this->normalizeQueryOrFragmentEncoding($this->query);
    }

    protected function normalizeFragmentPercentageEncoding()
    {
        $this->fragment = $this->normalizeQueryOrFragmentEncoding($this->fragment);
    }

    protected function normalizeQueryOrFragmentEncoding($item)
    {
        if (null !== $item) {
            $regex = '/[^' . preg_quote(self::QUERY_OR_FRAGMENT, '/') . ']/';
            $chars = str_split(urldecode($item));
            for ($i = 0; $i < count($chars); $i++) {
                if (preg_match($regex, $chars[$i])) {
                    array_splice($chars, $i, 1, rawurlencode($chars[$i]));
                }
            }
            $item  = implode('', $chars);
        }
        return $item;
    }


    protected function normalizeUsernamePercentageEncoding()
    {
    }

    protected function normalizeSchemeCase()
    {
        $this->scheme = strtolower($this->scheme);
    }

    protected function normalizeHostCase()
    {
        $this->host = strtolower($this->host);
    }

    protected function normalizePort()
    {
        if (null !== $this->scheme
            && isset(static::$defaultPorts[$this->scheme])
            && ($this->getPort() === static::$defaultPorts[$this->scheme])
        ) {
            $this->port = null;
        }
    }

    /**
     * From RFC 3986 paragraph 4.2
     *
     * relative-ref  = relative-part [ "?" query ] [ "#" fragment ]
     * relative-part = "//" authority path-abempty
     *                  / path-absolute
     *                  / path-noscheme
     *                  / path-empty
     *
     * then:
     *
     * From RFC 3986 paragraph 5.2.2
     *
     * For each Uri reference (R), the following pseudocode describes an
     * algorithm for transforming R into its target Uri (T):
     *
     *    -- The Uri reference is parsed into the five Uri components
     *    --
     *    (R.scheme, R.authority, R.path, R.query, R.fragment) = parse(R);
     *
     *    -- A non-strict parser may ignore a scheme in the reference
     *    -- if it is identical to the base Uri's scheme.
     *    --
     *    if ((not strict) and (R.scheme == Base.scheme)) then
     *       undefine(R.scheme);
     *    endif;
     *
     *    if defined(R.scheme) then
     *       T.scheme    = R.scheme;
     *       T.authority = R.authority;
     *       T.path      = remove_dot_segments(R.path);
     *       T.query     = R.query;
     *    else
     *       if defined(R.authority) then
     *          T.authority = R.authority;
     *          T.path      = remove_dot_segments(R.path);
     *          T.query     = R.query;
     *       else
     *          if (R.path == "") then
     *             T.path = Base.path;
     *             if defined(R.query) then
     *                T.query = R.query;
     *             else
     *                T.query = Base.query;
     *             endif;
     *          else
     *             if (R.path starts-with "/") then
     *                T.path = remove_dot_segments(R.path);
     *             else
     *                T.path = merge(Base.path, R.path);
     *                T.path = remove_dot_segments(T.path);
     *             endif;
     *             T.query = R.query;
     *          endif;
     *          T.authority = Base.authority;
     *       endif;
     *       T.scheme = Base.scheme;
     *    endif;
     *
     *    T.fragment = R.fragment;
     */
    private function resolveRelativeReference()
    {
        if (null !== $this->scheme) {
            $this->normalizeDotSegments();
        } else {
            $this->scheme = $this->baseUri->scheme;
            if (null !== $this->authority) {
                $this->normalizeDotSegments();
            } else {
                $this->authority = $this->baseUri->authority;
                $this->parseUserInfoHostPort();
                if ('' === $this->path) {
                    $this->path = $this->baseUri->path;
                    if (null === $this->query) {
                        $this->query = $this->baseUri->query;
                    }
                } else {
                    if (0 === strpos($this->path, '/')) {
                        $this->normalizeDotSegments();
                    } else {
                        $this->mergeBasePath();
                        $this->normalizeDotSegments();
                    }
                }
            }
        }
    }

    /**
     * From RFC 3986 paragraph 5.2.4
     *
     * 1.  The input buffer is initialized with the now-appended path
     *     components and the output buffer is initialized to the empty
     *     string.
     *
     * 2.  While the input buffer is not empty, loop as follows:
     *
     *  A.  If the input buffer begins with a prefix of "../" or "./",
     *      then remove that prefix from the input buffer; otherwise,
     *
     *  B.  if the input buffer begins with a prefix of "/./" or "/.",
     *      where "." is a complete path segment, then replace that
     *      prefix with "/" in the input buffer; otherwise,
     *
     *  C.  if the input buffer begins with a prefix of "/../" or "/..",
     *      where ".." is a complete path segment, then replace that
     *      prefix with "/" in the input buffer and remove the last
     *      segment and its preceding "/" (if any) from the output
     *      buffer; otherwise,
     *
     *  D.  if the input buffer consists only of "." or "..", then remove
     *      that from the input buffer; otherwise,
     *
     *  E.  move the first path segment in the input buffer to the end of
     *      the output buffer, including the initial "/" character (if
     *      any) and any subsequent characters up to, but not including,
     *      the next "/" character or the end of the input buffer.
     *
     * 3.  Finally, the output buffer is returned as the result of
     *     remove_dot_segments.
     *
     */
    private function normalizeDotSegments()
    {
        $input = explode('/', $this->path);
        $output = array();

        while (!empty($input)) {
            if ('..' === $input[0]) {
                if (1 === count($input)) {
                    array_shift($input);
                    if ('' !== end($output)) {
                        array_pop($output);
                    }
                    array_push($output, '');
                } else {
                    array_shift($input);
                    if ('' !== end($output)) {
                        array_pop($output);
                    }
                }
            } elseif ('.' === $input[0]) {
                if (1 === count($input)) {
                    array_shift($input);
                    array_push($output, '');
                } else {
                    array_shift($input);
                }
            } else {
                array_push($output, array_shift($input));
            }
        }
        $this->path = implode('/', $output);
    }

    /**
     * From RFC 3986 paragraph 5.2.3
     */
    private function mergeBasePath()
    {
        if (null !== $this->baseUri->authority && '' === $this->baseUri->path) {
            $this->path = '/' . $this->path;
        } else {
            if (false !== $lastSlashPos = strrpos($this->baseUri->path, '/')) {
                $basePath = substr($this->baseUri->path, 0, $lastSlashPos + 1);
                $this->path = $basePath . $this->path;
            }
        }
    }

    private function hasScheme()
    {
        $pos = strpos($this->uri, ':');
        if (false === $pos) {
            return false;
        }
        return true;
    }

    private function parseScheme()
    {
        if (false !== $pos = strpos($this->remaining, ':')) {
            $this->scheme = substr($this->remaining, 0, $pos);
            $this->validateScheme();
            // we do + 1 because we need to also skip the ':' character after the scheme
            $this->remaining = substr($this->remaining, strlen($this->scheme) + 1);
        }
    }

    private function parseAuthority()
    {
        if ('//' === substr($this->remaining, 0, 2)) {
            $this->remaining = substr($this->remaining, 2);
            $this->authority = $this->scanUntilFirstOf($this->remaining, '/?#');
            if (!empty($this->authority)) {
                $this->validateAuthority();
                $this->parseUserInfoHostPort();
                $this->remaining = substr($this->remaining, strlen($this->authority));
            } else {
                $this->authority = null;
            }
        }
    }

    private function parsePath()
    {
        $this->path = $this->scanUntilFirstOf($this->remaining, '?#');
        // This happens when there is nothing after the authority. Legal.
        // Path gets set to empty string because it an empty path is still a path according to spec
        if (false === $this->path) {
            $this->path = '';
        }
        $this->validatePath();
        $this->remaining = substr($this->remaining, strlen($this->path));
    }

    private function parseQuery()
    {
        if ('?' === substr($this->remaining, 0, 1)) {
            $this->remaining = substr($this->remaining, 1);
            if (false === $this->remaining) {
                $this->remaining = '';
            } // This happens when there is only a '?' with nothing after it. Legal
            $this->query = $this->scanUntilFirstOf($this->remaining, '#');
            $this->validateQuery();
            $this->remaining = substr($this->remaining, strlen($this->query));
        }
    }

    private function parseFragment()
    {
        if ('#' === substr($this->remaining, 0, 1)) {
            $this->remaining = substr($this->remaining, 1);
            if (false === $this->remaining) {
                $this->remaining = '';
            } // This happens when there is only a '#' with nothing after it. Legal
            $this->fragment = $this->remaining;
            $this->validateFragment();
            $this->remaining = '';
        }
    }

    /**
     * From RFC 3986 paragraph 4.1
     *
     * Uri-reference = Uri / relative-ref
     */
    private function parseUriReference()
    {
        $this->parseScheme();
        $this->parseAuthority();
        $this->parsePath();
        $this->parseQuery();
        $this->parseFragment();

        $this->doSchemeSpecificPostProcessing();

        if (strlen($this->remaining)) {
            throw new ErrorException("Still something left after parsing, shouldn't happen: '$this->remaining'");
        }
    }

    private function parseUserInfoHostPort()
    {
        if (!$this->authority) {
            throw new UriSyntaxException("Can't parse userInfo, host, port: no authority determined");
        }

        $remaining = $this->authority;

        // There is user info
        if (false !== $atPos = strrpos($remaining, '@')) {
            $this->userInfo = substr($remaining, 0, $atPos);

            // extract username and password
            if (false !== strpos($this->userInfo, ':')) {
                list($this->username, $this->password) = explode(':', $this->userInfo);
                $this->validatePassword();
            } else {
                $this->username = $this->userInfo;
            }
            $this->validateUsername();
            $remaining = substr($remaining, strlen($this->userInfo) + 1);
        }

        if ('[' === substr($remaining, 0, 1)) {
            throw new UriSyntaxException('IPv6 Addresses not yet supported');
        }

        // There is a port
        if (false !== $colonPos = strrpos($remaining, ':')) {
            $this->host = substr($remaining, 0, $colonPos);
            $this->validateHost();
            // we do + 1 because we need to skip the ':' character
            $this->port = substr($remaining, $colonPos + 1);
            $this->port = (int)$this->port;
            $this->validatePort();
        } else {
            $this->host = $remaining;
        }
    }

    private function scanUntilFirstOf($string, $characters)
    {
        if (false === strpbrk($string, $characters)) {
            return $string;
        } else {
            $positions = array();
            for ($i = 0; $i < strlen($characters); $i++) {
                $char = $characters[$i];
                if (false !== $pos = strpos($string, $char)) {
                    $positions[$char] = $pos;
                }
            }
            $firstPos = min($positions);
            return substr($string, 0, $firstPos);
        }
    }
}
