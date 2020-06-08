<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\API;

use Garden\Schema\Schema;
use Garden\Web\Data;
use Vanilla\ApiUtils;
use Vanilla\Contracts\Models\CrawlableInterface;
use Vanilla\Models\ModelFactory;
use Vanilla\Utility\ModelUtils;
use Vanilla\Web\Controller;

/**
 * Gives meta information about the resource models in the system.
 */
class ResourcesApiController extends Controller {
    /**
     * @var ModelFactory
     */
    private $factory;

    /**
     * ResourcesApiController constructor.
     *
     * @param ModelFactory $factory
     */
    public function __construct(ModelFactory $factory) {
        $this->factory = $factory;
    }

    /**
     * The `GET /resources` endpoint.
     *
     * @param array $query
     * @return Data
     */
    public function index(array $query = []): Data {
        $this->permission('Garden.Settings.Manage');

        $in = Schema::parse([
            'crawlable:b?'
        ]);
        $query = $in->validate($query);

        if (isset($query['crawlable'])) {
            $models = $this->factory->getAllByInterface(CrawlableInterface::class, $query['crawlable']);
        } else {
            $models = $this->factory->getAll();
        }

        $r = [];
        foreach ($models as $recordType => $model) {
            $r[] = [
                'recordType' => $recordType,
                'url' => \Gdn::request()->getSimpleUrl("/api/v2/resources/$recordType"),
                'crawlable' => ($model instanceof CrawlableInterface),
            ];
        }
        return new Data($r);
    }

    /**
     * @var ModelFactory
     */
    private $factory;

    /**
     * ResourcesApiController constructor.
     *
     * @param ModelFactory $factory
     */
    public function __construct(ModelFactory $factory) {
        $this->factory = $factory;
    }

    /**
     * The `GET /resources/:recordType` endpoint.
     *
     * @param string $recordType
     * @param array $query
     * @return Data
     */
    public function get(string $recordType, array $query = []): Data {
        $this->permission('Garden.Settings.Manage');

        $in = Schema::parse([
            'expand?' => ApiUtils::getExpandDefinition(['crawl']),
        ]);
        $query = $in->validate($query);

        $model = $this->factory->get($recordType);
        $recordType = $this->factory->getRecordType(get_class($model));

        $data = [
            'recordType' => $recordType,
        ];
        if (ModelUtils::isExpandOption('crawl', $query['expand']) && $model instanceof CrawlableInterface) {
            $data['crawl'] = $model->getCrawlInfo();
            $data['crawl']['url'] = \Gdn::request()->getSimpleUrl($data['crawl']['url']);
        }

        $out = Schema::parse([
            'recordType:s',
            'crawl?' => [
                'url:s' => ['format' => 'uri'],
                'parameter:s',
                'count:i',
                'min' => [
                    'type' => ['integer', 'datetime'],
                ],
                'max' => [
                    'type' => ['integer', 'datetime'],
                ],
            ]
        ]);
        $data = $out->validate($data);

        return new Data($data);
    }
}
