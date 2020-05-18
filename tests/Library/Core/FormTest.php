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
     * @var Gdn_Form
     */
    private $form;

    /**
     * Setup a dummy request because {@link Gdn_Form} needs it.
     */
    public function setUp(): void {
        parent::setUp();

        Gdn::factoryInstall(Gdn::AliasRequest, 'Gdn_Request', null, Gdn::FactoryRealSingleton, 'Create');
        Gdn::request()->fromImport(\Gdn_Request::create());

        $this->form = new \Gdn_Form('', 'bootstrap');
        $this->form->ErrorClass = 'test-error';
        \Gdn_Form::resetIDs();
    }

    /**
     * Test that errors are properly escape in the output.
     */
    public function testErrorEscaping() {
        $frm = $this->form;
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
<p class=test-error>&lt;script&gt;alert(document.cookie)&lt;/script&gt; @&lt;script&gt;alert(document.cookie)&lt;/script&gt;
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
        $input = $this->form->textBox('foo');
        $this->assertSame('<input type="text" id="Form_foo" name="foo" value="" class="form-control" />', $input);
    }

    /**
     * Test the react image upload component.
     */
    public function testImageUploadReact() {
        $frm = $this->form;

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
        $input = $this->form->color('test', ['placeholder' => 'My placeholder!']);

        $this->assertStringContainsString('placeholder="My placeholder!"', $input);
    }

    /**
     * Test a custom class being set on an input.
     */
    public function testTranslateClassesWithCustomClass() {
        $frm = new Gdn_Form('', 'bootstrap');

        $input = $frm->input('DefaultAvatar', 'file', ['class' => 'js-new-avatar-upload Hidden']);
        $this->assertSame(
            '<input type="file" id="Form_DefaultAvatar" name="DefaultAvatar" class="js-new-avatar-upload Hidden form-control-file" />',
            $input
        );
    }

    /**
     * The `Gdn_Form::InputPrefix` property is deprecated.
     */
    public function testInputPrefixDeprecation(): void {
        $this->expectDeprecation();
        $this->form->InputPrefix = 'foo';

        @$this->form->InputPrefix = 'bar';
        $p = @$this->form->InputPrefix;
        $this->assertSame('bar', $p);
    }

    /**
     * Test `Gdn_Form::addErrorClass()`.
     */
    public function testAddErrorClass(): void {
        $attributes = ['class' => 'foo'];
        $this->form->addErrorClass($attributes);
        $this->assertSame('foo test-error', $attributes['class']);

        $attributes = [];
        $this->form->addErrorClass($attributes);
        $this->assertSame('test-error', $attributes['class']);
    }

    /**
     * Test `Gdn_Form::setStyles()` and `Gdn_Form::getStyles()`.
     */
    public function testStyleAccessors(): void {
        $this->form->setStyles('foo');

        $this->assertSame('Button', $this->form->getStyle('button'));
        $this->assertSame('bar', $this->form->getStyle('foo', 'bar'));

        $this->form->setStyles('bootstrap');
        $this->assertSame('form-control', $this->form->getStyle('foo'));
    }

    /**
     * Test the default `Gdn_Form::bodyBox()`.
     */
    public function testDefaultBodyBox(): void {
        $actual = $this->form->bodyBox();
        $expected = <<<EOT
<div class="bodybox-wrap">
    <input type="hidden" id="Form_Format" name="Format" value="rich" />
    <div class="input-wrap">
        <textarea id="Form_Body" name="Body" class="form-control js-bodybox" format="rich" rows="6" cols="100"></textarea>
    </div>
</div>
EOT;
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test the default `Gdn_Form::button()`.
     */
    public function testDefaultButton(): void {
        $actual = $this->form->button('fo>"');
        $expected = <<<EOT
<button type="submit" id="Form_fo" name="fo&gt;&quot;" class="btn btn-primary" value="fo&gt;&quot;">fo&gt;&quot;</button>
EOT;

        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test the default `Gdn_Form::linkButton()`.
     */
    public function testDefaultLinkButton(): void {
        $actual = $this->form->linkButton('f<b>o</b>"', 'http://example.com#>');
        $expected = <<<EOT
<a href="http://example.com#&gt;" class="btn btn-primary">f<b>o</b>"</a>
EOT;
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test the default `Gdn_Form::toggle()`.
     */
    public function testDefaultToggle(): void {
        $actual = $this->form->toggle('fo>"');
        $expected = <<<EOT
<div class="toggle-wrap">
<input type="hidden" name="Checkboxes[]" value="fo&gt;&quot;" />
<input type="checkbox" id="Form_fo" name="fo&gt;&quot;" value="1" aria-labelledby="label-Form_fo" class="toggle-input" />
<label for="Form_fo" class="toggle">
</div>
EOT;
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test the default `Gdn_Form::fileUpload()`.
     */
    public function testDefaultFileUpload(): void {
        $actual = $this->form->fileUpload('fo>"');
        $expected = <<<EOT
<label class="file-upload">
<input type="file" name="fo&gt;&quot;" id="Form_fo"  class="js-file-upload form-control">
<span class="file-upload-choose" data-placeholder="Choose">Choose</span>
<span class="file-upload-browse">Browse</span>
</label>
EOT;

        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test the default `Gdn_Form::fileUploadWrap()`.
     */
    public function testDefaultFileUploadWrap(): void {
        $actual = $this->form->fileUploadWrap('fo>"');
        $expected = <<<EOT
<div class="input-wrap">
<label class="file-upload">
<input type="file" name="fo&gt;&quot;" id="Form_fo"  class=" js-file-upload form-control">
<span class="file-upload-choose" data-placeholder="Choose">Choose</span>
<span class="file-upload-browse">Browse</span>
</label></div>
EOT;

        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test the default `Gdn_Form::imageUploadPreview()`.
     */
    public function testDefaultImageUploadPreview(): void {
        $actual = $this->form->imageUploadPreview('fo>"', 'b<b>a</b>', 'd<b>esc</b>');
        $expected = <<<EOT
<li class="form-group js-image-preview-form-group">
    <div class="label-wrap">
        <div class="label">b<b>a</b></div>
        <div class="info">d<b>esc</b></div>
        <div id="fo-preview-wrapper" class="js-image-preview-old">
            <input type="hidden" id="Form_fo" name="fo&gt;&quot;" value="" />
        </div>
        <div class="js-image-preview-new hidden">
            <div><img class="js-image-preview"></div>
            <div><a class="js-remove-image-preview" href="#">Undo</a></div>
        </div>
    </div>
    <div class="input-wrap">
        <label class="file-upload">
            <input type="file" name="fo&gt;&quot;_New" id="Form_fo_New"  class="js-image-upload js-file-upload form-control">
            <span class="file-upload-choose" data-placeholder="Choose">Choose</span>
            <span class="file-upload-browse">Browse</span>
        </label>
    </div>
</li>
EOT;

        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test `Gdn_Form::escapeFieldName()` and `Gdn_Form::unescapeFieldName()`.
     *
     * @param string $name
     * @dataProvider provideEscapeFieldNameTests
     */
    public function testEscapeFieldName(string $name) {
        $actual = $this->form->unescapeFieldName($this->form->escapeFieldName($name));
        $this->assertSame($name, $actual);
    }

    /**
     * Provide some field names to test escaping.
     *
     * @return array
     */
    public function provideEscapeFieldNameTests(): array {
        $r = [
            ['a'],
            ['a.b'],
            ['a-dot-b']
        ];
        return array_column($r, null, 0);
    }

    /**
     * Test the default `Gdn_Form::imageUpload()`.
     */
    public function testDefaultImageUpload(): void {
        $actual = $this->form->imageUpload('fo>"');
        $expected = <<<EOT
<div class="FileUpload ImageUpload">
    <input type="hidden" id="Form_fo" name="fo&gt;&quot;" value="" />
    <div>
        <input type="file" id="Form_fo_New" name="fo&gt;&quot;_New" class="form-control-file" />
    </div>
</div>
EOT;

        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test the default `Gdn_Form::inputWrap()`.
     */
    public function testDefaultInputWrap(): void {
        $actual = $this->form->inputWrap('fo>"');
        $expected = <<<EOT
<div class="input-wrap">
    <input type="text" id="Form_fo" name="fo&gt;&quot;" value="" class="form-control" />
</div>
EOT;
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test the default `Gdn_Form::labelWrap()`.
     */
    public function testDefaultLabelWrap(): void {
        $actual = $this->form->labelWrap('<b>a</b>', 'fo>"');
        $expected = <<<EOT
<div class="label-wrap">
    <label for="Form_fo"><b>a</b></label>
</div>
EOT;
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test `Gdn_Form::labelCode()`.
     *
     * @param string|array $code
     * @param string $expected
     * @dataProvider provideLabelCodeTests
     */
    public function testLabelCode($code, string $expected): void {
        $actual = Gdn_Form::labelCode($code);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide some IDs to convert to label codes.
     *
     * @return array
     */
    public function provideLabelCodeTests(): array {
        $r = [
            'CamelCase' => ['CamelCase', 'Camel Case'],
            'insertID' => ['insertID', 'Insert ID'],
            'User1' => ['User1', 'User 1'],
            'User.Bar' => ['User.Bar', 'Bar'],
            'array label code' => [['LabelCode' => 'user1'], 'user1'],
            'array name' => [['Name' => 'user1'], 'User 1'],
        ];

        return $r;
    }
}
