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
class WidgetFactory implements \JsonSerializable
{
    use TwigRenderTrait;

    /** @var string */
    private $widgetClass;

    /**
     * Constructor.
     *
     * @param string $widgetClass
     */
    public function __construct(string $widgetClass)
    {
        $this->widgetClass = $widgetClass;
    }

    /**
     * @return array
     */
    public function getDefinition(): array
    {
        /** @var WidgetInterface $class */
        $class = $this->widgetClass;
        return [
            "widgetID" => $class::getWidgetID(),
            "name" => $class::getWidgetName(),
            "widgetClass" => $class,
            "schema" => $class::getWidgetSchema(),
        ];
    }

    /**
     * @return Schema
     */
    public function getSchema(): Schema
    {
        /** @var WidgetInterface $class */
        $class = $this->widgetClass;
        return $class::getWidgetSchema();
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        /** @var WidgetInterface $class */
        $class = $this->widgetClass;
        return $class::getWidgetName();
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return $this->getDefinition();
    }

    /**
     * Create an instance of the widget with the given parameters.
     *
     * @param array $parameters
     *
     * @return string
     */
    public function renderWidget(array $parameters): string
    {
        if (is_a($this->widgetClass, AbstractWidgetModule::class, true)) {
            // Use this until refactored.
            return \Gdn_Theme::module($this->widgetClass, $parameters);
        } else {
            throw new \Exception("Not implemented yet");
        }
    }

    /**
     * Render a widget summary.
     *
     * @param array $parameters
     * @return string
     */
    public function renderWidgetSummary(array $parameters): string
    {
        return $this->renderTwig("@library/Vanilla/Widgets/WidgetFactorySummary.twig", [
            "widgetName" => $this->getName(),
            "parameters" => $this->getWidgetSummaryParameters($parameters),
        ]);
    }

    /**
     * Get the parameters for a widget summary.
     *
     * @param array $parameters
     * @return array
     */
    public function getWidgetSummaryParameters(array $parameters): array
    {
        $schema = $this->getSchema();
        $widgetParameters = $this->getWidgetPropertiesInternal($schema, $parameters);
        return $widgetParameters;
    }

    /**
     * Get the properties to display in the schema.
     *
     * @param Schema|array $schema
     * @param array $parameters
     * @return array
     */
    private function getWidgetPropertiesInternal($schema, array $parameters = []): array
    {
        $widgetParameters = [];
        $schemaArray = is_array($schema) ? $schema : $schema->getSchemaArray();
        if (!isset($schemaArray["properties"])) {
            return [];
        }
        foreach ($schemaArray["properties"] as $fieldName => $property) {
            $type = $property["type"] ?? null;
            $control = $property["x-control"] ?? null;
            $label = $control["label"] ?? null;
            if (!$control || !$type || !$label) {
                continue;
            }

            if ($type === "object") {
                $widgetParameters[] = [
                    "name" => $label,
                    "value" => $this->getWidgetPropertiesInternal($property, $parameters[$fieldName] ?? []),
                ];
            } elseif ($type === "array") {
                $actualValue = $parameters[$fieldName] ?? ($control["default"] ?? []);

                $formattedValue = "";
                if (!is_array($actualValue)) {
                    $formattedValue = "(Can't Format)";
                } elseif (empty($actualValue)) {
                    $formattedValue = "(Default)";
                } else {
                    $firstItem = $actualValue[0];
                    $key = array_keys($firstItem[0]);
                    if (array_key_exists("label", $firstItem)) {
                        $key = "label";
                    } elseif (array_key_exists("value", $firstItem)) {
                        $key = "value";
                    }

                    $formattedValue = implode(", ", array_column($actualValue, $key));
                }

                $widgetParameters[] = [
                    "name" => $label,
                    "value" => $formattedValue,
                ];
            } else {
                $actualValue = $parameters[$fieldName] ?? ($control["default"] ?? "(Default)");
                $staticChoices = $control["choices"]["staticOptions"] ?? null;
                if ($staticChoices !== null && isset($staticChoices[$actualValue])) {
                    $actualValue = $staticChoices[$actualValue] ?? "(Unknown)";
                }
                $widgetParameters[] = [
                    "name" => $label,
                    "value" => $actualValue,
                ];
            }
        }
        return $widgetParameters;
    }
}
