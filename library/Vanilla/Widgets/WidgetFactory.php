<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets;

use Garden\Schema\Schema;
use Vanilla\Contracts\Addons\WidgetInterface;
use Vanilla\Forms\SchemaForm;
use Vanilla\Web\TwigRenderTrait;

/**
 * Class for instantiating widgets.
 */
class WidgetFactory implements \JsonSerializable {

    use TwigRenderTrait;

    /** @var string */
    private $widgetClass;

    /**
     * Constructor.
     *
     * @param string $widgetClass
     */
    public function __construct(string $widgetClass) {
        $this->widgetClass = $widgetClass;
    }

    /**
     * @return array
     */
    public function getDefinition(): array {
        /** @var WidgetInterface $class */
        $class = $this->widgetClass;
        return [
            'widgetID' => $class::getWidgetID(),
            'name' => $class::getWidgetName(),
            'widgetClass' => $class::getWidgetClass(),
            'schema' => $class::getWidgetSchema(),
        ];
    }

    /**
     * @return Schema
     */
    public function getSchema(): Schema {
        /** @var WidgetInterface $class */
        $class = $this->widgetClass;
        return $class::getWidgetSchema();
    }

    /**
     * @return string
     */
    public function getName(): string {
        /** @var WidgetInterface $class */
        $class = $this->widgetClass;
        return $class::getWidgetName();
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return $this->getDefinition();
    }

    /**
     * Create an instance of the widget with the given parameters.
     *
     * @param array $parameters
     *
     * @return string
     */
    public function renderWidget(array $parameters): string {
        if (is_a($this->widgetClass, AbstractWidgetModule::class, true)) {
            // Use this until refactored.
            return \Gdn_Theme::module($this->widgetClass, $parameters);
        } else {
            throw new \Exception('Not implemented yet');
        }
    }

    /**
     * Render a widget summary.
     *
     * @param array $parameters
     * @return string
     */
    public function renderWidgetSummary(array $parameters): string {
        $schema = $this->getSchema();
        $widgetParameters = [];
        foreach ($schema->getSchemaArray()['properties'] as $fieldName => $property) {
            $form = $property['x-control'] ?? null;
            if (!$form) {
                continue;
            }

            $label = $form['label'];
            $actualValue = $parameters[$fieldName] ?? $form['default'] ?? '(Default)';
            $staticChoices = $form['choices']['staticOptions'] ?? null;
            if ($staticChoices !== null && isset($staticChoices[$actualValue])) {
                $actualValue = $staticChoices[$actualValue] ?? '(Unknown)';
            }
            $widgetParameters[] = [
                'name' => $label,
                'value' => $actualValue,
            ];
        }

        return $this->renderTwig("@library/Vanilla/Widgets/WidgetFactorySummary.twig", [
            'widgetName' => $this->getName(),
            'parameters' => $widgetParameters,
        ]);
    }
}
