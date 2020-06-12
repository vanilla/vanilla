<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Search;

use Garden\Schema\ValidationException;
use Garden\Web\Exception\ServerException;
use Vanilla\EmbeddedContent\FallbackEmbedFactory;

/**
 * Entry point for making search queries among multiple providers.
 */
class SearchService {

    /** @var array [driver => SearchDriverInterface, priority => int] */
    private $drivers;

    /**
     * Register an active driver.
     *
     * @param SearchDriverInterface $driver The driver.
     * @param int $priority The highest priority number becomes the active driver.
     */
    public function registerActiveDriver(SearchDriverInterface $driver, int $priority = 0) {
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
     * @return SearchDriverInterface
     */
    public function getActiveDriver(): SearchDriverInterface {
        $driver = end($this->drivers)['driver'];
        if (!$driver) {
            throw new ServerException('Could not find active driver');
        }
        return $driver;
    }

    /**
     * Perform a query.
     *
     * @param array $queryData
     *
     * @return SearchResults
     */
    public function search(array $queryData): SearchResults {
        $activeDriver = $this->getActiveDriver();
        $query = $activeDriver->buildQuery($queryData);
        return $activeDriver->search($query);
    }
}
