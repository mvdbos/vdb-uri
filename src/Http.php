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
class Http extends Uri
{
    public static $allowedSchemes = array('http', 'https');
    public static array $defaultPorts = array('http' => 80, 'https' => 443);

    protected function validateScheme()
    {
        parent::validateScheme();
        $this->normalizeSchemeCase();
        if (null !== $this->scheme && !in_array($this->scheme, static::$allowedSchemes)) {
            throw new UriSyntaxException('Only HTTP scheme allowed');
        }
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

    protected function doSchemeSpecificPostProcessing()
    {
        if (null === $this->path || "" === $this->path) {
            $this->path = '/';
        }
    }
}
