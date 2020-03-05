<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Garden\Web;

use Garden\Web\Action;
use PHPUnit\Framework\TestCase;
use VanillaTests\Fixtures\Request;

/**
 * Test the `Action` class.
 */
class ActionTest extends TestCase {
    /**
     * Test the args accessors.
     */
    public function testGetSetArgs(): void {
        $action = new Action('strtolower');
        $args = ['foo'];
        $action->setArgs($args);
        $this->assertSame($args, $action->getArgs());
    }

    /**
     * Test the callback accessors.
     */
    public function testGetSetCallback(): void {
        $action = new Action('strtolower');
        $action->setCallback('strtoupper');
        $this->assertSame('strtoupper', $action->getCallback());
    }

    /**
     * Test the action invocation.
     */
    public function testInvoke(): void {
        $action = new Action('strtolower', ['FOO']);
        $str = $action();
        $this->assertSame('foo', $str);
    }

    /**
     * Test replacing the request arg.
     */
    public function testReplaceRequest(): void {
        $r1 = new Request();
        $r2 = new Request();

        $action = new Action(function ($r) {
                return $r;
        }, [$r1]);
        $action->replaceRequest($r2);

        $r3 = $action();
        $this->assertSame($r2, $r3);
    }
}
