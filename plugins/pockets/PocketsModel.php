<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Addons\Pockets;

use Garden\Schema\ValidationException;
use Psr\SimpleCache\CacheInterface;
use Vanilla\ApiUtils;
use Vanilla\BodyFormatValidator;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;
use Vanilla\Widgets\WidgetService;

/**
 * Model for pockets.
 */
class PocketsModel extends \Gdn_Model
{
    const FORMAT_CUSTOM = "raw";
    const FORMAT_WIDGET = "widget";

    const CACHE_KEY_ENABLED = "pocketsEnabled";
    const CACHE_TTL = 60 * 30;

    // Pages
    const PAGE_HOME = "home";
    const PAGE_ACTIVITY = "activity";
    const PAGE_COMMENTS = "comments";
    const PAGE_DISCUSSIONS = "discussions";
    const PAGE_CATEGORIES = "categories";
    const PAGE_INBOX = "inbox";
    const PAGE_PROFILE = "profile";

    /** @var CacheInterface */
    private $cache;

    /** @var WidgetService */
    private $widgetService;

    /** @var array  */
    public $locations = [
        "Content" => ["Name" => "Content"],
        "Panel" => ["Name" => "Panel"],
        "BetweenDiscussions" => ["Name" => "Between Discussions", "Wrap" => ["<li>", "</li>"]],
        "BetweenComments" => ["Name" => "Between Comments", "Wrap" => ["<li>", "</li>"]],
        "Head" => ["Name" => "Head"],
        "Foot" => ["Name" => "Foot"],
        "Custom" => ["Name" => "Custom"],
    ];

    /**
     * @inheritdoc
     */
    public function __construct()
    {
        parent::__construct("Pocket");
        $this->cache = \Gdn::getContainer()->get(CacheInterface::class);
        $this->widgetService = \Gdn::getContainer()->get(WidgetService::class);
    }

    /**
     * @inheritdoc
     */
    public function getID($id, $datasetType = null, $options = [])
    {
        $result = parent::getID($id, DATASET_TYPE_ARRAY, $options);
        if ($result) {
            $result = $this->expandAttributes($result);
        }
        return $result;
    }

    /**
     * Get an array mapping location keys to visual display names.
     *
     * @param bool $formatted Return a formatted array to be used by the schema.
     * @return array
     */
    public function getLocationsArray($formatted = false): array
    {
        $result = [];
        if (!$formatted) {
            $result = [
                "" => sprintf(t("Select a %s"), t("Location")),
            ];
        }
        foreach ($this->locations as $key => $value) {
            $result[$key] = val("Name", $value, $key);
        }

        if ($formatted) {
            $formattedArray = [];
            foreach ($result as $key => $formattedValue) {
                $formattedValue = str_replace(" ", "", $formattedValue);
                $formattedArray[$key] = $formattedValue;
            }
            $formattedArray = array_flip(array_values($formattedArray));
            $formattedValue = array_flip($formattedArray);
            $result = $formattedValue;
        }
        return $result;
    }

    /**
     * Normalize input fields.
     *
     * @param array $body Post body.
     */
    public function normalizeInput(array $body): array
    {
        if (array_key_exists("repeatType", $body)) {
            $body = $this->constructRepeatTypes($body);
        }
        if (array_key_exists("mobileType", $body) && $body["mobileType"] !== "default") {
            $body["mobileOnly"] = $body["mobileType"] === "only" ? 1 : 0;
            $body["mobileNever"] = $body["mobileType"] === "never" ? 1 : 0;
            unset($body["mobileType"]);
        } else {
            $body["mobileOnly"] = $body["mobileNever"] = 0;
        }
        if (array_key_exists("isDashboard", $body)) {
            $body["showInDashboard"] = $body["isDashboard"] ?? false;
            unset($body["isDashboard"]);
        }
        if (array_key_exists("isEmbeddable", $body)) {
            $body["embeddedNever"] = $body["isEmbeddable"] ? 0 : 1;
            unset($body["isEmbeddable"]);
        }
        if (array_key_exists("isAd", $body)) {
            $body["type"] = $body["isAd"] ? "ad" : "default";
            unset($body["isAd"]);
        }
        if (array_key_exists("enabled", $body)) {
            $body["disabled"] = $body["enabled"] ? 0 : 1;
        }
        $body = ArrayUtils::camelCase($body);
        return $body;
    }

    /**
     * Normalize output fields.
     *
     * @param array $body Record body.
     * @param array $query
     * @return array
     */
    public function normalizeOutput(array $body, array $query = []): array
    {
        $query = ArrayUtils::camelCase($query);
        $expand = $query["expand"] ?? [];
        if (!ModelUtils::isExpandOption("body", $expand) && !empty($query)) {
            unset($body["Body"]);
        }
        $body = ApiUtils::convertOutputKeys($body);
        if (array_key_exists("disabled", $body)) {
            $body["enabled"] = $body["disabled"] ? false : true;
            unset($body["disabled"]);
        }
        if (array_key_exists("showInDashboard", $body)) {
            $body["isDashboard"] = $body["showInDashboard"] ? true : false;
            unset($body["showInDashboard"]);
        }
        if (array_key_exists("type", $body)) {
            $body["isAd"] = $body["type"] === "ad";
            unset($body["type"]);
        }
        if (array_key_exists("embeddedNever", $body)) {
            $body["isEmbeddable"] = $body["embeddedNever"] ? false : true;
            unset($body["embeddedNever"]);
        }

        if (array_key_exists("mobileOnly", $body) || array_key_exists("mobileNever", $body)) {
            if (($body["mobileOnly"] === $body["mobileNever"]) === 0) {
                $body["mobileType"] = "default";
            } elseif ($body["mobileOnly"] === 1) {
                $body["mobileType"] = "only";
            } elseif ($body["mobileNever"] === 1) {
                $body["mobileType"] = "never";
            }
            unset($body["mobileOnly"]);
            unset($body["mobileNever"]);
        }

        if (array_key_exists("repeat", $body)) {
            $repeat = explode(" ", $body["repeat"]);
            if ($repeat[0] === "every") {
                $body["repeatType"] = "every";
                $body["repeatEvery"] = $repeat[1];
            } elseif ($repeat[0] === "index") {
                $body["repeatType"] = "index";
                $body["repeatIndexes"] = [$repeat[1]];
            } else {
                $body["repeatType"] = $repeat[0];
            }
            unset($body["repeat"]);
        }
        return $body;
    }

    /**
     * Construct for repeatIndex field
     *
     * @param array $body Request body.
     * @return array
     */
    private function constructRepeatTypes(array $body): array
    {
        if (isset($body["repeatType"])) {
            if ($body["repeatType"] === "index") {
                $body["repeat"] = "index ";
                if (isset($body["repeatIndexes"])) {
                    foreach ($body["repeatIndexes"] as $indexValue) {
                        if (!is_int($indexValue)) {
                            break;
                        } else {
                            $body["repeat"] .= $indexValue . ",";
                        }
                    }
                    unset($body["repeatIndexes"]);
                    $body["repeat"] = rtrim($body["repeat"], ",");
                } else {
                    unset($body["repeat"]);
                }
            } elseif ($body["repeatType"] === "every") {
                if (isset($body["repeatEvery"])) {
                    $body["repeat"] = "every " . $body["repeatEvery"];
                    unset($body["repeatEvery"]);
                }
            } else {
                $body["repeat"] = $body["repeatType"];
            }

            unset($body["repeatType"]);
        }
        return $body;
    }

    /**
     * @inheritdoc
     */
    public function save($formPostValues, $settings = false)
    {
        $this->validateWidgetType($formPostValues);
        $hasAttributes = count($this->getAttributes($formPostValues)) > 0;
        if ($hasAttributes) {
            $formPostValues = $this->collapseAttributes($formPostValues);
        }

        $savedRecordId = parent::save($formPostValues, $settings);
        return $savedRecordId;
    }

    /**
     * @return \Gdn_Schema
     */
    public function defineSchema()
    {
        $schema = parent::defineSchema();
        $this->Validation->unapplyRule("Body", "BodyFormat");
        return $schema;
    }

    /**
     * Return list of available pages.
     *
     * @return array
     */
    public static function getPages(): array
    {
        return [
            self::PAGE_ACTIVITY,
            self::PAGE_CATEGORIES,
            self::PAGE_COMMENTS,
            self::PAGE_DISCUSSIONS,
            self::PAGE_HOME,
            self::PAGE_INBOX,
            self::PAGE_PROFILE,
            "",
        ];
    }

    /**
     * Get all pockets
     *
     * @return array
     */
    public function getAll(): array
    {
        $pockets = $this->getWhere([], "Location, Sort, Name")->resultArray();
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
    public function getEnabled(): array
    {
        $pockets = $this->cache->get(self::CACHE_KEY_ENABLED, null);
        if ($pockets === null) {
            $pockets = $this->getWhere(["Disabled" => false], "Location, Sort, Name")->resultArray();
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
    protected function onUpdate()
    {
        parent::onUpdate();
        $this->cache->delete(self::CACHE_KEY_ENABLED);
    }

    /**
     * @inheritdoc
     */
    protected function collapseAttributes($data, $name = "Attributes")
    {
        $this->collapseWidgetParameters($data);
        return parent::collapseAttributes($data, $name);
    }

    /**
     * @inheritdoc
     */
    protected function expandAttributes($row, $name = "Attributes")
    {
        $result = parent::expandAttributes($row, $name);
        $this->expandWidgetParameters($result);
        return $result;
    }

    /**
     * @param array $formPostValues
     */
    private function validateWidgetType(array &$formPostValues)
    {
        if (!isset($formPostValues["Format"])) {
            return;
        }

        $widgetFormat = $formPostValues["Format"];
        if ($widgetFormat === self::FORMAT_WIDGET) {
            $widgetID = $formPostValues["WidgetID"] ?? null;
            $widgetParameters = $formPostValues["WidgetParameters"] ?? null;
            if (is_string($widgetParameters)) {
                $widgetParameters = json_decode($widgetParameters, true);
            }

            $factory = $this->widgetService->getFactoryByID($widgetID);
            if ($factory === null) {
                $this->Validation->addValidationResult("WidgetID", "Widget $widgetID does not exist");
                return;
            }

            try {
                $formPostValues["WidgetParameters"] = $factory->getSchema()->validate($widgetParameters);
            } catch (ValidationException $validationException) {
                $this->Validation->addResults($validationException);
                return;
            }
        } elseif ($widgetFormat === self::FORMAT_CUSTOM) {
            $formPostValues["WidgetID"] = null;
            $formPostValues["WidgetParameters"] = null;
        }
    }

    /**
     * @param array $row
     */
    private function collapseWidgetParameters(array &$row)
    {
        if (isset($row["WidgetParameters"])) {
            $params = $row["WidgetParameters"];
            unset($row["WidgetParameters"]);
            $row["Body"] = json_encode($params, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * @param array $row
     */
    private function expandWidgetParameters(array &$row)
    {
        $widgetType = $row["Format"] ?? self::FORMAT_CUSTOM;
        if ($widgetType === self::FORMAT_WIDGET) {
            $body = $row["Body"] ?? null;
            if (!$body) {
                $row["WidgetParameters"] = [];
                return;
            }

            $jsonDecoded = json_decode($body, true);
            if (!json_last_error()) {
                $row["WidgetParameters"] = $jsonDecoded;
            } else {
                trigger_error(json_last_error_msg(), E_USER_WARNING);
            }
        } else {
            $row["WidgetParameters"] = null;
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
    public function touchPocket(string $name, array $overrides)
    {
        $pockets = $this->getWhere(["Name" => $name])->resultArray();

        if (empty($pockets)) {
            $pocket = $overrides + [
                "Name" => $name,
                "Location" => "Content",
                "Sort" => 0,
                "Repeat" => \Pocket::REPEAT_BEFORE,
                "Body" => "<-- Empty Pocket -->",
                "Format" => PocketsModel::FORMAT_CUSTOM,
                "Disabled" => \Pocket::DISABLED,
                "MobileOnly" => 0,
                "MobileNever" => 0,
                "EmbeddedNever" => 0,
                "ShowInDashboard" => 0,
                "Type" => "default",
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
