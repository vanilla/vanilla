<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerQuantityTotal;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Web\SystemCallableInterface;

/**
 * Class for updating user ranks in bulk.
 */
class BanUserCountGenerator implements SystemCallableInterface
{
    const LOCK_KEY = "banUserCountUpdate";
    const BUCKET_SIZE = 50;

    /** @var BanModel */
    private $banModel;

    /** @var UserModel */
    private $userModel;

    /** @var \Gdn_Cache */
    private $cache;

    /**
     * DI.
     *
     * @param UserModel $userModel
     * @param BanModel $banModel
     * @param \Gdn_Cache $cache
     */
    public function __construct(UserModel $userModel, BanModel $banModel, \Gdn_Cache $cache)
    {
        $this->userModel = $userModel;
        $this->banModel = $banModel;
        $this->cache = $cache;
    }

    /**
     * @inheritDoc
     */
    public static function getSystemCallableMethods(): array
    {
        return ["processNewUserBans", "processUserUnBans"];
    }

    /**
     * Apply new Ban rule on all matching users.
     *
     * @param int|null $lastRecordID
     * @param string|null $cacheLockCombo
     * @param array|null $banData
     *
     * @return Generator
     */
    public function processUserBans(
        ?int $lastRecordID = null,
        ?string $cacheLockCombo = null,
        ?array $banData = []
    ): Generator {
        $lastRecordID = $lastRecordID ?? 0;
        $lockKey = $banData["BanID"] ?? 0;
        $stealableLock = new \StealableLock($this->cache, self::LOCK_KEY . "Apply{$lockKey}");
        $where = $this->banModel->banWhere($banData);

        // Yield total users to ban.
        yield new LongRunnerQuantityTotal([$this, "getTotalCount"], [$where]);
        try {
            if (isset($cacheLockCombo)) {
                $stealableLock->refresh($cacheLockCombo);
            } else {
                $cacheLockCombo = $stealableLock->steal();
            }
            try {
                $usersToProcess = $this->getUsersToBanIterator($where, $lastRecordID);

                foreach ($usersToProcess as $user) {
                    $lastRecordID = $user["UserID"];
                    try {
                        if (BanModel::isBanned($user["Banned"], BanModel::BAN_AUTOMATIC)) {
                            continue;
                        }
                        $this->banModel->saveUser($user, true, $banData);
                        yield new LongRunnerSuccessID($user["UserID"]);
                    } catch (LongRunnerTimeoutException $e) {
                        throw $e;
                    } catch (\Exception $exception) {
                        yield new LongRunnerFailedID($user["UserID"]);
                    }
                }
            } catch (LongRunnerTimeoutException $timeoutException) {
                return new LongRunnerNextArgs([$lastRecordID, $cacheLockCombo, $banData]);
            }
        } catch (\LockStolenException $lockE) {
            $stealableLock->release();
            return LongRunner::FINISHED;
        }
        $stealableLock->release();
        return LongRunner::FINISHED;
    }

    /**
     * Un-apply old Ban rule on previously matching users.
     *
     * @param int|null $lastRecordID
     * @param string|null $cacheLockCombo
     * @param array|null $oldBanData
     * @param array|null $newBanData
     *
     * @return Generator
     */
    public function processUserUnBans(
        ?int $lastRecordID = null,
        ?string $cacheLockCombo = null,
        ?array $newBanData = [],
        ?array $oldBanData = []
    ): Generator {
        $lastRecordID = $lastRecordID ?? 0;
        $lockKey = $oldBanData["BanID"] ?? 0;
        $stealableLock = new \StealableLock($this->cache, self::LOCK_KEY . "Undo{$lockKey}");
        try {
            if (isset($cacheLockCombo)) {
                $stealableLock->refresh($cacheLockCombo);
            } else {
                $cacheLockCombo = $stealableLock->steal();
            }
            // Yield total users to process.
            yield new LongRunnerQuantityTotal(function () use ($oldBanData) {
                return $oldBanData["CountUsers"];
            });

            try {
                $where = $this->banModel->banWhere($oldBanData);
                $newWhere =
                    $newBanData == null
                        ? $where
                        : array_merge($where, $this->banModel->banWhere($newBanData, "", true));

                $newWhere["Banned"] = 2;
                $usersToProcess = $this->getUsersToBanIterator($newWhere, $lastRecordID);

                foreach ($usersToProcess as $user) {
                    $lastRecordID = $user["UserID"];
                    try {
                        $this->banModel->saveUser($user, false, $newBanData);
                        yield new LongRunnerSuccessID($user["UserID"]);
                    } catch (LongRunnerTimeoutException $e) {
                        throw $e;
                    } catch (\Exception $exception) {
                        yield new LongRunnerFailedID($user["UserID"]);
                    }
                }
            } catch (LongRunnerTimeoutException $timeoutException) {
                return new LongRunnerNextArgs([$lastRecordID, $cacheLockCombo, $newBanData, $oldBanData]);
            }
        } catch (\LockStolenException $lockE) {
            $stealableLock->release();
            return LongRunner::FINISHED;
        }
        $stealableLock->release();
        return LongRunner::FINISHED;
    }

    /**
     * Get data of users that will need to be banned
     *
     * @param array $where
     * @param int $lastUserID
     * @return Generator
     * @throws \Exception
     */
    private function getUsersToBanIterator(array $where, int $lastUserID): Generator
    {
        while (true) {
            $where["UserID>"] = $lastUserID;
            $userSQL = $this->userModel->createSql();
            $sql = $userSQL
                ->from($this->userModel->Name)
                ->select("UserID, Banned")
                ->where($where, null, false)
                ->limit(100)
                ->orderBy("UserID", "asc")
                ->getSelect();
            $users = $userSQL->query($sql)->resultArray();
            if (empty($users)) {
                // No more left.
                return;
            }

            foreach ($users as $user) {
                $lastUserID = $user["UserID"];
                yield $lastUserID => $user;
            }
        }
    }

    /**
     * Get data of users that will need to be banned
     *
     * @param array $where
     * @return int
     * @throws Exception
     */
    public function getTotalCount(array $where): int
    {
        $this->userModel->SQL->select("*", "count", "Count")->from($this->userModel->Name);
        $this->userModel->SQL->where($where, null, false);

        $data = $this->userModel->SQL->get()->firstRow();

        return $data === false ? 0 : $data->Count;
    }
}
