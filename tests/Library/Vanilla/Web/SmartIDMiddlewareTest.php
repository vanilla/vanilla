<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\RequestInterface;
use PHPUnit\Framework\TestCase;
use Vanilla\Exception\PermissionException;
use Vanilla\Web\SmartIDMiddleware;
use Vanilla\Web\UserSmartIDResolver;
use VanillaTests\BootstrapTrait;
use VanillaTests\Fixtures\Request;

/**
 * Test the **SmartIDMiddleware** class.
 */
class SmartIDMiddlewareTest extends TestCase {
    use BootstrapTrait;

    /**
     * @var SmartIDMiddleware
     */
    protected $middleware;

    /**
     * @var UserSmartIDResolver
     */
    protected $userResolver;

    /**
     * @var \Gdn_Session
     */
    protected $session;

    /**
     * Create a configured test middleware for each test.
     */
    public function setUp(): void {
        $this->middleware =  new TestSmartIDMiddleware();
        $this->middleware->addSmartID('CategoryID', 'categories', ['name', 'urlcode'], 'Category');

        $this->session = new \Gdn_Session();
        $this->session->UserID = 123;

        $usr = $this->userResolver = new UserSmartIDResolver($this->session);
        $usr->setEmailEnabled(true)
            ->setViewEmail(true);
        $this->middleware->addSmartID('UserID', 'users', '*', $usr);
    }

    /**
     * Call the test middleware and return the augmented request.
     *
     * @param RequestInterface $request The request being called.
     * @return RequestInterface Returns the augmented request.
     */
    protected function callMiddleware(RequestInterface $request): RequestInterface {
        /* @var Data $data */
        $data = call_user_func($this->middleware, $request, function ($request) {
            return new Data([], ['request' => $request]);
        });

        return $data->getMeta('request');
    }

    /**
     * Some basic tests for smart IDs in paths.
     *
     * @param string $path The path to replace.
     * @param string $expected The expected path replacement.
     * @dataProvider providePathTests
     */
    public function testReplacePath(string $path, string $expected) {
        $request = new Request($path);
        $r = $this->callMiddleware($request);
        $this->assertEquals($expected, $r->getPath());
    }

    /**
     * Provide some basic path tests.
     *
     * @return array Returns a data provider.
     */
    public function providePathTests(): array {
        $r = [
            ['/categories/$name:foo', '/categories/(Category.CategoryID.name:foo)'],
            ['/categories/$urlCode:foo', '/categories/(Category.CategoryID.urlcode:foo)'],
            ['/users/$name:baz', '/users/(User.UserID.name:baz)'],
            ['/users/$foozbook:123', '/users/(UserAuthentication.UserID.providerKey:foozbook.foreignUserKey:123)'],
            ['/users/$query:userID?userID=$name:baz', '/users/(User.UserID.name:baz)'],
            ['/users/$me', '/users/123'],
        ];

        return array_column($r, null, 0);
    }

    /**
     * Test smart ID lookup in the querystring.
     *
     * @param array $query The request querystring.
     * @param array $expected The expected processed querystring.
     * @dataProvider provideQueryTests
     */
    public function testReplaceQuery(array $query, array $expected) {
        $request = new Request('/', 'GET', $query);
        $r = $this->callMiddleware($request);
        $this->assertEquals($expected, $r->getQuery());
    }

    /**
     * Provide some basic querystring replacement tests.
     *
     * @return array Returns a data provider.
     */
    public function provideQueryTests(): array {
        $r = [
            'basic' => [['categoryID' => '$name:foo'], ['categoryID' => '(Category.CategoryID.name:foo)']],
            'suffix' => [['parentCategoryID' => '$urlcode:foo'], ['parentCategoryID' => '(Category.CategoryID.urlcode:foo)']],
            'callback' => [['insertUserID' => '$name:baz'], ['insertUserID' => '(User.UserID.name:baz)']],
            'fully qualified' => [['parentID' => '$userID.name:baz'], ['parentID' => '(User.UserID.name:baz)']],
        ];

        return $r;
    }

    /**
     * Test smart ID lookup in the request body.
     *
     * @param array $body The request body.
     * @param array $expected The expected processed body.
     * @dataProvider provideBodyTests
     */
    public function testReplaceBody(array $body, array $expected) {
        $request = new Request('/', 'POST', $body);
        $r = $this->callMiddleware($request);
        $this->assertEquals($expected, $r->getBody());
    }

    /**
     * Provide test request bodies with smart IDs.
     *
     * @return array Returns a data provider.
     */
    public function provideBodyTests(): array {
        $r = [
            'basic' => [['categoryID' => '$name:foo'], ['categoryID' => '(Category.CategoryID.name:foo)']],
            'nested' => [['r' => ['categoryID' => '$urlcode:foo']], ['r' => ['categoryID' => '(Category.CategoryID.urlcode:foo)']]],
            'callback' => [['insertUserID' => '$name:baz'], ['insertUserID' => '(User.UserID.name:baz)']],
            'fully qualified' => [['parentID' => '$userID.name:baz'], ['parentID' => '(User.UserID.name:baz)']],
        ];

        return $r;
    }

    /**
     * The base path should limit the scope of the middleware.
     */
    public function testBasePath() {
        $this->middleware->setBasePath('/api/');
        $r = new Request('/categories/$name:foo');

        $r = $this->callMiddleware($r);
        $this->assertEquals('/categories/$name:foo', $r->getPath());

        $r2 = new Request('/api/categories/$name:foo');
        $r2 = $this->callMiddleware($r2);
        $this->assertEquals('/api/categories/(Category.CategoryID.name:foo)', $r2->getPath());
    }

    /**
     * Email smart IDs should fail if email addresses are not enabled.
     */
    public function testNoEmail() {
        $this->expectException(ForbiddenException::class);

        $this->userResolver
            ->setViewEmail(true)
            ->setEmailEnabled(false);

        $this->callMiddleware(new Request('/users/$email:foo@bar.com'));
    }

    /**
     * Email smart IDs should fail if email addresses are not enabled.
     */
    public function testNoEmailPermission() {
        $this->expectException(PermissionException::class);

        $this->userResolver
            ->setViewEmail(false)
            ->setEmailEnabled(true);

        $this->callMiddleware(new Request('/users/$email:foo@bar.com'));
    }

    /**
     * Column names are whitelisted.
     */
    public function testBadColumn() {
        $this->expectException(ClientException::class);

        $this->callMiddleware(new Request('/categories/$foo:bar'));
    }

    /**
     * The directory before the smart ID must be in the whitelist.
     */
    public function testBadResource() {
        $this->expectException(ClientException::class);

        $this->callMiddleware(new Request('/foo/$bar:baz'));
    }

    /**
     * Path smart IDs must have a resource before them.
     */
    public function testNoResource() {
        $this->expectException(ClientException::class);

        $this->callMiddleware(new Request('/$foo:bar'));
    }

    /**
     * Query substitution smart IDs must be in the querystring.
     */
    public function testInvalidQueryField() {
        $this->expectException(ClientException::class);

        $this->callMiddleware(new Request('/$query:bar'));
    }

    /**
     * The columns must be an array or "*".
     */
    public function testBadColumns() {
        $this->expectException(\InvalidArgumentException::class);

        $this->middleware->addSmartID('FooID', 'foo', 'bar', 'Baz');
    }

    /**
     * The resolver must be a string or callable.
     */
    public function testBadResolver() {
        $this->expectException(\InvalidArgumentException::class);

        $this->middleware->addSmartID('FooID', 'foo', '*', 123);
    }

    /**
     * The `$me` smart ID without a session should give the guest ID.
     */
    public function testGuestMe() {
        $this->session->UserID = 0;

        $request = new Request('/users/$me');
        $r = $this->callMiddleware($request);
        $this->assertEquals($r->getPath(), '/users/0');
    }

    /**
     * Test the basic full ID suffix access.
     */
    public function testAddRemoveFullSuffix(): void {
        $this->middleware->addFullSuffix('foo');
        $this->assertTrue($this->middleware->hasFullSuffix('foo'));
        $this->middleware->removeFullSuffix('foo');
        $this->assertFalse($this->middleware->hasFullSuffix('foo'));
    }

    /**
     * When you remove a fully qualified suffix you should not match its smart ID.
     */
    public function testNoFullSuffix(): void {
        $request = new Request('/', 'POST', [
            'parentID' => '$userID.name:baz'
        ]);
        $r = $this->callMiddleware($request);
        $this->assertEquals(['parentID' => '(User.UserID.name:baz)'], $r->getBody());
        $this->middleware->removeFullSuffix('ID');
        $r = $this->callMiddleware($request);
        $this->assertEquals($request->getBody(), $r->getBody());
    }

    /**
     * Test the basic accessors for the base path.
     */
    public function testBasePathAccessors() {
        $this->middleware->setBasePath('/foo');
        $this->assertSame('/foo', $this->middleware->getBasePath());
    }
}
