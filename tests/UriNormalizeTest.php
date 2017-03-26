<?php
namespace VDB\Uri\Tests;

use VDB\Uri\Uri;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 *
 *
 *    foo://example.com:8042/over/there?name=ferret#nose
 *    \_/   \______________/\_________/ \_________/ \__/
 *    |           |            |            |        |
 * scheme     authority       path        query   fragment
 *    |   _____________________|__
 *   / \ /                        \
 *   urn:example:animal:ferret:nose
 *
 *
 */
class UriNormalizeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider normalizePercentEncodingProvider
     */
    public function testNormalizePercentEncoding($uri, $expected)
    {
        $uri = new Uri($uri);
        $uri->normalize();

        $this->assertEquals($expected, $uri->toString());
    }

    /**
     * @return array
     */
    public function normalizePercentEncodingProvider()
    {
        return array(
            //paths
            array("http://foo/%20bar", "http://foo/%20bar"),
            array("http://foo/+bar", "http://foo/%20bar"),
            array("http://foo/ bar", "http://foo/%20bar"),
            array("http://foo/[bar", "http://foo/%5Bbar"),
            array("http://foo/%5bbar", "http://foo/%5Bbar"),

            //queries
            array("http://foo/?%20bar", "http://foo/?%20bar"),
            array("http://foo/?+bar", "http://foo/?%20bar"),
            array("http://foo/? bar", "http://foo/?%20bar"),
            array("http://foo/?[bar", "http://foo/?%5Bbar"),
            array("http://foo/?%5bbar", "http://foo/?%5Bbar"),

            //fragments
            array("http://foo/#%20bar", "http://foo/#%20bar"),
            array("http://foo/#+bar", "http://foo/#%20bar"),
            array("http://foo/# bar", "http://foo/#%20bar"),
            array("http://foo/#[bar", "http://foo/#%5Bbar"),
            array("http://foo/#%5bbar", "http://foo/#%5Bbar"),
        );
    }

    /**
     * @dataProvider normalizeCaseProvider
     */
    public function testNormalizeCase($uri, $expected)
    {
        $uri = new Uri($uri);
        $uri->normalize();

        $this->assertEquals($expected, $uri->toString());
    }

    /**
     * @return array
     */
    public function normalizeCaseProvider()
    {
        return array(
            array("HTTP://foo/bar", "http://foo/bar"),
            array("http://FOO/bar", "http://foo/bar"),
            array("http://foo/bar?BAZ", "http://foo/bar?BAZ"),
            array("http://foo/bar#BAZ", "http://foo/bar#BAZ"),
            array("http://USER@foo/bar", "http://USER@foo/bar"),
        );
    }

    /**
     * @dataProvider relativeReferenceDotSegmentsProvider
     */
    public function testRelativeReferenceDotSegments($relative, $base, $expected)
    {
        $uri = new Uri($relative, $base);

        $this->assertEquals($expected, $uri->toString());
    }

    /**
     * @return array
     *
     * From RFC 3986 paragraph 5.4
     */
    public function relativeReferenceDotSegmentsProvider()
    {
        return array(
            array("./g", 'http://a/b/c/d;p?q', "http://a/b/c/g"),
            array(".", 'http://a/b/c/d;p?q', "http://a/b/c/"),
            array("./", 'http://a/b/c/d;p?q', "http://a/b/c/"),
            array("..", 'http://a/b/c/d;p?q', "http://a/b/"),
            array("../", 'http://a/b/c/d;p?q', "http://a/b/"),
            array("../g", 'http://a/b/c/d;p?q', "http://a/b/g"),
            array("../..", 'http://a/b/c/d;p?q', "http://a/"),
            array("../../", 'http://a/b/c/d;p?q', "http://a/"),
            array("../../g", 'http://a/b/c/d;p?q', "http://a/g"),
        );
    }

    /**
     * @dataProvider absoluteUriDotSegmentsProvider
     */
    public function testAbsoluteUriDotSegments($uriWithDots, $normalizedUri)
    {
        $uri = new Uri($uriWithDots);
        $uri->normalize();

        $this->assertEquals($normalizedUri, $uri->toString());
    }

    /**
     * @return array
     *
     * From RFC 3986 paragraph 5.4
     */
    public function absoluteUriDotSegmentsProvider()
    {
        return array(
            array('http://a/b/c/./g', "http://a/b/c/g"),
            array('http://a/b/c/.', "http://a/b/c/"),
            array('http://a/b/c/./', "http://a/b/c/"),
            array('http://a/b/c/..', "http://a/b/"),
            array('http://a/b/c/../', "http://a/b/"),
            array('http://a/b/c/../g', "http://a/b/g"),
            array('http://a/b/c/../..', "http://a/"),
            array('http://a/b/c/../../', "http://a/"),
            array('http://a/b/c/../../g', "http://a/g"),
        );
    }

    /**
     * @dataProvider abnormalRelativeReferenceDotSegmentsProvider
     */
    public function testAbnormalRelativeReferenceDotSegments($relative, $base, $expected)
    {
        $uri = new Uri($relative, $base);

        $this->assertEquals($expected, $uri->toString());
    }

    /**
     * @return array
     *
     * From RFC 3986 paragraph 5.4.2
     */
    public function abnormalRelativeReferenceDotSegmentsProvider()
    {
        return array(
            array("../../../g", 'http://a/b/c/d;p?q', "http://a/g"),
            array("../../../../g", 'http://a/b/c/d;p?q', "http://a/g"),
            array("/../../../../g", 'http://a/b/c/d;p?q', "http://a/g"),
            array("/./g", 'http://a/b/c/d;p?q', "http://a/g"),
            array("/../g", 'http://a/b/c/d;p?q', "http://a/g"),
            array("g.", 'http://a/b/c/d;p?q', "http://a/b/c/g."),
            array(".g", 'http://a/b/c/d;p?q', "http://a/b/c/.g"),
            array("g..", 'http://a/b/c/d;p?q', "http://a/b/c/g.."),
            array("..g", 'http://a/b/c/d;p?q', "http://a/b/c/..g"),
            array("./../g", 'http://a/b/c/d;p?q', "http://a/b/g"),
            array("./g/.", 'http://a/b/c/d;p?q', "http://a/b/c/g/"),
            array("g/./h", 'http://a/b/c/d;p?q', "http://a/b/c/g/h"),
            array("g/../h", 'http://a/b/c/d;p?q', "http://a/b/c/h"),
            array("g;x=1/./y", 'http://a/b/c/d;p?q', "http://a/b/c/g;x=1/y"),
            array("g;x=1/../y", 'http://a/b/c/d;p?q', "http://a/b/c/y"),
            array("g?y/./x", 'http://a/b/c/d;p?q', "http://a/b/c/g?y/./x"),
            array("g?y/../x", 'http://a/b/c/d;p?q', "http://a/b/c/g?y/../x"),
            array("g#s/./x", 'http://a/b/c/d;p?q', "http://a/b/c/g#s/./x"),
            array("g#s/../x", 'http://a/b/c/d;p?q', "http://a/b/c/g#s/../x"),
        );
    }

    /**
     * @dataProvider equalsNormalizedURIProvider
     */
    public function testEqualsNormalized($uri1, $uri2)
    {
        $uri1 = new Uri($uri1);
        $uri2 = new Uri($uri2);

        $uri1->normalize();
        $uri2->normalize();

        $this->assertTrue($uri1->equals($uri2));
    }


    /**
     * @return array
     */
    public function equalsNormalizedURIProvider()
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
