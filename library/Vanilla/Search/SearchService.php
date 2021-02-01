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
class SearchService {

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
    public function registerActiveDriver(AbstractSearchDriver $driver, int $priority = 0) {
        $this->drivers[] = [
            'priority' => $priority,
            'driver' => $driver,
        ];
        uasort($this->drivers, function (array $driverA, array $driverB) {
            return $driverB['priority'] <=> $driverA['priority'];
        });
    }

    /**
     * Get the active search driver.
     *
     * @param ?string $driverName Driver name.
     * @return AbstractSearchDriver
     */
    public function getActiveDriver(?string $driverName = null): AbstractSearchDriver {
        if (empty($this->drivers)) {
            throw new ServerException('No search service is registered');
        }
        $forceDriver = \Gdn::config('Vanilla.Search.Driver', null);

        if ($driverName || $forceDriver) {
            $driver = $this->getDriverByName($driverName ?? $forceDriver);
        } else {
            $driver = end($this->drivers)['driver'];
        }
        if (!$driver) {
            throw new ServerException('Could not find active driver');
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
    public function search(array $queryData, SearchOptions $options): SearchResults {
        $activeDriver = $this->getActiveDriver();
        return $activeDriver->search($queryData, $options);
    }

    /**
     * Build the schema for the search.
     *
     * @return Schema
     */
    public function buildQuerySchema(): Schema {
        return $this
            ->getActiveDriver()
            ->buildQuerySchema();
    }

    /**
     * Register search service extender
     *
     * @param SearchTypeQueryExtenderInterface $extender
     */
    public function registerQueryExtender(SearchTypeQueryExtenderInterface $extender) {
        $this->extenders[] = $extender;
    }

    /**
     * Get all search service extenders
     *
     * @return array
     */
    public function getExtenders(): array {
        return $this->extenders;
    }

    /**
     * Get a single driver by name.
     *
     * @param string $name
     * @return AbstractSearchDriver
     */
    public function getDriverByName(string $name): AbstractSearchDriver {
        /** @var  AbstractSearchDriver $driver */
        foreach ($this->drivers as $currentDriver) {
            if (strtolower($currentDriver['driver']->getName()) === strtolower($name)) {
                $driver = $currentDriver['driver'];
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
    public function getDriverNames(): array {
        $driverNames = [];
        /** @var  AbstractSearchDriver $driver */
        foreach ($this->drivers as $driver) {
            $driverNames[] = strtolower($driver['driver']->getName());
        }
        return $driverNames;
    }
}
