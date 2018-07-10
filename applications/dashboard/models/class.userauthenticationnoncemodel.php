<?php
/**
 * @author Chris Chabilall chris.c@vanillaforums.com
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Handles user data and issuing and consuming nonces.
 */
class UserAuthenticationNonceModel extends Gdn_Model {

    use \Vanilla\PrunableTrait, \Vanilla\TokenSigningTrait;

    /** @var string Used to deteremine what type of token is generated. */
    protected static $tokenIdentifier = "nonce";

    const CONSUMED_TIMESTAMP = "1971-01-01 00:00:01";

    /**
     * Class constructor. Defines the related database table name.
     *
     * @param string $secret The secret used to sign access tokens for the client.
     */
    public function __construct($secret = '') {
        parent::__construct('UserAuthenticationNonce');
        $this->setPruneField('Timestamp');
        $this->setPruneAfter('45 minutes');
        $this->setSecret($secret);
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
        $result = parent::insert($fields);
        if (!empty($this->Database->LastInfo['RowCount'])) {
            $result = true;
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * Get a matching nonce from the UserAuthenticationNonce table.
     *
     * @param string $nonce The nonce to be looked up.
     * @param string $datasetType The datatype returned from the query.
     * @return array|bool
     */
    public function getNonce($nonce, $datasetType = DATASET_TYPE_ARRAY) {
        $row = $this->getWhere(['Nonce' => $nonce])->firstRow($datasetType);
        return $row;
    }

    /**
     * Issue a signed Nonce.
     *
     * @param string $expires The expiration time of the nonce.
     * @param string $type The type of the of token.
     * @return string $nonce The signed nonce.
     * @throws Gdn_UserException Unable to generate a signed nonce.
     */
    public function issue($expires = '5 minutes', $type = 'system'): string {

        $expireDate = Gdn_Format::toDateTime($this->toTimestamp($expires));
        $token = $this->randomToken();
        $nonce = $this->signToken($token, $expires);
        if (!$nonce) {
            throw new \Exception("Unable to generate Nonce", 500);
        }

        $result = $this->insert([
            'Nonce' => $nonce,
            'Token' => $type,
            'Timestamp' => $expireDate,
        ]);

        if (!$result) {
            throw new Gdn_UserException($this->Validation->resultsText(), 400);
        }

        return $nonce;
    }

    /**
     * Consumes the nonce and invalidates the timestamp so it can't be used again.
     *
     * @param string $nonce The nonce to be consumed.
     * @throws Exception Unable to find nonce.
     */
    public function consume(string $nonce) {
        $row = parent::getID($nonce, DATASET_TYPE_ARRAY);
        if ($row) {
            // The timestampo column is not nullable and the zero dates aren't allowed, so the date
            // is set to the Unix epoch time.
            parent::update(
                ['Timestamp' => self::CONSUMED_TIMESTAMP],
                ['Nonce' => $nonce]
            );
        } else {
            throw new \Exception("Unable to find Nonce", 500);
        }
    }

    /**
     * Verifies a nonce can be consumed and consumes it if the flag is true.
     *
     * @param string $nonce The nonce to verify.
     * @param bool $consume Flag to determine if the nonce is to be consumed.
     * @param bool $throw Whether or not to throw an exception on a verification error.
     * @return bool If the nonce is verified it returns true, false if otherwise.
     */
    public function verify(string $nonce = '', bool $consume = true, bool $throw = false): bool {
        // First verify the token without going to the database.
        if (!$this->verifyTokenSignature($nonce, self::$tokenIdentifier, $throw)) {
            return false;
        }

        $row = parent::getID($nonce, DATASET_TYPE_ARRAY);
        if (!$row) {
            return $this->tokenError('The nonce was not found in the database.', 401, $throw);
        }

        // Check the expiry date from the database.
        $dbExpires = $this->toTimestamp($row['Timestamp']);
        if ($dbExpires === self::CONSUMED_TIMESTAMP) {
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
