<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Addons\Pockets;

use Garden\Schema\ValidationException;
use Psr\SimpleCache\CacheInterface;
use Vanilla\BodyFormatValidator;
use Vanilla\Widgets\WidgetService;

/**
 * Model for pockets.
 */
class PocketsModel extends \Gdn_Model {

    const FORMAT_CUSTOM = "raw";
    const FORMAT_WIDGET = "widget";

    const CACHE_KEY_ENABLED = "pocketsEnabled";
    const CACHE_TTL = 60 * 30;

    /** @var CacheInterface */
    private $cache;

    /** @var WidgetService */
    private $widgetService;

    /**
     * @inheritdoc
     */
    public function __construct() {
        parent::__construct('Pocket');
        $this->cache = \Gdn::getContainer()->get(CacheInterface::class);
        $this->widgetService = \Gdn::getContainer()->get(WidgetService::class);
    }

    /**
     * @inheritdoc
     */
    public function getID($id, $datasetType = null, $options = []) {
        $result =  parent::getID($id, DATASET_TYPE_ARRAY, $options);
        return $this->expandAttributes($result);
    }

    /**
     * @inheritdoc
     */
    public function save($formPostValues, $settings = false) {
        $this->validateWidgetType($formPostValues);
        $row = $this->collapseAttributes($formPostValues);

        return parent::save($row, $settings);
    }

    /**
     * @return \Gdn_Schema
     */
    public function defineSchema() {
        $schema = parent::defineSchema();
        $this->Validation->unapplyRule('Body', 'BodyFormat');
        return $schema;
    }

    /**
     * Get all pockets
     *
     * @return array
     */
    public function getAll(): array {
        $pockets = $this->getWhere([], 'Location, Sort, Name')->resultArray();
        $pockets = array_map(function ($row) {
            return $this->expandAttributes($row);
        }, $pockets);

        return $pockets;
    }

    /**
     * Get all pockets
     *
     * @return array
     */
    public function getEnabled(): array {
        $pockets = $this->cache->get(self::CACHE_KEY_ENABLED, null);
        if ($pockets === null) {
            $pockets = $this->getWhere(['Disabled' => false], 'Location, Sort, Name')->resultArray();
            $pockets = array_map(function ($row) {
                return $this->expandAttributes($row);
            }, $pockets);
            $this->cache->set(self::CACHE_KEY_ENABLED, $pockets, self::CACHE_TTL);
        }

        return $pockets;
    }

    /**
     * Clear the cache.
     */
    protected function onUpdate() {
        parent::onUpdate();
        $this->cache->delete(self::CACHE_KEY_ENABLED);
    }

    /**
     * @inheritdoc
     */
    protected function collapseAttributes($data, $name = 'Attributes') {
        $this->collapseWidgetParameters($data);
        return parent::collapseAttributes($data, $name);
    }

    /**
     * @inheritdoc
     */
    protected function expandAttributes($row, $name = 'Attributes') {
        $result = parent::expandAttributes($row, $name);
        $this->expandWidgetParameters($result);
        return $result;
    }


    /**
     * @param array $formPostValues
     */
    private function validateWidgetType(array &$formPostValues) {
        if (!isset($formPostValues['Format'])) {
            return;
        }

        $widgetFormat = $formPostValues['Format'];
        if ($widgetFormat === self::FORMAT_WIDGET) {
            $widgetID = $formPostValues['WidgetID'] ?? null;
            $widgetParameters = $formPostValues['WidgetParameters'] ?? null;
            if (is_string($widgetParameters)) {
                $widgetParameters = json_decode($widgetParameters, true);
            }

            $factory = $this->widgetService->getFactoryByID($widgetID);
            if ($factory === null) {
                $this->Validation->addValidationResult('WidgetID', "Widget $widgetID does not exist");
                return;
            }

            try {
                $formPostValues['WidgetParameters'] = $factory->getSchema()->validate($widgetParameters);
            } catch (ValidationException $validationException) {
                $this->Validation->addResults($validationException);
                return;
            }
        } elseif ($widgetFormat === self::FORMAT_CUSTOM) {
            $formPostValues['WidgetID'] = null;
            $formPostValues['WidgetParameters'] = null;
        }
    }

    /**
     * @param array $row
     */
    private function collapseWidgetParameters(array &$row) {
        if (isset($row['WidgetParameters'])) {
            $params = $row['WidgetParameters'];
            unset($row['WidgetParameters']);
            $row['Body'] = json_encode($params, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * @param array $row
     */
    private function expandWidgetParameters(array &$row) {
        $widgetType = $row['Format'] ?? self::FORMAT_CUSTOM;
        if ($widgetType === self::FORMAT_WIDGET) {
            $body = $row['Body'] ?? null;
            if (!$body) {
                $row['WidgetParameters'] = [];
                return;
            }

            $jsonDecoded = json_decode($body, true);
            if (!json_last_error()) {
                $row['WidgetParameters'] = $jsonDecoded;
            } else {
                trigger_error(json_last_error_msg(), E_USER_WARNING);
            }
        } else {
            $row['WidgetParameters'] = null;
        }
    }

    /**
     * Create a pocket if it doesn't exist.
     *
     * @param string $name
     * @param array $overrides
     *
     * @return int|false
     */
    public function touchPocket(string $name, array $overrides) {
        $pockets = $this->getWhere(['Name' => $name])->resultArray();

        if (empty($pockets)) {
            $pocket = $overrides + [
                'Name' => $name,
                'Location' => 'Content',
                'Sort' => 0,
                'Repeat' => \Pocket::REPEAT_BEFORE,
                'Body' => '<-- Empty Pocket -->',
                'Format' => PocketsModel::FORMAT_CUSTOM,
                'Disabled' => \Pocket::DISABLED,
                'MobileOnly' => 0,
                'MobileNever' => 0,
                'EmbeddedNever' => 0,
                'ShowInDashboard' => 0,
                'Type' => 'default',
            ];
            $id = $this->save($pocket);
            if (is_numeric($id)) {
                return (int) $id;
            } else {
                return $id;
            }
        }
        return false;
    }
}
