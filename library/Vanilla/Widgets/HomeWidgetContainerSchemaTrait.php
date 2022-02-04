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
                'x-control' => SchemaForm::textBox(
                    new FormOptions('Description', 'Set a custom description.', $placeholder ?? ''),
                    'textarea'
                )
            ],
        ]);
    }

    /**
     * @return Schema
     */
    public static function widgetSubtitleSchema(): Schema {
        return Schema::parse([
            'subtitleContent:s?' => [
                'x-control' => SchemaForm::textBox(new FormOptions('Subtitle', 'Set a custom subtitle.'))
            ],
        ]);
    }
}
