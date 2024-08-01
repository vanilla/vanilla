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
class TrustedDomainModel
{
    private const PROVIDER_URLS = ["PasswordUrl", "ProfileUrl", "RegisterUrl", "SignInUrl", "SignOutUrl", "URL"];

    /** @var EventManager */
    private $eventManager;

    /** @var \Gdn_AuthenticationProviderModel */
    private $authProviderModel;

    /** @var array */
    private $configuredTrustedDomains;

    /** @var bool */
    private $isInstalled;

    /** @var bool */
    private $linkWarnLeaving;

    /** @var string */
    private $siteDomain;

    /** @var array|null */
    private $localDomainCache = null;

    /** @var ConfigurationInterface */
    private $config;

    /**
     * DI.
     *
     * @param EventManager $eventManager
     * @param ConfigurationInterface $config
     * @param RequestInterface $request
     */
    public function __construct(EventManager $eventManager, ConfigurationInterface $config, RequestInterface $request)
    {
        $this->eventManager = $eventManager;
        $this->config = $config;
        $configuredDomains = $config->get("Garden.TrustedDomains", []);
        if (!is_array($configuredDomains)) {
            $configuredDomains = is_string($configuredDomains) ? explode("\n", $configuredDomains) : [];
        }
        $this->configuredTrustedDomains = array_filter($configuredDomains);
        $this->isInstalled = $config->get("Garden.Installed", false);

        $this->linkWarnLeaving = $config->get("Garden.Format.WarnLeaving", true);

        $this->siteDomain = $request->getHost();
    }

    /**
     * Lazily fetch the auth provider because some tests don't have a DB connection.
     *
     * @return \Gdn_AuthenticationProviderModel
     */
    private function getAuthProviderModel(): \Gdn_AuthenticationProviderModel
    {
        if ($this->authProviderModel === null) {
            $this->authProviderModel = \Gdn::getContainer()->get(\Gdn_AuthenticationProviderModel::class);
        }
        return $this->authProviderModel;
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        $configuredDomains = $this->fetchTrustedDomains();

        if ($this->localDomainCache !== null) {
            return array_merge($this->localDomainCache, $configuredDomains);
        }

        $this->localDomainCache = array_merge([$this->siteDomain], $configuredDomains);
        if (!$this->isInstalled) {
            // Bail out here because we don't have a database yet.
            return $this->localDomainCache;
        }

        $providers = $this->getAuthProviderModel()->getAll();

        // Iterate through the providers, only grabbing URLs if they're not empty and not already present.
        foreach ($providers as $provider) {
            foreach (self::PROVIDER_URLS as $urlKey) {
                $providerUrl = $provider[$urlKey] ?? "";
                if ($providerDomain = parse_url($providerUrl, PHP_URL_HOST)) {
                    $this->localDomainCache[] = $providerDomain;
                }
            }
        }

        $args = [
            "TrustedDomains" => &$this->localDomainCache,
        ];
        $this->eventManager->fire(
            "entryController_beforeTargetReturn",
            \Gdn::pluginManager(), // eww but needed for compatibility.
            $args
        );

        return $this->localDomainCache;
    }

    /**
     * Check if a given url is trusted or not
     *
     * @param $url string The URL to check
     * @return bool The url is trusted or not
     */
    public function isTrustedDomain(string $url): bool
    {
        $trustedDomains = $this->getAll();
        $isTrustedDomain = false;

        foreach ($trustedDomains as $trustedDomain) {
            if (urlMatch($trustedDomain, $url)) {
                $isTrustedDomain = true;
                break;
            }
        }

        return $isTrustedDomain;
    }

    /**
     * Transform a destination to make sure that the resulting URL is "Safe".
     *
     * "Safe" means that the domain of the URL is trusted.
     *
     * @param string $destination Destination URL or path.
     * @param bool $withDomain
     * @return string The destination if safe, /home/leaving?Target=$destination if not.
     */
    public function safeUrl($destination, $withDomain = true)
    {
        $url = url($destination, true);
        $isTrustedDomain = $this->isTrustedDomain($url);

        return $isTrustedDomain ? $url : url("/home/leaving?target=" . urlencode($destination), $withDomain);
    }

    /**
     * Performs optional url filtering if the Garden.Format.WarnLeaving config isn't explicitly set to false,
     * otherwise it returns the url as is.
     *
     * @param string $destination Destination URL or path.
     * @param bool $withDomain
     * @return string The destination if safe, /home/leaving?Target=$destination if not.
     */
    public function safeContentUrl($destination)
    {
        // If the Garden.Format.WarnLeaving Config is explicitly set to false we return the url as is
        // otherwise we process the url through safeUrl();
        return !$this->linkWarnLeaving ? url($destination, true) : $this->safeUrl($destination, true);
    }

    public function fetchTrustedDomains(): array
    {
        $trustedDomains = $this->config->get("Garden.TrustedDomains", []);
        if (!is_array($trustedDomains)) {
            $trustedDomains = is_string($trustedDomains) ? explode("\n", $trustedDomains) : [];
            $trustedDomains = array_filter($trustedDomains, function ($item) {
                return !empty($item);
            });
        }
        $trustedDomains[] = "us.v-cdn.net";
        return $trustedDomains;
    }
}
