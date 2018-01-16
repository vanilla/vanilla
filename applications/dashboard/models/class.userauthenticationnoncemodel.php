<?php
/**
 * UserAuthenticationNonce model.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Handles user data.
 */
class UserAuthenticationNonceModel extends Gdn_Model {

    use \Vanilla\PrunableTrait;

    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('UserAuthenticationNonce');
        $this->setPruneField('Timestamp');
        $this->setPruneAfter('45 minutes');
    }

    /**
     * @inheritdoc
     */
    public function insert($fields) {
        $this->prune();

        if (!isset($fields['Timestamp'])) {
            $fields['Timestamp'] = date(MYSQL_DATE_FORMAT);
        }

        return parent::insert($fields);
    }
}
