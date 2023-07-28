<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Vanilla\Logger;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerMultiAction;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerQuantityTotal;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Web\SystemCallableInterface;
use Vanilla\Web\TwigRenderTrait;

/**
 * Class for processing digest data for users.
 */

class EmailDigestGenerator implements SystemCallableInterface
{
    use TwigRenderTrait;

    const DATA_PROCESSING_CHUNK_SIZE = 1000;

    private \CategoryModel $categoryModel;

    private \UserModel $userModel;

    private UserDigestModel $userDigestModel;

    private \PermissionModel $permissionModel;

    private LongRunner $longRunner;

    private \Gdn_Configuration $config;

    private \DiscussionsApiController $discussionApiController;

    private Logger $logger;

    public function __construct(
        \CategoryModel $categoryModel,
        UserDigestModel $userDigestModel,
        \UserModel $userModel,
        \DiscussionsApiController $discussionApiController,
        LongRunner $longRunner,
        \Gdn_Configuration $config,
        \PermissionModel $permissionModel,
        Logger $logger
    ) {
        $this->categoryModel = $categoryModel;
        $this->userDigestModel = $userDigestModel;
        $this->userModel = $userModel;
        $this->permissionModel = $permissionModel;
        $this->discussionApiController = $discussionApiController;
        $this->longRunner = $longRunner;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["collectFollowingCategoryDigestIterator", "collectDefaultDigestIterator"];
    }

    /**
     * Get weekly digest data for users of the site
     *
     * @return void
     */
    public function generateDigestData()
    {
        $context = [
            Logger::FIELD_CHANNEL => Logger::CHANNEL_SYSTEM,
            Logger::FIELD_TAGS => ["digest-content-generator"],
        ];
        if (!$this->isEmailDigestEnabledForSite()) {
            $this->logger->debug("Digest not enabled for the site", $context);
            return;
        }
        //select user roles who have permission to receive and view emails
        $rolesWithEmailViewPermission = $this->getRolesWithEmailViewPermission();
        if (empty($rolesWithEmailViewPermission)) {
            $this->logger->notice("No roles found with Garden.Email.View permission", $context);
            return;
        }

        $actions = [];

        $options = [
            "lastProcessedUser" => 0,
        ];
        $actions[] = new LongRunnerAction(self::class, "collectFollowingCategoryDigestIterator", [$options]);
        if ($this->shouldProcessDigestForUsersWithOutPreference()) {
            $actions[] = new LongRunnerAction(self::class, "collectDefaultDigestIterator", [$options]);
        }

        $finalAction = count($actions) === 1 ? $actions[0] : new LongRunnerMultiAction($actions);
        $this->longRunner->runDeferred($finalAction);
    }

    /**
     * Check all global preference to see if EmailDigest is enabled
     *
     * @note  Remove Feature flag (Feature.Digest.Enabled)
     * @return bool
     */
    private function isEmailDigestEnabledForSite(): bool
    {
        return !$this->config->get("Garden.Email.Disabled", false) &&
            $this->config->get("Feature.Digest.Enabled", false) &&
            $this->config->get("Garden.Digest.Enabled", false);
    }

    /**
     * Check if default category following is enabled for site
     *
     * @return bool
     */
    public function isDefaultFollowedEnabled(): bool
    {
        $defaultFollowedCategories = $this->config->get(\CategoryModel::DEFAULT_FOLLOWED_CATEGORIES_KEY, false);
        if (!empty($defaultFollowedCategories)) {
            return true;
        }
        return false;
    }

    /**
     * check if users without preference should receive digest
     *
     * @return bool
     */
    private function shouldProcessDigestForUsersWithOutPreference(): bool
    {
        if (!$this->isDefaultFollowedEnabled()) {
            return true;
        }
        $shouldProcess = false;
        $defaultFollowedCategories = $this->getDefaultFollowedCategories();
        foreach ($defaultFollowedCategories as $defaultCategory) {
            $preferences = $defaultCategory["preferences"];
            if ($preferences["preferences.email.digest"]) {
                $shouldProcess = true;
                break;
            }
        }

        return $shouldProcess;
    }

    /**
     * Get default followed categories configured for the site.
     *
     * @return array
     */
    public function getDefaultFollowedCategories(): array
    {
        $defaultFollowedCategories = $this->config->get(\CategoryModel::DEFAULT_FOLLOWED_CATEGORIES_KEY, []);
        if (!empty($defaultFollowedCategories)) {
            $defaultFollowedCategories = json_decode($defaultFollowedCategories, true);
            if (!is_array($defaultFollowedCategories)) {
                $this->logger->warning("Default categories are misconfigured.", [
                    Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                    Logger::FIELD_TAGS => ["configuration"],
                ]);
            }
        }
        return $defaultFollowedCategories ?? [];
    }

    /**
     * Create an iterable for getting email digest data based on users followed categories.
     * Fetches all the users having email view permission with following categories and
     * having digest enabled and makes dynamic templates based on users followed categories
     *
     * @param array $options
     * @return \Generator
     */
    public function collectFollowingCategoryDigestIterator(array $options): \Generator
    {
        try {
            yield new LongRunnerQuantityTotal(function () use (&$options) {
                return $options["totalFollowingUsers"] = $this->getTotalDigestEnabledUsersWithPreference();
            });
            $lastProcessedUser = $options["lastProcessedUser"] ?? 0;
            $usersFollowingCategory = $this->getDigestEnabledCategoryFollowingUserIterator(
                $lastProcessedUser,
                self::DATA_PROCESSING_CHUNK_SIZE
            );
            foreach ($usersFollowingCategory as $userID => $userMeta) {
                try {
                    $lastProcessedUser = $this->processDigestData($userMeta);
                    yield new LongRunnerSuccessID($userID);
                } catch (\Exception $e) {
                    if ($e instanceof LongRunnerTimeoutException) {
                        // Rethrow up to hit the outer catch.
                        throw $e;
                    }
                    yield new LongRunnerFailedID($userID, $e);
                }
            }
        } catch (LongRunnerTimeoutException $e) {
            $options["lastProcessedUser"] = $lastProcessedUser ?? 0;
            return new LongRunnerNextArgs([$options]);
        }
        return LongRunner::FINISHED;
    }

    /**
     * Create an iterable for getting email digest data for users without category preferences.
     * If default followed categories is enabled then generate digest based on that categories
     *
     * @param array $options
     * @return \Generator
     */
    public function collectDefaultDigestIterator(array $options): \Generator
    {
        try {
            yield new LongRunnerQuantityTotal(function () use (&$options) {
                return $options["totalDefaultDigestUsers"] = $this->getTotalUsersWithoutCategoryPreference();
            });
            $lastProcessedUser = $options["lastProcessedUser"] ?? 0;
            $usersWithNoPreferences = $this->getUsersWithOutCategoryPreferenceIterator(
                $lastProcessedUser,
                self::DATA_PROCESSING_CHUNK_SIZE
            );
            foreach ($usersWithNoPreferences as $userID => $userMeta) {
                try {
                    $lastProcessedUser = $this->processDigestData($userMeta, false);
                    yield new LongRunnerSuccessID($userID);
                } catch (\Exception $e) {
                    if ($e instanceof LongRunnerTimeoutException) {
                        // Rethrow up to hit the outer catch.
                        throw $e;
                    }
                    yield new LongRunnerFailedID($userID, $e);
                }
            }
        } catch (LongRunnerTimeoutException $e) {
            $options["lastProcessedUser"] = $lastProcessedUser ?? 0;
            return new LongRunnerNextArgs([$options]);
        }

        return LongRunner::FINISHED;
    }

    /**
     * Process Digest Data
     *
     * @param array $userData {UserID : int, Email : string }
     * @param bool $withPreference  Does the user have a category preference?
     * @return int
     */
    private function processDigestData(array $userData, bool $withPreference = true): int
    {
        $digestData = $existingData = [];
        $siteLanguage = \Gdn::locale()->current(); // @todo This needs to change later based user's language choice for their digest
        if ($withPreference) {
            //Get followed categories for the user
            $digestCategories = $this->getVisibleDigestEnabledCategoriesForUser($userData["UserID"]);
        } else {
            $digestCategories = $this->getDefaultDigestCategoryIDs($userData["UserID"]);
        }

        if (empty($digestCategories)) {
            $context = [
                "UserID" => $userData["UserID"],
            ];
            if ($withPreference) {
                $context["followedCategories"] = $this->categoryModel->getFollowed($userData["UserID"]);
            }
            $this->logger->info("There is no accessible categories for this user to create digest on.", $context);
            return $userData["UserID"];
        }
        $encodeArray = [
            "digestCategories" => $digestCategories,
            "language" => $siteLanguage,
        ];
        $digestHash = sha1(json_encode($encodeArray));
        $existingData = $this->userDigestModel->select(["digestHash" => $digestHash]);
        if (empty($existingData)) {
            $templateData["email"] = $this->getTemplateSettings();
            $templateData["email"]["locale"] = $siteLanguage;
            $trendingDiscussion = $this->getTrendingDiscussionForCategories($digestCategories, $withPreference);
            if (!$trendingDiscussion) {
                $this->logger->info("There is no trending discussion available for this user", [
                    "UserID" => $userData["UserID"],
                    "followedCategories" => $digestCategories,
                ]);
                return $userData["UserID"];
            }
            $templateData["email"]["categories"] = $trendingDiscussion;
            $renderHtml = $this->renderTwig("@dashboard/email/email-digest.twig", $templateData);
            $renderPlainText = $this->renderTwig("@dashboard/email/email-digest-plaintext.twig", $templateData);

            $attributes = [
                "digestLang" => $siteLanguage,
                "digestCategories" => $digestCategories,
                "digestUsers" => [
                    0 => $userData["UserID"],
                ],
                "digestWithPreference" => $withPreference,
            ];
            $digestData = [
                "digestHash" => $digestHash,
                "userMeta" => [$userData],
                "attributes" => $attributes,
                "digestContent" => [
                    "html" => $renderHtml,
                    "text" => $renderPlainText,
                ],
            ];
            $this->userDigestModel->insert($digestData);
        } else {
            $existingData = reset($existingData);
            //update the existing data
            $currentEmails = array_column($existingData["userMeta"], "Email");
            if (!in_array($userData["Email"], $currentEmails)) {
                $existingData["userMeta"][] = $userData;
            }
            $existingData["attributes"]["digestUsers"][] = $userData["UserID"];
            $this->userDigestModel->update(
                ["userMeta" => $existingData["userMeta"], "attributes" => $existingData["attributes"]],
                ["digestHash" => $digestHash]
            );
        }

        return $userData["UserID"];
    }

    /**
     * Get available followed categories for user based on category permissions
     *
     * @param int $userID
     * @return array
     */
    public function getVisibleDigestEnabledCategoriesForUser(int $userID): array
    {
        $followedCategoryIDs = $this->categoryModel->getDigestEnabledCategories($userID);
        if (empty($followedCategoryIDs)) {
            return [];
        }
        $publicVisibleCategories = $this->categoryModel->getPublicVisibleCategories();
        foreach ($followedCategoryIDs as $index => $categoryID) {
            if (!\CategoryModel::categories($categoryID)) {
                //Category doesn't exist
                continue;
            }
            if (!in_array($categoryID, $publicVisibleCategories)) {
                //check if the current user has permission to view this category
                if (!$this->userModel->getCategoryViewPermission($userID, $categoryID)) {
                    unset($followedCategoryIDs[$index]);
                }
            }
        }
        return array_values($followedCategoryIDs);
    }

    /**
     * Get top trending discussions based on the followed categories
     *
     * @param array $categories
     * @param bool $needUnsubscribeLink
     * @return array
     */
    public function getTrendingDiscussionForCategories(array $categories, bool $needUnsubscribeLink = true): array
    {
        $haveFeaturedImage = $this->config->get("Garden.Digest.ImageEnabled", false);
        if (empty($categories)) {
            return [];
        }
        $query = [
            "categoryID" => $categories,
            "limit" => 5,
            "expand" => ["snippet", "-body"],
            "sort" => "-hot",
            "excludeHiddenCategories" => false,
        ];

        //now get the top 5 trending Discussion Posts for these categories
        $result = $this->discussionApiController->index($query);
        $trendingDiscussion = $result->getData();
        if (empty($trendingDiscussion)) {
            return [];
        }
        $digestData = [];
        foreach ($trendingDiscussion as $trending) {
            $categoryID = $trending["categoryID"];
            if (!isset($digestData[$categoryID])) {
                $categoryData = \CategoryModel::categories($categoryID);
                $digestData[$categoryID] = [
                    "name" => $categoryData["Name"],
                    "url" => $categoryData["Url"],
                    "iconUrl" => $categoryData["Photo"]
                        ? (\Gdn_UploadImage::url($categoryData["Photo"]) ?:
                        null)
                        : null,
                ];
                if ($needUnsubscribeLink) {
                    $digestData[$categoryID]["unsubscribeLink"] = "*/unsubscribe_{$categoryID}}/*";
                }
            }
            $trending["insertUser"] = $trending["insertUser"]->jsonSerialize();
            $trending["excerpt"] = $trending["snippet"];
            if (isset($trending["image"]) && !$haveFeaturedImage) {
                unset($trending["image"]);
            }
            $digestData[$categoryID]["discussions"][] = $trending;
        }
        return $digestData;
    }

    /**
     * Get digest categoryIDs visible for users without preference
     *
     * @param int $userID
     * @return array
     */
    public function getDefaultDigestCategoryIDs(int $userID): array
    {
        $publicVisibleCategories = $this->categoryModel->getPublicVisibleCategories();
        if (!$this->isDefaultFollowedEnabled()) {
            return $publicVisibleCategories;
        }

        $defaultFollowedCategories = $this->getDefaultFollowedCategories();
        if (empty($defaultFollowedCategories)) {
            return [];
        }
        $digestCategories = [];
        foreach ($defaultFollowedCategories as $defaultCategory) {
            $categoryPreference = $defaultCategory["preferences"];
            if (!$categoryPreference["preferences.email.digest"]) {
                continue;
            }
            if (!in_array($defaultCategory["categoryID"], $publicVisibleCategories)) {
                if (!$this->userModel->getCategoryViewPermission($userID, $defaultCategory["categoryID"])) {
                    continue;
                }
            }
            $digestCategories[] = $defaultCategory["categoryID"];
        }

        return $digestCategories;
    }

    /**
     * Get user roles with "Garden.Email.View" permission
     *
     * @return array
     */
    private function getRolesWithEmailViewPermission(): array
    {
        return $this->permissionModel->getRolesHavingSpecificPermission("Garden.Email.View");
    }

    /**
     * Get total number of distinct users who has digest enabled and have email view permission
     *
     * @return int
     */
    public function getTotalDigestEnabledUsersWithPreference(): int
    {
        $rolesWithEmailViewPermission = $this->getRolesWithEmailViewPermission();
        return $this->categoryModel->SQL
            ->select("M.UserID")
            ->distinct()
            ->from("UserMeta M")
            ->join("User U", 'U.UserID = M.UserID and M.QueryValue = "Preferences.Email.DigestEnabled.1"')
            ->join("UserCategory C", "C.UserID = U.UserID and C.DigestEnabled =1")
            ->join("UserRole R", "R.UserID = U.UserID")
            ->where([
                "M.Name" => "Preferences.Email.DigestEnabled",
                "C.Followed" => 1,
                "C.DigestEnabled" => 1,
                "U.Deleted" => 0,
                "U.Banned" => 0,
                "R.RoleID" => $rolesWithEmailViewPermission,
            ])
            ->get()
            ->count();
    }

    /**
     * Get total number of distinct users not following any categories
     *
     * @return int
     */
    public function getTotalUsersWithoutCategoryPreference(): int
    {
        $rolesWithEmailViewPermission = $this->getRolesWithEmailViewPermission();
        $rolesWithEmailViewPermission = join(",", $rolesWithEmailViewPermission);
        $sql = $this->categoryModel->SQL;
        /*
        Generating a Query in this case with query builder can be more expensive than manual query due to the way it calculates the counts,
       The query builder fetches all the distinct UserIDs and then does a count on it rather than direct mysql count(distinct(UserID)),
       Also this query can be pretty expensive depending on the volume of data, and we can save a couple of millisecond on each iteration if we do a manual query
        */
        $query = <<<QUERY
SELECT 
    COUNT(DISTINCT (U.UserID)) AS total
FROM
    GDN_UserMeta M
        JOIN
    GDN_User U ON M.UserID = U.UserID
        JOIN
    GDN_UserRole R ON U.UserId = R.UserID
        LEFT JOIN
    (SELECT DISTINCT
        (UserID)
    FROM
        GDN_UserCategory
    WHERE
        Followed = 1 OR Unfollow = 1) C ON C.UserID = U.UserID
WHERE
    M.Name = 'Preferences.Email.DigestEnabled'
        AND M.QueryValue = 'Preferences.Email.DigestEnabled.1'
        AND C.UserID IS NULL
        AND U.Deleted = 0
        AND U.Banned = 0
        AND `R`.`RoleID` IN ({$rolesWithEmailViewPermission});
QUERY;
        $result = $sql->query($query)->column("total");
        return $result[0];
    }

    /**
     * Get the distinct users who are following at least a category based on a limit
     *
     * @param int $lastUserID
     * @param int $chunkSize
     * @return \Generator
     */
    public function getDigestEnabledCategoryFollowingUserIterator(int $lastUserID, int $chunkSize = 1000): \Generator
    {
        $offset = 0;
        $rolesWithEmailView = \Gdn::permissionModel()->getRolesHavingSpecificPermission("Garden.Email.View");
        if (empty($rolesWithEmailView)) {
            $this->logger->notice(
                "Could not find any user roles to process with 'Garden.Email.View' Permission, for users with category preference"
            );
            return;
        }
        $sql = clone $this->categoryModel->SQL;
        while (true) {
            $sql->reset();
            $results = $sql
                ->select("U.UserID")
                ->distinct()
                ->select("U.Email")
                ->from("UserMeta M")
                ->join("User U", "M.UserID = U.UserID and M.QueryValue = 'Preferences.Email.DigestEnabled.1'")
                ->join("UserCategory C", "C.UserID = U.UserID and C.DigestEnabled = 1")
                ->join("UserRole R", "R.UserID = U.UserID")
                ->where([
                    "R.RoleID" => $rolesWithEmailView,
                    "M.Name" => "Preferences.Email.DigestEnabled",
                    "C.Followed" => 1,
                    "C.UserID >" => $lastUserID,
                    "U.Deleted" => 0,
                    "U.Banned" => 0,
                ])
                ->orderBy("U.UserID", "asc")
                ->offset($offset)
                ->limit($chunkSize)
                ->get()
                ->resultArray();
            foreach ($results as $result) {
                yield $result["UserID"] => $result;
            }
            $offset += $chunkSize;
            //No more results to process
            if (empty($results) || count($results) < $chunkSize) {
                return;
            }
        }
    }

    /**
     * Get a list of users who have digest enabled and have no set category preferences
     *
     * @param int $lastUserID
     * @param int $chunkSize
     * @return \Generator
     */
    public function getUsersWithOutCategoryPreferenceIterator(int $lastUserID, int $chunkSize = 1000): \Generator
    {
        $sql = clone $this->categoryModel->SQL;
        $offset = 0;
        $rolesWithEmailView = \Gdn::permissionModel()->getRolesHavingSpecificPermission("Garden.Email.View");
        if (empty($rolesWithEmailView)) {
            $this->logger->notice(
                "Could not find any user roles to process with 'Garden.Email.View' Permission, for users without category preference"
            );
            return;
        }
        $subQuery = $sql
            ->select("UserID")
            ->distinct(true)
            ->from("UserCategory")
            ->where("Followed", 1)
            ->orWhere("Unfollow", 1)
            ->getSelect(true);
        $sql->reset();
        while (true) {
            $results = $sql
                ->select("U.UserID")
                ->distinct()
                ->select("U.Email")
                ->from("UserMeta M")
                ->join("User U", "U.UserID = M.UserID and M.QueryValue = 'Preferences.Email.DigestEnabled.1'")
                ->join("UserRole R", "U.UserID = R.UserID")
                ->leftJoin("({$subQuery}) UC", "UC.UserID = U.UserID")
                ->where([
                    "U.UserID >" => $lastUserID,
                    "UC.UserID" => null,
                    "M.Name" => "Preferences.Email.DigestEnabled",
                    "R.RoleID" => $rolesWithEmailView,
                    "U.Deleted" => 0,
                    "U.Banned" => 0,
                ])
                ->limit($chunkSize)
                ->offset($offset)
                ->get()
                ->resultArray();
            foreach ($results as $result) {
                yield $result["UserID"] => $result;
            }
            $offset += $chunkSize;
            //No more results to process
            if (empty($results) || count($results) < $chunkSize) {
                return;
            }
        }
    }

    /**
     * Get the template settings for email digest
     *
     * @return array
     */
    public function getTemplateSettings(): array
    {
        $templateConfig = $this->config->get("Garden.EmailTemplate", false);
        return [
            "siteUrl" => \Gdn::request()->getSimpleUrl(),
            "digestUnsubscribeLink" => "*/digest_unsubscribe/*",
            "notificationPreferenceLink" => url("/profile/preferences", true),
            "title" => "This Week's Trending Posts",
            "imageUrl" => $templateConfig["Image"] ?? "",
            "imageAlt" => $this->config->get("Garden.Title") ?? "Vanilla Forums Digest",
            "textColor" => $templateConfig["TextColor"] ?? \EmailTemplate::DEFAULT_TEXT_COLOR,
            "backgroundColor" => $templateConfig["BackgroundColor"] ?? \EmailTemplate::DEFAULT_BACKGROUND_COLOR,
            "buttonTextColor" => $templateConfig["ButtonTextColor"] ?? \EmailTemplate::DEFAULT_BUTTON_TEXT_COLOR,
            "buttonBackgroundColor" =>
                $templateConfig["ButtonBackgroundColor"] ?? \EmailTemplate::DEFAULT_BUTTON_BACKGROUND_COLOR,
            "footer" => "<p>Sample html footer</p>", // @todo : this needs to come from the config once VNLA-4357 is merged (Email::getFooterContent())
        ];
    }
}
