<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

/**
 * Model used to track External Services and their requirements.
 */
class ExternalServiceTracker
{
    /* @var array<ExternalServiceInterface> */
    private array $services = [];

    /**
     * Return the relevant status for an AI feature.
     *
     * @return ExternalServiceInterface[]
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * Register an external service.
     *
     * @param ExternalServiceInterface $service
     * @return void
     */
    public function registerService(ExternalServiceInterface $service): void
    {
        $this->services[$service->getName()] = $service;
    }
}
