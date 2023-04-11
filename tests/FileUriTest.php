<?php

namespace VDB\Uri\Tests;

use ErrorException;
use PHPUnit\Framework\TestCase;
use VDB\Uri\Exception\UriSyntaxException;
use VDB\Uri\FileUri;
use VDB\Uri\Uri;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 *
 *
 *    foo://example.com:8042/over/there?name=ferret#nose
 *    \_/   \______________/\_________/ \_________/ \__/
 *     |           |            |            |       |
 *  scheme     authority       path        query  fragment
 *     |   _____________________|__
 *    / \ /                        \
 *    urn:example:animal:ferret:nose
 *
 *
 */
class FileUriTest extends TestCase
{
    /**
     * @dataProvider baseProvider
     */
    public function testToBase($base, $full)
    {
        $uri = new FileUri($full);
        $this->assertEquals($base, $uri->toBaseUri()->toString());
    }

    /**
     * @return array
     */
    public function baseProvider(): array
    {
        return array(
            array('file://example.com/foo', 'file://example.com/foo?bar#baz'),
            array('file:///', 'file:///'),
            array('file:///robots.txt', 'file:///robots.txt'),
            array('file:///robots.txt', 'file:/robots.txt'),
            array('file://path/to/robots.txt', 'file://path/to/robots.txt'),
        );
    }

    /**
     */
    public function testRelativeNoBase()
    {
        $this->expectException(UriSyntaxException::class);
        $this->expectExceptionMessage("File URIs must be have a scheme");
        $uri = new FileUri('/no-scheme');
    }

    public function testRelativeRelativeBase()
    {
        $this->expectException("\VDB\URI\Exception\UriSyntaxException");
        new FileUri('b/c/g;x?y#s', '/foo');
    }

    /**
     * @dataProvider relativeReferenceDotSegmentsProvider
     */
    public function testRelativeReferenceDotSegments($relative, $base, $expected)
    {
        $uri = new FileUri($relative, $base);
        $this->assertEquals($expected, $uri->toString());
    }

    /**
     * @return array
     *
     * From RFC 3986 paragraph 5.4
     */
    public function relativeReferenceDotSegmentsProvider(): array
    {
        return array(
            array("./g", 'file:///a/b/c/d;p?q', "file:///a/b/c/g"),
            array(".", 'file:///a/b/c/d;p?q', "file:///a/b/c/"),
            array("./", 'file:///a/b/c/d;p?q', "file:///a/b/c/"),
            array("..", 'file:///a/b/c/d;p?q', "file:///a/b/"),
            array("../", 'file:///a/b/c/d;p?q', "file:///a/b/"),
            array("../g", 'file:///a/b/c/d;p?q', "file:///a/b/g"),
            array("../..", 'file:///a/b/c/d;p?q', "file:///a/"),
            array("../../", 'file:///a/b/c/d;p?q', "file:///a/"),
            array("../../g", 'file:///a/b/c/d;p?q', "file:///a/g"),
            array("../../../g", 'file:///a/b/c/d;p?q', "file:///g"),
            array("../../../g", 'file:///c:/a/b/c/d;p?q', "file:///c:/g"),
            array("../../../../g", 'file:///c:/a/b/c/d;p?q', "file:///c:/g"),
            array("/g", 'file:///c:/a/b/c/d;p?q', "file:///c:/g"),
            array("../g", 'file:///c:/a', "file:///c:/g"),
            array("../../g", 'file:///c:/a', "file:///c:/g"),
        );
    }

    /**
     * @dataProvider relativeReferenceProvider
     */
    public function testRelativeReferenceNormal($relative, $base, $expected)
    {
        $uri = new FileUri($relative, $base);
        $this->assertEquals($expected, $uri->toString());
    }

    /**
     * @return array
     *
     * From RFC 3986 paragraph 5.4
     */
    public function relativeReferenceProvider(): array
    {
        return array(
            array("foo", 'file:///b/c/d;p?q#bar', "file:///b/c/foo"), // base URI fragment should be ignored
            array("foo", 'file:/b/c/d;p?q#bar', "file:///b/c/foo"), // base URI fragment should be ignored
            array("file:///foo", 'file://host/b/c/d;p?q', "file:///foo"), // if rel has scheme, base is effectively ignored
            array("g", 'file://host/b/c/d;p?q', "file://host/b/c/g"),
            array("g/", 'file://host/b/c/d;p?q', "file://host/b/c/g/"),
            array("/g", 'file://host/b/c/d;p?q', "file://host/g"),
            array("/g", 'file://host/b/c/d;p?q', "file://host/g"),
            array("?y", 'file://host/b/c/d;p?q', "file://host/b/c/d;p?y"),
            array("g?y", 'file://host/b/c/d;p?q', "file://host/b/c/g?y"),
            array("#s", 'file://host/b/c/d;p?q', "file://host/b/c/d;p?q#s"),
            array("g#s", 'file://host/b/c/d;p?q', "file://host/b/c/g#s"),
            array("g?y#s", 'file://host/b/c/d;p?q', "file://host/b/c/g?y#s"),
            array(";x", 'file://host/b/c/d;p?q', "file://host/b/c/;x"),
            array("g;x", 'file://host/b/c/d;p?q', "file://host/b/c/g;x"),
            array("g;x?y#s", 'file://host/b/c/d;p?q', "file://host/b/c/g;x?y#s"),
            array("", 'file://host/b/c/d;p?q', "file://host/b/c/d;p?q"),
        );
    }

    /**
     * @return array
     * All taken rom RFC 3986
     */
    public function toStringProvider(): array
    {
        return array(
            array('file://192.0.2.16/robots.txt', 'file://192.0.2.16/robots.txt'),
            array('file://192.0.2.16/path/robots.txt', 'file://192.0.2.16/path/robots.txt'),
            array('file://localhost/robots.txt', 'file://localhost/robots.txt'),
            array('file://localhost/path/robots.txt', 'file://localhost/path/robots.txt'),
            array('file:/robots.txt', 'file:///robots.txt'),
            array('file:/path/robots.txt', 'file:///path/robots.txt'),
            array('file:/robots.txt', 'file:///robots.txt'),
            array('file:/path/robots.txt', 'file:///path/robots.txt'),
            array('file:///path/robots.txt', 'file:///path/robots.txt'),
            array('file:///robots.txt', 'file:///robots.txt'),
            array('file://authority//robots.txt', 'file://authority//robots.txt'),
        );
    }

    /**
     * @param $uriString
     * @param $expected
     *
     * @throws ErrorException
     * @throws UriSyntaxException
     * @dataProvider toStringProvider
     */
    public function testToString($uriString, $expected)
    {
        $uri = new FileUri($uriString);
        $uri->normalize();
        $this->assertEquals($expected, $uri->toString());
    }

    public function testToStringInvalid()
    {
        $this->expectException(UriSyntaxException::class);
        $this->expectExceptionMessage("Can't begin with '//' if no authority was found");
        new FileUri("file:////foo.tx");

    }

    /**
     * @dataProvider equalsNotNormalizedURIProvider
     */
    public function testEqualsNotNormalized($uri1, $uri2)
    {
        $uri = new FileUri($uri1);
        $this->assertTrue($uri->equals(new FileUri($uri2)));
    }

    /**
     * @dataProvider notEqualsNotNormalizedURIProvider
     */
    public function testNotEqualsNotNormalized($uri1, $uri2)
    {
        $uri = new FileUri($uri1);
        $this->assertFalse($uri->equals(new FileUri($uri2)));
    }

    public function equalsNotNormalizedURIProvider()
    {
        return array(

            // dotsegments
            array('file://host/b/c/../d;p?q', 'file://host/b/c/../d;p?q'),
            array('file://host/b/c/./d;p?q', 'file://host/b/c/./d;p?q'),

            // percent encoding
            array("file://foo/%20bar", "file://foo/%20bar"),
            array("file://foo/[bar", "file://foo/[bar"),
            array("file://foo/%5Bbar", "file://foo/%5Bbar"),

            //queries
            array("file://foo/?%20bar", "file://foo/?%20bar"),
            array("file://foo/?%5Bbar", "file://foo/?%5Bbar"),

            //fragments
            array("file://foo/#%20bar", "file://foo/#%20bar"),
            array("file://foo/#%5Bbar", "file://foo/#%5Bbar"),

        );
    }

    /**
     * @return array
     */
    public function notEqualsNotNormalizedURIProvider()
    {
        return array(

            // dotsegments
            array('file://host/b/c/../d;p?q', 'file://host/b/d;p?q'),
            array('file://host/b/c/./d;p?q', 'file://host/b/c/d;p?q'),

            // paths
            array("file://foo/ bar", "file://foo/%20bar"),
            array("file://foo/[bar", "file://foo/%5Bbar"),
            array("file://foo/%5bbar", "file://foo/%5Bbar"),

            //queries
            array("file://foo/?+bar", "file://foo/?%20bar"),
            array("file://foo/? bar", "file://foo/?%20bar"),
            array("file://foo/?[bar", "file://foo/?%5Bbar"),
            array("file://foo/?%5bbar", "file://foo/?%5Bbar"),

            //fragments
            array("file://foo/#+bar", "file://foo/#%20bar"),
            array("file://foo/# bar", "file://foo/#%20bar"),
            array("file://foo/#[bar", "file://foo/#%5Bbar"),
            array("file://foo/#%5bbar", "file://foo/#%5Bbar"),

        );
    }
}
