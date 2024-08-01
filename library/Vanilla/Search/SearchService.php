<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\ServerException;
use Vanilla\EmbeddedContent\FallbackEmbedFactory;

/**
 * Entry point for making search queries among multiple providers.
 */
class SearchService
{
    /** @var array [driver => SearchDriverInterface, priority => int] */
    private $drivers;

    /** @var array extenders */
    protected $extenders = [];

    /**
     * Register an active driver.
     *
     * @param AbstractSearchDriver $driver The driver.
     * @param int $priority The highest priority number becomes the active driver.
     */
    public function registerActiveDriver(AbstractSearchDriver $driver, int $priority = 0)
    {
        $this->drivers[] = [
            "priority" => $priority,
            "driver" => $driver,
        ];
        uasort($this->drivers, function (array $driverA, array $driverB) {
            return $driverB["priority"] <=> $driverA["priority"];
        });
    }

    /**
     * Get the active search driver.
     *
     * @param ?string $driverName Driver name.
     * @param bool $throwOnNotFound Set this to true to make this method throw if it can't find the requested driver.
     *
     * @return AbstractSearchDriver
     */
    public function getActiveDriver(?string $driverName = null, bool $throwOnNotFound = false): AbstractSearchDriver
    {
        if (empty($this->drivers)) {
            throw new ServerException("No search service is registered");
        }
        $driverName = $driverName ?? \Gdn::config("Vanilla.Search.Driver", null);
        $fallbackDriver = end($this->drivers)["driver"];

        if (!empty($driverName)) {
            $driver = $this->getDriverByName($driverName);
        } else {
            $driver = $fallbackDriver;
        }
        if (!$driver instanceof AbstractSearchDriver) {
            if ($throwOnNotFound) {
                throw new ServerException("Could not find search driver: '$driverName'.");
            } else {
                $driver = $fallbackDriver;
            }
        }
        $driver->setSearchService($this);
        return $driver;
    }

    /**
     * Perform a query.
     *
     * @param array $queryData
     * @param SearchOptions $options
     *
     * @return SearchResults
     */
    public function search(array $queryData, SearchOptions $options): SearchResults
    {
        $activeDriver = $this->getActiveDriver($queryData["driver"] ?? null, true);
        return $activeDriver->search($queryData, $options);
    }

    /**
     * Build the schema for the search.
     *
     * @return Schema
     */
    public function buildQuerySchema(): Schema
    {
        return $this->getActiveDriver()->buildQuerySchema();
    }

    /**
     * Register search service extender
     *
     * @param SearchTypeQueryExtenderInterface $extender
     */
    public function registerQueryExtender(SearchTypeQueryExtenderInterface $extender)
    {
        $this->extenders[] = $extender;
    }

    /**
     * Get all search service extenders
     *
     * @return array
     */
    public function getExtenders(): array
    {
        return $this->extenders;
    }

    /**
     * Get a single driver by name.
     *
     * @param string $name
     * @return ?AbstractSearchDriver
     */
    private function getDriverByName(string $name): ?AbstractSearchDriver
    {
        $driver = null;
        /** @var  AbstractSearchDriver $driver */
        foreach ($this->drivers as $currentDriver) {
            if (strtolower($currentDriver["driver"]->getName()) === strtolower($name)) {
                $driver = $currentDriver["driver"];
                break;
            }
        }
        return $driver;
    }

    /**
     * Get available driver names.
     *
     * @return array
     */
    public function getDriverNames(): array
    {
        $driverNames = [];
        /** @var  AbstractSearchDriver $driver */
        foreach ($this->drivers as $driver) {
            $driverNames[] = strtolower($driver["driver"]->getName());
        }
        return $driverNames;
    }
}
