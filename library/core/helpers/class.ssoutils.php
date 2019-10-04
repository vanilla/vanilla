<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Firebase\JWT\JWT;
use Garden\Web\Cookie;
use Vanilla\Logger;

/**
 * Class SsoUtils
 */
class SsoUtils {

    /**
     * State token time to live.
     */
    const STATE_TOKEN_TTL = 1200; // 20 minutes.

    /** Signing algorithm for JWT tokens. */
    const JWT_ALGORITHM = 'HS256';

    /** @var Cookie */
    private $cookie;

    /** @var string */
    private $cookieName;

    /** @var string */
    private $cookieSalt;

    /** @var Gdn_Session */
    private $session;

    /** @var string */
    private $stateToken;

    /** @var Logger  */
    private $logger;

    /**
     * SsoUtils constructor.
     */
    public function __construct(Gdn_Configuration $config, Cookie $cookie, Gdn_Session $session, ?\Psr\Log\LoggerInterface $logger = null) {
        $this->cookie = $cookie;
        $this->cookieName = $config->get('Garden.Cookie.Name', 'Vanilla').'-ssostatetoken';
        $this->cookieSalt = $config->get('Garden.Cookie.Salt');
        $this->session = $session;
        if ($logger === null) {
            $logger = Gdn::getContainer()->get(\Psr\Log\LoggerInterface::class);
        }
        $this->logger = $logger;

        if (!$this->cookieSalt) {
            throw new Gdn_UserException('Cookie salt is empty.');
        }
    }

    /**
     * Get a state token to verify on a subsequent request.
     *
     * @param bool $forceNew Force a new token to be generated.
     * @return A state token.
     */
    public function getStateToken($forceNew = false) {
        if ($this->stateToken === null || $forceNew) {
            $expiration = time() + self::STATE_TOKEN_TTL;
            $this->stateToken = betterRandomString(32);

            $payload = [
                'stateToken' => $this->stateToken,
                'exp' => $expiration,
            ];

            $jwt = JWT::encode($payload, $this->cookieSalt, self::JWT_ALGORITHM);

            $this->cookie->set($this->cookieName, $jwt, $expiration);
        }

        return $this->stateToken;
    }

    /**
     * Verify a state token.
     * This function will stash the token (using the context) so that it works with postbacks.
     * From there, trying to validate the token in a different context will fail.
     *
     * To explain this simply, if multiple sso plugins are in a page they will have all the same state token.
     * When trying to verify the state from an sso plugin the token becomes invalid for every other plugins but that one.
     * Furthermore, for plugins that offer multiple authentication actions (SignIn, Social Connect, ...), each separate
     * action should use a different context name.
     *
     * @param string $context Context defining where the verification happens.
     * @param $stateToken
     * @throws Gdn_UserException If the state token is invalid/expired.
     */
    public function verifyStateToken($context, $stateToken) {
        if (empty($stateToken)) {
            throw new Gdn_UserException(t('State token not found.'), 403);
        }

        $storedStateTokenData = null;
        $isStateTokenValid = false;
        try {
            $storedStateTokenData = $this->consumeStateToken($stateToken);
            // Stash the token in case we post back!
            $this->session->stash("{$context}StateToken", $storedStateTokenData);
            $isStateTokenValid = true;
        } catch (Exception $e){}

        if (!$storedStateTokenData) {
            $storedStateTokenData = $this->session->stash("{$context}StateToken", '', false);
            $isStateTokenValid = $this->isStateTokenValid($storedStateTokenData, $stateToken, 'stash');
        }

        if (!$isStateTokenValid) {
            throw new Gdn_UserException(t('Invalid/Expired state token.'), 400);
        }
    }

    /**
     * Consume a state token and return its data.
     *
     * @throws Exception
     * @param $stateToken
     * @return array The state token data.
     */
    protected function consumeStateToken($stateToken) {
        $stateTokenData = null;
        $jwt = $this->cookie->get($this->cookieName);
        if ($jwt) {
            try {
                $stateTokenData = (array)JWT::decode($jwt, $this->cookieSalt, [self::JWT_ALGORITHM]);
            } catch (Exception $e) {}
        }

        if (!$stateTokenData || empty($stateTokenData['stateToken'])) {
            throw new Exception(t('The state token could not be validated or is expired.'));
        }
        if (!$this->isStateTokenValid($stateTokenData, $stateToken, 'cookie')) {
            throw new Exception('The state token is invalid.');
        }

        $this->cookie->delete($this->cookieName);

        return $stateTokenData;
    }

    /**
     * Check if a state token against some state token data.
     *
     * @param array $stateTokenData
     * @param string $stateToken
     * @param string $source For logging purposes, optionally pass which token is being validated.
     * @return bool true if the data is valid and false otherwise.
     */
    protected function isStateTokenValid($stateTokenData, $stateToken, $source = '') {
        // Validate expected data.
        if (!is_array($stateTokenData)) {
            $this->logger->error('Missing stateTokenData', ['source' => $source]);
            return false;
        }

        // Validate it contains a stateToken.
        if (empty($stateTokenData['stateToken'])) {
            $this->logger->error('Missing stateToken from stateToken Array', ['source' => $source, 'stateTokenData' => $stateTokenData]);
            return false;
        }

        // Validate if exp exists.
        if (empty($stateTokenData['exp'])) {
            $this->logger->error('Missing Expiry from stateTokenArray', ['source' => $source, 'stateTokenData' => $stateTokenData]);
            return false;
        }

        // Check for expiration.
        if ($stateTokenData['exp'] < time()) {
            $this->logger->error('StateToken Expired', ['source' => $source, 'stateTokenExp' => $stateTokenData['exp'], 'time' => time()]);
            return false;
        }

        // Check the token.
        if ($stateToken !== $stateTokenData['stateToken']) {
            $this->logger->error('StateTokens do not match.', ['source' => $source, 'StoredStateToken' => $stateTokenData['stateToken'], 'RecievedStateToken' => $stateToken]);
            return false;
        }

        return true;
    }
}
