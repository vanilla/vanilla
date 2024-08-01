<?php
/**
 * Message model.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

use Garden\Schema\Schema;
use Vanilla\Schema\RangeExpression;
use Vanilla\Utility\ArrayUtils;

/**
 * Handles message data.
 */
class MessageModel extends Gdn_Model
{
    const MESSAGE_TYPES = ["casual", "info", "warning", "alert"];

    /** @var array Non-standard message location allowed. */
    private $_SpecialLocations = ["[Base]", "[NonAdmin]"];

    /** @var array Current message data. */
    protected static $Messages;

    /** @var \Vanilla\Models\ModelCache */
    private $modelCache;

    /** @var \Vanilla\Layout\LayoutService */
    private $layoutService;

    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct()
    {
        parent::__construct("Message");
        $this->modelCache = new \Vanilla\Models\ModelCache("moderationMessages", \Gdn::cache());
        $this->layoutService = Gdn::getContainer()->get(\Vanilla\Layout\LayoutService::class);
    }

    /**
     * Invalidate the cache on update.
     */
    protected function onUpdate(): void
    {
        $this->modelCache->invalidateAll();
    }

    /**
     * Build the Message's Location property and add it.
     *
     * @param array|object $message Message data.
     * @return array|object Message data with Location property/key added.
     */
    public function defineLocation($message)
    {
        $controller = val("Controller", $message);
        $application = val("Application", $message);
        $method = val("Method", $message);

        if (in_array($controller, $this->_SpecialLocations)) {
            setValue("Location", $message, $controller);
        } else {
            setValue("Location", $message, $application);
            if (!stringIsNullOrEmpty($controller)) {
                setValue("Location", $message, val("Location", $message) . "/" . $controller);
            }
            if (!stringIsNullOrEmpty($method)) {
                setValue("Location", $message, val("Location", $message) . "/" . $method);
            }
        }

        return $message;
    }

    /**
     * Whether we are in (or optionally below) a category.
     *
     * @param int $needleCategoryID
     * @param int $haystackCategoryID
     * @param bool $includeSubcategories
     * @return bool
     */
    protected static function inCategory($needleCategoryID, $haystackCategoryID, $includeSubcategories = false)
    {
        if (!$haystackCategoryID) {
            return true;
        }

        if ($needleCategoryID == $haystackCategoryID) {
            return true;
        }

        if ($includeSubcategories) {
            $cat = CategoryModel::categories($needleCategoryID);
            for ($i = 0; $i < 10; $i++) {
                if (!$cat) {
                    break;
                }

                if ($cat["CategoryID"] == $haystackCategoryID) {
                    return true;
                }

                $cat = CategoryModel::categories($cat["ParentCategoryID"]);
            }
        }

        return false;
    }

    /**
     * Get what messages are active for a template location.
     *
     * @param string $location
     * @param array $exceptions
     * @param null $categoryID
     * @return array|null
     */
    public function getMessagesForLocation(string $location, array $exceptions = ["[Base]"], $categoryID = null)
    {
        $session = Gdn::session();
        $prefs = $session->getPreference("DismissedMessages", []);

        $category = null;
        if (!empty($categoryID)) {
            $category = CategoryModel::categories($categoryID);
        }

        $exceptions = array_map("strtolower", $exceptions);

        // Get the messages from the cache.
        $messages = self::messages();
        $messagesByID = array_column($messages, null, "MessageID");
        $result = [];
        foreach ($messagesByID as $messageID => $message) {
            if (in_array($messageID, $prefs) || !$message["Enabled"]) {
                continue;
            }

            $legacyLocationMap = $this->getLocationMap();
            $visible =
                strtolower($location) === strtolower($message["LayoutViewType"]) ||
                in_array(strtolower($message["LayoutViewType"]), $exceptions);

            $visible =
                $visible &&
                self::inCategory($categoryID, val("RecordID", $message), val("IncludeSubcategories", $message));
            if ($category !== null) {
                $visible &= CategoryModel::checkPermission($category, "Vanilla.Discussions.View");
            }

            if ($visible) {
                $result[] = $message;
            }
        }
        return $result;
    }

    /**
     * Returns a distinct array of controllers that have enabled messages.
     *
     * @return array Locations with enabled messages.
     */
    public function getEnabledLocations()
    {
        $data = $this->SQL
            ->select("LayoutViewType")
            ->from("Message")
            ->where("Enabled", 1)
            ->get()
            ->resultArray();

        return array_column($data, "LayoutViewType");
    }

    // Kludge back in static messages

    /**
     * Get all messages or one message.
     *
     * @param int|null $id ID of message to get.
     * @return array|null
     */
    public static function messages($id = null)
    {
        $messageModel = \Gdn::getContainer()->get(MessageModel::class);
        if (isset($id)) {
            return $messageModel->getID($id);
        } else {
            return $messageModel->getMessages([]);
        }
    }

    /**
     * Get messages (from the cache if possible).
     *
     * @param array $where
     * @return mixed
     */
    public function getMessages($where = [])
    {
        $messages = $this->modelCache->getCachedOrHydrate([__FUNCTION__, "where" => $where], function () use ($where) {
            return $this->getWhere($where)->resultArray();
        });
        return $messages;
    }

    /**
     * Get the layoutViewTypes that we have messages for.
     *
     * @return array
     */
    public function getActiveLayoutViewTypes(): array
    {
        $layoutViewTypes = $this->modelCache->getCachedOrHydrate([__FUNCTION__], function () {
            $result = $this->createSql()
                ->select("LayoutViewType")
                ->get("Message")
                ->column("LayoutViewType");
            $result = array_unique($result);
            return $result;
        });
        return $layoutViewTypes;
    }

    /**
     * Save a message.
     *
     * @param array $formPostValues Message data.
     * @param bool $settings
     * @return int|bool The MessageID or false on failure.
     */
    public function save($formPostValues, $settings = false)
    {
        Gdn::cache()->remove("Messages");

        $input = $this->normalizeInput($formPostValues);

        try {
            $this->getNormalizedInputSchema()->validate($input);
        } catch (\Garden\Schema\ValidationException $e) {
            $this->Validation->addResults($e);
            return false;
        }

        return parent::save($input, $settings);
    }

    /**
     * Normalize data for api output.
     *
     * @param array $data
     * @return array
     */
    public function normalizeOutput(array $data): array
    {
        $outputData = ArrayUtils::camelCase($data);
        $outputData["moderationMessageID"] = $outputData["messageID"];
        $outputData["body"] = $outputData["content"];
        $outputData["isDismissible"] = $outputData["allowDismiss"];
        $outputData["isEnabled"] = $outputData["enabled"];
        $outputData["includeDescendants"] = $outputData["includeSubcategories"];
        $outputData["viewLocation"] = strtolower($outputData["assetTarget"]);
        $outputData = $this->getOutputSchema()->validate($outputData);
        return $outputData;
    }

    /**
     * Normalize data for saving to the database.
     *
     * @param array $data
     * @return array
     */
    public function normalizeInput(array $data)
    {
        $inputData = ArrayUtils::pascalCase($data);

        if (isset($inputData["ModerationMessageID"])) {
            $inputData["MessageID"] = $inputData["ModerationMessageID"];
        }
        $inputData["Format"] = $inputData["Format"] ?? "text";
        $inputData["Content"] = $inputData["Body"] ?? $inputData["Content"];
        $inputData["AllowDismiss"] = intval($inputData["IsDismissible"] ?? $inputData["AllowDismiss"]);
        $inputData["Enabled"] = intval($inputData["IsEnabled"] ?? $inputData["Enabled"]);
        $inputData["IncludeSubcategories"] = intval(
            $inputData["IncludeDescendants"] ?? $inputData["IncludeSubcategories"]
        );
        $inputData["AssetTarget"] = ucfirst($inputData["ViewLocation"] ?? $inputData["AssetTarget"]);
        $inputData["LayoutViewType"] =
            $inputData["LayoutViewType"] ?? ($this->getLocationMap()[$inputData["Location"]] ?? "all");
        $inputData["RecordID"] = $inputData["RecordID"] ?? ($inputData["CategoryID"] ?? null);
        $inputData["RecordID"] = empty($inputData["RecordID"]) ? null : $inputData["RecordID"];
        $inputData["RecordType"] = $inputData["RecordType"] ?? !is_null($inputData["RecordID"]) ? "category" : null;

        return $inputData;
    }

    /**
     * Get the post schema.
     *
     * @return Schema
     */
    public function getPostSchema(): Schema
    {
        $schema = Schema::parse([
            "body:s",
            "format:s" => new \Vanilla\Models\FormatSchema(true),
            "isDismissible:b?" => ["default" => false],
            "isEnabled:b?" => ["default" => true],
            "recordType:s|n?" => [
                "enum" => ["category"],
                "default" => null,
            ],
            "recordID:i|n?" => ["default" => null], // add custom validator to check both recordType and recordID are there.
            "includeDescendants:b?" => ["default" => false],
            "sort:i?",
            "type:s?" => [
                "enum" => self::MESSAGE_TYPES,
                "default" => "casual",
            ],
            "viewLocation:s" => [
                "enum" => ["content", "panel"],
            ],
            "layoutViewType:s",
        ]);

        $schema->addValidator("", function ($data, \Garden\Schema\ValidationField $field): void {
            if ($data["recordID"] !== null && $data["recordType"] === null) {
                $field->addError("recordType is required when saving a recordID.", ["code" => 403]);
            }

            if ($data["recordType"] !== null && $data["recordID"] === null) {
                $field->addError("recordID is required when saving a recordType.", ["code" => 403]);
            }
        });

        return $schema;
    }

    /**
     * Get the schema for the api index query.
     *
     * @return Schema
     */
    public function getIndexSchema(): Schema
    {
        $schema = Schema::parse([
            "isEnabled:b|n" => ["default" => Gdn::session()->checkPermission("community.moderate") ? null : true],
            "recordID?" => RangeExpression::createSchema([":int"]),
            "type:s?" => [
                "enum" => ["casual", "info", "alert", "warning"],
            ],
            "layoutViewType:s?" => [
                "enum" => $this->getLayoutViewTypes(),
            ],
            "recordType:s?" => ["enum" => ["category"]],
        ]);

        $schema->addValidator("isEnabled", function ($data, \Garden\Schema\ValidationField $field): void {
            if (
                isset($data["isEnabled"]) &&
                $data["isEnabled"] === false &&
                !Gdn::session()->checkPermission("community.moderate")
            ) {
                $this->addError("You need the 'community.moderate' permission to view disabled messages.");
            }
        });

        return $schema;
    }

    /**
     * Get the output schema.
     *
     * @return Schema
     */
    public function getOutputSchema(): Schema
    {
        return Schema::parse([
            "moderationMessageID:i",
            "body:s",
            "format:s|n",
            "isDismissible:b",
            "isEnabled:b",
            "recordType:s|n" => [
                "enum" => ["category"],
            ],
            "recordID:i|n",
            "includeDescendants:b?",
            "sort:i?",
            "type:s?",
            "viewLocation:s" => [
                "enum" => ["content", "panel"],
            ],
            "layoutViewType:s?",
        ]);
    }

    /**
     * Get the input schema.
     *
     * @return Schema
     */
    public function getNormalizedInputSchema(): Schema
    {
        return Schema::parse([
            "MessageID:i?",
            "Content:s",
            "Format:s?",
            "AllowDismiss:i",
            "Enabled:i",
            "RecordID:i|n?",
            "RecordType:s|n?",
            "IncludeSubcategories:i",
            "AssetTarget:s",
            "LayoutViewType:s",
            "Type:s",
        ]);
    }

    /**
     * Get all the registered legacy layout views.
     *
     * @return array
     */
    public function getLegacyLayoutViews()
    {
        return $this->layoutService->getLegacyLayoutViews();
    }

    /**
     * Get all available layout view types.
     *
     * @return array
     */
    public function getLayoutViewTypes()
    {
        $viewTypes = ["all"];
        foreach ($this->layoutService->getLayoutViews() as $view) {
            $viewTypes[] = $view->getType();
        }
        return $viewTypes;
    }

    /**
     * Get an array mapping the old locations to the new locations.
     *
     * @return string[]
     */
    public function getLocationMap(): array
    {
        $layoutViews = $this->layoutService->getLegacyLayoutViews();
        $map = ["[Base]" => "all", "[NonAdmin]" => "all"];
        foreach ($layoutViews as $view) {
            $map[$view->getLegacyType()] = $view->getType();
        }
        return $map;
    }
}
