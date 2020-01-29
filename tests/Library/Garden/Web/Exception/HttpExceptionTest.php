<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Garden\Web\Exception;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\MethodNotAllowedException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Guzzle\Tests\Http\Server;

/**
 * Basic access tests for the `HttpException` class.
 */
class HttpExceptionTest extends \PHPUnit\Framework\TestCase {

    /**
     * An exception with an empty message should use the response code for the message.
     */
    public function testAutoMessage(): void {
        $ex = new ClientException();
        $this->assertSame('Bad Request', $ex->getMessage());
    }

    /**
     * Test basic HTTP status class creation.
     *
     * @param int $status
     * @param string $class
     * @dataProvider provideStatusClasses
     */
    public function testCreateFromStatus(int $status, string $class): void {
        $ex = HttpException::createFromStatus($status);
        $this->assertInstanceOf($class, $ex);
    }

    /**
     * Provide tests for `HttpException::createFromStatus()`.
     *
     * @return array
     */
    public function provideStatusClasses(): array {
        $r = [
            [400, ClientException::class],
            [403, ForbiddenException::class],
            [404, NotFoundException::class],
            [500, ServerException::class],
        ];

        return array_column($r, null, 0);
    }

    /**
     * A 405 should result in a `MethodNotAllowed` exception.
     */
    public function testCreateFromStatus405(): void {
        $ex = HttpException::createFromStatus(405, '', ['method' => 'GET', 'allow' => ['POST']]);
        $this->assertInstanceOf(MethodNotAllowedException::class, $ex);
        /* @var \Garden\Web\Exception\MethodNotAllowedException $ex */
        $this->assertSame(['POST'], $ex->getAllow());
        $this->assertSame('GET not allowed.', $ex->getMessage());
    }

    /**
     * Test creating an exception with an unknown HTTP status.
     */
    public function testCreateFromStatusBad(): void {
        $ex = HttpException::createFromStatus(-1000);
        $this->assertInstanceOf(ServerException::class, $ex);
    }

    /**
     * You can pass a description to the context.
     */
    public function testDescriptionAccess(): void {
        $ex = new NotFoundException('Page', ['description' => 'foo']);
        $this->assertSame('foo', $ex->getDescription());
    }

    /**
     * Test basic JSON serialization.
     */
    public function testJsonSerializeBasic() {
        $ex = new ClientException('', 400, ['HTTP_FOO' => 'foo', 'bar' => 'bar']);
        $json = $ex->jsonSerialize();
        $this->assertArrayNotHasKey('HTTP_FOO', $json);
        $this->assertSame('bar', $json['bar']);
    }

    /**
     * The method not allowed exception can take an empty method name.
     */
    public function testMethodNotAllowedNoMethod() {
        $ex = new MethodNotAllowedException('');
        $this->assertSame('Method not allowed.', $ex->getMessage());
    }
}
