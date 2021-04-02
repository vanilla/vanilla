<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Garden\EventManager;
use Garden\Events\BulkUpdateEvent;
use Psr\SimpleCache\CacheInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\Formatting\DateTimeFormatter;

/**
 * Model for updating visit information.
 */
class UserVisitUpdater {

    const CACHE_KEY_LAST_UPDATE_TIMESTAMP = 'userVisitLastBulkUpdateTimestamp';
    const BULK_DISPATCH_DELAY = 300; // 5 minutes
    const BULK_DISPATCH_OVERLAP = 60; // 1 minute.

    /** @var \UserModel */
    private $userModel;

    /** @var \BanModel */
    private $banModel;

    /** @var \Gdn_Session */
    private $session;

    /** @var EventManager */
    private $eventManager;

    /** @var CacheInterface */
    private $cache;

    /**
     * DI.
     *
     * @param \UserModel $userModel
     * @param \BanModel $banModel
     * @param \Gdn_Session $session
     * @param EventManager $eventManager
     * @param CacheInterface $cache
     */
    public function __construct(
        \UserModel $userModel,
        \BanModel $banModel,
        \Gdn_Session $session,
        EventManager $eventManager,
        CacheInterface $cache
    ) {
        $this->userModel = $userModel;
        $this->banModel = $banModel;
        $this->session = $session;
        $this->eventManager = $eventManager;
        $this->cache = $cache;
    }

    /**
     * Updates visit level information such as date last active and the user's ip address.
     *
     * @param int $userID The user ID to update.
     * @param null|int|float $clientHour
     *
     * @throws \Exception If the user ID is not valid.
     * @return bool True on success, false if the user is banned or deleted.
     */
    public function updateVisit(int $userID, $clientHour = null) {
        if (!$userID) {
            throw new \Exception('A valid User ID is required.');
        }

        $user = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);

        if ($user['Banned'] || $user['Deleted']) {
            // Do not update visit information if the user is banned or deleted.
            return false;
        }

        $fields = [];

        if ($user['DateLastActive'] && DateTimeFormatter::dateTimeToTimeStamp($user['DateLastActive']) < strtotime('5 minutes ago')) {
            // We only update the last active date once every 5 minutes to cut down on DB activity.
            $fields['DateLastActive'] = DateTimeFormatter::timeStampToDateTime(CurrentTimeStamp::get());
        }

        // Update session level information if necessary.
        if ($userID == $this->session->UserID) {
            $ip = \Gdn::request()->getIP();
            $fields['LastIPAddress'] = ipEncode($ip);

            if ($this->session->newVisit()) {
                $fields['CountVisits'] = val('CountVisits', $user, 0) + 1;
                $this->userModel->fireEvent('Visit');
            }
        }

        // Set the hour offset based on the client's clock.
        if (is_numeric($clientHour) && $clientHour >= 0 && $clientHour < 24) {
            $hourOffset = $clientHour - date('G', time());
            $fields['HourOffset'] = $hourOffset;
        }

        // See if the fields have changed.
        $set = [];
        foreach ($fields as $key => $value) {
            if (($user[$key] ?? null) !== $value) {
                $set[$key] = $value;
            }
        }

        if (!empty($set)) {
            $this->userModel->EventArguments['Fields'] = &$set;
            $this->userModel->fireEvent('UpdateVisit');
            $this->userModel->setField($userID, $set);
            $this->tryDispatchBulkVisitUpdate();
        }

        if ($user['LastIPAddress'] ?? null !== $fields['LastIPAddress'] ?? null) {
            // Refetch user with latest updated data.
            $user = $this->userModel->getID($userID, DATASET_TYPE_ARRAY);
            if (!$this->banModel::checkUser($user, null, true, $bans)) {
                // User is freshly banned. Update the user record.
                $ban = array_pop($bans);
                $this->banModel->saveUser($user, true, $ban);
                $this->banModel->setCounts($ban);
            }
        }

        return true;
    }

    /**
     * Try to dispatch a bulk visit update event for all users updated within a recent timespan.
     */
    public function tryDispatchBulkVisitUpdate() {
        $lastUpdateTimestamp = $this->cache->get(
            self::CACHE_KEY_LAST_UPDATE_TIMESTAMP,
            null
        );

        $updateFrom = null;
        if ($lastUpdateTimestamp === null) {
            // No cache key found.
            // It might have experired or been cleared or pushed out so lets queue an update anyways.
            $updateFrom = CurrentTimeStamp::get() - self::BULK_DISPATCH_DELAY - self::BULK_DISPATCH_OVERLAP;
        } else {
            $timeDifference = CurrentTimeStamp::get() - $lastUpdateTimestamp;

            if ($timeDifference > self::BULK_DISPATCH_DELAY) {
                $updateFrom = $lastUpdateTimestamp - self::BULK_DISPATCH_OVERLAP;
            }
        }

        if ($updateFrom !== null) {
            // Write the cache key so that everything after gets queued.
            $this->cache->set(self::CACHE_KEY_LAST_UPDATE_TIMESTAMP, CurrentTimeStamp::get());

            $userIDs = $this->userModel->getLastActiveUserIDs(DateTimeFormatter::timeStampToDateTime($updateFrom));
            if (count($userIDs) === 0) {
                return;
            }

            $currentTime = CurrentTimeStamp::get();
            $this->eventManager->dispatch(new BulkUpdateEvent(
                'user',
                [
                    'userID' => $userIDs,
                ],
                [
                    'dateLastActive' => (new \DateTime("@$currentTime"))->format(\DateTime::RFC3339),
                ]
            ));
        }
    }
}
