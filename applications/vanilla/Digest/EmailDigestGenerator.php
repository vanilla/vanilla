<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Digest;

use Garden\EventManager;
use Garden\Web\Exception\ServerException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Community\Events\DigestUnsubscribeEvent;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Controller\Api\NotificationPreferencesApiController;
use Vanilla\Dashboard\Models\UserNotificationPreferencesModel;
use Vanilla\Logger;
use Vanilla\Models\Model;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerMultiAction;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerQuantityTotal;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Scheduler\TrackingSlipInterface;
use Vanilla\Web\SystemCallableInterface;
use Vanilla\Web\TwigRenderTrait;

/**
 * Class for processing digest data for users.
 */
class EmailDigestGenerator implements SystemCallableInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use TwigRenderTrait;

    const DATA_PROCESSING_CHUNK_SIZE = 1000;

    private \Gdn_Database $database;
    private \CategoryModel $categoryModel;

    private \UserModel $userModel;

    private \LocaleModel $localeModel;

    private \UserMetaModel $userMetaModel;

    private DigestModel $digestModel;

    private DigestContentModel $digestContentModel;

    private UserDigestModel $userDigestModel;

    private \PermissionModel $permissionModel;

    private LongRunner $longRunner;

    private ConfigurationInterface $config;

    private \Gdn_Email $email;

    private \DiscussionsApiController $discussionApiController;

    private NotificationPreferencesApiController $notificationPreferencesApiController;

    private EventManager $eventManager;

    /**
     * @param \Gdn_Database $database
     * @param \CategoryModel $categoryModel
     * @param \UserModel $userModel
     * @param \UserMetaModel $userMetaModel
     * @param \LocaleModel $localeModel
     * @param DigestModel $digestModel
     * @param DigestContentModel $digestContentModel
     * @param UserDigestModel $userDigestModel
     * @param \PermissionModel $permissionModel
     * @param LongRunner $longRunner
     * @param ConfigurationInterface $config
     * @param \Gdn_Email $email
     * @param \DiscussionsApiController $discussionApiController
     * @param NotificationPreferencesApiController $notificationPreferencesApiController
     * @param EventManager $eventManager
     */
    public function __construct(
        \Gdn_Database $database,
        \CategoryModel $categoryModel,
        \UserModel $userModel,
        \UserMetaModel $userMetaModel,
        \LocaleModel $localeModel,
        DigestModel $digestModel,
        DigestContentModel $digestContentModel,
        UserDigestModel $userDigestModel,
        \PermissionModel $permissionModel,
        LongRunner $longRunner,
        ConfigurationInterface $config,
        DigestEmail $email,
        \DiscussionsApiController $discussionApiController,
        NotificationPreferencesApiController $notificationPreferencesApiController,
        EventManager $eventManager
    ) {
        $this->database = $database;
        $this->categoryModel = $categoryModel;
        $this->userModel = $userModel;
        $this->userMetaModel = $userMetaModel;
        $this->localeModel = $localeModel;
        $this->digestModel = $digestModel;
        $this->digestContentModel = $digestContentModel;
        $this->userDigestModel = $userDigestModel;
        $this->permissionModel = $permissionModel;
        $this->longRunner = $longRunner;
        $this->config = $config;
        $this->email = $email;
        $this->discussionApiController = $discussionApiController;
        $this->notificationPreferencesApiController = $notificationPreferencesApiController;
        $this->eventManager = $eventManager;
    }

    /**
     * @inheritdoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["sendDigestsIterator", "createDigestsIterator"];
    }

    /**
     * Get weekly digest data for users of the site
     *
     * @param \DateTimeInterface $scheduleDate The date the digest should be scheduled for.
     *
     * @return TrackingSlipInterface
     */
    public function prepareWeeklyDigest(\DateTimeInterface $scheduleDate): TrackingSlipInterface
    {
        $action = $this->prepareDigestAction($scheduleDate, DigestModel::DIGEST_TYPE_WEEKLY);
        return $this->longRunner->runDeferred($action);
    }

    /**
     * Get daily digest data for users of the site
     *
     * @param \DateTimeInterface $scheduleDate The datetime the digest should be scheduled for generation.
     *
     * @return TrackingSlipInterface
     */
    public function prepareDailyDigest(\DateTimeInterface $scheduleDate): TrackingSlipInterface
    {
        $action = $this->prepareDigestAction($scheduleDate, DigestModel::DIGEST_TYPE_DAILY);
        return $this->longRunner->runDeferred($action);
    }

    /**
     * Get monthly digest data for users of the site
     *
     * @param \DateTimeInterface $scheduleDate The datetime the digest should be scheduled for generation.
     *
     * @return TrackingSlipInterface
     */
    public function prepareMonthlyDigest(\DateTimeInterface $scheduleDate): TrackingSlipInterface
    {
        $action = $this->prepareDigestAction($scheduleDate, DigestModel::DIGEST_TYPE_MONTHLY);
        return $this->longRunner->runDeferred($action);
    }

    /**
     * Get an action for usage with {@link self::prepareWeeklyDigest(),self::prepareMonthlyDigest(), self::prepareDailyDigest()}. Exposed publicly for testing only.
     *
     * @param \DateTimeInterface $scheduleDate The date the digest should be scheduled for.
     * @param string $digestType
     *
     * @return LongRunnerAction
     * @internal
     */
    public function prepareDigestAction(\DateTimeInterface $scheduleDate, string $digestType): LongRunnerAction
    {
        if (!in_array($digestType, DigestModel::DIGEST_FREQUENCY_OPTIONS)) {
            throw new \InvalidArgumentException("Invalid digest type");
        }
        // Create a digest
        $digestID = $this->digestModel->insert([
            "dateScheduled" => $scheduleDate,
            "digestType" => $digestType,
            "totalSubscribers" => $this->getDigestEnabledUsersCount($digestType),
        ]);

        $action = new LongRunnerMultiAction([
            new LongRunnerAction(self::class, "createDigestsIterator", [$digestID, $digestType]),
            new LongRunnerAction(self::class, "sendDigestsIterator", [$digestID]),
        ]);

        return $action;
    }

    /**
     * Get an action for usage with {@link self::prepareDailyDigest()}.
     *
     * @param \DateTimeInterface $scheduleDate The date the digest should be scheduled for.
     *
     * @return LongRunnerAction
     * @internal
     */
    public function prepareDailyDigestAction(\DateTimeInterface $scheduleDate): LongRunnerAction
    {
        // Create a digest
        $digestID = $this->digestModel->insert([
            "dateScheduled" => $scheduleDate,
            "digestType" => DigestModel::DIGEST_TYPE_DAILY,
            "totalSubscribers" => $this->getDigestEnabledUsersCount(),
        ]);

        $action = new LongRunnerMultiAction([
            new LongRunnerAction(self::class, "createDigestsIterator", [$digestID]),
            new LongRunnerAction(self::class, "sendDigestsIterator", [$digestID]),
        ]);

        return $action;
    }

    /**
     * Prepare a single digest email for a user.
     *
     * @param int $userID
     *
     * @return DigestEmail
     */
    public function prepareSingleUserDigest(int $userID): DigestEmail
    {
        // Create our digest.
        $digestID = $this->digestModel->insert([
            "digestType" => DigestModel::DIGEST_TYPE_IMMEDIATE,
            "dateScheduled" => CurrentTimeStamp::getDateTime(),
        ]);

        // Prep the digest content.
        // Don't use the digest iterator because we are explicitly not checking digest preferences.
        $digestUserCategory = [
            "userID" => $userID,
            "digestCategoryIDs" => $this->database
                ->createSql()
                ->select("CategoryID")
                ->distinct()
                ->from("UserCategory")
                ->where(["DigestEnabled" => true, "Followed" => true, "UserID" => $userID])
                ->get()
                ->column("CategoryID"),
            "digestLanguage" => $this->getUserLanguagePreference($userID),
        ];
        $utmParameters = $this->generateUtmParameters($digestID);
        $userDigestFrequencyPreference = $this->getUserDigestFrequencyPreference($userID);
        $userDigestID = $this->createUserDigest(
            $digestID,
            $digestUserCategory,
            $utmParameters,
            $userDigestFrequencyPreference
        );
        if ($userDigestID === null) {
            // In the future we may have a specific email template for this.
            throw new ServerException("There was no content found for this user's digest.");
        }

        // Now generate the digest email.
        $userDigests = iterator_to_array($this->userDigestModel->iterateUnsentDigests($digestID));
        if (count($userDigests) !== 1) {
            throw new ServerException(
                "Exactly 1 digest should have been generated. Instead we generated " . count($userDigests)
            );
        }

        $userDigest = reset($userDigests);

        $digestEmail = $this->prepareUserDigestEmail($userDigest);
        return $digestEmail;
    }

    /**
     * LongRunner to iterate through unsent user digests in a digest and send them.
     *
     * @param int $digestID The digestID we are sending for.
     *
     * @return iterable
     */
    public function sendDigestsIterator(int $digestID): iterable
    {
        $digest = $this->digestModel->selectSingle(["digestID" => $digestID]);
        $context = [
            Logger::FIELD_TAGS => ["digest", $digest["digestType"]],
            Logger::FIELD_CHANNEL => Logger::CHANNEL_SYSTEM,
        ];

        yield new LongRunnerQuantityTotal([$this->userDigestModel, "getCountUsersInDigest"], [$digestID]);

        foreach ($this->userDigestModel->iterateUnsentDigests($digestID, 500) as $userID => $digestRecord) {
            $statusWhere = [
                "digestID" => $digestID,
                "userID" => $userID,
            ];

            // we have an email to generate.
            try {
                if ($digestRecord === false) {
                    // We are skipping this record because the user was deleted between generation and sending.
                    $this->userDigestModel->update(["status" => UserDigestModel::STATUS_SKIPPED], $statusWhere);

                    yield new LongRunnerSuccessID($userID);
                    continue;
                }
                try {
                    $digestEmail = $this->prepareUserDigestEmail($digestRecord);
                    $digestEmail->scheduleDelivery($digest["dateScheduled"]);
                    $digestEmail->send();
                    $this->userDigestModel->update(["status" => UserDigestModel::STATUS_SENT], $statusWhere);
                    yield new LongRunnerSuccessID($userID);
                } catch (\Throwable $exception) {
                    if ($exception instanceof LongRunnerTimeoutException) {
                        // Rethrow to outer catch.
                        throw $exception;
                    }

                    $this->userDigestModel->update(["status" => UserDigestModel::STATUS_FAILED], $statusWhere);
                    $this->logger->error(
                        "Failed to send an email digest to target user {targetUser.Name}",
                        $context + [
                            "targetUser" => $digestRecord["user"],
                            "exception" => $exception,
                        ]
                    );
                    yield new LongRunnerFailedID($userID, $exception);
                }
            } catch (LongRunnerTimeoutException $exception) {
                return new LongRunnerNextArgs([$digestID]);
            }
        }
    }

    /**
     * Create an email instance for a specific user digest.
     *
     * @param array $digestRecord A record from {@link UserDigestModel::iterateUnsentDigests()}
     *
     * @return DigestEmail
     */
    public function prepareUserDigestEmail(array $digestRecord): DigestEmail
    {
        $digestEmail = \Gdn::getContainer()->get(DigestEmail::class);
        $digestEmail->setHtmlContent($digestRecord["digestContent"]["html"]);
        $digestEmail->setTextContent($digestRecord["digestContent"]["text"]);
        $canSubCategories = $digestRecord["digestAttributes"]["canUnsubscribeCategories"];
        $digestUser = $digestRecord["user"];
        $digestEmail->setToAddress($digestUser["Email"], $digestUser["Name"]);

        if ($canSubCategories) {
            $digestCategoryIDs = $digestRecord["digestAttributes"]["digestCategoryIDs"];
            $digestEmail->mergeCategoryUnSubscribe($digestUser, $digestCategoryIDs);
        }

        $digestEmail->mergeDigestUnsubscribe($digestUser);
        // Fire the event to merge additional data
        $this->eventManager->dispatch(
            new DigestUnsubscribeEvent($digestEmail, $digestUser, $digestRecord["digestAttributes"])
        );

        $addBannerTitle = $this->config->get("Garden.Digest.IncludeCommunityName", false);
        $digestTitle = "";
        if ($addBannerTitle) {
            $siteTitle = $this->config->get("Garden.Title");
            $digestTitle = "[$siteTitle] ";
        }
        $digestTitle .= $digestRecord["digestAttributes"]["title"];
        $digestEmail->subject($digestTitle);
        return $digestEmail;
    }

    /**
     * Create an iterable for all active users with digest enabled.
     *
     * @param int $digestID The digestID we are generating for.
     * @param int|null $lastProcessedUserID Use this for resuming the long runner to not repeat users.
     *
     * @return \Generator
     */
    public function createDigestsIterator(
        int $digestID,
        ?string $digestFrequency = null,
        ?int $lastProcessedUserID = null
    ): \Generator {
        try {
            //Long runner jobs can time out and when it resumes we need to flush category permissions
            \DiscussionModel::clearCategoryPermissions();
            yield new LongRunnerQuantityTotal(function () use ($digestFrequency) {
                return $this->getDigestEnabledUsersCount($digestFrequency);
            });
            $utmParameters = $this->generateUtmParameters($digestID);
            $condition = [
                "um.UserID >" => $lastProcessedUserID ?? 0,
            ];
            if (!empty($digestFrequency)) {
                $condition["um.DigestFrequency"] = $digestFrequency;
            }
            $usersWithDigest = $this->getDigestUserCategoriesIterator($condition, self::DATA_PROCESSING_CHUNK_SIZE);
            foreach ($usersWithDigest as $userID => $row) {
                try {
                    $lastProcessedUserID = $userID;
                    $this->createUserDigest(
                        $digestID,
                        $row,
                        $utmParameters,
                        $digestFrequency ?? DigestModel::DIGEST_TYPE_WEEKLY
                    );
                    yield new LongRunnerSuccessID($userID);
                } catch (\Exception $e) {
                    if ($e instanceof LongRunnerTimeoutException) {
                        // Rethrow up to hit the outer catch.
                        throw $e;
                    }
                    yield new LongRunnerFailedID($userID, $e);
                } finally {
                    $this->setLocale($this->getDefaultLocale());
                }
            }
        } catch (LongRunnerTimeoutException $e) {
            return new LongRunnerNextArgs([$digestID, $digestFrequency, $lastProcessedUserID]);
        }

        return LongRunner::FINISHED;
    }

    /**
     * Determine the categories / groups from where the data should be fetched.
     *
     * @param array $digestUserCategory A row from {@link self::getDigestUserCategoriesIterator()}
     *
     * @return array{categoryIDs: int[], canUnsubscribe: boolean, logContext: array}|null Null if the user has no access to any potential category-based digest content.
     */
    public function getDigestData(array $digestUserCategory): ?array
    {
        // Get the user's visible category data for digest.
        $userID = $digestUserCategory["userID"];
        $userVisibleCategoryIDs = $this->categoryModel->getCategoryIDsWithPermissionForUser(
            $userID,
            "Vanilla.Discussions.View"
        );
        $userVisibleCategoryIDs = array_filter($userVisibleCategoryIDs, function (int $categoryID) {
            if ($categoryID === \CategoryModel::ROOT_ID) {
                // Omit the root category.
                return false;
            }
            return true;
        });
        // These categories are marked "Hide from recent discussions and other widgets".
        $excludedFromGlobalsCategoryIDs = $this->categoryModel->selectCachedIDs([
            "HideAllDiscussions" => true,
        ]);

        ///
        /// Grab all the possible values of categoryIDs and intersect them with the user permissions.
        ///
        $selectedDigestCategoryIDs = array_intersect($digestUserCategory["digestCategoryIDs"], $userVisibleCategoryIDs);
        $defaultDigestCategoryIDs = [];
        foreach ($this->getDefaultFollowedCategories() as $defaultCategory) {
            $categoryPreference = $defaultCategory["preferences"];
            if ($categoryPreference[\CategoriesApiController::OUTPUT_PREFERENCE_DIGEST] ?? false) {
                // Digest is not enabled on the default followed category.
                $defaultDigestCategoryIDs[] = $defaultCategory["categoryID"];
            }
        }
        $defaultDigestCategoryIDs = array_intersect($defaultDigestCategoryIDs, $userVisibleCategoryIDs);

        /// Now we have a slightly complicated process for determining "digest" categories.
        $canUnsubscribeFromCategories = false;
        if (!empty($selectedDigestCategoryIDs)) {
            // In this scenario the user has explicitly enabled some digest categories.
            $digestCategoryIDs = $selectedDigestCategoryIDs;
            $canUnsubscribeFromCategories = true;
        } elseif (!empty($defaultDigestCategoryIDs)) {
            // Use the explicitly admin-configured default digest categories.
            $digestCategoryIDs = $defaultDigestCategoryIDs;
        } else {
            $digestCategoryIDs = array_diff($userVisibleCategoryIDs, $excludedFromGlobalsCategoryIDs);
        }

        $logContext = [
            "userID" => $userID,
            "visibleCategoryIDs" => $userVisibleCategoryIDs,
            "digestEnabledCategoryIDs" => $selectedDigestCategoryIDs,
            "defaultDigestCategoryIDs" => $defaultDigestCategoryIDs,
        ];
        $digestData = [
            "categoryIDs" => array_values(array_unique($digestCategoryIDs)),
            "canUnsubscribe" => $canUnsubscribeFromCategories,
            "logContext" => $logContext,
        ];
        // Fire the event to get any additional data
        return $this->eventManager->fireFilter("additionalEmailDigestData", $digestData);
    }

    /**
     * Create `digestContent` and `userDigest` records for a user and a particular digest.
     *
     * @param int $digestID
     * @param array $digestUserCategory A record from {@link self::getDigestUserCategoriesIterator()}
     * @param string $utmParameters
     *
     * @return int|null A userDigestID or null.
     */
    public function createUserDigest(
        int $digestID,
        array $digestUserCategory,
        string $utmParameters = "",
        string $digestFrequency = DigestModel::DIGEST_TYPE_WEEKLY
    ): ?int {
        $context = [
            Logger::FIELD_EVENT => "user_digest_skip",
            Logger::FIELD_TAGS => ["digest", $digestFrequency],
        ];
        $userID = $digestUserCategory["userID"];
        $digestLanguage = $digestUserCategory["digestLanguage"];
        $digestData = $this->getDigestData($digestUserCategory);
        $digestCategoryIDs = $digestData["categoryIDs"];
        $canUnsubscribe = $digestData["canUnsubscribe"];
        $logContext = $digestData["logContext"] + [
            Logger::FIELD_EVENT => ["user_digest_skip"],
            Logger::FIELD_TAGS => ["digest", $digestFrequency],
        ];

        if ($digestData === null) {
            $this->logger->info(
                "Skipped generating digest for user because there was no content visible to them.",
                $logContext + $context
            );
            // There we no categories available for the user.
            return null;
        }
        $additionalAttributes = [];
        if (!empty($digestData["additionalAttributes"])) {
            $additionalAttributes = $digestData["additionalAttributes"];
            unset($digestData["additionalAttributes"]);
        }

        $digestHashData = $digestData + [
            "language" => $digestLanguage,
        ];
        $digestHash = sha1(json_encode($digestHashData));

        $existingContent = $this->digestContentModel->select(
            [
                "digestContentHash" => $digestHash,
                "digestID" => $digestID,
            ],
            [
                Model::OPT_SELECT => ["digestID", "digestContentID", "digestContentHash"],
                Model::OPT_LIMIT => 1,
            ]
        );
        $digestContentID = $existingContent[0]["digestContentID"] ?? null;
        if ($digestContentID === null) {
            $defaultTitle = match ($digestFrequency) {
                DigestModel::DIGEST_TYPE_DAILY => "Today's trending content",
                DigestModel::DIGEST_TYPE_MONTHLY => "This month's trending content",
                default => "This week's trending content",
            };
            $title = $this->config->get("Garden.Digest.Title", $defaultTitle);
            $templateData["email"] = $this->getTemplateSettings();
            if (!empty($utmParameters)) {
                $templateData["email"]["utmParams"] = $utmParameters;
            }
            $templateData["email"]["title"] = $title;
            $templateData["email"]["locale"] = $digestLanguage;

            $slotType = strtolower(substr($digestFrequency, 0, 1));
            $trendingDiscussions = $this->getTopDiscussions($digestCategoryIDs, $canUnsubscribe, $slotType);

            // Fire filter to get additional data
            $trendingDiscussions = \Gdn::eventManager()->fireFilter(
                "additionalWeeklyDiscussion",
                $trendingDiscussions,
                $digestData
            );

            if (empty($trendingDiscussions)) {
                $this->logger->info(
                    "Skipped generating digest for user because there was no discussions visible to them.",
                    [
                        "UserID" => $userID,
                        "digestCategoryIDs" => $digestCategoryIDs,
                    ] + $context
                );
                //Add a skipped record to the database for easier tracking
                $this->userDigestModel->insert([
                    "userID" => $userID,
                    "digestID" => $digestID,
                    "digestContentID" => -1,
                    "status" => UserDigestModel::STATUS_SKIPPED,
                ]);
                return null;
            }
            $templateData["email"]["contents"] = $trendingDiscussions;
            $this->email->setFormat("html");
            $templateData["email"]["introduction"] = $this->email->getIntroductionContentForDigest();
            $templateData["email"]["footer"] = $this->email::imageInlineStyles($this->email->getFooterContent());

            $this->setLocale($digestLanguage);
            $renderHtml = $this->renderTwig("@vanilla/email/email-digest.twig", $templateData);
            $this->email->setFormat("text");
            $templateData["email"]["introduction"] = $this->email->getIntroductionContentForDigest();
            $templateData["email"]["footer"] = $this->email->getFooterContent();
            $renderPlainText = $this->renderTwig("@vanilla/email/email-digest-plaintext.twig", $templateData);

            $attributes = array_merge(
                [
                    "digestLang" => $digestLanguage,
                    "digestCategoryIDs" => $digestCategoryIDs,
                    "canUnsubscribeCategories" => $canUnsubscribe,
                    "title" => $title,
                ],
                $additionalAttributes
            );

            $newDigestContent = [
                "digestID" => $digestID,
                "digestContentHash" => $digestHash,
                "attributes" => $attributes,
                "digestContent" => [
                    "html" => $renderHtml,
                    "text" => $renderPlainText,
                ],
            ];
            $digestContentID = $this->digestContentModel->insert($newDigestContent);
        }

        // Now insert a userDigest record.
        $userDigestID = $this->userDigestModel->insert([
            "userID" => $userID,
            "digestID" => $digestID,
            "digestContentID" => $digestContentID,
            "status" => UserDigestModel::STATUS_PENDING,
        ]);

        return $userDigestID;
    }

    /**
     * Get top trending discussions based on the followed categories
     *
     * @param array $categories
     * @param bool $needUnsubscribeLink
     * @param string $slotType
     * @return array
     */
    public function getTopDiscussions(
        array $categories,
        bool $needUnsubscribeLink = true,
        string $slotType = "w"
    ): array {
        $metaSettings = $this->getDiscussionMetaSettings();
        $metaMappings = $this->getMetaMappings();
        $postLimit = $this->config->get("Garden.Digest.PostCount", 5);
        if (empty($categories)) {
            return [];
        }

        // now get the top 5 Discussion Posts for these categories
        $query = [
            "categoryID" => $categories,
            "limit" => $postLimit,
            "expand" => ["snippet", "-body"],
            "excludeHiddenCategories" => false,
            "sort" => "-" . \DiscussionModel::SORT_EXPIRIMENTAL_TRENDING,
            "slotType" => $slotType,
        ];
        $result = $this->discussionApiController->index($query);
        $trendingDiscussions = $result->getData();

        if (empty($trendingDiscussions)) {
            return [];
        }
        $digestData = [];
        foreach ($trendingDiscussions as $trending) {
            $categoryID = $trending["categoryID"];
            if (!isset($digestData[$categoryID])) {
                $categoryData = \CategoryModel::categories($categoryID);
                $digestData[$categoryID] = [
                    "name" => $categoryData["Name"],
                    "url" => categoryUrl($categoryData),
                    "iconUrl" => $categoryData["Photo"]
                        ? (\Gdn_UploadImage::url($categoryData["Photo"]) ?:
                        null)
                        : null,
                ];
                if ($needUnsubscribeLink) {
                    $digestData[$categoryID]["unsubscribeLink"] = "*/unsubscribe_{$categoryID}/*";
                }
            }
            $trending["insertUser"] = $trending["insertUser"]->jsonSerialize();
            $trending["excerpt"] = $trending["snippet"];
            foreach ($metaMappings as $metaKey => $mapping) {
                if ($metaSettings[$metaKey]) {
                    continue;
                }
                unset($trending[$mapping]);
            }
            $digestData[$categoryID]["discussions"][] = $trending;
        }
        return ["Category" => $digestData];
    }

    /**
     * Get default followed categories configured for the site.
     *
     * @return array
     */
    private function getDefaultFollowedCategories(): array
    {
        $defaultFollowedCategories = $this->config->get(\CategoryModel::DEFAULT_FOLLOWED_CATEGORIES_KEY, "");
        if (empty($defaultFollowedCategories)) {
            return [];
        }
        $defaultFollowedCategories = json_decode($defaultFollowedCategories, true);
        if (!is_array($defaultFollowedCategories)) {
            $this->logger->warning("Default categories are misconfigured.", [
                Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                Logger::FIELD_TAGS => ["configuration"],
            ]);
            return [];
        }

        $defaultFollowedCategories = $this->categoryModel->convertOldPreferencesToNew($defaultFollowedCategories);
        return $defaultFollowedCategories;
    }

    /**
     * Get user roles with "Garden.Email.View" permission
     *
     * @return array
     */
    public function getRolesWithEmailViewPermission(): array
    {
        return $this->permissionModel->getRoleIDsHavingSpecificPermission("Garden.Email.View");
    }

    /**
     * Base query for {@link self::getDigestEnabledUsersWithPreferenceCount()} and {@link self::getDigestEnabledUsersWithPreferenceIterator}.
     *
     * @return \Gdn_SQLDriver
     */
    private function getDigestEnabledUsersQuery(array $roleIDs): \Gdn_SQLDriver
    {
        $userOnClause =
            $this->config->get(DigestModel::AUTOSUBSCRIBE_DEFAULT_PREFERENCE) == 1
                ? 'u.UserID = um.UserID and um.QueryValue in ("Preferences.Email.DigestEnabled.1","Preferences.Email.DigestEnabled.3") AND u.Deleted = 0'
                : 'u.UserID = um.UserID and um.QueryValue = "Preferences.Email.DigestEnabled.1" AND u.Deleted = 0';

        $query = $this->database
            ->createSql()
            ->from("UserMeta um")
            // Here we are using the querying value so values can be pulled directly from the QueryValue_UserID index.
            ->join("User u", $userOnClause)
            // Exclude Deleted users
            ->join("UserRole ur", "ur.UserID = um.UserID")
            ->where([
                "ur.RoleID" => $roleIDs,
                "u.Confirmed" => 1,
            ]);

        return $query;
    }

    /**
     * Get the count of users who have digest enabled.
     * Optionally filter by digest frequency.
     *
     * @param string|null $digestFrequency
     * @return int
     * @throws \Exception
     */
    public function getDigestEnabledUsersCount(?string $digestFrequency = null): int
    {
        $roleIDs = $this->getRolesWithEmailViewPermission();
        if (empty($roleIDs)) {
            return 0;
        }

        $sql = $this->getDigestEnabledUsersQuery($roleIDs)->select("COUNT(DISTINCT(um.UserID)) as total");
        if (!empty($digestFrequency) && in_array($digestFrequency, DigestModel::DIGEST_FREQUENCY_OPTIONS)) {
            $this->addUserDigestFrequency($sql, $digestFrequency);
        }
        return $sql->get()->column("total")[0];
    }

    /**
     * Add the user digest frequency to the query filter
     *
     * @param \Gdn_SQLDriver $query
     * @param string $digestFrequency
     * @return void
     */
    private function addUserDigestFrequency(\Gdn_SQLDriver &$query, string $digestFrequency): void
    {
        // This is the default digest frequency for users who have not set a explicit preference.
        $defaultUserDigestFrequency = \Gdn::config()->get(
            DigestModel::DEFAULT_DIGEST_FREQUENCY_KEY,
            DigestModel::DIGEST_TYPE_WEEKLY
        );

        $key = UserNotificationPreferencesModel::USER_PREFERENCE_DIGEST_FREQUENCY_KEY;
        if ($digestFrequency === $defaultUserDigestFrequency) {
            $query
                ->join("UserMeta um2", "um.UserID = um2.UserID AND um2.Name = '$key'", "left")
                ->beginWhereGroup()
                ->where("um2.QueryValue", null)
                ->orWhere("um2.QueryValue", "$key.$digestFrequency", false, true)
                ->endWhereGroup();
        } else {
            $query->join(
                "UserMeta um2",
                "um.UserID = um2.UserID AND um2.QueryValue = '$key.$digestFrequency'",
                "inner"
            );
        }
    }

    /**
     * Get the distinct users who are following at least a category based on a limit
     *
     * @param array $where
     * @param int $chunkSize
     *
     * @return \Generator<mixed, int, array{userID: int, digestCategoryIDs: int[]}>
     */
    public function getDigestUserCategoriesIterator(array $where, int $chunkSize = 1000): iterable
    {
        $roleIDs = \Gdn::permissionModel()->getRoleIDsHavingSpecificPermission("Garden.Email.View");
        if (empty($roleIDs)) {
            $this->logger->notice("Could not find any user roles to process with 'Garden.Email.View' Permission.");
            return;
        }

        $lastUserID = 0;
        while (true) {
            $digestEnabledUserQuery = $this->getDigestEnabledUsersQuery($roleIDs);
            if (!empty($where["um.DigestFrequency"])) {
                $this->addUserDigestFrequency($digestEnabledUserQuery, $where["um.DigestFrequency"]);
                unset($where["um.DigestFrequency"]);
            }

            $innerQuery = $digestEnabledUserQuery
                ->select("um.UserID")
                ->where([
                    "um.UserID >" => $lastUserID,
                ])
                ->where($where)
                ->groupBy("um.UserID")
                ->orderBy("um.UserID", "asc")
                ->limit($chunkSize);

            $query = $this->database
                ->createSql()
                ->select(["innerU.UserID", "JSON_ARRAYAGG(ucDigest.CategoryID) as digestCategoryIDs"])
                ->from("({$innerQuery->getSelect(true)}) innerU")
                ->leftJoin(
                    "UserCategory ucDigest",
                    "ucDigest.UserID = innerU.UserID AND ucDigest.DigestEnabled = 1 AND ucDigest.Followed = 1"
                )
                ->groupBy("innerU.UserID")
                ->orderBy("innerU.UserID", "asc");

            $results = $query->get()->resultArray();

            $cleanArrayAgg = function (string $val): array {
                try {
                    $result = json_decode($val, true);
                    $result = array_filter($result);
                    $result = array_values($result);
                    return $result;
                } catch (\Throwable $e) {
                    return [];
                }
            };

            foreach ($results as $result) {
                $userID = $result["UserID"];

                $digestCategoryIDs = $cleanArrayAgg($result["digestCategoryIDs"]);
                $lastUserID = $userID;

                yield $userID => [
                    "userID" => $userID,
                    "digestCategoryIDs" => $digestCategoryIDs,
                    "digestLanguage" => $this->getUserLanguagePreference($userID),
                ];
            }
            // No more results to process
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
        $templateConfig = $this->config->get("Garden.EmailTemplate", []);
        $templateSetting = [
            "siteUrl" => \Gdn::request()->getSimpleUrl(),
            "digestSubscribeReason" => "*/digest_subscribe_reason/*",
            "digestUnsubscribeLink" => "*/digest_unsubscribe/*",
            "notificationPreferenceLink" => url("/profile/preferences", true),
            "imageUrl" => $this->config->get("Garden.Digest.Logo", null) ?? ($templateConfig["Image"] ?? ""),
            "imageAlt" => $this->config->get("Garden.Title") ?? "Vanilla Forums Digest",
            "textColor" => $templateConfig["TextColor"] ?? \EmailTemplate::DEFAULT_TEXT_COLOR,
            "backgroundColor" => $templateConfig["BackgroundColor"] ?? \EmailTemplate::DEFAULT_BACKGROUND_COLOR,
            "containerBackgroundColor" =>
                $templateConfig["ContainerBackgroundColor"] ?? \EmailTemplate::DEFAULT_CONTAINER_BACKGROUND_COLOR,
            "buttonTextColor" => $templateConfig["ButtonTextColor"] ?? \EmailTemplate::DEFAULT_BUTTON_TEXT_COLOR,
            "buttonBackgroundColor" =>
                $templateConfig["ButtonBackgroundColor"] ?? \EmailTemplate::DEFAULT_BUTTON_BACKGROUND_COLOR,
        ];
        return $templateSetting;
    }

    /**
     * Get meta configuration for digest
     *
     * @return array
     */
    public function getDiscussionMetaSettings(): array
    {
        $metaSettings = [
            "imageEnabled" => false,
            "authorEnabled" => true,
            "viewCountEnabled" => true,
            "commentCountEnabled" => true,
            "scoreCountEnabled" => true,
        ];

        foreach ($metaSettings as $key => $value) {
            $metaSettings[$key] = $this->config->get("Garden.Digest." . ucfirst($key), $value);
        }

        return $metaSettings;
    }

    /**
     * Return mappings for meta configuration
     *
     * @return string[]
     */
    public function getMetaMappings(): array
    {
        return $metaSettings = [
            "imageEnabled" => "image",
            "authorEnabled" => "insertUser",
            "viewCountEnabled" => "countViews",
            "commentCountEnabled" => "countComments",
            "scoreCountEnabled" => "score",
        ];
    }

    /**
     * Generate Utm parameters for digest links
     *
     * @param int $digestID
     * @return string
     */
    public function generateUtmParameters(int $digestID): string
    {
        $digestRecord = $this->digestModel->selectSingle(["digestID" => $digestID]);
        $digestType =
            $digestRecord["digestType"] == DigestModel::DIGEST_TYPE_IMMEDIATE ? "test" : $digestRecord["digestType"];
        $dateScheduled = $digestRecord["dateScheduled"]->format("Y-m-d");
        $utmParams = [
            "UTM_medium" => "email",
            "UTM_source" => "emaildigest",
            "UTM_content" => "{$digestType}digest" . $dateScheduled,
        ];
        return http_build_query($utmParams);
    }

    /**
     * Get users language preference for digest
     *
     * @param int $userID
     * @return string
     */
    public function getUserLanguagePreference(int $userID): string
    {
        $defaultSiteLanguage = $this->config->get("Garden.Locale", "en");
        if ($this->localeModel->hasMultiLocales()) {
            $preferenceKey = "Preferences." . UserNotificationPreferencesModel::PREFERENCE_USER_LANGUAGE;
            $userLanguagePreference = $this->userMetaModel->getUserMeta($userID, $preferenceKey, $defaultSiteLanguage)[
                $preferenceKey
            ];
            //Make sure the language the user chose is still active
            if ($this->notificationPreferencesApiController->validateLocale($userLanguagePreference)) {
                return $userLanguagePreference;
            }
            $this->logger->notice("The preferred language chose by user is not currently active.", [
                "UserID" => $userID,
                "Language Preference" => $userLanguagePreference,
                Logger::FIELD_TAGS => ["language preference"],
            ]);
        }
        return $defaultSiteLanguage;
    }

    /**
     * define and load the locale for translations
     *
     * @param string $locale
     * @return void
     */
    private function setLocale(string $locale): void
    {
        \Gdn::locale()->set($locale);
    }

    /**
     * Get the sites current default locale
     *
     * @return string
     */
    private function getDefaultLocale(): string
    {
        return $this->config->get("Garden.Locale", "en");
    }

    /**
     * Get a user's digest frequency preference. If the user has not set a preference, the default is used.
     *
     * @param $userID
     * @return string
     */
    private function getUserDigestFrequencyPreference($userID): string
    {
        $preferenceKey = UserNotificationPreferencesModel::USER_PREFERENCE_DIGEST_FREQUENCY_KEY;
        $defaultFrequency = $this->config->get(
            DigestModel::DEFAULT_DIGEST_FREQUENCY_KEY,
            DigestModel::DIGEST_TYPE_WEEKLY
        );
        return $this->userMetaModel->getUserMeta($userID, $preferenceKey, $defaultFrequency)[$preferenceKey];
    }
}
