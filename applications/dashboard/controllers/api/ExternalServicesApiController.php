<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

use Garden\Web\Data;
use Vanilla\Dashboard\Models\ExternalServiceTracker;

/**
 * Endpoint used to manage AI features.
 */
class ExternalServicesApiController extends AbstractApiController
{
    /**
     * D.I.
     *
     * @param ExternalServiceTracker $tracker
     */
    public function __construct(private ExternalServiceTracker $tracker)
    {
    }

    public function index(): Data
    {
        $this->permission("site.manage");
        $services = array_keys($this->tracker->getServices());
        return new Data($services);
    }

    /**
     * Get the status of the AI features.
     *
     * @return Data
     */
    public function get_status(string $service): Data
    {
        $this->permission("site.manage");
        $service = $this->tracker->getServices()[$service] ?? false;

        if (!$service) {
            throw new Exception("Service not found.");
        }

        $result = $service->isEnabled();

        return new Data($result);
    }
}
