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

    use \Vanilla\PrunableTrait, \Vanilla\TokenSigningTrait;

    protected static $tokenIdentifier = "nonce";
    /**
     * Class constructor. Defines the related database table name.
     *
     * @param string $secret The secret used to sign access tokens for the client.
     */
    public function __construct($secret = '') {
        parent::__construct('UserAuthenticationNonce');
        $this->setPruneField('Timestamp');
        $this->setPruneAfter('45 minutes');
        $this->secret = $secret ?: c('Garden.Cookie.Salt');
        $this->PrimaryKey = 'Nonce';
    }

    /**
     * @inheritdoc
     */
    public function insert($fields) {
        $this->prune();

        if (!isset($fields['Timestamp'])) {
            $fields['Timestamp'] = date(MYSQL_DATE_FORMAT);
        }

        $this->encodeRow($fields);
        parent::insert($fields);
        if (!empty($this->Database->LastInfo['RowCount'])) {
            $result = $fields['Nonce'];
        } else {
            $result = false;
        }

        return $result;
    }
    /**
     * {@inheritdoc}
     */
    public function update($fields, $where = false, $limit = false) {
        $this->encodeRow($fields);
        $result = parent::update($fields, $where, $limit);
        return $result;
    }

    /**
     * Get an access token by its numeric ID.
     *
     * @param int $nonce
     * @param string $datasetType
     * @param array $options
     * @return array|bool
     */
    public function getNonce($nonce, $datasetType = DATASET_TYPE_ARRAY, $options = []) {
        $row = $this->getWhere(['Nonce' => $nonce])->firstRow($datasetType);
        return $row;
    }


    /**
     * @param string $expires
     * @param string $type
     * @return string
     * @throws Gdn_UserException
     */
    public function issue($expires = '5 minutes', $type = 'system') {
        if ($expires instanceof  DateTimeInterface) {
            $expireDate = $expires->format(MYSQL_DATE_FORMAT);
        } else {
            $expireDate = Gdn_Format::toDateTime($this->toTimestamp($expires));
        }

        $token = $this->randomToken();
        $nonce = $this->signToken($token, $expireDate);
        if (!$nonce) {
            throw new \Exception("Unable to generate Nonce", 500);
        }

        $token = $this->insert([
            'Nonce' => $nonce,
            'Token' => $type,
            'Timestamp' => $expireDate,
        ]);

        if (!$token) {
            throw new Gdn_UserException($this->Validation->resultsText(), 400);
        }

        return $nonce;
    }

    /**
     * @param $nonce
     */
    public function consume(string $nonce) {
        $row = $this->getNonce($nonce);
        if ($row) {
            $this->update(
                ['Timestamp' => Gdn_Format::toDateTime($this->toTimestamp('1971-01-01 00:00:01'))],
                ['Nonce' => $nonce]
            );
        }
    }

    /**
     * @param string $nonce
     * @param bool $consume
     * @param bool $throw
     * @return bool
     * @throws Exception
     */
    public function verify(string $nonce = '', bool $consume = true, bool $throw = false): bool {
        // First verify the token without going to the database.
        if (!$this->verifyTokenSignature($nonce, self::$tokenIdentifier, $throw)) {
            return false;
        }

        $row = $this->getNonce($nonce);
        if (!$row) {
            return $this->tokenError('The nonce was not found in the database.', 401, $throw);
        }

        // Check the expiry date from the database.
        $dbExpires = $this->toTimestamp($row['Timestamp']);
        if ($dbExpires === '1971-01-01 00:00:01') {
            return $this->tokenError('Nonce was already used.', 401, $throw);
        } elseif ($dbExpires < time()) {
            return $this->tokenError('Nonce has expired.', 401, $throw);
        }

        if ($consume) {
            $this->consume($nonce);
        }

        return true;
    }

}
