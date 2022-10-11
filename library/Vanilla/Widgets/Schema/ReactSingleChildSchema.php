<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\Schema;

use Garden\Hydrate\DataHydrator;
use Garden\Hydrate\Schema\HydrateableSchema;
use Garden\Schema\Schema;
use Vanilla\Layout\Resolvers\ReactResolver;

/**
 * Schema for react component props.
 */
class ReactSingleChildSchema extends Schema
{
    /**
     * Constructor.
     *
     * @param string|null $description Set a custom description.
     * @param string $hydrateGroup
     */
    public function __construct(string $description = null, string $hydrateGroup = ReactResolver::HYDRATE_GROUP_REACT)
    {
        parent::__construct([
            "type" => "object",
            "description" => $description ?? "Render a specific react component with a set of props.",
            HydrateableSchema::X_HYDRATE_GROUP => $hydrateGroup,
            "properties" => [
                // Optional middleware that can get passed through the client.
                // If you want this to get through, your middleware has to explicitly set the middleware property.
                DataHydrator::KEY_MIDDLEWARE => [
                    "type" => "object",
                    "additionalProperties" => true,
                ],
                '$reactComponent' => [
                    HydrateableSchema::X_NO_HYDRATE => true,
                    "type" => "string",
                    "description" => "The name of a registered react component.",
                ],
                '$reactProps' => [
                    "type" => "object",
                    "description" => "Props to render the component with.",
                ],
            ],
            "advertisement" => ['$reactComponent', '$reactProps'],
            "required" => ['$reactComponent', '$reactProps'],
        ]);
    }
}
