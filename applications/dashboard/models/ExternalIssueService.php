<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

/**
 * Class ExternalIssueService
 */
class ExternalIssueService
{
    private $providers = [];

    /**
     * Add a provider to the service.
     *
     * @param ExternalIssueProviderInterface $provider
     * @return void
     */
    public function addProvider(ExternalIssueProviderInterface $provider)
    {
        $this->providers[$provider->getTypeName()] = $provider;
    }

    /**
     * Get all the providers in the service.
     *
     * @return array
     */
    public function getAllProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get a provider by source name.
     *
     * @param string $typeName
     * @return ExternalIssueProviderInterface
     */
    public function getProvider(string $typeName): ExternalIssueProviderInterface
    {
        return $this->providers[$typeName];
    }
}
