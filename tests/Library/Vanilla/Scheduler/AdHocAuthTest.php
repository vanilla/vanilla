<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Scheduler;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Web\RequestInterface;
use Gdn_Configuration;
use Gdn_Request;
use Vanilla\Scheduler\Auth\AdHocAuth;
use Vanilla\Scheduler\Auth\AdHocAuth401Exception;
use Vanilla\Scheduler\Auth\AdHocAuth403Exception;
use Vanilla\Scheduler\Auth\AdHocAuth412Exception;
use Vanilla\Scheduler\Auth\AdHocAuthException;

/**
 * Class AdHocAuthTest
 *
 * @package VanillaTests\Library\Vanilla\Scheduler
 */
class AdHocAuthTest extends SchedulerTestCase {

    /**
     * Test Missing Token
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     * @throws AdHocAuthException On error.
     */
    public function testMissingTokenConfiguration() {
        $this->expectException(AdHocAuth412Exception::class);
        $container = $this->getConfiguredContainer();

        $request = $this->createMock(Gdn_Request::class);
        $container->setInstance(RequestInterface::class, $request);

        /** @var AdHocAuth $auth */
        $auth = $container->get(AdHocAuth::class);
        $auth->validateToken();
    }

    /**
     * Test Missing Token
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     * @throws AdHocAuthException On error.
     */
    public function testMissingTokenHeader() {
        $this->expectException(AdHocAuth403Exception::class);
        $container = $this->getConfiguredContainer();

        $request = $this->createMock(Gdn_Request::class);
        $request->method('getHeader')->willReturn('');
        $container->setInstance(RequestInterface::class, $request);

        $config = $container->get(Gdn_Configuration::class);
        $config->set('Garden.Scheduler.Token', uniqid(), true, false);

        /** @var AdHocAuth $auth */
        $auth = $container->get(AdHocAuth::class);
        $auth->validateToken();
    }

    /**
     * Test Missing Token
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     * @throws AdHocAuthException On error.
     */
    public function testMalformedTokenHeader() {
        $this->expectException(AdHocAuth401Exception::class);
        $container = $this->getConfiguredContainer();

        $request = $this->createMock(Gdn_Request::class);
        $request->method('getHeader')->willReturn('NoBearer abc');
        $container->setInstance(RequestInterface::class, $request);

        $config = $container->get(Gdn_Configuration::class);
        $config->set('Garden.Scheduler.Token', uniqid(), true, false);

        /** @var AdHocAuth $auth */
        $auth = $container->get(AdHocAuth::class);
        $auth->validateToken();
    }

    /**
     * Test Missing Token
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     * @throws AdHocAuthException On error.
     */
    public function testWrongToken() {
        $this->expectException(AdHocAuth401Exception::class);
        $container = $this->getConfiguredContainer();

        $request = $this->createMock(Gdn_Request::class);
        $request->method('getHeader')->willReturn('abc');
        $container->setInstance(RequestInterface::class, $request);

        $config = $container->get(Gdn_Configuration::class);
        $config->set('Garden.Scheduler.Token', 'def', true, false);

        /** @var AdHocAuth $auth */
        $auth = $container->get(AdHocAuth::class);
        $auth->validateToken();
    }

    /**
     * Test Missing Token
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     * @throws AdHocAuthException On error.
     */
    public function testValidToken() {
        $container = $this->getConfiguredContainer();

        $request = $this->createMock(Gdn_Request::class);
        $request->method('getHeader')->willReturn('bearer abc');
        $container->setInstance(RequestInterface::class, $request);

        $config = $container->get(Gdn_Configuration::class);
        $config->set('Garden.Scheduler.Token', 'abc', true, false);

        /** @var AdHocAuth $auth */
        $auth = $container->get(AdHocAuth::class);
        $auth->validateToken();
    }
}
