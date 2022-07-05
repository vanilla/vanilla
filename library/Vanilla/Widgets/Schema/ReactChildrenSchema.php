<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\Schema;

use Garden\Hydrate\Schema\HydrateableSchema;
use Garden\Schema\Schema;

/**
 * Schema for react component props.
 */
class ReactChildrenSchema extends Schema
{
    /**
     * Constructor.
     *
     * @param string|null $description Set a custom description.
     */
    public function __construct(string $description = null)
    {
        $childSchema = (new ReactSingleChildSchema())->getSchemaArray();
        parent::__construct([
            "description" => $description ?? "Render a list of react components.",
            HydrateableSchema::X_NO_HYDRATE => true,
            HydrateableSchema::X_FORCE_HYDRATE_ITEMS => true,
            "type" => "array",
            "items" => $childSchema,
        ]);
        $this->addFilter("", function ($field) {
            // Some children might have become null after a middleware.
            $result = array_values(array_filter($field));
            return $result;
        });
    }
}
