<?php

namespace VDB\Uri\Tests;

use VDB\Uri\Http;

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
class HttpsTest extends \PHPUnit_Framework_TestCase
{
    /**
     */
    public function testRelativeNoBase()
    {
        $uri = new Http('b/../c/g;x?y#s');

        $this->assertNull($uri->getScheme());
        $this->assertNull($uri->getHost());
        $this->assertEquals('b/../c/g;x', $uri->getPath());
        $this->assertEquals('y', $uri->getQuery());
        $this->assertEquals('s', $uri->getFragment());

        $uri->normalize();

        $this->assertEquals('c/g;x', $uri->getPath());
    }

    public function testRelativeRelativeBase()
    {
        $this->expectException("\VDB\URI\Exception\UriSyntaxException");
        new Http('b/c/g;x?y#s', '/foo');
    }


    /**
     * @dataProvider relativeReferenceProvider
     */
    public function testRelativeReferenceNormal($relative, $base, $expected)
    {
        $uri = new Http($relative, $base);

        $this->assertEquals($expected, $uri->toString());
    }

    /**
     * @return array
     *
     * From RFC 3986 paragraph 5.4
     */
    public function relativeReferenceProvider()
    {
        return array(
            array("foo", 'http://a/b/c/d;p?q#bar', "http://a/b/c/foo"), // base URI fragment should be ignored
            array("http://foo", 'http://a/b/c/d;p?q', "http://foo/"), // if rel has scheme, base is effectively ignored
            array("g", 'http://a/b/c/d;p?q', "http://a/b/c/g"),
            array("g/", 'http://a/b/c/d;p?q', "http://a/b/c/g/"),
            array("/g", 'http://a/b/c/d;p?q', "http://a/g"),
            array("//g", 'http://a/b/c/d;p?q', "http://g/"),
            array("?y", 'http://a/b/c/d;p?q', "http://a/b/c/d;p?y"),
            array("g?y", 'http://a/b/c/d;p?q', "http://a/b/c/g?y"),
            array("#s", 'http://a/b/c/d;p?q', "http://a/b/c/d;p?q#s"),
            array("g#s", 'http://a/b/c/d;p?q', "http://a/b/c/g#s"),
            array("g?y#s", 'http://a/b/c/d;p?q', "http://a/b/c/g?y#s"),
            array(";x", 'http://a/b/c/d;p?q', "http://a/b/c/;x"),
            array("g;x", 'http://a/b/c/d;p?q', "http://a/b/c/g;x"),
            array("g;x?y#s", 'http://a/b/c/d;p?q', "http://a/b/c/g;x?y#s"),
            array("", 'http://a/b/c/d;p?q', "http://a/b/c/d;p?q"),
        );
    }

    /**
     * @param $uri
     * @dataProvider hostURIProvider
     * @throws \VDB\Uri\Exception\UriSyntaxException
     */
    public function testHost($uriString, $expected)
    {
        $uri = new Http($uriString);

        $this->assertEquals($expected, $uri->getHost());
    }

    /**
     * @return array
     * All taken rom RFC 3986
     */
    public function hostURIProvider()
    {
        return array(
            array('http://192.0.2.16:80/', '192.0.2.16'),
            array('https://example.com:8042/over/there?name=ferret#nose', 'example.com'),
        );
    }

    /**
     * @return array
     * All taken rom RFC 3986
     */
    public function toStringProvider()
    {
        return array(
            array('http://192.0.2.16:80/', 'http://192.0.2.16/'),
            array('https://example.com:443/over/there?name=ferret#nose', 'https://example.com/over/there?name=ferret#nose'),
            array('https://example.com:8042/over/there?name=ferret#nose', 'https://example.com:8042/over/there?name=ferret#nose'),
        );
    }

    /**
     * @param $uri
     * @dataProvider toStringProvider
     * @throws \VDB\Uri\Exception\UriSyntaxException
     */
    public function testToString($uriString, $expected)
    {
        $uri = new Http($uriString);
        $uri->normalize();
        $this->assertEquals($expected, $uri->toString());
    }

    /**
     * @dataProvider noIpSixURIProvider
     */
    public function testNoIpSixSupport($uriString)
    {
        $this->expectException("\VDB\URI\Exception\UriSyntaxException");
        new Http($uriString);
    }

    /**
     * @return array
     * All taken rom RFC 3986
     */
    public function noIpSixURIProvider()
    {
        return array(
            array('ldap://user:pass@[2001:db8::7]/c=GB?objectClass?one'),
            array('ldap://[2001:db8::7]/c=GB?objectClass?one'),
        );
    }

    /**
     * @dataProvider equalsNotNormalizedURIProvider
     */
    public function testEqualsNotNormalized($uri1, $uri2)
    {
        $uri = new Http($uri1);
        $this->assertTrue($uri->equals(new Http($uri2)));
    }

    /**
     * @dataProvider notEqualsNotNormalizedURIProvider
     */
    public function testNotEqualsNotNormalized($uri1, $uri2)
    {
        $uri = new Http($uri1);
        $this->assertFalse($uri->equals(new Http($uri2)));
    }

    public function equalsNotNormalizedURIProvider()
    {
        return array(

            // dotsegments
            array('http://a/b/c/../d;p?q', 'http://a/b/c/../d;p?q'),
            array('http://a/b/c/./d;p?q', 'http://a/b/c/./d;p?q'),

            // percent encoding
            array("http://foo/%20bar", "http://foo/%20bar"),
            array("http://foo/[bar", "http://foo/[bar"),
            array("http://foo/%5Bbar", "http://foo/%5Bbar"),

            //queries
            array("http://foo/?%20bar", "http://foo/?%20bar"),
            array("http://foo/?%5Bbar", "http://foo/?%5Bbar"),

            //fragments
            array("http://foo/#%20bar", "http://foo/#%20bar"),
            array("http://foo/#%5Bbar", "http://foo/#%5Bbar"),

        );
    }

    /**
     * @return array
     */
    public function notEqualsNotNormalizedURIProvider()
    {
        return array(

            // dotsegments
            array('http://a/b/c/../d;p?q', 'http://a/b/d;p?q'),
            array('http://a/b/c/./d;p?q', 'http://a/b/c/d;p?q'),

            // paths
            array("http://foo/ bar", "http://foo/%20bar"),
            array("http://foo/[bar", "http://foo/%5Bbar"),
            array("http://foo/%5bbar", "http://foo/%5Bbar"),

            //queries
            array("http://foo/?+bar", "http://foo/?%20bar"),
            array("http://foo/? bar", "http://foo/?%20bar"),
            array("http://foo/?[bar", "http://foo/?%5Bbar"),
            array("http://foo/?%5bbar", "http://foo/?%5Bbar"),

            //fragments
            array("http://foo/#+bar", "http://foo/#%20bar"),
            array("http://foo/# bar", "http://foo/#%20bar"),
            array("http://foo/#[bar", "http://foo/#%5Bbar"),
            array("http://foo/#%5bbar", "http://foo/#%5Bbar"),

        );
    }
}
