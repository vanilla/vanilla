<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use VanillaTests\MinimalContainerTestCase;
use Gdn;
use Gdn_Form;

class FormTest extends MinimalContainerTestCase {
    /**
     * Setup a dummy request because {@link Gdn_Form} needs it.
     */
    public function setUp(): void {
        parent::setUp();

        Gdn::factoryInstall(Gdn::AliasRequest, 'Gdn_Request', null, Gdn::FactoryRealSingleton, 'Create');
        Gdn::request()->fromImport(\Gdn_Request::create());
    }

    /**
     * Test a basic text box.
     */
    public function testTextBox() {
        $frm = new Gdn_Form('', 'bootstrap');

        $input = $frm->textBox('foo');
        $this->assertSame('<input type="text" id="Form_foo" name="foo" value="" class="form-control" />', $input);
    }

    /**
     * Test that placeholders can be applied to color inputs.
     */
    public function testColorInputPlaceholder() {
        $frm = new Gdn_Form('', 'bootstrap');
        $input = $frm->color('test', ['placeholder' => 'My placeholder!']);

        $this->assertStringContainsString('placeholder="My placeholder!"', $input);
    }

    /**
     * Test a custom class being set on an input.
     */
    public function testTranslateClassesWithCustomClass() {
        $frm = new Gdn_Form('', 'bootstrap');

        $input = $frm->input('DefaultAvatar', 'file', ['class' => 'js-new-avatar-upload Hidden']);
        $this->assertSame('<input type="file" id="Form_DefaultAvatar" name="DefaultAvatar" class="js-new-avatar-upload Hidden form-control-file" />', $input);
    }
}
