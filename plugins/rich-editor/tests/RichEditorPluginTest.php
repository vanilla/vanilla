<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\Formats\RichFormat;
use VanillaTests\SiteTestCase;

/**
 *
 */
class RichEditorPluginTest extends SiteTestCase
{
    public static $addons = ["rich-editor"];
    private RichEditorPlugin $plugin;

    public function setUp(): void
    {
        $this->plugin = $this->container()->get(RichEditorPlugin::class);
        parent::setUp();
    }

    /**
     * Test if we will force the content to be interpreted as rich or not.
     *
     * @param $format
     * @param $expected
     * @return void
     * @dataProvider provideFormat
     */
    public function testIsForcedRich($format, $expected): void
    {
        $errMessage = $expected
            ? "Expected $format to be interpreted as rich"
            : "Expected $format to not be interpreted as rich";
        $form = new Gdn_Form();
        $form->setValue("Format", $format);
        $result = $this->plugin->isForcedRich($form);
        $this->assertEquals($expected, $result, $errMessage);
    }

    /**
     * Provide various formats to test on.
     *
     * @return array[]
     */
    public static function provideFormat(): array
    {
        return [[RichFormat::FORMAT_KEY, true], ["", false], [HtmlFormat::FORMAT_KEY, false]];
    }
}
