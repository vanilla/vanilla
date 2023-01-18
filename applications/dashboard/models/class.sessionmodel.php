<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Ramsey\Uuid\Uuid;
use Vanilla\CurrentTimeStamp;
use Vanilla\Utility\ModelUtils;
use Psr\SimpleCache\CacheInterface;

/**
 * Class SessionModel
 */
class SessionModel extends Gdn_Model
{
    use \Vanilla\PrunableTrait;

    public const REFRESH_REFRESHED = "refreshed";
    public const REFRESH_NO_REFRESH = "no-refresh";
    public const REFRESH_EXPIRED = "expired";

    /** @var string Cache key for session. */
    private const SESSION_CACHE_KEY = "session/%s";

    /** @var int Cache time for session object. */
    private const SESSION_COUNT_TTL = 600; // 10 minutes.

    /** @var CacheInterface */
    private $cache;

    /**
     * Class constructor. Defines the related database table name.
     *
     */
    public function __construct()
    {
        parent::__construct("Session");
        $this->setPruneField("DateExpires");
        $this->setPruneAfter("45 minutes");
        $this->cache = GDN::getContainer()->get(CacheInterface::class);
    }

    /**
     * Gets persistExpiry and converts it to time in Unix timestamp.
     *
     * @return DateTimeImmutable
     */
    public function getPersistExpiry(): DateTimeImmutable
    {
        return CurrentTimeStamp::getDateTime()->modify("+" . \Gdn::config("Garden.Cookie.PersistExpiry"));
    }

    /**
     * Used by startSession to create & manage sessions for users & guests.     *
     *
     * @param int $userID user ID for creating session.
     * @param string|null $sessionID Session ID to use for the new session.
     *
     * @return bool|array Current session.
     * @throws \Garden\Schema\ValidationException Exception when insert fails.
     */
    public function startNewSession(int $userID, string $sessionID = null)
    {
        $sessionName = "sid";
        // Grab the entire session record.
        $tempSessionID = Gdn::authenticator()
            ->identity()
            ->getAttribute($sessionName);

        $session = $this->getID($sessionID ?? $tempSessionID, DATASET_TYPE_ARRAY);
        if (!$session) {
            $session = [
                "UserID" => $userID,
                "DateInserted" => CurrentTimeStamp::getMySQL(),
                "DateExpires" => $this->getPersistExpiry()->format(CurrentTimeStamp::MYSQL_DATE_FORMAT),
                "Attributes" => [],
            ];
            if ($sessionID != null) {
                $session["SessionID"] = $sessionID;
            }

            // Save the session information to the database.
            $sessionID = $this->insert($session);
            ModelUtils::validationResultToValidationException($this);
            $session["SessionID"] = $sessionID;
            trace("Inserting session stash $sessionID");

            // Save a session cookie.
            $path = c("Garden.Cookie.Path", "/");
            $domain = c("Garden.Cookie.Domain", "");
            $expire = 0;

            // If the domain being set is completely incompatible with the
            // current domain then make the domain work.
            $currentHost = Gdn::request()->host();
            if (!stringEndsWith($currentHost, trim($domain, "."))) {
                $domain = "";
            }
        } elseif ($session["UserID"] == 0 && $userID > 0) {
            $this->update(["UserID" => $userID], ["SessionID" => $sessionID]);
            $session = $this->getID($sessionID ?? $tempSessionID, DATASET_TYPE_ARRAY);
        }

        return $session;
    }

    /**
     * Get a particular session record.
     *
     * @param int $id Unique ID of session.
     * @param bool|string $datasetType The format of the resulting data.
     * @param array $options Not used.
     * @return array|object A single SQL result.
     */
    public function getID($id, $datasetType = false, $options = [])
    {
        $key = sprintf(self::SESSION_CACHE_KEY, $id);

        $cached = $this->cache->get($key, null);
        if ($cached !== null) {
            return $cached;
        }
        $session = parent::getID($id, $datasetType);
        if ($session != false) {
            $this->cache->set($key, $session, self::SESSION_COUNT_TTL);
        }
        return $session;
    }

    /**
     * @inheritdoc
     */
    public function insert($fields)
    {
        $this->prune();

        if (!isset($fields["DateInserted"])) {
            $fields["DateInserted"] = CurrentTimeStamp::getMySQL();
        }
        if (!isset($fields["SessionID"])) {
            // generate UUID strip dashes.
            $fields["SessionID"] = str_replace("-", "", Uuid::uuid1()->toString());
        }

        $r = parent::insert($fields);
        if ($r !== false) {
            $r = $fields["SessionID"];
        }
        return $r;
    }

    /**
     * @inheritdoc
     */
    public function update($fields, $where = false, $limit = false)
    {
        if (!isset($fields["DateUpdated"])) {
            $fields["DateUpdated"] = CurrentTimeStamp::getMySQL();
        }

        parent::update($fields, $where, $limit);

        $sessionID = val("SessionID", $where);
        if ($sessionID) {
            $this->clearCache($sessionID);
        }
    }

    /**
     * Refresh expiration of the session.
     *
     * @param string $sessionID session ID of the current active session.
     *
     * @return string One of the SessionModel::REFRESH_* constants.
     */
    public function refreshSession(string $sessionID): string
    {
        if ($this->isExpired($sessionID)) {
            return self::REFRESH_EXPIRED;
        }
        $session = $this->getID($sessionID, DATASET_TYPE_ARRAY);
        $halfTime = ($this->getPersistExpiry()->getTimestamp() - CurrentTimeStamp::get()) / 2;
        if (strtotime($session["DateExpires"]) < CurrentTimeStamp::get() + $halfTime) {
            $fields = [
                "DateExpires" => $this->getPersistExpiry()->format(CurrentTimeStamp::MYSQL_DATE_FORMAT),
                "DateUpdated" => CurrentTimeStamp::getMySQL(),
            ];

            $where = ["SessionID" => $sessionID];

            parent::update($fields, $where);
            $this->clearCache($sessionID);
            return self::REFRESH_REFRESHED;
        }
        return self::REFRESH_NO_REFRESH;
    }

    /**
     * Expire a user's sessions by userID and an optional sessionID.
     *
     * @param int $userID User ID.
     * @param string|null $sessionID Session ID.
     * @return int|false Returns the number of deleted records or **false** on failure.
     */
    public function expireUserSessions(int $userID, string $sessionID = null)
    {
        $where["UserID"] = $userID;
        if ($sessionID) {
            $where["SessionID"] = $sessionID;
        }
        $sessions = $this->getWhere($where)->resultArray();
        if ($sessions != null) {
            foreach ($sessions as $session) {
                $this->clearCache($session["SessionID"]);
            }
        }
        return parent::delete($where);
    }

    /**
     * Expire a session by sessionID.
     *
     * @param string $sessionID session ID of the current active session.
     * @return int|false Returns the number of deleted records or **false** on failure.
     */
    public function expireSession(string $sessionID)
    {
        $where = ["SessionID" => $sessionID];
        $this->clearCache($sessionID);
        return parent::delete($where);
    }

    /**
     * Tells whether a session is expired or not.
     *
     * @param array|string $session Session object or SessionID
     * @return bool
     */
    public function isExpired($session)
    {
        if (is_string($session)) {
            $session = $this->getID($session, DATASET_TYPE_ARRAY);
        }

        if (!is_array($session)) {
            return true;
        }

        // If the date expires is null then it never expires.
        if ($session["DateExpires"] === null) {
            return false;
        }

        $time = strtotime($session["DateExpires"]);
        if ($time && $time < CurrentTimeStamp::get()) {
            return true;
        }

        return false;
    }

    /**
     * Get a row from the sessions table that has not expired.
     *
     * @param string $id SessionID, primary key for storing session data.
     * @return array Row from Session table.
     * @throws Gdn_UserException Error thrown when sesssion is expired.
     */
    public function getActiveSession(string $id)
    {
        $row = $this->getID($id, DATASET_TYPE_ARRAY);

        if ($this->isExpired($row)) {
            throw new Gdn_UserException("Session expired, please try again.", 401);
        }
        return $row;
    }

    /**
     * Get a list of sessions, optionally filtered by `valid` or `invalid` sessions.
     *
     * @param int $userID user of the sessionID
     * @param string $filter
     * @return array
     */
    public function getSessions(int $userID, string $filter = ""): array
    {
        $currentTimeStamp = CurrentTimeStamp::getDateTime()->format(\DateTime::ATOM);
        switch ($filter) {
            case "invalid":
                $sessions = $this->SQL
                    ->select("*")
                    ->from("Session")
                    ->where("userID", $userID)
                    ->where("DateExpires <=", $currentTimeStamp)
                    ->orWhere("DateExpires", null)
                    ->get()
                    ->resultArray();
                break;
            case "valid":
                $where["DateExpires >"] = $currentTimeStamp;
                $where["UserID"] = $userID;
                $sessions = $this->getWhere($where)->resultArray();
                break;
            default:
                $where["UserID"] = $userID;
                $sessions = $this->getWhere($where)->resultArray();
                break;
        }
        return $sessions;
    }

    /**
     * Check if a session exists.
     *
     * @param int $userID
     * @param string|null $sessionID
     * @return bool
     */
    public function sessionExists(int $userID, string $sessionID = null): bool
    {
        $queryParams = ["UserID" => $userID];
        if ($sessionID) {
            $queryParams["SessionID"] = $sessionID;
        }
        $sessionLookup = $this->getWhere($queryParams)->resultArray();
        return count($sessionLookup) > 0;
    }

    /**
     * Clear cache for the sessionID.
     *
     * @param string $sessionID sessionID to use to clear cache.
     */
    private function clearCache(string $sessionID): void
    {
        $key = sprintf(self::SESSION_CACHE_KEY, $sessionID);
        $this->cache->delete($key);
    }
}
