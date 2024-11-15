<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Models;

use Garden\EventManager;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\ClientException;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\FeatureFlagHelper;
use Vanilla\Models\PipelineModel;

class PostTypeModel extends PipelineModel
{
    const FEATURE_POST_TYPES_AND_POST_FIELDS = "PostTypesAndPostFields";

    /**
     * D.I.
     */
    public function __construct(private EventManager $eventManager)
    {
        parent::__construct("postType");

        $this->addPipelineProcessor(new CurrentDateFieldProcessor(["dateInserted", "dateUpdated"], ["dateUpdated"]));
        $this->addPipelineProcessor(new BooleanFieldProcessor(["isOriginal", "isActive", "isDeleted"]));
        $userProcessor = new CurrentUserFieldProcessor(\Gdn::session());
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Get base query for querying post types.
     *
     * @param array $where
     * @return \Gdn_SQLDriver
     */
    private function getWhereQuery(array $where)
    {
        $sql = $this->createSql()
            ->from($this->getTable())
            ->where($where);

        return $sql;
    }

    /**
     * Query post types with filters.
     *
     * @param array $where
     * @param array $options
     * @return array|null
     * @throws \Exception
     */
    public function getWhere(array $where, array $options = [])
    {
        $sql = $this->getWhereQuery($where);

        $sql->applyModelOptions($options);

        $rows = $sql->get()->resultArray();
        return $rows;
    }

    /**
     * Query post type count with filters.
     *
     * @param array $where
     * @return int
     */
    public function getWhereCount(array $where): int
    {
        return $this->getWhereQuery($where)->getPagingCount("postTypeID");
    }

    /**
     * Returns the schema for displaying post types.
     *
     * @return Schema
     */
    public function outputSchema(): Schema
    {
        return Schema::parse([
            "postTypeID",
            "name",
            "parentPostTypeID",
            "isOriginal",
            "isActive",
            "isDeleted",
            "dateInserted",
            "dateUpdated",
            "insertUserID",
            "updateUserID",
        ]);
    }

    /**
     * Returns the schema for creating post types.
     *
     * @return Schema
     */
    public function postSchema(): Schema
    {
        $schema = Schema::parse(["postTypeID:s", "parentPostTypeID:s"])
            ->merge($this->patchSchema())
            ->addValidator("postTypeID", function ($postTypeID, ValidationField $field) {
                if (preg_match("#[.\s/|A-Z]#", $postTypeID)) {
                    $field->addError("Whitespace, slashes, periods and uppercase letters are not allowed");
                    return Invalid::value();
                }
            })
            ->addValidator("postTypeID", $this->createUniquePostTypeValidator())
            ->addValidator("parentPostTypeID", function ($value, ValidationField $field) {
                $postType = $this->select(["postTypeID" => $value, "isOriginal" => true], [self::OPT_LIMIT => 1]);

                if (empty($postType)) {
                    $field->addError("The selected parent post type does not exist");
                    return Invalid::value();
                }
            });

        return $schema;
    }

    /**
     * Returns the schema for updating post types.
     *
     * @return Schema
     */
    public function patchSchema(): Schema
    {
        $schema = Schema::parse([
            "name:s",
            "isActive:b?" => ["default" => false],
            "isDeleted:b?" => ["default" => false],
        ]);
        return $schema;
    }

    /**
     * Validator that checks if the table already contains a record with the given field value.
     *
     * @return callable
     */
    public function createUniquePostTypeValidator(): callable
    {
        return function ($value, ValidationField $field) {
            $postType = $this->select(["postTypeID" => $value], [self::OPT_LIMIT => 1])[0] ?? null;

            if (!empty($postType)) {
                $field->addError(
                    $postType["isDeleted"]
                        ? "This identifier is already used by a deleted post type."
                        : "This identifier is already used. Use a unique identifier."
                );
                return Invalid::value();
            }
        };
    }

    /**
     * Create an initial post type if it doesn't exist.
     *
     * @param array $row
     *
     * @return void
     */
    public function createInitialPostType(array $row): void
    {
        $hasExisting = $this->createSql()->getCount($this->getTable(), ["postTypeID" => $row["postTypeID"]]) > 0;
        if ($hasExisting) {
            return;
        }
        $this->insert($row);
    }

    /**
     * Structures the postType table.
     *
     * @param \Gdn_MySQLStructure $structure
     * @return void
     */
    public static function structure(\Gdn_DatabaseStructure $structure)
    {
        $structure
            ->table("postType")
            ->primaryKey("postTypeID", "varchar(100)", false)
            ->column("name", "varchar(100)")
            ->column("parentPostTypeID", "varchar(100)", true, "index")
            ->column("isOriginal", "tinyint", 0)
            ->column("isActive", "tinyint", 0)
            ->column("isDeleted", "tinyint", 0)
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime", true)
            ->column("insertUserID", "int")
            ->column("updateUserID", "int", true)
            ->set(true);

        // Add default post types.
        if (!$structure->CaptureOnly) {
            self::createInitialPostTypes();
        }
    }

    /**
     * Create initial post types.
     *
     * @return void
     */
    private static function createInitialPostTypes(): void
    {
        $postTypeModel = \Gdn::getContainer()->get(PostTypeModel::class);
        $addonManager = \Gdn::getContainer()->get(AddonManager::class);
        $postTypeModel->createInitialPostType([
            "postTypeID" => "discussion",
            "name" => "Discussion",
            "isOriginal" => true,
            "isActive" => true,
        ]);
        $postTypeModel->createInitialPostType([
            "postTypeID" => "question",
            "name" => "Question",
            "isOriginal" => true,
            "isActive" => $addonManager->isEnabled("qna", Addon::TYPE_ADDON),
        ]);
        $postTypeModel->createInitialPostType([
            "postTypeID" => "idea",
            "name" => "Idea",
            "isOriginal" => true,
            "isActive" => $addonManager->isEnabled("ideation", Addon::TYPE_ADDON),
        ]);
        $postTypeModel->createInitialPostType([
            "postTypeID" => "poll",
            "name" => "Poll",
            "isOriginal" => true,
            "isActive" => $addonManager->isEnabled("polls", Addon::TYPE_ADDON),
        ]);
        $postTypeModel->createInitialPostType([
            "postTypeID" => "event",
            "name" => "Event",
            "isOriginal" => true,
            "isActive" => $addonManager->isEnabled("groups", Addon::TYPE_ADDON),
        ]);
    }

    /**
     * Checks if the post types feature is enabled.
     *
     * @return void
     * @throws ClientException
     */
    public static function ensurePostTypesFeatureEnabled()
    {
        if (!FeatureFlagHelper::featureEnabled(self::FEATURE_POST_TYPES_AND_POST_FIELDS)) {
            throw new ClientException("Post Types & Post Fields is not enabled.");
        }
    }
}
