<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Garden\Schema\Schema;
use Gdn;
use UserModel;
use Vanilla\Dashboard\Activity\Activity;
use Vanilla\Dashboard\Activity\ActivityGroup;
use Vanilla\Permissions;

class ActivityService
{
    /** @var array<class-string<Activity>> */
    private $activities = [];

    /** @var array<string> */
    private $activityTypeIDs = [];

    public function __construct(UserModel $userModel)
    {
        $this->userModel = $userModel;
    }

    /**
     * Register an activity.
     *
     * @param class-string<Activity> $activity
     * @return void
     */
    public function registerActivity(string $activity): void
    {
        $this->activities[] = $activity;
        $this->activityTypeIDs[] = $activity::getActivityTypeID();
    }

    /**
     * Get all the available activities.
     *
     * @return array<class-string<Activity>>
     */
    public function getActivities(): array
    {
        return $this->activities;
    }

    /**
     * Get an array of all available activity type IDs.
     *
     * @return string[]
     */
    public function getActivityTypeIDs(): array
    {
        return $this->activityTypeIDs;
    }

    /**
     * Get an array of all available activity type IDs.
     * @param string $typeID Activity Type value.
     *
     * @return class-string<Activity>|null
     */
    public function getActivityByTypeID(string $typeID): ?string
    {
        $index = array_search($typeID, $this->activityTypeIDs);
        return $index !== false ? $this->activities[$index] : null;
    }

    /**
     * Get all activity preferences.
     *
     * @return array
     */
    public function getAllPreferences(): array
    {
        $preferences = [];
        /** @var Activity $activity */
        foreach ($this->activities as $activity) {
            $preferences[] = $activity::getPreference();
        }

        return $preferences;
    }

    /**
     * Get the schema for activity notification preferences.
     * Each activity is grouped under its corresponding activity group.
     *
     * @param bool $hasEmailViewPermission Whether the user associated with the schema has the email.view permission.
     * @param bool $defaults Whether to get the schema for the site-wide defaults. If false, get the schema for a user.
     * @return Schema
     */
    public function getPreferencesSchema(Permissions $permissions, bool $defaults = false): Schema
    {
        $activities = $this->getActivities();

        $schema = new Schema([
            "type" => "object",
            "properties" => [],
        ]);

        /**
         * @param class-string<ActivityGroup> $group
         * @param string $currentPath
         * @return string
         */
        $applyTypeGroup = function (string $group, string $currentPath) use (&$schema, &$applyTypeGroup): string {
            $parentGroup = $group::getParentGroupClass();
            if ($parentGroup !== null) {
                $currentPath = $applyTypeGroup($parentGroup, $currentPath);
            }

            $groupName = $group::getActivityGroupID();
            $currentPath = ltrim("{$currentPath}.properties.{$groupName}", ".");

            if (!empty($schema->getField($currentPath))) {
                return $currentPath;
            }
            $schema->setField($currentPath, [
                "x-control" => [
                    "label" => $group::getPreferenceLabel(),
                    "description" => $group::getPreferenceDescription(),
                ],
                "type" => "object",
                "properties" => [],
            ]);

            return $currentPath;
        };

        foreach ($activities as $activity) {
            /** @var <class-string<Activity>> $activity */

            if (Gdn::config("Garden.Preferences.Disabled.{$activity::getPreference()}")) {
                continue;
            }

            $requiredConfigsSet = $this->requiredConfigsSet($activity);

            if (!$requiredConfigsSet) {
                continue;
            }

            $hasPermission = $this->hasPermission($activity, $permissions);

            if (!$hasPermission) {
                continue;
            }

            $pathRoot = $applyTypeGroup($activity::getGroupClass(), "");
            $typeName = $activity::getPreference();
            $hasEmailViewPermission = $permissions->has("Garden.Email.View");
            $properties = $this->getFilteredSchemaProperties($activity, $hasEmailViewPermission, $defaults);
            if (!empty($properties)) {
                $schema->setField("{$pathRoot}.properties.{$typeName}", [
                    "x-control" => [
                        "description" => t($activity::getPreferenceDescription()),
                    ],
                    "type" => "object",
                    "properties" => $properties,
                ]);
            }
        }
        return $schema;
    }

    /**
     * Get the activity's schema properties filtered according to the passed email permission and
     * site-wide config settings.
     *
     * @param class-string<Activity> $activity
     * @param bool $hasEmailViewPermission
     * @param bool $default
     * @return array
     */
    private function getFilteredSchemaProperties(
        string $activity,
        bool $hasEmailViewPermission,
        bool $default = false
    ): array {
        $activityTypeID = $activity::getActivityTypeID();

        $properties = $activity::getPreferenceSchemaProperties();

        if ($default) {
            return $properties;
        } else {
            unset($properties["disabled"]);
        }

        $emailDisabled = Gdn::config("Garden.Email.Disabled");

        $notificationPreferenceDisabled = Gdn::config("Garden.Preferences.Disabled.{$activityTypeID}");

        if (!$hasEmailViewPermission || $emailDisabled || $notificationPreferenceDisabled) {
            unset($properties["email"]);
        }

        return $properties;
    }

    /**
     * Whether the configs required for the notification are set.
     *
     * @param $activity
     * @return bool
     */
    private function requiredConfigsSet($activity): bool
    {
        $requiredConfigs = $activity::getNotificationRequiredSettings();
        $requiredConfigsSet = true;
        if (!empty($requiredConfigs)) {
            foreach ($requiredConfigs as $config) {
                if (is_array($config)) {
                    $requiredConfigsSet = Gdn::config(array_key_first($config)) === $config[array_key_first($config)];
                } else {
                    $requiredConfigsSet = Gdn::config($config);
                }
                if ($requiredConfigsSet === false) {
                    break;
                }
            }
        }
        return $requiredConfigsSet;
    }

    /**
     * @param $activity
     * @param Permissions $permissions
     * @return bool|mixed
     */
    private function hasPermission($activity, Permissions $permissions)
    {
        $prefPermissions = $activity::getNotificationPermissions();
        $hasPermission = empty($prefPermissions) || $permissions->hasAll($prefPermissions);

        return $hasPermission;
    }
}
