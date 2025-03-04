<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Digest;

use Vanilla\Dashboard\Models\UserNotificationPreferencesModel;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Models\PipelineModel;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerQuantityTotal;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Web\SystemCallableInterface;

/**
 * Model for the email digest.
 */
class DigestModel extends PipelineModel implements SystemCallableInterface
{
    public const DIGEST_TYPE_WEEKLY = "weekly";
    public const DIGEST_TYPE_DAILY = "daily";
    public const DIGEST_TYPE_MONTHLY = "monthly";
    public const DIGEST_TYPE_TEST_WEEKLY = "test-weekly";
    public const DIGEST_TYPE_IMMEDIATE = "immediate";

    public const DEFAULT_DIGEST_FREQUENCY_KEY = "Garden.Digest.DefaultFrequency";

    public const MONTHLY_DIGEST_WEEK_KEY = "Garden.Digest.Monthly.SetPosition";

    public const DEFAULT_MONTHLY_DIGEST_WEEK = "first";

    public const DIGEST_MONTHLY_WEEK_OPTIONS = ["first", "last"];

    public const DIGEST_TYPES = [
        self::DIGEST_TYPE_DAILY,
        self::DIGEST_TYPE_WEEKLY,
        self::DIGEST_TYPE_MONTHLY,
        self::DIGEST_TYPE_TEST_WEEKLY,
        self::DIGEST_TYPE_IMMEDIATE,
    ];
    public const DIGEST_FREQUENCY_OPTIONS = [
        self::DIGEST_TYPE_DAILY,
        self::DIGEST_TYPE_WEEKLY,
        self::DIGEST_TYPE_MONTHLY,
    ];

    public const AUTOSUBSCRIBE_DEFAULT_PREFERENCE = "Garden.Digest.Autosubscribe.Enabled";

    public const MANUAL_OPT_IN = 1;

    public const MANUAL_OPT_OUT = 0;

    public const AUTO_OPT_IN = 3;

    /**
     * D.I.
     *
     * @param UserNotificationPreferencesModel $userNotificationPreferencesModel
     */
    public function __construct(private UserNotificationPreferencesModel $userNotificationPreferencesModel)
    {
        parent::__construct("digest");
        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted"])->setUpdateFields([]);
        $this->addPipelineProcessor($dateProcessor);
    }

    /**
     * @inheritDoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["backfillOptInIterator"];
    }

    public static function structure(\Gdn_DatabaseStructure $structure)
    {
        // Table for getting storing digest data
        $structure
            ->table("digest")
            ->primaryKey("digestID")
            ->column("digestType", self::DIGEST_TYPES, null, "index")
            ->column("totalSubscribers", "int", null)
            ->column("dateInserted", "datetime")
            ->column("dateScheduled", "datetime", null, "index")
            ->set();
    }

    /**
     * Get the scheduled digest dates based on a limit
     *
     * @param int $limit
     * @return array
     */
    public function getRecentWeeklyDigestScheduledDates(int $limit = 10): array
    {
        return $this->getRecentDigestScheduleDatesByType(self::DIGEST_TYPE_WEEKLY, $limit);
    }

    /**
     * Get the recent digest scheduled dates by digest type
     *
     * @param string $digestType
     * @param int $limit
     * @return array
     * @throws \Exception
     */
    public function getRecentDigestScheduleDatesByType(string $digestType, int $limit = 10)
    {
        if (!in_array($digestType, self::DIGEST_TYPES)) {
            throw new \InvalidArgumentException("Invalid digest type");
        }
        $sql = $this->database->createSql();
        return $sql
            ->select("dateScheduled")
            ->from($this->getTableName())
            ->where("digestType", $digestType)
            ->orderBy("dateScheduled", "desc")
            ->limit($limit)
            ->get()
            ->column("dateScheduled");
    }

    /**
     * Check if a digest is scheduled for the date provided
     *
     * @param \DateTimeImmutable $dateTime
     * @param string $digestType
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function checkIfDigestScheduledForDay(
        \DateTimeImmutable $dateTime,
        string $digestType = self::DIGEST_TYPE_DAILY
    ): bool {
        $sql = $this->database->createSql();
        if (!in_array($digestType, self::DIGEST_TYPES)) {
            throw new \InvalidArgumentException("Invalid digest type");
        }
        return $sql
            ->select("digestID")
            ->from($this->getTableName())
            ->where("digestType", $digestType)
            ->where("date(dateScheduled)", $dateTime->format("Y-m-d"), false, true)
            ->get()
            ->value("digestID") !== null;
    }

    /**
     * Get email digest scheduled history
     *
     * @param int $limit
     * @return array
     */
    public function getWeeklyDigestHistory(int $limit = 5): array
    {
        return $this->select(
            ["digestType" => self::DIGEST_TYPE_WEEKLY],
            [
                self::OPT_SELECT => ["totalSubscribers", "dateScheduled"],
                self::OPT_ORDER => "dateScheduled",
                "orderDirection" => "desc",
                self::OPT_LIMIT => $limit,
            ]
        );
    }

    /**
     * Longrunner for automatically subscribing users to the digest who meet the criteria.
     *
     * @param \DateTimeImmutable $dateLastActive
     * @param int|null $maxUserID
     * @return \Generator
     */
    public function backfillOptInIterator(string $dateLastActive, ?int $maxUserID = 0): \Generator
    {
        $dateLastActive = new \DateTimeImmutable($dateLastActive);
        yield new LongRunnerQuantityTotal(function () use ($dateLastActive) {
            return $this->getUsersOptInCount($dateLastActive);
        });

        try {
            $usersToNotify = $this->getUsersOptInGenerator($dateLastActive, $maxUserID);

            foreach ($usersToNotify as $userID) {
                try {
                    $this->userNotificationPreferencesModel->save($userID, [
                        "Email.DigestEnabled" => self::AUTO_OPT_IN,
                    ]);
                    yield new LongRunnerSuccessID($userID);
                    $maxUserID = $userID;
                } catch (LongRunnerTimeoutException $e) {
                    throw $e;
                } catch (\Exception $e) {
                    yield new LongRunnerFailedID($userID, $e);
                }
            }
        } catch (LongRunnerTimeoutException $timeoutException) {
            return new LongRunnerNextArgs([$dateLastActive->format("Y-m-d"), $maxUserID]);
        }

        return LongRunner::FINISHED;
    }

    /**
     * Get the count of users who should be opted in to the digest.
     *
     * @param \DateTimeImmutable $dateLastActive
     * @return int
     */
    private function getUsersOptInCount(\DateTimeImmutable $dateLastActive): int
    {
        $sql = $this->database->createSql();
        $innerQuery = $sql
            ->select("u.UserID")
            ->from("User u")
            ->where("u.DateLastActive >=", $dateLastActive->format("Y-m-d H:i:s"))
            ->where("u.Deleted", 0)
            ->where("u.Banned", 0)
            ->beginWhereGroup()
            ->where("um.QueryValue <>", [
                "Preferences.Email.DigestEnabled." . self::AUTO_OPT_IN,
                "Preferences.Email.DigestEnabled." . self::MANUAL_OPT_IN,
                "Preferences.Email.DigestEnabled." . self::MANUAL_OPT_OUT,
            ])
            ->orWhere("um.QueryValue is NULL")
            ->join("UserMeta um", "u.UserID = um.UserID and um.Name = 'Preferences.Email.DigestEnabled'", "left")
            ->groupBy("u.UserID")
            ->getSelect(true);

        return $this->database
            ->createSql()
            ->select("count(uc.UserID) as Users")
            ->from("($innerQuery) as uc")
            ->get()
            ->value("Users");
    }

    /**
     * Get the users who should be opted in to the digest.
     *
     * @param \DateTimeImmutable $dateLastActive
     * @param int $maxUserID
     * @return \Generator
     */
    private function getUsersOptInGenerator(\DateTimeImmutable $dateLastActive, $maxUserID = 0): \Generator
    {
        $sql = $this->database->createSql();
        while (true) {
            $sql->select("u.UserID")
                ->from("User u")
                ->where("u.DateLastActive >=", $dateLastActive->format("Y-m-d H:i:s"))
                ->where("u.UserID >", $maxUserID)
                ->where("u.Deleted", 0)
                ->where("u.Banned", 0)
                ->beginWhereGroup()
                ->where("um.QueryValue <>", [
                    "Preferences.Email.DigestEnabled." . self::AUTO_OPT_IN,
                    "Preferences.Email.DigestEnabled." . self::MANUAL_OPT_IN,
                    "Preferences.Email.DigestEnabled." . self::MANUAL_OPT_OUT,
                ])
                ->orWhere("um.QueryValue is NULL")
                ->join("UserMeta um", "u.UserID = um.UserID and um.Name = 'Preferences.Email.DigestEnabled'", "left")
                ->groupBy("u.UserID")
                ->orderBy("u.UserID")
                ->limit(100);
            $result = $sql->get()->resultArray();

            if (empty($result)) {
                // No more users to process.
                return;
            }

            foreach ($result as $row) {
                yield $row["UserID"];
            }
        }
    }
}
