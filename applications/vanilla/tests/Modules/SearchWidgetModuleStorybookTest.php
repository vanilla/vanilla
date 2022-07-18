<?php
/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2008-2021 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Modules;

use Vanilla\Community\SearchWidgetModule;
use Vanilla\Forms\ApiFormChoices;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Forum\MockTagSearchFormSchema;
use VanillaTests\Storybook\StorybookGenerationTestCase;

/**
 * Test rendering of the "Search Widget" module.
 */
class SearchWidgetModuleStorybookTest extends StorybookGenerationTestCase
{
    use EventSpyTestTrait;

    public static $addons = ["vanilla"];

    /**
     * Test rendering of the Search Widget module.
     */
    public function testRender()
    {
        $this->generateStoryHtml("/", "Custom Search Widget");
    }

    /**
     * Event handler to mount Search Widget module.
     *
     * @param \Gdn_Controller $sender
     */
    public function base_render_before(\Gdn_Controller $sender)
    {
        /** @var SearchWidgetModule $module */
        $module = self::container()->get(SearchWidgetModule::class);
        $module->setTitle("Custom Search Forum");

        $schema1 = new MockTagSearchFormSchema($this->buildFormSchema("my-tab"), "my-tab", "Submit", "Hello Search");
        $schema2 = new MockTagSearchFormSchema(
            $this->buildFormSchema("my-tab2"),
            "my-tab2",
            "Submit 2",
            "Hello Search 2"
        );
        $module->setTabFormSchemaClasses([$schema1, $schema2]);
        $sender->addModule($module);
    }

    /**
     * Create a form schema.
     *
     * @param string $tabID
     *
     * @return array
     */
    private function buildFormSchema(string $tabID): array
    {
        return [
            "x-form" => [
                "url" => "/search",
                "searchParams" => [
                    "domain" => "discussions",
                    "scope" => "site",
                    "tagsOptions[0][tagCode]" => "{jobRole.urlcode}",
                    "tagsOptions[0][value]" => "{jobRole.tagId}",
                    "tagsOptions[0][label]" => "{jobRole.name}",
                    "tagsOptions[1][tagCode]" => "{industry.urlcode}",
                    "tagsOptions[1][value]" => "{industry.tagId}",
                    "tagsOptions[1][label]" => "{industry.name}",
                ],
                "submitButtonText" => "Submit",
            ],
            "properties" => [
                "tabID" => [
                    "type" => "string",
                    "const" => $tabID,
                ],
                "jobRole" => [
                    "type" => "object",
                    "x-control" => SchemaForm::dropDown(new FormOptions("Job Role"), new StaticFormChoices([])),
                ],
                "industry" => [
                    "type" => "object",
                    "x-control" => SchemaForm::dropDown(new FormOptions("Industry"), new StaticFormChoices([])),
                ],
            ],
            "anyOf" => [["required" => ["jobRole"]], ["required" => ["industry"]]],
            "additionalProperties" => false,
        ];
    }
}
