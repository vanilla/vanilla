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
<div aria-label="Validation Failed" class="Messages Errors" role="alert">
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

    /**
     * Test the default `Gdn_Form::checkbox()`.
     */
    public function testDefaultCheckbox(): void {
        $actual = $this->form->checkBox('fo>"');
        $expected = <<<EOT
<div class="checkbox">
    <input type="hidden" name="Checkboxes[]" value="fo&gt;&quot;" />
    <input type="checkbox" id="Form_fo" name="fo&gt;&quot;" value="1" class="" />
</div>
EOT;
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test the default `Gdn_Form::radio()`.
     */
    public function testDefaultRadio(): void {
        $actual = $this->form->radio('fo>"', 'bar', ['value' => '2']);
        $expected = <<<EOT
<label>
    <input id=Form_fo name=fo&gt;&quot; type=radio value=2> bar
</label>
EOT;
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test the default `Gdn_Form::checkBoxList()`.
     */
    public function testDefaultCheckboxList(): void {
        $actual = $this->form->checkBoxList('foo', ['a' => 'b', 'c' => 'd']);
        $expected = <<<EOT
<ul class="CheckBoxList">
    <li><div class="checkbox">
        <label for="foo1">
            <input type="hidden" name="Checkboxes[]" value="foo" />
            <input type="checkbox" id="foo1" name="foo[]" value="b" class="" /> a
        </label>
    </div></li>
    <li><div class="checkbox">
        <label for="foo2">
            <input type="hidden" name="Checkboxes[]" value="foo" />
            <input type="checkbox" id="foo2" name="foo[]" value="d" class="" /> c
        </label>
    </div></li>
</ul>
EOT;
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test the default `Gdn_Form::radioList()`.
     */
    public function testDefaultRadioList(): void {
        $actual = $this->form->radioList('foo', ['a' => 'b', 'c' => 'd']);
        $expected = <<<EOT
<div class="radio">
    <label>
        <input type="radio" id="Form_foo" name="foo" value="a" class="" /> b
    </label>
</div>
<div class="radio">
    <label>
        <input type="radio" id="Form_foo1" name="foo" value="c" class="" /> d
    </label>
</div>
EOT;
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test the default `Gdn_Form::dropDown()`.
     */
    public function testDefaultDropdown(): void {
        $actual = $this->form->dropDown('foo', ['>' => 'b', '"' => 'd']);
        $expected = <<<EOT
<select id="Form_foo" data-value="" name="foo" class="form-control">
<option value="&gt;">b</option>
<option value="&quot;">d</option>
</select>
EOT;
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test the default `Gdn_Form::open()`.
     */
    public function testDefaultOpen(): void {
        $actual = $this->form->open(['action' => 'https://example.com']).
            $this->form->close();
        $expected = <<<EOT
<form method="post" action="https://example.com" autocomplete="off" >
    <div>
        <input type="hidden" id="Form_TransientKey" name="TransientKey" value="" />
    </div>
</form>
EOT;
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test a form with a GET method and a query string.
     */
    public function testOpenWithGetQuery(): void {
        $actual = $this->form->open([
            'action' => 'http://example.com?foo=bar',
            'method' => 'GET',
        ]).$this->form->close();

        $expected = <<<EOT
<form method="GET" action="http://example.com" autocomplete="off" >
    <div>
        <input type="hidden" name="foo" value="bar" />
        <input type="hidden" id="Form_TransientKey" name="TransientKey" value="" />
    </div>
</form>
EOT;
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test the default `Gdn_Form::date()`.
     */
    public function testDefaultDate(): void {
        $actual = $this->form->date('fo>"', ['YearRange' => '2019-2020']);
        $expected = <<<EOT
<select id="Form_fo_Month" data-value="" name="fo&gt;&quot;_Month" class="Month">
    <option value="0">Month</option>
    <option value="1">Jan</option>
    <option value="2">Feb</option>
    <option value="3">Mar</option>
    <option value="4">Apr</option>
    <option value="5">May</option>
    <option value="6">Jun</option>
    <option value="7">Jul</option>
    <option value="8">Aug</option>
    <option value="9">Sep</option>
    <option value="10">Oct</option>
    <option value="11">Nov</option>
    <option value="12">Dec</option>
</select>
<select id="Form_fo_Day" data-value="" name="fo&gt;&quot;_Day" class="Day">
    <option value="0">Day</option>
    <option value="1">1</option>
    <option value="2">2</option>
    <option value="3">3</option>
    <option value="4">4</option>
    <option value="5">5</option>
    <option value="6">6</option>
    <option value="7">7</option>
    <option value="8">8</option>
    <option value="9">9</option>
    <option value="10">10</option>
    <option value="11">11</option>
    <option value="12">12</option>
    <option value="13">13</option>
    <option value="14">14</option>
    <option value="15">15</option>
    <option value="16">16</option>
    <option value="17">17</option>
    <option value="18">18</option>
    <option value="19">19</option>
    <option value="20">20</option>
    <option value="21">21</option>
    <option value="22">22</option>
    <option value="23">23</option>
    <option value="24">24</option>
    <option value="25">25</option>
    <option value="26">26</option>
    <option value="27">27</option>
    <option value="28">28</option>
    <option value="29">29</option>
    <option value="30">30</option>
    <option value="31">31</option>
</select>
<select id="Form_fo_Year" data-value="" name="fo&gt;&quot;_Year" class="Year">
    <option value="0">Year</option>
    <option value="2019">2019</option>
    <option value="2020">2020</option>
</select>
<input type="hidden" name="DateFields[]" value="fo&gt;&quot;" />
EOT;
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Make sure that `Gd_Form::getFormValue()` supports `"[]"` style form access.
     *
     * @param string $name
     * @param string $expected
     * @dataProvider provideNestedFormValueNames
     */
    public function testGetFormValueNesting(string $name, string $expected) {
        $this->form->formValues(['a' => 'a', 'b' => ['a' => 'b', 'b' => ['a' => 'c']]]);

        $this->assertSame($expected, $this->form->getFormValue($name));
    }

    /**
     * Data provider.
     *
     * @return array
     */
    public function provideNestedFormValueNames(): array {
        $r = [
            ['a', 'a'],
            ['b[a]', 'b'],
            ['b[b][a]', 'c'],
        ];
        return array_column($r, null, 0);
    }

    /**
     * Test Gdn_Form::verifyAdditionalPermissions()
     *
     * @param array $testPermissions
     * @param array $testCategory
     * @param bool $expected Expected result
     * @dataProvider provideTestVerifyAdditionalPermissionsData
     */
    public function testVerifyAdditionalPermissions(array $testPermissions, array $testCategory, bool $expected) {
        $actual = Gdn_Form::verifyAdditionalPermissions($testPermissions, $testCategory);
        $this->assertSame($actual, $expected);
    }

    /**
     * Provide test data for testVerifyAdditionalPermissions.
     *
     * @return array
     */
    public function provideTestVerifyAdditionalPermissionsData() {
        $r = [
            'hasPermission' => [
                ["CanAdd"],
                [
                    "CategoryID" => 1,
                    "CategoryName" => 'Test',
                    "CanAdd" => true,
                ],
                true,
            ],
            'doesntHavePermission' => [
                ["CanAdd"],
                [
                    "CategoryID" => 1,
                    "CategoryName" => 'Test',
                    "CanAdd" => false,
                ],
                false,
            ],
            'hasMultiple' => [
                ["CanAdd", "CanEdit"],
                [
                    "CategoryID" => 1,
                    "CategoryName" => 'Test',
                    "CanAdd" => true,
                    "CanEdit" => true,
                ],
                true,
            ],
            'hasOneOfTwo' => [
                ["CanAdd", "CanEdit"],
                [
                    "CategoryID" => 1,
                    "CategoryName" => 'Test',
                    "CanAdd" => true,
                    "CanEdit" => false,
                ],
                false,
            ],
            'hasNeither' => [
                ["CanAdd", "CanEdit"],
                [
                    "CategoryID" => 1,
                    "CategoryName" => 'Test',
                    "CanAdd" => false,
                    "CanEdit" => false,
                ],
                false,
            ],
            'noCategoryKey' => [
                ["CanAdd"],
                [
                    "CategoryID" => 1,
                    "CategoryName" => 'Test',
                    "CanEdit" => false,
                ],
                false,
            ],
        ];

        return $r;
    }
}
