<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Widgets;

use Garden\Schema\Schema;
use Vanilla\Widgets\Schema\WidgetBackgroundSchema;

/**
 * Abstraction layer to generate schemas for widgets.
 */
trait WidgetSchemaTrait {

    /**
     * Get the schema for widget item options.
     *
     * @param string $fieldName
     * @return Schema
     */
    public static function itemOptionsSchema(string $fieldName = 'itemOptions'): Schema {
        return Schema::parse([
            "$fieldName?" => [
                'description' => 'Configure various widget item options',
                'type' => 'object',
                'properties' => [
                    'imagePlacement:s?' => [
                        'enum' => ['left', 'top'],
                        'description' => 'Describe where image will be placed on widget item.',
                    ],
                    'imagePlacementMobile:s?' => [
                        'enum' => ['left', 'top'],
                        'description' => 'Describe where image will be placed on widget item on mobile.',
                    ],
                    'box?' => [
                        'borderType:s?' => [
                            'enum' => self::borderTypeOptions(),
                            'description' => 'Describe what type of the border the widget item should have.',
                        ],
                        'border?' => self::borderSchema('Configure border style.'),
                        'background?' => new WidgetBackgroundSchema('Background options for the widget item.'),
                        'spacing?' => self::spacingSchema('Configure box internal spacing.'),
                    ],
                    'contentType:s?' => [
                        'enum' => self::contentTypeOptions(),
                        'description' => 'Describe the widget item display style.',
                    ],
                    'fg?' => [
                        'type' => 'string',
                        'description' => 'Widget item foreground color.',
                    ],
                    'display:?' => Schema::parse([
                        'name?' => [
                            'type' => 'boolean',
                            'description' => 'Whether to show widget item name.',
                        ],
                        'description?' => [
                            'type' => 'boolean',
                            'description' => 'Whether to show widget item description.',
                        ],
                        'counts?' => [
                            'type' => 'boolean',
                            'description' => 'Whether to show widget item counts.',
                        ],
                        'cta?' => [
                            'type' => 'boolean',
                            'description' => 'Whether to show widget item CTA.',
                        ],
                    ]),
                    'alignment?' => [
                        'enum' => ['center', 'left'],
                        'description' => 'Widget item foreground color.',
                    ],
                    'viewMore?' => Schema::parse([
                        'labelCode?' => [
                            'type' => 'string',
                            'description' => 'Button text/label.',
                        ],
                        'buttonType:s?' => [
                            'enum' => self::buttonTypeOptions(),
                            'description' => 'Button options.',
                        ],
                    ]),
                ],
            ],
        ]);
    }

    /**
     * Get the schema for a border style.
     *
     * @param string|null $description
     * @return Schema
     */
    public static function borderSchema(string $description = null): Schema {
        $schema = Schema::parse([
            'color?' => [
                'type' => 'string',
                'description' => 'Border color.',
            ],
            'width?' => [
                'type' => ['number', 'string'],
                'description' => 'Border width.',
            ],
            'style?' => [
                'type' => 'string',
                'description' => 'Border style.',
            ],
            'radius?' => [
                'type' => ['number', 'string'],
                'description' => 'Border radius.',
            ],
        ]);
        if ($description) {
            $schema->setField('description', $description);
        }

        return $schema;
    }

    /**
     * Get the schema for spacing.
     *
     * @return Schema
     */
    public static function spacingSchema(): Schema {
        return Schema::parse([
            'top?' => [
                'type' => ['string', 'number'],
                'description' => 'Top spacing.',
            ],
            'bottom?' => [
                'type' => ['string', 'number'],
                'description' => 'Bottom spacing.',
            ],
            'left?' => [
                'type' => ['string', 'number'],
                'description' => 'Left spacing.',
            ],
            'right?' => [
                'type' => ['string', 'number'],
                'description' => 'Right spacing.',
            ],
            'horizontal?' => [
                'type' => ['string', 'number'],
                'description' => 'Horizontal spacing (left and right).',
            ],
            'vertical?' => [
                'type' => ['string', 'number'],
                'description' => 'Vertical spacing (top and bottom).',
            ],
            'all?' => [
                'type' => ['string', 'number'],
                'description' => 'All spacing (top, right, bottom, left).',
            ],
        ]);
    }

    /**
     * Get an array of the button type options.
     *
     * @return string[]
     */
    public static function buttonTypeOptions(): array {
        return [
            "standard",
            "primary",
            "transparent",
            "translucid",
            "text",
            "custom",
        ];
    }

    /**
     * Get an array of the display type options.
     *
     * @return string[]
     */
    public static function contentTypeOptions(): array {
        return [
            "title-description-icon",
            "title-description-image",
            "title-background",
            "title-description",
        ];
    }
}
