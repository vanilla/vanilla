<?php

/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models;

use Vanilla\Forum\Search\DiscussionSearchType;
use Vanilla\Utility\ArrayUtils;

class PostFieldsOpenApi
{
    public function __invoke(array &$openApi): void
    {
        // Add the post field schema for filtering discussions to openapi.
        $schema = PostFieldModel::getPostMetaFilterSchema();
        $properties = $schema->jsonSerialize()["properties"] ?? [];
        ArrayUtils::setByPath(
            "components.parameters.PostFieldFilters.schema.properties.postMeta.properties",
            $openApi,
            $properties
        );

        // Add the post field schema for filtering discussion search to openapi.
        $schema = DiscussionSearchType::getPostMetaFilterSchema();
        $properties = $schema->jsonSerialize()["properties"] ?? [];
        ArrayUtils::setByPath("components.parameters.PostMetaSearchFilters.schema.properties", $openApi, $properties);
    }
}
