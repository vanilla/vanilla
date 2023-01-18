<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Storybook;

use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Formats\MarkdownFormat;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * HTML generation for the community in foundation.
 */
class RichEditorStorybookTest extends StorybookGenerationTestCase
{
    use CommunityApiTestTrait;

    /** @var string[] */
    public static $addons = ["rich-editor"];

    /**
     * Set up embeds for the tests
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::container()
            ->rule(EmbedService::class)
            ->addCall("addCoreEmbeds");
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
    public function testRenderedRichEditor(string $url, string $storyName)
    {
        $this->generateStoryHtml($url, $storyName);
    }

    /**
     * @return array[]
     */
    public function provideRichEditorTests(): array
    {
        return [];
    }

    /**
     * Test post being reinterpetted as rich.
     */
    public function testEditPostReinterpetRich()
    {
        $this->createDiscussion([
            "name" => "Markdown Post",
            "body" => "## Heading\n\n_italic_ **bold**\n\n- list 1\n- list 2",
            "format" => MarkdownFormat::FORMAT_KEY,
        ]);

        $this->runWithConfig(
            [
                \RichEditorPlugin::CONFIG_REINTERPRET_ENABLE => true,
            ],
            function () {
                $this->generateStoryHtml(
                    "/post/editdiscussion/" . $this->lastInsertedDiscussionID,
                    "RichEditor Convert Post"
                );
            }
        );
    }
}
