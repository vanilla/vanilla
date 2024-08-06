<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Web\Middleware;

use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;

/**
 * Applies a specific header which causes Cloudflare to issue a challenge for new unverified users.
 */
class CloudflareChallengeMiddleware
{
    public const CF_CHALLENGE_HEADER = ["x-cf-challenge", "managed"];

    private const CONF_CHALLENGE_NEW_USERS = "premoderation.challengeNewUsers";

    private const SEVEN_DAYS_IN_SECONDS = 60 * 60 * 24 * 7;

    public function __construct(private \Gdn_Session $session, private ConfigurationInterface $config)
    {
    }

    /**
     * Conditionally applies a header to the response.
     *
     * @param RequestInterface $request
     * @param callable $next
     * @return Data
     */
    public function __invoke(RequestInterface $request, callable $next)
    {
        $response = Data::box($next($request));
        if ($this->shouldUserReceiveChallenge()) {
            $response->setHeader(...self::CF_CHALLENGE_HEADER);
        }
        return $response;
    }

    /**
     * Returns true when the following conditions are met:
     * - the setting is enabled,
     * - a user is signed in,
     * - the user account is unverified,
     * - the user account doesn't have the `community.moderate` or `site.manage` permissions,
     * - and the user account is less than 7 days old.
     *
     * @return bool
     */
    public function shouldUserReceiveChallenge(): bool
    {
        if (!$this->config->get(self::CONF_CHALLENGE_NEW_USERS)) {
            return false;
        }
        if (!$this->session->isValid()) {
            return false;
        }
        if ($this->session->User->Verified ?? false) {
            return false;
        }
        if ($this->session->checkPermission(["community.moderate", "site.manage"], false)) {
            return false;
        }
        try {
            $dateRegistered = new \DateTimeImmutable($this->session->User->DateInserted ?? "now");
            if (CurrentTimeStamp::getCurrentTimeDifference($dateRegistered) > self::SEVEN_DAYS_IN_SECONDS) {
                return false;
            }
        } catch (\Throwable $e) {
            // The user object should have a proper date so this shouldn't happen.
            // Just in case it does, let's allow the challenge to be issued.
        }

        return true;
    }
}
