<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Middleware;

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\DataResolverInterface;
use Garden\Hydrate\Middleware\AbstractMiddleware;
use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Layout\Resolvers\ReactResolver;

/**
 * Middleware for controlling the visibility of a hydrated react node.
 */
class VisibilityMiddleware extends AbstractMiddleware
{
    /**
     * We can't actually determine our device on the server (we don't know it's size) so pass it through to the frontend.
     * @inheritdoc
     */
    protected function processInternal(
        array $nodeData,
        array $middlewareParams,
        array $hydrateParams,
        DataResolverInterface $next
    ) {
        $hydrated = $next->resolve($nodeData, $hydrateParams);

        if (ReactResolver::isReactNode($hydrated)) {
            // Pass through our middleware params to the frontend.
            $hydrated[DataHydrator::KEY_MIDDLEWARE][$this->getType()] = $middlewareParams;
        }

        return $hydrated;
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return "visibility";
    }

    /**
     * @inheritdoc
     */
    public function getSchema(): ?Schema
    {
        return Schema::parse([
            "device:s?" => [
                "description" => "Choose which type of devices to render the widget on",
                "default" => "all",
                "enum" => ["all", "desktop", "mobile"],
                "enumDescriptions" => [
                    "Render on all devices.",
                    "Render only on desktop devices.",
                    "Render only on mobile devices.",
                ],
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Visibility", "Configure on which devices this content will be visible."),
                    new StaticFormChoices([
                        "all" => t("Desktop & Mobile"),
                        "desktop" => t("Desktop"),
                        "mobile" => t("Mobile"),
                    ])
                ),
            ],
        ]);
    }
}
