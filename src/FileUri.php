<?php

namespace VDB\Uri;

use VDB\Uri\Exception\UriSyntaxException;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 *
 * Based on RFC 3986,
 * Amended with HTTP URI scheme from RFC 2616 paragraph 3.2
 *
 * Note: different from RFC 3986, empty path should become '/';
 *
 */
class FileUri extends Uri
{
    public static array $allowedSchemes = array('file');

    protected function validateScheme()
    {
        parent::validateScheme();
        $this->normalizeSchemeCase();
        if (null !== $this->scheme && !in_array($this->scheme, static::$allowedSchemes)) {
            throw new UriSyntaxException('Only file scheme allowed');
        }
    }

    /**
     * Adapted from Uri::resolveRelativeReference to correctly handle Windows/DOS drive letters.
     * @throws UriSyntaxException
     */
    protected function resolveRelativeReference()
    {
        if (null !== $this->scheme) {
            $this->normalizeDotSegments();
        } else {
            $this->scheme = $this->baseUri->scheme;
            if (null !== $this->authority) {
                $this->normalizeDotSegments();
            } else {
                if (null !== $this->baseUri->authority) {
                    $this->authority = $this->baseUri->authority;
                    $this->parseUserInfoHostPort();
                }
                if ('' === $this->path) {
                    $this->path = $this->baseUri->path;
                    if (null === $this->query) {
                        $this->query = $this->baseUri->query;
                    }
                } else {
                    if (0 !== strpos($this->path, '/')) {
                        $this->mergeBasePath();
                        $this->normalizeDotSegments();
                    } else {
                        // If relative reference is absolute (starts with /), and there is a drive letter,
                        // prepend the drive letter to preserve it.
                        if (($driveLetter = $this->getDriveLetter($this->baseUri)) !== null) {
                            $this->path = $driveLetter . ':' . $this->path;
                        }
                        $this->normalizeDotSegments();
                    }
                }
            }
        }
    }

    /**
     * Adapted from Uri::normalizeDotSegments to support Windows/DOS drive letters in the path.
     */
    protected function normalizeDotSegments()
    {
        if ($this->path == null) return;

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
                    if ('' !== end($output) && !$this->isDriveLetter(end($output))) {
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
     * @throws UriSyntaxException
     */
    protected function doSchemeSpecificPostProcessing()
    {
        if (!$this->hasScheme()) {
            throw new UriSyntaxException("File URIs must be have a scheme");
        }

        if ($this->getPath() == null || $this->getPath() == '') {
            throw new UriSyntaxException(
                "File URI '" . $this->toString() . "' must have a path. " .
                "Is your File URI local and did you use '//' to start the path? That is illegal. " .
                "Please use '///' or '/' for local File URIs.");
        }

        /*
         * Even though RFC 8090 allows both '/' and '///' for file URIs without a host,
         * PHP's file_get_contents can only handle file URIs with '///'.
         */
        if ($this->startsWithSingleSlash($this->path) && (null === $this->host || strlen($this->host) == 0 )) {
            $this->path = '//' . $this->path;
        }
    }

    public function toBaseUri()
    {
        $base = clone $this;
        $base->query = null;
        $base->fragment = null;
        return $base->normalize();
    }

    private function startsWithSingleSlash(?string $path): bool
    {
        if ($path !== null && strlen($path) > 0 && $path[0] === '/') {
            if (strlen($path) > 1) {
                return $path[1] !== '/';
            }
            return true;
        }
        return false;
    }

    private function isDriveLetter($end): bool
    {
        return preg_match('/[a-zA-Z]{1}:/', $end) === 1;
    }

    private function getDriveLetter(Uri $baseUri): ?string
    {
        $matches = array();
        if (preg_match('/(.*[a-zA-Z]{1}):.*/', $baseUri->getPath(), $matches, PREG_UNMATCHED_AS_NULL) === 1) {
            return $matches[1];
        }
        return null;
    }
}
