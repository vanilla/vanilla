<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Asset;

/**
 * A cache buster that works based on the last deployment time.
 *
 * Settings the config value `Garden.Deployed` to the current unix timestamp on a deploy
 * will allow this cache buster to work properly.
 *
 * The grace period here is prevent the following scenario:
 *
 * 1. Browser A requests pageY
 * 2. Servers 1 & 2 deploy.
 * 3. Server 1 returns pageY which has a scriptZ using the new cache-buster. Deploy is completed on server1.
 * 4. Browser A requests scriptZ.
 * 5. Load-balancer sends the request to Server 2. Server 2 is not finished deploying.
 * 6. Server2 serves the old scriptZ with the new cache-buster.
 * 7. Caching server caches this results.
 * 8. Users B-Z all receive the old asset with the new cache buster.
 *
 * The grace period adds a step:
 * 7a. After the grace period has elapsed,
 *     increment the timestamp for the grace period by the duration of the grace period.
 *
 * EVENTUALLY (after the grace period has completed) we get a fresh start and everything should be consistent.
 * In the intermediary time it is possible a user gets served an old script.
 */
class DeploymentCacheBuster {

    /**
     * The number of seconds to wait after a deploy before switching the cache buster.
     */
    const GRACE_PERIOD = 90;

    /** @var \DateTimeInterface */
    private $currentTime;

    /** @var int|null */
    private $deploymentTime;

    /** @var int|null */
    private $gracedDeploymentTime;

    /**
     * DeploymentCacheBuster constructor.
     *
     * @param \DateTimeInterface $currentTime
     * @param int|null $deploymentTime
     */
    public function __construct(\DateTimeInterface $currentTime, $deploymentTime) {
        $this->currentTime = $currentTime;
        $this->deploymentTime = $deploymentTime;
        if ($this->deploymentTime) {
            $this->gracedDeploymentTime = $this->deploymentTime + self::GRACE_PERIOD;
        }
    }

    /**
     * Get a cache buster string using the last deployment time.
     *
     * - Allows a grace period after the deployment before updating the query strings.
     * - Falls back to application version if there is no last deployment time.
     *
     * @return string
     */
    public function value(): string {
        if ($this->deploymentTime) {
            $isBeyondGracePeriod = $this->currentTime->getTimestamp() >= $this->gracedDeploymentTime;
            $deploymentTime = $isBeyondGracePeriod ? $this->gracedDeploymentTime : $this->deploymentTime;
            $result = dechex($deploymentTime);
        } else {
            $result = APPLICATION_VERSION;
        }

        return $result;
    }
}
