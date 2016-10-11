<?php

/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Core;

use Gdn_PluginManager;

/**
 * Test the {@link Gdn_PluginManager} class.
 */
class PluginManagerTest extends \PHPUnit_Framework_Testcase {
    /**
     * Test a basic usage of {@link Gdn_PluginManager::registerCallback}.
     */
    public function testRegisterCallback() {
        $pm = new Gdn_PluginManager();

        $called = false;
        $pm->registerCallback('Foo_Bar_Handler', function () use (&$called) {
            $called = true;
        });

        $pm->callEventHandler($this, 'foo', 'bar');

        $this->assertTrue($called);
    }

    /**
     * Test event references.
     *
     * Since events don't return values well, plugins usually set a reference in their event arguments and check them
     * after firing event.
     */
    public function testEventArgReference() {
        $arg = false;
        $sender = (object)['EventArguments' => []];
        $sender->EventArguments['arg'] =& $arg;

        $pm = new Gdn_PluginManager();
        $pm->registerCallback('Arg_Ref_Handler', function ($sender, $args) {
            $args['arg'] = true;
        });

        $pm->callEventHandler($sender, 'arg', 'ref');
        $this->assertTrue($arg);
    }

    /**
     * Registering "create" callbacks behaves differently than "handler" callbacks.
     */
    public function testRegisterCallbackCreate() {
        $pm = new Gdn_PluginManager();

        $called = false;
        $pm->registerCallback('Foo_Bar_Create', function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($pm->hasNewMethod('foo', 'bar'));

        $pm->callNewMethod($this, 'foo', 'bar');
        $this->assertTrue($called);
    }

    /**
     * Registering "override" callbacks behaves differently than "handler" callbacks.
     */
    public function testRegisterCallbackOverride() {
        $pm = new Gdn_PluginManager();

        $called = false;
        $pm->registerCallback('Foo_Bar_Override', function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($pm->hasMethodOverride('foo', 'bar'));

        $pm->callMethodOverride($this, 'foo', 'bar');
        $this->assertTrue($called);
    }
}
