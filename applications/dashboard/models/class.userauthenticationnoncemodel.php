<?php
/**
 * @author Chris Chabilall chris.c@vanillaforums.com
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Handles user data and issuing and consuming nonces.
 */
class UserAuthenticationNonceModel extends Gdn_Model {

    use \Vanilla\PrunableTrait;
    use \Vanilla\TokenSigningTrait;

    /**
     * The timestamp of a consumed nonce.
     */
    const CONSUMED_TIMESTAMP = "1971-01-01 00:00:01";

    /**
     * Class constructor. Defines the related database table name.
     *
     * @param string $secret The secret used to sign access tokens for the client.
     */
    public function __construct($secret = null) {
        parent::__construct('UserAuthenticationNonce');
        $this->setPruneField('Timestamp');
        $this->setPruneAfter('45 minutes');
        $secret = $secret ?: c('Garden.Cookie.Salt');
        $this->setSecret($secret);
        $this->PrimaryKey = 'Nonce';
        $this->tokenIdentifier ='nonce';
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

        return $result;
    }

    /**
     * Issue a signed Nonce.
     *
     * @param string $expires The expiration time of the nonce.
     * @param string $type The type of nonce.
     * @return string $nonce The signed nonce.
     * @throws Gdn_UserException Unable to generate a signed nonce.
     */
    public function issue($expires = '5 minutes', $type = 'system'): string {
        $token = $this->randomToken();
        $nonce = $this->signToken($token, $expires);
        $expireDate = Gdn_Format::toDateTime($this->toTimestamp($expires));

        $result = $this->insert([
            'Nonce' => $nonce,
            'Token' => $type,
            'Timestamp' => $expireDate,
        ]);

        if ($result === false) {
            throw new Gdn_UserException($this->Validation->resultsText(), 400);
        }
        return $nonce;
    }

    /**
     * Consumes the nonce and invalidates the timestamp so it can't be used again.
     *
     * @param string $nonce The nonce to be consumed.
     * @throws Exception if unable to find nonce.
     */
    public function consume(string $nonce) {
        $row = $this->getID($nonce, DATASET_TYPE_ARRAY);
        if ($row) {
            // Timestamp cannot be null or zero. Use a constant date for consumed nonces.
            $this->update(
                ['Timestamp' => self::CONSUMED_TIMESTAMP],
                ['Nonce' => $nonce]
            );
        } else {
            throw new \Exception("Unable to find nonce", 500);
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
        if (!$this->verifyTokenSignature($nonce, $throw)) {
            return false;
        }

        $row = $this->getID($nonce, DATASET_TYPE_ARRAY);
        if (!$row) {
            return $this->tokenError('The nonce was not found in the database.', 401, $throw);
        }

        // Check the expiry date from the database.
        $dbExpires = $this->toTimestamp($row['Timestamp']);
        if ($dbExpires === $this->toTimestamp(self::CONSUMED_TIMESTAMP)) {
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
