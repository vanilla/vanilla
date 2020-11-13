<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\EventManager;
use Garden\Web\RequestInterface;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Model for fetching information about trustd domains.
 */
class TrustedDomainModel {

    private const PROVIDER_URLS = [
        'PasswordUrl',
        'ProfileUrl',
        'RegisterUrl',
        'SignInUrl',
        'SignOutUrl',
        'URL'
    ];

    /** @var EventManager */
    private $eventManager;

    /** @var \Gdn_AuthenticationProviderModel */
    private $authProviderModel;

    /** @var array */
    private $configuredTrustedDomains;

    /** @var bool */
    private $isInstalled;

    /** @var string */
    private $siteDomain;

    /** @var array|null */
    private $localDomainCache = null;

    /**
     * DI.
     *
     * @param EventManager $eventManager
     * @param ConfigurationInterface $config
     * @param RequestInterface $request
     */
    public function __construct(
        EventManager $eventManager,
        ConfigurationInterface $config,
        RequestInterface $request
    ) {
        $this->eventManager = $eventManager;
        $configuredDomains = $config->get('Garden.TrustedDomains', []);
        if (!is_array($configuredDomains)) {
            $configuredDomains = is_string($configuredDomains) ? explode("\n", $configuredDomains) : [];
        }
        $this->configuredTrustedDomains = array_filter($configuredDomains);
        $this->isInstalled = $config->get('Garden.Installed', false);
        $this->siteDomain = $request->getHost();
    }

    /**
     * Lazily fetch the auth provider because some tests don't haver a DB connection.
     *
     * @return \Gdn_AuthenticationProviderModel
     */
    private function getAuthProviderModel(): \Gdn_AuthenticationProviderModel {
        if ($this->authProviderModel === null) {
            $this->authProviderModel = \Gdn::getContainer()->get(\Gdn_AuthenticationProviderModel::class);
        }
        return $this->authProviderModel;
    }


    /**
     * @return array
     */
    public function getAll(): array {
        if ($this->localDomainCache !== null) {
            return $this->localDomainCache;
        }
        $this->localDomainCache = array_merge([$this->siteDomain], $this->configuredTrustedDomains);
        if (!$this->isInstalled) {
            // Bail out here because we don't have a database yet.
            return $this->localDomainCache;
        }

        $providers = $this->getAuthProviderModel()->getAll();

        // Iterate through the providers, only grabbing URLs if they're not empty and not already present.
        foreach ($providers as $provider) {
            foreach (self::PROVIDER_URLS as $urlKey) {
                $providerUrl = $provider[$urlKey] ?? '';
                if ($providerDomain = parse_url($providerUrl, PHP_URL_HOST)) {
                    $this->localDomainCache[] = $providerDomain;
                }
            }
        }

        $args = [
            'TrustedDomains' => &$this->localDomainCache,
        ];
        $this->eventManager->fire(
            'entryController_beforeTargetReturn',
            \Gdn::pluginManager(), // eww but needed for compatibility.
            $args
        );

        return $this->localDomainCache;
    }
}
