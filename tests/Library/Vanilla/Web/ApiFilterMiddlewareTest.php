<?php
/**
 * @author Dani M <danim@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\Exception\ServerException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Vanilla\Web\ApiFilterMiddleware;
use VanillaTests\Fixtures\Request;

/**
 * Test ApiFilterMiddleware.
 */
class ApiFilterMiddlewareTest extends TestCase {

    /**
     * Test ApiFilterMiddleware with a whitelisted field.
     */
    public function testValidationSuccess() {
        $request = new Request();
        $apiMiddleware = new ApiFilterMiddleware();
        $testSuccessArray = ['discussionid' => 1];
        $response =  call_user_func($apiMiddleware, $request, function ($request) use ($testSuccessArray) {
            return new Data($testSuccessArray, ['request' => $request]);
        });
        $this->assertEquals(['discussionid' => 1], $response->getData());
    }

    /**
     * Test ApiFilterMiddleware with a blacklisted field.
     */
    public function testValidationFail() {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Unexpected field in content: insertipaddress');
        $request = new Request();
        $apiMiddleware = new ApiFilterMiddleware();
        $testFailureArray = [['discussionid' => 1, 'type' => 'Discussion', 'name' => 'testdiscussion', 'insertIPAddress' => '10.10.10.10']];
        call_user_func($apiMiddleware, $request, function ($request) use ($testFailureArray) {
            return new Data($testFailureArray, ['request' => $request]);
        });
    }

    /**
     * Test ApiFilterMiddleware with multiple layered test data.
     */
    public function testLayeredValidationFail() {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Unexpected field in content: email');
        $request = new Request();
        $apiMiddleware = new ApiFilterMiddleware();
        $testFailureArray = [
            [
                'discussionid' => 1,
                'name' => 'testdiscussion',
                'type' => 'discussion',
                'insertUser' => [
                    'userID'=> 1,
                    'name' => 'testuser',
                    'email' => 'test@test.com']
            ]
        ];
        call_user_func($apiMiddleware, $request, function ($request) use ($testFailureArray) {
            return new Data($testFailureArray, ['request' => $request]);
        });
    }

    /**
     * Test ApiFilterMiddleware with an allowed blacklisted field.
     */
    public function testValidationAllowed() {
        $request = new Request();
        $apiMiddleware = new ApiFilterMiddleware();
        $testSuccessArray = [['discussionid' => 1, 'type' => 'Discussion', 'name' => 'testdiscussion', 'insertIPAddress' => '10.10.10.10']];
        $response = call_user_func($apiMiddleware, $request, function ($request) use ($testSuccessArray) {
            return new Data($testSuccessArray, ['request' => $request, 'api-allow' => ['insertIPAddress']]);
        });
        $this->assertEquals($testSuccessArray, $response->getData());
    }

    /**
     * Test ApiFilterMiddleware with an allowed uppercased blacklisted field.
     */
    public function testValidationAllowedCasing() {
        $request = new Request();
        $apiMiddleware = new ApiFilterMiddleware();
        $testSuccessArray = [['discussionid' => 1, 'type' => 'Discussion', 'name' => 'testdiscussion', 'insertIPAddress' => '10.10.10.10']];
        $response = call_user_func($apiMiddleware, $request, function ($request) use ($testSuccessArray) {
            return new Data($testSuccessArray, ['request' => $request, 'api-allow' => ['InsertIPAddress']]);
        });
        $this->assertEquals($testSuccessArray, $response->getData());
    }

    /**
     * Test getBlacklistFields().
     */
    public function testGetBlacklist() {
        $apiMiddleware = new ApiFilterMiddleware();
        $actual = $apiMiddleware->getBlacklistFields();
        $expected = ['password', 'email', 'insertipaddress', 'updateipaddress'];
        $this->assertSame($expected, $actual);
    }

    /**
     * Set up function to call protected methods.
     *
     * @param object $testApiMiddleware Middleware object to test with.
     * @param string $protectedMethod Protected method to test.
     * @param array $testParameters Parameters to pass to $protectedMethod.
     * @return mixed Returns the return value of the called function.
     * @throws ReflectionException Throws exception.
     */
    public function invokeProtectedMethod(object &$testApiMiddleware, string $protectedMethod, array $testParameters) {
        $reflectedApiMiddleware = new \ReflectionClass(get_class($testApiMiddleware));
        $method = $reflectedApiMiddleware->getMethod($protectedMethod);
        $method->setAccessible(true);

        return $method->invokeArgs($testApiMiddleware, $testParameters);
    }

    /**
     * Test addBlacklistField().
     */
    public function testAddBlacklistField() {
        $apiMiddleware = new ApiFilterMiddleware();
        $this->invokeProtectedMethod($apiMiddleware, 'addBlacklistField', ['topsecretinfo']);
        $blacklistFields = $apiMiddleware->getBlacklistFields();
        $this->assertContains('topsecretinfo', $blacklistFields);
    }

    /**
     * Test removeBlacklistField().
     */
    public function testRemoveBlacklistField() {
        $apiMiddleware = new ApiFilterMiddleware();
        $this->invokeProtectedMethod($apiMiddleware, 'removeBlacklistField', ['email']);
        $blacklistFields = $apiMiddleware->getBlacklistFields();
        $this->assertNotContains('email', $blacklistFields);
    }
}
