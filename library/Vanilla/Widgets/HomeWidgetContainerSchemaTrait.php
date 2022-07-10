<?php
/**
 * @author David Barbier <david.barbier@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Widgets\Schema\WidgetBackgroundSchema;

/**
 * Abstraction layer for the module displaying Categories.
 */
trait HomeWidgetContainerSchemaTrait {
    /**
     * Get the schema for the widget title.
     *
     * @param ?string $placeholder
     *
     * @return Schema
     */
    public static function widgetTitleSchema(string $placeholder = null): Schema {
        return Schema::parse([
            'title:s?' => [
                'type' => 'string',
                'description' => 'Title of the widget',
                'x-control' => SchemaForm::textBox(new FormOptions('Title', 'Set a custom title.', $placeholder ?? ''))
            ],
        ]);
    }

    /**
     * Get the schema for the widget title.
     *
     * @param ?string $placeholder
     *
     * @return Schema
     */
    public static function widgetDescriptionSchema(string $placeholder = null): Schema {
        return Schema::parse([
            'description:s?' => [
                'type' => 'string',
                'description' => 'Description of the widget.',
                'x-control' => SchemaForm::textBox(
                    new FormOptions('Description', 'Set a custom description.', $placeholder ?? ''),
                    'textarea'
                )
            ],
        ]);
    }

    /**
     * Get schema for a widget subtitle.
     *
     * @param string $fieldName The name of the field.
     *
     * @return Schema
     */
    public static function widgetSubtitleSchema(string $fieldName = 'subtitleContent'): Schema {
        return Schema::parse([
            "${fieldName}:s?" => [
                'type' => 'string',
                'description' => 'Subtitle of the widget.',
                'x-control' => SchemaForm::textBox(new FormOptions('Subtitle', 'Set a custom subtitle.'))
            ],
        ]);
    }

    /**
     * Get the schema for container options.
     *
     * @param string $fieldName
     * @return Schema
     */
    public static function containerOptionsSchema(string $fieldName = 'options', array $allowedProperties = null): Schema {
        $propertiesSchema = Schema::parse([
            'outerBackground?' => new WidgetBackgroundSchema('Set a full width background for the container.'),
            'innerBackground?' => new WidgetBackgroundSchema('Set an inner background (inside of the margins) for the container.'),
            'borderType:s?' => [
                'enum' => self::borderTypeOptions(),
                'description' => 'Describe what type of the border the widget should have.'
            ],
            'viewAll?' => self::viewAllSchema('Configure a view all link for the widget.'),
            'maxColumnCount:i?' => [
                'description' => 'Set the maximum number of columns for the widget.',
            ],
            'displayType:s?' => [
                'enum' => self::displayTypeOptions(),
                'description' => 'Describe the widget display format.'
            ],
            'isGrid:b?' => [
                'deprecationMessage' => 'This is deprecated. Use displayType instead.',
                'description' => 'Configure if the widget should display as a grid. Defaults to false.',
            ],
            'isCarousel:b?' => [
                'deprecationMessage' => 'This is deprecated. Use displayType instead.',
                'description' => 'Configure if the widget should display in a carousel. Defaults to false.',
            ],
            'headerAlignment:s?' => [
                'description' => 'Configure alignment of the title, subtitle, and description.',
                'enum' => ['left', 'center'],
            ],
        ]);

        if ($allowedProperties) {
            $propertiesSchema = Schema::parse($allowedProperties)->add($propertiesSchema);
        }

        return Schema::parse([
            "$fieldName?" => $propertiesSchema->setDescription('Configure various container options'),
        ]);
    }

    /**
     * Get the schema for a viewAll action.
     *
     * @param string|null $description
     * @return Schema
     */
    public static function viewAllSchema(string $description = null): Schema {
        $schema = Schema::parse([
            'position:s?' => [
                'enum' => ['top', 'bottom'],
                'description' => 'Where to render the viewAll link. Default is "bottom" or defined by the theme.'
            ],
            'to:s' => [
                'description' => 'The URL of the view all link.',
            ],
            'name:s?' => [
                'description' => 'A custom name for the view all link. Default is "View All" or defined by the theme.',
            ],
        ]);
        if ($description) {
            $schema->setField('description', $description);
        }
        return $schema;
    }

    /**
     * Get an array of the border type options.
     *
     * @return string[]
     */
    public static function borderTypeOptions(): array {
        return [
            'border',
            'separator',
            'none',
            'shadow',
        ];
    }

    /**
     * Get an array of the display type options.
     *
     * @return string[]
     */
    public static function displayTypeOptions(): array {
        return [
            'grid',
            'list',
            'carousel',
            'link'
        ];
    }
}
