<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Modules;

use PHPUnit\Framework\TestCase;
use VanillaTests\Fixtures\MockNewDiscussionModule;
use VanillaTests\SiteTestTrait;

/**
 * Tests for getButtonsGroup() from NewDiscussionModule.
 */
class NewDiscussionModuleTest extends TestCase
{
    use SiteTestTrait;

    private $testDiscussionModule;

    /**
     * Get the names of addons to install.
     *
     * @return string[] Returns an array of addon names.
     */
    protected static function getAddons(): array
    {
        return ["vanilla"];
    }

    /**
     * Set up a mock discussion module to test on.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->testDiscussionModule = new MockNewDiscussionModule();
    }

    /**
     * Test getButtonGroups() against various scenarios.
     *
     * @param array $testButtons Array of Buttons to be sorted.
     * @param array $expected The expected result.
     * @dataProvider provideTestGetButtonGroupsArrays
     */
    public function testGetButtonGroups($testButtons, $expected)
    {
        foreach ($testButtons as $button) {
            $this->testDiscussionModule->addButton(
                $button["Text"],
                $button["Type"],
                $button["Url"],
                $button["Icon"],
                $button["asOwnButton"]
            );
        }
        $actual = $this->testDiscussionModule->getButtonGroups();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Provide test data for testGetButtonGroups().
     *
     * @return array Returns an array of test data.
     */
    public function provideTestGetButtonGroupsArrays()
    {
        $r = [
            "allOwnButtons" => [
                [
                    [
                        "Text" => "New Discussion",
                        "Url" => "/post/discussion",
                        "Icon" => "new-discussion",
                        "Type" => "discussion",
                        "asOwnButton" => true,
                    ],
                    [
                        "Text" => "Ask a Question",
                        "Url" => "/post/question",
                        "Icon" => "new-question",
                        "Type" => "question",
                        "asOwnButton" => true,
                    ],
                    [
                        "Text" => "New Poll",
                        "Url" => "/post/question",
                        "Icon" => "new-poll",
                        "Type" => "poll",
                        "asOwnButton" => true,
                    ],
                    [
                        "Text" => "New Idea",
                        "Url" => "/post/idea",
                        "Icon" => "new-idea",
                        "Type" => "idea",
                        "asOwnButton" => true,
                    ],
                ],
                [
                    [
                        [
                            "Text" => "New Discussion",
                            "Url" => "/post/discussion",
                            "Icon" => "new-discussion",
                            "Type" => "discussion",
                            "asOwnButton" => true,
                        ],
                    ],
                    [
                        [
                            "Text" => "Ask a Question",
                            "Url" => "/post/question",
                            "Icon" => "new-question",
                            "Type" => "question",
                            "asOwnButton" => true,
                        ],
                    ],
                    [
                        [
                            "Text" => "New Poll",
                            "Url" => "/post/question",
                            "Icon" => "new-poll",
                            "Type" => "poll",
                            "asOwnButton" => true,
                        ],
                    ],
                    [
                        [
                            "Text" => "New Idea",
                            "Url" => "/post/idea",
                            "Icon" => "new-idea",
                            "Type" => "idea",
                            "asOwnButton" => true,
                        ],
                    ],
                ],
            ],
            "allGroupButtons" => [
                [
                    [
                        "Text" => "New Discussion",
                        "Url" => "/post/discussion",
                        "Icon" => "new-discussion",
                        "Type" => "discussion",
                        "asOwnButton" => false,
                    ],
                    [
                        "Text" => "Ask a Question",
                        "Url" => "/post/question",
                        "Icon" => "new-question",
                        "Type" => "question",
                        "asOwnButton" => false,
                    ],
                    [
                        "Text" => "New Poll",
                        "Url" => "/post/question",
                        "Icon" => "new-poll",
                        "Type" => "poll",
                        "asOwnButton" => false,
                    ],
                    [
                        "Text" => "New Idea",
                        "Url" => "/post/idea",
                        "Icon" => "new-idea",
                        "Type" => "idea",
                        "asOwnButton" => false,
                    ],
                ],
                [
                    [
                        [
                            "Text" => "New Discussion",
                            "Url" => "/post/discussion",
                            "Icon" => "new-discussion",
                            "Type" => "discussion",
                            "asOwnButton" => false,
                        ],
                        [
                            "Text" => "Ask a Question",
                            "Url" => "/post/question",
                            "Icon" => "new-question",
                            "Type" => "question",
                            "asOwnButton" => false,
                        ],
                        [
                            "Text" => "New Poll",
                            "Url" => "/post/question",
                            "Icon" => "new-poll",
                            "Type" => "poll",
                            "asOwnButton" => false,
                        ],
                        [
                            "Text" => "New Idea",
                            "Url" => "/post/idea",
                            "Icon" => "new-idea",
                            "Type" => "idea",
                            "asOwnButton" => false,
                        ],
                    ],
                ],
            ],
            "twoGroupedTwoOwn" => [
                [
                    [
                        "Text" => "New Discussion",
                        "Url" => "/post/discussion",
                        "Icon" => "new-discussion",
                        "Type" => "discussion",
                        "asOwnButton" => true,
                    ],
                    [
                        "Text" => "Ask a Question",
                        "Url" => "/post/question",
                        "Icon" => "new-question",
                        "Type" => "question",
                        "asOwnButton" => false,
                    ],
                    [
                        "Text" => "New Poll",
                        "Url" => "/post/question",
                        "Icon" => "new-poll",
                        "Type" => "poll",
                        "asOwnButton" => true,
                    ],
                    [
                        "Text" => "New Idea",
                        "Url" => "/post/idea",
                        "Icon" => "new-idea",
                        "Type" => "idea",
                        "asOwnButton" => false,
                    ],
                ],
                [
                    [
                        [
                            "Text" => "Ask a Question",
                            "Url" => "/post/question",
                            "Icon" => "new-question",
                            "Type" => "question",
                            "asOwnButton" => false,
                        ],
                        [
                            "Text" => "New Idea",
                            "Url" => "/post/idea",
                            "Icon" => "new-idea",
                            "Type" => "idea",
                            "asOwnButton" => false,
                        ],
                    ],
                    [
                        [
                            "Text" => "New Discussion",
                            "Url" => "/post/discussion",
                            "Icon" => "new-discussion",
                            "Type" => "discussion",
                            "asOwnButton" => true,
                        ],
                    ],
                    [
                        [
                            "Text" => "New Poll",
                            "Url" => "/post/question",
                            "Icon" => "new-poll",
                            "Type" => "poll",
                            "asOwnButton" => true,
                        ],
                    ],
                ],
            ],
        ];

        return $r;
    }
}
