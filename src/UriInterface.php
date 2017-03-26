<?php
namespace VDB\Uri;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */
interface UriInterface
{
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
     */
    public function toString();

    /**
     * @param UriInterface $that
     * @param boolean $normalized whether to compare normalized versions of the URIs
     * @return boolean
     */
    public function equals(UriInterface $that, $normalized = false);

    /**
     * @return UriInterface
     */
    public function normalize();

    /**
     * Alias of Uri::toString()
     *
     * @return string
     */
    public function __toString();

    /**
     * @return string|null
     */
    public function getHost();

    /**
     * @return string|null
     */
    public function getPassword();

    /**
     * @return string|null
     */
    public function getPath();

    /**
     * @return int|null
     */
    public function getPort();

    /**
     * @return string|null
     */
    public function getQuery();

    /**
     * @return string|null
     */
    public function getScheme();

    /**
     * @return string|null
     */
    public function getUsername();

    /**
     * @return string|null
     */
    public function getFragment();
}
