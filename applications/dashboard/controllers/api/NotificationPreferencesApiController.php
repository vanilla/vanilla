<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controller\Api;

use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use UserModel;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Dashboard\Models\ActivityService;
use Vanilla\Dashboard\Models\UserNotificationPreferencesModel;
use Vanilla\Web\ApiFilterMiddleware;

/**
 * Handles the /notification-preferences endpoints.
 */
class NotificationPreferencesApiController extends \AbstractApiController
{
    /** @var ActivityService */
    private $activityTypeService;

    /** @var UserNotificationPreferencesModel */
    private $userPrefsModel;

    /** @var UserModel */
    private $userModel;

    private \LocaleModel $localeModel;

    private ConfigurationInterface $config;

    /**
     * D.I.
     *
     * @param ActivityService $activityTypeService
     * @param UserNotificationPreferencesModel $userPrefsModel
     * @param UserModel $userModel
     * @param \LocaleModel $localeModel
     * @param ConfigurationInterface $config
     */
    public function __construct(
        ActivityService $activityTypeService,
        UserNotificationPreferencesModel $userPrefsModel,
        UserModel $userModel,
        \LocaleModel $localeModel,
        ConfigurationInterface $config
    ) {
        $this->activityTypeService = $activityTypeService;
        $this->userPrefsModel = $userPrefsModel;
        $this->userModel = $userModel;
        $this->localeModel = $localeModel;
        $this->config = $config;
    }

    /**
     * Get the schema for notification preferences.
     *
     * @param array $query
     * @return Data
     */
    public function get_schema(array $query): Data
    {
        $this->permission("session.valid");

        $schemaType = $query["schemaType"] ?? "user";
        $userID = $query["userID"] ?? null;

        // Get the default preferences schema.
        if ($schemaType === "defaults") {
            $this->permission("site.manage");
            $schema = $this->activityTypeService->getPreferencesSchema(
                $this->userModel->getPermissions($this->getSession()->UserID),
                true
            );
        } else {
            if ($userID !== null) {
                $user = $this->userModel->getID($userID);
                if (!$user) {
                    throw new NotFoundException("User");
                }
            } else {
                $userID = $this->getSession()->UserID;
            }

            $permissions = $this->userModel->getPermissions($userID);

            $schema = $this->activityTypeService->getPreferencesSchema($permissions);
        }

        $response = new \Garden\Web\Data($schema, [ApiFilterMiddleware::FIELD_ALLOW => ["email"]]);
        $response = $response->withJsObjectFields(["properties"]);
        return $response;
    }

    /**
     * Get a user's notification preferences.
     *
     * @param int $userID
     * @return Data
     */
    public function get(int $userID): Data
    {
        if ($this->getSession()->UserID !== $userID) {
            $this->permission("Garden.Users.Edit");
        }

        $userPrefs = $this->userPrefsModel->getUserPrefs($userID);
        $output = $this->formatUserPreferences($userPrefs);
        return new Data($output, [ApiFilterMiddleware::FIELD_ALLOW => ["email"]]);
    }

    /**
     * Update a user's notification preferences.
     *
     * @param int $userID
     * @param array $body
     * @return Data
     * @throws \Garden\Web\Exception\ForbiddenException
     */
    public function patch(int $userID, array $body): Data
    {
        $normalizedBody = $this->normalizeForInput($body);
        if ($this->getSession()->UserID !== $userID) {
            $this->permission("Garden.Users.Edit");
            $user = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);
            if (!$user || $user["Deleted"] > 0) {
                throw new NotFoundException("User");
            }
            $userPermissions = $this->userModel->getPermissions($userID);

            // Ensure that the editing user has a higher ranking than the user being edited.
            $rankCompare = $this->getSession()
                ->getPermissions()
                ->compareRankTo($userPermissions);
            if ($rankCompare < 0) {
                throw new \Garden\Web\Exception\ForbiddenException(
                    t(\UsersApiController::ERROR_PATCH_HIGHER_PERMISSION_USER)
                );
            }
        }
        if (!empty($body["NotificationLanguage"])) {
            if (!$this->validateLocale($body["NotificationLanguage"])) {
                throw new NotFoundException("Selected language preference not found.");
            }
            $normalizedBody["NotificationLanguage"] = $body["NotificationLanguage"];
        }

        $updatedPrefs = $this->userPrefsModel->save($userID, $normalizedBody);
        $output = $this->formatUserPreferences($updatedPrefs);

        return new Data($output, [ApiFilterMiddleware::FIELD_ALLOW => ["email"]]);
    }

    /**
     * Get the default notification preference settings for a site.
     *
     * @return Data
     * @throws \Garden\Web\Exception\HttpException
     * @throws \Vanilla\Exception\PermissionException
     */
    public function get_defaults(): Data
    {
        $this->permission("site.manage");
        $defaults = $this->userPrefsModel->getDefaults();
        $defaults = $this->getAsBooleanValues($defaults);
        $output = $this->normalizeForOutput($defaults);
        return new Data($output, [ApiFilterMiddleware::FIELD_ALLOW => ["email"]]);
    }

    /**
     * Update the default notification preference settings for a site.
     *
     * @param array $body
     * @return Data
     */
    public function patch_defaults(array $body): Data
    {
        $this->permission("site.manage");
        $defaultsToSave = $this->normalizeForInput($body);
        $this->userPrefsModel->saveDefaults($defaultsToSave);
        $updatedDefaults = $this->get_defaults();
        return new Data($updatedDefaults, [ApiFilterMiddleware::FIELD_ALLOW => ["email"]]);
    }

    /**
     * Format the user preference data for output
     *
     * @param array $userPreferences
     * @return array
     */
    private function formatUserPreferences(array $userPreferences): array
    {
        $userLanguagePreference = $userPreferences[UserNotificationPreferencesModel::PREFERENCE_USER_LANGUAGE] ?? null;
        unset($userPreferences[UserNotificationPreferencesModel::PREFERENCE_USER_LANGUAGE]);
        $output = $this->getAsBooleanValues($userPreferences);
        $output = $this->normalizeForOutput($output);
        if (!empty($userLanguagePreference)) {
            $output[UserNotificationPreferencesModel::PREFERENCE_USER_LANGUAGE] = $userLanguagePreference;
        }
        return $output;
    }

    /**
     * Normalize a set of preferences for output via the api.
     *
     * @param array $prefs
     * @return array
     */
    private function normalizeForOutput(array $prefs): array
    {
        $normalized = [];
        foreach ($prefs as $key => $val) {
            $splitKey = explode(".", $key);
            $normalized[$splitKey[1]][strtolower($splitKey[0])] = $val;
        }

        return $normalized;
    }

    /**
     * Normalize a set of preferences for saving to the db.
     *
     * @param array $prefs
     * @return array
     */
    private function normalizeForInput(array $prefs): array
    {
        $normalized = [];
        foreach ($prefs as $activityType => $notificationTypes) {
            if (!is_array($notificationTypes)) {
                continue;
            }
            foreach ($notificationTypes as $type => $val) {
                $normalized[ucfirst($type) . "." . $activityType] = $val;
            }
        }

        return $normalized;
    }

    /**
     * Get an array of preferences with integer values.
     *
     * @param array $prefs
     * @return array
     */
    private function getAsIntegerValues(array $prefs): array
    {
        $prefsAsInts = array_map(function ($pref) {
            return is_bool($pref) ? intval($pref) : $pref;
        }, $prefs);
        return $prefsAsInts;
    }

    /**
     * Get an array of preferences with boolean values.
     *
     * @param array $prefs
     * @return array
     */
    private function getAsBooleanValues(array $prefs): array
    {
        $prefsAsBools = array_map(function ($pref) {
            return is_numeric($pref) ? (bool) $pref : $pref;
        }, $prefs);
        return $prefsAsBools;
    }

    /**
     * Check if the selected locale is an enabled valid locale
     *
     * @param string $selectedLocale
     * @return bool
     */
    public function validateLocale(string $selectedLocale): bool
    {
        return $this->localeModel->isEnabled($selectedLocale);
    }

    /*
     * Get default enabled locale
     */
    private function getDefaultLocale(): string
    {
        return $this->config->get("Garden.Locale", "en");
    }
}
