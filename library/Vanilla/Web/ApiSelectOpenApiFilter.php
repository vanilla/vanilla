<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

/**
 * OpenAPI filter to apply the {@link ApiSelectMiddleware} docs to the OpenAPI spec.
 */
class ApiSelectOpenApiFilter
{
    public function __invoke(array &$openApi): void
    {
        foreach ($openApi["paths"] as $path => &$pathData) {
            foreach ($pathData as $method => &$methodData) {
                if ($method !== "get") {
                    continue;
                }

                $methodData["parameters"][] = [
                    "name" => "fields",
                    "in" => "query",
                    "style" => "form",
                    "description" =>
                        "Only return fields with these keys from the output. Use dot notation for nested fields.",
                    "schema" => [
                        "type" => "array",
                        "items" => [
                            "type" => "string",
                        ],
                    ],
                ];
                $methodData["parameters"] = array_values($methodData["parameters"]);

                // Notably omitting the field mapping because it is too complicated for the OpenAPI spec. We don't have a good input for this type of field yet.
            }
        }
    }
}
