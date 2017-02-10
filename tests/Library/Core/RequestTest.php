<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Core;

class VanillaClassLocatorTest extends \PHPUnit_Framework_TestCase {

    public function provideUrls() {
        return [
            [
                'http://localhost:8080/path/to/resource.json?foo=bar',
                [
                    'scheme' => 'http',
                    'host' => 'localhost',
                    'port' => 8080,
                    'path' => '/path/to/resource',
                    'extension' => '.json',
                    'query' => ['foo' => 'bar']
                ]
            ],
            [
                'https://vanillaforums.com/en',
                [
                    'scheme' => 'https',
                    'host' => 'vanillaforums.com',
                    'port' => 443,
                    'path' => '/en',
                    'extension' => '',
                    'query' => []
                ]
            ],
            [
                'http://open.vanillaforums.com',
                [
                    'scheme' => 'http',
                    'host' => 'open.vanillaforums.com',
                    'port' => 80,
                    'path' => '/',
                    'extension' => '',
                    'query' => []
                ]
            ]
        ];
    }

    public function testGetUrl() {
        $request = new \Gdn_Request();
        $request->setScheme('http');
        $request->setHost('localhost');
        $request->setPort(8080);
        $request->setRoot('/root-dir');
        $request->setPath('/path/to/resource');
        $request->setExt('.json');
        $request->setQuery(['foo' => 'bar']);

        $this->assertSame('http://localhost:8080/root-dir/path/to/resource.json?foo=bar', $request->getUrl());
    }

    public function testMergeQuery() {
        $request = new \Gdn_Request();
        $request->setQuery([
            'One' => 'Alpha',
            'Two' => 'Bravo'
        ]);
        $request->mergeQuery([
            'Two' => 'Beta',
            'Three' => 'Charlie',
            'Four' => 'Delta'
        ]);

        $this->assertSame([
            'One' => 'Alpha',
            'Two' => 'Beta',
            'Three' => 'Charlie',
            'Four' => 'Delta'
        ], $request->getQuery());
    }

    public function testSetFullPath() {
        $request = new \Gdn_Request();
        $request->setRoot('root-dir');
        $request->setFullPath('/root-dir/path/to/resource.json');

        //$this->assertSame('/root-dir', $request->getRoot());
        $this->assertSame('/path/to/resource', $request->getPath());
        $this->assertSame('.json', $request->getExt());
    }

    public function testSetPathExt() {
        $request = new \Gdn_Request();
        $request->setPathExt('path/to/resource.json');

        $this->assertSame('/path/to/resource', $request->getPath());
        $this->assertSame('.json', $request->getExt());
    }

    public function testSetQueryItem() {
        $request = new \Gdn_Request();
        $request->setQuery([
            'One' => 'Alpha',
            'Two' => 'Bravo',
            'Three' => 'Charlie'
        ]);
        $request->setQueryItem('One', 'Delta');

        $this->assertSame('Delta', $request->getQueryItem('One'));
    }

    /**
     * @param string $url
     * @param array $expected
     * @dataProvider provideUrls
     */
    public function testSetUrl($url, $expected) {
        $request = new \Gdn_Request();
        $request->setUrl($url);

        $this->assertSame($expected['scheme'], $request->getScheme());
        $this->assertSame($expected['host'], $request->getHost());
        $this->assertSame($expected['port'], $request->getPort());
        $this->assertSame($expected['path'], $request->getPath());
        $this->assertSame($expected['extension'], $request->getExt());
        $this->assertSame($expected['query'], $request->getQuery());
    }
}
