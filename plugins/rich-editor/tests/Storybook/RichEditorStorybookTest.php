<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Storybook;

use Vanilla\EmbeddedContent\EmbedService;

/**
 * HTML generation for the community in foundation.
 */
class RichEditorStorybookTest extends StorybookGenerationTestCase {

    /** @var string[] */
    public static $addons = ["rich-editor"];

    /**
     * Set up embeds for the tests
     *
     * @return void
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();
        self::container()->rule(EmbedService::class)
            ->addCall('addCoreEmbeds');
    }


    /**
     * Test the rendered rich editor.
     *
     * @dataProvider provideRichEditorTests
     *
     * @param string $url
     * @param string $storyName
     *
     * @return void
     */
    public function testRenderedRichEditor(string $url, string $storyName) {
        $this->generateStoryHtml($url, $storyName);
    }


    /**
     * @return array[]
     */
    public function provideRichEditorTests(): array {
        return [
            ['/richeditorstyles/formatting', 'RichEditor Formatting'],
            ['/richeditorstyles/images', 'RichEditor Images'],
        ];
    }
}
