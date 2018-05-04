<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla\Web;


use Garden\Web\Data;
use Garden\Web\RequestInterface;
use PHPUnit\Framework\TestCase;
use Vanilla\Web\UserSmartIDResolver;
use VanillaTests\Fixtures\Request;

/**
 * Test the **SmartIDMiddleware** class.
 */
class SmartIDMiddlewareTest extends TestCase {
    protected $middleware;

    /**
     * Create a configured test middleware for each test.
     */
    public function setUp() {
        $this->middleware =  new TestSmartIDMiddleware();
        $this->middleware->addSmartID('CategoryID', 'categories', ['name', 'urlcode'], 'Category');

        $usr = new UserSmartIDResolver();
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
}
