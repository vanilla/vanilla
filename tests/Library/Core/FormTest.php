<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use VanillaTests\Library\Vanilla\Formatting\HtmlNormalizeTrait;
use VanillaTests\MinimalContainerTestCase;
use Gdn;
use Gdn_Form;

/**
 * Tests for Gdn_Form.
 */
class FormTest extends MinimalContainerTestCase {

    use HtmlNormalizeTrait;

    /**
     * Setup a dummy request because {@link Gdn_Form} needs it.
     */
    public function setUp(): void {
        parent::setUp();

        Gdn::factoryInstall(Gdn::AliasRequest, 'Gdn_Request', null, Gdn::FactoryRealSingleton, 'Create');
        Gdn::request()->fromImport(\Gdn_Request::create());
    }

    /**
     * Test that errors are properly escape in the output.
     */
    public function testErrorEscaping() {
        $frm = new Gdn_Form('', 'bootstrap');
        $stringError = '<script>alert(document.cookie)</script>';
        $exception = new \Exception($stringError);
        $frm->addError($stringError, 'item1');
        $frm->addError($exception, 'item1');
        $frm->addError(new \Gdn_SanitizedUserException('<strong>Hello World</strong>'), 'item3');

        $frm->setValidationResults([
            'item1' => [$stringError],
            'item2' => [$exception],
        ]);

        // 3 fields have errors
        $this->assertEquals(3, $frm->errorCount());

        // Make sure we are escaped properly.
        $expectedHtml = <<<HTML
<div class="Messages Errors">
<ul>
<li>&lt;script&gt;alert(document.cookie)&lt;/script&gt;</li>
<li>&lt;script&gt;alert(document.cookie)&lt;/script&gt;</li>
<li>&lt;script&gt;alert(document.cookie)&lt;/script&gt;</li>
<li><strong>Hello World</strong></li>
<li>&lt;script&gt;alert(document.cookie)&lt;/script&gt;</li>
</ul>
</div>
HTML;

        $expectedString = '&lt;script&gt;alert(document.cookie)&lt;/script&gt;. &lt;script&gt;alert(document.cookie)&lt;/script&gt;.'
                        .' &lt;script&gt;alert(document.cookie)&lt;/script&gt;. <strong>Hello World</strong>.'
                        . ' &lt;script&gt;alert(document.cookie)&lt;/script&gt;.';

        $expectedInline = <<<HTML
<p class=Error>&lt;script&gt;alert(document.cookie)&lt;/script&gt; @&lt;script&gt;alert(document.cookie)&lt;/script&gt;
&lt;script&gt;alert(document.cookie)&lt;/script&gt;
</p>
HTML;

        $this->assertHtmlStringEqualsHtmlString($expectedHtml, $frm->errors());
        $this->assertEquals($expectedString, $frm->errorString());
        $this->assertHtmlStringEqualsHtmlString($expectedInline, $frm->inlineError('item1'));
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
     * Test the react image upload component.
     */
    public function testImageUploadReact() {
        $frm = new Gdn_Form('', 'bootstrap');

        $expected = "<div data-react='imageUploadGroup' data-props="
            ."'{&quot;label&quot;:&quot;test label&quot;,&quot;description&quot;:&quot;test desc&quot;,"
            ."&quot;initialValue&quot;:null,"
            ."&quot;fieldName&quot;:&quot;test&quot;}'></div>";
        $input = $frm->imageUploadReact('test', 'test label', 'test desc');
        $this->assertHtmlStringEqualsHtmlString($expected, $input);

        $frm->setData(['test' => '533ae319e87e04.jpg']);
        $input = $frm->imageUploadReact('test', 'test label', 'test desc');

        $expected = "<div data-react='imageUploadGroup' data-props="
        ."'{&quot;label&quot;:&quot;test label&quot;,&quot;description&quot;:&quot;test desc&quot;,"
        ."&quot;initialValue&quot;:&quot;http:\/\/vanilla.test\/minimal-container-test\/uploads\/533ae319e87e04.jpg&quot;,"
        ."&quot;fieldName&quot;:&quot;test&quot;}'></div>";

        $this->assertHtmlStringEqualsHtmlString($expected, $input);
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
