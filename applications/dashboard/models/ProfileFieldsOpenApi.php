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
        $schema = $this->profileFieldModel->getUserProfileFieldSchema();
        $properties = $schema->jsonSerialize()["properties"] ?? [];

        // Add the profile fields schema to openapi.
        ArrayUtils::setByPath("components.schemas.UserProfileFields.properties", $openApi, $properties);

        ArrayUtils::setByPath(
            "components.parameters.ExtendedQuerySchema.schema",
            $openApi,
            $this->profileFieldModel->getProfileFieldQuerySchema()->jsonSerialize() ?? []
        );
    }
}
