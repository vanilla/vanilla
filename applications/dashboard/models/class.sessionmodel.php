<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

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
     * @param int $id
     * @return array|object
     * @throws Gdn_UserException
     */
    public function getActiveSession($id) {
        $row = $this->getID($id, DATASET_TYPE_ARRAY);

        if ($this->isExpired($row)) {
            throw new Gdn_UserException('Session expired, please try again.');
        }
        return $row;
    }
}
