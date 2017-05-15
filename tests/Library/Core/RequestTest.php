<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Core;

use Gdn_Request;

/**
 * Test the {@link Gdn_Request} class.
 */
class RequestTest extends \PHPUnit\Framework\TestCase {

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

    public function testBodyEquivalence() {
        $req = new Gdn_Request();

        $req->setBody(['foo' => 'bar']);
        $this->assertSame($req->getBody(), $req->getRequestArguments(Gdn_Request::INPUT_POST));

        $req->setRequestArguments(Gdn_Request::INPUT_POST, ['foo' => 'bar']);
        $this->assertSame($req->getBody(), $req->getRequestArguments(Gdn_Request::INPUT_POST));
    }

    public function testGetUrl() {
        $request = new Gdn_Request();
        $request->setScheme('http');
        $request->setHost('localhost');
        $request->setPort(8080);
        $request->setRoot('/root-dir');
        $request->setPath('/path/to/resource');
        $request->setExt('.json');
        $request->setQuery(['foo' => 'bar']);

        $this->assertSame('http://localhost:8080/root-dir/path/to/resource.json?foo=bar', $request->getUrl());
    }

    public function testHostEquivalence() {
        $req = new Gdn_Request();

        $req->setHost('localhost');
        $this->assertSame($req->getHost(), $req->host());

        $req->host('localhost');
        $this->assertSame($req->getHost(), $req->host());
    }

    public function testHostAndPortEquivalence() {
        $req = new Gdn_Request();

        $req->setHost('localhost');
        $req->setPort(8080);
        $this->assertSame($req->getHostAndPort(), $req->hostAndPort());

        $req->host('localhost');
        $req->port(8080);
        $this->assertSame($req->getHostAndPort(), $req->hostAndPort());
    }

    public function testIPEquivalence() {
        $req = new Gdn_Request();

        $req->setIP('127.0.0.1');
        $this->assertSame($req->getIP(), $req->ipAddress());
    }

    public function testMergeQuery() {
        $request = new Gdn_Request();
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

    /**
     * The {@link Gdn_Request::path()} and {@link Gdn_Request::getPath()} methods should be compatible.
     */
    public function testPathEquivalence() {
        $req = new Gdn_Request();

        $req->setPath('/foo');
        $this->assertSame($req->getPath(), '/'.$req->path());

        $req->path('/bar');
        $this->assertSame($req->getPath(), '/'.$req->path());
    }

    /**
     * Request paths should start with a slash and fix ones that don't.
     */
    public function testPathFixing() {
        $req = new Gdn_Request();

        $req->setPath('foo');
        $this->assertSame('/foo', $req->getPath());
    }

    public function testPortEquivalence() {
        $req = new Gdn_Request();

        $req->setPort(8080);
        $this->assertSame($req->getPort(), $req->port());

        $req->port(8080);
        $this->assertSame($req->getPort(), $req->port());
    }

    public function testQueryEquivalence() {
        $req = new Gdn_Request();

        $req->setQuery(['foo' => 'bar']);
        $this->assertSame($req->getQuery(), $req->getRequestArguments(Gdn_Request::INPUT_GET));

        $req->setRequestArguments(Gdn_Request::INPUT_GET, ['foo' => 'bar']);
        $this->assertSame($req->getQuery(), $req->getRequestArguments(Gdn_Request::INPUT_GET));

    }

    public function testQueryItemEquivalence() {
        $req = new Gdn_Request();

        $req->setQuery(['foo' => 'bar']);
        $this->assertSame($req->getQueryItem('foo'), $req->getValueFrom(Gdn_Request::INPUT_GET, 'foo'));

        $req->setRequestArguments(Gdn_Request::INPUT_GET, ['foo' => 'bar']);
        $this->assertSame($req->getQueryItem('foo'), $req->getValueFrom(Gdn_Request::INPUT_GET, 'foo'));
    }

    public function testSetFullPath() {
        $request = new Gdn_Request();
        $request->setRoot('root-dir');
        $request->setFullPath('/root-dir/path/to/resource.json');

        //$this->assertSame('/root-dir', $request->getRoot());
        $this->assertSame('/path/to/resource', $request->getPath());
        $this->assertSame('.json', $request->getExt());
    }

    public function testSetPathExt() {
        $request = new Gdn_Request();
        $request->setPathExt('path/to/resource.json');

        $this->assertSame('/path/to/resource', $request->getPath());
        $this->assertSame('.json', $request->getExt());
    }

    public function testSetQueryItem() {
        $request = new Gdn_Request();
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
        $request = new Gdn_Request();
        $request->setUrl($url);

        $this->assertSame($expected['scheme'], $request->getScheme());
        $this->assertSame($expected['host'], $request->getHost());
        $this->assertSame($expected['port'], $request->getPort());
        $this->assertSame($expected['path'], $request->getPath());
        $this->assertSame($expected['extension'], $request->getExt());
        $this->assertSame($expected['query'], $request->getQuery());
    }

    public function testRootEquivalence() {
        $req = new Gdn_Request();

        $req->setRoot('root-dir');
        $this->assertSame($req->getRoot(), '/'.$req->webRoot());

        $req->webRoot('root-dir');
        $this->assertSame($req->getRoot(), '/'.$req->webRoot());
    }

    /**
     * Request root should start with a slash and fix ones that don't. Slash-only roots should be empty strings.
     */
    public function testRootFixing() {
        $req = new Gdn_Request();

        $req->setRoot('root-dir');
        $this->assertSame('/root-dir', $req->getRoot());

        $req->setRoot('/');
        $this->assertSame('', $req->getRoot());
    }

    public function testSchemeEquivalence() {
        $req = new Gdn_Request();

        $req->setScheme('https');
        $this->assertSame($req->getScheme(), $req->scheme());

        $req->scheme('http');
        $this->assertSame($req->getScheme(), $req->scheme());
    }

    public function testUrlEquivalence() {
        $req = new Gdn_Request();

        $req->setScheme('http');
        $req->setHost('localhost');
        $req->setPort(8080);
        $req->setRoot('root-dir');
        $req->setPath('path/to/resource.json');
        $req->setQueryItem('foo', 'bar');

        $this->assertSame($req->getUrl(), $req->url('', true));

        $req->scheme('http');
        $req->host('localhost');
        $req->port(8080);
        $req->webRoot('root-dir');
        $req->path('path/to/resource.json');
        $req->setValueOn(Gdn_Request::INPUT_GET, 'foo', 'bar');

        $this->assertSame($req->getUrl(), $req->url('', true));
    }
}
