<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Search;

use Garden\Container\Container;
use Garden\Container\Reference;
use Garden\Http\HttpResponse;
use Vanilla\Forum\Search\ArticleSearchIndexTemplate;
use Vanilla\Forum\Search\CategorySearchIndexTemplate;
use Vanilla\Forum\Search\CommentSearchIndexTemplate;
use Vanilla\Forum\Search\DiscussionSearchIndexTemplate;
use Vanilla\Forum\Search\GroupSearchIndexTemplate;
use Vanilla\Forum\Search\KnowledgeBaseSearchIndexTemplate;
use Vanilla\Forum\Search\UserSearchIndexTemplate;
use Vanilla\Http\InternalClient;
use Vanilla\Search\GlobalSearchType;
use Vanilla\Search\MysqlSearchDriver;
use Vanilla\Search\SearchService;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\CategoryAndDiscussionApiTestTrait;
use VanillaTests\Models\ModelTestTrait;

/**
 * Base search tests.
 *
 * @method InternalClient api()
 */
abstract class AbstractSearchTest extends AbstractAPIv2Test {

    use ModelTestTrait;
    use CategoryAndDiscussionApiTestTrait;

    /** @var HttpResponse */
    protected $lastSearchResponse;

    /**
     * Get the class to use for the search driver.
     *
     * @return string
     */
    abstract protected static function getSearchDriverClass(): string;

    /**
     * Get the search types to register for the tests.
     *
     * @return string[]
     */
    abstract protected static function getSearchTypeClasses(): array;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        parent::setupBeforeClass();
        static::clearIndexes();
    }

    /**
     * @param Container $container
     */
    public static function configureSearchContainer(Container $container) {
        return;
    }

    /**
     * Apply some container configuration.
     *
     * @param Container $container
     */
    public static function configureContainerBeforeStartup(Container $container) {
        parent::configureContainerBeforeStartup($container);
        $container
            ->rule(SearchService::class)
            ->addCall('registerActiveDriver', [
                'driver' => new Reference(MysqlSearchDriver::class)
            ])
            ->addCall('registerActiveDriver', [
                'driver' => new Reference(static::getSearchDriverClass())
            ])
        ;

        $container
            ->rule(static::getSearchDriverClass())
            ->addCall('registerSearchType', [new Reference(GlobalSearchType::class)])
        ;

        foreach (static::getSearchTypeClasses() as $typeClass) {
            $container->addCall('registerSearchType', [new Reference($typeClass)]);
        }

        $container->addCall('registerSearchIndexTemplate', [new Reference(CategorySearchIndexTemplate::class)]);
        $container->addCall('registerSearchIndexTemplate', [new Reference(CommentSearchIndexTemplate::class)]);
        $container->addCall('registerSearchIndexTemplate', [new Reference(DiscussionSearchIndexTemplate::class)]);
        $container->addCall('registerSearchIndexTemplate', [new Reference(UserSearchIndexTemplate::class)]);

        static::configureSearchContainer($container);
    }

    /**
     * Do a search and return the results.
     *
     * @param array $searchParams
     * @return array
     */
    public function getSearchResults(array $searchParams): array {
        return $this->api()->get("/search", $searchParams)->getBody();
    }

    /**
     * Do a search and return the results.
     *
     * @param array $searchParams
     * @param array $expectedFields Mapping of expectedField => expectedValues.
     * @param bool $strictOrder Whether or not the fields should be returned in a strict order.
     * @param int|null $count Expected count of result items
     */
    public function assertSearchResults(array $searchParams, array $expectedFields, bool $strictOrder = false, int $count = null) {
        $response = $this->performSearch($searchParams);
        $this->lastSearchResponse = $response;
        $this->assertEquals(200, $response->getStatusCode());

        $results = $response->getBody();

        foreach ($expectedFields as $expectedField => $expectedValues) {
            if ($expectedValues === null) {
                foreach ($results as $result) {
                    $this->assertArrayNotHasKey($expectedField, $result);
                }
            } else {
                $actualValues = array_column($results, $expectedField);
                if (!$strictOrder) {
                    sort($actualValues);
                    sort($expectedValues);
                }

                $this->assertEquals($expectedValues, $actualValues);
            }
        }

        if (is_int($count)) {
            $this->assertEquals($count, count($results));
        }
    }

    /**
     * Perform a search.
     *
     * @param array $searchParams
     * @return HttpResponse
     */
    protected function performSearch(array $searchParams): HttpResponse {
        return $this->api()->get("/search", $searchParams);
    }

    /**
     * Some drivers may require some blocking while we wait for indexes.
     *
     * By default is just a stub method.
     */
    public function ensureIndexed() {
    }

    /**
     * Clear all existing indexes. Does nothing by default.
     */
    public static function clearIndexes() {
    }
}
