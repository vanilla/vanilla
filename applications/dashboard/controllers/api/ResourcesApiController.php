<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\API;

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\HttpException;
use Vanilla\ApiUtils;
use Vanilla\Contracts\Models\CrawlableInterface;
use Vanilla\DateFilterSchema;
use Vanilla\Models\DirtyRecordModel;
use Vanilla\Models\ModelFactory;
use Vanilla\Utility\ModelUtils;
use Vanilla\Utility\UrlUtils;
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
     * @var DirtyRecordModel
     */
    private $dirtyRecordModel;

    /**
     * ResourcesApiController constructor.
     *
     * @param ModelFactory $factory
     * @param DirtyRecordModel $dirtyRecordModel
     */
    public function __construct(ModelFactory $factory, DirtyRecordModel $dirtyRecordModel) {
        $this->factory = $factory;
        $this->dirtyRecordModel = $dirtyRecordModel;
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
            'crawlable:b?',
            'recordTypes:a?' => [
                'items' => ['type' => 'string'],
            ],
            'dirtyRecords:b?' => [
                'default' => false,
            ],
        ]);
        $query = $in->validate($query);


        $passThroughQueryParams = [];
        if ($query['dirtyRecords'] ?? null) {
            $passThroughQueryParams['dirtyRecords'] = $query['dirtyRecords'];
        }

        if (isset($query['crawlable'])) {
            $models = $this->factory->getAllByInterface(CrawlableInterface::class, $query['crawlable']);
            $passThroughQueryParams['expand'] = 'crawl';
        } else {
            $models = $this->factory->getAll();
        }

        $passthroughQuery = '';
        if (!empty($passThroughQueryParams)) {
            $passthroughQuery = http_build_query($passThroughQueryParams);
        }

        $r = [];
        $allowedRecordTypes = $query['recordTypes'] ?? null;
        foreach ($models as $recordType => $model) {
            if ($allowedRecordTypes !== null && !in_array($recordType, $allowedRecordTypes)) {
                continue;
            }

            $url = "/api/v2/resources/$recordType";
            if ($passthroughQuery) {
                $url .= '?' . $passthroughQuery;
            }
            $r[] = [
                'recordType' => $recordType,
                'url' => \Gdn::request()->getSimpleUrl($url),
                'crawlable' => ($model instanceof CrawlableInterface),
            ];
        }
        return new Data($r);
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
            'dirtyRecords:b?',
            'dateInserted?' => new DateFilterSchema(),
        ]);
        $date = $query['dateInserted'] ?? '';
        $query = $in->validate($query);

        $model = $this->factory->get($recordType);
        $recordType = $this->factory->getRecordType(get_class($model));

        $data = [
            'recordType' => $recordType,
        ];

        if (ModelUtils::isExpandOption('crawl', $query['expand']) && $model instanceof CrawlableInterface) {
            $data['crawl'] = $model->getCrawlInfo();
            $data['crawl']['url'] = \Gdn::request()->getSimpleUrl($data['crawl']['url']);
            if (isset($query['dirtyRecords'])) {
                $data['crawl']['url'] .= "&dirtyRecords=true";
            }
            if (isset($date)) {
                $data['crawl']['url'] .= "&dateInserted={$date}";
            }
            if (!isset($data['crawl']['maxLimit'])) {
                $data['crawl']['maxLimit'] = ApiUtils::getMaxLimit();
            }
        }

        $out = Schema::parse([
            'recordType:s',
            'crawl?' => [
                'url:s' => ['format' => 'uri'],
                'parameter:s',
                'unqiueIDField:s',
                'count:i',
                'maxLimit:i',
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

    /**
     * The `DELETE /resources/dirty-records` endpoint.
     *
     * @param string $recordType
     * @param array $query
     */
    public function delete_dirtyRecords(string $recordType, array $query = []) {
        $this->permission('Garden.Settings.Manage');

        $in = Schema::parse([
            'dateInserted' => new DateFilterSchema([
                'description' => '',
                'x-filter' => [
                    'field' => 'dateInserted',
                    'processor' => [DateFilterSchema::class, 'dateFilterField'],
                ],
            ]),
        ]);

        $query = $in->validate($query);
        $where = ApiUtils::queryToFilters($in, $query);
        $where['recordType'] = $recordType;

        $this->dirtyRecordModel->delete($where);
    }
}
