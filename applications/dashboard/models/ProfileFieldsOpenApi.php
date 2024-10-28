<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Vanilla\Utility\ArrayUtils;

class ProfileFieldsOpenApi
{
    /** @var ProfileFieldModel */
    private $profileFieldModel;

    public function __construct(ProfileFieldModel $profileFieldModel)
    {
        $this->profileFieldModel = $profileFieldModel;
    }

    public function __invoke(array &$openApi): void
    {
        // Add the profile field schema for updating users' profile fields to openapi.
        $schema = $this->profileFieldModel->getUserProfileFieldSchema();
        $properties = $schema->jsonSerialize()["properties"] ?? [];
        ArrayUtils::setByPath("components.schemas.UserProfileFields", $openApi, [
            "type" => "object",
            "properties" => $properties,
        ]);

        // Add the profile field schema for filtering users to openapi.
        $schema = $this->profileFieldModel->getProfileFieldFilterSchema();
        $properties = $schema->jsonSerialize()["properties"] ?? [];
        ArrayUtils::setByPath(
            "components.parameters.ProfileFieldFilters.schema.properties.profileFields.properties",
            $openApi,
            $properties
        );
    }
}
