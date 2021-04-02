<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Community;

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\AbstractReactModule;

/**
 * Widget to search by tag.
 */
class SearchWidgetModule extends AbstractReactModule {
    /** @var string|null */
    public $title = null;

    /** @var string|null */
    public $formSchema = null;

    /**
     * Return the module's title
     *
     * @return string|null
     */
    protected function getTitle(): ?string {
        return $this->title;
    }

    /**
     * Return the module's formSchema
     *
     * @return string|null
     */
    protected function getFormSchema(): ?string {
        return $this->formSchema;
    }

    /**
     * Create a schema of the props for the component.
     *
     * @return Schema
     */
    public static function getPropsSchema(): Schema {
        return Schema::parse([
            'title:s?',
            'formSchema:s?',
        ]);
    }

    /**
     * Get props for component
     *
     * @return array
     */
    public function getProps(): ?array {
        $props = [];
        $props['title'] = $this->getTitle();
        $props['formSchema'] = $this->getFormSchema();

        $props = $this->getPropsSchema()->validate($props);

        return $props;
    }

    /**
     * @inheritdoc
     */
    public function getComponentName(): string {
        return 'SearchWidget';
    }

    /**
     * @return Schema
     */
    public static function getWidgetSchema(): Schema {
        $widgetSchema = Schema::parse([
            'title:s?' => [
                'x-control' => SchemaForm::textBox(new FormOptions('Title', 'Set a custom title.'))
            ],
            'formSchema:s?' => [
                'x-control' => SchemaForm::codeBox(
                    new FormOptions('Form Schema', 'Set the form schema.'),
                    'application/json',
                    'https://json-schema.org/draft-07/schema'
                )
            ],
        ]);

        return SchemaUtils::composeSchemas($widgetSchema);
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string {
        return "Search Widget";
    }
}
