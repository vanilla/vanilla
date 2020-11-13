<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Site;

use Garden\Http\HttpClient;
use Vanilla\Navigation\Breadcrumb;

/**
 * Class representing a type.
 */
class Site implements \JsonSerializable {

    /** @var string */
    protected $name;

    /** @var string */
    protected $webUrl;

    /** @var int */
    protected $siteID;

    /** @var int */
    protected $accountID;

    /** @var HttpClient */
    protected $httpClient;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $webUrl
     * @param int $siteID
     * @param int $accountID
     * @param HttpClient $httpClient
     */
    public function __construct(
        string $name,
        string $webUrl,
        int $siteID,
        int $accountID,
        HttpClient $httpClient
    ) {
        $this->name = $name;
        $this->webUrl = $webUrl;
        $this->siteID = $siteID;
        $this->accountID = $accountID;
        $this->httpClient = $httpClient;
    }

    /**
     * Get the visual name of the site.
     *
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Get the web based URL root of the site.
     *
     * @return string
     */
    public function getWebUrl(): string {
        return $this->webUrl;
    }

    /**
     * Get a unique identifier for the site.
     *
     * @return int
     */
    public function getSiteID(): int {
        return $this->siteID;
    }

    /**
     * @param int $siteID
     */
    public function setSiteID(int $siteID): void {
        $this->siteID = $siteID;
    }

    /**
     * Get an ID that can be used to group sites together.
     *
     * @return int
     */
    public function getAccountID(): int {
        return $this->accountID;
    }

    /**
     * Get breadcrumbs representing the site.
     *
     * @return Breadcrumb[]
     */
    public function toBreadcrumbs(): array {
        return [
            new Breadcrumb($this->getName(), $this->getWebUrl())
        ];
    }

    /**
     * Get an authenticated HTTP client for the site.
     *
     * @return HttpClient
     */
    public function getHttpClient(): HttpClient {
        $this->httpClient->setThrowExceptions(true);
        return $this->httpClient;
    }

    /**
     * @return array
     */
    public function jsonSerialize() {
        return [
            'name' => $this->getName(),
            'webUrl' => $this->getWebUrl(),
            'accountID' => $this->getAccountID(),
            'siteID' => $this->getSiteID(),
        ];
    }
}
