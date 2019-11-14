<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\RequestInterface;
use PHPUnit\Framework\TestCase;
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
    public function setUp() {
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
     *
     * @expectedException \Garden\Web\Exception\ForbiddenException
     */
    public function testNoEmail() {
        $this->userResolver
            ->setViewEmail(true)
            ->setEmailEnabled(false);

        $this->callMiddleware(new Request('/users/$email:foo@bar.com'));
    }

    /**
     * Email smart IDs should fail if email addresses are not enabled.
     *
     * @expectedException \Vanilla\Exception\PermissionException
     */
    public function testNoEmailPermission() {
        $this->userResolver
            ->setViewEmail(false)
            ->setEmailEnabled(true);

        $this->callMiddleware(new Request('/users/$email:foo@bar.com'));
    }

    /**
     * Column names are whitelisted.
     *
     * @expectedException \Garden\Web\Exception\ClientException
     */
    public function testBadColumn() {
        $this->callMiddleware(new Request('/categories/$foo:bar'));
    }

    /**
     * The directory before the smart ID must be in the whitelist.
     *
     * @expectedException \Garden\Web\Exception\ClientException
     */
    public function testBadResource() {
        $this->callMiddleware(new Request('/foo/$bar:baz'));
    }

    /**
     * Path smart IDs must have a resource before them.
     *
     * @expectedException \Garden\Web\Exception\ClientException
     */
    public function testNoResource() {
        $this->callMiddleware(new Request('/$foo:bar'));
    }

    /**
     * Query substitution smart IDs must be in the querystring.
     *
     * @expectedException \Garden\Web\Exception\ClientException
     */
    public function testInvalidQueryField() {
        $this->callMiddleware(new Request('/$query:bar'));
    }

    /**
     * The columns must be an array or "*".
     *
     * @expectedException \InvalidArgumentException
     */
    public function testBadColumns() {
        $this->middleware->addSmartID('FooID', 'foo', 'bar', 'Baz');
    }

    /**
     * The resolver must be a string or callable.
     *
     * @expectedException \InvalidArgumentException
     */
    public function testBadResolver() {
        $this->middleware->addSmartID('FooID', 'foo', '*', 123);
    }

    /**
     * The `$me` smart ID without a session should be an exception.
     *
     * @expectedException \Garden\Web\Exception\ForbiddenException
     */
    public function testInvalidMe() {
        $this->session->UserID = 0;

        $request = new Request('/users/$me');
        $r = $this->callMiddleware($request);
    }
}
