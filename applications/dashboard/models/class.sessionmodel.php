<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Utility\ModelUtils;

/**
 * Class SessionModel
 */
class SessionModel extends Gdn_Model {
    use \Vanilla\PrunableTrait;

    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('Session');
        $this->setPruneField('DateExpires');
        $this->setPruneAfter('45 minutes');
    }

    /**
     * Used by startSession to create & manage sessions for users & guests.     *
     *
     * @param int $userID user ID for creating session.
     *
     * @return bool|array Current session.
     * @throws \Garden\Schema\ValidationException Exception when insert fails.
     */
    public function startNewSession(int $userID) {
        $sessionName = 'sid';
        // Grab the entire session record.
        $sessionID = Gdn::authenticator()->identity()->getAttribute($sessionName);

        $session = $this->getID($sessionID, DATASET_TYPE_ARRAY);

        if (!$session) {
            $session = [
                'UserID' => $userID,
                'DateInserted' => date(MYSQL_DATE_FORMAT),
                'DateExpires' => date(MYSQL_DATE_FORMAT, time() + Gdn_Session::VISIT_LENGTH),
                'Attributes' => [],
            ];

            // Save the session information to the database.
            $sessionID = $this->insert($session);
            ModelUtils::validationResultToValidationException($this);
            $session['SessionID'] = $sessionID;
            trace("Inserting session stash $sessionID");

            // Save a session cookie.
            $path = c('Garden.Cookie.Path', '/');
            $domain = c('Garden.Cookie.Domain', '');
            $expire = 0;

            // If the domain being set is completely incompatible with the
            // current domain then make the domain work.
            $currentHost = Gdn::request()->host();
            if (!stringEndsWith($currentHost, trim($domain, '.'))) {
                $domain = '';
            }
        }

        return $session;
    }

    /**
     * @inheritdoc
     */
    public function insert($fields) {
        $this->prune();

        if (!isset($fields['DateInserted'])) {
            $fields['DateInserted'] = date(MYSQL_DATE_FORMAT);
        }
        if (!isset($fields['SessionID'])) {
            $fields['SessionID'] = betterRandomString(12);
        }

        $r = parent::insert($fields);
        if ($r !== false) {
            $r = $fields['SessionID'];
        }
        return $r;
    }

    /**
     * @inheritdoc
     */
    public function update($fields, $where = false, $limit = false) {
        if (!isset($fields['DateUpdated'])) {
            $fields['DateUpdated'] = date(MYSQL_DATE_FORMAT);
        }

        parent::update($fields, $where, $limit);
    }

    /**
     * Refresh expiration of the session.
     *
     * @param string $sessionID session ID of the current active session.
     *
     * @return bool status of refresh
     */
    public function refreshSession(string $sessionID): bool {
        if ($this->isExpired($sessionID)) {
            return false;
        }

        $fields = ['DateExpires' => date(MYSQL_DATE_FORMAT, time() + Gdn_Session::VISIT_LENGTH),
                   'DateUpdated' => date(MYSQL_DATE_FORMAT)];

        $where =  ['SessionID' => $sessionID];

        parent::update($fields, $where);
        return true;
    }

    /**
     * Expire expiration of the session.
     *
     * @param string $sessionID session ID of the current active session.
     */
    public function expireSession(string $sessionID) {
        $where =  ['SessionID' => $sessionID];
        parent::delete($where);
    }

    /**
     * Tells whether a session is expired or not.
     *
     * @param array|string $session Session object or SessionID
     * @return bool
     */
    public function isExpired($session) {
        if (is_string($session)) {
            $session = $this->getID($session, DATASET_TYPE_ARRAY);
        }

        if (!is_array($session)) {
            return true;
        }

        // If the date expires is null then it never expires.
        if ($session['DateExpires'] === null) {
            return false;
        }

        $time = strtotime($session['DateExpires']);
        if ($time && $time < time()) {
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
    public function getActiveSession(string $id) {
        $row = $this->getID($id, DATASET_TYPE_ARRAY);

        if ($this->isExpired($row)) {
            throw new Gdn_UserException('Session expired, please try again.', 401);
        }
        return $row;
    }
}
