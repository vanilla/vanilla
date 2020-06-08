<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\API;

use Garden\Schema\Schema;
use Garden\Web\Data;
use Vanilla\Contracts\Models\CrawlableInterface;
use Vanilla\Models\ModelFactory;
use Vanilla\Web\Controller;

/**
 * Gives meta information about the resource models in the system.
 */
class ResourcesApiController extends Controller {
    /**
     * The `GET /resources` endpoint.
     *
     * @param \Gdn_Request $request
     * @param array $query
     * @return Data
     */
    public function index(\Gdn_Request $request, array $query = []): Data {
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
                'url' => $request->getSimpleUrl("/api/v2/resources/$recordType"),
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
     * @param \Gdn_Request $request
     * @param string $recordType
     * @return Data
     */
    public function get(\Gdn_Request $request, string $recordType): Data {
        $this->permission('Garden.Settings.Manage');

        $model = $this->factory->get($recordType);
        $recordType = $this->factory->getRecordType(get_class($model));

        $r = [
            'recordType' => $recordType,
        ];
        if ($model instanceof CrawlableInterface) {
            $r['crawl'] = $model->getCrawlInfo();
            $r['crawl']['url'] = $request->getSimpleUrl($r['crawl']['url']);
        }

        return new Data($r);
    }
}
