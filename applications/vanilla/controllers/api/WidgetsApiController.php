<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Controllers\Api;

use Garden\Schema\Schema;
use Garden\Web\Data;
use Vanilla\Utility\InstanceValidatorSchema;
use Vanilla\Widgets\WidgetFactory;
use Vanilla\Widgets\WidgetService;

/**
 * API Controller for the `/widgets`.
 */
class WidgetsApiController extends \AbstractApiController {

    /** @var WidgetService */
    private $widgetService;

    /**
     * WidgetsApiController constructor.
     *
     * @param WidgetService $widgetService
     */
    public function __construct(WidgetService $widgetService) {
        $this->widgetService = $widgetService;
    }

    /**
     * Get a schema instance comprised of all available draft fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function widgetSchema() {
        return new InstanceValidatorSchema(WidgetFactory::class);
    }

    /**
     * Get all registered widgets.
     *
     * @param array $query
     * @return Data
     */
    public function index(array $query = []): Data {
        $this->permission();
        $out = Schema::parse([":a" => $this->widgetSchema()]);

        $widgets = $this->widgetService->getFactories();
        $result = $out->validate($widgets);
        return Data::box($result);
    }

    /**
     * Get widgets from name.
     *
     * @param string $id
     * @return Data
     */
    public function get(string $id): Data {
        $this->permission();
        $out = $this->widgetSchema();

        $widget = $this->widgetService->getFactoryByID($id);
        $result = $out->validate($widget);

        return new Data($result);
    }
}
