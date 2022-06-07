<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\Schema;

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;

/**
 * Schema for a theme background.
 */
class WidgetBackgroundSchema extends Schema {

    /**
     * Constructor.
     *
     * @param string|null $description Set a custom description.
     */
    public function __construct(string $description = null) {
        parent::__construct([
            'type' => 'object',
            'description' => $description ?? 'A set of options for describing a background',
            'properties' => [
                'color' => [
                    'type' => 'string',
                    'description' => 'Set the background color of the component.',
                    'x-control' => SchemaForm::color(new FormOptions('Background color', 'Pick a background color.'))
                ],
                'image' => [
                    'type' => 'string',
                    'description' => "Set a background image or linear-gradient for the component.Note: "
                        ."Some special parsing may occur on this value.\n- URL's beginning with a \"~\""
                        ."character will be relative to the current theme directory. For example if the current "
                        ."file-based them is in /addons/themes/my-theme, ~/design/my-image.png would be equivalent"
                        ." to /addons/themes/my-theme/design/my-image.png\n- "
                        ."Please pass _URLs_ for this variable. __Do not__ pass urls wrapped in \"url()\".",
                    'x-control' => SchemaForm::textBox(new FormOptions('Image URL', 'Custom URL for the background image.'))
                ],
                'attachment' => [
                    'type' => 'string',
                    'description' => 'Set the background attachment of the component.'
                                            .'[MDN Reference](https://developer.mozilla.org/en-US/docs/Web/CSS/background-attachment)',
                    'enum' => ["fixed", "local", "scroll"],
                ],
                'position' => [
                    'type' => 'string',
                    'description' => 'Set the background position of the component.'
                                            .'[MDN Reference](https://developer.mozilla.org/en-US/docs/Web/CSS/background-position)'
                ],
                'size' => [
                    'type' => 'string',
                    'description' => 'Set the background size of the component.'
                                            .'[MDN Reference](https://developer.mozilla.org/en-US/docs/Web/CSS/background-size)'
                ],
                'repeat' => [
                    'type' => 'string',
                    'description' => 'Define how the background repeats. '
                        .'[MDN Reference](https://developer.mozilla.org/en-US/docs/Web/CSS/background-repeat)'
                ],
            ]
        ]);
    }
}
