<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Community;

use Garden\Schema\Schema;
use Vanilla\Community\Schemas\AbstractTabSearchFormSchema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\AbstractReactModule;

/**
 * Widget to search by tag.
 */
class SearchWidgetModule extends AbstractReactModule
{
    /** @var string|null */
    private $title = null;

    /** @var string|null */
    private $formSchema = null;

    /**
     * @param string|null $title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * Set form schema
     *
     * @param string|null $formSchema
     */
    public function setFormSchema(?string $formSchema): void
    {
        $this->formSchema = $formSchema;
    }

    /**
     * @param array<AbstractTabSearchFormSchema|string> $tabFormSchemaClasses
     */
    public function setTabFormSchemaClasses(array $tabFormSchemaClasses): void
    {
        /** @var AbstractTabSearchFormSchema[] $schemaInstances */
        $tabForms = [];
        foreach ($tabFormSchemaClasses as $schemaClass) {
            if ($schemaClass instanceof AbstractTabSearchFormSchema) {
                $instance = $schemaClass;
            } else {
                /** @var AbstractTabSearchFormSchema $instance */
                $instance = \Gdn::getContainer()->get($schemaClass);
            }

            $tabForms[] = $instance;
        }

        $schema = $this->buildFormSchema($tabForms);
        $this->formSchema = json_encode($schema);
    }

    /**
     * Find the default tab instance.
     *
     * @param AbstractTabSearchFormSchema[] $tabForms
     *
     * @return AbstractTabSearchFormSchema|null
     */
    private function findDefaultTabSchema(array $tabForms): ?AbstractTabSearchFormSchema
    {
        foreach ($tabForms as $tabForm) {
            if ($tabForm->isDefault()) {
                return $tabForm;
            }
        }

        return $tabForms[0] ?? null;
    }

    /**
     * Build the form schema.
     *
     * @param AbstractTabSearchFormSchema[] $tabForms
     *
     * @return Schema
     */
    private function buildFormSchema(array $tabForms): Schema
    {
        $defaultTabForm = $this->findDefaultTabSchema($tabForms);

        $tabOptions = [];
        foreach ($tabForms as $tabForm) {
            $tabOptions[$tabForm->getTabID()] = $tabForm->getTitle();
        }
        $schema = [
            "type" => "object",
            "x-control" => [
                "inputType" => "tabs",
                "property" => "tabID",
                "choices" => [
                    "staticOptions" => $tabOptions,
                ],
            ],
            "properties" => [
                "tabID" => [
                    "type" => "string",
                    "enum" => array_keys($tabOptions),
                    "default" => $defaultTabForm->getTabID(),
                ],
            ],
            "required" => ["tabID"],
            "discriminator" => [
                "propertyName" => "tabID",
            ],
            "oneOf" => array_map(function (AbstractTabSearchFormSchema $tabForm) {
                return $tabForm->schema();
            }, $tabForms),
        ];

        return Schema::parse($schema);
    }

    /**
     * Create a schema of the props for the component.
     *
     * @return Schema
     */
    public static function getPropsSchema(): Schema
    {
        return Schema::parse(["title:s?", "formSchema:s?"]);
    }

    /**
     * Get props for component
     *
     * @return array
     */
    public function getProps(): ?array
    {
        $props = [];
        $props["title"] = $this->title;
        $props["formSchema"] = $this->formSchema;

        $props = $this->getPropsSchema()->validate($props);

        return $props;
    }

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "SearchWidget";
    }

    /**
     * @return Schema
     */
    public static function getWidgetSchema(): Schema
    {
        $widgetSchema = Schema::parse([
            "title:s?" => [
                "x-control" => SchemaForm::textBox(new FormOptions("Title", "Set a custom title.")),
            ],
            "formSchema:s?" => [
                "x-control" => SchemaForm::codeBox(
                    new FormOptions("Form Schema", "Set the form schema."),
                    "application/json",
                    "https://json-schema.org/draft-07/schema"
                ),
            ],
        ]);

        return SchemaUtils::composeSchemas($widgetSchema);
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "Search Widget";
    }
}
