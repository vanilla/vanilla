<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use VanillaTests\SharedBootstrapTestCase;
use Gdn_Request;

/**
 * Test the {@link Gdn_Request} class.
 */
class RequestTest extends SharedBootstrapTestCase {

    /**
     * Provide some test URLs and how they should expand.
     */
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

    /**
     * The body should be the same as the POST.
     */
    public function testBodyEquivalence() {
        $req = new Gdn_Request();

        $req->setBody(['foo' => 'bar']);
        $this->assertSame($req->getBody(), $req->getRequestArguments(Gdn_Request::INPUT_POST));

        $req->setRequestArguments(Gdn_Request::INPUT_POST, ['foo' => 'bar']);
        $this->assertSame($req->getBody(), $req->getRequestArguments(Gdn_Request::INPUT_POST));
    }

    /**
     * Test that submitted files are properly merged into the POST array.
     */
    public function testFilesAsPost() {
        // Backup the superglobals.
        $post = $_POST;
        $files = $_FILES;

        $_FILES = [
            'MyFile' => [
                'error' => UPLOAD_ERR_OK,
                'name' => 'MyFile.txt',
                'size' => 10,
                'tmp_name' => '/tmp/php/php123',
                'type' => 'text/plain'
            ],
            'Foo' => [
                'error' => UPLOAD_ERR_OK,
                'name' => 'bar.jpg',
                'size' => 1024,
                'tmp_name' => '/tmp/php/php456',
                'type' => 'image/jpeg'
            ]
        ];
        $_POST = ['Foo' => 'Bar'];

        $request = Gdn_Request::create()->fromEnvironment();

        // Put everything back like we found it.
        $_POST = $post;
        $_FILES = $files;

        $this->assertInstanceOf(\Vanilla\UploadedFile::class, $request->post('MyFile'));
        $this->assertNotInstanceOf(\Vanilla\UploadedFile::class, $request->post('Foo'), 'POST value overwritten by file.');
    }

    /**
     * Test `Gdn_Request::getUrl()`.
     */
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

    /**
     * Test request header accessors.
     */
    public function testGetHeaders() {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_HOST' => 'localhost',
            'HTTP_CACHE_CONTROL' => 'no-cache'
        ];
        $expectedHeaders = [
            'Content-Type' => 'application/json',
            'Host' => 'localhost',
            'Cache-Control' => 'no-cache'
        ];

        $request = new Gdn_Request();
        $request->setRequestArguments(Gdn_Request::INPUT_SERVER, $server);

        $this->assertEquals($expectedHeaders, $request->getHeaders());
    }

    /**
     * Test request header accessors.
     */
    public function testGetHeader() {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CACHE_CONTROL' => 'no-cache',
        ];

        $request = new Gdn_Request();
        $request->setRequestArguments(Gdn_Request::INPUT_SERVER, $server);

        $this->assertEquals('application/json', $request->getHeader('CONTENT_TYPE'));
        $this->assertEquals('application/json', $request->getHeader('Content-Type'));
        $this->assertEquals('application/json', $request->getHeader('content-type'));

        $this->assertEquals('no-cache', $request->getHeader('HTTP_CACHE_CONTROL'));
        $this->assertEquals('no-cache', $request->getHeader('CACHE_CONTROL'));
        $this->assertEquals('no-cache', $request->getHeader('Cache-Control'));
        $this->assertEquals('no-cache', $request->getHeader('cache-control'));
    }

    /**
     * Test request header accessors.
     */
    public function testGetHeaderLine() {
        $server = [
            'CONTENT_LENGTH' => '',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => ['application/json', 'application/xml']
        ];

        $request = new Gdn_Request();
        $request->setRequestArguments(Gdn_Request::INPUT_SERVER, $server);

        $this->assertEquals('', $request->getHeaderLine('Content-Length'));
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertEquals('application/json,application/xml', $request->getHeaderLine('Accept'));
    }

    /**
     * Test request header accessors.
     */
    public function testHasHeader() {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_CACHE_CONTROL' => 'no-cache',
        ];

        $request = new Gdn_Request();
        $request->setRequestArguments(Gdn_Request::INPUT_SERVER, $server);

        $this->assertTrue($request->hasHeader('CONTENT_TYPE'));
        $this->assertTrue($request->hasHeader('Content-Type'));
        $this->assertTrue($request->hasHeader('content-type'));

        $this->assertTrue($request->hasHeader('HTTP_CACHE_CONTROL'));
        $this->assertTrue($request->hasHeader('CACHE_CONTROL'));
        $this->assertTrue($request->hasHeader('Cache-Control'));
        $this->assertTrue($request->hasHeader('cache-control'));

        $this->assertFalse($request->hasHeader('Auth'));
    }

    /**
     * Test compatibility between `Gdn_Request::getHost()` and `Gdn_Request::host()`.
     */
    public function testHostEquivalence() {
        $req = new Gdn_Request();

        $req->setHost('localhost');
        $this->assertSame($req->getHost(), $req->host());

        $req->host('localhost');
        $this->assertSame($req->getHost(), $req->host());
    }

    /**
     * Test compatibility between `Gdn_Request::getHostAndPort()` and `Gdn_Request::hostAndPort()`.
     */
    public function testHostAndPortEquivalence() {
        $req = new Gdn_Request();

        $req->setHost('localhost');
        $req->setPort(8080);
        $this->assertSame($req->getHostAndPort(), $req->hostAndPort());

        $req->host('localhost');
        $req->port(8080);
        $this->assertSame($req->getHostAndPort(), $req->hostAndPort());
    }

    /**
     * Test compatibility between `Gdn_Request::getIP()` and `Gdn_Request::ipAddress()`.
     */
    public function testIPEquivalence() {
        $req = new Gdn_Request();

        $req->setIP('127.0.0.1');
        $this->assertSame($req->getIP(), $req->ipAddress());
    }

    /**
     * Test `Gdn_Request::mergeQuery()`.
     */
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
     * Verify files with the UPLOAD_ERR_NO_FILE error are not added to the translated files array.
     */
    public function testNoFileRemoval() {
        $post = $_POST;
        $files = $_FILES;

        $_FILES = [
            'MyFile' => [
                'error' => UPLOAD_ERR_OK,
                'name' => 'MyFile.txt',
                'size' => 10,
                'tmp_name' => '/tmp/php/php123',
                'type' => 'text/plain'
            ],
            'NoFile' => [
                'error' => UPLOAD_ERR_NO_FILE,
                'name' => 'bar.jpg',
                'size' => 1024,
                'tmp_name' => '/tmp/php/php456',
                'type' => 'image/jpeg'
            ]
        ];

        $request = Gdn_Request::create()->fromEnvironment();

        // Put everything back like we found it.
        $_POST = $post;
        $_FILES = $files;

        $this->assertInstanceOf(\Vanilla\UploadedFile::class, $request->post('MyFile'));
        $this->assertFalse($request->post('NoFile', false), 'Nonexistent file was not removed.');
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

    /**
     * Test compatibility between `Gdn_Request::getPort()` and `Gdn_Request::port()`.
     */
    public function testPortEquivalence() {
        $req = new Gdn_Request();

        $req->setPort(8080);
        $this->assertSame($req->getPort(), $req->port());

        $req->port(8080);
        $this->assertSame($req->getPort(), $req->port());
    }

    /**
     * Test that the files array is normalized when merged into the POST array.
     */
    public function testPostFileNormalization() {
        // Backup the superglobals.
        $files = $_FILES;

        // This format represents what might come in from a form using input fields named "MyForm[Details][Avatars][]"
        $_FILES = [
            'MyForm' => [
                'tmp_name' => [
                    'Details' => [
                        'Avatar' => ['/tmp/php/abc123', '/tmp/php/xyz890']
                    ]
                ],
                'name' => [
                    'Details' => [
                        'Avatar' => ['AvatarOne', 'AvatarTwo']
                    ]
                ],
                'size' => [
                    'Details' => [
                        'Avatar' => [100, 110]
                    ]
                ],
                'type' => [
                    'Details' => [
                        'Avatar' => ['image/jpeg', 'image/jpeg']
                    ]
                ],
                'error' => [
                    'Details' => [
                        'Avatar' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK]
                    ]
                ]
            ]
        ];

        $request = Gdn_Request::create()->fromEnvironment();

        // Put everything back like we found it.
        $_FILES = $files;

        $formFiles = $request->post('MyForm');
        $this->assertInstanceOf(\Vanilla\UploadedFile::class, $formFiles['Details']['Avatar'][0]);
        $this->assertInstanceOf(\Vanilla\UploadedFile::class, $formFiles['Details']['Avatar'][1]);
    }

    /**
     * Test `Gdn_Request::getQuery()`.
     */
    public function testQueryEquivalence() {
        $req = new Gdn_Request();

        $req->setQuery(['foo' => 'bar']);
        $this->assertSame($req->getQuery(), $req->getRequestArguments(Gdn_Request::INPUT_GET));

        $req->setRequestArguments(Gdn_Request::INPUT_GET, ['foo' => 'bar']);
        $this->assertSame($req->getQuery(), $req->getRequestArguments(Gdn_Request::INPUT_GET));
    }

    /**
     * Test `Gdn_Request::getQueryItem()`.
     */
    public function testQueryItemEquivalence() {
        $req = new Gdn_Request();

        $req->setQuery(['foo' => 'bar']);
        $this->assertSame($req->getQueryItem('foo'), $req->getValueFrom(Gdn_Request::INPUT_GET, 'foo'));

        $req->setRequestArguments(Gdn_Request::INPUT_GET, ['foo' => 'bar']);
        $this->assertSame($req->getQueryItem('foo'), $req->getValueFrom(Gdn_Request::INPUT_GET, 'foo'));
    }

    /**
     * Test `Gdn_Request::setFullPath()`.
     */
    public function testSetFullPath() {
        $request = new Gdn_Request();
        $request->setRoot('root-dir');
        $request->setFullPath('/root-dir/path/to/resource.json');

        //$this->assertSame('/root-dir', $request->getRoot());
        $this->assertSame('/path/to/resource', $request->getPath());
        $this->assertSame('.json', $request->getExt());
    }

    /**
     * Test `Gdn_Request::setPathExt()`.
     */
    public function testSetPathExt() {
        $request = new Gdn_Request();
        $request->setPathExt('path/to/resource.json');

        $this->assertSame('/path/to/resource', $request->getPath());
        $this->assertSame('.json', $request->getExt());
    }

    /**
     * Test `Gdn_Request::setQueryItem()`.
     */
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
     * Test `Gdn_Request::setUrl()`.
     *
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

    /**
     * Test compatibility of `Gdn_Request::getRoot()` and `Gdn_Request::webRoot()`.
     */
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

    /**
     * Test compatibility between `Gdn_Request::getScheme()` and `Gdn_Request::scheme()`.
     */
    public function testSchemeEquivalence() {
        $req = new Gdn_Request();

        $req->setScheme('https');
        $this->assertSame($req->getScheme(), $req->scheme());

        $req->scheme('http');
        $this->assertSame($req->getScheme(), $req->scheme());
    }

    /**
     * Test compatibility between `Gdn_Request::getUrl()` and `Gdn_Request::url('', true)`.
     */
    public function testUrlEquivalence() {
        // Simulate that rewrite is ON
        $_SERVER['X_REWRITE'] = 1;

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

    /**
     * Test basic attribute accessors.
     */
    public function testAttributeAccessors(): void {
        $r = new Gdn_Request();

        $this->assertNull($r->getAttribute('foo'));
        $r->setAttribute('foo', 'bar');
        $this->assertSame('bar', $r->getAttribute('foo'));

        $this->assertArrayHasKey('foo', $r->getAttributes());
    }
}
