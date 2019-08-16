<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Asset;

/**
 * A cache buster that works based on the last deployment time.
 * If that deployment time is not available it will fall back to the application version.
 */
class DeploymentCacheBuster {

    /** @var int|null */
    private $deploymentTime;
    /**
     * DeploymentCacheBuster constructor.
     *
     * @param int|null $deploymentTime
     */
    public function __construct($deploymentTime) {
        $this->deploymentTime = $deploymentTime;
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
            $result = dechex($this->deploymentTime);
        } else {
            $result = APPLICATION_VERSION;
        }

        return $result;
    }
}
