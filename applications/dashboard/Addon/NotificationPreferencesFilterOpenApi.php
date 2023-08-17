<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Addon;

use Vanilla\Dashboard\Activity\Activity;
use Vanilla\Dashboard\Models\ActivityService;
use Vanilla\Utility\ArrayUtils;

/**
 * Class adding notification preference data to the notification preference endpoint schemas.
 */
class NotificationPreferencesFilterOpenApi
{
    /** @var ActivityService */
    private $activityService;

    /**
     * D.I.
     *
     * @param ActivityService $activityService
     */
    public function __construct(ActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    /**
     * Augment the openapi with activity notification preferences.
     *
     * @param array $openApi
     * @return void
     */
    public function __invoke(array &$openApi): void
    {
        $prefSchemaProps = [];
        /** @var class-string<Activity> $activity */
        foreach ($this->activityService->getActivities() as $activity) {
            $openApiSchemaProperties = [];
            $properties = $activity::getPreferenceSchemaProperties();
            foreach ($properties as $key => $value) {
                $openApiSchemaProperties[$key]["type"] = $value["type"];
                if (isset($value["default"])) {
                    $openApiSchemaProperties[$key]["default"] = $value["default"];
                }
            }
            $prefSchemaProps[$activity::getActivityTypeID()] = [
                "type" => "object",
                "properties" => $openApiSchemaProperties,
            ];
        }

        ArrayUtils::setByPath("components.schemas.NotificationPreferences.properties", $openApi, $prefSchemaProps);
    }
}
